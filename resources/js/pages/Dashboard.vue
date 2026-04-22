<template>
    <div class="dashboard">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <div class="client-name">{{ nomeCliente }}</div>
            </div>

            <div class="filter-section">
                <div class="filter-label-row">
                    <p class="filter-label mb-0">UF</p>
                    <button class="btn-toggle-all" @click="toggleAll('uf')">
                        {{ selectedUFs.length === ufOptions.length ? 'Desmarcar' : 'Marcar' }}
                    </button>
                </div>
                <div v-for="uf in ufOptions" :key="uf" class="filter-item">
                    <label class="filter-check">
                        <input type="checkbox" :value="uf" v-model="selectedUFs" />
                        <span>{{ uf }}</span>
                    </label>
                </div>
            </div>

            <div class="filter-section mt-3">
                <div class="filter-label-row">
                    <p class="filter-label mb-0">Fase Atual</p>
                    <button class="btn-toggle-all" @click="toggleAll('fase')">
                        {{ selectedFases.length === faseOptions.length ? 'Desmarcar' : 'Marcar' }}
                    </button>
                </div>
                <div v-for="fase in faseOptions" :key="fase" class="filter-item">
                    <label class="filter-check">
                        <input type="checkbox" :value="fase" v-model="selectedFases" />
                        <span>{{ fase }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <!-- KPI Cards -->
            <div class="kpi-row">
                <div
                    class="kpi-card"
                    :class="{ 'kpi-card--clickable': isTodasMode }"
                    @click="isTodasMode && openBreakdown('empreendimentos', null)"
                >
                    <div class="kpi-label">
                        Empreendimentos
                        <i v-if="isTodasMode" class="bi bi-bar-chart-fill kpi-drill-icon"></i>
                    </div>
                    <div class="kpi-value">{{ filteredData.length }}</div>
                </div>
                <div
                    class="kpi-card"
                    :class="{ 'kpi-card--clickable': isTodasMode }"
                    @click="isTodasMode && openBreakdown('vgv', null)"
                >
                    <div class="kpi-label">
                        VGV Total
                        <i v-if="isTodasMode" class="bi bi-bar-chart-fill kpi-drill-icon"></i>
                    </div>
                    <div class="kpi-value">{{ formatVGV(totalVGV) }}</div>
                </div>
                <div
                    class="kpi-card"
                    :class="{ 'kpi-card--clickable': isTodasMode }"
                    @click="isTodasMode && openBreakdown('area', null)"
                >
                    <div class="kpi-label">
                        Área Total
                        <i v-if="isTodasMode" class="bi bi-bar-chart-fill kpi-drill-icon"></i>
                    </div>
                    <div class="kpi-value">{{ formatArea(totalArea) }}</div>
                </div>
                <div
                    class="kpi-card"
                    :class="{ 'kpi-card--clickable': isTodasMode }"
                    @click="isTodasMode && openBreakdown('unidades', null)"
                >
                    <div class="kpi-label">
                        Unidades
                        <i v-if="isTodasMode" class="bi bi-bar-chart-fill kpi-drill-icon"></i>
                    </div>
                    <div class="kpi-value">{{ formatNumber(totalUnidades) }}</div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="charts-row">
                <div class="chart-card">
                    <highcharts :options="vgvPorRegiaoChart" />
                </div>
                <div class="chart-card">
                    <highcharts :options="vgvPorAnoChart" />
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="charts-row">
                <div class="chart-card">
                    <highcharts :options="unidadesPorRegiaoChart" />
                </div>
                <div class="chart-card">
                    <highcharts :options="empreendimentosPorFaseChart" />
                </div>
            </div>

        </div>

        <!-- Modal Breakdown por Cliente -->
        <div v-if="showBreakdown" class="breakdown-overlay" @click.self="showBreakdown = false">
            <div class="breakdown-modal">
                <div class="breakdown-header">
                    <div class="breakdown-header-content">
                        <div class="breakdown-title">{{ breakdownLabel }} por Cliente</div>
                        <div v-if="breakdownFilter" class="breakdown-subtitle">{{ breakdownFilter.value }}</div>
                    </div>
                    <button class="breakdown-close" @click="showBreakdown = false">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="breakdown-body">
                    <table class="breakdown-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">{{ breakdownLabel }}</th>
                                <th v-if="showPct" class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in breakdownRows" :key="row.clienteId">
                                <td>{{ row.clienteNome }}</td>
                                <td class="text-end">{{ row.formattedValue }}</td>
                                <td v-if="showPct" class="text-end">{{ row.pct }}%</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong>Total {{ breakdownFilter ? breakdownFilter.value : '' }}</strong></td>
                                <td class="text-end"><strong>{{ breakdownTotal }}</strong></td>
                                <td v-if="showPct" class="text-end"><strong>{{ breakdownTotalPct }}%</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { util } from '../mixins/util'
import Highcharts from 'highcharts'
import { Chart } from 'highcharts-vue'

const CHART_COLORS = ['#4AACC5', '#27AAE1', '#1C9E8E', '#8DD5DE', '#FF8C42', '#C5EDF2', '#2E8B9C', '#F4A460']

const baseChartOptions = {
    chart: {
        backgroundColor: 'transparent',
        style: { fontFamily: 'Arial, sans-serif' },
        height: 290,
    },
    credits: { enabled: false },
    colors: CHART_COLORS,
    title: {
        style: { color: '#FFFFFF', fontWeight: 'bold', fontSize: '14px' },
    },
    legend: {
        itemStyle: { color: '#CCECF5', fontWeight: 'normal' },
        itemHoverStyle: { color: '#FFFFFF' },
    },
    tooltip: {
        backgroundColor: '#0E3E52',
        style: { color: '#FFFFFF' },
        borderColor: '#4AACC5',
    },
    plotOptions: {
        series: {
            dataLabels: {
                style: { color: '#FFFFFF', textOutline: 'none', fontSize: '11px' },
            },
        },
    },
}

export default {
    mixins: [util],
    components: { highcharts: Chart },
    data() {
        return {
            projects: [],
            selectedUFs: [],
            selectedFases: [],
            showBreakdown: false,
            breakdownType: null,   // 'empreendimentos' | 'vgv' | 'area' | 'unidades'
            breakdownFilter: null, // { dim: 'uf'|'ano'|'fase', value: '...' } ou null
        }
    },
    computed: {
        ufOptions() {
            return [...new Set(this.projects.map(p => p.uf).filter(Boolean))].sort()
        },
        faseOptions() {
            return [...new Set(this.projects.map(p => p.faseAtual).filter(Boolean))].sort()
        },
        filteredData() {
            return this.projects.filter(p => {
                const ufOk = this.selectedUFs.includes(p.uf)
                const faseOk = this.selectedFases.includes(p.faseAtual)
                return ufOk && faseOk
            })
        },
        totalVGV() {
            return this.filteredData.reduce((s, p) => s + (p.vgv || 0), 0)
        },
        totalArea() {
            return this.filteredData.reduce((s, p) => s + (p.areaTotalM2 || 0), 0)
        },
        totalUnidades() {
            return this.filteredData.reduce((s, p) => s + (p.totalUnidades || 0), 0)
        },

        isTodasMode() {
            return this.projects.length > 0 && this.projects[0].isTodas === true
        },
        nomeCliente() {
            if (this.isTodasMode) return 'Portfólio Geral'
            return this.projects[0]?.clienteNome || ''
        },

        // --- Breakdown ---
        breakdownLabel() {
            const map = {
                empreendimentos: 'Empreendimentos',
                vgv: 'VGV Total',
                area: 'Área Total',
                unidades: 'Unidades',
            }
            return map[this.breakdownType] || ''
        },

        // Mostra % apenas nos gráficos de pizza (dim === 'uf')
        showPct() {
            return this.breakdownFilter?.dim === 'uf'
        },

        breakdownRows() {
            if (!this.breakdownType) return []

            // Total geral (todos os dados filtrados) — base para o %
            const grandTotal = this.filteredData.reduce((s, p) => {
                if (this.breakdownType === 'empreendimentos') return s + 1
                if (this.breakdownType === 'vgv')             return s + (p.vgv || 0)
                if (this.breakdownType === 'area')            return s + (p.areaTotalM2 || 0)
                if (this.breakdownType === 'unidades')        return s + (p.totalUnidades || 0)
                return s
            }, 0)

            // Dados filtrados pelo segmento clicado
            let data = this.filteredData
            if (this.breakdownFilter) {
                const { dim, value } = this.breakdownFilter
                if (dim === 'uf')   data = data.filter(p => p.uf === value)
                if (dim === 'ano')  data = data.filter(p => p.anoLancamento === value)
                if (dim === 'fase') data = data.filter(p => (p.faseAtual || 'Não informado') === value)
            }

            const grouped = {}
            data.forEach(p => {
                const id = p.clienteId
                if (!grouped[id]) {
                    grouped[id] = { clienteId: id, clienteNome: p.clienteNome || `Cliente ${id}`, value: 0 }
                }
                if (this.breakdownType === 'empreendimentos') grouped[id].value += 1
                else if (this.breakdownType === 'vgv')        grouped[id].value += (p.vgv || 0)
                else if (this.breakdownType === 'area')       grouped[id].value += (p.areaTotalM2 || 0)
                else if (this.breakdownType === 'unidades')   grouped[id].value += (p.totalUnidades || 0)
            })

            const rows = Object.values(grouped).sort((a, b) => b.value - a.value)

            return rows.map(r => ({
                ...r,
                formattedValue: this.formatBreakdownValue(r.value),
                // % de cada cliente em relação ao total geral (não só do segmento)
                pct: grandTotal > 0 ? ((r.value / grandTotal) * 100).toFixed(1) : '0.0',
            }))
        },

        breakdownTotal() {
            const segmentTotal = this.breakdownRows.reduce((s, r) => s + r.value, 0)
            return this.formatBreakdownValue(segmentTotal)
        },

        // % do segmento inteiro no total geral (ex: PR = 38.5%)
        breakdownTotalPct() {
            const grandTotal = this.filteredData.reduce((s, p) => {
                if (this.breakdownType === 'empreendimentos') return s + 1
                if (this.breakdownType === 'vgv')             return s + (p.vgv || 0)
                if (this.breakdownType === 'area')            return s + (p.areaTotalM2 || 0)
                if (this.breakdownType === 'unidades')        return s + (p.totalUnidades || 0)
                return s
            }, 0)
            const segmentTotal = this.breakdownRows.reduce((s, r) => s + r.value, 0)
            return grandTotal > 0 ? ((segmentTotal / grandTotal) * 100).toFixed(1) : '0.0'
        },

        // Chart 1: VGV por Região (Pie) — clicável em modo Todas
        vgvPorRegiaoChart() {
            const vm = this
            const grouped = {}
            this.filteredData.forEach(p => {
                if (!p.uf) return
                grouped[p.uf] = (grouped[p.uf] || 0) + (p.vgv || 0)
            })
            const data = Object.entries(grouped)
                .filter(([, v]) => v > 0)
                .map(([name, y]) => ({ name, y }))

            return {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'pie' },
                title: { ...baseChartOptions.title, text: 'Composição do VGV por Região' },
                tooltip: {
                    ...baseChartOptions.tooltip,
                    formatter() {
                        const v = this.y
                        const fmt = v >= 1e9
                            ? `R$ ${(v / 1e9).toFixed(2).replace('.', ',')} Bi`
                            : `R$ ${(v / 1e6).toFixed(1).replace('.', ',')} Mi`
                        const hint = vm.isTodasMode ? '<br/><small>Clique para detalhar por cliente</small>' : ''
                        return `<b>${this.point.name}</b><br/>${fmt} (${this.percentage.toFixed(1)}%)${hint}`
                    },
                },
                plotOptions: {
                    pie: {
                        cursor: vm.isTodasMode ? 'pointer' : 'default',
                        point: {
                            events: {
                                click: function () {
                                    if (!vm.isTodasMode) return
                                    vm.openBreakdown('vgv', { dim: 'uf', value: this.name })
                                },
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '11px' },
                        },
                    },
                },
                series: [{ name: 'VGV', colorByPoint: true, data }],
            }
        },

        // Chart 2: VGV por Ano de Lançamento (Column) — clicável em modo Todas
        vgvPorAnoChart() {
            const vm = this
            const grouped = {}
            this.filteredData.forEach(p => {
                if (!p.anoLancamento) return
                grouped[p.anoLancamento] = (grouped[p.anoLancamento] || 0) + (p.vgv || 0)
            })
            const years = Object.keys(grouped).sort()
            const values = years.map(y => grouped[y])

            return {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'column' },
                title: { ...baseChartOptions.title, text: 'VGV por Previsão de Lançamento' },
                xAxis: {
                    categories: years,
                    labels: { style: { color: '#CCECF5' } },
                    lineColor: '#3A7A95',
                    tickColor: '#3A7A95',
                },
                yAxis: {
                    title: { text: null },
                    labels: {
                        style: { color: '#CCECF5' },
                        formatter() {
                            return this.value >= 1e9
                                ? `R$ ${(this.value / 1e9).toFixed(1)}Bi`
                                : `R$ ${(this.value / 1e6).toFixed(0)}Mi`
                        },
                    },
                    gridLineColor: '#1E6A85',
                },
                tooltip: {
                    ...baseChartOptions.tooltip,
                    formatter() {
                        const v = this.y
                        const fmt = v >= 1e9
                            ? `R$ ${(v / 1e9).toFixed(2).replace('.', ',')} Bi`
                            : `R$ ${(v / 1e6).toFixed(1).replace('.', ',')} Mi`
                        const hint = vm.isTodasMode ? '<br/><small>Clique para detalhar por cliente</small>' : ''
                        return `<b>${this.x}</b><br/>${fmt}${hint}`
                    },
                },
                plotOptions: {
                    column: {
                        cursor: vm.isTodasMode ? 'pointer' : 'default',
                        point: {
                            events: {
                                click: function () {
                                    if (!vm.isTodasMode) return
                                    vm.openBreakdown('vgv', { dim: 'ano', value: this.category })
                                },
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            formatter() {
                                return this.y >= 1e9
                                    ? `${(this.y / 1e9).toFixed(1)}Bi`
                                    : `${(this.y / 1e6).toFixed(0)}Mi`
                            },
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '10px' },
                        },
                    },
                },
                series: [{ name: 'VGV', data: values, showInLegend: false }],
            }
        },

        // Chart 3: Unidades por Região (Pie) — clicável em modo Todas
        unidadesPorRegiaoChart() {
            const vm = this
            const grouped = {}
            this.filteredData.forEach(p => {
                if (!p.uf) return
                grouped[p.uf] = (grouped[p.uf] || 0) + (p.totalUnidades || 0)
            })
            const data = Object.entries(grouped)
                .filter(([, v]) => v > 0)
                .map(([name, y]) => ({ name, y }))

            return {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'pie' },
                title: { ...baseChartOptions.title, text: 'Nº de Unidades por Região' },
                tooltip: {
                    ...baseChartOptions.tooltip,
                    formatter() {
                        const hint = vm.isTodasMode ? '<br/><small>Clique para detalhar por cliente</small>' : ''
                        return `<b>${this.point.name}</b><br/>${this.y.toLocaleString('pt-BR')} unidades (${this.percentage.toFixed(1)}%)${hint}`
                    },
                },
                plotOptions: {
                    pie: {
                        cursor: vm.isTodasMode ? 'pointer' : 'default',
                        point: {
                            events: {
                                click: function () {
                                    if (!vm.isTodasMode) return
                                    vm.openBreakdown('unidades', { dim: 'uf', value: this.name })
                                },
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '11px' },
                        },
                    },
                },
                series: [{ name: 'Unidades', colorByPoint: true, data }],
            }
        },

        // Chart 4: Empreendimentos por Etapa (Horizontal Bar) — clicável em modo Todas
        empreendimentosPorFaseChart() {
            const vm = this
            const grouped = {}
            this.filteredData.forEach(p => {
                const fase = p.faseAtual || 'Não informado'
                grouped[fase] = (grouped[fase] || 0) + 1
            })
            const fases = Object.keys(grouped).sort((a, b) => grouped[b] - grouped[a])
            const values = fases.map(f => grouped[f])

            return {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar' },
                title: { ...baseChartOptions.title, text: 'Empreendimentos por Etapa' },
                xAxis: {
                    categories: fases,
                    labels: { style: { color: '#CCECF5', fontSize: '11px' } },
                    lineColor: '#3A7A95',
                },
                yAxis: {
                    title: { text: null },
                    labels: { style: { color: '#CCECF5' }, format: '{value}' },
                    gridLineColor: '#1E6A85',
                    allowDecimals: false,
                },
                tooltip: {
                    ...baseChartOptions.tooltip,
                    formatter() {
                        const hint = vm.isTodasMode ? '<br/><small>Clique para detalhar por cliente</small>' : ''
                        return `<b>${this.x}</b><br/>${this.y} empreendimento${this.y !== 1 ? 's' : ''}${hint}`
                    },
                },
                plotOptions: {
                    bar: {
                        cursor: vm.isTodasMode ? 'pointer' : 'default',
                        point: {
                            events: {
                                click: function () {
                                    if (!vm.isTodasMode) return
                                    vm.openBreakdown('empreendimentos', { dim: 'fase', value: this.category })
                                },
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            style: { color: '#FFFFFF', textOutline: 'none', fontSize: '11px' },
                        },
                    },
                },
                series: [{ name: 'Empreendimentos', data: values, showInLegend: false }],
            }
        },
    },
    mounted() {
        this.loadData()
    },
    methods: {
        toggleAll(type) {
            if (type === 'uf') {
                this.selectedUFs = this.selectedUFs.length === this.ufOptions.length
                    ? []
                    : [...this.ufOptions]
            } else {
                this.selectedFases = this.selectedFases.length === this.faseOptions.length
                    ? []
                    : [...this.faseOptions]
            }
        },
        loadData() {
            this.showPreLoader()
            this.axios({ method: 'get', url: '/web/dashboard' })
                .then(response => {
                    if (this.checkApiResponse(response)) {
                        this.projects = response.data.data
                        this.selectedUFs = [...new Set(this.projects.map(p => p.uf).filter(Boolean))]
                        this.selectedFases = [...new Set(this.projects.map(p => p.faseAtual).filter(Boolean))]
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader())
        },
        openBreakdown(type, filter) {
            this.breakdownType   = type
            this.breakdownFilter = filter
            this.showBreakdown   = true
        },
        formatBreakdownValue(value) {
            if (this.breakdownType === 'empreendimentos' || this.breakdownType === 'unidades') {
                return value.toLocaleString('pt-BR')
            }
            if (this.breakdownType === 'vgv') return this.formatVGV(value)
            if (this.breakdownType === 'area') return this.formatArea(value)
            return value
        },
        formatVGV(value) {
            if (!value) return 'R$ 0'
            if (value >= 1e9) return `R$ ${(value / 1e9).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Bi`
            if (value >= 1e6) return `R$ ${(value / 1e6).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} Mi`
            return `R$ ${value.toLocaleString('pt-BR')}`
        },
        formatArea(value) {
            if (!value) return '0 m²'
            return `${value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} m²`
        },
        formatNumber(value) {
            if (!value) return '0'
            return value.toLocaleString('pt-BR')
        },
    },
}
</script>

<style scoped>
.dashboard {
    display: flex;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    background: #124C60;
    color: #ffffff;
    font-family: Arial, sans-serif;
}

/* ---- Sidebar ---- */
.sidebar {
    width: 170px;
    min-width: 170px;
    background: #20556E;
    padding: 16px 12px;
    display: flex;
    flex-direction: column;
}

.sidebar-logo {
    text-align: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #3A7A95;
}
.client-name {
    font-size: 13px;
    font-weight: bold;
    color: #F0F9F8;
    line-height: 1.3;
    word-break: break-word;
}

.filter-section { margin-bottom: 6px; }
.filter-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}
.btn-toggle-all {
    background: transparent;
    border: 1px solid #4AACC5;
    color: #4AACC5;
    font-size: 9px;
    padding: 1px 6px;
    border-radius: 4px;
    cursor: pointer;
    line-height: 1.4;
}
.btn-toggle-all:hover {
    background: #4AACC5;
    color: #fff;
}
.filter-label {
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    color: #CCECF5;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.filter-item { margin-bottom: 3px; }
.filter-check {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 11px;
    color: #F0F9F8;
}
.filter-check input[type="checkbox"] {
    accent-color: #4AACC5;
    width: 12px;
    height: 12px;
    cursor: pointer;
}

/* ---- Main ---- */
.main-content {
    flex: 1;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

/* ---- KPI Cards ---- */
.kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
.kpi-card {
    background: #20556E;
    border-radius: 8px;
    padding: 12px 16px;
    text-align: center;
}
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
    margin-bottom: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.kpi-drill-icon {
    font-size: 10px;
    opacity: 0.7;
}
.kpi-value {
    font-size: 24px;
    font-weight: bold;
    color: #F0F9F8;
    line-height: 1.1;
}

/* ---- Charts ---- */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.chart-card {
    background: #20556E;
    border-radius: 8px;
    padding: 8px;
}
.chart-card :deep(.highcharts-container) {
    width: 100% !important;
}

/* ---- Breakdown Modal ---- */
.breakdown-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}
.breakdown-modal {
    background: #0E3E52;
    border: 1px solid #3A7A95;
    border-radius: 10px;
    width: 600px;
    max-width: 90vw;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
}
.breakdown-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #3A7A95;
}
.breakdown-header-content {
    flex: 1;
    text-align: center;
}
.breakdown-title {
    font-size: 15px;
    font-weight: bold;
    color: #F0F9F8;
}
.breakdown-subtitle {
    font-size: 22px;
    font-weight: bold;
    color: #FFFFFF;
    margin-top: 4px;
    letter-spacing: 1px;
}
.breakdown-close {
    background: transparent;
    border: none;
    color: #CCECF5;
    font-size: 16px;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
}
.breakdown-close:hover { color: #fff; }
.breakdown-body {
    padding: 16px 20px;
    overflow-y: auto;
}
.breakdown-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    color: #F0F9F8;
}
.breakdown-table th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #CCECF5;
    padding: 6px 8px;
    border-bottom: 1px solid #3A7A95;
}
.breakdown-table td {
    padding: 8px 8px;
    border-bottom: 1px solid #1E6A85;
}
.breakdown-table tbody tr:hover td {
    background: #20556E;
}
.breakdown-table tfoot td {
    border-top: 1px solid #4AACC5;
    border-bottom: none;
    padding-top: 10px;
    color: #F0F9F8;
}
.text-end { text-align: right; }
</style>
