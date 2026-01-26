<template>
    <div class="modal fade" :id="modalId" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inProgressFilterModalLabel">Filter Options</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-end">
                        <!-- Reset, filter -->
                        <div class="btn-group" role="group">
                            <button
                                class="btn btn-primary"
                                @click="resetFilter"
                            >
                                <i class="bi bi-arrow-repeat me-1"></i><small>Reset</small>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Term Due Date Inputs -->
                    <div class="mb-3">
                        <label class="label-header">Term Due Date</label>
                        <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <input type="date" class="form-control" v-model="inProgressFilters.termStartDueDateOrdered">
                            </div>

                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="date" class="form-control" v-model="inProgressFilters.termEndDueDateOrdered">
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Names Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-assigned-names-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button collapsed p-2 w-100"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-assigned-names-filter"
                                        aria-expanded="false"
                                        aria-controls="collapse-assigned-names-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
                                            <span class="fw-bold">
                                                Assigned Names
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('assignedNames')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('assignedNames')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-assigned-names-filter" class="accordion-collapse collapse" data-coreui-parent="#accordion-assigned-names-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(assignedName, key) in assignedNames" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`assigned-names-${key}`"
                                                class="form-check-input"
                                                v-model="inProgressFilters.assignedNames"
                                                :value="assignedName"
                                            />
                                            <label :for="`assigned-names-${key}`" class="form-check-label">{{ assignedName }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="applyFilters">
                        <i class="bi-save me-1"></i>Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {util} from '../../mixins/util'

export default {
    mixins: [util],
    components: { 
    },
    props : {
        inProgressRenewals: {
            type: Array,
            default: () => []
        }
    },
    emits: ["events", "applyFilters", "resetFilters"],
    data () {
        return {
            modalId: 'inProgressFilterModal',
            inProgressFilters: {          
                assignedNames: [],
                termStartDueDateOrdered: null,
                termEndDueDateOrdered: null,
            },
            autoCompleteKey: 0,
            filteredData: [],
            assignedNames: [],
        }
    },
    mounted() {
        this.$nextTick(() => {
            const modalEl = document.getElementById(this.modalId);
            if(modalEl) {
                modalEl.addEventListener('shown.coreui.modal', () => {
                    this.filteredData = JSON.parse(JSON.stringify(this.inProgressRenewals));
                    this.getFilterOptions()
                    if(this.autoCompleteKey === 0) {
                        this.autoCompleteKey += 1;
                    }
                });
            }
        });
    },  
    computed: {
    },
    methods: {
        getFilterOptions: function() {
            for (let i = 0; i < this.filteredData.length; i++) {
                for(let k = 0; k < this.filteredData[i].data.length; k++) {
                    
                    if(!this.assignedNames.includes(this.filteredData[i].data[k].assignedName)) {
                        if(this.filteredData[i].data[k].assignedName !== "") {
                            this.assignedNames.push(this.filteredData[i].data[k].assignedName)
                        }
                    }
                }
            }
        },
        applyFilters() {    
            this.$emit("applyFilters", this.inProgressFilters);
            this.hideModal(this.modalId)
        },
        generalCheckAll(key) {
            this.inProgressFilters[key] = [...this[key]];
        },
        generalUncheckAll(key) {
            this.inProgressFilters[key] = [];
        },
        resetFilter() {
            const defaults = {
                assignedNames: [],
                termStartDueDateOrdered: null,
                termEndDueDateOrdered: null,
            };

            if (this.inProgressFilters) {
                Object.keys(defaults).forEach(key => {
                    this.inProgressFilters[key] = defaults[key];
                });
            }

            this.autoCompleteKey += 1;

            this.$emit("resetFilters");
        }
    }
}
</script>
