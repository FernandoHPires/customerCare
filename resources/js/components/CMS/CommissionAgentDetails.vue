<template>
     <div
            class="modal fade"
            id="commissionAgentDetails"
            data-coreui-keyboard="false"
            tabindex="-1"
            style="display: none"
        >
            <div class="modal-dialog commission-agent-details" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agent Summary</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="hideModal(modalId)"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Count</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody v-if="commissions?.length == 0">
                                <tr>
                                    <td colspan="7">No Commission</td>
                                </tr>
                            </tbody>

                            <tbody v-else>
                                <tr v-for="(commission, key) in commissions" :key="key">
                                    <td>{{ commission.group }}</td>
                                    <td>{{ commission.count }}</td>
                                    <td>{{ formatDecimal(commission.amount) }}</td>

                                    <td v-if="commission.group != 'Total'" class="text-end">
                                        <button type="button" class="btn btn-info" @click="summary(commission)">
                                            <i class="bi bi-card-checklist"></i>
                                            Details
                                        </button>
                                    </td>
                                    <td v-else></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button
                            class="btn btn-outline-dark"
                            type="button"
                            @click="hideModal(modalId)"
                        >
                            <i class="bi-x-lg me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <summary-commission-approval
        :commission="commissionSelected"
        :modalId="modalIdApproval">
    </summary-commission-approval>
</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";
import SummaryCommissionApproval from "../../components/CMS/SummaryCommissionApproval";
import { DatePicker } from "v-calendar";
import "v-calendar/dist/style.css";
import RemoveDialog from "../../components/RemoveDialog";

export default {
    mixins: [util],
     props: {
        commission: {
            type: Object,
            required: true,
        },
        modalId: String,
        reference: String,
        agent: Number
    },
    emits: ["events"],
    components: { VTooltip, SummaryCommissionApproval, DatePicker, RemoveDialog, },
    watch: {
        reference: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
        agent: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
    },
    data() {
        return {
            commissions: [],
            commissionsApproval: [],
            search: "",
            modalIdApproval: "summaryCommissionApproval",
            commissionSelected: {},
        };
    },

    mounted() {
        this.getData();    
    },

    methods: {
        getData: function () {
            if(this.agent !== null && this.reference !== null) {

                let referenceDate = new Date(this.reference);
                let data = {
                    referenceDate: referenceDate,
                    agent: this.agent,
                };

                this.axios({
                    method: "post",
                    url: "web/cms/commission-agent-detail",
                    data: data,
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.commissions = response.data.data;                        
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = "Error in commission agent deatil";
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
            }
        },

        summary: function (commission) {

            this.commissionSelected = { ...commission };
            
            this.showModal("summaryCommissionApproval");
        },
    },
};
</script>
