<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Validate PAP Update</h5>
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
                            <tr>
                                <td>Request Type</td>
                                <td>{{ transaction.requestType !== undefined ? transaction.requestType.name : '' }}</td>
                            </tr>
                            <tr>
                                <td>Requested By</td>
                                <td>{{ transaction.createdBy }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <strong>Details</strong>
                    <template v-if="transaction.requestTypeId == 512">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Original Payment Date</th>
                                    <th>Original Payment Amount</th>
                                    <th>1st Payment Date</th>
                                    <th>1st Payment Amount</th>
                                    <th>2nd Payment Date</th>
                                    <th>2nd Payment Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transactionDetail, key) in transactionDetails" :key="key">
                                    <td>{{ formatPhpDate(transactionDetail.originalPaymentDate) }}</td>
                                    <td>{{ formatDecimal(transactionDetail.originalPaymentAmount) }}</td>
                                    <td>{{ formatPhpDate(transactionDetail.new1stPaymentDate) }}</td>
                                    <td>{{ formatDecimal(transactionDetail.new1stPaymentAmount) }}</td>
                                    <td>{{ formatPhpDate(transactionDetail.new2ndPaymentDate) }}</td>
                                    <td>{{ formatDecimal(transactionDetail.new2ndPaymentAmount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>

                    <template v-else>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Original Payment Date</th>
                                    <th>Original Payment Amount</th>
                                    <th>New Payment Date</th>
                                    <th>New Payment Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transactionDetail, key) in transactionDetails" :key="key">
                                    <td>{{ formatPhpDate(transactionDetail.originalPaymentDate) }}</td>
                                    <td>{{ formatDecimal(transactionDetail.originalPaymentAmount) }}</td>
                                    <td>{{ formatPhpDate(transactionDetail.new1stPaymentDate) }}</td>
                                    <td>{{ formatDecimal(transactionDetail.new1stPaymentAmount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-danger" type="button" @click="reject(transaction.id)">
                        <i class="bi-x-circle me-1"></i>Reject
                    </button>
                    <button class="btn btn-success" type="button" @click="validate(transaction.id)">
                        <i class="bi-check-lg me-1"></i>Validate
                    </button>
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
</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationDialog from '../ConfirmationDialog'
import RejectDialog from '../RejectDialog'
import StatusBadge from '../StatusBadge'

export default {
    mixins: [util],
    emits: ['events','refresh'],
    components: { ConfirmationDialog, RejectDialog, StatusBadge },
    props: ['transaction'],
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
            modalId: 'validateUpdate',
            event: null,
            dialogMessage: null,
            transactionDetails: null
        }
    },
    methods: {

        validate: function() {
            if(this.transaction.isDeferral || this.transaction.requestTypeId === 534) {
                this.dialogMessage = 'Do you confirm you have the new disclosure documents received and signed by the client? The payment(s) will be changed on the card.'
            } else {
                this.dialogMessage = 'Do you confirm this PAP update is correct? The payment(s) will be changed on the card.'
            }
            this.event = 'update'
            this.showModal('confirmationDialog' + this.modalId)
        },
        reject: function() {
            this.dialogMessage = 'Reject Reason'
            this.event = 'update'
            this.showModal('rejectDialog' + this.modalId)
        },

        confirmationDialogOnReturn: function(event, returnMessage) {
            if(returnMessage == 'confirmed') {
                this.showPreLoader()

                this.axios({
                    method: 'get',
                    url: 'web/pap/transactions/process/' + this.transaction.id
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

        rejectDialogOnReturn: function(event, returnMessage, rejectReason) {
            if(returnMessage == 'rejected') {
                this.showPreLoader()

                let data = {
                    rejectReason: rejectReason
                }

                this.axios({
                    method: 'put',
                    url: 'web/pap/transactions/reject/' + this.transaction.id,
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

        getTransactionDetails: function() {
            this.showPreLoader()

            this.axios.get(
                'web/pap/transactions/details/' + this.transaction.mortgageId,
                {
                    params: {
                        category: this.transaction.requestTypeId
                    }
                }
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.transactionDetails = response.data.data
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
