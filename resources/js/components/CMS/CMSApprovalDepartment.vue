
<template>
    <div class="card-body">
        <div class="card mb-4" v-if="this.pendingAgents == true && accounting.length > 0">
            <div class="card-header text-white bg-danger">
                <div class="d-flex align-items-center">
                    <div>
                        CMS Alert
                    </div>
                </div>
            </div>
            <div class="card-body">
                There are pending approvals for agents or commission setup! Please check before the calculation.
            </div>
        </div>       

        <div class="row mb-3">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Filters</div>
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="pe-2">
                                <label class="form-label">Month</label>
                                <select class="form-select" v-model="selectedMonth">
                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                        {{ month.description }}
                                    </option>
                                </select>
                            </div>

                            <div class="pe-2">
                                <label class="form-label">Year</label>
                                <select class="form-select" v-model="selectedYear">
                                    <option v-for="year in years" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>                            
                            <div>
                                <label class="form-label">Company</label>
                                <select class="form-select" v-model="company" @change="handleCompanyChange">
                                    <option
                                        v-for="(company, key) in companies" :key="key" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card" style="height: 127.5px">
                    <div class="card-header">Actions</div>
                    <div class="card-body">
                        <div class="d-flex" style="position: relative; top: 20%">
                            <div class="pe-2" v-if="accounting?.length > 0 ">
                                <button
                                    type="button"
                                    class="btn"
                                    :class="company !== '' && reference !== '' && !allApproved ? 'btn-success' : 'btn-success disabled'"
                                    @click="refresh()"
                                    :disabled="allApproved"
                                >
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Calculate Commission
                                </button>
                            </div>
                            <div class="d-flex">
                                <div v-if="accounting?.length > 0">
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="company !== '' && reference !== '' ? 'btn-primary' : 'btn-primary disabled'"
                                        @click="reviewedAll()"
                                    >
                                        <i class="bi-check-all me-1"></i>Review All
                                    </button>
                                    <button class="btn ms-2" 
                                        :class="company !== '' && reference !== '' ? 'btn-outline-success' : 'btn-outline-success disabled'"
                                        type="button" @click="download">
                                        <i class="bi-filetype-csv me-1"></i>Download CSV
                                    </button>
                                </div>

                                <div v-if="managers?.length > 0">
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="company !== '' && reference !== '' ? 'btn-primary' : 'btn-primary disabled'"
                                        @click="reviewedAll()"
                                    >
                                        <i class="bi-check-all me-1"></i>Approve All
                                    </button>
                                </div>

                                <div v-if="executives?.length > 0">
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="getAllApprovalStatus ? 'btn-primary' : 'btn-primary disabled'"
                                        @click="reviewedAll(commission, 'executives')"
                                        :disabled="!readyToApprove"
                                    >
                                        <i class="bi-check-all me-1"></i>Approve All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card cms">
            <div class="card-header">
                <div class="d-flex">
                    <h5>Commission Summary</h5>

                    <div class="ms-auto pe-2">
                        <button class="btn ms-2"
                            :class="company !== '' && reference !== '' ? 'btn-outline-success' : 'btn-outline-success disabled'"
                            type="button"
                            @click="exportColuns"
                        >
                            <i class="bi-filetype-csv me-1"></i>Export
                        </button>
                    </div>

                    <div class="text-end">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi-search"></i></span>
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

            <div class="table-wrapper">
                <table class="table table-hover table-cms-summary">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th v-for="(type, key) in types" :key="key" :colspan="type.id < 7 ? 3 : 2">
                                {{ type.name }}
                            </th>
                            <th colspan="6"></th>
                        </tr>
                        <tr>
                            <th></th>
                            <th v-for="(total, key) in totals" :key="key">
                                {{ total.name }}
                            </th>
                            <th>Total </th>
                            <th>Accounting</th>
                            <th>Manager</th>
                            <th>Agent</th>
                            <th>Executive</th>
                            <th>Reminder</th>
                        </tr>
                    </thead>

                    <tbody v-if="filteredData?.length == 0">
                        <tr>
                            <td class="no-commission">No Commission Summary</td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr v-for="(commission, key) in filteredData" :key="key">
                            <td class="agent-name" @click="showCommissionDetails(commission)">
                                <a href="#" class="link-underline-primary">{{ commission.name }}</a>
                            </td>
                            <template v-for="(column, key) in commission.columns" :key="key">
                                <td>{{ column.count }}</td>
                                <td>{{ formatDecimal(column.amount) }}</td>
                                <td v-if="column.gross !== undefined">{{ formatDecimal(column.gross) }}</td>
                            </template>
                            <td>{{ formatDecimal(commission.total) }}</td>

                            <!-- Accounting -->
                            <td v-if="accounting.length <= 0">
                                <span v-if="commission.accountingStatus === 'A'">Processed</span>
                                <span v-else>Process</span>
                            </td>
                            <td v-else>
                                <button class="btn"
                                    :class="commission?.accountingStatus === 'A' ? 'btn-success disabled' 
                                    :accounting?.length >  0 ? 'btn-primary' : 'btn-primary disabled'"
                                    @click="accountingApproval(commission)"
                                >
                                    {{
                                        commission.accountingStatus === "A"
                                            ? "Processed"
                                            : "Process"
                                    }}
                                </button>
                            </td>

                            <!-- Manager -->
                            <td v-if="managers.length <= 0">
                                <span v-if="commission?.managerStatus === null && commission?.agentStatus === null">
                                    Pending
                                </span>
                                <span v-else-if="commission?.managerStatus === 'A' && commission?.agentStatus !== 'J'">
                                    Approved
                                </span>
                                <span v-else-if="commission?.managerStatus === 'A' && commission?.agentStatus === 'J'">
                                    Dispute
                                </span>
                                <span v-else>Pending</span>
                            </td>
                            <td v-else>
                                <button v-if="commission.managerStatus === 'A' &&  managers?.length  > 0  && commission.agentStatus === 'J'" class="btn btn-success" style="margin-right: 5px;"
                                    @click="managerApproval(commission, commission.agentStatus, 'A')">
                                    Accept Dispute
                                </button>
                                <button
                                    class="btn"
                                    :class="
                                        managers.length <= 0 && (commission.managerStatus == null) ? 'btn-primary disabled' :
                                        (commission.managerStatus === 'A' &&  managers?.length  > 0  && commission.agentStatus !== 'J') ? 'btn-success disabled' :
                                        (commission.managerStatus === 'A' &&  managers?.length <= 0  && commission.agentStatus !== 'J') ? 'btn-success disabled' :
                                        (commission.managerStatus === 'A' &&  managers?.length  > 0  && commission.agentStatus === 'J') ? 'btn-danger' :
                                        (commission.managerStatus === 'A' &&  managers?.length <= 0  && commission.agentStatus === 'J') ? 'btn-danger disabled' :
                                        managers.length >= 0  && commission.managerStatus === null ? 'btn-primary' : 'btn-primary disabled'
                                    "
                                    @click="managerApproval(commission, commission.agentStatus, 'R')"
                                >
                                    {{
                                        commission?.managerStatus === null && commission?.agentStatus === null
                                            ? "Review"
                                            : commission?.managerStatus === "A" && commission?.agentStatus !== "J"
                                            ? "Reviewed"
                                            : commission?.managerStatus === "A" && commission?.agentStatus === "J"
                                            ? "Reject Dispute"
                                            : "Review"
                                    }}
                                </button>
                            </td>

                            <!-- Agent -->
                            <td class="agent-approval">
                                <div :class="commission.agentStatus === 'A'? 'text-success' : 'text-primary'">
                                    <span v-if="commission.agentStatus === 'A'">Approved</span>
                                    <span class="text-danger" v-else-if="commission.agentStatus === 'J'" v-tooltip ="commission.agentDisputeReason">
                                        <i class="bi bi-info-circle"> </i> Dispute
                                    </span>
                                    <span v-else>Pending</span>
                                </div>
                            </td>

                            <!-- Executive -->
                            <td v-if="executives.length <= 0">
                                <span v-if="commission.executiveStatus === 'A'">Approved</span>
                                <span v-else>Pending</span>
                            </td>
                            <td v-else>
                                <button
                                    class="btn"
                                    :class="commission?.executiveStatus === 'A' ? 'btn-success disabled' 
                                    :executives !== null && commission?.accountingStatus !== 'A' || commission?.managerStatus !== 'A' ? 'btn-primary disabled'
                                    :executives !== null && commission?.accountingStatus === 'A' && commission?.managerStatus === 'A' && commission?.agentStatus !== null ? 'btn-primary' : 'btn-primary disabled'"
                                    @click="executiveApproval(commission)"
                                >
                                    {{
                                        commission.executiveStatus === "A"
                                            ? "Approved"
                                            : "Approve"
                                    }}
                                </button>
                            </td>

                            <!-- Send -->
                            <td>
                                <button
                                    class="btn btn-primary"
                                    :class="
                                        commission.accountingStatus === 'A' && commission.managerStatus === 'A'
                                            ? ''
                                            : 'disabled'
                                    "
                                    @click="sendNotificationToAgent(commission)"
                                >
                                    <i class="bi-envelope"></i> Send 
                                </button>
                            </td>
                        </tr>
                        
                        <tr v-if="totalCommissions?.length > 0">
                            <td class="agent-name">Total</td>
                            <template v-for="(total, key) in totalCommissions" :key="key">
                                <td v-if="total.count !== undefined">{{ total.count }}</td>
                                <td v-if="total.amount !== undefined">{{ formatDecimal(total.amount) }}</td>
                                <td v-if="total.gross !== undefined">{{ formatDecimal(total.gross) }}</td>
                                <td v-if="total.total !== undefined">{{ formatDecimal(total.total) }}</td>
                            </template>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <template v-if="agent!==null">
        <CommissionAgentDetails
            :commission="commissionSelected"
            :modalId="modalIdAgent"
            :reference="reference"
            :agent="agent"
        />
    </template>

    <template v-if="reviewAll=true">
        <ApproveAllCommissionDialog
            :modalIdApprove="modalIdApprove"
            :title="'Review/Approve All'"
            :message="'Are you sure you want to review/approve all?'"
            :approvalConfirmation="approvalConfirmation"
            @setApprovalConfirmation="setApprovalConfirmation"
        />
    </template>


