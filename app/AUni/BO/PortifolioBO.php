<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\UsersTable;
use App\Models\Viabilidades;
use App\Models\Empreendimentos;
use App\Models\Clientes;


class PortifolioBO {

    private $logger;
    private $db;
    

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getPortfolio() {

        $userId = Auth::user()->user_id;

        $usersTable = UsersTable::Query()
        ->where('user_id', $userId)
        ->first();

        if ($usersTable) {
            $isUniUser = $usersTable->is_uni_user === 'S';
            $activeCompanyId = $usersTable->active_company_id;
            $data = [
                'userId'  => $usersTable->user_id,
                'company' => ($isUniUser && $activeCompanyId !== null)
                    ? $activeCompanyId
                    : $usersTable->default_company_id
            ];
        } else {
            $data = [
                'userId'  => 0,
                'company' => 0
            ];
        }
        
        return $data;
    }

    public function getDashboard() {

        $this->logger->info("PortifolioBO->getDashboard");

        $userId = Auth::user()->user_id;

        $usersTable = UsersTable::Query()
            ->where('user_id', $userId)
            ->first();

        if (!$usersTable) return [];

        $isUniUser = $usersTable->is_uni_user === 'S';
        $activeCompanyId = $usersTable->active_company_id;

        // Todas as empresas (active_company_id = 0, somente uni users)
        if ($isUniUser && $activeCompanyId === 0) {
            $empreendimentos = Empreendimentos::query()
                ->join('clientes', 'clientes.id', '=', 'empreendimentos.cliente_id')
                ->orderBy('clientes.nome')
                ->select('empreendimentos.*', 'clientes.nome as cliente_nome')
                ->get();
        } else {
            $companyId = ($isUniUser && $activeCompanyId)
                ? $activeCompanyId
                : $usersTable->default_company_id;

            $clientes = Clientes::find($companyId);
            if (!$clientes) return [];

            $empreendimentos = Empreendimentos::query()
                ->where('cliente_id', $clientes->id)
                ->get();
        }

        $isTodas = $isUniUser && $activeCompanyId !== null && (int)$activeCompanyId === 0;
        $result  = [];

        foreach ($empreendimentos as $emp) {

            $viabilidade = Viabilidades::query()
                ->where('empreendimento_id', $emp->id)
                ->where('status', 'A')
                ->first();

            $lancamento = $emp->previsao_lancamento
                ? substr($emp->previsao_lancamento, 0, 4)
                : null;

            $result[] = [
                'empreendimentoId' => $emp->id,
                'clienteId'        => $emp->cliente_id,
                'clienteNome'      => $emp->cliente_nome ?? null,
                'isTodas'          => $isTodas,
                'nome'             => $emp->nome,
                'uf'               => $emp->uf,
                'faseAtual'        => $emp->fase_atual,
                'totalUnidades'    => (int)($emp->total_unidades ?? 0),
                'anoLancamento'    => $lancamento,
                'vgv'              => $viabilidade ? (float)$viabilidade->vgv : 0,
                'areaTotalM2'      => $viabilidade ? (float)$viabilidade->area_total_m2 : 0,
            ];
        }

        return $result;
    }

