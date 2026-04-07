<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\UsersTable;
use App\Models\Viabilidades;
use App\Models\Empreendimentos;
use App\Models\Clientes;


class EmpreendimentoBO {

    private $logger;
    private $db;
    

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function saveEmpreendimento($fields) {

        $this->logger->info('EmpreendimentoBO->saveEmpreendimento 333', [$fields]);

        $userId = Auth::user()->user_id;

        $usersTable = UsersTable::Query()
        ->where('user_id', $userId)
        ->first();        

        $this->db->beginTransaction();

        try {

            if ($fields->action == 'Adicionar') {
                $empreendimento = new Empreendimentos();
                $clienteId = $usersTable->cliente_id;
            } else {
                $clienteId = $fields->clienteId;
                $empreendimento = Empreendimentos::find($fields->id);
                if (!$empreendimento) {
                    return false;
                }
            }
            $empreendimento->cliente_id = $clienteId;
            $empreendimento->nome = $fields->nome;
            $empreendimento->cidade = $fields->cidade;
            $empreendimento->uf = $fields->uf;
            $empreendimento->tipo_produto = $fields->tipoProduto;
            $empreendimento->total_unidades = $fields->totalUnidades;
            $empreendimento->unidades_permutadas = $fields->unidadesPermutadas;
            $empreendimento->participacao_resultados = $fields->participacaoResultados;
            $empreendimento->percentual_resultado_liquido = $fields->percentualResultadoLiquido;
            $empreendimento->tir_aa = $fields->tirAa;
            $empreendimento->mtir = $fields->mtir;
            $empreendimento->percentual_participacao_spe = $fields->percentualParticipacaoSpe;
            $empreendimento->valor_participacao_spe = $fields->valorParticipacaoSpe;
            $empreendimento->fase_atual = $fields->faseAtual;
            $empreendimento->previsao_lancamento = $fields->previsaoLancamento;
            $empreendimento->previsao_inicio_obras = $fields->previsaoInicioObras;
            $empreendimento->previsao_entrega = $fields->previsaoEntrega;
            $empreendimento->status_empreendimento = $fields->statusEmpreendimento;
            if ($fields->action == 'Adicionar') {
                $empreendimento->created_by = $userId;
            } else {
                $empreendimento->updated_by = $userId;
            }
            $empreendimento->save();

            $this->db->commit();       
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('EmpreendimentoBO->saveEmpreendimento', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function deleteEmpreendimento($id) {

        $this->logger->info('EmpreendimentoBO->deleteEmpreendimento', ['id' => $id]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {
            $empreendimento = Empreendimentos::find($id);
            if (!$empreendimento) {
                return false;
            }
            $empreendimento->deleted_by = $userId;
            $empreendimento->save();
            $empreendimento->delete();

            $this->db->commit();       
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('EmpreendimentoBO->deleteEmpreendimento', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

        

    
}