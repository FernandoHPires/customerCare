<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\PerfilBO;

class PerfilController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getPerfis(Request $request) {

        $perfilBO = new PerfilBO($this->logger, $this->db);
        $res = $perfilBO->getPerfis();

        $response = new Response();
        $response->status = 'success';
        $response->data = $res;

        return response()->json($response, 200);
    }

    public function savePerfil(Request $request) {

        $this->logger->info('PerfilController->savePerfil', [$request->all()]);

        $fields = (object) $request->all();
        $perfilBO = new PerfilBO($this->logger, $this->db);
        $res = $perfilBO->savePerfil($fields);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = ['perfilId' => $res];
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function deletePerfil(Request $request, $id) {

        $this->logger->info('PerfilController->deletePerfil', ['id' => $id]);

        $perfilBO = new PerfilBO($this->logger, $this->db);
        $res = $perfilBO->deletePerfil($id);

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
