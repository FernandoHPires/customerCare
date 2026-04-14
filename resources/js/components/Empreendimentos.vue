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
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="mb-0">{{ viabilidadeAction }} Viabilidade</h6>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-3">
                                    <label class="table-header">Unidades Venda</label>
                                    <input type="number" class="form-control" v-model="vForm.unidadesVenda" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">Área Total m²</label>
                                    <CurrencyInput v-model="vForm.areaTotalM2" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">Valor Previsto m²</label>
                                    <CurrencyInput v-model="vForm.valorPrevisto" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">Aquisição Terreno R$</label>
                                    <CurrencyInput v-model="vForm.valorAquisicaoTerreno" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-3">
                                    <label class="table-header">Aquisição Terreno %</label>
                                    <CurrencyInput v-model="vForm.percentualAquisicaoTerreno" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">% Permuta Física/Financ.</label>
                                    <CurrencyInput v-model="vForm.percentualPermutaFisica" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">VGV Estimado</label>
                                    <CurrencyInput v-model="vForm.vgv" />
                                </div>
                                <div class="form-group col-3">
                                    <label class="table-header">Exposição Caixa VP</label>
                                    <CurrencyInput v-model="vForm.exposicaoCaixa" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-4">
                                    <label class="table-header">Resultado Líquido (VPL)</label>
                                    <CurrencyInput v-model="vForm.resultadoLiquido" />
                                </div>
                                <div class="form-group col-4">
                                    <label class="table-header">Status</label>
                                    <select v-model="vForm.status" class="form-select">
                                        <option value="A">Ativa</option>
                                        <option value="I">Inativa</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-2"/>
                            <p class="table-header mb-2">Composição de Custos</p>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Obra R$</label>
                                    <CurrencyInput v-model="vForm.valorCustoObra" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Obra %</label>
                                    <CurrencyInput v-model="vForm.percentualCustoObra" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Comissão R$</label>
                                    <CurrencyInput v-model="vForm.valorComissao" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Comissão %</label>
                                    <CurrencyInput v-model="vForm.percentualComissao" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Tributos R$</label>
                                    <CurrencyInput v-model="vForm.valorTributo" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Tributos %</label>
                                    <CurrencyInput v-model="vForm.percentualTributo" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Incorporação R$</label>
                                    <CurrencyInput v-model="vForm.valorIncorporacao" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Incorporação %</label>
                                    <CurrencyInput v-model="vForm.percentualIncorporacao" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Marketing R$</label>
                                    <CurrencyInput v-model="vForm.valorMarketing" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Marketing %</label>
                                    <CurrencyInput v-model="vForm.percentualMarketing" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Gestão Obra R$</label>
                                    <CurrencyInput v-model="vForm.valorDespesaObra" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Gestão Obra %</label>
                                    <CurrencyInput v-model="vForm.percentualDespesaObra" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Gestão Vendas R$</label>
                                    <CurrencyInput v-model="vForm.valorDespesaVenda" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Gestão Vendas %</label>
                                    <CurrencyInput v-model="vForm.percentualDespesaVenda" />
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="form-group col-6">
                                    <label class="table-header">Administração R$</label>
                                    <CurrencyInput v-model="vForm.valorAdministracao" />
                                </div>
                                <div class="form-group col-6">
                                    <label class="table-header">Administração %</label>
                                    <CurrencyInput v-model="vForm.percentualAdministracao" />
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
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

        saveViabilidade() {
            const data = {
                action: this.viabilidadeAction,
                id: this.vForm.id,
                empreendimentoId: this.effectiveEmpreendimentoId(),
                ...this.vForm
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
</style>
