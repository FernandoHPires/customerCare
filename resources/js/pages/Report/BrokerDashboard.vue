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
                Broker Dashboard
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Broker Dashboard - {{ selectedMonthYear }}</h5>
            <div class="d-flex gap-2 align-items-center">
                <div class="d-flex align-items-center me-3">
                    <label class="form-label me-2 mb-0">Broker Group:</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="brokerGroup" id="all" value="all" v-model="selectedBrokerGroup" @change="filterBrokers">
                        <label class="btn btn-outline-primary btn-sm" for="all">DB</label>
                        
                        <input type="radio" class="btn-check" name="brokerGroup" id="pb" value="PB" v-model="selectedBrokerGroup" @change="filterBrokers">
                        <label class="btn btn-outline-primary btn-sm" for="pb">PB</label>
                        
                        <input type="radio" class="btn-check" name="brokerGroup" id="nb" value="NB" v-model="selectedBrokerGroup" @change="filterBrokers">
                        <label class="btn btn-outline-primary btn-sm" for="nb">NB</label>
                        
                        <input type="radio" class="btn-check" name="brokerGroup" id="lt" value="LT" v-model="selectedBrokerGroup" @change="filterBrokers">
                        <label class="btn btn-outline-primary btn-sm" for="lt">LT</label>
                    </div>
                </div>
                <div class="d-flex align-items-center me-3">
                    <label class="form-label me-2 mb-0">Province:</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="province" id="allProvince" value="" v-model="selectedProvince" @change="fetchData">
                        <label class="btn btn-outline-primary btn-sm" for="allProvince">All</label>
                        
                        <input type="radio" class="btn-check" name="province" id="ab" value="AB" v-model="selectedProvince" @change="fetchData">
                        <label class="btn btn-outline-primary btn-sm" for="ab">AB</label>
                        
                        <input type="radio" class="btn-check" name="province" id="bc" value="BC" v-model="selectedProvince" @change="fetchData">
                        <label class="btn btn-outline-primary btn-sm" for="bc">BC</label>
                        
                        <input type="radio" class="btn-check" name="province" id="on" value="ON" v-model="selectedProvince" @change="fetchData">
                        <label class="btn btn-outline-primary btn-sm" for="on">ON</label>
                    </div>
                </div>
                <select v-model="selectedMonth" @change="fetchData" class="form-select form-select-sm" style="width: auto;">
                    <option v-for="month in availableMonths" :key="month.value" :value="month.value">{{ month.label }}</option>
                </select>
                <select v-model="selectedYear" @change="fetchData" class="form-select form-select-sm" style="width: auto;">
                    <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>
        </div>

        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Funded Files</h6>
                            <h3 class="mt-auto">{{ formatCurrency(totalFundedFiles) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Total Gross Mortgages</h6>
                            <h3 class="mt-auto">${{ formatCurrency(totalGrossAmount) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Total Gross Fees</h6>
                            <h3 class="mt-auto">${{ formatCurrency(totalGrossFees) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Average Gross Fee %</h6>
                            <h3 class="mt-auto">{{ formatPercentage(averageGrossFeePercentage) }}%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Summary Cards for NB and LT -->
            <div v-if="selectedBrokerGroup === 'NB' || selectedBrokerGroup === 'LT'" class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Sales Journey</h6>
                            <h3 class="mt-auto">{{ formatCurrency(totalSalesJourney) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-white text-black h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">Conversion %</h6>
                            <h3 class="mt-auto">{{ formatPercentage(averageConversionRate) }}%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pipeline Forecast Section - Only show when "All" is selected -->
            <div v-if="selectedBrokerGroup === 'all'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Pipeline Forecast</h6>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>File Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Initial Docs</strong></td>
                                <td>{{ pipelineForecast.initialDocs.count }}</td>
                                <td>${{ formatCurrency(pipelineForecast.initialDocs.grossAmount) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Signing</strong></td>
                                <td>{{ pipelineForecast.signing.count }}</td>
                                <td>${{ formatCurrency(pipelineForecast.signing.grossAmount) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Funding</strong></td>
                                <td>{{ pipelineForecast.funding.count }}</td>
                                <td>${{ formatCurrency(pipelineForecast.funding.grossAmount) }}</td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Total</strong></td>
                                <td><strong>{{ pipelineForecast.total.count }}</strong></td>
                                <td><strong>${{ formatCurrency(pipelineForecast.total.grossAmount) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Broker Performance Table -->
            <div v-if="selectedBrokerGroup !== 'all'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Broker Performance - {{ selectedMonthYear }}</h6>
                </div>

                <div class="card-body">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Broker Group</th>
                                <th>Broker Name</th>
                                <th v-if="selectedBrokerGroup === 'NB' || selectedBrokerGroup === 'LT'">Sales Journey</th>
                                <th>Funded Files</th>
                                <th v-if="selectedBrokerGroup === 'NB' || selectedBrokerGroup === 'LT'">Conversion</th>
                                <th>Total Gross Amount</th>
                                <!-- <th>Total Discount Fee</th> -->
                                <th>Gross Fee</th>
                                <th>Gross Fee Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="broker in filteredBrokers" :key="broker.user_fname">
                                <td>{{ broker.broker_group }}</td>
                                <td>
                                    <a href="#" @click.prevent="viewBrokerDetails(broker.user_fname)" class="text-decoration-none">
                                        {{ broker.user_fname }}
                                    </a>
                                </td>
                                <td v-if="selectedBrokerGroup === 'NB' || selectedBrokerGroup === 'LT'">{{ broker.sales_journey }}</td>
                                <td>{{ broker.funded_file }}</td>
                                <td v-if="selectedBrokerGroup === 'NB' || selectedBrokerGroup === 'LT'">{{ Math.round(broker.conversion * 100) }}%</td>
                                <td>${{ formatCurrency(broker.total_gross_amt) }}</td>
                                <!-- <td>${{ formatCurrency(broker.total_discount_fee) }}</td> -->
                                <td>${{ formatCurrency(broker.gross_fee) }}</td>
                                <td>
                                    {{ formatPercentage(broker.gross_fee_percentage) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PB Breakdown Table - Only show when PB is selected -->
            <div v-if="selectedBrokerGroup === 'PB'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">PB Breakdown</h6>
                </div>
                <div class="card-body">
                    <!-- Section 1: Detailed Breakdown by Loan Size -->
                    <div class="mb-4">
                        <h6 class="mb-3">Breakdown by Loan Size</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pbBreakdownData.detailedBreakdown" :key="item.category" :class="item.category === 'All Files' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'All Files'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 2: Summary Breakdown -->
                    <div>
                        <h6 class="mb-3">Summary Breakdown</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pbBreakdownData.summaryBreakdown" :key="item.category" :class="item.category === 'Total' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'Total'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- NB Breakdown Table - Only show when NB is selected -->
            <div v-if="selectedBrokerGroup === 'NB'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">NB Breakdown</h6>
                </div>
                <div class="card-body">
                    <!-- Section 1: Detailed Breakdown by Loan Size -->
                    <div class="mb-4">
                        <h6 class="mb-3">Breakdown by Loan Size</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in nbBreakdownData.detailedBreakdown" :key="item.category" :class="item.category === 'All Files' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'All Files'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 2: Summary Breakdown -->
                    <div>
                        <h6 class="mb-3">Summary Breakdown</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in nbBreakdownData.summaryBreakdown" :key="item.category" :class="item.category === 'Total' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'Total'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- LT Breakdown Table - Only show when LT is selected -->
            <div v-if="selectedBrokerGroup === 'LT'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">LT Breakdown</h6>
                </div>
                <div class="card-body">
                    <!-- Section 1: Detailed Breakdown by Loan Size -->
                    <div class="mb-4">
                        <h6 class="mb-3">Breakdown by Loan Size</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in ltBreakdownData.detailedBreakdown" :key="item.category" :class="item.category === 'All Files' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'All Files'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'All Files'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'All Files'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 2: Summary Breakdown -->
                    <div>
                        <h6 class="mb-3">Summary Breakdown</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in ltBreakdownData.summaryBreakdown" :key="item.category" :class="item.category === 'Total' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'Total'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- All Breakdown Table - Only show when All is selected -->
            <div v-if="selectedBrokerGroup === 'all'" class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">All Brokers Breakdown</h6>
                </div>
                <div class="card-body">
                    <!-- Summary Breakdown -->
                    <div>
                        <h6 class="mb-3">Summary Breakdown</h6>
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Files</th>
                                    <th>Ratio</th>
                                    <th>Volume</th>
                                    <th>Ratio</th>
                                    <th>Ave Size</th>
                                    <th>Total Fees</th>
                                    <th>Ratio</th>
                                    <th>Fee %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in allBreakdownData.summaryBreakdown" :key="item.category" :class="item.category === 'Total' ? 'table-info' : ''">
                                    <td><strong v-if="item.category === 'Total'">{{ item.category }}</strong><span v-else>{{ item.category }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.files }}</strong><span v-else>{{ item.files }}</span></td>
                                    <td>{{ item.files_ratio !== null ? item.files_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.volume) }}</strong><span v-else>${{ formatCurrency(item.volume) }}</span></td>
                                    <td>{{ item.volume_ratio !== null ? item.volume_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.ave_size) }}</strong><span v-else>${{ formatCurrency(item.ave_size) }}</span></td>
                                    <td><strong v-if="item.category === 'Total'">${{ formatCurrency(item.total_fees) }}</strong><span v-else>${{ formatCurrency(item.total_fees) }}</span></td>
                                    <td>{{ item.fees_ratio !== null ? item.fees_ratio + '%' : '-' }}</td>
                                    <td><strong v-if="item.category === 'Total'">{{ item.fee_percentage }}%</strong><span v-else>{{ item.fee_percentage }}%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { util } from '../../mixins/util'

export default {
    name: 'BrokerDashboard',
    setup() {
        const router = useRouter()
        const brokers = ref([])
        const allBreakdownData = ref({ detailedBreakdown: [], summaryBreakdown: [] })
        const pbBreakdownData = ref({ detailedBreakdown: [], summaryBreakdown: [] })
        const nbBreakdownData = ref({ detailedBreakdown: [], summaryBreakdown: [] })
        const ltBreakdownData = ref({ detailedBreakdown: [], summaryBreakdown: [] })
        const pipelineForecast = ref({
            initialDocs: { count: 0, grossAmount: 0 },
            signing: { count: 0, grossAmount: 0 },
            funding: { count: 0, grossAmount: 0 },
            total: { count: 0, grossAmount: 0 }
        })
        const loading = ref(false)
        const selectedMonth = ref((new Date()).getMonth() + 1)
        const selectedYear = ref((new Date()).getYear() + 1900)
        const selectedBrokerGroup = ref('all')
        const selectedProvince = ref('')

        const filteredBrokers = computed(() => {
            if (selectedBrokerGroup.value === 'all') {
                return brokers.value
            }
            return brokers.value.filter(broker => broker.broker_group === selectedBrokerGroup.value)
        })

        const totalFundedFiles = computed(() => {
            return filteredBrokers.value.reduce((sum, broker) => sum + parseInt(broker.funded_file || 0), 0)
        })

        const totalGrossAmount = computed(() => {
            return filteredBrokers.value.reduce((sum, broker) => sum + parseFloat(broker.total_gross_amt || 0), 0)
        })

        const totalGrossFees = computed(() => {
            return filteredBrokers.value.reduce((sum, broker) => sum + parseFloat(broker.gross_fee || 0), 0)
        })

        // const totalDiscountFees = computed(() => {
        //     return brokers.value.reduce((sum, broker) => sum + parseFloat(broker.total_discount_fee || 0), 0)
        // })

        const averageGrossFeePercentage = computed(() => {
            if (filteredBrokers.value.length === 0) return 0
            const totalFeePercentage = filteredBrokers.value.reduce((sum, broker) => sum + parseFloat(broker.gross_fee_percentage || 0), 0)
            return totalFeePercentage / filteredBrokers.value.length
        })

        const totalSalesJourney = computed(() => {
            return filteredBrokers.value.reduce((sum, broker) => sum + parseInt(broker.sales_journey || 0), 0)
        })

        const averageConversionRate = computed(() => {
            if (totalSalesJourney.value === 0) return 0
            return totalFundedFiles.value / totalSalesJourney.value
        })

        const selectedMonthYear = computed(() => {
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
            const monthIndex = parseInt(selectedMonth.value) - 1
            return `${monthNames[monthIndex]} ${selectedYear.value}`
        })

        const availableYears = computed(() => {
            const currentYear = new Date().getFullYear()
            const years = []
            // Start from 2025 and go up to current year + 2
            for (let year = 2025; year <= currentYear + 2; year++) {
                years.push(year)
            }
            return years
        })

        const availableMonths = computed(() => {
            const allMonths = [
                { value: '1', label: 'January' },
                { value: '2', label: 'February' },
                { value: '3', label: 'March' },
                { value: '4', label: 'April' },
                { value: '5', label: 'May' },
                { value: '6', label: 'June' },
                { value: '7', label: 'July' },
                { value: '8', label: 'August' },
                { value: '9', label: 'September' },
                { value: '10', label: 'October' },
                { value: '11', label: 'November' },
                { value: '12', label: 'December' }
            ]
            
            // If selected year is 2025, only show June onwards
            if (selectedYear.value === '2025') {
                return allMonths.filter(month => parseInt(month.value) >= 6)
            }
            
            // For other years, show all months
            return allMonths
        })

        const fetchData = async () => {
            loading.value = true
            try {
                const response = await axios.get('/web/reports/broker-dashboard', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    brokers.value = response.data.data.brokers || []
                }
                
                // Fetch pipeline forecast data
                fetchPipelineForecast()
                
                // Also fetch breakdown data for the currently selected broker group
                if (selectedBrokerGroup.value === 'all') {
                    fetchAllBreakdown()
                } else if (selectedBrokerGroup.value === 'PB') {
                    fetchPBBreakdown()
                } else if (selectedBrokerGroup.value === 'NB') {
                    fetchNBBreakdown()
                } else if (selectedBrokerGroup.value === 'LT') {
                    fetchLTBreakdown()
                }
            } catch (error) {
                console.error('Error fetching broker dashboard data:', error)
            } finally {
                loading.value = false
            }
        }

        const fetchPBBreakdown = async () => {
            try {
                const response = await axios.get('/web/reports/pb-breakdown', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    pbBreakdownData.value = {
                        detailedBreakdown: response.data.data.detailedBreakdown || [],
                        summaryBreakdown: response.data.data.summaryBreakdown || []
                    }
                }
            } catch (error) {
                console.error('Error fetching PB breakdown data:', error)
            }
        }

        const fetchNBBreakdown = async () => {
            try {
                const response = await axios.get('/web/reports/nb-breakdown', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    nbBreakdownData.value = {
                        detailedBreakdown: response.data.data.detailedBreakdown || [],
                        summaryBreakdown: response.data.data.summaryBreakdown || []
                    }
                }
            } catch (error) {
                console.error('Error fetching NB breakdown data:', error)
            }
        }

        const fetchLTBreakdown = async () => {
            try {
                const response = await axios.get('/web/reports/lt-breakdown', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    ltBreakdownData.value = {
                        detailedBreakdown: response.data.data.detailedBreakdown || [],
                        summaryBreakdown: response.data.data.summaryBreakdown || []
                    }
                }
            } catch (error) {
                console.error('Error fetching LT breakdown data:', error)
            }
        }

        const fetchAllBreakdown = async () => {
            try {
                const response = await axios.get('/web/reports/all-breakdown', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    allBreakdownData.value = {
                        detailedBreakdown: response.data.data.detailedBreakdown || [],
                        summaryBreakdown: response.data.data.summaryBreakdown || []
                    }
                }
            } catch (error) {
                console.error('Error fetching All breakdown data:', error)
            }
        }

        const fetchPipelineForecast = async () => {
            try {
                const response = await axios.get('/web/reports/pipeline-forecast', {
                    params: {
                        month: selectedMonth.value,
                        year: selectedYear.value,
                        province: selectedProvince.value
                    }
                })
                if (response.data.status === 'success') {
                    pipelineForecast.value = {
                        initialDocs: response.data.data.initialDocs || { count: 0, grossAmount: 0 },
                        signing: response.data.data.signing || { count: 0, grossAmount: 0 },
                        funding: response.data.data.funding || { count: 0, grossAmount: 0 },
                        total: response.data.data.total || { count: 0, grossAmount: 0 }
                    }
                }
            } catch (error) {
                console.error('Error fetching pipeline forecast data:', error)
            }
        }

        const formatCurrency = (value) => {
            if (!value) return '0'
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value)
        }

        const formatPercentage = (value) => {
            if (!value) return '0'
            return (parseFloat(value) * 100).toFixed(1)
        }

        const getFeePercentageClass = (percentage) => {
            const rate = parseFloat(percentage)
            if (rate >= 5) return 'bg-success'
            if (rate >= 3) return 'bg-warning'
            return 'bg-danger'
        }

        const getBrokerGroupBadgeClass = (group) => {
            switch (group) {
                case 'PB':
                    return 'bg-primary'
                case 'LT':
                    return 'bg-success'
                case 'NB':
                    return 'bg-secondary'
                default:
                    return 'bg-secondary'
            }
        }

        const filterBrokers = () => {
            // This function is called when the broker group filter changes
            // The filtering is handled by the computed property filteredBrokers
            // All summary cards will automatically update due to reactivity
            
            // Fetch breakdown data when specific groups are selected
            if (selectedBrokerGroup.value === 'PB') {
                fetchPBBreakdown()
            } else if (selectedBrokerGroup.value === 'NB') {
                fetchNBBreakdown()
            } else if (selectedBrokerGroup.value === 'LT') {
                fetchLTBreakdown()
            } else if (selectedBrokerGroup.value === 'all') {
                fetchAllBreakdown()
            }
        }

        const viewBrokerDetails = (brokerName) => {
            // Find the broker's user_id from the brokers array
            const broker = brokers.value.find(b => b.user_fname === brokerName)
            if (broker) {
                router.push({
                    name: 'BrokerDetails',
                    params: { userId: broker.user_id },
                    query: { 
                        month: selectedMonth.value,
                        year: selectedYear.value
                    }
                })
            }
        }

        onMounted(() => {
            fetchData()
        })

        // Watch for year changes and adjust month if needed
        watch(selectedYear, (newYear) => {
            if (newYear === '2025' && parseInt(selectedMonth.value) < 6) {
                selectedMonth.value = '06' // Default to June if current month is before June
            }
        })

        watch(loading, (newLoading) => {
            if(newLoading) {
                document.getElementById("preloader").style.display = ""
            } else {
                document.getElementById("preloader").style.display = "none"
            }
        })

        return {
            brokers,
            allBreakdownData,
            pbBreakdownData,
            nbBreakdownData,
            ltBreakdownData,
            pipelineForecast,
            filteredBrokers,
            loading,
            totalFundedFiles,
            totalGrossAmount,
            totalGrossFees,
            // totalDiscountFees,
            averageGrossFeePercentage,
            totalSalesJourney,
            averageConversionRate,
            formatCurrency,
            formatPercentage,
            getFeePercentageClass,
            getBrokerGroupBadgeClass,
            selectedMonth,
            selectedYear,
            selectedBrokerGroup,
            selectedProvince,
            selectedMonthYear,
            availableYears,
            availableMonths,
            fetchData,
            fetchAllBreakdown,
            fetchPBBreakdown,
            fetchNBBreakdown,
            fetchLTBreakdown,
            fetchPipelineForecast,
            filterBrokers,
            viewBrokerDetails
        }
    }
}
</script>

<style scoped>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.progress {
    background-color: #e9ecef;
}

.badge {
    font-size: 0.75em;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.progress-bar {
    font-size: 0.75em;
    line-height: 20px;
}
</style>
