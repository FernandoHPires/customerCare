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
                Cancel Payout
            </li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row">
                        
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
                                            @change="getData()"
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
                <div>
                    
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
                            <i class="me-1" v-bind:class="[getSortIcon('payout_date')]"></i>Payout Date
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

                <tbody v-if="filteredData.length == 0">
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
                            <button v-if="payout.status != ''" class="btn btn-danger" type="button" disabled>
                                <i class="bi-x-circle me-1"></i>{{ payout.status }}
                            </button>

                            <button v-else class="btn btn-outline-danger" type="button" @click="cancel(payout)">
                                <i class="bi-x-circle me-1"></i>Cancel
                            </button>
                        </td>
                    </tr>
                </tbody>
                
            </table>
        </div>
    </div>   

    <cancel-dialog
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :key="modalId"
        @return="rejectDialogOnReturn">
    </cancel-dialog>

</template>

<script>
import { util } from '../../mixins/util'
import CancelDialog from '../../components/CancelDialog'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { DatePicker, CancelDialog },
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
            payouts: [],         
            actualTransaction: {}, 
            endDate:   new Date((new Date()).valueOf() + 1000*60*60*1440),
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
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
            var data = this.payouts
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

        cancel: function(payout) {
            this.dialogMessage = 'Are you sure you want to CANCEL this payout?'
            this.event = this.modalId
            this.payout = payout
            this.showModal('cancelDialog' + this.modalId)
        },
       
        rejectDialogOnReturn: function(event, returnMessage, returnRejectReason) {
            if(returnMessage == 'canceled' && event == this.modalId) {               
                if (returnRejectReason != '') {
                    
                    this.showPreLoader() 

                    let data = {
                        id: this.payout.id,
                        mortgage_id: this.payout.mortgage_id,
                        payout_id: this.payout.payout_id,                        
                        comment: returnRejectReason
                    }

                    this.axios({
                        method: 'post',
                        url: 'web/payout-cancel',
                        data: data
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
                } else {
                    this.alertMessage = 'Reason for Cancel must be informed!'
                    this.showAlert('error')
                }
            }
        },  

        getData: function() {
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
            }

            this.axios.get(
                'web/cancel-payout',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.payouts = response.data.data
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
