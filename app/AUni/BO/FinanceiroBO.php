<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\UsersTable;
use App\Models\Clientes;
use App\Models\Filiais;
use DateTime;

class FinanceiroBO {

    private $logger;
    private $db;
    private $minutosCache = 15;
    private $hoje;

    public function __construct(ILogger $logger, IDB $db) {

        $this->logger = $logger;
        $this->db = $db;
        $this->hoje = new DateTime('today');
    }

    public function getDashboardFaturasPagar($mes, $atualizar = false) {

        $this->logger->info('FinanceiroBO->getDashboardFaturasPagar', [$mes, $atualizar]);

        $inicioMes = DateTime::createFromFormat('!Y-m', (string) $mes);
        if (!$inicioMes) {
            $inicioMes = new DateTime('first day of this month 00:00:00');
        }

        $fimMes = (clone $inicioMes)->modify('last day of this month');

        $cliente = $this->getClienteAtual();

        $dados = [
            'mes' => $inicioMes->format('Y-m'),
            'dataReferencia' => $this->hoje->format('d/m/Y'),
            'empresa' => $cliente ? $cliente->nome : null,
            'filial' => $cliente ? $cliente->filial : null,
            'aviso' => null,
        ];

        if ($cliente === 0) {
            $dados['aviso'] = 'Selecione uma empresa para ver o dashboard financeiro.';
            return $dados;
        }

        if (!$cliente || empty($cliente->filial)) {
            $dados['aviso'] = 'Esta empresa não possui filial cadastrada. Informe a filial no cadastro do cliente.';
            return $dados;
        }

        $megaBO = new MegaBO($this->logger, $this->db);

        $parcelasMes = $this->buscarParcelas($megaBO, $cliente->filial, $inicioMes, $fimMes, $atualizar);
        if ($parcelasMes === false) {
            return false;
        }

        $mesAtual = array_map(fn($p) => $this->montarParcela($p), $parcelasMes);

        $dados['resumo'] = $this->montarResumo($mesAtual);
        $dados['porDia'] = $this->montarPorDia($mesAtual, $inicioMes, $fimMes);
        $dados['porTipoDocumento'] = $this->agruparPor($mesAtual, 'tipoDocumento');
        $dados['porFornecedor'] = array_slice($this->agruparPor($mesAtual, 'fornecedor'), 0, 10);
        $dados['vencidosPorAtraso'] = $this->montarPorAtraso($mesAtual);
        $dados['parcelas'] = $mesAtual;

        return $dados;
    }

    public function getFiltrosFluxoCaixa($filial = null) {

        $this->logger->info('FinanceiroBO->getFiltrosFluxoCaixa', [$filial]);

        $isUniUser = $this->isUniUser();
        $cliente = $this->getClienteAtual();
        $filialPadrao = $cliente ? $cliente->filial : null;
        $filialConsulta = ($isUniUser && !empty($filial)) ? (int) $filial : $filialPadrao;

        $filialCadastro = empty($filialConsulta) ? null : Filiais::where('codigo', $filialConsulta)->first();

        $dados = [
            'isUniUser' => $isUniUser,
            'filial' => $filialConsulta,
            'filialNome' => $filialCadastro ? $filialCadastro->nome : null,
            'contasReceber' => ($filialCadastro ? $filialCadastro->contas_receber : null) ?: 'CRR',
            'empreendimentos' => $filialCadastro ? (new FilialBO($this->logger, $this->db))->getEmpreendimentos($filialCadastro->id) : [],
            'filiais' => $isUniUser ? (new FilialBO($this->logger, $this->db))->getFiliais() : [],
            'aviso' => null,
            'receber' => [],
            'pagar' => [],
            'receberNaoConsiderados' => [],
            'pagarNaoConsiderados' => [],
            'totalReceber' => 0,
            'totalPagar' => 0,
        ];

        if (empty($filialConsulta)) {
            $dados['aviso'] = $isUniUser ? 'Selecione uma filial para ver o fluxo de caixa.' : 'Sua empresa não possui filial cadastrada.';
        }

        return $dados;
    }

