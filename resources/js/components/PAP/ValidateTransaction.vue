<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalTitle }}</h5>
                    <button type="button" class="btn-close" @click="hideModal(modalId)" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <td>Mortgage Code</td>
                                <td>{{ transaction.mortgageCode }}</td>
                            </tr>
                            <tr>
                                <td>Client Name</td>
                                <td>{{ transaction.clientName }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="card mb-3" v-if="Object.keys(bankTransaction).length > 0">
                        <div class="card-header">
                            <div class="d-flex">
                                <span>Bank Account Information</span>

                                <div class="ms-auto">
                                    <button 
                                        class="btn btn-primary" 
                                        @click="viewDocuments()">
                                        <i class="bi bi-file-pdf"></i>
                                        View Documents
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <tr>
                                        <td>Status</td>
                                        <td>
                                            <status-badge :status="bankTransaction.status"></status-badge>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Requested By</td>
                                        <td>{{ bankTransaction.createdBy }}</td>
                                    </tr>
                                    <tr>
                                        <td>Requested At</td>
                                        <td>{{ formatPhpDateTime(bankTransaction.createdAt) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Payee Name</td>
                                        <td>{{ bankTransaction.payeeName }}</td>
                                    </tr>
                                    <tr>
                                        <td>Bank Information</td>
                                        <td>
                                            <div class="d-flex align-items-end">
                                                <div class="me-2">
                                                    <label>Institution</label>
                                                    <input class="form-control" type="text" v-model="bankTransaction.institutionCode" disabled>
                                                </div>

                                                <div class="me-2">
                                                    <label>Transit</label>
                                                    <input class="form-control" type="text" v-model="bankTransaction.transit" disabled>
                                                </div>

                                                <div class="me-2">
                                                    <label>Account</label>
                                                    <input class="form-control" type="text" v-model="bankTransaction.account" disabled>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-end">
                                                <div class="me-2 pt-1">
                                                    <small>{{ bankTransaction.institutionName }}</small>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer" v-if="bankTransaction.status == 'Pending'">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex justify-content-start align-items-center text-danger" style="width: 70%;">
                                    <span id="banking_warning_message" style="font-weight: 700;">{{ errorMessage }}</span>
                                </div>
                                <div class="d-flex justify-content-end align-items-center" style="width: 30%;">
                                    <div class="me-1">
                                        <button class="btn btn-outline-danger" type="button" @click="rejectBank(bankTransaction.id)">
                                            <i class="bi-x-circle me-1"></i>Reject
                                        </button>
                                    </div>
                                    <div class="me-1">
                                        <button class="btn btn-success" type="button" @click="validateBank(bankTransaction.id)">
                                            <i class="bi-check-lg me-1"></i>Validate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card" v-if="Object.keys(newPapTransaction).length > 0 && category == 'new'">
                        <div class="card-header">New PAP</div>

                        <div class="card-body">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <td>Status</td>
                                        <td>
                                            <status-badge :status="newPapTransaction.status"></status-badge>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Requested By</td>
                                        <td>{{ newPapTransaction.createdBy }}</td>
                                    </tr>
                                    <tr>
                                        <td>Requested At</td>
                                        <td>{{ formatPhpDateTime(newPapTransaction.createdAt) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <strong>Details</strong>

                            <template v-if="newPapTransaction.paymentsDetail.paymentsFound">
                                <div class="alert alert-danger mt-2" role="alert">
                                    {{ newPapTransaction.paymentsDetail.paymentsMessage }}
                                </div>
                            </template>

                            <div class="row">
                                <div class="col-6">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>                                                
                                                <th v-if="showCheckbox"></th>
                                                <th>Payment Date</th>
                                                <th>Payment Amount</th>
                                                <th>Pre-Payment</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <template v-for="(transactionDetail, key) in newPapTransaction.details" :key="key">
                                                <tr v-if="key % 2 === 0">
                                                    <td v-if="showCheckbox">
                                                        <input 
                                                            type="checkbox" 
                                                            class="form-check-input me-2"
                                                            v-model="transactionDetail.validated"
                                                            :disabled="!transactionDetail.paymentBeforeNextBusinessDay || transactionDetail.isPaid"
                                                            @change="checkdisableValidatePayment(transactionDetail, key)"
                                                        />
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ formatPhpDate(transactionDetail.paymentDate) }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ formatDecimal(transactionDetail.paymentAmount) }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ (transactionDetail.prePayment ? 'Yes' : 'No') }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }"
                                                            v-if="transactionDetail.isPaid || transactionDetail.paymentBeforeNextBusinessDay">
                                                        <i class="bi bi-info-circle" v-tooltip="transactionDetail.message"></i>
                                                    </td>
                                                    <td v-else></td>                                                    
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-6">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th v-if="showCheckbox"></th>
                                                <th>Payment Date</th>
                                                <th>Payment Amount</th>
                                                <th>Pre-Payment</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <template v-for="(transactionDetail, key) in newPapTransaction.details" :key="key">
                                                <tr v-if="key % 2 !== 0">
                                                    <td v-if="showCheckbox">
                                                        <input 
                                                            type="checkbox" 
                                                            class="form-check-input me-2"
                                                            v-model="transactionDetail.validated"
                                                            :disabled="!transactionDetail.paymentBeforeNextBusinessDay || transactionDetail.isPaid"
                                                            @change="checkdisableValidatePayment(transactionDetail, key)"
                                                        />
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ formatPhpDate(transactionDetail.paymentDate) }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ formatDecimal(transactionDetail.paymentAmount) }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }">
                                                        {{ (transactionDetail.prePayment ? 'Yes' : 'No') }}
                                                    </td>
                                                    <td :class="{
                                                                'bg-danger': transactionDetail.isPaid,
                                                                'bg-warning': !transactionDetail.isPaid && transactionDetail.paymentBeforeNextBusinessDay
                                                                }" 
                                                            v-if="transactionDetail.isPaid || transactionDetail.paymentBeforeNextBusinessDay">
                                                        <i class="bi bi-info-circle" v-tooltip="transactionDetail.message"></i>
                                                    </td>
                                                    <td v-else></td>                                                    
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer" v-if="newPapTransaction.status == 'Pending'">
                            <div class="d-flex justify-content-end">
                                <div class="me-1">
                                    <button class="btn btn-outline-danger" type="button" @click="reject()">
                                        <i class="bi-x-circle me-1"></i>Reject
                                    </button>
                                </div>
                                <div class="me-1">
                                    <button class="btn btn-success" type="button" @click="validate()" :disabled="disableValidatePayment">
                                        <i class="bi-check-lg me-1"></i>Validate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

    <reject-dialog
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :key="modalId"
        @return="rejectDialogOnReturn">
    </reject-dialog>

    <payment-collected-confirmation
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :refreshCount="refreshCount"
        :key="modalId"
        @return="paymentCollectdOnReturn">
    </payment-collected-confirmation>

