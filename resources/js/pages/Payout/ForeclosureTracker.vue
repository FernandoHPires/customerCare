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
                <div>Foreclosure Tracker</div>

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
                        <th @click="sort('applicationId')">
                            <i class="me-1" v-bind:class="[getSortIcon('applicationId')]"></i>TACL#
                        </th>
                        <th @click="sort('mortgageCode')">
                            <i class="me-1" v-bind:class="[getSortIcon('mortgageCode')]"></i>Mortgage Code
                        </th>
                        <th @click="sort('position')">
                            <i class="me-1" v-bind:class="[getSortIcon('position')]"></i>Position
                        </th>
                        <th @click="sort('clientName')">
                            <i class="me-1" v-bind:class="[getSortIcon('clientName')]"></i>Borrower Name
                        </th>
                        <th @click="sort('lenderName')">
                            <i class="me-1" v-bind:class="[getSortIcon('lenderName')]"></i>Investor
                        </th>
                        <th @click="sort('lawyerName')">
                            <i class="me-1" v-bind:class="[getSortIcon('lawyerName')]"></i>Lawyer
                        </th>
                        <th @click="sort('province')">
                            <i class="me-1" v-bind:class="[getSortIcon('province')]"></i>Province
                        </th>
                        <th @click="sort('createdAtS')">
                            <i class="me-1" v-bind:class="[getSortIcon('createdAtS')]"></i>Start Date
                        </th>
                        <th @click="sort('closedAtS')">
                            <i class="me-1" v-bind:class="[getSortIcon('closedAtS')]"></i>Close Date
                        </th>
                        <th @click="sort('alpineOSB')">
                            <i class="me-1" v-bind:class="[getSortIcon('alpineOSB')]"></i>Alpine OSB
                        </th>
                        <th @click="sort('firstMortgageOSB')">
                            <i class="me-1" v-bind:class="[getSortIcon('firstMortgageOSB')]"></i>1st Mortgage OSB
                        </th>
                        <th @click="sort('secondMortgageOSB')">
                            <i class="me-1" v-bind:class="[getSortIcon('secondMortgageOSB')]"></i>2nd Mortgage OSB
                        </th>
                        <th @click="sort('ltv')">
                            <i class="me-1" v-bind:class="[getSortIcon('ltv')]"></i>LTV
                        </th>
                        <th @click="sort('appraisalValue')">
                            <i class="me-1" v-bind:class="[getSortIcon('appraisalValue')]"></i>Appraisal Value
                        </th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="14">No foreclosure</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(payout, key) in filteredData" :key="key">
                        <td>{{ payout.applicationId }}</td>
                        <td>{{ payout.mortgageCode }}</td>
                        <td>{{ payout.position }}</td>
                        <td>{{ payout.clientName }}</td>
                        <td>{{ payout.lenderName }}</td>
                        <td>{{ payout.lawyerName }}</td>
                        <td>{{ payout.province }}</td>
                        <td>{{ formatPhpDate(payout.createdAt) }}</td>
                        <td>{{ payout.closedAtS }}</td>
                        <td>{{ formatDecimal(payout.alpineOSB) }}</td>
                        <td>{{ formatDecimal(payout.firstMortgageOSB) }}</td>
                        <td>{{ formatDecimal(payout.secondMortgageOSB) }}</td>
                        <td>{{ payout.ltv }}</td>
                        <td>{{ formatDecimal(payout.appraisalValue) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :key="modalId"
        @return="confirmationDialogOnReturn"
    />
</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import RejectDialog from '../../components/RejectDialog'
import StatusBadge from '../../components/StatusBadge'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationDialog, RejectDialog, DatePicker, StatusBadge },
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
            modalId: 'foreclosure',
            event: null,
            dialogMessage: null,
            data: [],
            actualTransaction: {},
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
            endDate: new Date((new Date()).valueOf() + 1000*60*60*1440),
            currentSort: 'createdAtS',
            currentSortDir: 'bi-sort-up',
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

                        } else if(transaction.status === 'Completed') {
                           papCompanies[index].done++;

                        } else {
                            papCompanies[index].pending++;
                        }
                    }
                })

            })

            this.papCompanies = papCompanies
        },

        getData: function() {
            
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                'web/foreclosures',
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
