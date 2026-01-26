<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                Finance
            </li>
            <li class="breadcrumb-item active">
                New PAP
            </li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row">

            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        Summary
                    </div>
                    <div class="card-body">

                        <div class="d-flex">    
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Done</th>
                                        <th>Pending</th>
                                        <th>Rejected</th>
                                    </tr>
                                </thead>                              
                                
                                <tbody v-if="papCompanies.length == 0">
                                    <tr>
                                        <td colspan="7">No transactions</td>
                                    </tr>
                                </tbody>

                                <tbody v-else>
                                    <tr v-for="(company, key) in papCompanies" :key="key">
                                        <td>{{ company.name }}</td>
                                        <td>{{ company.done }}</td>
                                        <td>{{ company.pending }}</td>
                                        <td>{{ company.rejected }}</td>
                                    </tr>
                                </tbody>

                            </table>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        Filters
                    </div>
                    <div class="card-body">

                        <div class="d-flex">
                            <div class="pe-2">
                                <label for="" class="form-label">Company</label>
                                <select class="form-select" v-model="companyIdFilter" @change="getData()">
                                    <option value="0">All</option>
                                    <option v-for="(company, key) in papCompanies" :key="key" :value="company.id">{{ company.name }}</option>
                                </select>
                            </div>

                            <div class="pe-2">
                                <label for="" class="form-label">Start Date</label>
                                <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                            @change="getData()"
                                        />
                                    </template>
                                </DatePicker>
                            </div>

                            <div class="me-auto">
                                <label for="" class="form-label">End Date</label>
                                <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                            @change="getData()"
                                        />
                                    </template>
                                </DatePicker>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" v-if="alerts.length > 0">
        <div class="card-header text-white bg-danger">
            <div class="d-flex align-items-center">
                <div>
                    PAP Alert
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mortgage Code</th>
                        <th>Date</th>
                        <th>Alert</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(alert, key) in alerts" :key="key">
                        <td>{{ alert.mortgageCode }}</td>
                        <td>{{ formatPhpDate(alert.processingDate) }}</td>
                        <td>{{ alert.message }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div>
                    New PAP
                </div>

                <div class="ms-auto">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search" v-model="search">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th @click="filteredSort('applicationId')">
                            <i class="me-1" v-bind:class="[getSortIcon('applicationId')]"></i>TACL#
                        </th>
                        <th @click="filteredSort('mortgageCode')">
                            <i class="me-1" v-bind:class="[getSortIcon('mortgageCode')]"></i>Mortgage Code
                        </th>
                        <th @click="filteredSort('clientName')">
                            <i class="me-1" v-bind:class="[getSortIcon('clientName')]"></i>Client Name
                        </th>
                        <th @click="filteredSort('statuses.bank')">
                            <i class="me-1" v-bind:class="[getSortIcon('statuses.bank')]"></i>Bank Info
                        </th>
                        <th @click="filteredSort('statuses.newPap')">
                            <i class="me-1" v-bind:class="[getSortIcon('statuses.newPap')]"></i>New PAP
                        </th>
                        <th></th>
                    </tr>
                </thead> 

                <tbody v-if="data.length == 0">
                    <tr>
                        <td colspan="6">No transactions</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(transaction, key) in filteredData" :key="key">
                        <td>
                            <span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.applicationId }}</span>
                        </td>
                        <td>
                            <span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.mortgageCode }}</span>
                        </td>
                        <td>
                            <span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.clientName }}</span>
                        </td>
                        <td>
                            <status-badge :status="transaction.statuses.bank"></status-badge>
                        </td>
                        <td>
                            <status-badge :status="transaction.statuses.newPap"></status-badge>
                        </td>
                        
                        <td class="text-end">
                            <template v-if="transaction.status == 'Done'">
                                <button type="button" class="btn btn-secondary me-2" disabled>Validated</button>
                            </template>

                            <template v-else>
                                <button type="button" class="btn btn-secondary me-2"
                                    @click="showValidate(transaction)">Validate
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!--Modals-->
    <pap-validate-transaction
        :transaction="actualTransaction"
        category="new"
        @refresh="getData()">
    </pap-validate-transaction>
</template>

<script>
import { util } from '../../mixins/util'
import PapValidateTransaction from '../../components/PAP/ValidateTransaction'
import StatusBadge from '../../components/StatusBadge'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { PapValidateTransaction, DatePicker, StatusBadge },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        }
    },
    data() {
        return {
            data: [],
            alerts: [],
            actualTransaction: {},
            companyId: '',
            endDate: new Date(),
            startDate: new Date(),
            companyIdFilter: 0,
            search: '',
            currentSort: 'mortgageId',
            currentSortDir: 'bi-sort-up',
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data   = this.data

            data       = data.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return (
                        String(row[key]).toLowerCase().indexOf(search) > -1
                    )
                })
            })

            return data
        }
    },
    methods: {
        getSummary() {
            this.papCompanies.forEach(company => {
                company.done = 0
                company.pending = 0
                company.rejected = 0
            })

            var papCompanies = this.papCompanies

            this.data.map(transaction => {

                papCompanies.forEach((company, index) => {
                    if(company.id === transaction.companyId) {
                        if(transaction.status === 'Done') {
                            papCompanies[index].done++;
                        } else if(transaction.status === 'Pending') {
                            papCompanies[index].pending++;
                        } else {
                            papCompanies[index].rejected++;
                        }
                    }
                })

            })

            this.papCompanies = papCompanies
        },
        showValidate: function(transaction) {
            this.actualTransaction = transaction
            this.showModal('validateTransaction')
        },
        getData: function() {
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
                companyIdFilter: this.companyIdFilter,
                category: 'new'
            }

            this.axios.get(
                'web/pap/transactions',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.data = response.data.data.transactions
                    this.alerts = response.data.data.alerts
                    this.getSummary();
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>
