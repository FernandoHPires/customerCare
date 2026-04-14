<?php

namespace App\Models;

class Simulacoes extends ModelFingerprint {

    protected $table = 'simulacoes';

    protected $fillable = [
        'cliente_id',
        'unidades_venda',
        'valor_aquisicao_terreno',
        'percentual_aquisicao_terreno',
        'percentual_permuta_fisica',
        'area_total_m2',
        'valor_previsto',
        'vgv',
        'exposicao_caixa',
        'resultado_liquido',
        'valor_custo_obra',
        'percentual_custo_obra',
        'valor_comissao',
        'percentual_comissao',
        'valor_tributo',
        'percentual_tributo',
        'valor_incorporacao',
        'percentual_incorporacao',
        'valor_marketing',
        'percentual_marketing',
        'valor_despesa_obra',
        'percentual_despesa_obra',
        'valor_despesa_venda',
        'percentual_despesa_venda',
        'valor_administracao',
        'percentual_administracao',
        'created_by',
        'updated_by',
    ];

}
