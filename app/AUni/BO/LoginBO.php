<?php

namespace App\AUni\BO;

use Illuminate\Support\Facades\Auth;
use App\AUni\Bean\ILogger;
use App\Models\UsersTable;

class LoginBO {

    private $logger;

    const MAX_ATTEMPTS  = 5;
    const LOCKOUT_MINS  = 10;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function login($username, $password, $request) {

        $this->logger->info('LoginBO->login', ['username' => $username]);

        // Busca o usuário pelo username
        $user = UsersTable::where('user_name', $username)->first();

        if (!$user) {
            return ['status' => 'error', 'message' => 'Usuário ou senha incorretos.'];
        }

        // Verifica se a conta está bloqueada
        if ($user->locked_until && now()->lt($user->locked_until)) {
            $minutos = now()->diffInMinutes($user->locked_until) + 1;
            return [
                'status'  => 'error',
                'message' => "Conta bloqueada. Tente novamente em {$minutos} minuto(s).",
            ];
        }

        // Tenta autenticar
        $credentials = [
            'user_name' => $username,
            'password'  => $password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Sucesso — zera tentativas
            $user->login_attempts    = 0;
            $user->locked_until      = null;
            $user->last_login_attempt = now();
            $user->save();

            return ['status' => 'success', 'message' => ''];
        }

        // Falha — verifica janela de tempo (15 minutos)
        $agora = now();
        $janela = 15; // minutos

        // Se a última tentativa foi há mais de 15 minutos, zera o contador
        if ($user->last_login_attempt && $agora->diffInMinutes($user->last_login_attempt) >= $janela) {
            $user->login_attempts = 0;
        }

        $user->login_attempts     = ($user->login_attempts ?? 0) + 1;
        $user->last_login_attempt = $agora;

        if ($user->login_attempts >= self::MAX_ATTEMPTS) {
            $user->locked_until   = $agora->addMinutes(self::LOCKOUT_MINS);
            $user->login_attempts = 0;
            $user->save();

            return [
                'status'  => 'error',
                'message' => 'Conta bloqueada por ' . self::LOCKOUT_MINS . ' minutos após ' . self::MAX_ATTEMPTS . ' tentativas incorretas em menos de ' . $janela . ' minutos.',
            ];
        }

        $restantes = self::MAX_ATTEMPTS - $user->login_attempts;
        $user->save();

        return [
            'status'  => 'error',
            'message' => "Usuário ou senha incorretos. {$restantes} tentativa(s) restante(s).",
        ];
    }

    public function logout($request) {

        Auth::logout();

        return ['status' => 'success', 'message' => ''];
    }
}