</template>

<script>
import { template } from 'lodash'
import { util } from '../../mixins/util'
import ConfirmationDialog from '../ConfirmationDialog'
import PaymentCollectedConfirmation from '../PaymentCollectedConfirmation'
import RejectDialog from '../RejectDialog'
import StatusBadge from '../StatusBadge'

export default {
    mixins: [util],
    emits: ['events','refresh'],
    components: { ConfirmationDialog, RejectDialog, StatusBadge, PaymentCollectedConfirmation },
    props: ['transaction','category'],
    watch: {
        transaction: {
            handler(newValue, oldValue) {
                this.getTransactionDetails()
            },
            deep: true
        }
    }, 
    data() {
        return {
            modalId: 'validateTransaction',
            event: null,
            dialogMessage: null,
            errorMessage: "",
            bankTransaction: [],
            newPapTransaction: [],
            disableValidatePayment: false,
            classBgWarning: false,
            currentDetailIndex: null,
            refreshCount: 0,
            showCheckbox: false,
        }
    },
    mounted() {
        const modalElement = document.getElementById('validateTransaction');
        if(modalElement){
            modalElement.addEventListener('hidden.coreui.modal', () =>{
                this.errorMessage = "";
            })
        }
    },
    computed: {
        modalTitle: function() {
            return 'Validate Transaction'
        }
    },
    methods: {
        viewDocuments: function () {
            window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument/Shared%20Documents/" + this.transaction.folder + "TACL/" + this.transaction.applicationId, "_blank");
        },
        validate: function() {
            this.dialogMessage = 'Do you confirm this transaction is correct? All payments will be created on the card.'
            this.event = 'newPap'
            this.showModal('confirmationDialog' + this.modalId)
        },
        reject: function() {
            this.dialogMessage = 'Reject Reason'
            this.event = 'newPap'
            this.showModal('rejectDialog' + this.modalId)
        },
        
        //Bank Information
        validateBank: function() {
            this.errorMessage = "";
            if((this.isEmpty(this.bankTransaction.payeeName) || (this.isEmpty(this.bankTransaction.institutionCode)) || (this.isEmpty(this.bankTransaction.transit)) || (this.isEmpty(this.bankTransaction.account)))){
                var tempErrorMessage = "Warning! The field(s)";
                if(this.isEmpty(this.bankTransaction.payeeName)){
                    tempErrorMessage += ' "Payee Name",'
                }
                if(this.isEmpty(this.bankTransaction.institutionCode)){
                    tempErrorMessage += ' "Institution",'
                }
                if(this.isEmpty(this.bankTransaction.transit)){
                    tempErrorMessage += ' "Transit",'
                }
                if(this.isEmpty(this.bankTransaction.account)){
                    tempErrorMessage += ' "Account",'
                }   
                tempErrorMessage = tempErrorMessage.slice(0, -1);
                this.errorMessage = tempErrorMessage + " is missing";
            } else {
                this.dialogMessage = 'Do you confirm the bank account information is correct?'
                this.event = 'bankInformation'
                this.showModal('confirmationDialog' + this.modalId)
            }
        },
        rejectBank: function() {
            this.errorMessage = "";
            this.dialogMessage = 'Reject Reason'
            this.event = 'bankInformation'
            this.showModal('rejectDialog' + this.modalId)
        },
        isEmpty: function(field){
            if(field == 0 || field == 'undefinded' || field == null || field == '' || field == '0'){
                return true;
            }
            return false;
        },
        confirmationDialogOnReturn: function(event, returnMessage) {
            if(returnMessage == 'confirmed') {
                if(event == 'bankInformation') {
                    this.showPreLoader()

                    this.axios({
                        method: 'put',
                        url: 'web/pap/bank-info/validate/' + this.bankTransaction.id
                    })
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            if(this.newPapTransaction.status === undefined || this.newPapTransaction.status == 'Done' || this.newPapTransaction.status == 'Rejected') {
                                this.hideModal(this.modalId)
                                this.$emit('refresh')
                            } else {
                                this.getTransactionDetails()
                            }
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

                } else if(event == 'newPap') {

                    this.showPreLoader()

                    this.axios({
                        method: 'get',
                        url: 'web/pap/transactions/process/' + this.newPapTransaction.id
                    })
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            if(this.bankTransaction.status === undefined || this.bankTransaction.status == 'Done' || this.bankTransaction.status == 'Rejected') {
                                this.hideModal(this.modalId)
                                this.$emit('refresh')
                            } else {
                                this.getTransactionDetails()
                            }
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
            } else if (returnMessage == 'cancelled') {

            }
        },
        paymentCollectdOnReturn: function(event, returnMessage, newPaymentDate) {

            if(returnMessage == 'collected' || returnMessage == 'notCollected') {

                if (returnMessage == 'collected') {
                    this.updatePayment('Payment Collected', newPaymentDate)
                } else {
                    this.updatePayment('Payment not Collected', newPaymentDate)
                }

                this.checkAllConfirmation()

                this.currentDetailIndex = null
                
            } else if (returnMessage == 'cancelled') {

                if (this.currentDetailIndex !== null) {
                    this.newPapTransaction.details[this.currentDetailIndex].validated = false
                    this.currentDetailIndex = null
                }

                this.checkAllConfirmation()

            }
        },
        rejectDialogOnReturn: function(event, returnMessage, rejectReason) {
            if(returnMessage == 'rejected') {
                if(event == 'bankInformation') {
                    this.showPreLoader()

                    let data = {
                        rejectReason: rejectReason
                    }

                    this.axios({
                        method: 'put',
                        url: 'web/pap/bank-info/reject/' + this.bankTransaction.id,
                        data: data
                    })
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            if(this.newPapTransaction.status === undefined || this.newPapTransaction.status == 'Done' || this.newPapTransaction.status == 'Rejected') {
                                this.hideModal(this.modalId)
                                this.$emit('refresh')
                            } else {
                                this.getTransactionDetails()
                            }
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
                } else if(event == 'newPap') {
                    this.showPreLoader()

                    let data = {
                        rejectReason: rejectReason
                    }

                    this.axios({
                        method: 'put',
                        url: 'web/pap/transactions/reject/' + this.newPapTransaction.id,
                        data: data
                    })
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            if(this.bankTransaction.status === undefined || this.bankTransaction.status == 'Done' || this.bankTransaction.status == 'Rejected') {
                                this.hideModal(this.modalId)
                                this.$emit('refresh')
                            } else {
                                this.getTransactionDetails()
                            }
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
            }
        },

        getTransactionDetails: function() {
            this.showPreLoader()

            this.axios.get(
                'web/pap/transactions/details/' + this.transaction.mortgageId,
                {
                    params: {
                        category: this.category
                    }
                }
            )
            .then(response => {
                if(this.checkApiResponse(response)) {

                    this.bankTransaction = response.data.data.bankTransaction
                    this.newPapTransaction = response.data.data.newPapTransaction

                    this.checkAllConfirmation()
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        checkdisableValidatePayment: function(transactionDetail, index) {            

            this.currentDetailIndex = index

            if (transactionDetail.validated === true) {                

                let messages = []
                
                if (transactionDetail.paymentBeforeNextBusinessDay === true) {
                    const date = this.formatPhpDate(transactionDetail.paymentDate)
                    const amount = this.formatDecimal(transactionDetail.paymentAmount)
                    messages.push(`Date: ${date} - Amount: ${amount}`)
                }

                this.dialogMessage = "Past due payment<br><br>" + messages.join("<br>")
                this.refreshCount++;

                this.event = 'paymentCollectedConfirmation'
                this.showModal('paymentCollectedConfirmation' + this.modalId)

            } else {

                this.updatePayment('',null)

                this.checkAllConfirmation()
            }

        },
        checkAllConfirmation: function() {

            if (this.newPapTransaction && Array.isArray(this.newPapTransaction.details) && this.newPapTransaction.details.length > 0) {

                this.disableValidatePayment = this.newPapTransaction.details.some(item => item.isPaid === true)

                if (this.disableValidatePayment == false) {
                    this.disableValidatePayment = this.newPapTransaction.paymentsDetail.paymentsFound
                }

                if (this.disableValidatePayment == false) {
                    this.disableValidatePayment = this.newPapTransaction.details.some(
                        item => item.paymentBeforeNextBusinessDay === true && item.validated === false
                    )
                }


                this.showCheckbox = this.newPapTransaction.details.some(item => item.paymentBeforeNextBusinessDay === true);
            }
        },
        updatePayment: function(paymentStatus, newPaymentDate) {

            let data = {
                id: this.newPapTransaction.details[this.currentDetailIndex].id,
                transactionId: this.newPapTransaction.id,
                paymentStatus: paymentStatus,
                newPaymentDate: newPaymentDate
            }

            this.axios({
                method: 'put',
                url: 'web/pap/transactions/payments',
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
           
                }
            })           
        }
    }
}
</script>
