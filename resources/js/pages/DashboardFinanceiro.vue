<template>
    <div class="fin-dashboard">

        <div class="fin-header">
            <div>
                <div class="fin-title">Contas a Pagar</div>
                <div class="fin-subtitle" v-if="dados.empresa">
                    {{ dados.empresa }}<span v-if="dados.filial"> · Filial {{ dados.filial }}</span>
                </div>
            </div>
            <div class="fin-filters">
                <button class="fin-btn" @click="mudarMes(-1)" title="Mês anterior">
                    <i class="bi-chevron-left"></i>
                </button>
                <input type="month" class="fin-month" v-model="mes" @change="carregar(false)" />
                <button class="fin-btn" @click="mudarMes(1)" title="Próximo mês">
                    <i class="bi-chevron-right"></i>
                </button>
                <button class="fin-btn fin-btn--text" @click="carregar(true)" title="Buscar dados atualizados na Mega">
                    <i class="bi-arrow-clockwise me-1"></i>Atualizar
                </button>
            </div>
        </div>

        <div v-if="dados.aviso" class="fin-aviso">
            <i class="bi-info-circle me-2"></i>{{ dados.aviso }}
        </div>

        <div v-else-if="erro" class="fin-aviso fin-aviso--erro">
            <i class="bi-exclamation-triangle me-2"></i>{{ erro }}
        </div>

        <template v-else-if="resumo">

            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Total do mês</div>
                    <div class="kpi-value">{{ formatarMoeda(resumo.total.valor) }}</div>
                    <div class="kpi-info">{{ resumo.total.quantidade }} parcelas</div>
                </div>
                <div class="kpi-card kpi-card--pago kpi-card--clickable" @click="filtrarSituacao('paga')">
                    <div class="kpi-label">Pago</div>
                    <div class="kpi-value">{{ formatarMoeda(resumo.pago.valor) }}</div>
                    <div class="kpi-info">{{ percentual(resumo.pago.valor, resumo.total.valor) }} do total</div>
                    <div class="kpi-info kpi-info--parcial" v-if="resumo.parcial.quantidade > 0" @click.stop="filtrarSituacao('parcial')">
                        inclui {{ formatarMoeda(resumo.parcial.baixado) }} de {{ resumo.parcial.quantidade }} {{ resumo.parcial.quantidade === 1 ? 'baixa parcial' : 'baixas parciais' }}
                    </div>
                </div>
                <div class="kpi-card kpi-card--avencer kpi-card--clickable" @click="filtrarSituacao('aVencer')">
                    <div class="kpi-label">A vencer</div>
                    <div class="kpi-value">{{ formatarMoeda(resumo.aVencer.valor) }}</div>
                    <div class="kpi-info">{{ resumo.aVencer.quantidade }} parcelas</div>
                </div>
                <div class="kpi-card kpi-card--vencido kpi-card--clickable" @click="filtrarSituacao('vencida')">
                    <div class="kpi-label">Vencido no mês</div>
                    <div class="kpi-value">{{ formatarMoeda(resumo.vencido.valor) }}</div>
                    <div class="kpi-info">{{ resumo.vencido.quantidade }} parcelas</div>
                </div>
            </div>

            <div class="chart-card">
                <highcharts :options="graficoPorDia" />
            </div>

            <div class="charts-row">
                <div class="chart-card">
                    <highcharts :options="graficoSituacao" />
                </div>
                <div class="chart-card">
                    <highcharts :options="graficoAtraso" />
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-card">
                    <highcharts :options="graficoTipoDocumento" />
                </div>
                <div class="chart-card">
                    <highcharts :options="graficoFornecedor" />
                </div>
            </div>

            <div class="table-card" ref="tabela">
                <div class="table-toolbar">
                    <div class="table-title">Parcelas</div>
                    <div class="situacao-filtros">
                        <button
                            v-for="f in filtrosSituacao"
                            :key="f.valor"
                            class="filtro-btn"
                            :class="{ 'filtro-btn--ativo': situacaoFiltro === f.valor }"
                            @click="situacaoFiltro = f.valor"
                        >
                            {{ f.nome }} <span class="filtro-qtd">{{ contarSituacao(f.valor) }}</span>
                        </button>
                    </div>
                    <div class="table-search">
                        <i class="bi-search"></i>
                        <input type="text" v-model="busca" placeholder="Buscar fornecedor, documento..." />
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th class="sortable" @click="ordenarPor('dia')">Vencimento <i :class="iconeOrdem('dia')"></i></th>
                                <th class="sortable" @click="ordenarPor('fornecedor')">Fornecedor <i :class="iconeOrdem('fornecedor')"></i></th>
                                <th class="sortable" @click="ordenarPor('tipoDocumento')">Tipo <i :class="iconeOrdem('tipoDocumento')"></i></th>
                                <th>Documento</th>
                                <th>Parcela</th>
                                <th class="text-end sortable" @click="ordenarPor('valor')">Valor <i :class="iconeOrdem('valor')"></i></th>
                                <th class="text-end sortable" @click="ordenarPor('pago')">Pago <i :class="iconeOrdem('pago')"></i></th>
                                <th class="text-end sortable" @click="ordenarPor('saldo')">Saldo <i :class="iconeOrdem('saldo')"></i></th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="parcelasFiltradas.length === 0">
                                <td colspan="9" class="text-center">Nenhuma parcela encontrada.</td>
                            </tr>
                            <tr v-for="(p, i) in parcelasFiltradas" :key="i">
                                <td>{{ p.vencimento }}</td>
                                <td>
                                    <div>{{ p.fornecedor }}</div>
                                    <div class="cnpj" v-if="p.cnpj">{{ p.cnpj }}</div>
                                </td>
                                <td>{{ p.tipoDocumento }}</td>
                                <td>{{ p.numeroDocumento }}</td>
                                <td>{{ p.parcela }}</td>
                                <td class="text-end">{{ formatarMoeda(p.valor) }}</td>
                                <td class="text-end">{{ formatarMoeda(p.pago) }}</td>
                                <td class="text-end">{{ formatarMoeda(p.saldo) }}</td>
                                <td>
                                    <span class="badge-situacao" :class="'badge-situacao--' + p.situacao">
                                        {{ nomeSituacao(p) }}
                                    </span>
                                    <span v-if="p.parcial" class="badge-situacao badge-situacao--parcial">Baixa parcial</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="parcelasFiltradas.length > 0">
                            <tr>
                                <td colspan="5"><strong>Total ({{ parcelasFiltradas.length }})</strong></td>
                                <td class="text-end"><strong>{{ formatarMoeda(somar(parcelasFiltradas, 'valor')) }}</strong></td>
                                <td class="text-end"><strong>{{ formatarMoeda(somar(parcelasFiltradas, 'pago')) }}</strong></td>
                                <td class="text-end"><strong>{{ formatarMoeda(somar(parcelasFiltradas, 'saldo')) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="fin-rodape">
                Situação calculada em {{ dados.dataReferencia }}.
            </div>

        </template>
    </div>
</template>

<script>
import { util } from '../mixins/util'
import { Chart } from 'highcharts-vue'

const CORES = {
    pago: '#1C9E8E',
    aVencer: '#4AACC5',
    vencido: '#E8604C',
    texto: '#CCECF5',
    grade: '#1E6A85',
    linha: '#3A7A95',
}

const baseGrafico = {
    chart: {
        backgroundColor: 'transparent',
        style: { fontFamily: 'Arial, sans-serif' },
        height: 300,
    },
    credits: { enabled: false },
    title: {
        align: 'left',
        style: { color: '#FFFFFF', fontWeight: 'bold', fontSize: '14px' },
    },
    legend: {
        itemStyle: { color: CORES.texto, fontWeight: 'normal' },
        itemHoverStyle: { color: '#FFFFFF' },
    },
    tooltip: {
        backgroundColor: '#0E3E52',
        style: { color: '#FFFFFF' },
        borderColor: CORES.aVencer,
    },
}

const eixoCategorias = (categorias) => ({
    categories: categorias,
    labels: { style: { color: CORES.texto, fontSize: '11px' } },
    lineColor: CORES.linha,
    tickColor: CORES.linha,
})

export default {
    mixins: [util],
    components: { highcharts: Chart },
    name: 'dashboard-financeiro',
    data() {
        return {
            mes: this.mesAtual(),
            dados: {},
            erro: null,
            busca: '',
            situacaoFiltro: 'todas',
            ordem: { campo: 'dia', direcao: 1 },
            filtrosSituacao: [
                { valor: 'todas', nome: 'Todas' },
                { valor: 'paga', nome: 'Pagas' },
                { valor: 'aVencer', nome: 'A vencer' },
                { valor: 'vencida', nome: 'Vencidas' },
                { valor: 'parcial', nome: 'Baixa parcial' },
            ],
        }
    },
    computed: {
        resumo() {
            return this.dados.resumo || null
        },
        parcelas() {
            return this.dados.parcelas || []
        },
        parcelasFiltradas() {
            const termo = this.busca.trim().toLowerCase()

            const lista = this.parcelas.filter(p => {
                if (!this.passaFiltroSituacao(p, this.situacaoFiltro)) return false
                if (!termo) return true
                return [p.fornecedor, p.cnpj, p.tipoDocumento, p.numeroDocumento, p.numeroAP]
                    .some(v => v !== null && String(v).toLowerCase().includes(termo))
            })

            const { campo, direcao } = this.ordem
            return lista.sort((a, b) => {
                if (a[campo] < b[campo]) return -1 * direcao
                if (a[campo] > b[campo]) return 1 * direcao
                return 0
            })
        },
        graficoPorDia() {
            const vm = this
            const dias = this.dados.porDia || []

            return {
                ...baseGrafico,
                chart: { ...baseGrafico.chart, type: 'column' },
                title: { ...baseGrafico.title, text: 'Vencimentos por dia' },
                subtitle: {
                    align: 'left',
                    useHTML: true,
                    text: `<span style="color:${CORES.pago}">●</span> Pago ${this.formatarMoeda(this.resumo.pago.valor)}`
                        + ` &nbsp; <span style="color:${CORES.aVencer}">●</span> A vencer ${this.formatarMoeda(this.resumo.aVencer.valor)}`
                        + ` &nbsp; <span style="color:${CORES.vencido}">●</span> Vencido ${this.formatarMoeda(this.resumo.vencido.valor)}`,
                    style: { color: CORES.texto, fontSize: '12px' },
                },
                xAxis: eixoCategorias(dias.map(d => d.dia)),
                yAxis: {
                    title: { text: null },
                    labels: { style: { color: CORES.texto }, formatter() { return vm.formatarCompacto(this.value) } },
                    gridLineColor: CORES.grade,
                    stackLabels: { enabled: false },
                },
                tooltip: {
                    ...baseGrafico.tooltip,
                    shared: true,
                    formatter() {
                        let html = `<b>Dia ${this.points[0].key}</b>`
                        this.points.forEach(pt => {
                            if (pt.y > 0) html += `<br/><span style="color:${pt.color}">●</span> ${pt.series.name}: ${vm.formatarMoeda(pt.y)}`
                        })
                        return html
                    },
                },
                plotOptions: { column: { stacking: 'normal', borderWidth: 0, minPointLength: 4 } },
                series: [
                    { name: 'Pago', data: dias.map(d => d.pago || null), color: CORES.pago },
                    { name: 'A vencer', data: dias.map(d => d.aVencer || null), color: CORES.aVencer },
                    { name: 'Vencido', data: dias.map(d => d.vencido || null), color: CORES.vencido },
                ],
            }
        },
        graficoSituacao() {
            const vm = this
            const r = this.resumo

            return {
                ...baseGrafico,
                chart: { ...baseGrafico.chart, type: 'pie' },
                title: { ...baseGrafico.title, text: 'Situação do mês' },
                tooltip: {
                    ...baseGrafico.tooltip,
                    formatter() {
                        return `<b>${this.point.name}</b><br/>${vm.formatarMoeda(this.y)} (${this.percentage.toFixed(1)}%)`
                    },
                },
                plotOptions: {
                    pie: {
                        innerSize: '60%',
                        borderWidth: 0,
                        cursor: 'pointer',
                        point: { events: { click() { vm.filtrarSituacao(this.options.situacao) } } },
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '11px' },
                        },
                    },
                },
                series: [{
                    name: 'Situação',
                    data: [
                        { name: 'Pago', y: r.pago.valor, color: CORES.pago, situacao: 'paga' },
                        { name: 'A vencer', y: r.aVencer.valor, color: CORES.aVencer, situacao: 'aVencer' },
                        { name: 'Vencido', y: r.vencido.valor, color: CORES.vencido, situacao: 'vencida' },
                    ].filter(d => d.y > 0),
                }],
            }
        },
        graficoAtraso() {
            const vm = this
            const faixas = this.dados.vencidosPorAtraso || []

            return {
                ...baseGrafico,
                chart: { ...baseGrafico.chart, type: 'column' },
                title: { ...baseGrafico.title, text: 'Vencidos por tempo de atraso' },
                xAxis: eixoCategorias(faixas.map(f => f.nome)),
                yAxis: {
                    title: { text: null },
                    labels: { style: { color: CORES.texto }, formatter() { return vm.formatarCompacto(this.value) } },
                    gridLineColor: CORES.grade,
                },
                tooltip: {
                    ...baseGrafico.tooltip,
                    formatter() {
                        const qtd = faixas[this.point.index].quantidade
                        return `<b>${this.key}</b><br/>${vm.formatarMoeda(this.y)}<br/>${qtd} parcela${qtd !== 1 ? 's' : ''}`
                    },
                },
                plotOptions: {
                    column: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            formatter() { return this.y > 0 ? vm.formatarCompacto(this.y) : '' },
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '10px' },
                        },
                    },
                },
                series: [{ name: 'Vencido', data: faixas.map(f => f.valor), color: CORES.vencido, showInLegend: false }],
            }
        },
        graficoTipoDocumento() {
            return this.graficoBarras('Por tipo de documento', this.dados.porTipoDocumento || [], 'tipoDocumento')
        },
        graficoFornecedor() {
            return this.graficoBarras('10 maiores fornecedores', this.dados.porFornecedor || [], 'fornecedor')
        },
    },
    mounted() {
        this.carregar(false)
    },
    methods: {
        mesAtual() {
            const d = new Date()
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
        },
        mudarMes(delta) {
            const [ano, mes] = this.mes.split('-').map(Number)
            const d = new Date(ano, mes - 1 + delta, 1)
            this.mes = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
            this.carregar(false)
        },
        carregar(atualizar) {
            this.showPreLoader()
            this.erro = null

            this.axios({
                method: 'get',
                url: '/web/dashboard-financeiro',
                params: { mes: this.mes, atualizar: atualizar ? 1 : 0 },
            })
                .then(response => {
                    if (this.checkApiResponse(response)) {
                        this.dados = response.data.data
                    } else {
                        this.dados = {}
                        this.erro = 'Não foi possível carregar os dados da Mega. Tente novamente em alguns instantes.'
                    }
                })
                .catch(() => {
                    this.dados = {}
                    this.erro = 'Não foi possível carregar o dashboard.'
                })
                .finally(() => this.hidePreLoader())
        },
        graficoBarras(titulo, itens, campo) {
            const vm = this

            return {
                ...baseGrafico,
                chart: { ...baseGrafico.chart, type: 'bar', height: Math.max(300, itens.length * 32 + 80) },
                title: { ...baseGrafico.title, text: titulo },
                xAxis: eixoCategorias(itens.map(i => i.nome)),
                yAxis: {
                    title: { text: null },
                    labels: { style: { color: CORES.texto }, formatter() { return vm.formatarCompacto(this.value) } },
                    gridLineColor: CORES.grade,
                },
                tooltip: {
                    ...baseGrafico.tooltip,
                    formatter() {
                        const qtd = itens[this.point.index].quantidade
                        return `<b>${this.key}</b><br/>${vm.formatarMoeda(this.y)}<br/>${qtd} parcela${qtd !== 1 ? 's' : ''}`
                    },
                },
                plotOptions: {
                    bar: {
                        borderWidth: 0,
                        cursor: 'pointer',
                        point: { events: { click() { vm.buscarNaTabela(this.category) } } },
                        dataLabels: {
                            enabled: true,
                            formatter() { return vm.formatarCompacto(this.y) },
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '10px' },
                        },
                    },
                },
                series: [{ name: 'Valor', data: itens.map(i => i.valor), color: CORES.aVencer, showInLegend: false }],
            }
        },
        passaFiltroSituacao(p, filtro) {
            if (filtro === 'todas') return true
            if (filtro === 'parcial') return p.parcial
            return p.situacao === filtro
        },
        contarSituacao(filtro) {
            return this.parcelas.filter(p => this.passaFiltroSituacao(p, filtro)).length
        },
        filtrarSituacao(situacao) {
            this.situacaoFiltro = situacao
            this.rolarParaTabela()
        },
        buscarNaTabela(texto) {
            this.situacaoFiltro = 'todas'
            this.busca = texto
            this.rolarParaTabela()
        },
        rolarParaTabela() {
            this.$nextTick(() => this.$refs.tabela?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
        },
        ordenarPor(campo) {
            this.ordem = this.ordem.campo === campo
                ? { campo, direcao: this.ordem.direcao * -1 }
                : { campo, direcao: 1 }
        },
        iconeOrdem(campo) {
            if (this.ordem.campo !== campo) return 'bi-arrow-down-up text-muted-icon'
            return this.ordem.direcao === 1 ? 'bi-sort-up' : 'bi-sort-down'
        },
        nomeSituacao(p) {
            if (p.situacao === 'paga') return 'Paga'
            if (p.situacao === 'aVencer') return 'A vencer'
            return `Vencida há ${p.diasAtraso} dia${p.diasAtraso !== 1 ? 's' : ''}`
        },
        somar(lista, campo) {
            return lista.reduce((s, p) => s + (p[campo] || 0), 0)
        },
        percentual(valor, total) {
            if (!total) return '0%'
            return `${((valor / total) * 100).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`
        },
        formatarMoeda(valor) {
            return (valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        },
        formatarCompacto(valor) {
            if (!valor) return 'R$ 0'
            if (Math.abs(valor) >= 1e6) return `R$ ${(valor / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`
            if (Math.abs(valor) >= 1e3) return `R$ ${(valor / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mil`
            return `R$ ${valor.toLocaleString('pt-BR', { maximumFractionDigits: 0 })}`
        },
    },
}
</script>

