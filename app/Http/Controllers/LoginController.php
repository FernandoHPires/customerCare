<?php

namespace App\Http\Controllers;

use App\Amur\Bean\Logger;
use App\Amur\BO\LoginBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function login(Request $request) {

        $request->validate([
            //'email' => 'required|email:rfc,dns',
            'username' => 'required|max:255',
            'password' => 'required|max:255',
        ]);

        $loginBO = new LoginBO($this->logger);
        $response = $loginBO->login($request->username, $request->password, $request);

        return response()->json($response, 200);
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