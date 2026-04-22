<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Utilities\Email;
use App\Models\UsersTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorBO {

    private $logger;

    const CODE_EXPIRY_MINUTES = 10;
    const MAX_ATTEMPTS        = 3;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    /**
     * Gera um código de 6 dígitos, salva no usuário e envia por email.
     */
    public function generateAndSend(UsersTable $user): void {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->two_factor_code       = Hash::make($code);
        $user->two_factor_expires_at = now()->addMinutes(self::CODE_EXPIRY_MINUTES);
        $user->two_factor_attempts   = 0;
        $user->save();

        $this->sendCode($user, $code);
    }

    /**
     * Verifica o código digitado pelo usuário e completa o login se correto.
     */
    public function verify($code, $request): array {
        $userId = $request->session()->get('two_factor_pending_user_id');

        if (!$userId) {
            return ['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $user = UsersTable::find($userId);

        if (!$user) {
            return ['status' => 'error', 'message' => 'Usuário não encontrado.'];
        }

        // Muitas tentativas erradas
        if ($user->two_factor_attempts >= self::MAX_ATTEMPTS) {
            $request->session()->forget('two_factor_pending_user_id');
            return ['status' => 'error', 'message' => 'Muitas tentativas incorretas. Faça login novamente.'];
        }

        // Código expirado
        if (!$user->two_factor_expires_at || now()->gt($user->two_factor_expires_at)) {
            $request->session()->forget('two_factor_pending_user_id');
            return ['status' => 'error', 'message' => 'Código expirado. Faça login novamente.'];
        }

        // Código incorreto
        if (!$user->two_factor_code || !Hash::check(trim($code), $user->two_factor_code)) {
            $user->two_factor_attempts++;
            $user->save();

            $restantes = self::MAX_ATTEMPTS - $user->two_factor_attempts;

            if ($restantes <= 0) {
                $request->session()->forget('two_factor_pending_user_id');
                return ['status' => 'error', 'message' => 'Muitas tentativas incorretas. Faça login novamente.'];
            }

            return ['status' => 'error', 'message' => "Código incorreto. {$restantes} tentativa(s) restante(s)."];
        }

        // Sucesso — limpa código e completa o login
        $user->two_factor_code       = null;
        $user->two_factor_expires_at = null;
        $user->two_factor_attempts   = 0;
        $user->login_attempts        = 0;
        $user->locked_until          = null;
        $user->save();

        // Auth::login() regenera o session ID internamente (migrate)
        // por isso salvamos o session_token DEPOIS do login
        Auth::login($user);
        $request->session()->forget('two_factor_pending_user_id');

        $user->session_token = $request->session()->getId();
        $user->save();

        return ['status' => 'success', 'message' => ''];
    }

    /**
     * Reenvia um novo código para o usuário pendente.
     */
    public function resend($request): array {
        $userId = $request->session()->get('two_factor_pending_user_id');

        if (!$userId) {
            return ['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $user = UsersTable::find($userId);

        if (!$user) {
            return ['status' => 'error', 'message' => 'Usuário não encontrado.'];
        }

        $this->generateAndSend($user);

        return ['status' => 'success', 'message' => 'Novo código enviado para ' . $this->maskEmail($user->user_email) . '.'];
    }

    /**
     * Mascara o email para exibição. Ex: f***@gmail.com
     */
    public function maskEmail(string $email): string {
        [$local, $domain] = explode('@', $email, 2);

        $len    = strlen($local);
        $masked = substr($local, 0, 1)
                . str_repeat('*', max($len - 2, 1))
                . ($len > 1 ? substr($local, -1) : '');

        return $masked . '@' . $domain;
    }

    private function sendCode(UsersTable $user, string $code): void {
        $expiry = self::CODE_EXPIRY_MINUTES;

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;'>
                <h2 style='color: #1C4D64;'>Código de verificação</h2>
                <p>Olá, <strong>{$user->user_fname}</strong>!</p>
                <p>Use o código abaixo para concluir seu login:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-size: 36px; font-weight: bold; letter-spacing: 12px; color: #1C4D64;'>{$code}</span>
                </div>
                <p style='color: #666;'>Este código expira em <strong>{$expiry} minutos</strong>.</p>
                <p style='color: #999; font-size: 12px;'>Se você não tentou fazer login, ignore este email.</p>
            </div>
        ";

        Email::send($user->user_email, 'Código de verificação - UNI Sistema', $body);
    }
}
