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

                    <p class="small">All fields required</p>

                    <div class="form-group mb-2">
                        <label>Type</label>
                        <select v-model="cmsType" class="form-select">
                            <option value="0"></option>
                            <option
                                v-for="(type, i) in types"
                                :key="i"
                                :value="type"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="cmsType?.id === 12">

                        <div class="form-group mb-2">
                            <label class="form-group">Start date</label>
                            <select class="form-select" v-model="effectiveAt">
                                <option
                                    v-for="(reference, key) in references"
                                    :key="key"
                                    :value="reference.id"
                                >
                                    {{ reference.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-group">End date</label>
                            <select class="form-select" v-model="effectiveUntil">
                                <option
                                    v-for="(reference, key) in referencesEnd"
                                    :key="key"
                                    :value="reference.id"
                                >
                                    {{ reference.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label>Guaranteed Commission Amount</label>
                            <input
                                id="amount"
                                type="number"
                                class="form-control"
                                v-model="amount"
                            />
                        </div>

                    </div>

                    <div v-else>
                        
                        <div class="form-group mb-2">
                            <label class="form-group">Effective At</label>
                            <select class="form-select" v-model="effectiveAt">
                                <option
                                    v-for="(reference, key) in references"
                                    :key="key"
                                    :value="reference.id"
                                >
                                    {{ reference.name }}
                                </option>
                            </select>
                        </div>    

                        <div class="form-group">
                            <label>Percentage per Deal</label>
                            <input
                                id="percentage"
                                type="number"
                                class="form-control"
                                v-model="percentage"
                            />
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>Amount per Deal</label>
                            <input
                                id="amount"
                                type="number"
                                class="form-control"
                                v-model="amount"
                            />
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
                        @click="saveClicked()"
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
    emits: ["saveCustom"],
    data() {
        return {
            modalId: "customSetup",
            customAmt: "",
            cmsType: "",
            effectiveAt: "",
            effectiveUntil: "",
            amount: "",
            percentage: "",
            types: [],
        };
    },
    mounted() {
        this.getData()
    },
    computed: {
        references() {

            let date = new Date();
            let arr = [];

            let prevMonthDate = new Date(date.getFullYear(), date.getMonth() - 1, 1);
            arr.push(this.createReferenceObject(prevMonthDate));

            arr.push(this.createReferenceObject(new Date(date.getFullYear(), date.getMonth(), 1)));

            for (let i = 1; i <= 4; i++) {
                let futureMonthDate = new Date(date.getFullYear(), date.getMonth() + i, 1);
                arr.push(this.createReferenceObject(futureMonthDate));
            }

            return arr;
        },
        referencesEnd() {

            let date = new Date();
            let arr = [];

            let prevMonthDate = new Date(date.getFullYear(), date.getMonth() - 1, 1);
            arr.push(this.createReferenceObject(prevMonthDate));

            arr.push(this.createReferenceObject(new Date(date.getFullYear(), date.getMonth(), 1)));

            for (let i = 1; i <= 12; i++) {
                let futureMonthDate = new Date(date.getFullYear(), date.getMonth() + i, 1);
                arr.push(this.createReferenceObject(futureMonthDate));
            }

            return arr;
        },        
        
    },
    methods: {
        createReferenceObject(date) {

            const monthName = this.monthNames[date.getMonth()].value;
            const year = date.getFullYear();
            const id = `${year}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;

            return {
                id: id,
                name: `${monthName} / ${year}`,
            };
        },    
        saveClicked() {


            if (this.cmsType?.id === 12) {

                if (this.effectiveAt === '' || this.effectiveAt === null) {
                    this.alertMessage = "Start date must be informed!";
                    this.showAlert("error");
                    return;   
                }                
                
                if (this.effectiveUntil === '' || this.effectiveUntil === null) {
                    this.alertMessage = "End date must be informed!";
                    this.showAlert("error");
                    return;   
                }

                if (this.amount === "" || this.amount === null || this.amount <= 0) {
                    this.alertMessage = "Amount must be informed!";
                    this.showAlert("error");
                    return;   
                }
                this.percentage = 0;

            }else {

                if (this.effectiveAt === '' || this.effectiveAt === null) {
                    this.alertMessage = "Effective at must be informed!";
                    this.showAlert("error");
                    return;   
                }

                if (this.percentage === "") {
                    this.alertMessage = "Percentage must be informed!";
                    this.showAlert("error");
                    return;   
                }
                if (this.amount === "") {
                    this.alertMessage = "Amount must be informed!";
                    this.showAlert("error");
                    return;   
                }
                if (this.percentage === 0 && this.amount === 0) {
                    this.alertMessage = "Percentage or Amount must be informed!";
                    this.showAlert("error");
                    return; 
                }
            }
            
            let data = {
                type: this.cmsType?.name || "",
                typeId: this.cmsType?.id || "",
                effective: this.effectiveAt,
                effectiveUntil: this.effectiveUntil,
                amount: this.amount,
                percentage: this.percentage,
            };
            (this.cmsType = ""),
                (this.effectiveAt = ""),
                (this.amount = ""),
                (this.percentage = ""),
                this.$emit("saveCustom", data);
            this.hideModal("customSetup");
        },
        close: function () {
            (this.cmsType = ""),
                (this.effectiveAt = ""),
                (this.amount = ""),
                (this.percentage = ""),
                this.hideModal("customSetup");
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
    }
}
</script>
