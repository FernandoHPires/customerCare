<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <h5 class="m-0">Commission Setup</h5>

                <div class="ms-auto me-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="commissiontype()"
                    >
                        <i class="bi-plus-lg me-1"></i>Add
                    </button>
                </div>
                <div class="text-end">
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
        <div class="card-body">
            <h5 class="pb-3">Active Setup</h5>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Effective At</th>
                        <th>Percentage per Deal</th>
                        <th>Amount per Deal</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="7">No Commission Setup</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(commission, key) in filteredData" :key="key">
                        <td>{{ commission.type }}</td>
                        <td>{{ commission.effective }}</td>
                        <td>{{ commission.percentage }}%</td>
                        <td>${{ formatDecimal(commission.amount) }}</td>
                        
                    </tr>
                </tbody>
            </table>
            
            <h5 class="mt-3 pb-3">History Setup</h5>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Effective At</th>
                        <th>Percentage per Deal</th>
                        <th>Amount per Deal</th>                        
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="historySetups.length == 0">
                    <tr>
                        <td colspan="7">No Commission Setup</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(commission, key) in historySetups" :key="key">
                        <td>{{ commission.type }}</td>
                        <td>{{ commission.effective }}</td>
                        <td>{{ commission.percentage }} %</td>
                        <td>${{ formatDecimal(commission.amount) }}</td>
                        
                    </tr>
                </tbody>
            </table>
        </div>

        <!--Modal-->

        <CmsAddCommissionSetup @refresh="getData()" />

        <ConfirmationDialog
            :event="event"
            :message="dialogMessage"
            parentModalId=""
            type="success"
            @return="confirmationDialogOnReturn"
        />
    </div>
</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";
import ConfirmationDialog from "../../components/ConfirmationDialog";
import CmsAddCommissionSetup from "../../components/CMS/AddCommissionSetup";
import { DatePicker } from "v-calendar";
import "v-calendar/dist/style.css";

export default {
    mixins: [util],
    emits: ["events"],
    components: {
        VTooltip,
        ConfirmationDialog,
        CmsAddCommissionSetup,
        DatePicker,
    },

    data() {
        return {
            commissions: [],
            search: "",
            setups: [],
            activeSetups: [],
            historySetups: [],
            dialogMessage : "",
            status: "",
        };
    },

    mounted() {
        this.getData();
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase();
            var data = this.activeSetups;
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
            this.showPreLoader();

            let data = {};

            this.axios({
                method: "get",
                url: "web/cms/commission-setup",
                data: data,
            })

                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.setups = response.data.data.commissions;
                        this.updateActiveAndHistorySetups();
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = "Error";
                    this.showAlert("error", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        updateActiveAndHistorySetups() {
            this.activeSetups = [];
            this.historySetups = [];

            this.setups.forEach((setup) => {
                const existingSetupIndex = this.activeSetups.findIndex(
                    (activeSetup) => activeSetup.type === setup.type
                );

                const currentEffectiveDate = new Date(setup.effective);

                if (existingSetupIndex === -1) {
                    // If the type doesn't exist in activeSetups, add the setup to it
                    this.activeSetups.push(setup);
                } else {
                    const existingEffectiveDate = new Date(
                        this.activeSetups[existingSetupIndex].effective
                    );

                    if (currentEffectiveDate > existingEffectiveDate) {
                        // If the current setup is newer, move the older setup to historySetups
                        this.historySetups.push(
                            this.activeSetups[existingSetupIndex]
                        );
                        //remove the existing setup in activeSetups
                        this.activeSetups.splice(existingSetupIndex, 1, setup);
                    } else {
                        // If the current setup has an older month, move the current setup to historySetups
                        this.historySetups.push(setup);
                    }
                }
            });
        },

        confirmationDialogOnReturn: function (event, returnMessage) {
            this.getData();
        },

        commissiontype: function () {
            this.showModal("cmsAddCommissionSetup");
        },
    },
};
</script>
