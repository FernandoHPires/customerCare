<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Renewals For Approval
            </li>
        </ol>
    </nav>
    
    <!-- Broker Requested Renewals -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div>Renewals for Approval</div>
            </div>
        </div>

        <div class = "card-body table-responsive">
            <!-- Approved / Rejected Renewals For Approval Headers -->
            <ul class="nav nav-tabs" id="renewals-for-approval-tablist" role="tablist">
                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link active"
                        id="renewals-for-approval-tablist-1-tab"
                        data-coreui-toggle="tab"
                        href="#renewals-for-approval-tablist-1"
                        role="tab"
                        aria-controls="renewals-for-approval-tablist-1"
                        aria-selected="true"
                    >Renewal ({{ formatNumber(assignedRenewals.length) }})</a>
                </li>

                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link"
                        id="renewals-for-approval-tablist-2-tab"
                        data-coreui-toggle="tab"
                        href="#renewals-for-approval-tablist-2"
                        role="tab"
                        aria-controls="renewals-for-approval-tablist-2"
                        aria-selected="false"
                    >Non Renewal ({{ formatNumber(assignedNonRenewals.length) }})</a>
                </li>
            </ul>

            <div class="tab-content" id="renewalsForApprovalTabContent">
                <!-- Renewals -->
                <div
                    class="tab-pane show active fade table-responsive px-0"
                    id="renewals-for-approval-tablist-1"
                    role="tabpanel"
                    aria-labelledby="renewals-for-approval-tablist-1-tab"
                    style="max-height: 70dvh;"
                >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Acct #</th>
                                <th class="text-center">Last Name</th>
                                <th class="text-center table-cell-max-width">City</th>
                                <th class="text-center">Province</th>
                                <th class="text-center">Position</th>
                                <th class="text-center">Term Due Date</th>
                                <th class="text-center table-cell-max-width">Coll Status</th>
                                <th class="text-center">Investor</th>
                                <th class="text-center">Orig Rate</th>
                                <th class="text-center">Current Rate</th>
                                <th class="text-center">New Rate</th>
                                <th class="text-center">Old Payment</th>
                                <th class="text-center">New Payment</th>
                                <th class="text-center table-cell-max-width">Comments</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>

                        <tbody v-if="assignedRenewals.length == 0">
                            <tr>
                                <td class="text-nowrap px-2 py-1" colspan="26">No Renewals for Approval</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in assignedRenewals" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">
                                    <a 
                                        class="text-danger cursor-pointer text-decoration-none" 
                                        @click="investorCardLink(renewal)
                                    ">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newInterestRate ? `${renewal.newInterestRate}%` : 'N/A' }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newMonthlyPayment == null ? 'N/A' : `$${formatDecimal(renewal.newMonthlyPayment)}` }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-nowrap px-2 py-1 d-flex flex-row align-items-center justify-content-end gap-2 bg-transparent">
                                    <button
                                        type="button"
                                        class="btn btn-success"
                                        @click="brokerApproval(renewal)"
                                    >
                                        <i class="bi-check2-circle me-1"></i>Approve
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="viewDocuments(renewal)"
                                    >
                                        <i class="bi-files me-1"></i>Documents
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Non Renewals -->
                <div
                    class="tab-pane fade table-responsive px-0"
                    id="renewals-for-approval-tablist-2"
                    role="tabpanel"
                    aria-labelledby="renewals-for-approval-tablist-2-tab"
                    style="max-height: 70dvh;"
                >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Acct #</th>
                                <th class="text-center">Last Name</th>
                                <th class="text-center table-cell-max-width">City</th>
                                <th class="text-center">Province</th>
                                <th class="text-center">Position</th>
                                <th class="text-center">Term Due Date</th>
                                <th class="text-center table-cell-max-width">Coll Status</th>
                                <th class="text-center">Investor</th>
                                <th class="text-center">Orig Rate</th>
                                <th class="text-center">Current Rate</th>
                                <th class="text-center">Old Payment</th>
                                <th class="text-center table-cell-max-width">Comments</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>

                        <tbody v-if="assignedNonRenewals.length == 0">
                            <tr>
                                <td class="text-nowrap px-2 py-1" colspan="26">No Non Renewals for Approval</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in assignedNonRenewals" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">
                                    <a 
                                        class="text-danger cursor-pointer text-decoration-none" 
                                        @click="investorCardLink(renewal)
                                    ">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width" style="max-width: 220px;">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-nowrap px-2 py-1 d-flex flex-row align-items-center justify-content-end gap-2 bg-transparent">
                                    <button
                                        type="button"
                                        class="btn btn-success"
                                        @click="brokerApproval(renewal)"
                                    >
                                        <i class="bi-check2-circle me-1"></i>Approve
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="viewDocuments(renewal)"
                                    >
                                        <i class="bi-files me-1"></i>Documents
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationDialog
        :event="brokerApprovalEvent"
        :message="brokerApprovalDialogMessage"
        type="success"
        :parentModalId="brokerApprovalModalId"
        :key="brokerApprovalModalId"
        @return="brokerApprovalResponse"
    />
