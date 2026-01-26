<template>
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
                                <label for="" class="form-label">Start Date</label>
                                <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                        />
                                    </template>
                                </DatePicker>
                            </div>

                            <div>
                                <label for="" class="form-label">End Date</label>
                                <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                            
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
                <div>Process Foreclosure</div>

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
                        <th @click="filteredSort('payout_date')">
                            <i class="me-1" v-bind:class="[getSortIcon('payout_date')]"></i>Foreclosure Date
                        </th>
                        <th @click="filteredSort('last_balance')">
                            <i class="me-1" v-bind:class="[getSortIcon('last_balance')]"></i>Last Balance
                        </th>
                        <th @click="filteredSort('current_balance')">
                            <i class="me-1" v-bind:class="[getSortIcon('current_balance')]"></i>Current Balance
                        </th>
                        <th @click="filteredSort('payout_amount')">
                            <i class="me-1" v-bind:class="[getSortIcon('payout_amount')]"></i>Foreclosure Amount
                        </th>
                        <th @click="filteredSort('per_diem')">
                            <i class="me-1" v-bind:class="[getSortIcon('per_diem')]"></i>Per Diem
                        </th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="9">No foreclosure</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(foreclosure, key) in filteredData" :key="key">
                        <td>{{ foreclosure.application_id }}</td>
                        <td>{{ foreclosure.mortgage_code }}</td>
                        <td>{{ foreclosure.name }}</td>
                        <td>{{ formatPhpDate(foreclosure.payout_date) }}</td>

                        <td>{{ formatDecimal(foreclosure.last_balance) }}</td>
                        <td>{{ formatDecimal(foreclosure.current_balance) }}</td>
                        <td>{{ formatDecimal(foreclosure.payout_amount) }}</td>
                        <td>{{ foreclosure.per_diem }}</td>


                        <td style="text-align: right">

                            <button v-if="foreclosure.status == 'Processed'" class="btn btn-success me-2" type="button"
                                :disabled="true"
                            >
                                <i class="bi-check-lg me-1"></i>Processed
                            </button>

                            <button v-else-if="foreclosure.status == 'Deleted'" class="btn btn-outline-danger" type="button"
                                :disabled="true"
                            >
                                <i class="bi-x-circle me-1"></i>Canceled
                            </button>                            


                            <button v-else-if="foreclosure.last_balance != foreclosure.current_balance" class="btn btn-success me-2" type="button"
                                :disabled="foreclosure.last_balance != foreclosure.current_balance || foreclosure.payout_reason < 2 || foreclosure.status == 'True'"
                            >
                                <i class="bi-check-lg me-1"></i>Price error!
                            </button>
                          

                            <button v-else-if="foreclosure.payout_reason < 2" class="btn btn-success me-2" type="button"
                                :disabled="foreclosure.last_balance != foreclosure.current_balance || foreclosure.payout_reason < 2 || foreclosure.status == 'True'"
                            >
                                <i class="bi-check-lg me-1"></i>Need Payout Reason!
                            </button> 
                            


                            <button v-else class="btn btn-success me-2" type="button"
                                :disabled="foreclosure.last_balance != foreclosure.current_balance || foreclosure.payout_reason < 2 || foreclosure.status == 'True'"
                                @click="process(foreclosure)" 
                            >
                                <i class="bi-check-lg me-1"></i>Process
                            </button>                            

                        </td>                                            
                    </tr>
                </tbody>
                
            </table>
        </div>
    </div>    
   
    <confirmation-foreclosure-amount
        :payout="foreclosure"
        @refresh="getData()">
    </confirmation-foreclosure-amount>
</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationForeclosureAmount from '../../components/ConfirmationForeclosureAmount'
import RejectDialog from '../../components/RejectDialog'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationForeclosureAmount, RejectDialog, DatePicker },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                if(typeof oldValue !== 'object') this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                if(typeof oldValue !== 'object') this.getData()
            },
            deep: true
        }        
    },     
    data() {
        return {
            origTransaction: {},
            event: null,
            dialogMessage: null,
            data: [],
            foreclosure: {},
            actualTransaction: {}, 
            endDate:   new Date((new Date()).valueOf() + 1000*60*60*1440),
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
            companyIdFilter: 0,
            search: '',
            currentSort: 'mortgageId',
            currentSortDir: 'bi-sort-up'
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data = this.data
            data = data.filter(function(row) {
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

        process: function(foreclosure) {            
            this.foreclosure = foreclosure
            this.showModal('confirmationForeclosureAmount')
        },

        getData: function() {
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
            }   

            this.axios.get(
                'web/foreclosure-process',
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
