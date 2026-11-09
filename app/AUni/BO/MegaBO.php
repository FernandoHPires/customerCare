<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use App\AUni\Utilities\HttpRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MegaBO {

    private $logger;
    private $db;
    private $platform = 'MEGA';
    private $accessToken;
    private $chamadasParalelas = 10;

    public function __construct(ILogger $logger, IDB $db) {

        $this->logger = $logger;
        $this->db = $db;

        $this->getAccessToken();
    }

    public function getAccessToken() {

        $tokenBO = new TokenBO($this->logger, $this->db);
        $tokenBO->setPlatform($this->platform);

        $this->accessToken = $tokenBO->getAccessToken();
    }

    public function getFaturasPagar($dataInicial, $dataFinal, $expand = 'agente') {

        $this->logger->info('MegaBO->getFaturasPagar', [$dataInicial, $dataFinal, $expand]);

        $url = config('mega.base_url') . '/api/FinanceiroMovimentacao/FaturaPagar/Saldo/'. rawurlencode($dataInicial) . '/' . rawurlencode($dataFinal);

        return $this->get($url, $this->getExpandFields($expand), 'MegaBO->getFaturasPagar');
    }

    public function getFaturasPagarEmAberto($dataInicial, $dataFinal, $expand = 'agente') {

        $this->logger->info('MegaBO->getFaturasPagarEmAberto', [$dataInicial, $dataFinal, $expand]);

        $url = config('mega.base_url') . '/api/FinanceiroMovimentacao/FaturaPagar/SaldoEmAberto/'. rawurlencode($dataInicial) . '/' . rawurlencode($dataFinal);

        return $this->get($url, $this->getExpandFields($expand), 'MegaBO->getFaturasPagarEmAberto');
    }

    public function getFaturasPagarPorFilial($filial, $dataInicial, $dataFinal, $expand = 'agente') {

        $this->logger->info('MegaBO->getFaturasPagarPorFilial', [$filial, $dataInicial, $dataFinal, $expand]);

        $url = config('mega.base_url') . '/api/FinanceiroMovimentacao/FaturaPagar/Saldo/Filial/'. rawurlencode($filial) . '/' . rawurlencode($dataInicial) . '/' . rawurlencode($dataFinal);

        return $this->get($url, $this->getExpandFields($expand), 'MegaBO->getFaturasPagarPorFilial');
    }

    public function getFaturasReceberPorFilial($filial, $dataInicial, $dataFinal, $expand = 'agente') {

        $this->logger->info('MegaBO->getFaturasReceberPorFilial', [$filial, $dataInicial, $dataFinal, $expand]);

        $url = config('mega.base_url') . '/api/FinanceiroMovimentacao/FaturaReceber/Saldo/Filial/'. rawurlencode($filial) . '/' . rawurlencode($dataInicial) . '/' . rawurlencode($dataFinal);

        return $this->get($url, $this->getExpandFields($expand), 'MegaBO->getFaturasReceberPorFilial');
    }

    public function getFaturasPagarPorAgente($agente, $dataInicial, $dataFinal, $expand = 'agente') {

        $this->logger->info('MegaBO->getFaturasPagarPorAgente', [$agente, $dataInicial, $dataFinal, $expand]);

        $url = config('mega.base_url') . '/api/FinanceiroMovimentacao/FaturaPagar/Saldo/Agente/'. rawurlencode($agente) . '/' . rawurlencode($dataInicial) . '/' . rawurlencode($dataFinal);

        return $this->get($url, $this->getExpandFields($expand), 'MegaBO->getFaturasPagarPorAgente');
    }

    public function getEmpreendimentosPorFilial($filial) {

        $this->logger->info('MegaBO->getEmpreendimentosPorFilial', [$filial]);

        $url = config('mega.base_url') . '/api/globalestruturas/Empreendimentos/Filial';

        return $this->get($url, ['filial' => $filial], 'MegaBO->getEmpreendimentosPorFilial');
    }

    public function getContratosPorEmpreendimento($empreendimento) {

        $this->logger->info('MegaBO->getContratosPorEmpreendimento', [$empreendimento]);

        $url = config('mega.base_url') . '/api/Carteira/DadosContrato/IdEmpreendimento=' . rawurlencode($empreendimento);

        return $this->get($url, null, 'MegaBO->getContratosPorEmpreendimento');
    }

    public function getParcelasPorContratos($contratos) {

        $this->logger->info('MegaBO->getParcelasPorContratos', [count($contratos)]);

        $parcelas = [];
        foreach (array_chunk($contratos, $this->chamadasParalelas) as $grupo) {
            $respostas = Http::pool(fn(Pool $pool) => array_map(
                fn($contrato) => $pool->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'tenantId' => config('mega.tenant_id'),
                ])->timeout(30)->get(config('mega.base_url') . '/api/Carteira/DadosParcelas/IdContrato=' . rawurlencode($contrato)),
                $grupo
            ));

            foreach ($respostas as $resposta) {
                if (!($resposta instanceof Response) || !$resposta->successful()) {
                    $this->logger->error('MegaBO->getParcelasPorContratos', [
                        'response_code' => $resposta instanceof Response ? $resposta->status() : null,
                    ]);
                    return false;
                }

                $parcelas = array_merge($parcelas, json_decode($resposta->body()) ?: []);
            }
        }

        return $parcelas;
    }

    private function getExpandFields($expand) {

        return empty($expand) ? null : ['expand' => $expand];
    }

    private function get($url, $fields, $source) {

        if(empty($this->accessToken)) {
            $this->logger->error($source . ' - Access token not available');
            return false;
        }

        $httpRequest = $this->execGet($url, $fields);

        if($httpRequest->getResponseCode() == 401) {
            $this->logger->warning($source . ' - Unauthorized, renewing access token');

            $tokenBO = new TokenBO($this->logger, $this->db);
            $tokenBO->setPlatform($this->platform);
            $this->accessToken = $tokenBO->refreshToken();

            if(empty($this->accessToken)) {
                return false;
            }

            $httpRequest = $this->execGet($url, $fields);
        }

        if($httpRequest->getResponseCode() != 200) {
            $this->logger->error($source, [
                'response_code' => $httpRequest->getResponseCode(),
                'response' => substr((string) $httpRequest->getResponse(), 0, 1000)
            ]);
            return false;
        }

        return json_decode($httpRequest->getResponse());
    }

    private function execGet($url, $fields) {

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setMethod('get');
        $httpRequest->setUrl($url);
        $httpRequest->setHeaderType('custom');
        $httpRequest->setHeaders([
            'Accept: application/json',
            'Authorization: Bearer ' . $this->accessToken,
            'tenantId: ' . config('mega.tenant_id')
        ]);
        $httpRequest->setFields($fields);
        $httpRequest->exec();

        return $httpRequest;
    }
}