    public function getPortfolioView() {

        $this->logger->info("PortifolioBO->getPortfolioView");

        $porfilioView = array();

        $userId = Auth::user()->user_id;

        $usersTable = UsersTable::Query()
        ->where('user_id', $userId)
        ->first();

        if ($usersTable) {

            $isUniUser = $usersTable->is_uni_user === 'S';
            $activeCompanyId = $usersTable->active_company_id;

            // Todas as empresas (active_company_id = 0, somente uni users)
            if ($isUniUser && $activeCompanyId === 0) {
                $empreendimentos = Empreendimentos::query()
                    ->join('clientes', 'clientes.id', '=', 'empreendimentos.cliente_id')
                    ->orderBy('empreendimentos.cliente_id')
                    ->orderBy('empreendimentos.id')
                    ->select('empreendimentos.*', 'clientes.nome as cliente_nome')
                    ->get();
            } else {
                $companyId = ($isUniUser && $activeCompanyId)
                    ? $activeCompanyId
                    : $usersTable->default_company_id;

                $clientes = Clientes::find($companyId);
                if (!$clientes) return $porfilioView;

                $empreendimentos = Empreendimentos::query()
                    ->where('cliente_id', $clientes->id)
                    ->get();
            }

            $isTodas = $isUniUser && $activeCompanyId !== null && (int)$activeCompanyId === 0;

            foreach ($empreendimentos as $empreendimento) {

                    $viabilidade = Viabilidades::query()
                    ->where('empreendimento_id', $empreendimento->id)
                    ->where('status', 'A')
                    ->first();

                    $porfilioView[] = [
                        'empreendimentoId'           => $empreendimento->id,
                        'viabilidadeId'              => $viabilidade?->id,
                        'clienteId'                  => $empreendimento->cliente_id,
                        'clienteNome'                => $empreendimento->cliente_nome ?? null,
                        'isTodas'                    => $isTodas,
                        'nome'                       => $empreendimento->nome,
                        'cidade'                     => $empreendimento->cidade,
                        'uf'                         => $empreendimento->uf,
                        'tipoProduto'                => $empreendimento->tipo_produto,
                        'totalUnidades'              => $empreendimento->total_unidades,
                        'unidadesPermutadas'         => $empreendimento->unidades_permutadas,
                        'unidadesVenda'              => $viabilidade?->unidades_venda,
                        'valorAquisicaoTerreno'      => $viabilidade?->valor_aquisicao_terreno,
                        'percentualAquisicaoTerreno' => $viabilidade?->percentual_aquisicao_terreno,
                        'percentualPermutaFisica'    => $viabilidade?->percentual_permuta_fisica,
                        'areaTotalM2'                => $viabilidade?->area_total_m2,
                        'valorPrevisto'              => $viabilidade?->valor_previsto,
                        'vgv'                        => $viabilidade?->vgv,
                        'exposicaoCaixa'             => $viabilidade?->exposicao_caixa,
                        'resultadoLiquido'           => $viabilidade?->resultado_liquido,
                        'participacaoResultados'     => $empreendimento->participacao_resultados,
                        'percentualResultadoLiquido' => $empreendimento->percentual_resultado_liquido,
                        'tirAa'                      => $empreendimento->tir_aa,
                        'mtir'                       => $empreendimento->mtir,
                        'percentualCustoObra'        => $viabilidade?->percentual_custo_obra,
                        'valorCustoObra'             => $viabilidade?->valor_custo_obra,
                        'percentualComissao'         => $viabilidade?->percentual_comissao,
                        'valorComissao'              => $viabilidade?->valor_comissao,
                        'percentualTributo'          => $viabilidade?->percentual_tributo,
                        'valorTributo'               => $viabilidade?->valor_tributo,
                        'percentualIncorporacao'     => $viabilidade?->percentual_incorporacao,
                        'valorIncorporacao'          => $viabilidade?->valor_incorporacao,
                        'percentualMarketing'        => $viabilidade?->percentual_marketing,
                        'valorMarketing'             => $viabilidade?->valor_marketing,
                        'percentualDespesaObra'      => $viabilidade?->percentual_despesa_obra,
                        'valorDespesaObra'           => $viabilidade?->valor_despesa_obra,
                        'percentualDespesaVenda'     => $viabilidade?->percentual_despesa_venda,
                        'valorDespesaVenda'          => $viabilidade?->valor_despesa_venda,
                        'percentualAdministracao'    => $viabilidade?->percentual_administracao,
                        'valorAdministracao'         => $viabilidade?->valor_administracao,
                        'percentualParticipacaoSpe'  => $empreendimento->percentual_participacao_spe,
                        'valorParticipacaoSpe'       => $empreendimento->valor_participacao_spe,
                        'faseAtual'                  => $empreendimento->fase_atual,
                        'previsaoLancamento'         => $empreendimento->previsao_lancamento,
                        'previsaoInicioObras'        => $empreendimento->previsao_inicio_obras,
                        'previsaoEntrega'            => $empreendimento->previsao_entrega,
                        'statusEmpreendimento'       => $empreendimento->status_empreendimento
                    ];
            }
        }
        
        return $porfilioView;
    }        

    
}