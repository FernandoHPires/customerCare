<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ action }} Empreendimento</h5>
                    <button type="button" class="btn-close" @click="closeModel(modalId)" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    
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
                        <div class="form-group col-6">
                            
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModel(modalId)">
                        <i class="bi-x-lg me-1"></i>Fechar
                    </button>
                    <button type="button" class="btn btn-success" @click="saveChanges">
                        <i class="bi-save me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>

<script>

import { util } from '../mixins/util'
import CurrencyInput from '../components/CurrencyInput.vue'
import { ref } from 'vue';


export default {
    mixins: [util],
    props: ['action','refreshCount','empreendimentoData'],
    emits: [ "return" ],
    components: { 
        CurrencyInput
    },
    watch: {
        refreshCount() {
            this.cleanFields();
            if (this.action==='Editar') {
                this.loadData();
            }
        }
    },
    data () {
        return {
            modalId: 'empreendimentos',
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
                { value: 'AC', text: 'AC' },
                { value: 'AL', text: 'AL' },
                { value: 'AP', text: 'AP' },
                { value: 'AM', text: 'AM' },
                { value: 'BA', text: 'BA' },
                { value: 'CE', text: 'CE' },
                { value: 'DF', text: 'DF' },
                { value: 'ES', text: 'ES' },
                { value: 'GO', text: 'GO' },
                { value: 'MA', text: 'MA' },
                { value: 'MT', text: 'MT' },
                { value: 'MS', text: 'MS' },
                { value: 'MG', text: 'MG' },
                { value: 'PA', text: 'PA' },
                { value: 'PB', text: 'PB' },
                { value: 'PR', text: 'PR' },
                { value: 'PE', text: 'PE' },
                { value: 'PI', text: 'PI' },
                { value: 'RJ', text: 'RJ' },
                { value: 'RN', text: 'RN' },
                { value: 'RS', text: 'RS' },
                { value: 'RO', text: 'RO' },
                { value: 'RR', text: 'RR' },
                { value: 'SC', text: 'SC' },
                { value: 'SP', text: 'SP' },
                { value: 'SE', text: 'SE' },
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
            statusEmpreendimento: ""

        }
    },
    methods: {

        saveChanges() {

            let empreendimentoId  = 0;
            let clienteId = 0;

            if (this.action === 'Editar') {
                empreendimentoId = this.empreendimentoData.empreendimentoId;
                clienteId = this.empreendimentoData.clienteId;
            }

            let data = {                
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
            }

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: 'web/empreendimento',
                data: data
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    this.alertMessage = "Empreendimento salvo com sucesso!";
                    this.showAlert(response.data.status);
                    
                    this.$emit('return');
                    this.closeModel(this.modalId);

                } else {
                    this.alertMessage = "Erro ao salvar empreendimento!";
                    this.showAlert(response.data.status);
                }

                
            })
            .catch((error) => {
                this.alertMessage = "Erro ao salvar empreendimento!";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        closeModel(modalId) {
            this.hideModal(modalId)
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
</style>