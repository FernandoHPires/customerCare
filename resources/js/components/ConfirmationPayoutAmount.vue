<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payout</h5>
                    <button type="button" class="btn-close" @click="hideModal(modalId)" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <td>Mortgage Code</td>
                                <td>{{ payout.mortgage_code }}</td>
                            </tr>
                            <tr>
                                <td>Interest Accrual Date</td>
                                <td>{{ formatPhpDate(payout.interest_acrrual_date) }}</td>
                            </tr>
                            <tr>
                                <td>Payment Received Date</td>
                                <td>
                                    <DatePicker v-model="paymentReceivedDate" :model-config="modelConfig" :timezone="timezone">
                                        <template v-slot="{ inputValue, inputEvents }">
                                            <input
                                                class="form-control"
                                                :value="inputValue"
                                                v-on="inputEvents"
                                            />
                                        </template>
                                    </DatePicker>
                                </td>
                            </tr>
                            <tr>
                                <td>Per Diem</td>
                                <td> {{ formatDecimal(payout.per_diem) }} </td>
                            </tr>
                            <tr>
                                <td>Per Diem Days</td>
                                <td> {{ perdiemDays }} </td>
                            </tr>
                            <tr>
                                <td>Original Payout Amount</td>
                                <td> {{ formatDecimal(payout.payout_amount) }} </td>
                            </tr>
                            <tr>
                                <td>Total Payout Amount</td>
                                <td> {{ formatDecimal(totalPayoutAmount) }} </td>
                            </tr>
                            <tr>
                                <td>Amount Received</td>
                                <td>
                                    <input type="number" class="form-control" v-model="amountReceived">
                                </td>
                            </tr>

                        </tbody>
                    </table>
             
                </div>
                

                <div class="modal-footer">
                    <button class="btn btn-outline-danger" type="button" @click="reject()">
                        <i class="bi-x-circle me-1"></i>Reject
                    </button>
                    
                    <button class="btn btn-success" type="button" @click="confirm()"
                        :disabled="totalPayoutAmount == 0 || !(amountReceived > (totalPayoutAmount - toleranceLimit) && amountReceived < (totalPayoutAmount + toleranceLimit))"
                    >
                        <i class="bi-check-lg me-1"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <reject-dialog
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :key="modalId"
        @return="rejectDialogOnReturn">
    </reject-dialog>
</template>

<script>
import { util } from '../mixins/util'
import RejectDialog from '../components/RejectDialog'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    props: ['payout'],
    emits: ['refresh'],
    components: { RejectDialog, DatePicker },
    watch: {
        paymentReceivedDate: {
            handler(newValue, oldValue) {
                this.calculatePayout()
            },
            deep: true
        },
        payout: {
            handler(newValue, oldValue) {
                this.cleanupForm()
            },
            deep: true
        }
    },
    data() {
        return {
            modalId: 'confirmationPayoutAmount',
            event: '',
            dialogMessage: '',
            paymentReceivedDate: null,
            perdiemDays: null,
            totalPayoutAmount: 0,
            amountReceived: '',
            toleranceLimit: 10
        }
    },
    methods: {
        cleanupForm: function() {
            this.paymentReceivedDate = null
            this.perdiemDays = null
            this.totalPayoutAmount = 0
            this.amountReceived = ''
        },
        confirm: function() {
            this.showPreLoader()

            let data = {
                id: this.payout.id,
                amountReceived: this.amountReceived,
                paymentReceivedDate: this.paymentReceivedDate
            }
            
            this.axios({
                method: 'post',
                url: 'web/payout-confirm',
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.hideModal(this.modalId)
                    this.$emit('refresh')
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
        
        reject: function() {
            this.dialogMessage = 'Are you sure you want to REJECT this payout?'
            this.event = 'rejectDialog' + this.modalId
            this.showModal('rejectDialog' + this.modalId)
        },
        rejectDialogOnReturn: function(event, returnMessage, returnRejectReason) {
            if(returnMessage == 'rejected' && event == 'rejectDialog' + this.modalId) {
                    
                this.showPreLoader()
                let data = {               
                    rejectReason: returnRejectReason
                }

                this.axios({
                    method: 'put',
                    url: 'web/payout/reject/' + this.payout.id,
                    data: data
                })
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.hideModal(this.modalId)
                        this.$emit('refresh')
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
            }
        },

        calculatePayout: function() {
            this.showPreLoader()
    
            let data = {
                id: this.payout.id,
                paymentReceivedDate: this.paymentReceivedDate
            }

            this.axios({
                method: 'post',
                url: 'web/calculate-payout',
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.perdiemDays = response.data.data.per_diem_days
                    this.totalPayoutAmount = response.data.data.total_payout_amount
                } else {
                    this.perdiemDays = 0
                    this.totalPayoutAmount = 0
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
