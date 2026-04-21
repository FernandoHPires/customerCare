<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localAction }} Empreendimento</h5>
                    <button type="button" class="btn-close" @click="closeModel(modalId)" aria-label="Close"></button>
                </div>

                <div v-if="localAction === 'Editar'" class="px-3 pt-2">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link text-dark" :class="{ active: activeTab === 'empreendimento' }" href="#" @click.prevent="activeTab = 'empreendimento'">
                                Empreendimento
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" :class="{ active: activeTab === 'viabilidades' }" href="#" @click.prevent="switchToViabilidades()">
                                Viabilidades
                                <span v-if="viabilidadesList.length > 0" class="badge bg-secondary ms-1">{{ viabilidadesList.length }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="modal-body">

                    <!-- ===== Tab: Empreendimento ===== -->
                    <div v-show="activeTab === 'empreendimento'">
                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Nome</label>
                                <input type="text" class="form-control" v-model="nome" />
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Cidade</label>
                                <input type="text" class="form-control" v-model="cidade" />
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">UF</label>
                                <select v-model="uf" class="form-select">
                                    <option v-for="(option, key) in estados" :key="key" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Tipo do Produto</label>
                                <select v-model="tipoProduto" class="form-select">
                                    <option v-for="(option, key) in produtos" :key="key" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Total de Unidades</label>
                                <input type="number" class="form-control" v-model="totalUnidades" />
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Unidades Permutadas</label>
                                <input type="number" class="form-control" v-model="unidadesPermutadas" />
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Participação nos Resultados</label>
                                <CurrencyInput v-model="participacaoResultados"/>
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Percentual do Resultado Líquido</label>
                                <CurrencyInput v-model="percentualResultadoLiquido"/>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">TIR a.a</label>
                                <CurrencyInput v-model="tirAa"/>
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">MTIR</label>
                                <CurrencyInput v-model="mtir"/>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Percentual Participação SPE</label>
                                <CurrencyInput v-model="percentualParticipacaoSpe"/>
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Valor Participação SPE</label>
                                <CurrencyInput v-model="valorParticipacaoSpe"/>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Fase Atual</label>
                                <select v-model="faseAtual" class="form-select">
                                    <option v-for="(option, key) in faseAtualOptions" :key="key" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Previsão de Lançamento</label>
                                <input type="date" class="form-control" v-model="previsaoLancamento" />
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Previsão Inicio das Obras</label>
                                <input type="date" class="form-control" v-model="previsaoInicioObras" />
                            </div>
                            <div class="form-group col-6">
                                <label class="table-header">Previsão de Entrega</label>
                                <input type="date" class="form-control" v-model="previsaoEntrega" />
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-group col-6">
                                <label class="table-header">Status do Empreendimento</label>
                                <select v-model="statusEmpreendimento" class="form-select">
                                    <option v-for="(option, key) in statusOptions" :key="key" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Tab: Viabilidades ===== -->
                    <div v-show="activeTab === 'viabilidades'">

                        <div class="d-flex justify-content-end mb-2" v-if="!showViabilidadeForm">
                            <button type="button" class="btn btn-success btn-sm" @click="novaViabilidade()">
                                <i class="bi-plus-lg me-1"></i>Nova Viabilidade
                            </button>
                        </div>

                        <!-- Grid -->
                        <div class="table-responsive mb-3" v-if="!showViabilidadeForm">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>VGV</th>
                                        <th>Resultado Líquido</th>
                                        <th>Exposição Caixa</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody v-if="viabilidadesList.length === 0">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Nenhuma viabilidade cadastrada</td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr v-for="(v, index) in viabilidadesList" :key="v.viabilidadeId">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ formatDecimal(v.vgv) }}</td>
                                        <td>{{ formatDecimal(v.resultadoLiquido) }}</td>
                                        <td>{{ formatDecimal(v.exposicaoCaixa) }}</td>
                                        <td>
                                            <span v-if="v.status === 'A'" class="badge bg-success">Ativa</span>
                                            <span v-else class="badge bg-secondary">Inativa</span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="editViabilidade(v)">
                                                <i class="bi-pencil"></i>
                                            </button>
                                            <button v-if="v.status !== 'A'" type="button" class="btn btn-sm btn-outline-success me-1" @click="ativarViabilidade(v)">
                                                <i class="bi-check-circle"></i> Ativar
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" @click="confirmDeleteViabilidade(v)">
                                                <i class="bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Formulário inline -->
                        <div v-if="showViabilidadeForm" class="viabilidade-form">

                            <!-- ─── Dados do Empreendimento ──────────────── -->
                            <p class="vf-secao-titulo">Dados do Empreendimento</p>
                            <div class="row g-2 mb-3">
                                <div class="col-md-2">
                                    <label class="table-header">Nº de unidades - Estoque</label>
                                    <input type="number" class="form-control form-control-sm" v-model.number="vForm.unidadesVenda" />
                                </div>
                                <div class="col-md-2">
                                    <label class="table-header">Área total m²</label>
                                    <CurrencyInput v-model="vForm.areaTotalM2" />
                                </div>
                                <div class="col-md-2">
                                    <label class="table-header">Valor m²</label>
                                    <CurrencyInput v-model="vForm.valorPrevisto" />
                                </div>
                                <div class="col-md-2">
                                    <label class="table-header">% Permuta Física/Financ.</label>
                                    <CurrencyInput v-model="vForm.percentualPermutaFisica" />
                                </div>
                                <div class="col-md-2">
                                    <label class="table-header">Exposição de Caixa VP</label>
                                    <CurrencyInput v-model="vForm.exposicaoCaixa" />
                                </div>
                                <div class="col-md-2">
                                    <label class="table-header">Status</label>
                                    <select v-model="vForm.status" class="form-select form-select-sm">
                                        <option value="A">Ativa</option>
                                        <option value="I">Inativa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- VGV calculado -->
                            <div class="vf-vgv-banner mb-3">
                                <span class="vf-vgv-label">Receita bruta da venda das unidades (VGV)</span>
                                <span class="vf-vgv-valor">{{ fmtReais(vgvCalculado) }}</span>
                                <span class="vf-vgv-formula">= Área total m² × Valor m²</span>
                            </div>

                            <!-- ─── Descritivo Financeiro ─────────────────── -->
                            <p class="vf-secao-titulo">Descritivo Financeiro</p>
                            <table class="table table-sm vf-tabela-dre mb-3">
                                <thead>
                                    <tr>
                                        <th>Descritivo Financeiro</th>
                                        <th class="vf-col-reais text-end">Valor (R$)</th>
                                        <th class="vf-col-pct text-center">(%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="vf-linha-vgv">
                                        <td><strong>(+) Receita bruta da venda das unidades (VGV)</strong></td>
                                        <td class="text-end"><span class="vf-badge vf-badge-vgv">{{ fmtReais(vgvCalculado) }}</span></td>
                                        <td class="text-center"><span class="vf-badge vf-badge-vgv">100,00%</span></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Comissões e premiações de venda</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.comissoesReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualComissao" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Tributos sobre a receita de venda</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.tributosReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualTributo" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Incorporação</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.incorporacaoReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualIncorporacao" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) MKT</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.marketingReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualMarketing" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Obras totais</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.obrasReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualCustoObra" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Gestão de Vendas</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.gestaoVendasReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualDespesaVenda" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Administração</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.administracaoReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualAdministracao" /></td>
                                    </tr>
                                    <tr>
                                        <td>(-) Terreno</td>
                                        <td class="text-end"><span class="vf-badge">{{ fmtReais(dre.terrenoReais) }}</span></td>
                                        <td><CurrencyInput v-model="vForm.percentualAquisicaoTerreno" /></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- ─── Cards de Resultado ───────────────────── -->
                            <p class="vf-secao-titulo">Resultados</p>
                            <div class="row g-2 mb-3">

                                <!-- Receita Líquida -->
                                <div class="col-md-4">
                                    <div class="vf-tooltip-wrapper">
                                        <div class="vf-resultado-card">
                                            <div class="vf-resultado-label">(=) Receita líquida da venda das unidades</div>
                                            <div class="vf-resultado-valor">{{ fmtReais(dre.receitaLiquida) }}</div>
                                            <div class="vf-resultado-pct">{{ fmtPct(dre.receitaLiquidaPct) }}</div>
                                        </div>
                                        <div class="vf-tooltip-calculo">
                                            <div class="vf-tooltip-titulo">Como é calculado</div>
                                            <div class="vf-tooltip-linha">
                                                <span>(+) VGV</span><span>{{ fmtReais(vgvCalculado) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Comissões ({{ vForm.percentualComissao }}%)</span><span>{{ fmtReais(dre.comissoesReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Tributos ({{ vForm.percentualTributo }}%)</span><span>{{ fmtReais(dre.tributosReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-divisor"></div>
                                            <div class="vf-tooltip-linha vf-tooltip-resultado">
                                                <span>(=) Receita Líquida</span><span>{{ fmtReais(dre.receitaLiquida) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resultado Operacional -->
                                <div class="col-md-4">
                                    <div class="vf-tooltip-wrapper">
                                        <div class="vf-resultado-card">
                                            <div class="vf-resultado-label">(=) Resultado operacional</div>
                                            <div class="vf-resultado-valor">{{ fmtReais(dre.resultadoOperacional) }}</div>
                                            <div class="vf-resultado-pct">{{ fmtPct(dre.resultadoOperacionalPct) }}</div>
                                        </div>
                                        <div class="vf-tooltip-calculo">
                                            <div class="vf-tooltip-titulo">Como é calculado</div>
                                            <div class="vf-tooltip-linha">
                                                <span>(+) Receita Líquida</span><span>{{ fmtReais(dre.receitaLiquida) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Incorporação ({{ vForm.percentualIncorporacao }}%)</span><span>{{ fmtReais(dre.incorporacaoReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) MKT ({{ vForm.percentualMarketing }}%)</span><span>{{ fmtReais(dre.marketingReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Obras totais ({{ vForm.percentualCustoObra }}%)</span><span>{{ fmtReais(dre.obrasReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Gestão de Vendas ({{ vForm.percentualDespesaVenda }}%)</span><span>{{ fmtReais(dre.gestaoVendasReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Administração ({{ vForm.percentualAdministracao }}%)</span><span>{{ fmtReais(dre.administracaoReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-divisor"></div>
                                            <div class="vf-tooltip-linha vf-tooltip-resultado">
                                                <span>(=) Resultado Operacional</span><span>{{ fmtReais(dre.resultadoOperacional) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resultado Líquido -->
                                <div class="col-md-4">
                                    <div class="vf-tooltip-wrapper">
                                        <div class="vf-resultado-card"
                                            :class="{
                                                'vf-resultado-card--positivo': dre.resultadoLiquido > 0,
                                                'vf-resultado-card--negativo': dre.resultadoLiquido < 0,
                                            }"
                                        >
                                            <div class="vf-resultado-label">(=) Resultado líquido</div>
                                            <div class="vf-resultado-valor">{{ fmtReais(dre.resultadoLiquido) }}</div>
                                            <div class="vf-resultado-pct">{{ fmtPct(dre.resultadoLiquidoPct) }}</div>
                                        </div>
                                        <div class="vf-tooltip-calculo">
                                            <div class="vf-tooltip-titulo">Como é calculado</div>
                                            <div class="vf-tooltip-linha">
                                                <span>(+) Resultado Operacional</span><span>{{ fmtReais(dre.resultadoOperacional) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Terreno ({{ vForm.percentualAquisicaoTerreno }}%)</span><span>{{ fmtReais(dre.terrenoReais) }}</span>
                                            </div>
                                            <div class="vf-tooltip-divisor"></div>
                                            <div class="vf-tooltip-linha vf-tooltip-resultado">
                                                <span>(=) Resultado Líquido</span><span>{{ fmtReais(dre.resultadoLiquido) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Participação Taboada -->
                                <div class="col-md-6">
                                    <div class="vf-tooltip-wrapper">
                                        <div class="vf-resultado-card vf-resultado-card--neutro">
                                            <div class="vf-resultado-label">Participação Taboada (7% do Resultado Líquido)</div>
                                            <div class="vf-resultado-valor">{{ fmtReais(participacaoTaboada) }}</div>
                                        </div>
                                        <div class="vf-tooltip-calculo">
                                            <div class="vf-tooltip-titulo">Como é calculado</div>
                                            <div class="vf-tooltip-linha">
                                                <span>Resultado Líquido</span><span>{{ fmtReais(dre.resultadoLiquido) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>× Participação Taboada</span><span>7%</span>
                                            </div>
                                            <div class="vf-tooltip-divisor"></div>
                                            <div class="vf-tooltip-linha vf-tooltip-resultado">
                                                <span>(=) Participação Taboada</span><span>{{ fmtReais(participacaoTaboada) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- % após Part. Taboada -->
                                <div class="col-md-6">
                                    <div class="vf-tooltip-wrapper">
                                        <div class="vf-resultado-card vf-resultado-card--neutro">
                                            <div class="vf-resultado-label">Resultado Líquido após Part. Taboada (%)</div>
                                            <div class="vf-resultado-valor">{{ fmtPct(resultadoLiquidoAposParticipacaoPct) }}</div>
                                        </div>
                                        <div class="vf-tooltip-calculo">
                                            <div class="vf-tooltip-titulo">Como é calculado</div>
                                            <div class="vf-tooltip-linha">
                                                <span>Resultado Líquido</span><span>{{ fmtReais(dre.resultadoLiquido) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha vf-tooltip-deducao">
                                                <span>(-) Participação Taboada</span><span>{{ fmtReais(participacaoTaboada) }}</span>
                                            </div>
                                            <div class="vf-tooltip-linha">
                                                <span>÷ VGV</span><span>{{ fmtReais(vgvCalculado) }}</span>
                                            </div>
                                            <div class="vf-tooltip-divisor"></div>
                                            <div class="vf-tooltip-linha vf-tooltip-resultado">
                                                <span>(=) % do VGV</span><span>{{ fmtPct(resultadoLiquidoAposParticipacaoPct) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-2">
                                <button type="button" class="btn btn-outline-dark btn-sm" @click="cancelViabilidadeForm()">
                                    <i class="bi-x-lg me-1"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-success btn-sm" @click="saveViabilidade()">
                                    <i class="bi-save me-1"></i>Salvar Viabilidade
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModel(modalId)">
                        <i class="bi-x-lg me-1"></i>Fechar
                    </button>
                    <button v-if="activeTab === 'empreendimento'" type="button" class="btn btn-success" @click="saveChanges">
                        <i class="bi-save me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationDialog
        :event="viabilidadeDialogEvent"
        :message="viabilidadeDialogMessage"
        :type="viabilidadeDialogType"
        :parentModalId="viabilidadeDialogParentId"
        :key="viabilidadeDialogParentId"
        @return="viabilidadeDialogOnReturn"
    />

</template>

<script>

import { util } from '../mixins/util'
import CurrencyInput from '../components/CurrencyInput.vue'
import ConfirmationDialog from '../components/ConfirmationDialog.vue'

export default {
    mixins: [util],
    props: ['action','refreshCount','empreendimentoData'],
    emits: [ "return" ],
    components: {
        CurrencyInput,
        ConfirmationDialog
    },
    computed: {

        // ─── VGV calculado a partir da área total e do valor por m² ──────
        vgvCalculado() {
            const areaTotalM2 = parseFloat(this.vForm.areaTotalM2)  || 0;
            const valorPorM2  = parseFloat(this.vForm.valorPrevisto) || 0;
            return areaTotalM2 * valorPorM2;
        },

        // ─── DRE — Demonstrativo de Resultado do Empreendimento ───────────
        dre() {
            const vgv = this.vgvCalculado;

            const comissoesReais      = this.calcSobreVgv(vgv, this.vForm.percentualComissao);
            const tributosReais       = this.calcSobreVgv(vgv, this.vForm.percentualTributo);
            const receitaLiquida      = vgv - comissoesReais - tributosReais;

            const incorporacaoReais   = this.calcSobreVgv(vgv, this.vForm.percentualIncorporacao);
            const marketingReais      = this.calcSobreVgv(vgv, this.vForm.percentualMarketing);
            const obrasReais          = this.calcSobreVgv(vgv, this.vForm.percentualCustoObra);
            const gestaoVendasReais   = this.calcSobreVgv(vgv, this.vForm.percentualDespesaVenda);
            const administracaoReais  = this.calcSobreVgv(vgv, this.vForm.percentualAdministracao);

            const resultadoOperacional = receitaLiquida
                - incorporacaoReais - marketingReais - obrasReais
                - gestaoVendasReais - administracaoReais;

            const terrenoReais     = this.calcSobreVgv(vgv, this.vForm.percentualAquisicaoTerreno);
            const resultadoLiquido = resultadoOperacional - terrenoReais;

            const receitaLiquidaPct       = vgv > 0 ? (receitaLiquida       / vgv) * 100 : 0;
            const resultadoOperacionalPct = vgv > 0 ? (resultadoOperacional  / vgv) * 100 : 0;
            const resultadoLiquidoPct     = vgv > 0 ? (resultadoLiquido      / vgv) * 100 : 0;

            return {
                comissoesReais, tributosReais,
                receitaLiquida, receitaLiquidaPct,
                incorporacaoReais, marketingReais, obrasReais, gestaoVendasReais, administracaoReais,
                resultadoOperacional, resultadoOperacionalPct,
                terrenoReais, resultadoLiquido, resultadoLiquidoPct,
            };
        },

        // ─── Participação Taboada (7% do Resultado Líquido) ──────────────
        participacaoTaboada() {
            return this.dre.resultadoLiquido * 0.07;
        },

        // ─── Resultado líquido após descontar a participação Taboada ─────
        resultadoLiquidoAposParticipacaoPct() {
            const vgv = this.vgvCalculado;
            if (!vgv) return 0;
            return ((this.dre.resultadoLiquido - this.participacaoTaboada) / vgv) * 100;
        },

    },

    watch: {
        refreshCount() {
            this.cleanFields();
            this.activeTab = 'empreendimento';
            this.localAction = this.action;
            this.localEmpreendimentoId = null;
            if (this.action === 'Editar') {
                this.loadData();
                this.loadViabilidades();
            }
        }
    },
    data () {
        return {
            modalId: 'empreendimentos',
            activeTab: 'empreendimento',
            localAction: 'Adicionar',
            localEmpreendimentoId: null,

            produtos: [
                { value: 'Comercial', text: 'Comercial' },
                { value: 'Condomínio de Lotes', text: 'Condomínio de Lotes' },
                { value: 'Condomínio Fechado', text: 'Condomínio Fechado' },
                { value: 'Hotelaria', text: 'Hotelaria' },
                { value: 'Loteamento Aberto', text: 'Loteamento Aberto' },
                { value: 'Loteamento Fechado', text: 'Loteamento Fechado' },
                { value: 'Marina', text: 'Marina' }
            ],
            estados: [
                { value: 'AC', text: 'AC' }, { value: 'AL', text: 'AL' },
                { value: 'AP', text: 'AP' }, { value: 'AM', text: 'AM' },
                { value: 'BA', text: 'BA' }, { value: 'CE', text: 'CE' },
                { value: 'DF', text: 'DF' }, { value: 'ES', text: 'ES' },
                { value: 'GO', text: 'GO' }, { value: 'MA', text: 'MA' },
                { value: 'MT', text: 'MT' }, { value: 'MS', text: 'MS' },
                { value: 'MG', text: 'MG' }, { value: 'PA', text: 'PA' },
                { value: 'PB', text: 'PB' }, { value: 'PR', text: 'PR' },
                { value: 'PE', text: 'PE' }, { value: 'PI', text: 'PI' },
                { value: 'RJ', text: 'RJ' }, { value: 'RN', text: 'RN' },
                { value: 'RS', text: 'RS' }, { value: 'RO', text: 'RO' },
                { value: 'RR', text: 'RR' }, { value: 'SC', text: 'SC' },
                { value: 'SP', text: 'SP' }, { value: 'SE', text: 'SE' },
                { value: 'TO', text: 'TO' }
            ],
            faseAtualOptions: [
                { value: 'Aprovações legais', text: 'Aprovações legais' },
                { value: 'Concluído', text: 'Concluído' },
                { value: 'Estudo de viabilidade', text: 'Estudo de viabilidade' },
                { value: 'Fase de Obras', text: 'Fase de Obras' },
                { value: 'Ideação', text: 'Ideação' },
                { value: 'Plano Futuro', text: 'Plano Futuro' }
            ],
            statusOptions: [
                { value: 'Em andamento', text: 'Em andamento' },
                { value: 'Entregue', text: 'Entregue' }
            ],

            // Empreendimento fields
            nome: "",
            cidade: "",
            uf: "",
            tipoProduto: "",
            totalUnidades: 0,
            unidadesPermutadas: 0,
            participacaoResultados: 0,
            percentualResultadoLiquido: 0,
            tirAa: 0,
            mtir: 0,
            percentualParticipacaoSpe: 0,
            valorParticipacaoSpe: 0,
            faseAtual: "",
            previsaoLancamento: "",
            previsaoInicioObras: "",
            previsaoEntrega: "",
            statusEmpreendimento: "",

            // Viabilidades
            viabilidadesList: [],
            showViabilidadeForm: false,
            viabilidadeAction: 'Adicionar',
            vForm: {
                id: 0,
                status: 'A',
                unidadesVenda: 0,
                valorAquisicaoTerreno: 0,
                percentualAquisicaoTerreno: 0,
                percentualPermutaFisica: 0,
                areaTotalM2: 0,
                valorPrevisto: 0,
                vgv: 0,
                exposicaoCaixa: 0,
                resultadoLiquido: 0,
                valorCustoObra: 0,
                percentualCustoObra: 0,
                valorComissao: 0,
                percentualComissao: 0,
                valorTributo: 0,
                percentualTributo: 0,
                valorIncorporacao: 0,
                percentualIncorporacao: 0,
                valorMarketing: 0,
                percentualMarketing: 0,
                valorDespesaObra: 0,
                percentualDespesaObra: 0,
                valorDespesaVenda: 0,
                percentualDespesaVenda: 0,
                valorAdministracao: 0,
                percentualAdministracao: 0,
            },

            // Dialog para excluir viabilidade
            viabilidadeDialogParentId: 'viabilidadeDelete',
            viabilidadeDialogEvent: '',
            viabilidadeDialogMessage: '',
            viabilidadeDialogType: 'danger',
            viabilidadeToDelete: null,
        }
    },
    methods: {

        emptyVForm() {
            return {
                id: 0,
                status: 'A',
                unidadesVenda: 0,
                valorAquisicaoTerreno: 0,
                percentualAquisicaoTerreno: 0,
                percentualPermutaFisica: 0,
                areaTotalM2: 0,
                valorPrevisto: 0,
                vgv: 0,
                exposicaoCaixa: 0,
                resultadoLiquido: 0,
                valorCustoObra: 0,
                percentualCustoObra: 0,
                valorComissao: 0,
                percentualComissao: 0,
                valorTributo: 0,
                percentualTributo: 0,
                valorIncorporacao: 0,
                percentualIncorporacao: 0,
                valorMarketing: 0,
                percentualMarketing: 0,
                valorDespesaObra: 0,
                percentualDespesaObra: 0,
                valorDespesaVenda: 0,
                percentualDespesaVenda: 0,
                valorAdministracao: 0,
                percentualAdministracao: 0,
            }
        },

        switchToViabilidades() {
            this.activeTab = 'viabilidades';
            if (this.viabilidadesList.length === 0) {
                this.loadViabilidades();
            }
        },

        effectiveEmpreendimentoId() {
            return this.localEmpreendimentoId || (this.empreendimentoData && this.empreendimentoData.empreendimentoId) || null;
        },

        loadViabilidades() {
            if (!this.effectiveEmpreendimentoId()) return;

            this.axios({
                method: 'get',
                url: '/web/viabilidades/' + this.effectiveEmpreendimentoId()
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.viabilidadesList = response.data.data;
                }
            })
            .catch(() => {});
        },

        novaViabilidade() {
            this.viabilidadeAction = 'Adicionar';
            this.vForm = this.emptyVForm();
            this.showViabilidadeForm = true;
        },

        editViabilidade(v) {
            this.viabilidadeAction = 'Editar';
            this.vForm = { ...v, id: v.viabilidadeId };
            this.showViabilidadeForm = true;
        },

        cancelViabilidadeForm() {
            this.showViabilidadeForm = false;
            this.vForm = this.emptyVForm();
        },

        // ─── Fórmulas base ───────────────────────────────────────────────
        calcSobreVgv(vgv, percentual) {
            return vgv * ((parseFloat(percentual) || 0) / 100);
        },

        // ─── Formatação ──────────────────────────────────────────────────
        fmtReais(valor) {
            const n = parseFloat(valor) || 0;
            return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        fmtPct(valor) {
            const n = parseFloat(valor) || 0;
            return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
        },

        saveViabilidade() {
            const data = {
                action: this.viabilidadeAction,
                id: this.vForm.id,
                empreendimentoId: this.effectiveEmpreendimentoId(),
                ...this.vForm,
                // Valores calculados — salvos para uso no portfólio e relatórios
                vgv:                   this.vgvCalculado,
                resultadoLiquido:      this.dre.resultadoLiquido,
                valorAquisicaoTerreno: this.dre.terrenoReais,
                valorCustoObra:        this.dre.obrasReais,
                valorComissao:         this.dre.comissoesReais,
                valorTributo:          this.dre.tributosReais,
                valorIncorporacao:     this.dre.incorporacaoReais,
                valorMarketing:        this.dre.marketingReais,
                valorDespesaVenda:     this.dre.gestaoVendasReais,
                valorAdministracao:    this.dre.administracaoReais,
                valorDespesaObra:      0,
                percentualDespesaObra: 0,
            };

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/viabilidade',
                data: data
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.alertMessage = "Viabilidade salva com sucesso!";
                    this.showAlert('success');
                    this.showViabilidadeForm = false;
                    this.vForm = this.emptyVForm();
                    this.loadViabilidades();
                } else {
                    this.alertMessage = "Erro ao salvar viabilidade!";
                    this.showAlert('error');
                }
            })
            .catch(() => {
                this.alertMessage = "Erro ao salvar viabilidade!";
                this.showAlert('error');
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        ativarViabilidade(v) {
            this.showPreLoader();

            this.axios({
                method: 'patch',
                url: '/web/viabilidade/' + v.viabilidadeId + '/ativar'
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.alertMessage = "Viabilidade ativada!";
                    this.showAlert('success');
                    this.loadViabilidades();
                } else {
                    this.alertMessage = "Erro ao ativar viabilidade!";
                    this.showAlert('error');
                }
            })
            .catch(() => {
                this.alertMessage = "Erro ao ativar viabilidade!";
                this.showAlert('error');
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        confirmDeleteViabilidade(v) {
            this.viabilidadeToDelete = v;
            this.viabilidadeDialogEvent = 'deleteViabilidade';
            this.viabilidadeDialogMessage = 'Tem certeza que deseja excluir esta viabilidade?';
            this.viabilidadeDialogType = 'danger';
            this.showModal('confirmationDialog' + this.viabilidadeDialogParentId);
        },

        viabilidadeDialogOnReturn(event, result) {
            if (event === 'deleteViabilidade' && result === 'confirmed') {
                this.showPreLoader();

                this.axios({
                    method: 'delete',
                    url: '/web/viabilidade/' + this.viabilidadeToDelete.viabilidadeId
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = "Viabilidade excluída!";
                        this.showAlert('success');
                        this.loadViabilidades();
                    } else {
                        this.alertMessage = "Erro ao excluir viabilidade!";
                        this.showAlert('error');
                    }
                })
                .catch(() => {
                    this.alertMessage = "Erro ao excluir viabilidade!";
                    this.showAlert('error');
                })
                .finally(() => {
                    this.hidePreLoader();
                });
            }
        },

        saveChanges() {
            const isAdding = this.action === 'Adicionar';
            const empreendimentoId = isAdding ? 0 : this.empreendimentoData.empreendimentoId;
            const clienteId       = isAdding ? 0 : this.empreendimentoData.clienteId;

            const data = {
                id: empreendimentoId,
                clienteId: clienteId,
                nome: this.nome,
                cidade: this.cidade,
                uf: this.uf,
                tipoProduto: this.tipoProduto,
                totalUnidades: this.totalUnidades,
                unidadesPermutadas: this.unidadesPermutadas,
                participacaoResultados: this.participacaoResultados,
                percentualResultadoLiquido: this.percentualResultadoLiquido,
                tirAa: this.tirAa,
                mtir: this.mtir,
                percentualParticipacaoSpe: this.percentualParticipacaoSpe,
                valorParticipacaoSpe: this.valorParticipacaoSpe,
                faseAtual: this.faseAtual,
                previsaoLancamento: this.previsaoLancamento,
                previsaoInicioObras: this.previsaoInicioObras,
                previsaoEntrega: this.previsaoEntrega,
                statusEmpreendimento: this.statusEmpreendimento,
                action: this.action
            };

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: 'web/empreendimento',
                data: data
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.$emit('return');

                    if (isAdding) {
                        // Guardar o ID gerado e mudar para modo Editar + aba Viabilidades
                        this.localEmpreendimentoId = response.data.data.empreendimentoId;
                        this.localAction = 'Editar';
                        this.activeTab = 'viabilidades';
                        this.alertMessage = "Empreendimento criado! Agora preencha a viabilidade.";
                        this.showAlert('success');
                        this.novaViabilidade();
                    } else {
                        this.alertMessage = "Empreendimento salvo com sucesso!";
                        this.showAlert('success');
                        this.closeModel(this.modalId);
                    }
                } else {
                    this.alertMessage = "Erro ao salvar empreendimento!";
                    this.showAlert('error');
                }
            })
            .catch(() => {
                this.alertMessage = "Erro ao salvar empreendimento!";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        closeModel(modalId) {
            this.hideModal(modalId);
        },

        cleanFields() {
            this.nome = "";
            this.cidade = "";
            this.uf = "";
            this.tipoProduto = "";
            this.totalUnidades = 0;
            this.unidadesPermutadas = 0;
            this.participacaoResultados = 0;
            this.percentualResultadoLiquido = 0;
            this.tirAa = 0;
            this.mtir = 0;
            this.percentualParticipacaoSpe = 0;
            this.valorParticipacaoSpe = 0;
            this.faseAtual = "";
            this.previsaoLancamento = "";
            this.previsaoInicioObras = "";
            this.previsaoEntrega = "";
            this.statusEmpreendimento = "";
            this.viabilidadesList = [];
            this.showViabilidadeForm = false;
            this.vForm = this.emptyVForm();
            this.localAction = this.action;
            this.localEmpreendimentoId = null;
        },

        loadData() {
            this.nome = this.empreendimentoData.nome;
            this.cidade = this.empreendimentoData.cidade;
            this.uf = this.empreendimentoData.uf;
            this.tipoProduto = this.empreendimentoData.tipoProduto;
            this.totalUnidades = this.empreendimentoData.totalUnidades;
            this.unidadesPermutadas = this.empreendimentoData.unidadesPermutadas;
            this.participacaoResultados = this.empreendimentoData.participacaoResultados;
            this.percentualResultadoLiquido = this.empreendimentoData.percentualResultadoLiquido;
            this.tirAa = this.empreendimentoData.tirAa;
            this.mtir = this.empreendimentoData.mtir;
            this.percentualParticipacaoSpe = this.empreendimentoData.percentualParticipacaoSpe;
            this.valorParticipacaoSpe = this.empreendimentoData.valorParticipacaoSpe;
            this.faseAtual = this.empreendimentoData.faseAtual;
            this.previsaoLancamento = this.empreendimentoData.previsaoLancamento;
            this.previsaoInicioObras = this.empreendimentoData.previsaoInicioObras;
            this.previsaoEntrega = this.empreendimentoData.previsaoEntrega;
            this.statusEmpreendimento = this.empreendimentoData.statusEmpreendimento;
        }
    }
}
</script>

<style scoped>
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
}
.table-header {
    padding-right: 1rem;
    padding-bottom: 0;
    border: none;
    font-size: 0.875em;
    font-weight: bold;
}
.table-body {
    padding-right: 1rem;
    padding-top: 0;
    border: none;
    font-size: 0.875em;
    font-weight: normal;
}
.viabilidade-form {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

/* ─── Títulos de seção do formulário de viabilidade ── */
.vf-secao-titulo {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #124C60;
    border-bottom: 2px solid #124C60;
    padding-bottom: 4px;
    margin-bottom: 10px;
}

/* ─── Banner VGV calculado ───────────────────────── */
.vf-vgv-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #e8f4f8;
    border: 1px solid #b6d4e3;
    border-radius: 6px;
    padding: 8px 14px;
}
.vf-vgv-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #124C60;
    flex: 1;
}
.vf-vgv-valor {
    font-size: 1rem;
    font-weight: 700;
    color: #124C60;
}
.vf-vgv-formula {
    font-size: 0.72rem;
    color: #7aa8bc;
}

/* ─── Tabela DRE ─────────────────────────────────── */
.vf-tabela-dre thead th {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #124C60;
    background: #e8f4f8;
    border-bottom: 2px solid #b6d4e3;
    padding: 7px 10px;
}
.vf-tabela-dre td {
    vertical-align: middle;
    padding: 4px 10px;
    font-size: 0.82rem;
    color: #333;
    border-bottom: 1px solid #f0f0f0;
}
.vf-tabela-dre .vf-col-reais { width: 175px; }
.vf-tabela-dre .vf-col-pct   { width: 110px; }
.vf-linha-vgv td { background: #e8f4f8; }

/* ─── Badges de valor calculado ─────────────────── */
.vf-badge {
    display: inline-block;
    font-weight: 500;
    font-size: 0.78rem;
    padding: 2px 8px;
    background: #e0eff5;
    color: #124C60;
    border-radius: 4px;
    min-width: 140px;
    text-align: right;
}
.vf-badge-vgv {
    font-weight: 700;
    background: #124C60;
    color: #fff;
}

/* ─── Cards de resultado ─────────────────────────── */
.vf-resultado-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 12px;
    text-align: center;
}
.vf-resultado-card--positivo { background: #d1fae5; border-color: #6ee7b7; }
.vf-resultado-card--negativo { background: #fee2e2; border-color: #fca5a5; }
.vf-resultado-card--neutro   { background: #eff6ff; border-color: #bfdbfe; }
.vf-resultado-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #555;
    margin-bottom: 4px;
}
.vf-resultado-valor {
    font-size: 0.9rem;
    font-weight: 700;
    color: #124C60;
}
.vf-resultado-pct {
    font-size: 0.75rem;
    color: #666;
    margin-top: 1px;
}

/* ─── Tooltips de cálculo ────────────────────────── */
.vf-tooltip-wrapper {
    position: relative;
}
.vf-tooltip-wrapper:hover .vf-tooltip-calculo {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}
.vf-tooltip-calculo {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%) translateY(6px);
    width: 300px;
    background: #1a2e3a;
    color: #e8f4f8;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
    pointer-events: none;
}
.vf-tooltip-calculo::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #1a2e3a;
}
.vf-tooltip-titulo {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7eb8cc;
    margin-bottom: 8px;
}
.vf-tooltip-linha {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    padding: 2px 0;
    color: #cde8f0;
    gap: 8px;
}
.vf-tooltip-linha span:first-child { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vf-tooltip-linha span:last-child  { font-weight: 600; white-space: nowrap; color: #ffffff; }
.vf-tooltip-deducao { color: #f9a8b0; }
.vf-tooltip-deducao span:last-child { color: #fca5a5; }
.vf-tooltip-divisor { border-top: 1px solid rgba(255,255,255,0.15); margin: 6px 0; }
.vf-tooltip-resultado span:last-child { color: #6ee7b7; font-size: 0.8rem; }
</style>
