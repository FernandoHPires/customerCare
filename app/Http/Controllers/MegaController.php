<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\MegaBO;
use App\AUni\BO\FinanceiroBO;
use App\AUni\BO\PlanilhaFluxoCaixaBO;
use DateTime;

class MegaController extends Controller {

    private $logger;
    private $db;

    public function __construct() {

        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getFaturasPagar(Request $request) {

        $this->logger->info('MegaController->getFaturasPagar', [$request->query()]);

        $megaBO = new MegaBO($this->logger, $this->db);
        $res = $megaBO->getFaturasPagar($request->dataInicial, $request->dataFinal, $request->expand);

        return $this->responder($res);
    }

    public function getFaturasPagarEmAberto(Request $request) {

        $this->logger->info('MegaController->getFaturasPagarEmAberto', [$request->query()]);

        $megaBO = new MegaBO($this->logger, $this->db);
        $res = $megaBO->getFaturasPagarEmAberto($request->dataInicial, $request->dataFinal, $request->expand);

        return $this->responder($res);
    }

    public function getFaturasPagarPorFilial(Request $request, $filial) {

        $this->logger->info('MegaController->getFaturasPagarPorFilial', ['filial' => $filial, $request->query()]);

        $megaBO = new MegaBO($this->logger, $this->db);
        $res = $megaBO->getFaturasPagarPorFilial($filial, $request->dataInicial, $request->dataFinal, $request->expand);

        return $this->responder($res);
    }

    public function getFaturasPagarPorAgente(Request $request, $agente) {

        $this->logger->info('MegaController->getFaturasPagarPorAgente', ['agente' => $agente, $request->query()]);

        $megaBO = new MegaBO($this->logger, $this->db);
        $res = $megaBO->getFaturasPagarPorAgente($agente, $request->dataInicial, $request->dataFinal, $request->expand);

        return $this->responder($res);
    }

    public function getDashboardFaturasPagar(Request $request) {

        $this->logger->info('MegaController->getDashboardFaturasPagar', [$request->query()]);

        $financeiroBO = new FinanceiroBO($this->logger, $this->db);
        $res = $financeiroBO->getDashboardFaturasPagar($request->mes, $request->boolean('atualizar'));

        return $this->responder($res);
    }

    public function getFiltrosFluxoCaixa(Request $request) {

        $this->logger->info('MegaController->getFiltrosFluxoCaixa', [$request->query()]);

        $financeiroBO = new FinanceiroBO($this->logger, $this->db);

        return $this->responder($financeiroBO->getFiltrosFluxoCaixa($request->filial));
    }

    public function getFluxoCaixa(Request $request) {

        $this->logger->info('MegaController->getFluxoCaixa', [$request->query()]);

        set_time_limit(300);

        $financeiroBO = new FinanceiroBO($this->logger, $this->db);
        $res = $financeiroBO->getFluxoCaixa($request->dataInicial, $request->dataFinal, $request->filial, $request->idEmpreendimento);

        return $this->responder($res);
    }

    public function baixarPlanilhaFluxoCaixa(Request $request) {

        $this->logger->info('MegaController->baixarPlanilhaFluxoCaixa');

        $planilhaBO = new PlanilhaFluxoCaixaBO($this->logger, $this->db);
        $arquivo = $planilhaBO->gerarPlanilha($request->dados ?? [], $request->filtros ?? []);

        if ($arquivo === false) {
            return $this->responder(false);
        }

        return response()->download($arquivo['caminho'], $arquivo['nome'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function responder($res) {

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }
}
