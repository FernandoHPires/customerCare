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
                Process Payout
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
                                        <th>Completed</th>
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
                                <label class="form-label">Company</label>
                                <select class="form-select" v-model="companyIdFilter" @change="getData()">
                                    <option value="0">All</option>
                                    <option v-for="(company, key) in papCompanies" :key="key" :value="company.id">{{ company.name }}</option>
                                </select>
                            </div>

                            <div class="pe-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" v-model="status">
                                    <option v-for="(value, key) in statuses" :key="key" :value="value.id">{{ value.name }}</option>
                                </select>
                            </div>

                            <div class="pe-2">
                                <label class="form-label">Start Date</label>
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

                            <div class="pe-2">
                                <label class="form-label">End Date</label>
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

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div></div>

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
                        <th @click="filteredSort('application_id')">
                            <i class="me-1" v-bind:class="[getSortIcon('application_id')]"></i>TACL#
                        </th>
                        <th @click="filteredSort('mortgage_code')">
                            <i class="me-1" v-bind:class="[getSortIcon('mortgage_code')]"></i>Mortgage Code
                        </th>
                        <th @click="filteredSort('name')">
                            <i class="me-1" v-bind:class="[getSortIcon('name')]"></i>Client Name
                        </th>
                        <th @click="filteredSort('payout_date_s')">
                            <i class="me-1" v-bind:class="[getSortIcon('payout_date_s')]"></i>Payout Date
                        </th>
                        <th @click="filteredSort('last_balance')">
                            <i class="me-1" v-bind:class="[getSortIcon('last_balance')]"></i>Last Balance
                        </th>
                        <th @click="filteredSort('current_balance')">
                            <i class="me-1" v-bind:class="[getSortIcon('current_balance')]"></i>Current Balance
                        </th>
                        <th @click="filteredSort('payout_amount')">
                            <i class="me-1" v-bind:class="[getSortIcon('payout_amount')]"></i>Payout Amount
                        </th>
                        <th @click="filteredSort('per_diem')">
                            <i class="me-1" v-bind:class="[getSortIcon('per_diem')]"></i>Per Diem
                        </th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="data.length == 0">
                    <tr>
                        <td colspan="9">No payouts</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(payout, key) in filteredData" :key="key">
                        <td>{{ payout.application_id }}</td>
                        <td>{{ payout.mortgage_code }}</td>
                        <td>{{ payout.name }}</td>
                        <td>{{ formatPhpDate(payout.payout_date) }}</td>
                        <td>{{ formatDecimal(payout.last_balance) }}</td>
                        <td>{{ formatDecimal(payout.current_balance) }}</td>
                        <td>{{ formatDecimal(payout.payout_amount) }}</td>
                        <td>{{ payout.per_diem }}</td>

                        <td class="text-end">
                            <button v-if="payout.status == 'Processed'" class="btn btn-success" type="button" disabled>
                                <i class="bi-check-lg me-1"></i>Processed
                            </button>
                            <button v-else-if="payout.status == 'Rejected' || payout.status == 'Canceled'" class="btn btn-danger" type="button" disabled>
                                <i class="bi-x-circle me-1"></i>{{ payout.status }}
                            </button>
                            <button v-else-if="payout.last_balance != payout.current_balance" class="btn btn-success" type="button"
                                :disabled="payout.last_balance != payout.current_balance || payout.payout_reason < 2"
                            >
                                <i class="bi-check-lg me-1"></i>Price error!
                            </button>
                            <button v-else-if="payout.payout_reason < 2" class="btn btn-success" type="button"
                                :disabled="payout.last_balance != payout.current_balance || payout.payout_reason < 2"
                            >
                                <i class="bi-check-lg me-1"></i>Need Payout Reason!
                            </button> 
                            <button v-else class="btn btn-success" type="button"
                                :disabled="payout.last_balance != payout.current_balance || payout.payout_reason < 2"
                                @click="process(payout)" 
                            >
                                <i class="bi-check-lg me-1"></i>Process
                            </button>
                        </td>
                    </tr>
                </tbody>
                
            </table>
        </div>
    </div>
   
    <confirmation-payout-amount
        :payout="payout"
        @refresh="getData()">
    </confirmation-payout-amount>

</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationPayoutAmount from '../../components/ConfirmationPayoutAmount'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationPayoutAmount, DatePicker },
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
            isEditing: false,
            origTransaction: {},
            event: null,
            dialogMessage: null,
            data: [],
            payout: {},
            actualTransaction: {}, 
            endDate: new Date((new Date()).valueOf() + 1000*60*60*720),
            startDate: new Date((new Date()).valueOf() - 1000*60*60*720),
            currentSort: 'application_id',
            currentSortDir: 'bi-sort-up',
            companyIdFilter: 0,
            status: 'all',
            statuses: [
                {id: 'all', name: 'All'},
                {id: 'Active', name: 'Process'},
                {id: 'Processed', name: 'Processed'},
                {id: 'Rejected', name: 'Rejected'},
                {id: 'Canceled', name: 'Canceled'}
            ],
            search: ''
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data = this.data
            var status = this.status

            data = data.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return (
                        String(row[key]).toLowerCase().indexOf(search) > -1 &&
                        (status === 'all' || row.status === status)
                    )
                })
            })

            return data
        }
    },
    methods: {
        getSummary: function() {
            this.papCompanies.forEach(company => {
                company.done = 0
                company.pending = 0
                company.rejected = 0
            })

            var papCompanies = this.papCompanies

            this.data.map(transaction => {

                papCompanies.forEach((company, index) => {
                    if(company.id === transaction.company_id) {
                        if(transaction.status === 'Rejected') {
                           papCompanies[index].rejected++;

                        } else if(transaction.status === 'Completed' || transaction.status === 'Processed') {
                           papCompanies[index].done++;

                        } else {
                            papCompanies[index].pending++;
                        }
                    }
                })

            })

            this.papCompanies = papCompanies
        },

        process: function(payout) {
            this.payout = payout
            this.showModal('confirmationPayoutAmount')
        },

        getData: function() {
            this.showPreLoader()

            let data = {
                companyId: this.companyIdFilter,
                startDate: this.startDate,
                endDate: this.endDate
            }   

            this.axios.get(
                'web/payout-process',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.data = response.data.data
                    this.getSummary()
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
