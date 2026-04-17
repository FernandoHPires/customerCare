<?php

namespace App\Http\Controllers;

use App\AUni\Bean\ILogger;
use App\AUni\BO\InviteTokenBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    // Envia o e-mail de convite para um usuário
    public function send(Request $request, int $userId) {

        $loggedUser = Auth::user();

        $bo     = new InviteTokenBO($this->logger);
        $result = $bo->generateAndSend($userId, $loggedUser->user_id);

        if (!$result) {
            return response()->json(['status' => 'error', 'message' => 'Usuário não encontrado.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'E-mail enviado com sucesso.']);
    }

    // Valida o token do link e autentica o usuário
    public function accept(Request $request, string $token) {

        $bo     = new InviteTokenBO($this->logger);
        $result = $bo->validateAndLogin($token, $request);

        if (!$result['ok']) {
            return redirect('/login')->with('invite_error', $result['message']);
        }

        // Redireciona para o app — o reset_request vai forçar /alterar-senha
        return redirect('/');
    }
}
