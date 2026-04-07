<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\EmpreendimentoBO;

class EmpreendimentoController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function saveEmpreendimento(Request $request) {

        $this->logger->info('EmpreendimentoController->saveEmpreendimento', [$request->all()]);

        $fields = (object) $request->all();
        $empreendimentoBO = new EmpreendimentoBO($this->logger, $this->db);
        $res = $empreendimentoBO->saveEmpreendimento($fields);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function deleteEmpreendimento(Request $request, $id) {

        $this->logger->info('EmpreendimentoController->deleteEmpreendimento', ['id' => $id]);

        $empreendimentoBO = new EmpreendimentoBO($this->logger, $this->db);
        $res = $empreendimentoBO->deleteEmpreendimento($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }    
    
}