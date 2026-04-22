<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\BO\LoginBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function login(Request $request) {

        $request->validate([
            'username' => 'required|max:255',
            'password' => 'required|max:255',
        ]);

        // ── Valida o token do Cloudflare Turnstile ────────────────────────
        $turnstileValido = $this->validarTurnstile(
            $request->input('turnstileToken', ''),
            $request->ip()
        );

        if (!$turnstileValido) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Verificação de segurança falhou. Tente novamente.',
            ], 200);
        }

        $loginBO  = new LoginBO($this->logger);
        $response = $loginBO->login($request->username, $request->password, $request, $request->boolean('force', false));

        return response()->json($response, 200);
    }

    // ── Verifica o token Turnstile na API do Cloudflare ───────────────────
    private function validarTurnstile(string $token, string $ip): bool {

        // Em local/development não valida — chaves de produção não funcionam no localhost
        if (env('APP_ENV') !== 'production') {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $secretKey = env('TURNSTILE_SECRET_KEY');

        $resposta = \Illuminate\Support\Facades\Http::asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]
        );

        return $resposta->json('success') === true;
    }

    public function federationLogin(Request $request, $token) {
        $redirect = $request->session()->get('redirect');
        if(empty($redirect)) {
            $redirect = $request->redirect ?? '';

            $fields = $request->all();
            unset($fields['redirect']);
            $redirect .= '?' . http_build_query($fields);
        }

        $this->logger->info('LoginController->federationLogin', ['token' => $token, 'redirect' => $redirect]);

        if(!Auth::check()) {
            $loginBO = new LoginBO($this->logger);
            $loginBO->federationLogin($token);
        }

        if(empty($redirect)) {
            return redirect('/');
        }

        return redirect($redirect);
    }

    public function logout(Request $request) {
        $loginBO = new LoginBO($this->logger);
        $response = $loginBO->logout($request);

        return response()->json($response, 200);
    }

    public function checkSession(Request $request) {
        $this->logger->info('LoginController->checkSession', ['session_key' => $request->session()->get('session_key')]);

        $response = ['status' => 'success', 'message' => ''];
        return response()->json($response, 200);
    }
}