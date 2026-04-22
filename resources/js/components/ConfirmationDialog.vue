<template>
    <div class="modal fade"
        :id="'confirmationDialog' + parentModalId"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
        style="display: none;
        z-index: 8000">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" @click="cancel()" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center">
                        <h5 class="my-3">{{ message }}</h5>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="cancel()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button v-bind:class="['btn', type == 'success' ? 'btn-success' : 'btn-danger']" type="button" @click="confirm()">
                        <i class="bi-check-lg me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    props: ['event', 'message', 'type', 'parentModalId'],
    emits: ['return'],
    data() {
        return {
            
        }
    },
    methods: {
        confirm: function() {
            this.$emit('return',this.event,'confirmed')
            this.hideModal('confirmationDialog' + this.parentModalId)
            this.hideBackdrop()
        },
        cancel: function() {
            this.$emit('return',this.event,'cancelled')
            this.hideModal('confirmationDialog' + this.parentModalId)
            this.hideBackdrop()
        },
    }
}
</script>
