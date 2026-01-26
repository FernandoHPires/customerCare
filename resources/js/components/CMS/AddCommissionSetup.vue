<template>
    <div class="modal fade" :id="modalId" data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" style="display: none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Commission Setup</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="close()"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label>Type</label>
                        <select id="type" class="form-select" v-model="name">
                            <option value="0"></option>
                            <option
                                v-for="(type, key) in types"
                                :key="key"
                                :value="type.id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="form-group">Effective At</label>
                        <select class="form-select" v-model="reference">
                            <option
                                v-for="(r, key) in references"
                                :key="key"
                                :value="r.id"
                            >{{ r.name }}</option>
                        </select>
                    </div>
                    <div v-if="name === 12">
                        <div class="form-group mb-2">
                            <label>Guaranteed Commission Amount</label>
                            <input
                                id="amount"
                                type="number"
                                class="form-control"
                                v-model="amount"/>
                        </div>
                    </div>
                    <div v-else>
                        <div class="form-group mb-2">
                            <label>Amount per Deal</label>
                            <input
                                id="amount"
                                type="number"
                                class="form-control"
                                v-model="amount"/>
                        </div>

                        <div class="form-group mb-2">
                            <label>Percentage per Deal</label>
                            <input
                                id="percentage"
                                type="number"
                                class="form-control"
                                v-model="percentage"/>
                        </div>
                    </div>    

                    
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-outline-dark"
                        type="button"
                        @click="close()"
                    >
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="save()"
                    >
                        <i class="bi-save me-1"></i>Save
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
    emits: ["events", "refresh"],
    data() {
        return {
            name: 0,
            amount: 0,
            percentage: 0,
            modalId: "cmsAddCommissionSetup",
            types: [],
            references: [],
            guaranteedCommissionStart: null,
            guaranteedCommissionEnd: null,
        };
    },
    mounted() {
        this.getData()
        this.getPeriods()
    },
    methods: {
        getPeriods: function() {
            let date = new Date();

            date.setMonth(date.getMonth() - 1);
            this.addPeriod(date);

            date = new Date();
            this.addPeriod(date);

            for (let i = 0; i < 4; i++) {
                date.setMonth(date.getMonth() + 1);
                this.addPeriod(date);
            }
        },

        addPeriod: function(date) {
            let month = date.getMonth() + 1;
            let monthName = this.monthNames[date.getMonth()].value;
            let year = date.getFullYear();
            let dataAux = `'${year}-${month}-1'`;

            this.references.push({
                id: dataAux,
                name: `${monthName} / ${year}`,
            });
        },  

        getData: function() {
            this.axios({
                method: "get",
                url: "/web/cms/types",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.types = response.data.data;
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            });
        },
        save: function () {

            let effectiveAt = new Date(this.reference);

            if (this.name === 0) {
                this.alertMessage = "Type must be informed!";
                this.showAlert("error");
                return;
            }

            if (isNaN(effectiveAt.getTime())) {
                this.alertMessage = "Effective at must be informed!";
                this.showAlert("error");
                return;
            }

            if (this.name === 12) {

                if (this.amount === null) {
                    this.amount = 0
                }

                this.percentage = 0;

            } else {

                if (this.percentage <= 0 && this.amount <= 0) {
                    this.alertMessage = "Percentage or Amount must be informed!";
                    this.showAlert("error");
                    return; 
                }

                if (this.amount === null || this.amount <= 0) {
                    this.alertMessage = "Amount must be informed!";
                    this.showAlert("error");
                    return;
                }

                if (this.percentage === null || this.percentage <= 0) {
                    this.alertMessage = "Percentage must be informed!";
                    this.showAlert("error");
                    return;
                }

            }           

            let data = {
                cmsTypeId: this.name,
                effectiveAt: effectiveAt,
                amount: this.amount,
                percentage: this.percentage
            };

            this.showPreLoader();

            this.axios({
                method: "post",
                url: "web/cms/setup",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.name = "";
                    this.reference = "";
                    this.amount = "";
                    this.percentage = "";
                    this.hideModal(this.modalId);
                    this.$emit("refresh");
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
        close: function () {
            this.name = "";
            this.reference = "";
            this.amount = "";
            this.percentage = "";
            this.hideModal(this.modalId);
        },
    },
};
</script>
