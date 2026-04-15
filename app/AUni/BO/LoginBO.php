<?php

namespace App\AUni\BO;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\AUni\Bean\ILogger;
use App\Models\UsersTable;

class LoginBO {

    private $logger;

    const MAX_ATTEMPTS  = 5;
    const LOCKOUT_MINS  = 10;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function login($username, $password, $request, $force = false) {

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

            // Limpa token fantasma: sessão expirou naturalmente, arquivo já não existe
            if (!empty($user->session_token) && !$this->isSessaoAtiva($user->session_token)) {
                $user->session_token = null;
            }

            // Sessão realmente ativa — pergunta ao usuário se deseja continuar
            if (!$force && !empty($user->session_token)) {
                Auth::logout(); // Remove a autenticação mas mantém a sessão/CSRF válidos
                return [
                    'status'  => 'session_active',
                    'message' => 'Já existe uma sessão ativa para este usuário. Deseja continuar e encerrar a sessão anterior?',
                ];
            }

            // Sucesso — zera tentativas e registra o token da sessão atual
            $user->login_attempts     = 0;
            $user->locked_until       = null;
            $user->last_login_attempt = now();
            $user->session_token      = $request->session()->getId();
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

    /**
     * Verifica se a sessão está REALMENTE ativa (driver: file).
     *
     * Usa uma janela curta de 15 minutos para detectar atividade real,
     * evitando falsos positivos quando o usuário fechou o browser sem fazer logout.
     * O check-session do frontend atualiza o arquivo a cada 10 minutos,
     * então qualquer sessão genuinamente ativa estará dentro dessa janela.
     */
    private function isSessaoAtiva($sessionToken) {
        if (empty($sessionToken)) return false;

        $arquivo = storage_path('framework/sessions/' . $sessionToken);

        if (!file_exists($arquivo)) return false;

        $janelaAtiva     = 15 * 60; // 15 minutos em segundos
        $ultimaAtividade = filemtime($arquivo);

        return (time() - $ultimaAtividade) < $janelaAtiva;
    }

    public function logout($request) {

        // Invalida o token — usa UUID aleatório para derrubar qualquer sessão ainda aberta
        // Busca o usuário pelo session_token caso Auth::user() esteja indisponível
        $user = Auth::user();
        if ($user) {
            UsersTable::where('user_id', $user->user_id)->update(['session_token' => Str::uuid()]);
        } else {
            $sessionToken = $request->session()->getId();
            if ($sessionToken) {
                UsersTable::where('session_token', $sessionToken)->update(['session_token' => Str::uuid()]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ['status' => 'success', 'message' => ''];
    }
}
