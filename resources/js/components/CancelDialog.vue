<template>
    <div class="modal fade" :id="'cancelDialog' + parentModalId" data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; z-index: 8000">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" @click="hideModal('cancelDialog' + parentModalId)" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div>
                        <label>{{ message }}</label>
                        <input v-model="rejectReason" type="text" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="hideModal('cancelDialog' + parentModalId)">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button class="btn btn-danger" type="button" @click="reject()">
                        <i class="bi-x-circle me-1"></i>Cancel
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
    props: ['event', 'message', 'type', 'parentModalId'],
    emits: ['return'],
    watch: {
        event: function(newValue, oldValue) {
            this.rejectReason = ''
        },
        parentModalId: function(newValue, oldValue) {
            this.rejectReason = ''
        }
    },
    data() {
        return {
            rejectReason: ''
        }
    },  
    methods: {
        reject: function() {
            if(this.rejectReason == '') {
                this.alertMessage = 'Cancel reason must be informed!'
                this.showAlert('error')
            } else {
                this.$emit('return', this.event, 'canceled', this.rejectReason)
                this.rejectReason = ''
                this.hideModal('cancelDialog' + this.parentModalId)
                this.hideBackdrop()
            }
        }
    }
}
</script>
