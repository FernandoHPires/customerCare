<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\ClienteBO;

class ClienteController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getClientes(Request $request) {

        $this->logger->info('ClienteController->getClientes', []);

        $clienteBO = new ClienteBO($this->logger, $this->db);
        $res = $clienteBO->getClientes();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function saveCliente(Request $request) {

        $this->logger->info('ClienteController->saveCliente', [$request->all()]);

        $fields = (object) $request->all();
        $clienteBO = new ClienteBO($this->logger, $this->db);
        $res = $clienteBO->saveCliente($fields);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = ['clienteId' => $res];
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function deleteCliente(Request $request, $id) {

        $this->logger->info('ClienteController->deleteCliente', ['id' => $id]);

        $clienteBO = new ClienteBO($this->logger, $this->db);
        $res = $clienteBO->deleteCliente($id);

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
