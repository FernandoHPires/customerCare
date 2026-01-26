<template>
    <div
        class="modal fade"
        id="approveAllCommissionDialog"
        data-coreui-keyboard="false"
        tabindex="-1"
        style="display: none">
        <div class="modal-dialog approve-all-commission-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="hideModal(modalIdApprove)"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    {{ message }}
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success" type="button" @click="accept()">
                        <i class="bi-check-lg me-1"></i>Yes
                    </button>
                    <button class="btn btn-outline-danger" type="button" @click="reject()">
                        <i class="bi-x-circle me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";
import "v-calendar/dist/style.css";
import RemoveDialog from "../../components/RemoveDialog";

export default {
    mixins: [util],
    props: {
        modalIdApprove: String,
        title: String,
        message: String,
        approvalConfirmation: Boolean,
    },
    emits: ["setApprovalConfirmation"],
    components: { RemoveDialog},
    data() {
        return {
            commissions: [],
        };
    },
    methods: {
        reject: function () {
            this.hideModal(this.modalIdApprove);
            this.hideBackdrop()
        },
        accept: function () {
            this.$emit("setApprovalConfirmation");
            this.hideModal(this.modalIdApprove);
            this.hideBackdrop()
        },
    },
};
</script>
