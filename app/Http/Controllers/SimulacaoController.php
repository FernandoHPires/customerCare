<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\SimulacaoBO;

class SimulacaoController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getSimulacao(Request $request) {

        $this->logger->info('SimulacaoController->getSimulacao');

        $bo   = new SimulacaoBO($this->logger, $this->db);
        $data = $bo->getSimulacao();

        $response         = new Response();
        $response->status = 'success';
        $response->data   = $data;

        return response()->json($response, 200);
    }

    public function saveSimulacao(Request $request) {

        $this->logger->info('SimulacaoController->saveSimulacao', [$request->all()]);

        $fields = (object) $request->all();
        $bo     = new SimulacaoBO($this->logger, $this->db);
        $res    = $bo->saveSimulacao($fields);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data   = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }
}
