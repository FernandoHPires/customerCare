<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\Viabilidades;
use App\Models\Empreendimentos;
use App\Models\UsersTable;

class ViabilidadeBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    // ── Verifica se o usuário logado tem acesso ao empreendimento ────────────
    private function verificarAcessoEmpreendimento($empreendimentoId): bool {
        $user = UsersTable::find(Auth::user()->user_id);

        // Usuários UNI têm acesso irrestrito
        if ($user && $user->is_uni_user === 'S') {
            return true;
        }

        $empreendimento = Empreendimentos::find($empreendimentoId);

        if (!$empreendimento) {
            return false;
        }

        return $user && $empreendimento->cliente_id === $user->default_company_id;
    }

    public function getViabilidades($empreendimentoId) {

        if (!$this->verificarAcessoEmpreendimento($empreendimentoId)) {
            return null;
        }

        $viabilidades = Viabilidades::query()
            ->where('empreendimento_id', $empreendimentoId)
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];
        foreach ($viabilidades as $v) {
            $result[] = $this->mapViabilidade($v);
        }

        return $result;
    }

    public function saveViabilidade($fields) {

        $this->logger->info('ViabilidadeBO->saveViabilidade', [$fields]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            if ($fields->action === 'Adicionar') {
                if (!$this->verificarAcessoEmpreendimento($fields->empreendimentoId)) {
                    return false;
                }
                $viabilidade = new Viabilidades();
                $viabilidade->empreendimento_id = $fields->empreendimentoId;
                $viabilidade->status = 'I';
                $viabilidade->created_by = $userId;
            } else {
                $viabilidade = Viabilidades::find($fields->id);
                if (!$viabilidade) return false;
                if (!$this->verificarAcessoEmpreendimento($viabilidade->empreendimento_id)) {
                    return false;
                }
            }

            $viabilidade->unidades_venda               = $fields->unidadesVenda;
            $viabilidade->valor_aquisicao_terreno      = $fields->valorAquisicaoTerreno;
            $viabilidade->percentual_aquisicao_terreno = $fields->percentualAquisicaoTerreno;
            $viabilidade->percentual_permuta_fisica    = $fields->percentualPermutaFisica;
            $viabilidade->area_total_m2                = $fields->areaTotalM2;
            $viabilidade->valor_previsto               = $fields->valorPrevisto;
            $viabilidade->vgv                          = $fields->vgv;
            $viabilidade->exposicao_caixa              = $fields->exposicaoCaixa;
            $viabilidade->resultado_liquido            = $fields->resultadoLiquido;
            $viabilidade->valor_custo_obra             = $fields->valorCustoObra;
            $viabilidade->percentual_custo_obra        = $fields->percentualCustoObra;
            $viabilidade->valor_comissao               = $fields->valorComissao;
            $viabilidade->percentual_comissao          = $fields->percentualComissao;
            $viabilidade->valor_tributo                = $fields->valorTributo;
            $viabilidade->percentual_tributo           = $fields->percentualTributo;
            $viabilidade->valor_incorporacao           = $fields->valorIncorporacao;
            $viabilidade->percentual_incorporacao      = $fields->percentualIncorporacao;
            $viabilidade->valor_marketing              = $fields->valorMarketing;
            $viabilidade->percentual_marketing         = $fields->percentualMarketing;
            $viabilidade->valor_despesa_obra           = $fields->valorDespesaObra;
            $viabilidade->percentual_despesa_obra      = $fields->percentualDespesaObra;
            $viabilidade->valor_despesa_venda          = $fields->valorDespesaVenda;
            $viabilidade->percentual_despesa_venda     = $fields->percentualDespesaVenda;
            $viabilidade->valor_administracao          = $fields->valorAdministracao;
            $viabilidade->percentual_administracao     = $fields->percentualAdministracao;
            $viabilidade->updated_by                   = $userId;

            if (isset($fields->status) && $fields->status === 'A') {
                Viabilidades::query()
                    ->where('empreendimento_id', $viabilidade->empreendimento_id)
                    ->where('id', '!=', $viabilidade->id)
                    ->update(['status' => 'I', 'updated_by' => $userId]);
                $viabilidade->status = 'A';
            } elseif (isset($fields->status)) {
                $viabilidade->status = $fields->status;
            }

            $viabilidade->save();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->info('ViabilidadeBO->saveViabilidade', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function ativarViabilidade($id) {

        $this->logger->info('ViabilidadeBO->ativarViabilidade', ['id' => $id]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            $viabilidade = Viabilidades::find($id);
            if (!$viabilidade) return false;
            if (!$this->verificarAcessoEmpreendimento($viabilidade->empreendimento_id)) {
                return false;
            }

            Viabilidades::query()
                ->where('empreendimento_id', $viabilidade->empreendimento_id)
                ->where('id', '!=', $id)
                ->update(['status' => 'I', 'updated_by' => $userId]);

            $viabilidade->status = 'A';
            $viabilidade->updated_by = $userId;
            $viabilidade->save();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->info('ViabilidadeBO->ativarViabilidade', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function deleteViabilidade($id) {

        $this->logger->info('ViabilidadeBO->deleteViabilidade', ['id' => $id]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            $viabilidade = Viabilidades::find($id);
            if (!$viabilidade) return false;
            if (!$this->verificarAcessoEmpreendimento($viabilidade->empreendimento_id)) {
                return false;
            }

            $viabilidade->deleted_by = $userId;
            $viabilidade->save();
            $viabilidade->delete();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->info('ViabilidadeBO->deleteViabilidade', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    private function mapViabilidade($v) {
        return [
            'viabilidadeId'              => $v->id,
            'empreendimentoId'           => $v->empreendimento_id,
            'status'                     => $v->status,
            'unidadesVenda'              => $v->unidades_venda,
            'valorAquisicaoTerreno'      => $v->valor_aquisicao_terreno,
            'percentualAquisicaoTerreno' => $v->percentual_aquisicao_terreno,
            'percentualPermutaFisica'    => $v->percentual_permuta_fisica,
            'areaTotalM2'                => $v->area_total_m2,
            'valorPrevisto'              => $v->valor_previsto,
            'vgv'                        => $v->vgv,
            'exposicaoCaixa'             => $v->exposicao_caixa,
            'resultadoLiquido'           => $v->resultado_liquido,
            'valorCustoObra'             => $v->valor_custo_obra,
            'percentualCustoObra'        => $v->percentual_custo_obra,
            'valorComissao'              => $v->valor_comissao,
            'percentualComissao'         => $v->percentual_comissao,
            'valorTributo'               => $v->valor_tributo,
            'percentualTributo'          => $v->percentual_tributo,
            'valorIncorporacao'          => $v->valor_incorporacao,
            'percentualIncorporacao'     => $v->percentual_incorporacao,
            'valorMarketing'             => $v->valor_marketing,
            'percentualMarketing'        => $v->percentual_marketing,
            'valorDespesaObra'           => $v->valor_despesa_obra,
            'percentualDespesaObra'      => $v->percentual_despesa_obra,
            'valorDespesaVenda'          => $v->valor_despesa_venda,
            'percentualDespesaVenda'     => $v->percentual_despesa_venda,
            'valorAdministracao'         => $v->valor_administracao,
            'percentualAdministracao'    => $v->percentual_administracao,
        ];
    }
}
