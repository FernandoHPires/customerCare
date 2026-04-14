<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\PermissaoBO;

class PermissaoController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function getPermissoes(Request $request) {

        $permissaoBO = new PermissaoBO($this->logger);
        $res = $permissaoBO->getPermissoes();

        $response = new Response();
        $response->status = 'success';
        $response->data = $res;

        return response()->json($response, 200);
    }

    public function getMenusPerfil(Request $request, $perfilId) {

        $this->logger->info('PermissaoController->getMenusPerfil', ['perfilId' => $perfilId]);

        $permissaoBO = new PermissaoBO($this->logger);
        $res = $permissaoBO->getMenusPerfil($perfilId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $res;

        return response()->json($response, 200);
    }

    public function savePermissao(Request $request, $perfilId) {

        $this->logger->info('PermissaoController->savePermissao', ['perfilId' => $perfilId]);

        $menuIds = $request->input('menuIds', []);
        $permissaoBO = new PermissaoBO($this->logger);
        $res = $permissaoBO->savePermissao($perfilId, $menuIds);

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
