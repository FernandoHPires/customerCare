<template>
    <div class="card">
        <div class="card-body d-flex justify-content-center">
            <div class="simulacao-container p-3">

                <!-- ─── Dados do Empreendimento ──────────────────────────── -->
                <h6 class="secao-titulo">Dados do Empreendimento</h6>
                <div class="row g-3 mb-4">

                    <div class="col-md-3">
                        <label class="form-label">Nº de unidades - Estoque</label>
                        <input type="number" class="form-control" v-model.number="form.unidadesVenda" @blur="autoSave" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Área total m²</label>
                        <input type="number" step="0.01" class="form-control" v-model.number="form.areaTotalM2" @blur="autoSave" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor m²</label>
                        <input type="number" step="0.01" class="form-control" v-model.number="form.valorPrevisto" @blur="autoSave" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">% Permuta Física / Financ.</label>
                        <input type="number" step="0.01" class="form-control" v-model.number="form.percentualPermutaFisica" @blur="autoSave" />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Exposição de Caixa (R$)</label>
                        <input type="number" step="0.01" class="form-control" v-model.number="form.exposicaoCaixa" @blur="autoSave" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Receita bruta da venda das unidades (VGV)</label>
                        <div class="campo-calculado">{{ formatarReais(vgv) }}</div>
                        <small class="text-muted">Calculado: Área total m² × Valor m²</small>
                    </div>

                </div>

                <!-- ─── Descritivo Financeiro ────────────────────────────── -->
                <h6 class="secao-titulo">Descritivo Financeiro</h6>
                <p class="text-muted small mb-2">
                    Informe o percentual de cada item — o valor em R$ é calculado automaticamente sobre o VGV.
                </p>

                <table class="table table-sm tabela-dre mb-4">
                    <thead>
                        <tr>
                            <th>Descritivo Financeiro</th>
                            <th class="col-reais text-end">Valor (R$)</th>
                            <th class="col-percentual text-center">(%)</th>
                        </tr>
                    </thead>
                    <tbody>

                        <!-- VGV -->
                        <tr class="linha-vgv">
                            <td><strong>(+) Receita bruta da venda das unidades (VGV)</strong></td>
                            <td class="text-end"><span class="badge-calculado badge-vgv">{{ formatarReais(vgv) }}</span></td>
                            <td class="text-center"><span class="badge-calculado badge-vgv">100,00%</span></td>
                        </tr>

                        <!-- Comissões -->
                        <tr>
                            <td>(-) Comissões e premiações de venda</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.comissoesReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualComissao" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Tributos -->
                        <tr>
                            <td>(-) Tributos sobre a receita de venda</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.tributosReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualTributo" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Incorporação -->
                        <tr>
                            <td>(-) Incorporação</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.incorporacaoReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualIncorporacao" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- MKT -->
                        <tr>
                            <td>(-) MKT</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.marketingReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualMarketing" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Obras totais -->
                        <tr>
                            <td>(-) Obras totais</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.obrasReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualCustoObra" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Gestão de Vendas -->
                        <tr>
                            <td>(-) Gestão de Vendas</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.gestaoVendasReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualDespesaVenda" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Administração -->
                        <tr>
                            <td>(-) Administração</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.administracaoReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualAdministracao" @blur="autoSave" />
                            </td>
                        </tr>

                        <!-- Terreno -->
                        <tr>
                            <td>(-) Terreno</td>
                            <td class="text-end"><span class="badge-calculado">{{ formatarReais(dre.terrenoReais) }}</span></td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm"
                                    v-model.number="form.percentualAquisicaoTerreno" @blur="autoSave" />
                            </td>
                        </tr>

                    </tbody>
                </table>

                <!-- ─── Resultados ─────────────────────────────────────────── -->
                <h6 class="secao-titulo">Resultados</h6>
                <div class="row g-3">

                    <!-- (=) Receita Líquida -->
                    <div class="col-md-4">
                        <div class="tooltip-wrapper">
                            <div class="resultado-card">
                                <div class="resultado-label">(=) Receita líquida da venda das unidades</div>
                                <div class="resultado-valor">{{ formatarReais(dre.receitaLiquida) }}</div>
                                <div class="resultado-pct">{{ formatarPorcentagem(dre.receitaLiquidaPct) }}</div>
                            </div>
                            <div class="tooltip-calculo">
                                <div class="tooltip-titulo">Como é calculado</div>
                                <div class="tooltip-linha">
                                    <span>(+) VGV</span>
                                    <span>{{ formatarReais(vgv) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Comissões ({{ form.percentualComissao }}%)</span>
                                    <span>{{ formatarReais(dre.comissoesReais) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Tributos ({{ form.percentualTributo }}%)</span>
                                    <span>{{ formatarReais(dre.tributosReais) }}</span>
                                </div>
                                <div class="tooltip-divisor"></div>
                                <div class="tooltip-linha tooltip-resultado">
                                    <span>(=) Receita Líquida</span>
                                    <span>{{ formatarReais(dre.receitaLiquida) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- (=) Resultado Operacional -->
                    <div class="col-md-4">
                        <div class="tooltip-wrapper">
                            <div class="resultado-card">
                                <div class="resultado-label">(=) Resultado operacional</div>
                                <div class="resultado-valor">{{ formatarReais(dre.resultadoOperacional) }}</div>
                                <div class="resultado-pct">{{ formatarPorcentagem(dre.resultadoOperacionalPct) }}</div>
                            </div>
                            <div class="tooltip-calculo">
                                <div class="tooltip-titulo">Como é calculado</div>
                                <div class="tooltip-linha">
                                    <span>(+) Receita Líquida</span>
                                    <span>{{ formatarReais(dre.receitaLiquida) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Incorporação ({{ form.percentualIncorporacao }}%)</span>
                                    <span>{{ formatarReais(dre.incorporacaoReais) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) MKT ({{ form.percentualMarketing }}%)</span>
                                    <span>{{ formatarReais(dre.marketingReais) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Obras totais ({{ form.percentualCustoObra }}%)</span>
                                    <span>{{ formatarReais(dre.obrasReais) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Gestão de Vendas ({{ form.percentualDespesaVenda }}%)</span>
                                    <span>{{ formatarReais(dre.gestaoVendasReais) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Administração ({{ form.percentualAdministracao }}%)</span>
                                    <span>{{ formatarReais(dre.administracaoReais) }}</span>
                                </div>
                                <div class="tooltip-divisor"></div>
                                <div class="tooltip-linha tooltip-resultado">
                                    <span>(=) Resultado Operacional</span>
                                    <span>{{ formatarReais(dre.resultadoOperacional) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- (=) Resultado Líquido -->
                    <div class="col-md-4">
                        <div class="tooltip-wrapper">
                            <div class="resultado-card"
                                :class="{
                                    'resultado-card--positivo': dre.resultadoLiquido > 0,
                                    'resultado-card--negativo': dre.resultadoLiquido < 0,
                                }"
                            >
                                <div class="resultado-label">(=) Resultado líquido</div>
                                <div class="resultado-valor">{{ formatarReais(dre.resultadoLiquido) }}</div>
                                <div class="resultado-pct">{{ formatarPorcentagem(dre.resultadoLiquidoPct) }}</div>
                            </div>
                            <div class="tooltip-calculo">
                                <div class="tooltip-titulo">Como é calculado</div>
                                <div class="tooltip-linha">
                                    <span>(+) Resultado Operacional</span>
                                    <span>{{ formatarReais(dre.resultadoOperacional) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Terreno ({{ form.percentualAquisicaoTerreno }}%)</span>
                                    <span>{{ formatarReais(dre.terrenoReais) }}</span>
                                </div>
                                <div class="tooltip-divisor"></div>
                                <div class="tooltip-linha tooltip-resultado">
                                    <span>(=) Resultado Líquido</span>
                                    <span>{{ formatarReais(dre.resultadoLiquido) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Participação Taboada -->
                    <div class="col-md-6">
                        <div class="tooltip-wrapper">
                            <div class="resultado-card resultado-card--neutro">
                                <div class="resultado-label">Participação Taboada (7% do Resultado Líquido)</div>
                                <div class="resultado-valor">{{ formatarReais(participacaoTaboada) }}</div>
                            </div>
                            <div class="tooltip-calculo">
                                <div class="tooltip-titulo">Como é calculado</div>
                                <div class="tooltip-linha">
                                    <span>Resultado Líquido</span>
                                    <span>{{ formatarReais(dre.resultadoLiquido) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>× Participação Taboada</span>
                                    <span>7%</span>
                                </div>
                                <div class="tooltip-divisor"></div>
                                <div class="tooltip-linha tooltip-resultado">
                                    <span>(=) Participação Taboada</span>
                                    <span>{{ formatarReais(participacaoTaboada) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado Líquido após Part. Taboada % -->
                    <div class="col-md-6">
                        <div class="tooltip-wrapper">
                            <div class="resultado-card resultado-card--neutro">
                                <div class="resultado-label">Resultado Líquido após Part. Taboada (%)</div>
                                <div class="resultado-valor">{{ formatarPorcentagem(resultadoLiquidoAposParticipacaoPct) }}</div>
                            </div>
                            <div class="tooltip-calculo">
                                <div class="tooltip-titulo">Como é calculado</div>
                                <div class="tooltip-linha">
                                    <span>Resultado Líquido</span>
                                    <span>{{ formatarReais(dre.resultadoLiquido) }}</span>
                                </div>
                                <div class="tooltip-linha tooltip-deducao">
                                    <span>(-) Participação Taboada</span>
                                    <span>{{ formatarReais(participacaoTaboada) }}</span>
                                </div>
                                <div class="tooltip-linha">
                                    <span>÷ VGV</span>
                                    <span>{{ formatarReais(vgv) }}</span>
                                </div>
                                <div class="tooltip-divisor"></div>
                                <div class="tooltip-linha tooltip-resultado">
                                    <span>(=) % do VGV</span>
                                    <span>{{ formatarPorcentagem(resultadoLiquidoAposParticipacaoPct) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';

export default {
    mixins: [util],

    data() {
        return {
            debounceTimer: null,
            form: {
                unidadesVenda:               0,
                areaTotalM2:                 0,
                valorPrevisto:               0,
                percentualPermutaFisica:     0,
                exposicaoCaixa:              0,
                // Percentuais digitados pelo usuário — os valores em R$ são calculados automaticamente
                percentualComissao:          0,
                percentualTributo:           0,
                percentualIncorporacao:      0,
                percentualMarketing:         0,
                percentualCustoObra:         0,
                percentualDespesaVenda:      0,
                percentualAdministracao:     0,
                percentualAquisicaoTerreno:  0,
            },
        };
    },

    computed: {

        // ─── VGV: calculado a partir da área total e do valor por m² ──────
        vgv() {
            const areaTotalM2  = parseFloat(this.form.areaTotalM2)  || 0;
            const valorPorM2   = parseFloat(this.form.valorPrevisto) || 0;
            return areaTotalM2 * valorPorM2;
        },

        // ─── DRE — Demonstrativo de Resultado do Empreendimento ───────────
        // Cada linha segue o fluxo da planilha:
        //   VGV → (-) Comissões → (-) Tributos → (=) Receita Líquida
        //       → (-) custos operacionais       → (=) Resultado Operacional
        //       → (-) Terreno                   → (=) Resultado Líquido
        dre() {
            const vgv = this.vgv;

            // Deduções diretas sobre o VGV
            const comissoesReais      = this.sobreVgv(vgv, this.form.percentualComissao);
            const tributosReais       = this.sobreVgv(vgv, this.form.percentualTributo);
            const receitaLiquida      = vgv - comissoesReais - tributosReais;

            // Custos operacionais (calculados sobre o VGV, conforme planilha)
            const incorporacaoReais   = this.sobreVgv(vgv, this.form.percentualIncorporacao);
            const marketingReais      = this.sobreVgv(vgv, this.form.percentualMarketing);
            const obrasReais          = this.sobreVgv(vgv, this.form.percentualCustoObra);
            const gestaoVendasReais   = this.sobreVgv(vgv, this.form.percentualDespesaVenda);
            const administracaoReais  = this.sobreVgv(vgv, this.form.percentualAdministracao);

            const resultadoOperacional = receitaLiquida
                - incorporacaoReais
                - marketingReais
                - obrasReais
                - gestaoVendasReais
                - administracaoReais;

            // Terreno
            const terrenoReais        = this.sobreVgv(vgv, this.form.percentualAquisicaoTerreno);
            const resultadoLiquido    = resultadoOperacional - terrenoReais;

            // Percentuais sobre o VGV para os cards de resultado
            const receitaLiquidaPct         = vgv > 0 ? (receitaLiquida      / vgv) * 100 : 0;
            const resultadoOperacionalPct   = vgv > 0 ? (resultadoOperacional / vgv) * 100 : 0;
            const resultadoLiquidoPct       = vgv > 0 ? (resultadoLiquido     / vgv) * 100 : 0;

            return {
                comissoesReais,
                tributosReais,
                receitaLiquida,
                receitaLiquidaPct,
                incorporacaoReais,
                marketingReais,
                obrasReais,
                gestaoVendasReais,
                administracaoReais,
                resultadoOperacional,
                resultadoOperacionalPct,
                terrenoReais,
                resultadoLiquido,
                resultadoLiquidoPct,
            };
        },

        // ─── Participação Taboada (7% do Resultado Líquido) ──────────────
        participacaoTaboada() {
            return this.dre.resultadoLiquido * 0.07;
        },

        // ─── Resultado líquido após descontar a participação Taboada ─────
        resultadoLiquidoAposParticipacaoPct() {
            const vgv = this.vgv;
            if (!vgv) return 0;
            return ((this.dre.resultadoLiquido - this.participacaoTaboada) / vgv) * 100;
        },

    },

    mounted() {
        this.load();
    },

    methods: {

        // ─── Fórmulas base ─────────────────────────────────────────────────
        // Aplica um percentual sobre o VGV e retorna o valor em R$
        sobreVgv(vgv, percentual) {
            return vgv * ((parseFloat(percentual) || 0) / 100);
        },

        // ─── Formatação ────────────────────────────────────────────────────
        formatarReais(valor) {
            const n = parseFloat(valor) || 0;
            return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatarPorcentagem(valor) {
            const n = parseFloat(valor) || 0;
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
        },

        // ─── Carregamento ──────────────────────────────────────────────────
        load() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/simulacao' })
                .then((response) => {
                    if (this.checkApiResponse(response) && response.data.data) {
                        this.form = { ...this.form, ...response.data.data };
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        // ─── Salvamento ────────────────────────────────────────────────────
        // Persiste no banco os percentuais digitados e os valores em R$ calculados
        autoSave() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                const payload = {
                    ...this.form,
                    // Valores calculados — salvos para uso no portfólio e relatórios
                    vgv:                    this.vgv,
                    resultadoLiquido:       this.dre.resultadoLiquido,
                    valorAquisicaoTerreno:  this.dre.terrenoReais,
                    valorCustoObra:         this.dre.obrasReais,
                    valorComissao:          this.dre.comissoesReais,
                    valorTributo:           this.dre.tributosReais,
                    valorIncorporacao:      this.dre.incorporacaoReais,
                    valorMarketing:         this.dre.marketingReais,
                    valorDespesaVenda:      this.dre.gestaoVendasReais,
                    valorAdministracao:     this.dre.administracaoReais,
                    valorDespesaObra:       0,   // Gestão de Obra não é mais separada
                    percentualDespesaObra:  0,
                };
                this.axios({ method: 'post', url: '/web/simulacao', data: payload })
                    .catch(() => {});
            }, 400);
        },

    },
};
</script>

<style scoped>

/* ─── Container principal ──────────────────────────── */
.simulacao-container {
    width: 100%;
    max-width: 820px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 10px;
}

/* ─── Títulos de seção ─────────────────────────────── */
.secao-titulo {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #124C60;
    border-bottom: 2px solid #124C60;
    padding-bottom: 5px;
    margin-bottom: 14px;
}

/* ─── Labels e inputs ──────────────────────────────── */
.form-label {
    margin-bottom: 2px;
    font-size: 0.8rem;
    color: #555;
}

/* ─── Campo calculado (VGV no topo) ────────────────── */
.campo-calculado {
    display: flex;
    align-items: center;
    height: 38px;
    padding: 6px 12px;
    background: #e0eff5;
    border: 1px solid #b6d4e3;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.95rem;
    color: #124C60;
}

/* ─── Tabela DRE ────────────────────────────────────── */
.tabela-dre thead th {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #124C60;
    background: #e8f4f8;
    border-bottom: 2px solid #b6d4e3;
    padding: 9px 12px;
}
.tabela-dre td {
    vertical-align: middle;
    padding: 5px 12px;
    font-size: 0.875rem;
    color: #333;
    border-bottom: 1px solid #f0f0f0;
}
.tabela-dre .col-reais      { width: 190px; }
.tabela-dre .col-percentual { width: 110px; }

/* Linha do VGV (destaque) */
.linha-vgv td {
    background: #e8f4f8;
    padding-top: 8px;
    padding-bottom: 8px;
}

/* ─── Badge de valor calculado ─────────────────────── */
.badge-calculado {
    display: inline-block;
    font-weight: 500;
    font-size: 0.82rem;
    padding: 3px 10px;
    background: #e0eff5;
    color: #124C60;
    border-radius: 4px;
    min-width: 155px;
    text-align: right;
}
.badge-vgv {
    font-weight: 700;
    background: #124C60;
    color: #fff;
}

/* ─── Cards de resultado ───────────────────────────── */
.resultado-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 14px 16px;
    text-align: center;
}
.resultado-card--positivo {
    background: #d1fae5;
    border-color: #6ee7b7;
}
.resultado-card--negativo {
    background: #fee2e2;
    border-color: #fca5a5;
}
.resultado-card--neutro {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.resultado-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #555;
    margin-bottom: 6px;
}
.resultado-valor {
    font-size: 1rem;
    font-weight: 700;
    color: #124C60;
}
.resultado-pct {
    font-size: 0.8rem;
    color: #666;
    margin-top: 2px;
}

/* ─── Tooltip de cálculo ───────────────────────────── */
.tooltip-wrapper {
    position: relative;
}

/* A janelinha aparece ao passar o mouse no wrapper */
.tooltip-wrapper:hover .tooltip-calculo {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.tooltip-calculo {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) translateY(6px);
    width: 320px;
    background: #1a2e3a;
    color: #e8f4f8;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    z-index: 100;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
    /* Seta apontando para o card abaixo */
    pointer-events: none;
}

/* Seta do tooltip */
.tooltip-calculo::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 7px solid transparent;
    border-top-color: #1a2e3a;
}

.tooltip-titulo {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7eb8cc;
    margin-bottom: 10px;
}

.tooltip-linha {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    padding: 3px 0;
    color: #cde8f0;
    gap: 8px;
}

.tooltip-linha span:first-child {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tooltip-linha span:last-child {
    font-weight: 600;
    white-space: nowrap;
    color: #ffffff;
}

/* Linhas de dedução ficam levemente recuadas e numa cor diferente */
.tooltip-deducao {
    color: #f9a8b0;
}
.tooltip-deducao span:last-child {
    color: #fca5a5;
}

/* Linha divisória */
.tooltip-divisor {
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    margin: 8px 0;
}

/* Linha de resultado final — destaque */
.tooltip-resultado {
    font-size: 0.85rem;
}
.tooltip-resultado span:last-child {
    color: #6ee7b7;
    font-size: 0.9rem;
}

</style>