</template>

<script>
import { util } from '../../mixins/util';
import ConfirmationDialog from "../../components/ConfirmationDialog.vue";

export default {
    mixins: [util],   
    components : { ConfirmationDialog },
    emits: ['events'],
    data() {
        return {
            brokerApprovalDialogMessage: false,
            brokerApprovalEvent: null,
            brokerApprovalRenewal: null,
            brokerApprovalModalId: "brokerApproval",
            assignedRenewals: [],
            assignedNonRenewals: []
        }
    },
    mounted() {
        this.getBrokerRequestedRenewals()
    },
    computed: {
    },
    watch: {
    },
    methods: {
        getBrokerRequestedRenewals: function() {
            this.showPreLoader()

            this.axios.get('/web/renewals/broker-requested')
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.assignedRenewals = response.data.data.assignedRenewals
                    this.assignedNonRenewals = response.data.data.assignedNonRenewals
                } else {
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
        },
        viewDocuments: function (renewalObj) {
            // Sequence Application
            if(renewalObj.companyId == 701) {
                window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/SQCTACL/" + renewalObj.applicationId, "_blank");
            // Alpine Application
            } else {
                window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/ACLTACL/" + renewalObj.applicationId, "_blank");
            }
        },
        brokerApproval: function(renewalObj) {
            this.brokerApprovalDialogMessage = "Are you sure you want to approve this renewal?"
            this.brokerApprovalEvent = 'brokerApproval';
            this.brokerApprovalRenewal = renewalObj
            this.showModal("confirmationDialog" + this.brokerApprovalModalId);
        },
        brokerApprovalResponse(event, status) {
            if (status !== 'confirmed') {
                return;
            }

            let emailObj = {
                toAddress: ["adam@amurgroup.ca", "joy@amurgroup.ca" ],
                subject: "Broker Approval",
                bodyType: "html",
                body: "<p>This mortgage ( Application ID: " + this.brokerApprovalRenewal.applicationId + ", Mortgage Id: " + this.brokerApprovalRenewal.acctNumber + " ) renewal has been approved</p>",
            }

            let data = {
                renewalApprovalId: this.brokerApprovalRenewal.renewalApprovalId,
                emailObj: emailObj,
            }

            this.showPreLoader()

            this.axios.put(
                'web/renewals/broker-approval',
                data
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getBrokerRequestedRenewals()
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
        investorCardLink: function(renewalObj) {
            window.open('https://tacl-dev-2.amurfinancial.group/TACL/TACL_live/index.php?mortgageId=' + renewalObj.mortgageId + '&userId=' + renewalObj.userId, '_blank', 'noopener,noreferrer');
        },
        colorDate: function(inputDate) {
            const date = new Date(inputDate);
            const todayDate = new Date();

            const diffTime = todayDate - date;
            const diffDate = Math.round(diffTime/(1000 * 60 * 60 * 24))

            if(diffDate >= 1 && diffDate <=2000) {
                return 'red';
            } else if(diffDate >= 0 && diffDate < 1 ) {
                return 'blue';
            } else if(diffDate >= -40 && diffDate < 0 ) {
                return 'green';
            } else if(diffDate < -40 ) {
                return 'black';
            } else {
                return 'inherit';
            }
        },
        colorRow: function(renewalObj) {
            if(renewalObj.payoutCount > 0) {
                return '#C080C0' // purple
            } else if(renewalObj.noteCount > 0) {
                return '#FFD280' // orange
            } else {
                return '#FFFFFF'
            }
        }
    }
}
</script>

<style scoped>

.table-cell-max-width {
    max-width: 120px;
}

</style>