<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\FilialBO;

class FilialController extends Controller {

    private $logger;
    private $db;

    public function __construct() {

        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getFiliais(Request $request) {

        $this->logger->info('FilialController->getFiliais', []);

        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->getFiliais();

        return $this->responder($filialBO, $res, $res);
    }

    public function saveFilial(Request $request) {

        $this->logger->info('FilialController->saveFilial', [$request->all()]);

        $fields = (object) $request->all();
        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->saveFilial($fields);

        return $this->responder($filialBO, $res, ['filialId' => $res]);
    }

    public function deleteFilial(Request $request, $id) {

        $this->logger->info('FilialController->deleteFilial', ['id' => $id]);

        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->deleteFilial($id);

        return $this->responder($filialBO, $res, '');
    }

    public function getEmpreendimentos(Request $request, $id) {

        $this->logger->info('FilialController->getEmpreendimentos', ['id' => $id]);

        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->getEmpreendimentos($id);

        return $this->responder($filialBO, $res, $res);
    }

    public function saveEmpreendimento(Request $request) {

        $this->logger->info('FilialController->saveEmpreendimento', [$request->all()]);

        $fields = (object) $request->all();
        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->saveEmpreendimento($fields);

        return $this->responder($filialBO, $res, ['empreendimentoId' => $res]);
    }

    public function deleteEmpreendimento(Request $request, $id) {

        $this->logger->info('FilialController->deleteEmpreendimento', ['id' => $id]);

        $filialBO = new FilialBO($this->logger, $this->db);
        $res = $filialBO->deleteEmpreendimento($id);

        return $this->responder($filialBO, $res, '');
    }

    private function responder(FilialBO $filialBO, $res, $data) {

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $data;
        } else {
            $response->status = 'error';
            $response->message = $filialBO->getMensagem();
        }

        return response()->json($response, 200);
    }
}
