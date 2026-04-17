<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <h5>Portfólio</h5>

                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="viabilidades('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Nova Viabilidade
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
<!-- 
Empreendimento	nome
Nº unidades venda	unidades_venda
Aquisição terreno R$	valor_aquisicao_terreno
Aquisição terreno %	percentual_aquisicao_terreno
% permuta	percentual_permuta_fisica
Área total	area_total_m2
Valor m²	valor_previsto
VGV	vgv
Exposição caixa	exposicao_caixa
Resultado líquido	resultado_liquido
Obra %	percentual_custo_obra
Obra R$	valor_custo_obra
Comissão %	percentual_comissao
Comissão R$	valor_comissao
Tributos %	percentual_tributo
Tributos R$	valor_tributo
Incorporação %	percentual_incorporacao
Incorporação R$	valor_incorporacao
Marketing %	percentual_marketing
Marketing R$	valor_marketing
Gestão obra %	percentual_despesa_obra
Gestão obra R$	valor_despesa_obra
Gestão vendas %	percentual_despesa_venda
Gestão vendas R$	valor_despesa_venda
Administração %	percentual_administracao
Administração R$	valor_administracao -->
        <div class="table-responsive">
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
                        <td>{{ row.totalUnidades }}</td>
                        <td>{{ row.unidadesPermutadas }}</td>
                        <td>{{ row.unidadesVenda }}</td>
                        <td>{{ row.valorAquisicaoTerreno }}</td>
                        <td>{{ row.percentualAquisicaoTerreno }}</td>
                        <td>{{ row.percentualPermutaFisica }}</td>
                        <td>{{ row.areaTotalM2 }}</td>
                        <td>{{ row.valorPrevisto }}</td>
                        <td>{{ row.vgv }}</td>
                        <td>{{ row.exposicaoCaixa }}</td>
                        <td>{{ row.resultadoLiquido }}</td>
                        <td>{{ row.participacaoResultados }}</td>
                        <td>{{ row.percentualResultadoLiquido }}</td>
                        <td>{{ row.tirAa }}</td>
                        <td>{{ row.mtir }}</td>
                        <td>{{ row.percentualCustoObra }}</td>
                        <td>{{ row.valorCustoObra }}</td>
                        <td>{{ row.percentualComissao }}</td>
                        <td>{{ row.valorComissao }}</td>
                        <td>{{ row.percentualTributo }}</td>
                        <td>{{ row.valorTributo }}</td>
                        <td>{{ row.percentualIncorporacao }}</td>
                        <td>{{ row.valorIncorporacao }}</td>
                        <td>{{ row.percentualMarketing }}</td>
                        <td>{{ row.valorMarketing }}</td>
                        <td>{{ row.percentualDespesaObra }}</td>
                        <td>{{ row.valorDespesaObra }}</td>
                        <td>{{ row.percentualDespesaVenda }}</td>
                        <td>{{ row.valorDespesaVenda }}</td>
                        <td>{{ row.percentualAdministracao }}</td>
                        <td>{{ row.valorAdministracao }}</td>
                        <td>{{ row.percentualParticipacaoSpe }}</td>
                        <td>{{ row.valorParticipacaoSpe }}</td>
                        <td>{{ row.faseAtual }}</td>
                        <td>{{ row.previsaoLancamento }}</td>
                        <td>{{ row.previsaoInicioObras }}</td>
                        <td>{{ row.previsaoEntrega }}</td>
                        <td>{{ row.statusEmpreendimento }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="viabilidades('Editar', row)"
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
                //
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