    public function getFluxoCaixa($dataInicial, $dataFinal, $filial, $idEmpreendimento = null) {

        $this->logger->info('FinanceiroBO->getFluxoCaixa', [$dataInicial, $dataFinal, $filial, $idEmpreendimento]);

        $inicio = DateTime::createFromFormat('!Y-m-d', (string) $dataInicial) ?: new DateTime('first day of this month 00:00:00');
        $fim = DateTime::createFromFormat('!Y-m-d', (string) $dataFinal) ?: new DateTime('last day of this month 00:00:00');

        $dados = $this->getFiltrosFluxoCaixa($filial);
        $filialConsulta = $dados['filial'];

        if (empty($filialConsulta)) {
            return $dados;
        }

        if ($inicio > $fim) {
            $dados['aviso'] = 'A data inicial não pode ser maior que a data final.';
            return $dados;
        }

        $megaBO = new MegaBO($this->logger, $this->db);

        $usaContasReceber = in_array($dados['contasReceber'], ['COR', 'CRR']);
        $usaCarteira = in_array($dados['contasReceber'], ['CAR', 'CRR']);
        $inicioReceber = (clone $inicio)->modify('-1 day');

        $parcelasReceber = [];
        if ($usaContasReceber) {
            $parcelasReceber = $megaBO->getFaturasReceberPorFilial($filialConsulta, $inicioReceber->format('Y-m-d'), $fim->format('Y-m-d'));
            if ($parcelasReceber === false) {
                return false;
            }
        }

        $parcelasPagar = $megaBO->getFaturasPagarPorFilial($filialConsulta, $inicio->format('Y-m-d'), $fim->format('Y-m-d'));
        if ($parcelasPagar === false) {
            return false;
        }

        $carteira = ['contratos' => [], 'parcelas' => []];
        if ($usaCarteira) {
            $codigosEmpreendimentos = array_column($dados['empreendimentos'], 'codigo');

            if (empty($idEmpreendimento)) {
                $dados['aviso'] = 'Selecione um empreendimento para consultar a carteira de recebíveis.';
            } elseif (!in_array((int) $idEmpreendimento, $codigosEmpreendimentos, true)) {
                $dados['aviso'] = 'O empreendimento selecionado não pertence a esta filial.';
            } else {
                $resultadoCarteira = $this->buscarCarteira($megaBO, $filialConsulta, $idEmpreendimento);
                if ($resultadoCarteira === false) {
                    $dados['aviso'] = 'Não foi possível consultar a carteira de contratos. Os valores abaixo não incluem as parcelas dos contratos.';
                } else {
                    $carteira = $resultadoCarteira;
                }
            }
        }

        $titulosReceber = $this->montarFluxo($parcelasReceber ?: []);
        $parcelasCarteira = $this->montarCarteira($carteira, $inicioReceber, $fim);
        $titulosPagar = $this->montarFluxo($parcelasPagar ?: []);

        $dados['receber'] = $this->ordenarPorDia(array_merge($titulosReceber['considerados'], $parcelasCarteira['considerados']));
        $dados['receberNaoConsiderados'] = $this->ordenarPorDia(array_merge($titulosReceber['naoConsiderados'], $parcelasCarteira['naoConsiderados']));
        $dados['pagar'] = $titulosPagar['considerados'];
        $dados['pagarNaoConsiderados'] = $titulosPagar['naoConsiderados'];
        $dados['totalReceber'] = round(array_sum(array_column($dados['receber'], 'valor')), 2);
        $dados['totalPagar'] = round(array_sum(array_column($dados['pagar'], 'valor')), 2);

        return $dados;
    }

    private function isUniUser() {

        $usersTable = UsersTable::query()
            ->where('user_id', Auth::user()->user_id)
            ->first();

        return $usersTable && $usersTable->is_uni_user === 'S';
    }

    private function getClienteAtual() {

        $usersTable = UsersTable::query()
            ->where('user_id', Auth::user()->user_id)
            ->first();

        if (!$usersTable) {
            return null;
        }

        $isUniUser = $usersTable->is_uni_user === 'S';
        $activeCompanyId = $usersTable->active_company_id;

        if ($isUniUser && $activeCompanyId !== null && (int) $activeCompanyId === 0) {
            return 0;
        }

        $companyId = ($isUniUser && $activeCompanyId !== null) ? $activeCompanyId : $usersTable->default_company_id;

        return Clientes::find($companyId);
    }

    private function buscarParcelas(MegaBO $megaBO, $filial, DateTime $inicio, DateTime $fim, $atualizar) {

        $chave = 'mega_faturas_pagar_filial_' . $filial . '_' . $inicio->format('Ymd') . '_' . $fim->format('Ymd');

        if (!$atualizar && Cache::has($chave)) {
            return Cache::get($chave);
        }

        $res = $megaBO->getFaturasPagarPorFilial($filial, $inicio->format('Y-m-d'), $fim->format('Y-m-d'));
        if ($res === false) {
            return false;
        }

        $parcelas = is_array($res) ? $res : [];
        Cache::put($chave, $parcelas, now()->addMinutes($this->minutosCache));

        return $parcelas;
    }

