<template>
    <div class="fluxo" ref="tela">

        <div class="fluxo-titulo">
            <i class="bi-bar-chart-fill"></i>
            <h4 class="mb-0">Fluxo de Caixa</h4>
        </div>

        <div class="fluxo-filtros">
            <div class="filtro-card filtro-consulta">
                <div class="filtro-grupo">
                    <div class="filtro-label"><i class="bi-calendar3 me-2"></i>Período</div>
                    <div class="filtro-periodo">
                        <input type="date" class="form-control" v-model="dataInicial" />
                        <span>até</span>
                        <input type="date" class="form-control" v-model="dataFinal" />
                    </div>
                </div>
                <div class="filtro-grupo filtro-grupo--filial" v-if="dados.isUniUser">
                    <div class="filtro-label"><i class="bi-shop me-2"></i>Filial</div>
                    <SearchableSelect
                        :modelValue="filial"
                        :options="filialOptions"
                        placeholder="Pesquisar filial..."
                        @update:modelValue="trocarFilial"
                    />
                </div>
                <div class="filtro-grupo filtro-empreendimento" v-if="contasReceber !== 'COR'">
                    <div class="filtro-label"><i class="bi-building me-2"></i>Empreendimento</div>
                    <SearchableSelect
                        :modelValue="idEmpreendimento"
                        :options="empreendimentoOptions"
                        placeholder="Pesquisar empreendimento..."
                        @update:modelValue="trocarEmpreendimento"
                    />
                </div>
                <div class="filtro-grupo">
                    <div class="filtro-label"><i class="bi-cash-coin me-2"></i>Recebíveis</div>
                    <button
                        type="button"
                        class="filtro-flag"
                        :class="{ 'filtro-flag--ativa': recebiveisFuturos }"
                        :aria-pressed="recebiveisFuturos"
                        title="Considera somente os títulos que vencem a partir de amanhã."
                        @click="recebiveisFuturos = !recebiveisFuturos"
                    >
                        <i :class="recebiveisFuturos ? 'bi-check-square-fill' : 'bi-square'"></i>
                        Vencimentos a partir de {{ dataAmanha }}
                    </button>
                </div>
                <div class="filtro-grupo filtro-acao">
                    <div class="filtro-label">&nbsp;</div>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm fluxo-atualizar"
                        title="Buscar dados atualizados na Mega"
                        @click="carregar()"
                    >
                        <i class="bi-arrow-clockwise me-1"></i>Atualizar
                    </button>
                </div>
            </div>
            <div class="filtro-card">
                <div class="filtro-label"><i class="bi-bank me-2"></i>Saldo Inicial</div>
                <input
                    type="text"
                    class="form-control"
                    inputmode="numeric"
                    :value="saldoInicialTexto"
                    @input="atualizarSaldoInicial"
                />
            </div>
        </div>

        <div v-if="dados.aviso" class="fluxo-aviso">
            <i class="bi-info-circle me-2"></i>{{ dados.aviso }}
        </div>

        <div v-if="erro" class="fluxo-aviso fluxo-aviso--erro">
            <i class="bi-exclamation-triangle me-2"></i>{{ erro }}
        </div>

        <div class="fluxo-grid">
            <div class="fluxo-listas">

                <div class="lista-card lista-card--receber">
                    <div class="lista-titulo"><i class="bi-currency-dollar"></i>Contas a Receber</div>
                    <div class="lista-scroll">
                        <table class="lista-tabela">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th class="col-vencimento">Vencimento</th>
                                    <th class="col-valor">Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="receber.length === 0">
                                    <td colspan="3" class="lista-vazia">{{ consultado ? 'Nenhuma conta a receber no período.' : 'Clique em Atualizar para consultar.' }}</td>
                                </tr>
                                <tr v-for="(item, i) in receber" :key="i">
                                    <td :title="item.descricao">{{ item.descricao }}</td>
                                    <td class="col-vencimento">{{ item.vencimento }}</td>
                                    <td class="col-valor">{{ formatarNumero(item.valor) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="lista-total">
                        <span>Total a Receber</span>
                        <span>{{ formatarMoeda(totalReceber) }}</span>
                    </div>
                </div>

                <div class="lista-card lista-card--pagar">
                    <div class="lista-titulo"><i class="bi-currency-dollar"></i>Contas a Pagar</div>
                    <div class="lista-scroll">
                        <table class="lista-tabela">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th class="col-vencimento">Vencimento</th>
                                    <th class="col-valor">Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="pagar.length === 0">
                                    <td colspan="3" class="lista-vazia">{{ consultado ? 'Nenhuma conta a pagar no período.' : 'Clique em Atualizar para consultar.' }}</td>
                                </tr>
                                <tr v-for="(item, i) in pagar" :key="i">
                                    <td :title="item.descricao">{{ item.descricao }}</td>
                                    <td class="col-vencimento">{{ item.vencimento }}</td>
                                    <td class="col-valor">{{ formatarNumero(item.valor) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="lista-total">
                        <span>Total a Pagar</span>
                        <span>{{ formatarMoeda(totalPagar) }}</span>
                    </div>
                </div>

            </div>

            <div class="resumo-card">
                <div class="resumo-titulo"><i class="bi-bar-chart-fill"></i>Resumo do Período</div>

                <div class="resumo-item">
                    <span class="resumo-icone resumo-icone--azul"><i class="bi-bank"></i></span>
                    <div>
                        <div class="resumo-label">Saldo inicial</div>
                        <div class="resumo-valor">{{ formatarMoeda(saldoInicial) }}</div>
                    </div>
                </div>

                <div class="resumo-item">
                    <span class="resumo-icone resumo-icone--verde"><i class="bi-arrow-down"></i></span>
                    <div>
                        <div class="resumo-label">Contas a receber (CR)</div>
                        <div class="resumo-valor">{{ formatarMoeda(totalReceber) }}</div>
                    </div>
                </div>

                <div class="resumo-item">
                    <span class="resumo-icone resumo-icone--vermelho"><i class="bi-arrow-up"></i></span>
                    <div>
                        <div class="resumo-label">Contas a pagar (CP)</div>
                        <div class="resumo-valor">{{ formatarMoeda(totalPagar) }}</div>
                    </div>
                </div>

                <hr />

                <div class="resumo-destaque" :class="saldoProjetado < 0 ? 'resumo-destaque--negativo' : 'resumo-destaque--positivo'">
                    <span class="resumo-icone"><i class="bi-calculator"></i></span>
                    <div>
                        <div class="resumo-label">Saldo projetado</div>
                        <div class="resumo-valor-grande">{{ formatarMoeda(saldoProjetado) }}</div>
                    </div>
                </div>

                <div class="resumo-destaque resumo-destaque--aporte">
                    <span class="resumo-icone"><i class="bi-coin"></i></span>
                    <div class="flex-grow-1">
                        <div class="resumo-label">Necessidade de aporte</div>
                        <div class="resumo-valor-grande">{{ formatarMoeda(necessidadeAporte) }}</div>
                    </div>
                    <i class="bi-info-circle" title="Valor necessário para o saldo projetado não ficar negativo."></i>
                </div>

                <div class="resumo-acoes">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm me-2"
                        title="Baixar planilha Excel"
                        @click="baixarPlanilha"
                    >
                        <i class="bi-file-earmark-excel"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        title="Copiar para área de transferência"
                        @click="copiarTela"
                    >
                        <i class="bi-clipboard"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';
import SearchableSelect from '../components/SearchableSelect.vue';
import { toBlob } from 'html-to-image';

export default {
    mixins: [util],
    components: { SearchableSelect },
    name: 'fluxo-caixa',
    data() {
        const hoje = new Date();

        return {
            dataInicial: this.formatarData(new Date(hoje.getFullYear(), hoje.getMonth(), 1)),
            dataFinal: this.formatarData(new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0)),
            filial: null,
            saldoInicial: 0,
            saldoInicialTexto: this.formatarMoeda(0),
            dados: {},
            erro: null,
            consultado: false,
            recebiveisFuturos: false,
            idEmpreendimento: null,
            empreendimentos: [],
            contasReceber: 'CRR',
            consulta: null,
        };
    },
    computed: {
        dataAmanha() {
            const amanha = new Date();
            amanha.setDate(amanha.getDate() + 1);
            return amanha.toLocaleDateString('pt-BR');
        },
        empreendimentoOptions() {
            return this.empreendimentos.map((e) => ({ id: e.codigo, label: e.codigo + ' - ' + e.descricao }));
        },
        filialOptions() {
            return (this.dados.filiais || []).map((f) => ({ id: f.codigo, label: f.codigo + ' - ' + f.nome }));
        },
        receber() {
            const itens = this.dados.receber || [];
            if (!this.recebiveisFuturos) return itens;

            const hoje = this.formatarData(new Date());
            return itens.filter((item) => item.dia > hoje);
        },
        pagar() {
            return this.dados.pagar || [];
        },
        totalReceber() {
            return this.receber.reduce((total, item) => total + item.valor, 0);
        },
        totalPagar() {
            return this.dados.totalPagar || 0;
        },
        saldoProjetado() {
            return this.saldoInicial + this.totalReceber - this.totalPagar;
        },
        necessidadeAporte() {
            return this.saldoProjetado < 0 ? Math.abs(this.saldoProjetado) : 0;
        },
    },
    mounted() {
        this.carregarFiltros();
    },
    methods: {
        formatarData(data) {
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const dia = String(data.getDate()).padStart(2, '0');
            return `${data.getFullYear()}-${mes}-${dia}`;
        },

        carregarFiltros() {
            this.showPreLoader();

            this.axios({ method: 'get', url: '/web/fluxo-caixa/filtros' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.dados = response.data.data;
                        this.filial = this.dados.filial;
                        this.aplicarEmpreendimentos(this.dados);
                    } else {
                        this.erro = 'Não foi possível carregar os filtros.';
                    }
                })
                .catch(() => {
                    this.erro = 'Não foi possível carregar os filtros.';
                })
                .finally(() => this.hidePreLoader());
        },

        carregar() {
            if (!this.dataInicial || !this.dataFinal) return;

            this.showPreLoader();
            this.erro = null;

            this.axios({
                method: 'get',
                url: '/web/fluxo-caixa',
                params: {
                    dataInicial: this.dataInicial,
                    dataFinal: this.dataFinal,
                    filial: this.filial,
                    idEmpreendimento: this.idEmpreendimento,
                },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.dados = response.data.data;
                        this.filial = this.dados.filial;
                        this.consultado = true;
                        this.consulta = {
                            dataInicial: this.dataInicial,
                            dataFinal: this.dataFinal,
                            filial: this.dados.filial,
                            filialNome: this.dados.filialNome,
                            idEmpreendimento: this.idEmpreendimento,
                        };
                    } else {
                        this.dados = { ...this.dados, receber: [], pagar: [], totalReceber: 0, totalPagar: 0, aviso: null };
                        this.erro = 'Não foi possível carregar os dados da Mega. Tente novamente em alguns instantes.';
                    }
                })
                .catch(() => {
                    this.erro = 'Não foi possível carregar o fluxo de caixa.';
                })
                .finally(() => this.hidePreLoader());
        },

        trocarFilial(codigo) {
            if (codigo === this.filial) return;

            this.filial = codigo;
            this.saldoInicial = 0;
            this.saldoInicialTexto = this.formatarMoeda(0);
            this.carregarEmpreendimentos(codigo);
        },

        trocarEmpreendimento(codigo) {
            if (codigo === this.idEmpreendimento) return;

            this.idEmpreendimento = codigo;
            this.saldoInicial = 0;
            this.saldoInicialTexto = this.formatarMoeda(0);
        },

        carregarEmpreendimentos(filial) {
            this.empreendimentos = [];
            this.idEmpreendimento = null;

            this.axios({ method: 'get', url: '/web/fluxo-caixa/filtros', params: { filial } })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.aplicarEmpreendimentos(response.data.data);
                    } else {
                        this.erro = 'Não foi possível carregar os empreendimentos da filial.';
                    }
                })
                .catch(() => {
                    this.erro = 'Não foi possível carregar os empreendimentos da filial.';
                });
        },

        aplicarEmpreendimentos(filtros) {
            this.empreendimentos = filtros.empreendimentos || [];
            this.contasReceber = filtros.contasReceber || 'CRR';
            this.idEmpreendimento = this.empreendimentos.length === 1 ? this.empreendimentos[0].codigo : null;
        },

        baixarPlanilha() {
            if (!this.consulta) {
                this.alertMessage = 'Clique em Atualizar antes de baixar a planilha.';
                this.showAlert('error');
                return;
            }

            const amanha = new Date();
            amanha.setDate(amanha.getDate() + 1);

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/fluxo-caixa/planilha',
                responseType: 'blob',
                data: {
                    dados: {
                        receber: this.dados.receber || [],
                        receberNaoConsiderados: this.dados.receberNaoConsiderados || [],
                        pagar: this.dados.pagar || [],
                        pagarNaoConsiderados: this.dados.pagarNaoConsiderados || [],
                    },
                    filtros: {
                        ...this.consulta,
                        saldoInicial: this.saldoInicial,
                        dataCorte: this.recebiveisFuturos ? this.formatarData(amanha) : null,
                    },
                },
            })
                .then((response) => {
                    if (!response.data || (response.data.type || '').includes('json')) {
                        this.alertMessage = 'Não foi possível gerar a planilha.';
                        this.showAlert('error');
                        return;
                    }

                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(response.data);
                    link.download = `fluxo-caixa-filial-${this.consulta.filial}-${this.consulta.dataInicial}-a-${this.consulta.dataFinal}.xlsx`;
                    link.click();
                    URL.revokeObjectURL(link.href);
                })
                .catch(() => {
                    this.alertMessage = 'Não foi possível gerar a planilha.';
                    this.showAlert('error');
                })
                .finally(() => this.hidePreLoader());
        },

        copiarTela() {
            if (!navigator.clipboard || !window.ClipboardItem) {
                this.alertMessage = 'Este navegador não permite copiar imagens. O site precisa estar em HTTPS.';
                this.showAlert('error');
                return;
            }

            const imagem = toBlob(this.$refs.tela, {
                backgroundColor: '#f3f4f7',
                pixelRatio: 2,
                filter: (node) => !node.classList || !['resumo-acoes', 'filtro-acao'].some((classe) => node.classList.contains(classe)),
            });

            navigator.clipboard.write([new ClipboardItem({ 'image/png': imagem })])
                .then(() => {
                    this.alertMessage = 'Tela copiada para a área de transferência!';
                    this.showAlert('success');
                })
                .catch(() => {
                    this.alertMessage = 'Não foi possível copiar a tela.';
                    this.showAlert('error');
                });
        },

        atualizarSaldoInicial(event) {
            const negativo = event.target.value.includes('-');
            const centavos = Number(event.target.value.replace(/\D/g, '')) || 0;

            this.saldoInicial = (negativo ? -centavos : centavos) / 100;
            this.saldoInicialTexto = this.formatarMoeda(this.saldoInicial);
            event.target.value = this.saldoInicialTexto;
        },

        formatarMoeda(valor) {
            return (valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        },

        formatarNumero(valor) {
            return (valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    },
};
</script>

<style scoped>
.fluxo {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: calc(100vh - 160px);
    min-height: 560px;
    color: #1f2d3d;
}

.fluxo-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.fluxo-titulo > i {
    color: #2f80ed;
    font-size: 24px;
}
.fluxo-titulo h4 {
    font-weight: 700;
}
.fluxo-atualizar {
    flex: 1;
    padding: 0 18px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #1C4D64;
    border-color: #1C4D64;
    color: #fff;
}
.fluxo-atualizar:hover,
.fluxo-atualizar:focus,
.fluxo-atualizar:active {
    background-color: #255E78;
    border-color: #255E78;
    color: #fff;
}
.fluxo-atualizar i {
    line-height: 1;
}

.fluxo-filtros {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 12px;
}
.filtro-card,
.lista-card,
.resumo-card {
    background: #fff;
    border: 1px solid #e6e9ef;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(16, 24, 40, 0.06);
}
.filtro-card {
    min-width: 0;
    padding: 10px 14px;
}
.filtro-consulta {
    display: flex;
    align-items: stretch;
    gap: 16px;
}
.filtro-grupo {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.filtro-grupo--filial {
    flex: 1;
    padding-left: 16px;
    border-left: 1px solid #e6e9ef;
}
.filtro-flag {
    flex: 1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #6c757d;
    font-size: 14px;
    white-space: nowrap;
}
.filtro-flag--ativa {
    color: #1C4D64;
    font-weight: 600;
}
.filtro-empreendimento {
    flex: none;
    width: 240px;
}
.filtro-acao {
    flex: none;
    margin-left: auto;
}
.filtro-label {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 6px;
}
.filtro-periodo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.filtro-periodo span {
    font-size: 14px;
    color: #6c757d;
}
.filtro-periodo input {
    flex: none;
    width: 150px;
}

.fluxo-aviso {
    background: #e8f1fd;
    border-left: 4px solid #2f80ed;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
}
.fluxo-aviso--erro {
    background: #fdecea;
    border-left-color: #d92d20;
}

.fluxo-grid {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 12px;
}
.fluxo-listas {
    display: grid;
    grid-template-rows: minmax(0, 1fr) minmax(0, 1fr);
    gap: 12px;
    min-height: 0;
}

.lista-card {
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 10px 14px;
}
.lista-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 8px;
    padding-left: 10px;
    border-left: 4px solid;
}
.lista-titulo i {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border: 2px solid;
    border-radius: 50%;
    font-size: 13px;
}
.lista-card--receber .lista-titulo {
    color: #15803d;
    border-left-color: #16a34a;
}
.lista-card--pagar .lista-titulo {
    color: #c81e1e;
    border-left-color: #dc2626;
}
.lista-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}
.lista-tabela {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: 13px;
}
.lista-tabela th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f0f2f6;
    padding: 6px 10px;
    font-weight: 600;
}
.lista-tabela td {
    padding: 6px 10px;
    border-bottom: 1px solid #edf0f4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.col-vencimento {
    width: 110px;
}
.col-valor {
    width: 120px;
    text-align: right;
}
.lista-tabela .lista-vazia {
    text-align: center;
    color: #6c757d;
    padding: 16px;
}
.lista-total {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 700;
}
.lista-card--receber .lista-total {
    background: #e3f6e8;
    color: #15803d;
}
.lista-card--pagar .lista-total {
    background: #fde4e4;
    color: #c81e1e;
}

