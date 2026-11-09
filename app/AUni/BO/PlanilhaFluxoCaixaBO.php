<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use DateTime;
use Throwable;

class PlanilhaFluxoCaixaBO {

    private $logger;
    private $db;
    private $formatoMoeda = '#,##0.00;[Red]-#,##0.00';
    private $formatoData = 'dd/mm/yyyy';
    private $corMarca = 'FF1C4D64';
    private $diasSemana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    private $tabelas = 0;

    public function __construct(ILogger $logger, IDB $db) {

        $this->logger = $logger;
        $this->db = $db;
    }

    public function gerarPlanilha($dados, $filtros) {

        $this->logger->info('PlanilhaFluxoCaixaBO->gerarPlanilha', [$filtros['filial'] ?? null, $filtros['dataInicial'] ?? null, $filtros['dataFinal'] ?? null]);

        $inicio = DateTime::createFromFormat('!Y-m-d', (string) ($filtros['dataInicial'] ?? ''));
        $fim = DateTime::createFromFormat('!Y-m-d', (string) ($filtros['dataFinal'] ?? ''));
        if (!$inicio || !$fim || $inicio > $fim) {
            $this->logger->error('PlanilhaFluxoCaixaBO->gerarPlanilha - Período inválido');
            return false;
        }

        $recebiveisD1 = filter_var($filtros['recebiveisD1'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $inicioReceber = $recebiveisD1 ? (clone $inicio)->modify('-1 day') : $inicio;
        $fimReceber = $recebiveisD1 ? (clone $fim)->modify('-1 day') : $fim;

        $receber = ['considerados' => $this->filtrarPeriodo($dados['receber'] ?? [], $inicioReceber, $fimReceber)];
        $receberNaoConsiderados = $this->filtrarPeriodo($dados['receberNaoConsiderados'] ?? [], $inicioReceber, $fimReceber);
        $pagar = $dados['pagar'] ?? [];
        $pagarNaoConsiderados = $dados['pagarNaoConsiderados'] ?? [];

        try {
            $planilha = new Spreadsheet();

            $abaReceber = $planilha->getActiveSheet();
            $abaReceber->setTitle('Contas a Receber');
            $this->preencherReceber($abaReceber, $receber['considerados'], $receberNaoConsiderados);

            $abaPagar = $planilha->createSheet();
            $abaPagar->setTitle('Contas a Pagar');
            $this->preencherPagar($abaPagar, $pagar, $pagarNaoConsiderados);

            $abaPainel = $planilha->createSheet();
            $abaPainel->setTitle('Painel');
            $this->preencherPainel($abaPainel, $receber['considerados'], $pagar, (float) ($filtros['saldoInicial'] ?? 0), $inicio, $fim);

            $planilha->setActiveSheetIndex(0);

            $caminho = tempnam(sys_get_temp_dir(), 'fluxo');
            $writer = new Xlsx($planilha);
            $writer->setIncludeCharts(true);
            $writer->save($caminho);
        } catch (Throwable $e) {
            $this->logger->error('PlanilhaFluxoCaixaBO->gerarPlanilha', [$e->getMessage()]);
            return false;
        }

        return [
            'caminho' => $caminho,
            'nome' => 'fluxo-caixa-filial-' . preg_replace('/\D/', '', (string) ($filtros['filial'] ?? '')) . '-' . $inicio->format('Y-m-d') . '-a-' . $fim->format('Y-m-d') . '.xlsx',
        ];
    }

    private function filtrarPeriodo($itens, DateTime $inicio, DateTime $fim) {

        $primeiroDia = $inicio->format('Y-m-d');
        $ultimoDia = $fim->format('Y-m-d');

        return array_values(array_filter($itens, function ($item) use ($primeiroDia, $ultimoDia) {
            $dia = $item['dia'] ?? '';
            return $dia === '' || ($dia >= $primeiroDia && $dia <= $ultimoDia);
        }));
    }

    private function ordenarPorDia($itens) {

        usort($itens, fn($a, $b) => ($a['dia'] ?? '') <=> ($b['dia'] ?? ''));

        return $itens;
    }

    private function preencherReceber(Worksheet $aba, $considerados, $naoConsiderados) {

        $colunas = [
            ['Origem', 'texto'],
            ['Documento', 'texto'],
            ['Parcela', 'texto'],
            ['Sequência', 'texto'],
            ['Tipo da parcela', 'texto'],
            ['Cliente', 'texto'],
            ['CNPJ', 'texto'],
            ['Bloco', 'texto'],
            ['Unidade', 'texto'],
            ['Status do contrato', 'texto'],
            ['Situação', 'texto'],
            ['Vencimento', 'data'],
            ['Vencimento original', 'data'],
            ['Valor original', 'moeda'],
            ['Valor considerado', 'moeda'],
            ['Valor presente', 'moeda'],
            ['Multa', 'moeda'],
            ['Juros', 'moeda'],
            ['Valor pago', 'moeda'],
            ['Data da baixa', 'data'],
        ];

        $linha = $this->escreverCabecalho($aba, 'Contas a Receber');

        $linha = $this->escreverTabela(
            $aba,
            $linha,
            'Considerados no fluxo de caixa',
            $colunas,
            array_map(fn($item) => $this->linhaReceber($item), $considerados)
        );

        $this->escreverTabela(
            $aba,
            $linha,
            'Não considerados no período',
            array_merge([['Motivo', 'texto']], $colunas),
            array_map(fn($item) => array_merge([$item['motivo'] ?? ''], $this->linhaReceber($item)), $naoConsiderados)
        );

        $this->ajustarLarguras($aba, count($colunas) + 1);
    }

    private function linhaReceber($item) {

        $d = $item['detalhe'] ?? [];
        $v = fn($chave) => $d[$chave] ?? '';

        if ($v('origem') === 'Contrato') {
            return [
                'Contrato',
                $v('documento'),
                $v('parcela'),
                $v('sequencia'),
                $v('tipo'),
                $v('cliente'),
                '',
                $v('bloco'),
                $v('unidade'),
                $v('statusContrato'),
                $v('situacao') ?: $v('statusParcela'),
                $item['vencimento'] ?? '',
                $v('vencimento'),
                $v('valorOriginal'),
                $item['valor'] ?? '',
                $v('valorPresente'),
                $v('multa'),
                $v('juros'),
                $v('valorPago'),
                $v('dataBaixa'),
            ];
        }

        return [
            'Título',
            $v('documento'),
            $v('parcela'),
            '',
            '',
            $this->nomeAgente($d),
            $v('cnpj'),
            '',
            '',
            '',
            $this->situacaoTitulo($d),
            $item['vencimento'] ?? '',
            $v('dataVencimento'),
            $v('valorParcela'),
            $item['valor'] ?? '',
            '',
            '',
            '',
            $this->valorBaixado($d),
            '',
        ];
    }

    private function preencherPagar(Worksheet $aba, $considerados, $naoConsiderados) {

        $colunas = [
            ['Documento', 'texto'],
            ['Nº AP', 'texto'],
            ['Parcela', 'texto'],
            ['Fornecedor', 'texto'],
            ['Nome fantasia', 'texto'],
            ['CNPJ', 'texto'],
            ['Situação', 'texto'],
            ['Vencimento', 'data'],
            ['Vencimento original', 'data'],
            ['Valor da parcela', 'moeda'],
            ['Valor considerado', 'moeda'],
            ['Valor baixado', 'moeda'],
        ];

        $linha = $this->escreverCabecalho($aba, 'Contas a Pagar');

        $linha = $this->escreverTabela(
            $aba,
            $linha,
            'Considerados no fluxo de caixa',
            $colunas,
            array_map(fn($item) => $this->linhaPagar($item), $considerados)
        );

        $this->escreverTabela(
            $aba,
            $linha,
            'Não considerados no período',
            array_merge([['Motivo', 'texto']], $colunas),
            array_map(fn($item) => array_merge([$item['motivo'] ?? ''], $this->linhaPagar($item)), $naoConsiderados)
        );

        $this->ajustarLarguras($aba, count($colunas) + 1);
    }

    private function linhaPagar($item) {

        $d = $item['detalhe'] ?? [];

        return [
            $d['documento'] ?? '',
            $d['numeroAP'] ?? '',
            $d['parcela'] ?? '',
            $this->nomeAgente($d),
            $d['nomeFantasia'] ?? '',
            $d['cnpj'] ?? '',
            $this->situacaoTitulo($d),
            $item['vencimento'] ?? '',
            $d['dataVencimento'] ?? '',
            $d['valorParcela'] ?? '',
            $item['valor'] ?? '',
            $this->valorBaixado($d),
        ];
    }

    private function nomeAgente($d) {

        return trim(implode(' - ', array_filter([(string) ($d['agenteCodigo'] ?? ''), (string) ($d['agenteNome'] ?? '')], 'strlen')));
    }

    private function situacaoTitulo($d) {

        $saldo = (float) ($d['saldo'] ?? 0);
        $valor = (float) ($d['valorParcela'] ?? 0);

        if ($saldo <= 0) {
            return 'Pago';
        }

        return $saldo < $valor ? 'Baixa parcial' : 'Em aberto';
    }

    private function valorBaixado($d) {

        if (!isset($d['valorParcela']) || $d['valorParcela'] === '' || $d['valorParcela'] === null) {
            return '';
        }

        return round((float) $d['valorParcela'] - (float) ($d['saldo'] ?? 0), 2);
    }

    private function preencherPainel(Worksheet $aba, $receber, $pagar, $saldoInicial, DateTime $inicio, DateTime $fim) {

        $entradas = $this->somarPorDia($receber);
        $saidas = $this->somarPorDia($pagar);

        $dias = array_merge(array_keys($entradas), array_keys($saidas));
        $primeiroDia = min(array_merge([$inicio->format('Y-m-d')], $dias));
        $ultimoDia = max(array_merge([$fim->format('Y-m-d')], $dias));

        $linha = $this->escreverCabecalho($aba, 'Painel do Fluxo de Caixa');

        $linhaIndicadores = $linha;
        $celulaSaldo = '$B$' . ($linhaIndicadores + 1);
        $linhaTabela = $linhaIndicadores + 10;
        $primeiraLinha = $linhaTabela + 2;

        $periodo = [];
        $dia = new DateTime($primeiroDia);
        $limite = new DateTime($ultimoDia);
        while ($dia <= $limite) {
            $periodo[] = clone $dia;
            $dia->modify('+1 day');
        }

        $ultimaLinha = $primeiraLinha + count($periodo) - 1;
        $faixa = fn($coluna) => '$' . $coluna . '$' . $primeiraLinha . ':$' . $coluna . '$' . $ultimaLinha;

        $this->escreverTitulo($aba, 1, $linhaIndicadores, 'Indicadores');
        $indicadores = [
            ['Saldo inicial', round($saldoInicial, 2), $this->formatoMoeda],
            ['Entradas', '=SUM(' . $faixa('C') . ')', $this->formatoMoeda],
            ['Saídas', '=SUM(' . $faixa('D') . ')', $this->formatoMoeda],
            ['Saldo final', '=E' . $ultimaLinha, $this->formatoMoeda],
            ['Menor saldo do período', '=MIN(' . $faixa('E') . ')', $this->formatoMoeda],
            ['Dia do menor saldo', '=INDEX(' . $faixa('B') . ',MATCH(MIN(' . $faixa('E') . '),' . $faixa('E') . ',0))', $this->formatoData],
            ['Necessidade de aporte', '=MAX(0,-MIN(' . $faixa('E') . '))', $this->formatoMoeda],
            ['Dias com saldo negativo', '=COUNTIF(' . $faixa('E') . ',"<0")', '0'],
        ];

        foreach ($indicadores as $i => $indicador) {
            $linhaIndicador = $linhaIndicadores + 1 + $i;
            $this->escreverTexto($aba, 1, $linhaIndicador, $indicador[0]);
            $aba->setCellValue('B' . $linhaIndicador, $indicador[1]);
            $aba->getStyle('B' . $linhaIndicador)->getNumberFormat()->setFormatCode($indicador[2]);
            $aba->getStyle('B' . $linhaIndicador)->getFont()->setBold(true);
        }

        $this->escreverTitulo($aba, 1, $linhaTabela, 'Saldo dia a dia');
        $cabecalhoTabela = ['Dia', 'Data', 'Entradas', 'Saídas', 'Saldo do dia'];
        foreach ($cabecalhoTabela as $i => $titulo) {
            $this->escreverTexto($aba, $i + 1, $linhaTabela + 1, $titulo);
        }
        $this->estilizarCabecalho($aba, 'A' . ($linhaTabela + 1) . ':E' . ($linhaTabela + 1));

        foreach ($periodo as $i => $data) {
            $linhaDia = $primeiraLinha + $i;
            $chave = $data->format('Y-m-d');

            $this->escreverTexto($aba, 1, $linhaDia, $this->diasSemana[(int) $data->format('w')] . ' ' . $data->format('d/m'));
            $aba->setCellValue('B' . $linhaDia, Date::PHPToExcel($data));
            $aba->setCellValue('C' . $linhaDia, round($entradas[$chave] ?? 0, 2));
            $aba->setCellValue('D' . $linhaDia, round($saidas[$chave] ?? 0, 2));
            $saldoAnterior = $i === 0 ? $celulaSaldo : 'E' . ($linhaDia - 1);
            $aba->setCellValue('E' . $linhaDia, '=' . $saldoAnterior . '+C' . $linhaDia . '-D' . $linhaDia);
        }

        $linhaTotal = $ultimaLinha + 1;
        $this->escreverTexto($aba, 1, $linhaTotal, 'Total');
        foreach (['C', 'D'] as $coluna) {
            $aba->setCellValue($coluna . $linhaTotal, '=SUM(' . $faixa($coluna) . ')');
        }
        $aba->getStyle('A' . $linhaTotal . ':E' . $linhaTotal)->getFont()->setBold(true);

        $aba->getStyle('B' . $primeiraLinha . ':B' . $ultimaLinha)->getNumberFormat()->setFormatCode($this->formatoData);
        $aba->getStyle('C' . $primeiraLinha . ':E' . $linhaTotal)->getNumberFormat()->setFormatCode($this->formatoMoeda);

        $negativo = new Conditional();
        $negativo->setConditionType(Conditional::CONDITION_CELLIS);
        $negativo->setOperatorType(Conditional::OPERATOR_LESSTHAN);
        $negativo->addCondition('0');
        $negativo->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FFB42318');
        $negativo->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $negativo->getStyle()->getFill()->getStartColor()->setARGB('FFFDECEA');
        $negativo->getStyle()->getFill()->getEndColor()->setARGB('FFFDECEA');
        $aba->getStyle('E' . $primeiraLinha . ':E' . $ultimaLinha)->setConditionalStyles([$negativo]);
        $aba->getStyle('B' . ($linhaIndicadores + 4) . ':B' . ($linhaIndicadores + 5))->setConditionalStyles([$negativo]);

        $this->adicionarGrafico($aba, $primeiraLinha, $ultimaLinha, $linhaTabela + 1, $linha);

        $aba->getColumnDimension('A')->setWidth(38);
        $aba->getColumnDimension('B')->setWidth(18);
        foreach (['C', 'D', 'E'] as $coluna) {
            $aba->getColumnDimension($coluna)->setWidth(24);
        }
    }

    private function somarPorDia($itens) {

        $totais = [];
        foreach ($itens as $item) {
            $dia = $item['dia'] ?? '';
            if ($dia === '') {
                continue;
            }

            $totais[$dia] = ($totais[$dia] ?? 0) + (float) ($item['valor'] ?? 0);
        }

        return $totais;
    }

    private function adicionarGrafico(Worksheet $aba, $primeiraLinha, $ultimaLinha, $linhaCabecalho, $linhaTopo) {

        $quantidade = $ultimaLinha - $primeiraLinha + 1;
        $nomeAba = "'" . $aba->getTitle() . "'!";

        $serie = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $nomeAba . '$E$' . $linhaCabecalho, null, 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $nomeAba . '$A$' . $primeiraLinha . ':$A$' . $ultimaLinha, null, $quantidade)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $nomeAba . '$E$' . $primeiraLinha . ':$E$' . $ultimaLinha, null, $quantidade)]
        );

        $grafico = new Chart(
            'saldoDiaADia',
            new Title('Saldo do dia'),
            new Legend(Legend::POSITION_BOTTOM, null, false),
            new PlotArea(null, [$serie])
        );

        $grafico->setTopLeftPosition('H' . $linhaTopo);
        $grafico->setBottomRightPosition('S' . ($linhaTopo + 24));

        $aba->addChart($grafico);
    }

    private function escreverCabecalho(Worksheet $aba, $titulo) {

        $this->escreverTexto($aba, 1, 1, $titulo);
        $aba->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB($this->corMarca);

        return 3;
    }

    private function escreverTitulo(Worksheet $aba, $coluna, $linha, $texto) {

        $this->escreverTexto($aba, $coluna, $linha, $texto);
        $aba->getStyle(Coordinate::stringFromColumnIndex($coluna) . $linha)->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($this->corMarca);
    }

    private function escreverTabela(Worksheet $aba, $linha, $titulo, $colunas, $linhas) {

        $this->escreverTitulo($aba, 1, $linha, $titulo . ' (' . count($linhas) . ')');

        $linhaCabecalho = $linha + 1;

        if (empty($linhas)) {
            $this->escreverTexto($aba, 1, $linhaCabecalho, 'Nenhum registro.');
            return $linhaCabecalho + 3;
        }

        foreach ($colunas as $i => $coluna) {
            $this->escreverTexto($aba, $i + 1, $linhaCabecalho, $coluna[0]);
        }

        $primeiraLinha = $linhaCabecalho + 1;
        foreach ($linhas as $i => $valores) {
            foreach ($colunas as $j => $coluna) {
                $this->escreverValor($aba, $j + 1, $primeiraLinha + $i, $valores[$j] ?? '', $coluna[1]);
            }
        }

        $ultimaLinha = $primeiraLinha + count($linhas) - 1;
        $ultimaColuna = Coordinate::stringFromColumnIndex(count($colunas));

        $this->tabelas++;
        $tabela = new Table('A' . $linhaCabecalho . ':' . $ultimaColuna . $ultimaLinha, 'Tabela' . $this->tabelas);
        $estilo = new TableStyle();
        $estilo->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
        $estilo->setShowRowStripes(true);
        $tabela->setStyle($estilo);
        $aba->addTable($tabela);

        $linhaTotal = $ultimaLinha + 1;
        $this->escreverTexto($aba, 1, $linhaTotal, 'Total');
        foreach ($colunas as $j => $coluna) {
            $letra = Coordinate::stringFromColumnIndex($j + 1);

            if ($coluna[1] === 'moeda') {
                $aba->setCellValue($letra . $linhaTotal, '=SUBTOTAL(109,' . $letra . $primeiraLinha . ':' . $letra . $ultimaLinha . ')');
            }

            $formato = ['moeda' => $this->formatoMoeda, 'data' => $this->formatoData][$coluna[1]] ?? null;
            if ($formato) {
                $aba->getStyle($letra . $primeiraLinha . ':' . $letra . $linhaTotal)->getNumberFormat()->setFormatCode($formato);
            }
        }
        $aba->getStyle('A' . $linhaTotal . ':' . $ultimaColuna . $linhaTotal)->getFont()->setBold(true);

        return $linhaTotal + 3;
    }

    private function escreverValor(Worksheet $aba, $coluna, $linha, $valor, $tipo) {

        if ($valor === '' || $valor === null) {
            return;
        }

        $endereco = Coordinate::stringFromColumnIndex($coluna) . $linha;

        if ($tipo === 'moeda') {
            $aba->setCellValue($endereco, round((float) $valor, 2));
            return;
        }

        if ($tipo === 'data') {
            $data = DateTime::createFromFormat('!d/m/Y', (string) $valor);
            if ($data) {
                $aba->setCellValue($endereco, Date::PHPToExcel($data));
                return;
            }
        }

        $this->escreverTexto($aba, $coluna, $linha, $valor);
    }

    private function escreverTexto(Worksheet $aba, $coluna, $linha, $texto) {

        $aba->setCellValueExplicit(Coordinate::stringFromColumnIndex($coluna) . $linha, (string) $texto, DataType::TYPE_STRING);
    }

    private function estilizarCabecalho(Worksheet $aba, $faixa) {

        $estilo = $aba->getStyle($faixa);
        $estilo->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->corMarca);
    }

    private function ajustarLarguras(Worksheet $aba, $quantidade) {

        $aba->getColumnDimension('A')->setWidth(30);
        for ($i = 2; $i <= $quantidade; $i++) {
            $aba->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }
}
