<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\AlterarSenhaBO;

class AlterarSenhaController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function alterarSenha(Request $request) {

        $this->logger->info('AlterarSenhaController->alterarSenha', []);

        $fields = (object) $request->all();
        $bo = new AlterarSenhaBO($this->logger);
        $res = $bo->alterarSenha($fields);

        $response = new Response();
        if ($res['ok']) {
            $response->status  = 'success';
            $response->data    = '';
        } else {
            $response->status  = 'error';
            $response->message = $res['message'];
        }

        return response()->json($response, 200);
    }
}