<style scoped>
.fin-dashboard {
    background: #124C60;
    color: #FFFFFF;
    font-family: Arial, sans-serif;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: calc(100vh - 120px);
}

.fin-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: #20556E;
    border-radius: 8px;
    padding: 12px 16px;
}
.fin-title {
    font-size: 18px;
    font-weight: bold;
    color: #F0F9F8;
}
.fin-subtitle {
    font-size: 12px;
    color: #CCECF5;
}
.fin-filters {
    display: flex;
    align-items: center;
    gap: 6px;
}
.fin-month {
    background: #124C60;
    border: 1px solid #3A7A95;
    border-radius: 6px;
    color: #FFFFFF;
    padding: 4px 8px;
    font-size: 13px;
    color-scheme: dark;
}
.fin-btn {
    background: transparent;
    border: 1px solid #4AACC5;
    color: #4AACC5;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 13px;
    cursor: pointer;
}
.fin-btn:hover {
    background: #4AACC5;
    color: #FFFFFF;
}

.fin-aviso {
    background: #20556E;
    border-left: 4px solid #4AACC5;
    border-radius: 8px;
    padding: 16px;
    font-size: 14px;
}
.fin-aviso--erro {
    border-left-color: #E8604C;
}

.kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
.kpi-card {
    background: #20556E;
    border-radius: 8px;
    padding: 12px 16px;
    border-top: 3px solid #CCECF5;
}
.kpi-card--pago { border-top-color: #1C9E8E; }
.kpi-card--avencer { border-top-color: #4AACC5; }
.kpi-card--vencido { border-top-color: #E8604C; }
.kpi-card--clickable {
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
}
.kpi-card--clickable:hover {
    background: #2A6A88;
    transform: translateY(-2px);
}
.kpi-label {
    font-size: 11px;
    color: #CCECF5;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
}
.kpi-value {
    font-size: 22px;
    font-weight: bold;
    color: #F0F9F8;
    line-height: 1.2;
}
.kpi-info {
    font-size: 12px;
    color: #CCECF5;
    margin-top: 2px;
}
.kpi-info--parcial {
    color: #F4A460;
    text-decoration: underline dotted;
}

.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.chart-card {
    background: #20556E;
    border-radius: 8px;
    padding: 8px;
    min-width: 0;
}
.chart-card :deep(.highcharts-container) {
    width: 100% !important;
}

.table-card {
    background: #20556E;
    border-radius: 8px;
    padding: 12px;
}
.table-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}
.table-title {
    font-size: 14px;
    font-weight: bold;
}
.situacao-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.filtro-btn {
    background: transparent;
    border: 1px solid #3A7A95;
    color: #CCECF5;
    border-radius: 14px;
    padding: 2px 10px;
    font-size: 12px;
    cursor: pointer;
}
.filtro-btn--ativo {
    background: #4AACC5;
    border-color: #4AACC5;
    color: #FFFFFF;
}
.filtro-qtd {
    opacity: 0.75;
    margin-left: 2px;
}
.table-search {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    background: #124C60;
    border: 1px solid #3A7A95;
    border-radius: 6px;
    padding: 4px 8px;
    color: #CCECF5;
}
.table-search input {
    background: transparent;
    border: none;
    outline: none;
    color: #FFFFFF;
    font-size: 13px;
    width: 220px;
}
.table-wrapper {
    max-height: 500px;
    overflow: auto;
}
.fin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    color: #F0F9F8;
}
.fin-table th {
    position: sticky;
    top: 0;
    background: #20556E;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #CCECF5;
    padding: 8px;
    border-bottom: 1px solid #3A7A95;
    white-space: nowrap;
}
.fin-table th.sortable {
    cursor: pointer;
}
.fin-table td {
    padding: 8px;
    border-bottom: 1px solid #1E6A85;
    vertical-align: middle;
}
.fin-table tbody tr:hover td {
    background: #2A6A88;
}
.fin-table tfoot td {
    border-top: 1px solid #4AACC5;
    border-bottom: none;
}
.text-muted-icon {
    opacity: 0.4;
}
.cnpj {
    font-size: 11px;
    color: #CCECF5;
}
.badge-situacao {
    display: inline-block;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    margin-right: 4px;
}
.badge-situacao--paga { background: #1C9E8E; }
.badge-situacao--aVencer { background: #4AACC5; }
.badge-situacao--vencida { background: #E8604C; }
.badge-situacao--parcial { background: transparent; border: 1px solid #F4A460; color: #F4A460; }
.text-end { text-align: right; }
.text-center { text-align: center; }

.fin-rodape {
    font-size: 11px;
    color: #CCECF5;
    opacity: 0.8;
}

@media (max-width: 1200px) {
    .kpi-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .kpi-row { grid-template-columns: 1fr 1fr; }
    .charts-row { grid-template-columns: 1fr; }
    .table-search { margin-left: 0; width: 100%; }
    .table-search input { width: 100%; }
}
</style>
