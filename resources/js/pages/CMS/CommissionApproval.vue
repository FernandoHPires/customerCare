<template>
    <div class="row mb-3">
        <div class="col-6">
            <div class="card">
                <div class="card-header">Filter</div>

                <div class="card-body">
                    <div class="d-flex">
                        <div class="pe-2">
                            <label class="form-label">Reference Month</label>
                            <select class="form-select" v-model="reference">
                                <option
                                    v-for="(reference, key) in references"
                                    :key="key"
                                    :value="reference.id"
                                >
                                    {{ reference.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card" style="height: 127.5px">
                <div class="card-header">Action</div>

                <div class="card-body">
                    <div
                        class="d-flex"
                        style="position: relative; top: 20%"
                    >
                        <div class="pe-2 d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-success"
                                :class="commissionsApproval[0]?.approval !== 'review' ? 'disabled' : ''"
                                @click="approve()"
                            >
                                {{commissionsApproval[0]?.approval === 'yes' ? 'Approved' : 'Approve it' }}
                            </button>
                            <button 
                                type="button"
                                class="btn btn-danger"
                                :class="commissionsApproval[0]?.approval !== 'review' ? 'disabled' : ''"
                                @click="dispute()"
                            >
                            {{commissionsApproval[0]?.approval === 'no' ? 'Disputed' : 'Dispute' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <h5 class="m-0">Commission Approval</h5>

                <div class="ms-auto">
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="bi-search"></i
                        ></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search"
                            v-model="search"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th>Count</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody v-if="filteredData.length == 0">
                        <tr>
                            <td colspan="7">No Commission Approval</td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr
                            v-for="(commission, key) in filteredData"
                            :key="key"
                        >
                            <td>{{ commission.group }}</td>
                            <td>{{ commission.count }}</td>
                            <td>{{ formatDecimal(commission.amount) }}</td>

                            <td
                                v-if="commission.group != 'Total'"
                                class="text-end"
                            >
                                <button
                                    type="button"
                                    class="btn btn-info"
                                    @click="summary(commission)"
                                >
                                    <i class="bi bi-card-checklist"></i>
                                    Details
                                </button>
                            </td>
                            <td v-else></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <summary-commission-approval
        :commission="commissionSelected"
        :modalId="modalIdApproval"
    >
    </summary-commission-approval>
    <dispute-commission-approval
        :modalId="modalIdDispute"
        :disputeReason="disputeReason"
        @return="removeDialogOnReturn"
        @updateDisputeReason = "updateDisputeReason"
    >
    </dispute-commission-approval>

    
    
</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";
import SummaryCommissionApproval from "../../components/CMS/SummaryCommissionApproval";
import { DatePicker } from "v-calendar";
import "v-calendar/dist/style.css";
import { isEmpty } from "lodash";
import RemoveDialog from "../../components/RemoveDialog";
import DisputeCommissionApproval from "../../components/CMS/DisputeCommissionApproval";

export default {
    mixins: [util],
    emits: ["events"],
    components: { VTooltip, SummaryCommissionApproval, DatePicker, RemoveDialog, DisputeCommissionApproval },
    watch: {
        reference: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
    },
    data() {
        return {
            references: [],
            commissions: [],
            commissionsApproval: [],
            reference: "",
            search: "",
            modalIdApproval: "summaryCommissionApproval",
            commissionSelected: {},
            modalIdDispute: "agentDispute",
            disputeReason: '',
        };
    },

    mounted() {
        this.getData()
        this.references = this.getReferencePeriods(4)
        if (this.references.length > 0) {
            this.reference = this.references[0].id; // Set the first option value as default
        }
    },

    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase();
            var data = this.commissions;
            data = data.filter(function (row) {
                return Object.keys(row).some(function (key) {
                    return String(row[key]).toLowerCase().indexOf(search) > -1;
                });
            });
            return data;
        },
    },
    methods: {
        getCurrentMonthAndYear() {
            const currentDate = new Date();
            this.currentMonth = currentDate.toLocaleString("default", {
                month: "long",
            });
            this.currentYear = currentDate.getFullYear();
        },
        getReferences: function () {
            if (this.reference === "") {
                let date = new Date();
                date.setMonth(date.getMonth());
                let month = date.getMonth() + 1;
                let monthName = this.monthNames[date.getMonth()].value;
                let year = date.getFullYear();
                let dataAux = "'" + year + "-" + month + "-" + 1 + "'";
                this.references.push({
                    id: dataAux,
                    name: monthName + " / " + date.getFullYear(),
                });

                for (let i = 0; i < 4; i++) {
                    date.setMonth(date.getMonth() - 1);
                    let month = date.getMonth() + 1;
                    let monthName = this.monthNames[date.getMonth()].value;
                    if (month == 0) {
                        month = 12;
                    }
                    let year = date.getFullYear();
                    let dataAux = "'" + year + "-" + month + "-" + 1 + "'";
                    this.references.push({
                        id: dataAux,
                        name: monthName + " / " + date.getFullYear(),
                    });
                }
            }
        },
        getData: function () {
            this.showPreLoader();

            let referenceDate = new Date(this.reference);

            let data = {
                referenceDate: referenceDate,
            };

            this.axios({
                method: "post",
                url: "web/cms/commission-approval",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.commissions = response.data.data.commissions;
                    this.commissionsApproval = response.data.data.commissionsApproval;
                } else {

                    this.commissions = [];
                    this.commissionsApproval = [];

                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        approve: function () {
            if (isEmpty(this.reference)) {
                this.alertMessage = "Reference must be informed!";
                this.showAlert("error");
                return;
            }

            this.showPreLoader();
            let referenceDate = new Date(this.reference);


            let data = {
                referenceDate: referenceDate,
            };

            this.axios({
                method: "post",
                url: "web/cms/save-commission-approval",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData();
                    console.log(
                        "inside save commission approve",
                        response.data.data
                    );
                }
                this.alertMessage = response.data.message;
                this.showAlert(response.data.status);
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        updateDisputeReason: function (disputeReason) {
            this.disputeReason = disputeReason;
        },
        dispute: function (){
            //open a modal to dispute
            console.log("inside dispute", this.modalIdDispute)
            // this.event = this.modalIdDispute
            this.showModal("agentDispute");
        },
        removeDialogOnReturn: function (returnRemoveReason) {
            if (returnRemoveReason != "") {
                this.showPreLoader();
                    let referenceDate = new Date(this.reference);

                let data = {
                    agent: this.commissions[0].agentName,
                    removeReason: returnRemoveReason,
                    referenceDate: referenceDate,
                };
                console.log("inside remove dialog on return", data)
                this.axios({
                    method: "post",
                    url: "web/cms/dispute-agent",
                    data: data,
                })
                .then((response) => {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                    this.getData();
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });

            } else {
                this.alertMessage = "Cancel reason must be informed!";
                this.showAlert("error");
            }
        },
        summary: function (commission) {
            console.log("inside commission approval", commission);
            this.commissionSelected = { ...commission };
            this.showModal("summaryCommissionApproval");
        },
    },
}
</script>