    private function buscarCarteira(MegaBO $megaBO, $filial, $idEmpreendimento) {

        $carteira = ['contratos' => [], 'parcelas' => []];

        if (empty($idEmpreendimento)) {
            return $carteira;
        }

        $res = $megaBO->getContratosPorEmpreendimento((int) $idEmpreendimento);
        if ($res === false) {
            return false;
        }

        foreach ($res ?: [] as $contrato) {
            if ((int) ($contrato->cod_filial ?? 0) !== (int) $filial) {
                continue;
            }

            $carteira['contratos'][$contrato->cod_contrato] = [
                'cliente' => trim((string) ($contrato->nome_cliente ?? '')),
                'bloco' => $contrato->nome_bloco ?? '',
                'unidade' => $contrato->nome_unidade ?? '',
                'status' => $contrato->status_contrato ?? '',
            ];
        }

        if (empty($carteira['contratos'])) {
            return $carteira;
        }

        $parcelas = $megaBO->getParcelasPorContratos(array_keys($carteira['contratos']));
        if ($parcelas === false) {
            return false;
        }

        $carteira['parcelas'] = $parcelas;

        return $carteira;
    }

    private function montarCarteira($carteira, DateTime $inicio, DateTime $fim) {

        $resultado = ['considerados' => [], 'naoConsiderados' => []];

        foreach ($carteira['parcelas'] as $p) {
            $vencimento = DateTime::createFromFormat('!d/m/Y', (string) ($p->data_vencimento ?? ''));
            if (!$vencimento || $vencimento < $inicio || $vencimento > $fim) {
                continue;
            }

            $contrato = $carteira['contratos'][$p->cod_contrato ?? 0] ?? [];
            $motivo = $this->motivoParcelaCarteira($p, $contrato);

            $item = [
                'descricao' => 'Contrato ' . ($p->cod_contrato ?? '') . ' - Parcela ' . ($p->cod_parcela ?? '') . ' - ' . ($p->tipo_parcela ?? ''),
                'vencimento' => $vencimento->format('d/m/Y'),
                'dia' => $vencimento->format('Y-m-d'),
                'valor' => round((float) ($p->vlr_corrigido ?? 0), 2),
                'detalhe' => [
                    'origem' => 'Contrato',
                    'documento' => 'Contrato ' . ($p->cod_contrato ?? ''),
                    'parcela' => $p->cod_parcela ?? '',
                    'sequencia' => $p->sequencia ?? '',
                    'tipo' => $p->tipo_parcela ?? '',
                    'cliente' => $contrato['cliente'] ?? '',
                    'bloco' => $contrato['bloco'] ?? '',
                    'unidade' => $contrato['unidade'] ?? '',
                    'statusContrato' => $contrato['status'] ?? '',
                    'statusParcela' => $p->status_parcela ?? '',
                    'situacao' => $p->situacao ?? '',
                    'vencimento' => $p->data_vencimento ?? '',
                    'valorOriginal' => $p->vlr_original ?? null,
                    'valorPresente' => $p->vlr_presente ?? null,
                    'multa' => $p->vlr_multa ?? null,
                    'juros' => $p->vlr_juros ?? null,
                    'valorPago' => $p->vlr_pago ?? null,
                    'dataBaixa' => $p->data_baixa ?? '',
                ],
            ];

            if ($motivo === null) {
                $resultado['considerados'][] = $item;
            } else {
                $item['motivo'] = $motivo;
                $resultado['naoConsiderados'][] = $item;
            }
        }

        return $resultado;
    }

    private function motivoParcelaCarteira($p, $contrato) {

        if (($p->situacao ?? '') === 'Pago' || !empty($p->data_baixa)) {
            return 'Parcela paga' . (empty($p->data_baixa) ? '' : ' em ' . $p->data_baixa);
        }

        $status = $contrato['status'] ?? '';
        if (in_array($status, ['Quitado', 'Distratado'])) {
            return 'Contrato ' . mb_strtolower($status);
        }

        if (($p->status_parcela ?? '') !== 'Ativo') {
            return 'Parcela com status ' . (($p->status_parcela ?? '') ?: 'não informado');
        }

        return null;
    }

