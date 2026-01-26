<template>
    <div class="modal fade" :id="modalId" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="highlightModalLabel">Filter Options</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-end mb-3">
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

                    <!-- Origination Company Input -->
                    <fieldset class="mb-3">
                        <div class="accordion" id="accordion-origination-company-highlight">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.originationCompanyNames || localHighlightFilters.originationCompanyNames.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-origination-company-highlight"
                                        :aria-expanded="(localHighlightFilters.originationCompanyNames && localHighlightFilters.originationCompanyNames.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-origination-company-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
                                            <span class="fw-bold">
                                                Origination Company
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('originationCompanyNames')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('originationCompanyNames')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-origination-company-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.originationCompanyNames && localHighlightFilters.originationCompanyNames.length > 0 }" data-coreui-parent="#accordion-origination-company-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(originationCompanyName, key) in originationCompanyNames" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`originationCompanyName-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.originationCompanyNames"
                                                :value="originationCompanyName"
                                            />
                                            <label :for="`originationCompanyName-${key}`" class="form-check-label">{{ originationCompanyName }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Province Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-province-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.provinces || localHighlightFilters.provinces.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-province-highlight"
                                        :aria-expanded="(localHighlightFilters.provinces && localHighlightFilters.provinces.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-province-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Provinces
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('provinces')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('provinces')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-province-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.provinces && localHighlightFilters.provinces.length > 0 }" data-coreui-parent="#accordion-province-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(province, key) in provinces" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`province-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.provinces"
                                                :value="province"
                                            />
                                            <label :for="`province-${key}`" class="form-check-label">{{ province }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Property Type Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-property-type-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.propertyTypes || localHighlightFilters.propertyTypes.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-property-type-highlight"
                                        :aria-expanded="(localHighlightFilters.propertyTypes && localHighlightFilters.propertyTypes.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-property-type-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Property Types
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('propertyTypes')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('propertyTypes')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-property-type-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.propertyTypes && localHighlightFilters.propertyTypes.length > 0 }" data-coreui-parent="#accordion-property-type-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(propertyType, key) in propertyTypes" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`propertyType-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.propertyTypes"
                                                :value="propertyType"
                                            />
                                            <label :for="`propertyType-${key}`" class="form-check-label">{{ propertyType }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- House Style Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-house-style-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.houseStyles || localHighlightFilters.houseStyles.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-house-style-highlight"
                                        :aria-expanded="(localHighlightFilters.houseStyles && localHighlightFilters.houseStyles.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-house-style-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                House Styles
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('houseStyles')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('houseStyles')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-house-style-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.houseStyles && localHighlightFilters.houseStyles.length > 0 }" data-coreui-parent="#accordion-house-style-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(houseStyle, key) in houseStyles" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`houseStyle-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.houseStyles"
                                                :value="houseStyle"
                                            />
                                            <label :for="`houseStyle-${key}`" class="form-check-label">{{ houseStyle }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Positions Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-positions-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.positions || localHighlightFilters.positions.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-positions-highlight"
                                        :aria-expanded="(localHighlightFilters.positions && localHighlightFilters.positions.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-positions-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Position
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('positions')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('positions')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-positions-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.positions && localHighlightFilters.positions.length > 0 }" data-coreui-parent="#accordion-positions-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(position, key) in positions" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`position-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.positions"
                                                :value="position"
                                            />
                                            <label :for="`position-${key}`" class="form-check-label">{{ position }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Coll Status Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-coll-status-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.collStatuses || localHighlightFilters.collStatuses.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-coll-status-highlight"
                                        :aria-expanded="(localHighlightFilters.collStatuses && localHighlightFilters.collStatuses.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-coll-status-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Coll Status
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('collStatuses')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('collStatuses')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-coll-status-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.collStatuses && localHighlightFilters.collStatuses.length > 0 }" data-coreui-parent="#accordion-coll-status-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(collStatus, key) in collStatuses" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`collStatus-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.collStatuses"
                                                :value="collStatus"
                                            />
                                            <label :for="`collStatus-${key}`" class="form-check-label">{{ collStatus }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Flag Input -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-flag-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.flags || localHighlightFilters.flags.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-flag-highlight"
                                        :aria-expanded="(localHighlightFilters.flags && localHighlightFilters.flags.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-flag-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Flag
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('flags')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('flags')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-flag-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.flags && localHighlightFilters.flags.length > 0 }" data-coreui-parent="#accordion-flag-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(flag, key) in flags" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`flag-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.flags"
                                                :value="flag"
                                            />
                                            <label :for="`flag-${key}`" class="form-check-label">{{ flag }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Other Mortgagee Inputs -->
                    <fieldset class="mb-3">
                        <div class = "accordion" id="accordion-other-mortgagee-highlight">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localHighlightFilters.otherMortgagees || localHighlightFilters.otherMortgagees.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-other-mortgagee-highlight"
                                        :aria-expanded="(localHighlightFilters.otherMortgagees && localHighlightFilters.otherMortgagees.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-other-mortgagee-highlight"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-start w-50">
                                            <span class="fw-bold">
                                                Other Mortgagee
                                            </span>
                                
                                            <span>
                                                <button class="btn btn-outline-dark btn-sm" @click="generalCheckAll('otherMortgagees')" type="button" :id="`closure-type-all`">
                                                    <i class="bi bi-check2-square me-1"></i>Select All
                                                </button>

                                                <button class="btn btn-outline-dark btn-sm ms-2" @click="generalUncheckAll('otherMortgagees')" type="button" :id="`closure-type-none`">
                                                    <i class="bi bi-square me-1"></i>Deselect All
                                                </button>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-other-mortgagee-highlight" class="accordion-collapse collapse" :class="{ show: localHighlightFilters.otherMortgagees && localHighlightFilters.otherMortgagees.length > 0 }" data-coreui-parent="#accordion-other-mortgagee-highlight" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(otherMortgagee, key) in otherMortgagees" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`otherMortgagee-${key}`"
                                                class="form-check-input"
                                                v-model="localHighlightFilters.otherMortgagees"
                                                :value="otherMortgagee"
                                            />
                                            <label :for="`otherMortgagee-${key}`" class="form-check-label">{{ otherMortgagee }}</label>
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
import AutoComplete from "../../components/AutoComplete.vue"

export default {
    mixins: [util],
    components: { 
        AutoComplete
    },
    props : {
        newRenewalsTab: {
            type: Array,
            default: () => []
        },
        highlightFilters: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ["events", "applyHighlight"],
    data () {
        return {
            modalId: 'highlightModal',
            filteredData: [],
            localHighlightFilters: {        
                provinces: [],
                propertyTypes: [],
                houseStyles: [],
                positions: [],
                collStatuses: [],
                flags: [],
                originationCompanyNames: [],
                otherMortgagees: []
            },
            provinces: [],
            propertyTypes: [],
            houseStyles: [],
            positions: [],
            collStatuses: [],
            otherMortgagees: [],
            flags: [],
            originationCompanyNames: []
        }
    },
    mounted() {
        this.$nextTick(() => {
            const modalEl = document.getElementById(this.modalId);
            if(modalEl) {
                modalEl.addEventListener('shown.coreui.modal', () => {   
                    
                    this.localHighlightFilters = { ...this.highlightFilters };

                    this.filteredData = JSON.parse(JSON.stringify(this.newRenewalsTab));
                    
                    this.getFilterOptions()
                });
            }
        });
    },  
    methods: {
        getFilterOptions: function() {
            for (let i = 0; i < this.filteredData.length; i++) {
                for(let k = 0; k < this.filteredData[i].data.length; k++) {
                    if(!this.provinces.includes(this.filteredData[i].data[k].province)) {
                        if(this.filteredData[i].data[k].province !== "") {
                            this.provinces.push(this.filteredData[i].data[k].province)
                        }
                    }
                    
                    if(!this.propertyTypes.includes(this.filteredData[i].data[k].propertyType)) {
                        if(this.filteredData[i].data[k].propertyType !== "") {
                            this.propertyTypes.push(this.filteredData[i].data[k].propertyType)
                        }
                    }

                    if(!this.houseStyles.includes(this.filteredData[i].data[k].houseStyle)) {
                        if(this.filteredData[i].data[k].houseStyle !== "") {
                            this.houseStyles.push(this.filteredData[i].data[k].houseStyle)
                        }
                    }
                    
                    if(!this.positions.includes(this.filteredData[i].data[k].pos)) {
                        if(this.filteredData[i].data[k].pos !== "") {
                            this.positions.push(this.filteredData[i].data[k].pos)
                        }
                    }

                    if(!this.collStatuses.includes(this.filteredData[i].data[k].collStatus)) {
                        if(this.filteredData[i].data[k].collStatus !== "") {
                            this.collStatuses.push(this.filteredData[i].data[k].collStatus)
                        }
                    }
                    
                    if(!this.flags.includes(this.filteredData[i].data[k].flag)) {
                        if(this.filteredData[i].data[k].flag !== "") {
                            this.flags.push(this.filteredData[i].data[k].flag)
                        } 
                    }

                    if(!this.originationCompanyNames.includes(this.filteredData[i].data[k].originationCompanyName)) {
                        if(this.filteredData[i].data[k].originationCompanyName !== "") {
                            this.originationCompanyNames.push(this.filteredData[i].data[k].originationCompanyName)
                        }
                    }

                    if(!this.otherMortgagees.includes(this.filteredData[i].data[k].otherMortgage)) {
                        if(this.filteredData[i].data[k].otherMortgage !== "") {
                            this.otherMortgagees.push(this.filteredData[i].data[k].otherMortgage)
                        }
                    }
                }
            }
        },
        parseLocalDate(str) {
            const [year, month, day] = str.split('-');
            return new Date(year, month - 1, day);
        },
        applyFilters() {
            this.$emit("applyHighlight", this.localHighlightFilters);
            this.hideModal(this.modalId)
            return;
        },
        generalCheckAll(key) {
            this.localHighlightFilters[key] = [...this[key]];
        },
        generalUncheckAll(key) {
            this.localHighlightFilters[key] = [];
        },
        resetFilter() {
            this.localHighlightFilters = {
                provinces: [],
                propertyTypes: [],
                houseStyles: [],
                positions: [],
                collStatuses: [],
                flags: [],
                originationCompanyNames: [],
                otherMortgagees: []
            };
        }
    }
}
</script>