</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";
import { DatePicker } from "v-calendar";
import "v-calendar/dist/style.css";
import { forEach, get, isEmpty } from "lodash";
import CommissionAgentDetails from "./CommissionAgentDetails";
import ApproveAllCommissionDialog from "./ApproveAllCommissionDialog";

export default {
    mixins: [util],
    emits: ["events"],
    components: { VTooltip, DatePicker, CommissionAgentDetails, ApproveAllCommissionDialog },
    data() {
        const currentDate = new Date();
        const currentYear = new Date().getFullYear();
        return {
            selectedMonth: currentDate.getMonth() + 1,
            selectedYear: currentYear,
            years: Array.from({ length: 5 }, (_, i) => currentYear - i),
            months: [
                { value: 1, description: "January" },
                { value: 2, description: "February" },
                { value: 3, description: "March" },
                { value: 4, description: "April" },
                { value: 5, description: "May" },
                { value: 6, description: "June" },
                { value: 7, description: "July" },
                { value: 8, description: "August" },
                { value: 9, description: "September" },
                { value: 10, description: "October" },
                { value: 11, description: "November" },
                { value: 12, description: "December" },
            ],            
            companies: [],
            commissions: [],
            columns: [],
            types: [],
            totals: [],
            reference: "",
            company: "",
            count: 0,
            search: "",
            emails: [],
            currentUser: {},
            managers: [],
            accounting: [],
            executives: [],
            agentSetter: [],
            modalIdAgent: "commissionAgentDetails",
            commissionSelected: {},
            agent: null,
            modalIdApprove: "approveAllCommissionDialog",
            reviewAll: false,
            approvalConfirmation: false,
            allApproved: false,
            pendingAgents: false,
            totalCommissions: [],
            readyToApprove: false
        };
    },
    watch: {
        selectedMonth: {
            handler(newValue, oldValue) {
                this.updateReference();
            },
            deep: true,
        },
        selectedYear: {
            handler(newValue, oldValue) {
                this.updateReference();                
            },
            deep: true,
        },
        company: {
            handler(newValue, oldValue) {
                this.updateReference();
                this.getType();
            },
            deep: true,
        },
        agent: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
        approvalConfirmation: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
    },
    mounted() {
        this.updateReference()
        this.getData()
        this.getCurrentUser()
        this.getAccounting()
        this.getManagers()
        this.getExecutives()
    },
    computed: {
        filteredData() {

            var search = this.search && this.search.toLowerCase();
            var data = this.commissions;

            data = data.filter(function (row) {
                
                return Object.keys(row).some(function (key) {
                    if (key === 'columns' && typeof row[key] === 'object') {
                        return Object.values(row[key]).some(function (columnValue) {
                            return String(columnValue).toLowerCase().indexOf(search) > -1;
                        });
                    } else {
                        return String(row[key]).toLowerCase().indexOf(search) > -1;
                    }
                });
            });

            return data;
        },
        getAllApprovalStatus() {

            if (this.company === '' && this.reference === '') {
                this.readyToApprove = false;
                return;
            }
            
            let allApprovedTmp = false;

            for (const commission of this.commissions) {
                if ((commission.agentStatus === "A" && commission.accountingStatus === "A" && commission.managerStatus === "A")) {
                    allApprovedTmp = true;
                    
                }else {
                    allApprovedTmp = false;
                    break;
                }
            }

            if (allApprovedTmp === true) {
                for (const commission of this.commissions) {
                    if (commission.executiveStatus === "A") {
                        allApprovedTmp = false;
                    }else {
                        allApprovedTmp = true;
                        break;   
                    }
                }
            }

            this.readyToApprove = allApprovedTmp;

            return this.readyToApprove;
        }


    },
    methods: {
        updateReference() {

            const year = this.selectedYear;
            const month = String(this.selectedMonth).padStart(2, '0');
            const day = "01";
            this.reference = `${year}-${month}-${day}`;

            this.getData();
        },        
        handleCompanyChange: function () {
            this.getType();
        },

        getData: function () {

            if (this.companies && this.companies.length === 0) {
                this.getCompanies();
            }

            if(this.company === '' || this.reference === ''){
                return;
            }

            this.showPreLoader();

            let referenceDate = this.reference;

            let data = {
                referenceDate: referenceDate,
                company: this.company,
            }

            this.axios({
                method: "post",
                url: "web/cms/commissions",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    this.commissions      = response.data.data.commission
                    this.pendingAgents    = response.data.data.pendingAgents
                    this.totalCommissions = response.data.data.totalCommissions
                    this.allApproved      = response.data.data.allApproved

                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        getCompanies: function () {
            this.axios({
                method: "get",
                url: "web/cms/companies"

            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.companies = response.data.data
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
            })
        },
        download: function() {
            this.showPreLoader()
            let referenceDate = new Date(this.reference);

            let data = {
                company : this.company,
                referenceDate: referenceDate
            }

            this.axios.get(
                '/web/cms/download',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    const link = document.createElement('a')
                    link.href = 'data:text/plain;base64,' + response.data.data.file
                    link.setAttribute('download', response.data.data.fileName)
                    document.body.appendChild(link)
                    link.click()
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
        exportColuns: function() {

            this.showPreLoader()
            let referenceDate = new Date(this.reference);
                    

            let data = {
                types: this.types,
                colunms: this.totalCommissions,
                commission : this.commissions,
                total: this.totalCommissions
            }

            this.axios.post(
                '/web/cms/export',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    const link = document.createElement('a')
                    link.href = 'data:text/plain;base64,' + response.data.data.file
                    link.setAttribute('download', response.data.data.fileName)
                    document.body.appendChild(link)
                    link.click()
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
        

        getType: function () {
            this.showPreLoader();

            let data = {
                company: this.company
            };

            this.axios({
                method: "post",
                url: "web/cms/type",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.types = response.data.data;
                    this.totals = [];
                    this.types.map((x) => {
                        this.totals.push({
                            name: "Count",
                        });
                        this.totals.push({
                            name: "Amount",
                        });
                        if (x.id <= 6) {
                            this.totals.push({
                            name: "Gross Amount",
                        }); 
                        }
                    });
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = "Error";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        refresh: function () {
            if (isEmpty(this.reference)) {
                this.alertMessage = "Reference must be informed!";
                this.showAlert("error");
                return;
            }

            if (this.company === "") {
                this.alertMessage = "Company must be informed!";
                this.showAlert("error");
                return;
            }
            let dateReference = new Date(this.reference);
            this.showPreLoader();

            let data = {
                reference: dateReference,
                company: this.company,
            };

            this.axios({
                method: "post",
                url: "web/cms/calculate-commission",
                data: data,
            })
            .then((response) => {
                this.getData();
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        //send notifications to agent when clicked send reminder or automatically when manager and accounting approves
        sendNotificationToAgent: function (agentData) {

            let referenceDate = new Date(this.reference);

            let data = {
                to: [agentData.email],
                name: agentData.name,
                referenceDate: referenceDate     
            };

            this.showPreLoader();

            this.axios({
                method: "post",
                url: "web/cms/send-notification-agent",
                data: data,
            })
            .then((response) => {
                this.getData();
            })
            .catch((error) => {
                this.alertMessage = "Error";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        setApprovalConfirmation: function () {
            this.approvalConfirmation = true;
            let department;
            if(this.managers?.length > 0){
                department = 'managers';
            }

            if(this.accounting?.length > 0){
                department = 'accounting';
            }

            if(this.executives?.length > 0){
                department = 'executives';
            }

            this.approvedConfirmation(department);
        },
        //approve all records in the table or reviewd all records in the table
        reviewedAll: function() {
            this.reviewAll = true;
            this.showModal("approveAllCommissionDialog");
        },
        approvedConfirmation: function(department) {
            this.showPreLoader()
            
            let data = {
                department: department,
                company: this.company,
                referenceDate: this.reference,
            }

            this.axios({
                method: "put",
                url: "web/cms/department-approval",
                data: data,
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
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

        getCurrentUser: function () {
            this.axios({
                method: "get",
                url: "web/current-user",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.currentUser = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },

        getManagers: function () {
            this.axios({
                method: "get",
                url: "web/cms/managers",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.managers = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },
        getAccounting: function () {
            this.axios({
                method: "get",
                url: "web/cms/accounting",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.accounting = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },
        getExecutives: function () {
            this.axios({
                method: "get",
                url: "web/cms/executives",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.executives = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },
        //individual record manager approval
        managerApproval: function (commission, agentStatus, action) {
            let referenceDate = new Date(this.reference);
            this.showPreLoader();

            if(agentStatus !== 'J') {

                let data = {
                    managerApprovalStatus: 'A', 
                    agentId: commission.agent,
                    referenceDate: referenceDate,
                };

                this.axios({
                    method: "post",
                    url: "web/cms/manager-approval",
                    data: data,
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getData()
                    } else {
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
            } else {

                //manager clicked reject or accept dispute

                let data = {
                    agentId: commission.agent,
                    referenceDate: referenceDate,
                    action: action
                };

                this.axios({
                    method: "post",
                    url: "web/cms/agent-reset-status",
                    data: data,
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getData()
                    } else {
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
            }
        },
        //individual record accounting approval
        accountingApproval: function (commission) {

            let referenceDate = new Date(this.reference);
            this.showPreLoader();

            let data = {
                accountingStatus: 'A',
                agentId: commission.agent,
                referenceDate: referenceDate,
            };

            this.axios({
                method: "post",
                url: "web/cms/accounting-approval",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData()
                } else {
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
        executiveApproval: function (commission) {
            let referenceDate = new Date(this.reference);

            this.showPreLoader();

            let data = {
                executiveStatus: 'A',
                agentId: commission.agent,
                referenceDate: referenceDate,
            };

            this.axios({
                method: "post",
                url: "web/cms/executive-approval",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData()
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = "Error";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        showCommissionDetails: function(commission){
            this.agent = commission.agent;
            this.commissionSelected = commission;
            setTimeout(() => {
                this.showModal("commissionAgentDetails");
            }, 300);


        },
    },
};
</script>

<style scoped>
.table-wrapper {
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: auto;
}

.table thead tr:first-child th {
    position: sticky;
    top: 0;
    background-color: white;
    z-index: 3;
}

.table thead tr:nth-child(2) th {
    position: sticky;
    top: 28px;
    background-color: white;
    z-index: 2;
}

.table tbody td:first-child {
    position: sticky;
    left: 0;
    background-color: white;
    z-index: 1;
}
</style>