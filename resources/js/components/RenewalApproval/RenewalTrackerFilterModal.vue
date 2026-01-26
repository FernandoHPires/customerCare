<template>
    <div class="modal fade" :id="modalId" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewalTrackerFilterModalLabel">Filter Options</h5>
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
                                <input type="date" class="form-control" v-model="localRealFilters.termStartDueDateOrdered">
                            </div>

                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="date" class="form-control" v-model="localRealFilters.termEndDueDateOrdered">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- TACL# Input -->
                        <div class="mb-3 flex-grow-1">
                            <div class="label-header">TACL #</div>
                            <AutoComplete
                                :key="autoCompleteKey"
                                :modelValue="localRealFilters.applicationId"
                                :options="applicationIds"
                                :placeholder="'Search an application...'"
                                @update:modelValue="(newValue) => handleAutoCompleteChange(newValue, 'applicationId')"
                            />
                        </div>

                        <!-- Acct# Input -->
                        <div class="mb-3 flex-grow-1">
                            <div class="label-header">Account #</div>
                            <AutoComplete
                                :key="autoCompleteKey"
                                :modelValue="localRealFilters.acctNumbers"
                                :options="acctNumbers"
                                :placeholder="'Search an account #...'"
                                @update:modelValue="(newValue) => handleAutoCompleteChange(newValue, 'acctNumbers')"
                            />
                        </div>
                    </div>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- Last Name Input -->
                        <div class="mb-3 flex-grow-1">
                            <div class="label-header">Last Name</div>
                            <AutoComplete
                                :key="autoCompleteKey"
                                :modelValue="localRealFilters.lastNames"
                                :options="lastNames"
                                :placeholder="'Search an last name...'"
                                @update:modelValue="(newValue) => handleAutoCompleteChange(newValue, 'lastNames')"
                            />
                        </div>

                        <!-- City Input -->
                        <div class="mb-3 flex-grow-1">
                            <div class="label-header">City</div>
                            <AutoComplete
                                :key="autoCompleteKey"
                                :modelValue="localRealFilters.cities"
                                :options="cities"
                                :placeholder="'Search an city...'"
                                @update:modelValue="(newValue) => handleAutoCompleteChange(newValue, 'cities')"
                            />
                        </div>
                    </div>

                    <!-- Origination Company Input -->
                    <fieldset class="mb-3">
                        <div class="accordion" id="accordion-origination-company-filter">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.originationCompanyNames || localRealFilters.originationCompanyNames.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-origination-company-filter"
                                        :aria-expanded="(localRealFilters.originationCompanyNames && localRealFilters.originationCompanyNames.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-origination-company-filter"
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
                                <div id="collapse-origination-company-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.originationCompanyNames && localRealFilters.originationCompanyNames.length > 0 }" data-coreui-parent="#accordion-origination-company-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(originationCompanyName, key) in originationCompanyNames" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`originationCompanyName-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.originationCompanyNames"
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
                        <div class = "accordion" id="accordion-province-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.provinces || localRealFilters.provinces.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-province-filter"
                                        :aria-expanded="(localRealFilters.provinces && localRealFilters.provinces.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-province-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-province-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.provinces && localRealFilters.provinces.length > 0 }" data-coreui-parent="#accordion-province-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(province, key) in provinces" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`province-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.provinces"
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
                        <div class = "accordion" id="accordion-property-type-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.propertyTypes || localRealFilters.propertyTypes.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-property-type-filter"
                                        :aria-expanded="(localRealFilters.propertyTypes && localRealFilters.propertyTypes.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-property-type-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-property-type-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.propertyTypes && localRealFilters.propertyTypes.length > 0 }" data-coreui-parent="#accordion-property-type-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(propertyType, key) in propertyTypes" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`propertyType-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.propertyTypes"
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
                        <div class = "accordion" id="accordion-house-style-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.houseStyles || localRealFilters.houseStyles.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-house-style-filter"
                                        :aria-expanded="(localRealFilters.houseStyles && localRealFilters.houseStyles.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-house-style-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-house-style-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.houseStyles && localRealFilters.houseStyles.length > 0 }" data-coreui-parent="#accordion-house-style-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(houseStyle, key) in houseStyles" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`houseStyle-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.houseStyles"
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
                        <div class = "accordion" id="accordion-positions-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.positions || localRealFilters.positions.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-positions-filter"
                                        :aria-expanded="(localRealFilters.positions && localRealFilters.positions.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-positions-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-positions-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.positions && localRealFilters.positions.length > 0 }" data-coreui-parent="#accordion-positions-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(position, key) in positions" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`position-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.positions"
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
                        <div class = "accordion" id="accordion-coll-status-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.collStatuses || localRealFilters.collStatuses.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-coll-status-filter"
                                        :aria-expanded="(localRealFilters.collStatuses && localRealFilters.collStatuses.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-coll-status-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-coll-status-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.collStatuses && localRealFilters.collStatuses.length > 0 }" data-coreui-parent="#accordion-coll-status-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(collStatus, key) in collStatuses" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`collStatus-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.collStatuses"
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
                        <div class = "accordion" id="accordion-flag-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.flags || localRealFilters.flags.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-flag-filter"
                                        :aria-expanded="(localRealFilters.flags && localRealFilters.flags.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-flag-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-flag-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.flags && localRealFilters.flags.length > 0 }" data-coreui-parent="#accordion-flag-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(flag, key) in flags" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`flag-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.flags"
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
                        <div class = "accordion" id="accordion-other-mortgagee-filter">
                            <div class = "accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button p-2 w-100"
                                        :class="{ collapsed: !localRealFilters.otherMortgagees || localRealFilters.otherMortgagees.length === 0 }"
                                        type="button"
                                        data-coreui-toggle="collapse"
                                        data-coreui-target="#collapse-other-mortgagee-filter"
                                        :aria-expanded="(localRealFilters.otherMortgagees && localRealFilters.otherMortgagees.length > 0) ? 'true' : 'false'"
                                        aria-controls="collapse-other-mortgagee-filter"
                                        style="background: none; box-shadow: none;"
                                    >
                                        <div class="d-flex flex-row justify-content-between align-items-center w-50">
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
                                <div id="collapse-other-mortgagee-filter" class="accordion-collapse collapse" :class="{ show: localRealFilters.otherMortgagees && localRealFilters.otherMortgagees.length > 0 }" data-coreui-parent="#accordion-other-mortgagee-filter" >
                                    <div class="d-flex flex-wrap table-responsive p-2" style="max-height: 14dvh;">
                                        <div v-for="(otherMortgagee, key) in otherMortgagees" :key="key" class="form-check col-3">
                                            <input
                                                type="checkbox"
                                                :id="`otherMortgagee-${key}`"
                                                class="form-check-input"
                                                v-model="localRealFilters.otherMortgagees"
                                                :value="otherMortgagee"
                                            />
                                            <label :for="`otherMortgagee-${key}`" class="form-check-label">{{ otherMortgagee }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- Date Inputs -->
                        <div class="mb-3 flex-grow-1 w-50">
                            <label class="label-header">Original Date</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.origDateOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="date"
                                    id="origDateOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.origDateOrdered"
                                />
                            </div>
                        </div>

                        <!-- LTV Inputs -->
                        <div class="mb-3 flex-grow-1 w-50">
                            <label class="label-header">LTV</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.ltvOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="ltvOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.ltvOrdered"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- Orig Balance Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Original Balance</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.origBalanceOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="origBalanceOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.origBalanceOrdered"
                                />
                            </div>
                        </div>

                        <!-- Current Balance Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Current Balance</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.currentBalanceOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="currentBalanceOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.currentBalanceOrdered"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- Orig Rate Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Original Rate</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.origRateOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="origRateOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.origRateOrdered"
                                />
                            </div>
                        </div>

                        <!-- Current Rate Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Current Rate</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.currentRateOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="currentRateOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.currentRateOrdered"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                        <!-- Old Payment Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Old Payment</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.oldPaymentOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="oldPaymentOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.oldPaymentOrdered"
                                />
                            </div>
                        </div>

                        <!-- Prior Mortgage Inputs -->
                        <div class="mb-3 flex-grow-1">
                            <label class="label-header">Prior Mortgage</label>
                            <div class="input-group">
                                <select
                                    class="form-select"
                                    style="max-width: 100px;"
                                    v-model="localRealFilters.priorMortgageOperator"
                                >
                                    <option value="=">=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </select>
                                <input
                                    type="number"
                                    id="priorMortgageOrdered"
                                    class="form-control"
                                    v-model="localRealFilters.priorMortgageOrdered"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- NSF -->
                    <div class="mb-3 w-50" style="padding-right: 4px;">
                        <label class="label-header"># of NSF</label>
                        <div class="input-group">
                            <select
                                class="form-select"
                                style="max-width: 100px;"
                                v-model="localRealFilters.nsfOperator"
                            >
                                <option value="=">=</option>
                                <option value=">=">&gt;=</option>
                                <option value="<=">&lt;=</option>
                            </select>
                            <input
                                type="number"
                                id="nsfOrdered"
                                class="form-control"
                                v-model="localRealFilters.nsfOrdered"
                            />
                        </div>
                    </div>
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
    props: {
        newRenewalsTab: {
            type: Array,
            default: () => []
        },
        realFilters: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ["events", "applyFilters"],
    data () {
        return {
            modalId: 'renewalTrackerFilterModal',
            localRealFilters: {},
            autoCompleteKey: 0,
            filteredData: [],
            applicationIds: [],
            acctNumbers: [],
            lastNames: [],
            cities: [],
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
                    this.localRealFilters = { ...this.realFilters };
                    this.filteredData = JSON.parse(JSON.stringify(this.newRenewalsTab));
                    this.getFilterOptions()
                    this.$nextTick(() => {
                        if(this.autoCompleteKey === 0) {
                            this.autoCompleteKey += 1;
                        }
                    });
                });
            }
        });
    },  
    computed: {
    },
    methods: {
        handleAutoCompleteChange(newValue, key) {
            if(!newValue || (Array.isArray(newValue) && newValue.length === 0)) {
                this.localRealFilters[key] = [];
            } else {
                const value = newValue.value;
                this.localRealFilters[key] = {
                    fullName: value,
                    id: value,
                };
            }
        },
        getFilterOptions: function() {
            const applicationIdsMap = new Map();
            const acctNumbersMap = new Map();
            const lastNamesMap = new Map();
            const citiesMap = new Map();

            for (let i = 0; i < this.filteredData.length; i++) {
                for(let k = 0; k < this.filteredData[i].data.length; k++) {

                    applicationIdsMap.set(String(this.filteredData[i].data[k].applicationId), {
                        fullName: String(this.filteredData[i].data[k].applicationId),
                        id: String(this.filteredData[i].data[k].applicationId),
                    });

                    acctNumbersMap.set(String(this.filteredData[i].data[k].acctNumber), {
                        fullName: String(this.filteredData[i].data[k].acctNumber),
                        id: String(this.filteredData[i].data[k].acctNumber),
                    });

                    lastNamesMap.set(String(this.filteredData[i].data[k].lastName), {
                        fullName: String(this.filteredData[i].data[k].lastName),
                        id: String(this.filteredData[i].data[k].lastName),
                    });

                    citiesMap.set(String(this.filteredData[i].data[k].city), {
                        fullName: String(this.filteredData[i].data[k].city),
                        id: String(this.filteredData[i].data[k].city),
                    });
                    
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

            this.applicationIds = [...applicationIdsMap.values()]
            this.acctNumbers = [...acctNumbersMap.values()]
            this.lastNames = [...lastNamesMap.values()]
            this.cities = [...citiesMap.values()]
        },
        applyFilters() {    

            const selectedDate = new Date(this.localRealFilters.termEndDueDateOrdered);
            const sixMonthsFromToday = new Date();
            sixMonthsFromToday.setMonth(sixMonthsFromToday.getMonth() + 6);

            selectedDate.setHours(0, 0, 0, 0);
            sixMonthsFromToday.setHours(0, 0, 0, 0);

            if (selectedDate > sixMonthsFromToday) {
                this.alertMessage = 'Selected date must be within 6 months.'
                this.showAlert('error')
                return;
            }

            this.$emit("applyFilters", this.localRealFilters);
            this.hideModal(this.modalId)
        },
        generalCheckAll(key) {
            this.localRealFilters[key] = [...this[key]];
        },
        generalUncheckAll(key) {
            this.localRealFilters[key] = [];
        },
        checkAllClosureTypeCheckboxes() {
            this.localRealFilters.foreclosure = true;
            this.localRealFilters.payout = true;
        },
        uncheckAllClosureTypeCheckboxes() {
            this.localRealFilters.foreclosure = false;
            this.localRealFilters.payout = false;
        },
        resetFilter() {
            const defaults = {
                applicationId: [],
                acctNumbers: [],
                lastNames: [],
                cities: [],
                provinces: [],
                propertyTypes: [],
                houseStyles: [],
                positions: [],
                collStatuses: [],
                nsfs: [],
                flags: [],
                originationCompanyNames: [],
                otherMortgagees: [],
                origDateOperator: '=',
                origDateOrdered: null,
                termStartDueDateOrdered: null,
                termEndDueDateOrdered: null,
                ltvOperator: '=',
                ltvOrdered: null,
                nsfOperator: '=',
                nsfOrdered: null,
                origBalanceOperator: '=',
                origBalanceOrdered: null,
                currentBalanceOperator: '=',
                currentBalanceOrdered: null,
                origRateOperator: '=',
                origRateOrdered: null,
                currentRateOperator: '=',
                currentRateOrdered: null,
                oldPaymentOperator: '=',
                oldPaymentOrdered: null,
                priorMortgageOperator: '=',
                priorMortgageOrdered: null,
                foreclosure: false,
                payout: false
            };

            if (this.localRealFilters) {
                Object.keys(defaults).forEach(key => {
                    this.localRealFilters[key] = defaults[key];
                });
            }

            let date = new Date((new Date()).setDate((new Date()).getDate() + 90));

            let year = date.getFullYear();
            let month = String(date.getMonth() + 1).padStart(2, "0");
            let day = String(date.getDate()).padStart(2, "0");

            this.localRealFilters.termEndDueDateOrdered = `${year}-${month}-${day}`;

            this.autoCompleteKey += 1;
        }
    }
}
</script>
