<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports">Reports</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports/broker-dashboard">Broker Dashboard</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                {{ brokerName }} - Details
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ brokerName }} - Broker Details ({{ selectedMonthYear }})</h5>
            <div class="d-flex gap-2">
                <button @click="goBack" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </button>
            </div>
        </div>

        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-white text-black">
                        <div class="card-body">
                            <h6 class="card-title">Sales Journey</h6>
                            <h3>{{ brokerData.sales_journey || 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-white text-black">
                        <div class="card-body">
                            <h6 class="card-title">Funded Files</h6>
                            <h3>{{ brokerData.funded_file || 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div v-if="brokerData.broker_group !== 'PB'" class="col-md-3">
                    <div class="card bg-white text-black">
                        <div class="card-body">
                            <h6 class="card-title">Conversion %</h6>
                            <h3>{{ formatPercentage(brokerData.conversion || 0) }}%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-white text-black">
                        <div class="card-body">
                            <h6 class="card-title">Total Gross Amount</h6>
                            <h3>${{ formatCurrency(brokerData.total_gross_amt || 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row - Average Fee % -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-white text-black">
                        <div class="card-body">
                            <h6 class="card-title">Average Fee %</h6>
                            <h3>{{ formatPercentage(brokerData.gross_fee_percentage || 0) }}%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for Sales Journey and Funded Files -->
            <ul class="nav nav-tabs mb-3" id="brokerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link" 
                        :class="{ active: activeTab === 'sales-journey' }"
                        @click="activeTab = 'sales-journey'"
                        type="button"
                    >
                        Sales Journey ({{ salesJourneyData.length }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link" 
                        :class="{ active: activeTab === 'funded-files' }"
                        @click="activeTab = 'funded-files'"
                        type="button"
                    >
                        Funded Files ({{ fundedFilesData.length }})
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="brokerTabsContent">
                <!-- Sales Journey Tab -->
                <div class="tab-pane fade" :class="{ 'show active': activeTab === 'sales-journey' }" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Sales Journey Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Sales Journey ID</th>
                                            <th>Created Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in salesJourneyData" :key="item.application_id">
                                            <td>{{ item.application_id }}</td>
                                            <td>{{ item.sales_journey_id }}</td>
                                            <td>{{ formatDate(item.created_at) }}</td>
                                        </tr>
                                        <tr v-if="salesJourneyData.length === 0">
                                            <td colspan="3" class="text-center text-muted">No sales journey data found</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Funded Files Tab -->
                <div class="tab-pane fade" :class="{ 'show active': activeTab === 'funded-files' }" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Funded Files Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Mortgage ID</th>
                                            <th>Funding Date</th>
                                            <th>Gross Amount</th>
                                            <th>Gross Fee</th>
                                            <th>Fee Percentage</th>
                                            <th>Company</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in fundedFilesData" :key="item.mortgage_id">
                                            <td>{{ item.application_id }}</td>
                                            <td>{{ item.mortgage_id }}</td>
                                            <td>{{ formatDate(item.funding_date) }}</td>
                                            <td>${{ formatCurrency(item.gross_amt) }}</td>
                                            <td>${{ formatCurrency(item.gross_fee) }}</td>
                                            <td>{{ formatPercentage(item.fee_percentage) }}%</td>
                                            <td>{{ item.company_name }}</td>
                                        </tr>
                                        <tr v-if="fundedFilesData.length === 0">
                                            <td colspan="7" class="text-center text-muted">No funded files data found</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

export default {
    name: 'BrokerDetails',
    setup() {
        const route = useRoute()
        const router = useRouter()
        
        const userId = ref('')
        const brokerName = ref('')
        const brokerData = ref({})
        const salesJourneyData = ref([])
        const fundedFilesData = ref([])
        const loading = ref(false)
        const selectedMonth = ref((new Date()).getMonth() + 1)
        const selectedYear = ref((new Date()).getYear() + 1900)
        const activeTab = ref('sales-journey')

        const selectedMonthYear = computed(() => {
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ]
            const monthIndex = parseInt(selectedMonth.value) - 1
            return `${monthNames[monthIndex]} ${selectedYear.value}`
        })

        const fetchBrokerDetails = async () => {
            loading.value = true
            try {
                const response = await axios.get('/web/reports/broker-details', {
                    params: {
                        userId: userId.value,
                        month: selectedMonth.value,
                        year: selectedYear.value
                    }
                })
                if (response.data.status === 'success') {
                    brokerData.value = response.data.data.brokerData || {}
                    salesJourneyData.value = response.data.data.salesJourneyData || []
                    fundedFilesData.value = response.data.data.fundedFilesData || []
                    // Set broker name from the response data
                    if (brokerData.value.user_fname) {
                        brokerName.value = brokerData.value.user_fname
                    }
                }
            } catch (error) {
                console.error('Error fetching broker details:', error)
            } finally {
                loading.value = false
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

        const formatDate = (dateString) => {
            if (!dateString) return ''
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })
        }

        const goBack = () => {
            router.push('/reports/broker-dashboard')
        }

        onMounted(() => {
            userId.value = route.params.userId || ''
            selectedMonth.value = route.query.month || (new Date()).getMonth() + 1
            selectedYear.value = route.query.year || (new Date()).getYear() + 1900
            
            if (userId.value) {
                fetchBrokerDetails()
            }
        })

        return {
            userId,
            brokerName,
            brokerData,
            salesJourneyData,
            fundedFilesData,
            loading,
            selectedMonth,
            selectedYear,
            activeTab,
            selectedMonthYear,
            formatCurrency,
            formatPercentage,
            formatDate,
            goBack
        }
    }
}
</script>

<style scoped>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.nav-tabs .nav-link {
    color: #495057;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    font-weight: 500;
}
</style>
