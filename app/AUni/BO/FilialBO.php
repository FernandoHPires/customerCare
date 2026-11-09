<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\Clientes;
use App\Models\Filiais;
use App\Models\FiliaisEmpreendimentos;

class FilialBO {

    private $logger;
    private $db;
    private $mensagem;

    public function __construct(ILogger $logger, IDB $db) {

        $this->logger = $logger;
        $this->db = $db;
    }

    public function getMensagem() {

        return $this->mensagem;
    }

    public function getFiliais() {

        $this->logger->info('FilialBO->getFiliais', []);

        $filiais = Filiais::orderBy('nome', 'asc')->get();

        $result = [];
        foreach ($filiais as $f) {
            $result[] = [
                'id'     => $f->id,
                'codigo' => (int) $f->codigo,
                'nome'   => $f->nome,
                'contasReceber' => $f->contas_receber,
            ];
        }

        return $result;
    }

    public function saveFilial($fields) {

        $this->logger->info('FilialBO->saveFilial', [$fields]);

        $codigo = (int) ($fields->codigo ?? 0);
        $nome = trim($fields->nome ?? '');
        $contasReceber = $fields->contasReceber ?? '';

        if ($codigo <= 0 || $nome === '') {
            $this->mensagem = 'Informe o código e o nome da filial.';
            return false;
        }

        if (!in_array($contasReceber, ['CAR', 'COR', 'CRR'], true)) {
            $this->mensagem = 'Selecione uma opção de Contas a Receber.';
            return false;
        }

        $id = $fields->action == 'Adicionar' ? null : $fields->id;

        if ($this->codigoEmUso($codigo, $id)) {
            $this->mensagem = 'Já existe uma filial com este código.';
            return false;
        }

        $filial = $id === null ? new Filiais() : Filiais::find($id);
        if (!$filial) {
            return false;
        }

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            if ($id === null) {
                $filial->created_by = $userId;
            } else {
                $filial->updated_by = $userId;

                if ((int) $filial->codigo !== $codigo) {
                    Clientes::where('filial', $filial->codigo)->update(['filial' => $codigo]);
                }
            }

            $filial->codigo = $codigo;
            $filial->nome   = $nome;
            $filial->contas_receber = $contasReceber;
            $filial->save();

            $this->db->commit();
            return $filial->id;

        } catch (\Throwable $e) {

            $this->logger->info('FilialBO->saveFilial', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function deleteFilial($id) {

        $this->logger->info('FilialBO->deleteFilial', ['id' => $id]);

        $filial = Filiais::find($id);
        if (!$filial) {
            return false;
        }

        if (Clientes::where('filial', $filial->codigo)->exists()) {
            $this->mensagem = 'Esta filial está vinculada a um cliente e não pode ser excluída.';
            return false;
        }

        if (FiliaisEmpreendimentos::where('filial_id', $filial->id)->exists()) {
            $this->mensagem = 'Esta filial possui empreendimentos. Exclua os empreendimentos antes de excluir a filial.';
            return false;
        }

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            $filial->deleted_by = $userId;
            $filial->save();
            $filial->delete();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('FilialBO->deleteFilial', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function getEmpreendimentos($filialId) {

        $this->logger->info('FilialBO->getEmpreendimentos', ['filialId' => $filialId]);

        return FiliaisEmpreendimentos::where('filial_id', $filialId)
            ->orderBy('codigo', 'asc')
            ->get()
            ->map(fn($e) => [
                'id'        => $e->id,
                'codigo'    => (int) $e->codigo,
                'descricao' => $e->descricao,
            ])
            ->all();
    }

    public function saveEmpreendimento($fields) {

        $this->logger->info('FilialBO->saveEmpreendimento', [$fields]);

        $filialId = (int) ($fields->filialId ?? 0);
        $codigo = (int) ($fields->codigo ?? 0);
        $descricao = trim($fields->descricao ?? '');

        if (!empty($fields->id)) {
            return $this->updateEmpreendimento((int) $fields->id, $filialId, $descricao);
        }

        if ($codigo <= 0 || $descricao === '') {
            $this->mensagem = 'Informe o código e a descrição do empreendimento.';
            return false;
        }

        if (!Filiais::find($filialId)) {
            $this->mensagem = 'Filial não encontrada.';
            return false;
        }

        $existente = FiliaisEmpreendimentos::where('codigo', $codigo)->first();
        if ($existente) {
            $filial = Filiais::find($existente->filial_id);
            $this->mensagem = 'Este empreendimento já está cadastrado' . ($filial ? ' na filial ' . $filial->codigo . ' - ' . $filial->nome : '') . '.';
            return false;
        }

        try {

            $empreendimento = new FiliaisEmpreendimentos();
            $empreendimento->filial_id = $filialId;
            $empreendimento->codigo = $codigo;
            $empreendimento->descricao = $descricao;
            $empreendimento->created_by = Auth::user()->user_id;
            $empreendimento->save();

            return $empreendimento->id;

        } catch (\Throwable $e) {

            $this->logger->info('FilialBO->saveEmpreendimento', [$e->getMessage(), $e->getTraceAsString()]);
            return false;
        }
    }

    private function updateEmpreendimento($id, $filialId, $descricao) {

        if ($descricao === '') {
            $this->mensagem = 'Informe a descrição do empreendimento.';
            return false;
        }

        $empreendimento = FiliaisEmpreendimentos::where('id', $id)->where('filial_id', $filialId)->first();
        if (!$empreendimento) {
            $this->mensagem = 'Empreendimento não encontrado.';
            return false;
        }

        try {

            $empreendimento->descricao = $descricao;
            $empreendimento->updated_by = Auth::user()->user_id;
            $empreendimento->save();

            return $empreendimento->id;

        } catch (\Throwable $e) {

            $this->logger->info('FilialBO->updateEmpreendimento', [$e->getMessage(), $e->getTraceAsString()]);
            return false;
        }
    }

    public function deleteEmpreendimento($id) {

        $this->logger->info('FilialBO->deleteEmpreendimento', ['id' => $id]);

        $empreendimento = FiliaisEmpreendimentos::find($id);
        if (!$empreendimento) {
            return false;
        }

        try {

            $empreendimento->deleted_by = Auth::user()->user_id;
            $empreendimento->save();
            $empreendimento->delete();

            return true;

        } catch (\Throwable $e) {

            $this->logger->info('FilialBO->deleteEmpreendimento', [$e->getMessage(), $e->getTraceAsString()]);
            return false;
        }
    }

    private function codigoEmUso($codigo, $id) {

        return Filiais::where('codigo', $codigo)
            ->when($id !== null, fn($q) => $q->where('id', '!=', $id))
            ->exists();
    }
}
