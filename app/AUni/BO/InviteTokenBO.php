<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Utilities\Email;
use App\Models\UserInviteToken;
use App\Models\UsersTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InviteTokenBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function generateAndSend(int $userId, int $createdBy): bool {

        $this->logger->info('InviteTokenBO->generateAndSend', ['userId' => $userId]);

        $user = UsersTable::find($userId);
        if (!$user) return false;

        // Invalida tokens anteriores não utilizados deste usuário
        UserInviteToken::where('user_id', $userId)
            ->whereNull('used_at')
            ->delete();

        // Gera token único
        $token = Str::random(48);

        UserInviteToken::create([
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => now()->addHours(24),
            'used_at'    => null,
            'created_at' => now(),
            'created_by' => $createdBy,
        ]);

        $link     = url('/convite/' . $token);
        $nome     = trim($user->user_fname . ' ' . $user->user_lname);
        $username = $user->user_name;

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #124C60; padding: 30px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>UNI Gestão de Negócios</h1>
                </div>
                <div style='padding: 30px; background: #f9f9f9;'>
                    <p style='font-size: 16px;'>Olá, <strong>{$nome}</strong>!</p>
                    <p style='font-size: 15px;'>Seu acesso ao sistema <strong>UNI Gestão de Negócios</strong> foi configurado.</p>
                    <p style='font-size: 15px;'><strong>Usuário:</strong> {$username}</p>
                    <p style='font-size: 15px;'>Clique no botão abaixo para acessar o sistema e definir sua senha:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$link}' style='background: #124C60; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;'>
                            Acessar o Sistema
                        </a>
                    </div>
                    <p style='font-size: 13px; color: #888;'>Este link é válido por <strong>24 horas</strong> e pode ser utilizado apenas uma vez.</p>
                    <p style='font-size: 13px; color: #888;'>Se você não esperava este e-mail, ignore-o.</p>
                </div>
                <div style='padding: 16px; text-align: center; background: #eee;'>
                    <p style='font-size: 12px; color: #999; margin: 0;'>UNI Gestão de Negócios</p>
                </div>
            </div>
        ";

        Email::send(
            $user->user_email,
            'Acesso ao Sistema UNI Gestão de Negócios',
            $body
        );

        return true;
    }

    public function validateAndLogin(string $token, $request): array {

        $this->logger->info('InviteTokenBO->validateAndLogin', ['token' => substr($token, 0, 8) . '...']);

        $invite = UserInviteToken::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invite) {
            return ['ok' => false, 'message' => 'Link inválido ou expirado.'];
        }

        $user = UsersTable::find($invite->user_id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Usuário não encontrado.'];
        }

        // Marca token como utilizado
        $invite->used_at = now();
        $invite->save();

        // Garante que o usuário precisará trocar a senha
        $user->reset_request = 1;
        $user->save();

        // Autentica o usuário
        Auth::login($user);

        // Salva session_token APÓS Auth::login (que regenera o ID da sessão)
        $user->session_token = $request->session()->getId();
        $user->save();

        return ['ok' => true];
    }
}
