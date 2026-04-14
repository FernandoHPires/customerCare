<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\UsersTable;
use App\Models\Clientes;
use App\Models\Simulacoes;

class SimulacaoBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    private function getClienteId() {
        $userId = Auth::user()->user_id;

        $user = UsersTable::query()
            ->where('user_id', $userId)
            ->first();

        if (!$user) return null;

        $cliente = Clientes::query()
            ->where('id', $user->default_company_id)
            ->first();

        return $cliente ? $cliente->id : null;
    }

    public function getSimulacao() {

        $this->logger->info('SimulacaoBO->getSimulacao');

        $clienteId = $this->getClienteId();
        if (!$clienteId) return null;

        $simulacao = Simulacoes::query()
            ->where('cliente_id', $clienteId)
            ->first();

        if (!$simulacao) return null;

        return $this->mapSimulacao($simulacao);
    }

    public function saveSimulacao($fields) {

        $this->logger->info('SimulacaoBO->saveSimulacao', [$fields]);

        $userId    = Auth::user()->user_id;
        $clienteId = $this->getClienteId();

        $this->logger->info('SimulacaoBO->saveSimulacao clienteId', ['clienteId' => $clienteId]);

        if (!$clienteId) return false;

        $this->db->beginTransaction();

        try {

            Simulacoes::updateOrCreate(
                ['cliente_id' => $clienteId],
                [
                    'unidades_venda'               => $fields->unidadesVenda              ?? null,
                    'valor_aquisicao_terreno'       => $fields->valorAquisicaoTerreno      ?? null,
                    'percentual_aquisicao_terreno'  => $fields->percentualAquisicaoTerreno ?? null,
                    'percentual_permuta_fisica'     => $fields->percentualPermutaFisica    ?? null,
                    'area_total_m2'                 => $fields->areaTotalM2                ?? null,
                    'valor_previsto'                => $fields->valorPrevisto              ?? null,
                    'vgv'                           => $fields->vgv                        ?? null,
                    'exposicao_caixa'               => $fields->exposicaoCaixa             ?? null,
                    'resultado_liquido'             => $fields->resultadoLiquido           ?? null,
                    'valor_custo_obra'              => $fields->valorCustoObra             ?? null,
                    'percentual_custo_obra'         => $fields->percentualCustoObra        ?? null,
                    'valor_comissao'                => $fields->valorComissao              ?? null,
                    'percentual_comissao'           => $fields->percentualComissao         ?? null,
                    'valor_tributo'                 => $fields->valorTributo               ?? null,
                    'percentual_tributo'            => $fields->percentualTributo          ?? null,
                    'valor_incorporacao'            => $fields->valorIncorporacao          ?? null,
                    'percentual_incorporacao'       => $fields->percentualIncorporacao     ?? null,
                    'valor_marketing'               => $fields->valorMarketing             ?? null,
                    'percentual_marketing'          => $fields->percentualMarketing        ?? null,
                    'valor_despesa_obra'            => $fields->valorDespesaObra           ?? null,
                    'percentual_despesa_obra'       => $fields->percentualDespesaObra      ?? null,
                    'valor_despesa_venda'           => $fields->valorDespesaVenda          ?? null,
                    'percentual_despesa_venda'      => $fields->percentualDespesaVenda     ?? null,
                    'valor_administracao'           => $fields->valorAdministracao         ?? null,
                    'percentual_administracao'      => $fields->percentualAdministracao    ?? null,
                    'updated_by'                    => $userId,
                ]
            );

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->info('SimulacaoBO->saveSimulacao error', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    private function mapSimulacao($s) {
        return [
            'simulacaoId'                => $s->id,
            'clienteId'                  => $s->cliente_id,
            'unidadesVenda'              => $s->unidades_venda,
            'valorAquisicaoTerreno'      => $s->valor_aquisicao_terreno,
            'percentualAquisicaoTerreno' => $s->percentual_aquisicao_terreno,
            'percentualPermutaFisica'    => $s->percentual_permuta_fisica,
            'areaTotalM2'                => $s->area_total_m2,
            'valorPrevisto'              => $s->valor_previsto,
            'vgv'                        => $s->vgv,
            'exposicaoCaixa'             => $s->exposicao_caixa,
            'resultadoLiquido'           => $s->resultado_liquido,
            'valorCustoObra'             => $s->valor_custo_obra,
            'percentualCustoObra'        => $s->percentual_custo_obra,
            'valorComissao'              => $s->valor_comissao,
            'percentualComissao'         => $s->percentual_comissao,
            'valorTributo'               => $s->valor_tributo,
            'percentualTributo'          => $s->percentual_tributo,
            'valorIncorporacao'          => $s->valor_incorporacao,
            'percentualIncorporacao'     => $s->percentual_incorporacao,
            'valorMarketing'             => $s->valor_marketing,
            'percentualMarketing'        => $s->percentual_marketing,
            'valorDespesaObra'           => $s->valor_despesa_obra,
            'percentualDespesaObra'      => $s->percentual_despesa_obra,
            'valorDespesaVenda'          => $s->valor_despesa_venda,
            'percentualDespesaVenda'     => $s->percentual_despesa_venda,
            'valorAdministracao'         => $s->valor_administracao,
            'percentualAdministracao'    => $s->percentual_administracao,
        ];
    }
}
