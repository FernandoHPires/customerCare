<?php

namespace App\AUni\BO;

use Illuminate\Support\Facades\Auth;
use App\AUni\Bean\ILogger;
use DateTime;

class LoginBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function login($username, $password, $request) {
      
        $credentials = [
            'user_name' => $username,
            'password' => $password
        ];

        if(Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            return ['status' => 'success', 'message' => ''];
        }
        
        return ['status' => 'error', 'message' => 'Wrong username or password!'];
    }
    
    public function logout($request) {

        Auth::logout();

        return ['status' => 'success', 'message' => ''];
    }
}