.resumo-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    min-height: 0;
    overflow-y: auto;
}
.resumo-card hr {
    margin: 2px 0;
}
.resumo-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 17px;
    font-weight: 700;
}
.resumo-titulo i {
    color: #64748b;
}
.resumo-item,
.resumo-destaque {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 8px;
}
.resumo-item {
    background: #f7f8fa;
    border: 1px solid #edf0f4;
}
.resumo-icone {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    font-size: 17px;
}
.resumo-icone--azul {
    background: #e1edfd;
    color: #2f80ed;
}
.resumo-icone--verde {
    background: #e3f6e8;
    color: #16a34a;
}
.resumo-icone--vermelho {
    background: #fde4e4;
    color: #dc2626;
}
.resumo-label {
    font-size: 13px;
}
.resumo-valor {
    font-size: 15px;
    font-weight: 700;
}
.resumo-valor-grande {
    font-size: 20px;
    font-weight: 700;
}
.resumo-destaque--positivo {
    background: #e3f6e8;
    color: #15803d;
}
.resumo-destaque--negativo {
    background: #fde4e4;
    color: #c81e1e;
}
.resumo-destaque--aporte {
    background: #e1edfd;
    color: #2f80ed;
}
.resumo-acoes {
    margin-top: auto;
    text-align: right;
}
.resumo-destaque .resumo-label {
    color: #1f2d3d;
    font-weight: 600;
}

@media (max-width: 992px) {
    .fluxo {
        height: auto;
    }
    .filtro-consulta {
        flex-wrap: wrap;
    }
    .filtro-grupo--filial {
        flex: 1 1 100%;
        padding-left: 0;
        border-left: 0;
    }
    .fluxo-filtros,
    .fluxo-grid {
        grid-template-columns: minmax(0, 1fr);
    }
    .fluxo-listas {
        grid-template-rows: auto;
    }
    .lista-scroll {
        max-height: 300px;
    }
}
</style>