    private function ordenarPorDia($itens) {

        usort($itens, fn($a, $b) => $a['dia'] <=> $b['dia']);

        return $itens;
    }

    private function montarFluxo($parcelas) {

        $resultado = ['considerados' => [], 'naoConsiderados' => []];

        foreach ($parcelas as $p) {
            $saldo = round((float) ($p->SaldoAtual ?? 0), 2);

            $data = ($p->DataProrrogado ?? null) ?: ($p->DataVencimento ?? '');
            $vencimento = DateTime::createFromFormat('!d/m/Y', (string) $data);

            $agente = $p->Agente ?? null;
            $nome = $agente ? trim(($agente->NomeFantasia ?? null) ?: ($agente->Nome ?? '')) : '';
            $documento = trim(($p->TipoDocumento ?? '') . ' ' . ($p->NumeroDocumento ?? ''));

            $item = [
                'descricao' => implode(' - ', array_filter([$documento, $nome])) ?: 'Não informado',
                'vencimento' => $vencimento ? $vencimento->format('d/m/Y') : null,
                'dia' => $vencimento ? $vencimento->format('Y-m-d') : '',
                'valor' => $saldo,
                'detalhe' => [
                    'origem' => 'Título',
                    'documento' => $documento,
                    'numeroAP' => $p->NumeroAP ?? '',
                    'parcela' => $p->NumeroParcela ?? '',
                    'agenteCodigo' => $agente->Codigo ?? '',
                    'agenteNome' => $agente->Nome ?? '',
                    'nomeFantasia' => $agente->NomeFantasia ?? '',
                    'cnpj' => $agente->Cnpj ?? '',
                    'dataVencimento' => $p->DataVencimento ?? '',
                    'valorParcela' => $p->ValorParcela ?? null,
                    'saldo' => $saldo,
                ],
            ];

            if ($saldo > 0) {
                $resultado['considerados'][] = $item;
            } else {
                $item['motivo'] = 'Título pago (saldo zero)';
                $resultado['naoConsiderados'][] = $item;
            }
        }

        return [
            'considerados' => $this->ordenarPorDia($resultado['considerados']),
            'naoConsiderados' => $this->ordenarPorDia($resultado['naoConsiderados']),
        ];
    }

    private function montarParcela($p) {

        $valor = round((float) ($p->ValorParcela ?? 0), 2);
        $saldo = round((float) ($p->SaldoAtual ?? 0), 2);

        $vencimento = DateTime::createFromFormat('!d/m/Y', (string) ($p->DataProrrogado ?: $p->DataVencimento));

        $diasAtraso = 0;
        if ($saldo <= 0) {
            $situacao = 'paga';
        } elseif ($vencimento && $vencimento < $this->hoje) {
            $situacao = 'vencida';
            $diasAtraso = (int) $vencimento->diff($this->hoje)->days;
        } else {
            $situacao = 'aVencer';
        }

        $agente = $p->Agente ?? null;
        $fornecedor = $agente ? trim($agente->NomeFantasia ?: $agente->Nome) : 'Não informado';

        return [
            'numeroAP' => $p->NumeroAP ?? null,
            'fornecedor' => $fornecedor !== '' ? $fornecedor : 'Não informado',
            'cnpj' => $this->formatarCnpj($agente->Cnpj ?? null),
            'tipoDocumento' => trim($p->TipoDocumento ?? '') ?: 'Não informado',
            'numeroDocumento' => $p->NumeroDocumento ?? null,
            'parcela' => $p->NumeroParcela ?? null,
            'vencimento' => $vencimento ? $vencimento->format('d/m/Y') : null,
            'dia' => $vencimento ? $vencimento->format('Y-m-d') : null,
            'valor' => $valor,
            'saldo' => $saldo,
            'pago' => round($valor - $saldo, 2),
            'situacao' => $situacao,
            'parcial' => $saldo > 0 && $saldo < $valor,
            'diasAtraso' => $diasAtraso,
        ];
    }

    private function formatarCnpj($cnpj) {

        $digitos = preg_replace('/\D/', '', (string) $cnpj);

        if (strlen($digitos) === 14) {
            return vsprintf('%s.%s.%s/%s-%s', [substr($digitos, 0, 2), substr($digitos, 2, 3), substr($digitos, 5, 3), substr($digitos, 8, 4), substr($digitos, 12, 2)]);
        }

        if (strlen($digitos) === 11) {
            return vsprintf('%s.%s.%s-%s', [substr($digitos, 0, 3), substr($digitos, 3, 3), substr($digitos, 6, 3), substr($digitos, 9, 2)]);
        }

        return null;
    }

