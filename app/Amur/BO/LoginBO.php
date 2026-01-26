<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\ILogger;
use App\Models\FederationToken;
use App\Models\UsersTable;
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

    public function federationLogin($token) {
        $ft = FederationToken::query()
        ->where('token', $token)
        ->first();

        $this->logger->info('LoginBO->federationLogin', [$token, env('SYSTEM_APPLICATION_ID')]);

        if($ft && new DateTime($ft->expires_at) > new DateTime() && $ft->system_application_id == env('SYSTEM_APPLICATION_ID')) {
            $user = UsersTable::query()
            ->where('user_id', $ft->user_id)
            ->where('inuse', 'yes')
            ->first();

            if($user) {
                $credentials = [
                    'user_name' => $user->user_name,
                    'password' => $user->user_password
                ];
        
                if(Auth::attempt($credentials)) {
                    $ft->delete();
                    return true;
                }
            }
        }

        return false;
    }

    public function logout($request) {

        Auth::logout();

        return ['status' => 'success', 'message' => ''];
    }
}
