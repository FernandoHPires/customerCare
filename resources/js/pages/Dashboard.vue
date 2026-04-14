<template>
    <div class="dashboard">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="/images/logo-uni.png" alt="UNI" class="logo-img" onerror="this.style.display='none'" />
                <div class="logo-text">UNI<br><small>Gestão de Negócios</small></div>
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
                <div class="kpi-card">
                    <div class="kpi-label">Empreendimentos</div>
                    <div class="kpi-value">{{ filteredData.length }}</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">VGV Total</div>
                    <div class="kpi-value">{{ formatVGV(totalVGV) }}</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Área Total</div>
                    <div class="kpi-value">{{ formatArea(totalArea) }}</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Unidades</div>
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

        // Chart 1: VGV por Região (Pie)
        vgvPorRegiaoChart() {
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
                        return `<b>${this.point.name}</b><br/>${fmt} (${this.percentage.toFixed(1)}%)`
                    },
                },
                plotOptions: {
                    pie: {
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

        // Chart 2: VGV por Ano de Lançamento (Column)
        vgvPorAnoChart() {
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
                        return `<b>${this.x}</b><br/>${fmt}`
                    },
                },
                plotOptions: {
                    column: {
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

        // Chart 3: Unidades por Região (Pie)
        unidadesPorRegiaoChart() {
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
                        return `<b>${this.point.name}</b><br/>${this.y.toLocaleString('pt-BR')} unidades (${this.percentage.toFixed(1)}%)`
                    },
                },
                plotOptions: {
                    pie: {
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

        // Chart 4: Empreendimentos por Etapa (Horizontal Bar)
        empreendimentosPorFaseChart() {
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
                        return `<b>${this.x}</b><br/>${this.y} empreendimento${this.y !== 1 ? 's' : ''}`
                    },
                },
                plotOptions: {
                    bar: {
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
.logo-img {
    max-width: 80px;
    margin-bottom: 4px;
}
.logo-text {
    font-size: 15px;
    font-weight: bold;
    color: #F0F9F8;
    line-height: 1.2;
}
.logo-text small {
    font-size: 9px;
    font-weight: normal;
    color: #CCECF5;
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
.kpi-label {
    font-size: 11px;
    color: #CCECF5;
    margin-bottom: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
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
</style>
