<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports">Reports</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Stage Pipeline
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Stage Pipeline - {{ selectedMonthYear }}</h5>
                
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Month and Year Selectors -->
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label mb-0">Month:</label>
                        <select v-model="selectedMonth" @change="loadMonthData" class="form-select form-select-sm" style="width: auto;">
                            <option v-for="month in availableMonths" :key="month.value" :value="month.value">{{ month.label }}</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label mb-0">Year:</label>
                        <select v-model="selectedYear" @change="loadMonthData" class="form-select form-select-sm" style="width: auto;">
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Comparison Section -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Compare Two Dates</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-end">
                        <div>
                            <label class="form-label">Date 1</label>
                            <input type="date" class="form-control" :class="{'is-invalid': dateValidationError}" v-model="compareDate1" @change="compareDates" style="width: 200px; max-width: 100%;">
                        </div>
                        <div>
                            <label class="form-label">Date 2</label>
                            <input type="date" class="form-control" :class="{'is-invalid': dateValidationError}" v-model="compareDate2" @change="compareDates" style="width: 200px; max-width: 100%;">
                        </div>
                    </div>
                    <div v-if="dateValidationError" class="text-danger mt-2" style="font-size: 0.875rem;">
                        {{ dateValidationError }}
                    </div>

                    <!-- Comparison Results -->
                    <div v-if="comparisonData" class="mt-4">
                        <div class="row comparison-row g-0">
                            <div class="col-md-4">
                                <h6>Date 1: {{ comparisonData.date1.date }}</h6>
                                <table class="table table-sm table-bordered compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: auto;">Stage</th>
                                            <th class="text-end" style="width: 80px;">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Initial Docs Sent</td>
                                            <td class="text-end">{{ comparisonData.date1.initial_docs_sent || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Initial Docs Received</td>
                                            <td class="text-end">{{ comparisonData.date1.initial_docs_received || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Signing</td>
                                            <td class="text-end">{{ comparisonData.date1.signing || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Funding</td>
                                            <td class="text-end">{{ comparisonData.date1.funding || 0 }}</td>
                                        </tr>
                                        <tr class="table-info">
                                            <td><strong>Total</strong></td>
                                            <td class="text-end"><strong>{{ comparisonData.date1.total || 0 }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <h6>Date 2: {{ comparisonData.date2.date }}</h6>
                                <table class="table table-sm table-bordered compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: auto;">Stage</th>
                                            <th class="text-end" style="width: 80px;">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Initial Docs Sent</td>
                                            <td class="text-end">{{ comparisonData.date2.initial_docs_sent || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Initial Docs Received</td>
                                            <td class="text-end">{{ comparisonData.date2.initial_docs_received || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Signing</td>
                                            <td class="text-end">{{ comparisonData.date2.signing || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Funding</td>
                                            <td class="text-end">{{ comparisonData.date2.funding || 0 }}</td>
                                        </tr>
                                        <tr class="table-info">
                                            <td><strong>Total</strong></td>
                                            <td class="text-end"><strong>{{ comparisonData.date2.total || 0 }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <h6>Changes (Date 2 vs Date 1):</h6>
                                <table class="table table-sm table-bordered compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: auto;">Stage</th>
                                        <th class="text-end" style="width: 80px;">Added</th>
                                        <th class="text-end" style="width: 80px;">Moved</th>
                                        <th class="text-end" style="width: 80px;">Closed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Initial Docs Sent</td>
                                        <td class="text-end">
                                            <span 
                                                v-if="comparisonData.drill_down && comparisonData.drill_down.added.by_stage.initial_docs_sent > 0"
                                                @click="showDrillDown('initial_docs_sent')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ comparisonData.drill_down.added.by_stage.initial_docs_sent || 0 }}
                                            </span>
                                            <span v-else>
                                                {{ comparisonData.drill_down ? (comparisonData.drill_down.added.by_stage.initial_docs_sent || 0) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getMovedCountByStage('initial_docs_sent') > 0"
                                                @click="showDrillDown('initial_docs_sent')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getMovedCountByStage('initial_docs_sent') }}
                                            </span>
                                            <span v-else>
                                                {{ getMovedCountByStage('initial_docs_sent') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getClosedCountByStage('initial_docs_sent') > 0"
                                                @click="showDrillDown('closed', 'initial_docs_sent')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getClosedCountByStage('initial_docs_sent') }}
                                            </span>
                                            <span v-else>
                                                {{ getClosedCountByStage('initial_docs_sent') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Initial Docs Received</td>
                                        <td class="text-end">
                                            <span 
                                                v-if="comparisonData.drill_down && comparisonData.drill_down.added.by_stage.initial_docs_received > 0"
                                                @click="showDrillDown('initial_docs_received')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ comparisonData.drill_down.added.by_stage.initial_docs_received || 0 }}
                                            </span>
                                            <span v-else>
                                                {{ comparisonData.drill_down ? (comparisonData.drill_down.added.by_stage.initial_docs_received || 0) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getMovedCountByStage('initial_docs_received') > 0"
                                                @click="showDrillDown('initial_docs_received')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getMovedCountByStage('initial_docs_received') }}
                                            </span>
                                            <span v-else>
                                                {{ getMovedCountByStage('initial_docs_received') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getClosedCountByStage('initial_docs_received') > 0"
                                                @click="showDrillDown('closed', 'initial_docs_received')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getClosedCountByStage('initial_docs_received') }}
                                            </span>
                                            <span v-else>
                                                {{ getClosedCountByStage('initial_docs_received') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Signing</td>
                                        <td class="text-end">
                                            <span 
                                                v-if="comparisonData.drill_down && comparisonData.drill_down.added.by_stage.signing > 0"
                                                @click="showDrillDown('signing')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ comparisonData.drill_down.added.by_stage.signing || 0 }}
                                            </span>
                                            <span v-else>
                                                {{ comparisonData.drill_down ? (comparisonData.drill_down.added.by_stage.signing || 0) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getMovedCountByStage('signing') > 0"
                                                @click="showDrillDown('signing')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getMovedCountByStage('signing') }}
                                            </span>
                                            <span v-else>
                                                {{ getMovedCountByStage('signing') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getClosedCountByStage('signing') > 0"
                                                @click="showDrillDown('closed', 'signing')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getClosedCountByStage('signing') }}
                                            </span>
                                            <span v-else>
                                                {{ getClosedCountByStage('signing') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Funding</td>
                                        <td class="text-end">
                                            <span 
                                                v-if="comparisonData.drill_down && comparisonData.drill_down.added.by_stage.funding > 0"
                                                @click="showDrillDown('funding')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ comparisonData.drill_down.added.by_stage.funding || 0 }}
                                            </span>
                                            <span v-else>
                                                {{ comparisonData.drill_down ? (comparisonData.drill_down.added.by_stage.funding || 0) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getMovedCountByStage('funding') > 0"
                                                @click="showDrillDown('funding')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getMovedCountByStage('funding') }}
                                            </span>
                                            <span v-else>
                                                {{ getMovedCountByStage('funding') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span 
                                                v-if="getClosedCountByStage('funding') > 0"
                                                @click="showDrillDown('closed', 'funding')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getClosedCountByStage('funding') }}
                                            </span>
                                            <span v-else>
                                                {{ getClosedCountByStage('funding') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Total</strong></td>
                                        <td class="text-end">
                                            <strong>{{ comparisonData.drill_down ? (comparisonData.drill_down.added.count || 0) : 0 }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ getTotalMovedCount() }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong 
                                                v-if="getTotalClosedCount() > 0"
                                                @click="showDrillDown('closed')"
                                                class="text-decoration-underline"
                                                style="cursor: pointer;"
                                                :title="'Click to see details'"
                                            >
                                                {{ getTotalClosedCount() }}
                                            </strong>
                                            <strong v-else>{{ getTotalClosedCount() }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drill Down Modal -->
            <div v-if="showDrillDownModal" class="modal fade show" style="display: block;" tabindex="-1" @click.self="closeDrillDown">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Drill Down: {{ drillDownStageName }}</h5>
                            <button type="button" class="btn-close" @click="closeDrillDown"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Closed Records View -->
                            <div v-if="drillDownStage === 'closed' && drillDownData && drillDownData.closed" class="row">
                                <div class="col-12">
                                    <h6>
                                        Closed ({{ drillDownData.closed.count }} records)
                                    </h6>
                                    <div v-if="drillDownData.closed.records.length > 0" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered compact-table">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 120px;">Application ID</th>
                                                    <th style="width: auto;">Close Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(record, index) in drillDownData.closed.records" :key="'closed-' + index">
                                                    <td>{{ record.application_id || 'N/A' }}</td>
                                                    <td>{{ record.close_reason || 'N/A' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-else class="text-muted">No closed records</p>
                                </div>
                            </div>
                            
                            <!-- Regular Stage View (Added/Removed) -->
                            <div v-else-if="drillDownData" class="row">
                                <!-- Added Records -->
                                <div class="col-md-6">
                                    <h6>
                                        Added ({{ drillDownData.added.count }} records)
                                        <span class="text-muted ms-2" style="font-size: 0.85em;">compare with {{ comparisonData.date1.date }}</span>
                                    </h6>
                                    <div v-if="drillDownData.added.records.length > 0" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered compact-table">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 120px;">Application ID</th>
                                                    <th style="width: 120px;">Mortgage ID</th>
                                                    <th style="width: 120px;">Saved Quote ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(record, index) in drillDownData.added.records" :key="'added-' + index">
                                                    <td>{{ record.application_id || 'N/A' }}</td>
                                                    <td>{{ record.mortgage_id }}</td>
                                                    <td>{{ record.saved_quote_id || 'N/A' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-else class="text-muted">No records added</p>
                                </div>
                                
                                <!-- Removed Records -->
                                <div class="col-md-6">
                                    <h6>
                                        Moved ({{ drillDownData.removed.count }} records)
                                        <span class="text-muted ms-2" style="font-size: 0.85em;">compare with {{ comparisonData.date2.date }}</span>
                                    </h6>
                                    <div v-if="drillDownData.removed.records.length > 0" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered compact-table">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 100px;">Application ID</th>
                                                    <th style="width: 100px;">Mortgage ID</th>
                                                    <th style="width: 100px;">Saved Quote ID</th>
                                                    <th style="width: auto;">Current Stage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(record, index) in drillDownData.removed.records" :key="'removed-' + index">
                                                    <td>{{ record.application_id || 'N/A' }}</td>
                                                    <td>{{ record.mortgage_id }}</td>
                                                    <td>{{ record.saved_quote_id || 'N/A' }}</td>
                                                    <td>{{ record.current_stage || 'N/A' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-else class="text-muted">No records moved</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeDrillDown">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showDrillDownModal" class="modal-backdrop fade show"></div>

            <!-- Monthly Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Monthly Pipeline Data - {{ selectedMonthYear }}</h6>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    
                    <div v-else-if="monthlyData.length === 0" class="text-center py-4 text-muted">
                        <p>No data available for {{ selectedMonthYear }}</p>
                    </div>
                    
                    <div v-else class="table-responsive">
                        <table class="table table-bordered table-hover table-sm compact-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 120px;">Date</th>
                                    <th class="text-end" style="width: 100px;">Initial Docs Sent</th>
                                    <th class="text-end" style="width: 100px;">Initial Docs Received</th>
                                    <th class="text-end" style="width: 80px;">Signing</th>
                                    <th class="text-end" style="width: 80px;">Funding</th>
                                    <th class="text-end" style="width: 80px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="day in monthlyData" :key="day.date" 
                                    :class="{'table-warning': day.isToday, 'table-info': day.isSelected}">
                                    <td>
                                        <strong>{{ formatDate(day.date) }}</strong>
                                        <span v-if="day.isToday" class="badge bg-primary ms-2">Today</span>
                                    </td>
                                    <td class="text-end">{{ day.initial_docs_sent || 0 }}</td>
                                    <td class="text-end">{{ day.initial_docs_received || 0 }}</td>
                                    <td class="text-end">{{ day.signing || 0 }}</td>
                                    <td class="text-end">{{ day.funding || 0 }}</td>
                                    <td class="text-end"><strong>{{ day.total || 0 }}</strong></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-info">
                                <tr>
                                    <td><strong>Monthly Total</strong></td>
                                    <td class="text-end"><strong>{{ monthlyTotals.initial_docs_sent }}</strong></td>
                                    <td class="text-end"><strong>{{ monthlyTotals.initial_docs_received }}</strong></td>
                                    <td class="text-end"><strong>{{ monthlyTotals.signing }}</strong></td>
                                    <td class="text-end"><strong>{{ monthlyTotals.funding }}</strong></td>
                                    <td class="text-end"><strong>{{ monthlyTotals.total }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],
    data() {
        const today = new Date()
        const currentMonth = String(today.getMonth() + 1).padStart(2, '0')
        const currentYear = today.getFullYear()
        
        return {
            selectedMonth: currentMonth,
            selectedYear: currentYear,
            monthlyData: [],
            loading: false,
            compareDate1: '',
            compareDate2: '',
            comparisonData: null,
            dateValidationError: '',
            showDrillDownModal: false,
            drillDownStage: null,
            drillDownData: null,
            availableMonths: [
                { value: '01', label: 'January' },
                { value: '02', label: 'February' },
                { value: '03', label: 'March' },
                { value: '04', label: 'April' },
                { value: '05', label: 'May' },
                { value: '06', label: 'June' },
                { value: '07', label: 'July' },
                { value: '08', label: 'August' },
                { value: '09', label: 'September' },
                { value: '10', label: 'October' },
                { value: '11', label: 'November' },
                { value: '12', label: 'December' }
            ],
            availableYears: []
        }
    },
    computed: {
        selectedMonthYear() {
            const monthName = this.availableMonths.find(m => m.value === this.selectedMonth)?.label || ''
            return `${monthName} ${this.selectedYear}`
        },
        monthlyTotals() {
            return this.monthlyData.reduce((totals, day) => {
                totals.initial_docs_sent += day.initial_docs_sent || 0
                totals.initial_docs_received += day.initial_docs_received || 0
                totals.signing += day.signing || 0
                totals.funding += day.funding || 0
                totals.total += day.total || 0
                return totals
            }, { initial_docs_sent: 0, initial_docs_received: 0, signing: 0, funding: 0, total: 0 })
        },
        drillDownStageName() {
            if (!this.drillDownStage) return ''
            const stageNames = {
                'initial_docs_sent': 'Initial Docs Sent',
                'initial_docs_received': 'Initial Docs Received',
                'signing': 'Signing',
                'funding': 'Funding',
                'closed': 'Closed'
            }
            return stageNames[this.drillDownStage] || this.drillDownStage
        }
    },
    mounted() {
        // Generate available years (current year ± 2 years)
        const currentYear = new Date().getFullYear()
        for (let i = currentYear - 2; i <= currentYear + 2; i++) {
            this.availableYears.push(i)
        }
        
        this.loadMonthData()
    },
    methods: {
        loadMonthData() {
            this.loading = true

            this.axios.get('/web/reports/stage-pipeline/counts', {
                params: {
                    month: this.selectedMonth,
                    year: this.selectedYear,
                }
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.monthlyData = response.data.data || []

                    // const today = this.getTodayLocalDate()
                    this.monthlyData.forEach(day => {
                        // day.isToday = (day.date === today)
                        day.isSelected = (day.date === this.compareDate1 || day.date === this.compareDate2)
                    })
                }
            })
            .catch(error => {
                console.error('Error loading monthly data:', error)
                this.monthlyData = []
            })
            .finally(() => {
                this.loading = false
            })
        },

        compareDates() {
            // Clear previous validation error
            this.dateValidationError = ''
            
            if (!this.compareDate1 || !this.compareDate2) {
                this.comparisonData = null
                return
            }
            
            // Validate that date 2 is later than date 1
            if (this.compareDate2 <= this.compareDate1) {
                this.dateValidationError = 'Date 2 must be later than Date 1'
                this.comparisonData = null
                return
            }
            
            this.loading = true
            
            this.axios.get('/web/reports/stage-pipeline/compare', {
                params: {
                    date1: this.compareDate1,
                    date2: this.compareDate2
                }
            })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    this.comparisonData = response.data.data
                    // Refresh monthly data to highlight selected dates
                    this.loadMonthData()
                }
            })
            .catch(error => {
                console.error('Error comparing dates:', error)
                this.comparisonData = null
            })
            .finally(() => {
                this.loading = false
            })
        },
        
        getDaysInMonth(year, month) {
            return new Date(year, month, 0).getDate()
        },
        
        getTodayLocalDate() {
            // Get today's date in local timezone (not UTC) in YYYY-MM-DD format
            const today = new Date()
            const year = today.getFullYear()
            const month = String(today.getMonth() + 1).padStart(2, '0')
            const day = String(today.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        },
        
        formatDate(dateString) {
            // Parse date string directly to avoid timezone conversion issues
            // dateString is in YYYY-MM-DD format
            const parts = dateString.split('-')
            const year = parseInt(parts[0])
            const month = parseInt(parts[1]) - 1 // JavaScript months are 0-indexed
            const day = parseInt(parts[2])
            
            // Create date in local timezone to avoid UTC conversion
            const date = new Date(year, month, day)
            const dayName = date.toLocaleDateString('en-US', { weekday: 'short' })
            return `${dayName} ${day}`
        },
        
        formatDifference(value) {
            if (value > 0) {
                return `+${value}`
            }
            return value.toString()
        },
        
        getDifferenceClass(value) {
            if (value > 0) {
                return 'text-success'
            } else if (value < 0) {
                return 'text-danger'
            }
            return ''
        },
        
        showDrillDown(stage, closedForStage = null) {
            if (!this.comparisonData || !this.comparisonData.drill_down) {
                return
            }
            
            // Handle "closed" as a special case
            if (stage === 'closed') {
                this.drillDownStage = 'closed'
                let closedRecords = this.comparisonData.drill_down.removed.records.filter(r => r.current_status_id === 18)
                
                // If closedForStage is provided, filter by that stage
                if (closedForStage) {
                    closedRecords = closedRecords.filter(r => {
                        const recordStage = this.getStageForStatusId(parseInt(r.status_id))
                        return recordStage === closedForStage
                    })
                }
                
                this.drillDownData = {
                    closed: {
                        records: closedRecords,
                        count: closedForStage ? this.getClosedCountByStage(closedForStage) : this.getTotalClosedCount()
                    }
                }
                this.showDrillDownModal = true
                return
            }
            
            this.drillDownStage = stage
            this.drillDownData = {
                added: {
                    records: this.comparisonData.drill_down.added.records.filter(r => {
                        const recordStage = this.getStageForStatusId(parseInt(r.status_id))
                        return recordStage === stage
                    }),
                    count: this.comparisonData.drill_down.added.by_stage[stage] || 0
                },
                removed: {
                    records: this.comparisonData.drill_down.removed.records.filter(r => {
                        const recordStage = this.getStageForStatusId(parseInt(r.status_id))
                        return recordStage === stage && r.current_status_id !== 18
                    }),
                    count: this.getMovedCountByStage(stage)
                }
            }
            this.showDrillDownModal = true
        },
        
        closeDrillDown() {
            this.showDrillDownModal = false
            this.drillDownStage = null
            this.drillDownData = null
        },
        
        getStageForStatusId(statusId) {
            if (statusId == 8) {
                return 'initial_docs_sent'
            } else if (statusId == 17) {
                return 'initial_docs_received'
            } else if ([10, 14].includes(statusId)) {
                return 'signing'
            } else if (statusId == 13) {
                return 'funding'
            }
            return null
        },
        
        getClosedCountByStage(stage) {
            if (!this.comparisonData || !this.comparisonData.drill_down) {
                return 0
            }
            return this.comparisonData.drill_down.removed.records.filter(r => {
                const recordStage = this.getStageForStatusId(parseInt(r.status_id))
                return recordStage === stage && r.current_status_id === 18
            }).length
        },
        
        getMovedCountByStage(stage) {
            if (!this.comparisonData || !this.comparisonData.drill_down) {
                return 0
            }
            const totalRemoved = this.comparisonData.drill_down.removed.by_stage[stage] || 0
            const closedCount = this.getClosedCountByStage(stage)
            return totalRemoved - closedCount
        },
        
        getTotalClosedCount() {
            if (!this.comparisonData || !this.comparisonData.drill_down) {
                return 0
            }
            return this.comparisonData.drill_down.removed.records.filter(r => r.current_status_id === 18).length
        },
        
        getTotalMovedCount() {
            if (!this.comparisonData || !this.comparisonData.drill_down) {
                return 0
            }
            const totalRemoved = this.comparisonData.drill_down.removed.count || 0
            const closedCount = this.getTotalClosedCount()
            return totalRemoved - closedCount
        }
    }
}
</script>

<style scoped>
.comparison-row {
    --bs-gutter-x: 0.5rem;
}

.comparison-row > [class*="col-"] {
    padding-right: 0.25rem;
    padding-left: 0.25rem;
}

.comparison-row {
    margin-bottom: 0;
}

.table-responsive {
    max-height: 600px;
    overflow-y: auto;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.compact-table {
    table-layout: auto;
    width: auto;
}

.compact-table th,
.compact-table td {
    white-space: nowrap;
    padding: 0.5rem;
}

.compact-table th:first-child,
.compact-table td:first-child {
    white-space: normal;
}
</style>
