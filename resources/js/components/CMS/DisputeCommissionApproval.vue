<template>
    <div
        class="modal fade"
        :id="modalId"
        data-coreui-keyboard="false"
        tabindex="-1"
        style="display: none"
    >
        <div class="modal-dialog summary-dispute" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispute Reason?</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="hideModal(modalId)"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <div>
                        <input :value="localdisputeReason" @input="updateRejectReason($event)" type="text" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="cancel">
                        <i class="bi-x-lg me-1"></i>Cancel
                    </button>
                    <button class="btn btn-danger" type="button" @click="reject()">
                        <i class="bi-x-circle me-1"></i>Submit Dispute
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";

export default {
    mixins: [util],
    props: ['event', 'modalId', 'type', 'parentModalId', 'disputeReason'],
    emits: ['return', 'updateDisputeReason'],
    components: {},
    data() {
        return {
            localdisputeReason: ''
        }
    },  
   
    methods: {
        reject: function() {
            if(this.localDisputeReason  == '') {
                this.alertMessage = 'Reject reason must be informed!'
                this.showAlert('error')
            } else {
                this.$emit('updateDisputeReason', this.localDisputeReason); 

                this.$emit('return', this.localdisputeReason)
                this.hideModal(this.modalId);
                this.hideBackdrop()
            }
        },
        updateRejectReason(event) {
             this.localdisputeReason = event.target.value;
        },
        cancel: function () {
            this.hideModal(this.modalId);
            this.hideBackdrop()
        },
    },
};
</script>
