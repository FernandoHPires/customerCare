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
                Payout Tracker
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
                                <label for="" class="form-label">Company</label>
                                <select class="form-select" v-model="companyId" @change="getData()">
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

                            <div>
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
                        <th @click="filteredSort('created_by')">
                            <i class="me-1" v-bind:class="[getSortIcon('created_by')]"></i>Request By
                        </th>
                        <th @click="filteredSort('created_at_s')">
                            <i class="me-1" v-bind:class="[getSortIcon('created_at_s')]"></i>Request At
                        </th>
                        <th @click="filteredSort('admin_status')">
                            <i class="me-1" v-bind:class="[getSortIcon('admin_status')]"></i>Admin
                        </th>
                        <!--<th @click="filteredSort('broker_status')">
                            <i class="me-1" v-bind:class="[getSortIcon('broker_status')]"></i>Broker
                        </th>-->
                        <th @click="filteredSort('lawyer_status')">
                            <i class="me-1" v-bind:class="[getSortIcon('lawyer_status')]"></i>Lawyer
                        </th>
                        <th @click="filteredSort('finance_status')">
                            <i class="me-1" v-bind:class="[getSortIcon('finance_status')]"></i>Finance
                        </th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="10">No payouts</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(payout, key) in filteredData" :key="key">
                        <td>{{ payout.application_id }}</td>
                        <td>{{ payout.mortgage_code }}</td>
                        <td>{{ payout.name }}</td>
                        <td>{{ formatPhpDate(payout.payout_date) }}</td>
                        <td>{{ payout.created_by }}</td>
                        <td>{{ formatPhpDateTime(payout.created_at) }}</td>
                        <td class="text-center">
                            <status-badge v-if="payout.finance_status != 'Rejected'" :status="payout.admin_status"></status-badge>
                        </td>
                        <!--<td class="text-center">
                            <status-badge v-if="payout.finance_status != 'Rejected'" :status="payout.broker_status"></status-badge>
                        </td>-->
                        <td class="text-center">
                            <status-badge v-if="payout.finance_status != 'Rejected'" :status="payout.lawyer_status"></status-badge>
                        </td>
                        <td class="text-center">
                            <status-badge :status="payout.finance_status" :tooltip="payout.cancel_reason"></status-badge>
                        </td>
                        <td class="text-end">
                            <button v-if="payout.finance_status != 'Rejected' && payout.admin_status == 'A' && payout.broker_status == 'A' && payout.lawyer_status != 'S'"
                                @click="sendLawyer(payout)" class="btn btn-success me-2" type="button">
                                <i class="bi-send me-1"></i>Send to Lawyer
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
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
            modalId: 'payout',
            isEditing: false,
            origTransaction: {},
            event: null,
            dialogMessage: null,
            data: [],
            actualTransaction: {},
            startDate: new Date((new Date()).valueOf() - 1000*60*60*360),
            endDate: new Date((new Date()).valueOf() + 1000*60*60*1440),
            currentSort: 'created_at_s',
            currentSortDir: 'bi-sort-up',
            companyId: 0,
            status: 'all',
            statuses: [
                {id: 'all', name: 'All'},
                {id: '', name: 'Pending'},
                {id: 'Completed', name: 'Completed'},
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

        sendLawyer: function(payout) {
            this.showPreLoader()

            this.axios({
                method: 'put',
                url: 'web/payout/send-lawyer/' + payout.id
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
                }
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },

        getData: function() {
            
            this.showPreLoader()

            let data = {
                companyId: this.companyId,
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                'web/payouts',
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
