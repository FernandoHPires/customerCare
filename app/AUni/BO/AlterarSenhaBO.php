<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\Models\UsersTable;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AlterarSenhaBO {

    private $logger;

    const HISTORICO_LIMITE = 5;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function alterarSenha($fields) {

        $this->logger->info('AlterarSenhaBO->alterarSenha', []);

        $userId = Auth::user()->user_id;

        $user = UsersTable::find($userId);
        if (!$user) {
            return ['ok' => false, 'message' => 'Usuário não encontrado.'];
        }

        // Verifica senha atual
        if (!Hash::check($fields->senhaAtual, $user->user_password)) {
            return ['ok' => false, 'message' => 'Senha atual incorreta.'];
        }

        // Valida força da nova senha
        $validacao = $this->validarForca($fields->novaSenha);
        if (!$validacao['ok']) {
            return $validacao;
        }

        // Verifica histórico das últimas 5 senhas
        $historico = PasswordHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(self::HISTORICO_LIMITE)
            ->get();

        foreach ($historico as $registro) {
            if (Hash::check($fields->novaSenha, $registro->password_hash)) {
                return ['ok' => false, 'message' => 'A nova senha não pode ser igual a uma das últimas ' . self::HISTORICO_LIMITE . ' senhas utilizadas.'];
            }
        }

        DB::beginTransaction();

        try {

            // Salva senha antiga no histórico
            PasswordHistory::create([
                'user_id'       => $userId,
                'password_hash' => $user->user_password,
                'created_at'    => now(),
                'created_by'    => $userId,
            ]);

            // Mantém apenas os últimos N registros no histórico
            $ids = PasswordHistory::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->skip(self::HISTORICO_LIMITE)
                ->values();

            if ($ids->count() > 0) {
                PasswordHistory::whereIn('id', $ids)->delete();
            }

            // Atualiza a senha
            $user->user_password = Hash::make($fields->novaSenha);
            $user->updated_by    = $userId;
            $user->save();

            DB::commit();
            return ['ok' => true, 'message' => 'Senha alterada com sucesso!'];

        } catch (\Throwable $e) {

            $this->logger->info('AlterarSenhaBO->alterarSenha', [$e->getMessage(), $e->getTraceAsString()]);
            DB::rollback();
            return ['ok' => false, 'message' => 'Erro ao alterar senha.'];
        }
    }

    private function validarForca($senha) {

        if (strlen($senha) < 8) {
            return ['ok' => false, 'message' => 'A senha deve ter no mínimo 8 caracteres.'];
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos uma letra maiúscula.'];
        }
        if (!preg_match('/[0-9]/', $senha)) {
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos um número.'];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos um caractere especial (!@#$%...).'];
        }

        return ['ok' => true, 'message' => ''];
    }
}
