<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <h5>Portfólio</h5>

                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="empreendimentos('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Novo Empreendimento
                    </button>
                </div>
                <div class="text-end">
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="bi-search"></i
                        ></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search"
                            v-model="search"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive portfolio-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="col-wide">Empreendimento</th>
                        <th>Cidade</th>
                        <th class="col-small">UF</th>
                        <th>Tipo<br>Produto</th>

                        <th>Nº Total<br>Unidades</th>
                        <th>Nº Unid.<br>Permutadas</th>
                        <th>Nº Unid.<br>Venda</th>

                        <th>Aquisição<br>Terreno R$</th>
                        <th>Aquisição<br>Terreno %</th>
                        <th>% Permuta<br>Física/Financ.</th>

                        <th>Área Total<br>m²</th>
                        <th>Valor Prev.<br>m²</th>
                        <th>VGV<br>Estimado</th>

                        <th>Exposição<br>Caixa VP</th>
                        <th>Resultado<br>Líquido (VPL)</th>

                        <th class="col-wide">Part. Taboada<br>nos Resultados</th>
                        <th class="col-wide">Res. Líquido após<br>Part. Taboada %</th>

                        <th class="col-small">TIR a.a.</th>
                        <th class="col-small">MTIR</th>

                        <th>Obra %</th>
                        <th>Obra R$</th>

                        <th>Comissões %</th>
                        <th>Comissões R$</th>

                        <th>Tributos %</th>
                        <th>Tributos R$</th>

                        <th>Incorporação %</th>
                        <th>Incorporação R$</th>

                        <th>Marketing %</th>
                        <th>Marketing R$</th>

                        <th>Gestão Obra %</th>
                        <th>Gestão Obra R$</th>

                        <th>Gestão Vendas %</th>
                        <th>Gestão Vendas R$</th>

                        <th>Administração %</th>
                        <th>Administração R$</th>

                        <th class="col-wide">Part. SPE<br>Taboada %</th>
                        <th class="col-wide">Part. SPE<br>Taboada R$</th>

                        <th>Fase Atual</th>
                        <th>Prev.<br>Lançamento</th>
                        <th>Prev. Início<br>Obras</th>
                        <th>Prev.<br>Entrega</th>

                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="44">Nenhum empreendimento encontrado</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.nome }}</td>
                        <td>{{ row.cidade }}</td>
                        <td>{{ row.uf }}</td>
                        <td>{{ row.tipoProduto }}</td>
                        <td>{{ formatNumber(row.totalUnidades) }}</td>
                        <td>{{ formatNumber(row.unidadesPermutadas) }}</td>
                        <td>{{ formatNumber(row.unidadesVenda) }}</td>
                        <td>{{ formatDecimal(row.valorAquisicaoTerreno) }}</td>
                        <td>{{ formatDecimal(row.percentualAquisicaoTerreno) }}%</td>
                        <td>{{ formatDecimal(row.percentualPermutaFisica) }}%</td>
                        <td>{{ formatDecimal(row.areaTotalM2) }}</td>
                        <td>{{ formatDecimal(row.valorPrevisto) }}</td>
                        <td>{{ formatDecimal(row.vgv) }}</td>
                        <td>{{ formatDecimal(row.exposicaoCaixa) }}</td>
                        <td>{{ formatDecimal(row.resultadoLiquido) }}</td>
                        <td>{{ formatDecimal(row.participacaoResultados) }}</td>
                        <td>{{ formatDecimal(row.percentualResultadoLiquido) }}%</td>
                        <td>{{ formatDecimal(row.tirAa) }}%</td>
                        <td>{{ formatDecimal(row.mtir) }}%</td>
                        <td>{{ formatDecimal(row.percentualCustoObra) }}%</td>
                        <td>{{ formatDecimal(row.valorCustoObra) }}</td>
                        <td>{{ formatDecimal(row.percentualComissao) }}%</td>
                        <td>{{ formatDecimal(row.valorComissao) }}</td>
                        <td>{{ formatDecimal(row.percentualTributo) }}%</td>
                        <td>{{ formatDecimal(row.valorTributo) }}</td>
                        <td>{{ formatDecimal(row.percentualIncorporacao) }}%</td>
                        <td>{{ formatDecimal(row.valorIncorporacao) }}</td>
                        <td>{{ formatDecimal(row.percentualMarketing) }}%</td>
                        <td>{{ formatDecimal(row.valorMarketing) }}</td>
                        <td>{{ formatDecimal(row.percentualDespesaObra) }}%</td>
                        <td>{{ formatDecimal(row.valorDespesaObra) }}</td>
                        <td>{{ formatDecimal(row.percentualDespesaVenda) }}%</td>
                        <td>{{ formatDecimal(row.valorDespesaVenda) }}</td>
                        <td>{{ formatDecimal(row.percentualAdministracao) }}%</td>
                        <td>{{ formatDecimal(row.valorAdministracao) }}</td>
                        <td>{{ formatDecimal(row.percentualParticipacaoSpe) }}%</td>
                        <td>{{ formatDecimal(row.valorParticipacaoSpe) }}</td>
                        <td>{{ row.faseAtual }}</td>
                        <td>{{ formatPhpDate(row.previsaoLancamento) }}</td>
                        <td>{{ formatPhpDate(row.previsaoInicioObras) }}</td>
                        <td>{{ formatPhpDate(row.previsaoEntrega) }}</td>
                        <td>{{ row.statusEmpreendimento }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="empreendimentos('Editar', row)"
                            >
                                <i class="bi-pencil me-1"></i>Edit
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger mx-2"
                                @click="deleteEmpreedimento(row)"
                            >
                                <i class="bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Empreendimentos
        :action="modalAction"
        :refreshCount="refreshCount"
        :empreendimentoData="empreendimentoData"
        @return="empreendimentosOnReturn"
    />    

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        :type="dialogType"
        :parentModalId="modalId"
        :key="modalId"
        @return="dialogOnReturn"
    />    

</template>

<script>

import { util } from "../mixins/util";
import Empreendimentos from "../components/Empreendimentos.vue";
import ConfirmationDialog from '../components/ConfirmationDialog';

export default {
    mixins: [util],
    components: { Empreendimentos, ConfirmationDialog },
    name: "visao-geral",
    emits: ['events'],
    data() {
        return {
            modalId: "empreendimentos",
            modalAction: "Adicionar",
            portifolios: [],
            types: [],
            search: "",
            refreshCount: 0,
            empreendimentoData: {},
            empreendimentoDel: {},
            dialogMessage: "",
            dialogType: "",
            event: "",

        };
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase();
            var data = this.portifolios;
            data = data.filter(function (row) {
                return Object.keys(row).some(function (key) {
                    return String(row[key]).toLowerCase().indexOf(search) > -1;
                });
            });
            return data;
        },
    },
    mounted() {
        this.getPortifolios();
    },
    methods: {
        getPortifolios() {

            this.showPreLoader()

            this.axios({
                method: "get",
                url: "/web/portfolio-view",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.portifolios = response.data.data;
                } else {
                    this.alertMessage = "Error in getting portfolio data!";
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                console.error("An error occurred:", error);
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        empreendimentos(action, row) {
            this.modalAction = action;
            this.refreshCount++;
            this.empreendimentoData = row;
            this.showModal(this.modalId);
        },
        empreendimentosOnReturn() {
            this.getPortifolios();
        },
        deleteEmpreedimento(row) {
            this.dialogMessage = "Tem certeza que deseja excluir este empreendimento?"
            this.empreendimentoDel = row
            this.event = 'destroy'
            this.dialogType = 'danger'
            this.showModal('confirmationDialog' + this.modalId)
        },
        dialogOnReturn(event, returnMessage) {

            if(event == 'destroy' && returnMessage === 'confirmed') {

                this.showPreLoader()

                this.axios({
                    method: "delete",
                    url: "/web/empreendimento/" + this.empreendimentoDel.empreendimentoId
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getPortifolios();
                        this.alertMessage = "Empreendimento excluído!";
                        this.showAlert(response.data.status);
                    } else {
                        this.alertMessage = "Empreendimento não pode ser excluído!";
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                })
                .finally(() => {
                    this.hidePreLoader()
                })
            }
        }


    }

};
</script>
<style scoped>
.portfolio-table {
    max-height: calc(100vh - 220px);
    overflow-x: auto;
    overflow-y: auto;
}

.portfolio-table table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    min-width: 100%;
    margin-bottom: 0;
}

.portfolio-table thead th {
    position: sticky;
    top: 0;
    z-index: 4;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
    white-space: normal;
    min-width: 110px;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 10px;
}

.portfolio-table tbody td {
    vertical-align: middle;
    padding: 10px;
    white-space: nowrap;
    background: #fff;
}

.col-wide {
    min-width: 180px !important;
}

.col-small {
    min-width: 70px !important;
}

.portfolio-table th:first-child,
.portfolio-table td:first-child {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #fff;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

.portfolio-table thead th:first-child {
    z-index: 5;
    background: #f8f9fa;
}
</style>

