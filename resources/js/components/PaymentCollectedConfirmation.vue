<template>
    <div class="modal fade" :id="'paymentCollectedConfirmation' + parentModalId" data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; z-index: 8000">
        <div class="modal-dialog" role="document">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" @click="cancel()" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center">
                        <h5 class="my-3" v-html="message"></h5>
                    </div>


                    <strong>Payment Option</strong>

                    <div class="d-flex align-items-center gap-2 mt-1">
                        <input type="radio" v-model="paymentStatus" value="Payment Collected" />
                        <label class="ml-2">Payment Collected</label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" v-model="paymentStatus" value="Payment not Collected" />
                        <label class="ml-2">Payment not Collected</label>
                    </div>
                    
                    <div class="mt-3" v-if="paymentStatus === 'Payment not Collected'">
                        
                        <label class="form-label fw-bold">Select a new payment date</label>
                        <input 
                            type="date" 
                            class="form-control w-auto text-center" 
                            style="max-width: 180px;" 
                            v-model="newPaymentDate"
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="cancel()">
                        <i class="bi-x-lg me-1"></i>Cancel
                    </button>                    
                    <button class="btn btn-success" type="button" @click="save()">
                        <i class="bi-check-lg me-1"></i>Save
                    </button>                   
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';

export default {
    mixins: [util],
    props: ['event', 'message', 'type', 'parentModalId', 'refreshCount'],
    emits: ['return'],
    watch: {
        refreshCount: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
    },

    data() {
        return {
            paymentStatus: '',
            newPaymentDate: null,
        }
    },
    methods: {
        getData: function() {
            this.paymentStatus = ''
            this.newPaymentDate = null
        },
        save: function() {

            if (this.paymentStatus === '') {
                this.alertMessage = 'Please select a payment option';
                this.showAlert('error')
                return;
            }

            if (this.paymentStatus === 'Payment not Collected') {
                if (this.newPaymentDate === null) {
                    this.alertMessage = 'Please select a new payment date';
                    this.showAlert('error');
                    return;
                }

                const selectedDateStr = this.newPaymentDate;
                const todayStr = new Date().toISOString().split('T')[0];

                if (selectedDateStr <= todayStr) {
                    this.alertMessage = 'The new payment date must be greater than today';
                    this.showAlert('error');
                    return;
                }
            }

            if (this.paymentStatus === 'Payment Collected') {
                this.newPaymentDate = null;
            }

            if (this.paymentStatus === 'Payment Collected') {
                this.collected();
            } else if (this.paymentStatus === 'Payment not Collected') {
                this.notCollected();
            } else {
                this.cancel();
            }
        },
        collected: function() {
            this.$emit('return',this.event,'collected', this.newPaymentDate)
            this.hideModal('paymentCollectedConfirmation' + this.parentModalId)
            this.hideBackdrop()
        },
        notCollected: function() {
            this.$emit('return',this.event,'notCollected', this.newPaymentDate)
            this.hideModal('paymentCollectedConfirmation' + this.parentModalId)
            this.hideBackdrop()
        },        
        cancel: function() {
            this.$emit('return',this.event,'cancelled')
            this.hideModal('paymentCollectedConfirmation' + this.parentModalId)
            this.hideBackdrop()
        }
    }
}
</script>
