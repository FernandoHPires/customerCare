<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\ViabilidadeBO;

class ViabilidadeController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getViabilidades(Request $request, $empreendimentoId) {

        $this->logger->info('ViabilidadeController->getViabilidades', ['empreendimentoId' => $empreendimentoId]);

        $bo = new ViabilidadeBO($this->logger, $this->db);
        $data = $bo->getViabilidades($empreendimentoId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $data;

        return response()->json($response, 200);
    }

    public function saveViabilidade(Request $request) {

        $this->logger->info('ViabilidadeController->saveViabilidade', [$request->all()]);

        $fields = (object) $request->all();
        $bo = new ViabilidadeBO($this->logger, $this->db);
        $res = $bo->saveViabilidade($fields);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function ativarViabilidade(Request $request, $id) {

        $this->logger->info('ViabilidadeController->ativarViabilidade', ['id' => $id]);

        $bo = new ViabilidadeBO($this->logger, $this->db);
        $res = $bo->ativarViabilidade($id);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function deleteViabilidade(Request $request, $id) {

        $this->logger->info('ViabilidadeController->deleteViabilidade', ['id' => $id]);

        $bo = new ViabilidadeBO($this->logger, $this->db);
        $res = $bo->deleteViabilidade($id);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }
}