    private function montarResumo($mesAtual) {

        $resumo = [
            'total' => ['valor' => 0, 'quantidade' => 0],
            'pago' => ['valor' => 0, 'quantidade' => 0],
            'aVencer' => ['valor' => 0, 'quantidade' => 0],
            'vencido' => ['valor' => 0, 'quantidade' => 0],
            'parcial' => ['valor' => 0, 'quantidade' => 0, 'baixado' => 0],
        ];

        foreach ($mesAtual as $p) {
            $resumo['total']['valor'] += $p['valor'];
            $resumo['total']['quantidade']++;
            $resumo['pago']['valor'] += $p['pago'];

            if ($p['situacao'] === 'paga') {
                $resumo['pago']['quantidade']++;
            } else {
                $resumo[$p['situacao'] === 'vencida' ? 'vencido' : 'aVencer']['valor'] += $p['saldo'];
                $resumo[$p['situacao'] === 'vencida' ? 'vencido' : 'aVencer']['quantidade']++;
            }

            if ($p['parcial']) {
                $resumo['parcial']['valor'] += $p['saldo'];
                $resumo['parcial']['baixado'] += $p['pago'];
                $resumo['parcial']['quantidade']++;
            }
        }

        foreach ($resumo as $k => $item) {
            $resumo[$k]['valor'] = round($item['valor'], 2);
        }

        $resumo['parcial']['baixado'] = round($resumo['parcial']['baixado'], 2);

        return $resumo;
    }

    private function montarPorDia($mesAtual, DateTime $inicioMes, DateTime $fimMes) {

        $dias = [];
        for ($d = clone $inicioMes; $d <= $fimMes; $d->modify('+1 day')) {
            $dias[$d->format('Y-m-d')] = ['dia' => $d->format('d'), 'pago' => 0, 'aVencer' => 0, 'vencido' => 0];
        }

        foreach ($mesAtual as $p) {
            if (!isset($dias[$p['dia']])) {
                continue;
            }

            $dias[$p['dia']]['pago'] += $p['pago'];

            if ($p['situacao'] === 'vencida') {
                $dias[$p['dia']]['vencido'] += $p['saldo'];
            } elseif ($p['situacao'] === 'aVencer') {
                $dias[$p['dia']]['aVencer'] += $p['saldo'];
            }
        }

        return array_values(array_map(fn($d) => [
            'dia' => $d['dia'],
            'pago' => round($d['pago'], 2),
            'aVencer' => round($d['aVencer'], 2),
            'vencido' => round($d['vencido'], 2),
        ], $dias));
    }

    private function agruparPor($parcelas, $campo) {

        $grupos = [];
        foreach ($parcelas as $p) {
            $nome = $p[$campo];
            if (!isset($grupos[$nome])) {
                $grupos[$nome] = ['nome' => $nome, 'valor' => 0, 'quantidade' => 0];
            }
            $grupos[$nome]['valor'] += $p['valor'];
            $grupos[$nome]['quantidade']++;
        }

        usort($grupos, fn($a, $b) => $b['valor'] <=> $a['valor']);

        return array_map(fn($g) => array_merge($g, ['valor' => round($g['valor'], 2)]), $grupos);
    }

    private function montarPorAtraso($parcelas) {

        $faixas = [
            ['nome' => '1 a 15 dias', 'ate' => 15, 'valor' => 0, 'quantidade' => 0],
            ['nome' => '16 a 30 dias', 'ate' => 30, 'valor' => 0, 'quantidade' => 0],
            ['nome' => '31 a 60 dias', 'ate' => 60, 'valor' => 0, 'quantidade' => 0],
            ['nome' => '61 a 90 dias', 'ate' => 90, 'valor' => 0, 'quantidade' => 0],
            ['nome' => 'Mais de 90 dias', 'ate' => PHP_INT_MAX, 'valor' => 0, 'quantidade' => 0],
        ];

        foreach ($parcelas as $p) {
            if ($p['situacao'] !== 'vencida') {
                continue;
            }

            foreach ($faixas as $i => $f) {
                if ($p['diasAtraso'] <= $f['ate']) {
                    $faixas[$i]['valor'] += $p['saldo'];
                    $faixas[$i]['quantidade']++;
                    break;
                }
            }
        }

        return array_map(fn($f) => ['nome' => $f['nome'], 'valor' => round($f['valor'], 2), 'quantidade' => $f['quantidade']], $faixas);
    }
}
