<template>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Filters</div>

                    <div class="card-body">
                        <div class="d-flex">
                            <div class="pe-2">
                                <label class="form-label">Reference</label>
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

                            <div>
                                <label class="form-label">Company</label>
                                <select class="form-select" v-model="company">
                                    <option
                                        v-for="(company, key) in companies"
                                        :key="key"
                                        :value="company.id"
                                    >
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
                        <div
                            class="d-flex"
                            style="position: relative; top: 20%"
                        >
                            <div class="pe-2">
                                <button
                                    type="button"
                                    class="btn btn-success"
                                    @click="refresh()"
                                >
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card cms">
            <div class="card-header">
                <div class="d-flex">
                    <h5 class="m-0">Commission Summary</h5>

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
                    <table
                        class="table table-hover table-cms-summary table-responsive"
                    >
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th
                                    v-for="(type, key) in types"
                                    :key="key"
                                    colspan="4"
                                >
                                    {{ type.name }}
                                </th>
                                <th>Total Payable to Agent</th>
                                <th>Manager Approval</th>
                                <th>Accounting Approval</th>
                                <th colspan="2">Agent Approval</th>
                            </tr>
                        </thead>

                        <thead>
                            <tr>
                                <th></th>
                                <th v-for="(total, key) in totals" :key="key">
                                    {{ total.name }}
                                </th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody v-if="filteredData.length == 0">
                            <tr>
                                <td colspan="7">No Commission Summary</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr
                                v-for="(commission, key) in filteredData"
                                :key="key"
                            >
                                <td>{{ commission.name }}</td>

                                <template
                                    v-for="(column, key) in commission.columns"
                                    :key="key"
                                >
                                    <td>{{ column.count }}</td>
                                    <td>{{ formatDecimal(column.amount) }}</td>
                                </template>
                                <td>{{ formatDecimal(commission.total) }}</td>
                                <td>
                                    <button
                                        class="btn btn-primary"
                                        :class="
                                            managers === null ? 'disabled' : ''
                                        "
                                        @click="managerApproval(commission)"
                                    >
                                        {{
                                            commission.managerStatus === "A"
                                                ? "Approved"
                                                : "In Review"
                                        }}
                                    </button>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-primary"
                                        :class="
                                            accounting === null
                                                ? 'disabled'
                                                : ''
                                        "
                                        @click="accountingApproval(commission)"
                                    >
                                        {{
                                            commission.accountingStatus === "A"
                                                ? "Approved"
                                                : "In Review"
                                        }}
                                    </button>
                                </td>

                                <td class="d-flex agent-approval">
                                    <span
                                        :class="
                                            commission.agentStatus === 'A'
                                                ? 'text-success'
                                                : 'text-primary'
                                        "
                                    >
                                        {{
                                            commission.agentStatus === "A"
                                                ? "Approved"
                                                : "In Review"
                                        }}
                                    </span>
                                    <button
                                        class="btn btn-primary"
                                        :class="
                                            commission.agentStatus === 'A'
                                                ? 'disabled'
                                                : ''
                                        "
                                        @click="
                                            sendNotificationToAgent(commission)
                                        "
                                    >
                                        Send Reminder
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";
import ConfirmationDialog from "../../components/ConfirmationDialog";
import { DatePicker } from "v-calendar";
import "v-calendar/dist/style.css";
import { forEach, get, isEmpty } from "lodash";

export default {
    mixins: [util],
    emits: ["events"],
    components: { VTooltip, ConfirmationDialog, DatePicker },
    watch: {
        reference: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
        company: {
            handler(newValue, oldValue) {
                this.getData();
            },
            deep: true,
        },
    },
    data() {
        return {
            references: [],
            companies: [
                { id: "ACL", name: "Alpine Credits Limited" },
                { id: "SQC", name: "Sequence Capital" },
            ],
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
            managers: {},
            accounting: {},
            managerStatus: false,
            accountingStatus: "",
            agentStatus: "",
        };
    },

    mounted() {
        this.getType();
        this.getData();
        this.getCurrentUser();
        this.getAccounting();
        this.getManagers();
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase();
            var data = this.commissions;
            var status = this.status;
            data = data.filter(function (row) {
                return Object.keys(row).some(function (key) {
                    return String(row[key]).toLowerCase().indexOf(search) > -1;
                });
            });
            return data;
        },
    },
    methods: {
        getData: function () {

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

            this.showPreLoader();

            let referenceDate = new Date(this.reference);

            let data = {
                referenceDate: referenceDate,
                company: this.company,
            };

            this.axios({
                method: "post",
                url: "web/cms/commissions",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.commissions = response.data.data;
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = "Error"
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },

        getType: function () {
            this.showPreLoader();

            let data = {};

            this.axios({
                method: "get",
                url: "web/cms/type",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.types = response.data.data;
                    this.totals = [];
                    console.log("types", this.types);

                    this.totals = [];
                    this.types.map((x) => {
                        this.totals.push({
                            name: "Count",
                        });
                        this.totals.push({
                            name: "Amount $",
                        });
                        this.totals.push({
                            name: "Count",
                        });
                        this.totals.push({
                            name: "Amount %",
                        });
                    });
                    console.log("totals", this.totals);
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
                this.alertMessage = "Error";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        //send notifications to agent when clicked send reminder or automatically when manager and accounting approves
        sendNotificationToAgent: function (agentData) {
            let data = {
                to: [agentData.email],
                name: agentData.name,
            };

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
        getCurrentUser: function () {
            this.axios({
                method: "get",
                url: "web/current-user",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.currentUser = response.data.data;
                    console.log("current user", this.currentUser);
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
                    console.log("managers", this.managers);
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
                    console.log("accounting managers", this.accounting);
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },

        managerApproval: function (commission) {
            if (commission.managerStatus === null) {
                commission.managerStatus = "A"; // Set to 'Approved'
            } else {
                // Toggle between 'Approved' and 'In Review'
                commission.managerStatus =
                    commission.managerStatus === "A" ? "R" : "A";
            }

            this.commissions.forEach((item, index) => {
                if (item === commission) {
                    this.commissions[index] = commission;
                }
            });

            let referenceDate = new Date(this.reference);

            let data = {
                managerApprovalStatus: commission.managerStatus,
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
            })

            this.getData();
        },
    },
};
</script>
