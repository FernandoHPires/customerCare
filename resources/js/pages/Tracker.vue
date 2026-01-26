<template>
    <div class="row mb-3" v-if="userPermissions.canViewFundingTables && allLendersSummary">
        <TrackerSummaryTable
            v-if="fundedSummary"
            type="gross"
            :title="'Running Gross (Funded)'"
            :summaryData="fundedSummary"
            :colSize="(fundedSummary && allLendersSummary) ? 3 : 6"
        />

        <TrackerSummaryTable
            type="gross"
            :title="'Running Gross (All Files)'"
            :summaryData="allLendersSummary"
            :colSize="(fundedSummary && allLendersSummary) ? 3 : 6"
        />

        <TrackerSummaryTable
            type="origination"
            :data="originationGroupSummary"
            :colSize="6"
        />
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <ul class="nav nav-pills me-3">
                <li class="nav-item">
                    <button
                        class="nav-link nav-link-sm"
                        :class="{ active: activeTab === 'tracker' }"
                        @click="activeTab = 'tracker'"
                    >
                        Tracker
                    </button>
                </li>
                <li class="nav-item" v-if="userPermissions.canViewReport">
                    <button
                        class="nav-link"
                        :class="{ active: activeTab === 'report' }"
                        @click="activeTab = 'report'"
                    >
                        Report
                    </button>
                </li>
            </ul>

            <div class="spinner-border spinner-border-sm" role="status" v-if="loading">
                <span class="visually-hidden">Loading...</span>
            </div>

            <div class="me-auto"></div>
            <button
                class="btn btn-primary me-2"
                @click="openFilterModal"
            >
                <i class="bi bi-funnel me-1"></i><small>Filter</small>
            </button>

            <button class="btn btn-primary"
                @click="openModal('addOrderModal')"
                v-if="userPermissions.canAddOrder">
                <i class="bi bi-plus-lg me-1"></i><small>Add Order</small>
            </button>
        </div>
        
        <div v-if="activeTab === 'tracker'">
            <div class="card-body table-responsive px-0 pt-0">
                <table class="table table-striped table-hover text-center mb-0">
                    <thead>
                        <tr>
                            <th></th>
                            <th @click="filteredSort('id')">
                                <i class="me-1" v-bind:class="[getSortIcon('id')]"></i><small>Doc#</small>
                            </th>
                            <th @click="filteredSort('applicationId')">
                                <i class="me-1" v-bind:class="[getSortIcon('applicationId')]"></i><small>TACL#</small>
                            </th>
                            <th @click="filteredSort('docType')">
                                <i class="me-1" v-bind:class="[getSortIcon('docType')]"></i><small>Doc Type</small>
                            </th>
                            <th v-if="showOriginationColumn" @click="filteredSort('origination')">
                                <i class="me-1" v-bind:class="[getSortIcon('origination')]"></i><small>Origination</small>
                            </th>
                            <th v-if="showOriginationColumn" @click="filteredSort('province')">
                                <i class="me-1" v-bind:class="[getSortIcon('province')]"></i><small>Province</small>
                            </th>
                            <th v-if="showGrossAmountColumn" @click="filteredSort('grossAmount')">
                                <i class="me-1" v-bind:class="[getSortIcon('grossAmount')]"></i><small>Gross Amount</small>
                            </th>
                            <th @click="filteredSort('lender')">
                                <i class="me-1" v-bind:class="[getSortIcon('lender')]"></i><small>Lender</small>
                            </th>
                            <th @click="filteredSort('mortgageCode')">
                                <i class="me-1" v-bind:class="[getSortIcon('mortgageCode')]"></i><small>Mortgage Code</small>
                            </th>
                            <th @click="filteredSort('brokerName')">
                                <i class="me-1" v-bind:class="[getSortIcon('brokerName')]"></i><small>Broker</small>
                            </th>
                            <th @click="filteredSort('brokerNotes')">
                                <i class="me-1" v-bind:class="[getSortIcon('brokerNotes')]"></i><small>Broker Notes</small>
                            </th>
                            <th></th>
                            <th @click="filteredSort('supportName')">
                                <i class="me-1" v-bind:class="[getSortIcon('supportName')]"></i><small>Support</small>
                            </th>
                            <th @click="filteredSort('supportStatus')">
                                <i class="me-1" v-bind:class="[getSortIcon('supportStatus')]"></i><small>Support Status</small>
                            </th>
                            <th v-if="showAccountingColumns" @click="filteredSort('accountingName')">
                                <i class="me-1" v-bind:class="[getSortIcon('accountingName')]"></i><small>Accounting</small>
                            </th>
                            <th v-if="showAccountingColumns" @click="filteredSort('accountingStatus')">
                                <i class="me-1" v-bind:class="[getSortIcon('accountingStatus')]"></i><small>Accounting Status</small>
                            </th>
                            <th @click="filteredSort('createDate')">
                                <i class="me-1" v-bind:class="[getSortIcon('createDate')]"></i><small>Age</small>
                            </th>
                            <th v-if="showAccountingColumns" @click="filteredSort('accountingAge')">
                                <i class="me-1" v-bind:class="[getSortIcon('accountingAge')]"></i><small>Accounting Age</small>
                            </th>
                            <th @click="filteredSort('cancelOrder')">
                                <i class="me-1" v-bind:class="[getSortIcon('cancelOrder')]"></i><small>Cancelled</small>
                            </th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody v-if="filteredData.length === 0">
                        <tr>
                            <td colspan="100%" class="text-muted">No requests yet</td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for="(doc, index) in filteredData" :key="index">
                            <td><small>{{ index + 1 }}</small></td>
                            <td><small>{{ doc.id }}</small></td>
                            <td><small>{{ doc.applicationId }}</small></td>
                            <td :class="getDocTypeClass(doc.docType)">
                                <small>{{ doc.docType }}</small>
                            </td>
                            <td v-if="showOriginationColumn"><small>{{ doc.origination }}</small></td>
                            <td v-if="showOriginationColumn"><small>{{ doc.province }}</small></td>
                            <td v-if="showGrossAmountColumn">
                                <span v-if="doc.docType == 'Funding_NB' || doc.docType == 'Funding_PB'">
                                    <small>{{ formatDecimal(doc.grossAmount.reduce((sum, amount) => sum + parseFloat(amount || 0), 0)) }}</small>
                                </span>
                            </td>
                            <td>
                                <span v-if="['Funding_NB', 'Funding_PB', 'Transmittal_Letter', 'Transfer', 'Instructions_NB'].includes(doc.docType)">
                                    <small>{{ formatLenders(doc.lender) }}</small>
                                </span>
                            </td>
                            <td><small>{{ doc.mortgageCode }}</small></td>
                            <td :class="{'fw-bold text-dark': doc.brokerName === currentUser.fullName}">
                                <small>{{ doc.brokerName }}</small>
                            </td>
                            <td><small>{{ doc.brokerNotes }}</small></td>
                            <td>
                                <button v-if="doc.cancelOrder !== 'yes'" class="btn btn-sm btn-outline-primary" @click="openEditNotesModal(doc)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                            <td :class="{'fw-bold text-dark': doc.supportName === currentUser.fullName}">
                                <small>{{ doc.supportName }}</small>
                            </td>
                            <td>
                                <button
                                    class="btn btn-sm"
                                    v-if="doc.cancelOrder !== 'yes'"
                                    :class="getStatusClass(doc.supportStatus)"
                                    :disabled="!userPermissions.canChangeSupportStatus"
                                    @click="openStatusModal('Support', doc)"
                                >
                                    <small>{{ doc.supportStatus || 'Unassigned' }}</small>
                                </button>
                            </td>
                            <td v-if="showAccountingColumns" :class="{'fw-bold text-dark': doc.accountingName === currentUser.fullName}">
                                <small>{{ doc.accountingName }}</small>
                            </td>
                            <td v-if="showAccountingColumns">
                                <button
                                    class="btn"
                                    v-if="doc.cancelOrder !== 'yes'"
                                    :class="getStatusClass(doc.accountingStatus)"
                                    :disabled="!userPermissions.canChangeAccountingStatus"
                                    @click="openStatusModal('Accounting', doc)"
                                >
                                    <small>{{ doc.accountingStatus || 'Unassigned' }}</small>
                                </button>
                            </td>
                            <td>
                                <small v-if="doc.cancelOrder !== 'yes'">{{ doc.age }}</small>
                            </td>
                            <td v-if="showAccountingColumns">
                                <small v-if="doc.cancelOrder !== 'yes'">{{ doc.accountingAge }}</small>
                            </td>
                            <td>
                                <small>{{ doc.cancelOrder }}</small>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    @click="confirmDelete(doc)"
                                    v-if="userPermissions.canDeleteOrder && doc.supportStatus !== 'Completed' && doc.cancelOrder !== 'yes'"
                                >
                                    <i class="bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="activeTab === 'report'">
            <TrackerReportModal :current-user="currentUser" />
        </div>
    </div>

    <!-- Edit Notes Modal -->
    <div class="modal fade" :class="{ show: showEditNotesModal, 'd-block': showEditNotesModal }" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Notes</h5>
                    <button type="button" class="btn-close" @click="closeEditNotesModal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>TACL#:</strong> {{ selectedDocument?.applicationId }}</p>
                    <p><strong>Doc#:</strong> {{ selectedDocument?.id }}</p>
                    <p><strong>Type:</strong> {{ selectedDocument?.docType }}</p>
                    <p><strong>S.Status:</strong> {{ selectedDocument?.supportStatus }}</p>
                    <p><strong>A.Status:</strong> {{ selectedDocument?.accountingStatus }}</p>

                    <label class="form-label"><strong>Broker Notes</strong></label>
                    <textarea v-model="editedNotes" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" @click="closeEditNotesModal">
                        <i class="bi bi-x-lg me-1"></i>Close
                    </button>
                    <button class="btn btn-success" type="button" @click="saveNotes">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal Overlay -->
    <div v-if="showEditNotesModal" class="modal-backdrop fade show"></div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Options</h5>
                    <button type="button" class="btn-close" @click="cancelFilters" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- User Dropdown -->
                    <div class="mb-3">
                        <label class="fw-bold">User</label>
                        <select
                            id="userDropdown"
                            class="form-select"
                            v-model="tempFilters.selectedUser"
                        >
                            <option value=""></option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- TACL# (Application No.) Input -->
                    <div class="mb-3">
                        <label class="fw-bold">TACL#</label>
                        <input
                            type="text"
                            id="applicationId"
                            class="form-control"
                            v-model="tempFilters.applicationId"
                            placeholder="Enter TACL#"
                        />
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Lender</label>
                        <select
                            class="form-select"
                            v-model="tempFilters.selectedLenders"
                        >
                            <option v-for="lender in lenders" :key="lender" :value="lender">
                                {{ lender }}
                            </option>
                        </select>
                    </div>

                    <!-- Cancelled Orders -->
                    <div class="mb-3 form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="cancelledOrders"
                            v-model="tempFilters.cancelledOrders"
                        />
                        <label class="form-check-label fw-bold" for="cancelledOrders">
                            Cancelled Orders
                        </label>
                    </div>
                    <!-- Document Types -->
                    <fieldset class="mb-3">
                        <label class="fw-bold">Document Types</label>
                        <span style="margin-left: 10px;">
                            <button class="btn btn-outline-dark btn-sm" @click="checkAllDocumentTypesCheckboxes" type="button" :id="`documentTypes-all`">
                                <i class="bi bi-check2-square me-1"></i>Select All
                            </button>
                            <button class="btn btn-outline-dark btn-sm" style="margin-left: 10px;" @click="uncheckAllDocumentTypesCheckboxes" type="button" :id="`documentTypes-none`">
                                <i class="bi bi-square me-1"></i>Deselect All
                            </button>
                        </span>
                        <div class="d-flex flex-wrap">
                            <div v-for="docType in documentTypes" :key="docType.id" class="form-check col-4">
                                <input
                                    type="checkbox"
                                    :id="`docType-${docType.id}`"
                                    class="form-check-input"
                                    v-model="tempFilters.documentTypes"
                                    :value="docType.name"
                                />
                                <label :for="`docType-${docType.id}`" class="form-check-label">{{ docType.name }}</label>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Support Statuses -->
                    <fieldset class="mb-3">
                        <label class="fw-bold">Support Status</label>
                        <span style="margin-left: 10px;">
                            <button class="btn btn-outline-dark btn-sm" @click="checkAllSupportCheckboxes" type="button" :id="`support-all`">
                                <i class="bi bi-check2-square me-1"></i>Select All
                            </button>
                            <button class="btn btn-outline-dark btn-sm" style="margin-left: 10px;" @click="uncheckAllSupportCheckboxes" type="button" :id="`support-none`">
                                <i class="bi bi-square me-1"></i>Deselect All
                            </button>
                        </span>
                        <div class="d-flex flex-wrap">
                            <div v-for="status in supportStatuses" :key="status" class="form-check col-4">
                                <input
                                    type="checkbox"
                                    :id="`support-${status}`"
                                    class="form-check-input"
                                    v-model="tempFilters.supportStatuses"
                                    :value="status"
                                />
                                <label :for="`support-${status}`" class="form-check-label">{{ status }}</label>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Accounting Statuses -->
                    <fieldset class="mb-3">
                        <label class="fw-bold">Accounting Status</label>
                        <span style="margin-left: 10px;">
                            <button class="btn btn-outline-dark btn-sm" @click="checkAllAccountingStatusCheckboxes" type="button" :id="`accountingStatus-all`">
                                <i class="bi bi-check2-square me-1"></i>Select All
                            </button>
                            <button class="btn btn-outline-dark btn-sm" style="margin-left: 10px;" @click="uncheckAllAccountingStatusCheckboxes" type="button" :id="`accountingStatus-none`">
                                <i class="bi bi-square me-1"></i>Deselect All
                            </button>
                        </span>
                        <div class="d-flex flex-wrap">
                            <div v-for="status in accountingStatuses" :key="status" class="form-check col-4">
                                <input
                                    type="checkbox"
                                    :id="`accounting-${status}`"
                                    class="form-check-input"
                                    v-model="tempFilters.accountingStatuses"
                                    :value="status"
                                />
                                <label :for="`accounting-${status}`" class="form-check-label">{{ status }}</label>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Date Inputs -->
                    <div class="mb-3">
                        <label class="fw-bold">Date Ordered</label>
                        <div class="input-group">
                            <select
                                class="form-select"
                                style="max-width: 100px;"
                                v-model="tempFilters.dateOrderedOperator"
                            >
                                <option value="=">=</option>
                                <option value=">=">&gt;=</option>
                                <option value="<=">&lt;=</option>
                            </select>
                            <input
                                type="date"
                                id="dateOrdered"
                                class="form-control"
                                v-model="tempFilters.dateOrdered"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Support Date</label>
                        <div class="input-group">
                            <select
                                class="form-select"
                                style="max-width: 100px;"
                                v-model="tempFilters.supportDateOperator"
                            >
                                <option value="=">=</option>
                                <option value=">=">&gt;=</option>
                                <option value="<=">&lt;=</option>
                            </select>
                            <input
                                type="date"
                                id="supportDate"
                                class="form-control"
                                v-model="tempFilters.supportDate"
                            />
                        </div>
                    </div>

                <div class="mb-3">
                    <label class="fw-bold">Accounting Date</label>
                    <div class="input-group">
                        <select
                            class="form-select"
                            style="max-width: 100px;"
                            v-model="tempFilters.accountingDateOperator"
                        >
                            <option value="=">=</option>
                            <option value=">=">&gt;=</option>
                            <option value="<=">&lt;=</option>
                        </select>
                        <input
                            type="date"
                            id="accountingDate"
                            class="form-control"
                            v-model="tempFilters.accountingDate"
                        />
                    </div>
                </div>
            </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="hideModal('filterModal')">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="applyFilters">
                        <i class="bi-save me-1"></i>Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Order Modal -->
    <div
        class="modal fade"
        id="addOrderModal"
        tabindex="-1"
        aria-labelledby="addOrderModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOrderModalLabel">Add Order</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal('addOrderModal')"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="taclLookup" class="form-label">TACL#</label>
                        <div class="input-group">
                            <input
                                type="text"
                                id="taclLookup"
                                v-model="searchTACL"
                                class="form-control"
                                placeholder="Enter TACL#"
                                @keyup.enter="lookupTACL"
                            />
                            <button type="button" class="btn btn-primary" @click="lookupTACL">
                                Search
                            </button>
                        </div>
                        <div v-if="lookupError" class="mt-2 text-danger">
                            {{ lookupError }}
                        </div>
                    </div>
                    <div v-if="lookupResult" class="mb-3">
                        <p><strong>Agent:</strong> {{ lookupResult.agentName }}</p>
                        <p><strong>Signing Agent:</strong> {{ lookupResult.signingAgentName }}</p>
                    </div>
                    <div class="mb-3">
                        <label for="broker" class="form-label">Broker</label>
                        <select id="broker" v-model="formData.broker" class="form-select">
                            <option disabled value="">Select Broker</option>
                            <option
                                v-for="broker in brokers"
                                :key="broker.id"
                                :value="broker.name"
                            >
                                {{ broker.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="docType" class="form-label">Document Type</label>
                        <select id="docType" v-model="formData.docType" class="form-select" @change="fetchMortgageIDs(searchTACL)">
                            <option disabled value="">Select Document Type</option>
                            <option v-for="(documentType, key) in documentTypes" :key="key" :value="documentType.id">{{ documentType.name }}</option>
                        </select>
                    </div>

                    <!-- Mortgage ID Field -->
                    <div v-if="formData.docType == 'Funding_NB' || formData.docType == 'Funding_PB'" class="mb-3">
                        <label for="mortgageId" class="form-label">Mortgage</label>
                        <select
                            id="mortgageId"
                            v-model="formData.mortgageId"
                            class="form-select"
                        >
                            <option disabled value="">Select Mortgage Code</option>
                            <option 
                                v-for="(mortgage, key) in mortgages" 
                                :key="key" 
                                :value="mortgage.id"
                            >
                                {{ mortgage.mortgageCode }}
                            </option>
                        </select>
                        <!-- Show a message when no IDs are available -->
                        <p v-if="!mortgages.length" class="text-danger mt-2">
                            No Mortgage IDs found for the selected TACL#
                        </p>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            class="form-control"
                            placeholder="Add any notes for the document"
                            rows="4"
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        class="btn btn-outline-dark"
                        type="button"
                        @click="closeModal('addOrderModal')"
                    >
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="saveDocument"
                        :disabled="!isFormValid"
                        :title="!isFormValid ? `Missing fields: ${missingFields.join(', ')}` : ''"
                    >
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unified Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">
                        Update {{ statusModalConfig.type }} Status
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal('statusModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong>TACL#:</strong> {{ statusModalConfig.taclId }}</p>
                        <p class="mb-1"><strong>Doc#:</strong> {{ statusModalConfig.docId }}</p>
                        <p class="mb-1"><strong>Doc Type:</strong> {{ statusModalConfig.docType }}</p>
                        <p class="mb-1"><strong>Broker:</strong> {{ statusModalConfig.broker }}</p>
                        <p class="mb-1"><strong>Current Status:</strong> {{ statusModalConfig.currentStatus }}</p>
                    </div>

                    <!-- New Status Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">
                            New {{ statusModalConfig.type }} Status
                        </label>
                        <select
                            :id="statusModalConfig.statusField"
                            v-model="statusModalConfig.newStatus"
                            class="form-select"
                        >
                            <option
                                v-for="option in statusModalConfig.statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Assign to User Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">
                            Assign to {{ statusModalConfig.type }} User
                        </label>
                        <select
                            :id="statusModalConfig.userField"
                            v-model="statusModalConfig.selectedUser"
                            class="form-select"
                        >
                            <option disabled value="">Select User</option>
                            <option
                                v-for="user in statusModalConfig.userOptions"
                                :key="user.id"
                                :value="String(user.id)"
                                :class="{'fw-bold text-dark': user.name === currentUser.fullName}"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea v-model="currentBrokerNotes" class="form-control" rows="4"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="closeModal('statusModal')">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="saveStatus(false)"
                        :disabled="!statusModalConfig.newStatus || !statusModalConfig.selectedUser"
                    >
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <ConfirmationDialog
        :event="currentDocument"
        :message="dialogMessage"
        :type="confirmationType === 'save' ? 'success' : 'danger'"
        :parentModalId="modalId"
        @return="confirmationOnReturn"
    />
</template>

<script>
import { util } from "../mixins/util";
import { ref } from "vue";
import ConfirmationDialog from "../components/ConfirmationDialog.vue";
import TrackerReportModal from "../components/TrackerReportModal.vue";
import TrackerSummaryTable from "../components/TrackerSummaryTable.vue";

export default {
    mixins: [util],
    emits: ["events"],
    props: ['applicationId', 'opportunityId'],
    components: { ConfirmationDialog, TrackerReportModal, TrackerSummaryTable },
    watch: {
        currentUser(newUser) {
            if (newUser && newUser.fullName) {
                this.formData.broker = newUser.fullName;
            }
        },
        '$route.query.applicationId': {
            immediate: true,
            handler(newApplicationId) {
                if (newApplicationId) {
                    this.searchTACL = newApplicationId;
                    this.lookupTACL();
                     
                    this.$nextTick(() => {
                        this.openModal("addOrderModal");
                    });
 
                    this.$router.replace({ path: "/tracker" }); // Clear applicationId from URL
                }
            }
        },
        '$route.query.opportunityId': {
            immediate: true,
            handler(newApplicationId) {
                if(newApplicationId) {
                    this.searchTACL = newApplicationId;
                    this.lookupTACL();

                    this.$nextTick(() => {
                        this.openModal("addOrderModal");
                    });

                    this.$router.replace({ path: "/tracker" }); // Clear applicationId from URL
                }
            },
        },
    },
    setup() {
        const showEditNotesModal = ref(false);
        const selectedDocument = ref(null);
        const editedNotes = ref("");

        const openEditNotesModal = (doc) => {
            selectedDocument.value = { ...doc };
            editedNotes.value = doc.brokerNotes || "";
            showEditNotesModal.value = true;
        };

        const closeEditNotesModal = () => {
            showEditNotesModal.value = false;
            selectedDocument.value = null;
            editedNotes.value = "";
        };

        return {
            showEditNotesModal,
            selectedDocument,
            editedNotes,
            openEditNotesModal,
            closeEditNotesModal,
        };
    },
    data() {
        return {
            activeTab: 'tracker', // default tab
            trackerData: [], // Table data for document tracker
            currentUser: {},
            formData: {
                broker: "",
                docType: "",
                notes: "",
                mortgageId: "",
            },
            statusModalConfig: {
                type: "", // Either 'Support' or 'Accounting'
                taclId: "",
                docId: "",
                docType: "",
                broker: "",
                currentStatus: "",
                newStatus: "",
                selectedUser: "",
                statusField: "",
                userField: "",
                statusOptions: [], // Options for the dropdown
                userOptions: [], // Users for assignment
            },
            reportData: [], // Original report data
            searchTACL: "", // For TACL lookup
            lookupResult: null, // Holds the TACL lookup result
            lookupError: "",
            brokers: [], // List of brokers
            currentSort: "id", // Default sort column
            currentSortDir: "bi-sort-down", // Default sort direction
            search: "", // Search filter
            currentDocument: null,
            dialogMessage: "",
            modalId: "tracker",
            mortgages: [], // Stores Mortgage ID options
            isMortgageSelectable: false, // Controls dropdown enabled/disabled state
            loading: false,
            selectedUser: "all",
            filterInitialized: false,
            documentTypes: [],
            filters: {
                dateOrdered: "", // Default to empty
                supportDate: "", // Default to empty
                accountingDate: "", // Default to empty
                dateOrderedOperator: "=", // Default operator
                supportDateOperator: "=", // Default operator
                accountingDateOperator: "=", // Default operator
                selectedUser: "",
                selectedLenders: "All",
                documentTypes: [],
                supportStatuses: [],
                accountingStatuses: [],
                applicationId: "",
                cancelledOrders: true,
            },
            tempFilters: {
            dateOrdered: "",
            supportDate: "",
            accountingDate: "",
            dateOrderedOperator: "=", // Default operator
            supportDateOperator: "=", // Default operator
            accountingDateOperator: "=", // Default operator
            selectedUser: "",
            selectedLenders: "All",
            documentTypes: [],
            supportStatuses: [],
            accountingStatuses: [],
            applicationId: "",
            cancelledOrders: true,
            },
            // Defaults for checkboxes (preselected but not applied)
            defaultFilters: {
                supportStatuses: ["Unassigned", "In Progress", "Awaiting Accounting", "Awaiting Broker"],
                accountingStatuses: ["Unassigned", "Editing TACL", "OK to Fund", "Funded", "Not Funded"],
                documentTypes:[],
                cancelledOrders: true,
                dateOrderedOperator: "=", // Default operator
                supportDateOperator: "=", // Default operator
                accountingDateOperator: "=", // Default operator
                selectedLenders: "All",
            },
            users: [],
            lenders: ['All', 'RMIF', 'BSF', 'MII', 'ACL'],
            supportStatuses: ["Unassigned", "In Progress", "Awaiting Accounting", "Awaiting Broker", "Completed", "Not Completed"],
            accountingStatuses: ["Unassigned", "Editing TACL", "OK to Fund", "Funded", "Not Funded"],
            confirmationType: "",
            currentSelectedUserId: "",
            currentSelectedUserName: "",
            currentBrokerNotes: "",
        };
    },
    created() {
        this.getData(true)
        this.getCurrentUser()
        this.getBrokers()
        this.getAccountingUsers()
        this.getFundingUsers()  
        this.getSupportUsers()
        this.getUsers()
        this.getDocumentTypes()
        this.autoRefresh()
    },
    mounted() {
        this.filters = JSON.parse(JSON.stringify(this.defaultFilters))
    },
    computed: {
        isFormValid() {
            const docType = this.formData.docType;
            const hasMortgageIds = this.mortgages && this.mortgages.length > 0;

            const requiredFieldsFilled =
                this.lookupResult !== null &&
                this.formData.broker !== undefined &&
                this.formData.broker.trim() !== "" &&
                docType !== undefined &&
                docType.trim() !== "";

            const isFundingDocType = docType === "Funding_NB" || docType === "Funding_PB";

            // If it's a funding doc type and there are no mortgages, it's invalid
            if (isFundingDocType && !hasMortgageIds) {
                return false;
            }
            
            // Mortgage ID is only required if there are mortgage IDs
            const mortgageIdValid = (hasMortgageIds && (this.formData.docType === "Funding_NB" || this.formData.docType === "Funding_PB"))
                ? (this.formData.mortgageId || "").toString().trim() !== ""
                : true;

            return requiredFieldsFilled && mortgageIdValid;
        },
        missingFields() {
            const errors = [];

            if (this.lookupResult === null) {
                errors.push("TACL# lookup");
            }
            console.log(this.formData)
            if (this.formData.broker !== undefined && !this.formData.broker.trim()) {
                errors.push("Broker");
            }
            if (this.formData.docType !== undefined && !this.formData.docType.trim()) {
                errors.push("Document Type");
            }
            if (
                this.mortgages &&
                this.mortgages.length > 0 &&
                (this.formData.mortgageId || "").toString().trim() === ""
            ) {
                errors.push("Mortgage Code");
            }

            return errors;
        },
        filteredData() {
            // Normalize document types for comparison
            const normalizedDocTypes = this.filters.documentTypes.map((docType) =>
                docType.replace(/ /g, "_").toLowerCase()
            );

            const accountingStatuses = this.filters?.accountingStatuses || []; // Selected accounting statuses
            const supportStatuses = this.filters?.supportStatuses || []; // Selected support statuses

            // Start with the base tracker data
            let filtered = this.trackerData;

            // General filtering for other user roles
            if (normalizedDocTypes.length > 0) {
                filtered = filtered.filter((doc) =>
                    normalizedDocTypes.includes(doc.docType.toLowerCase())
                );
            }

            if (supportStatuses.length > 0) {
                filtered = filtered.filter((doc) =>
                    supportStatuses.includes(doc.supportStatus)
                );
            }

            if (accountingStatuses.length > 0) {
                filtered = filtered.filter((doc) =>
                    accountingStatuses.includes(doc.accountingStatus)
                );
            }

            // Cancelled Orders toggle
            if (this.filters.cancelledOrders === false) {
                filtered = filtered.filter(doc => doc.cancelOrder !== 'yes');
            }

            const lenderVisibleDocTypes = [
                'Funding_NB', 'Funding_PB', 'Transmittal_Letter', 'Transfer', 'Instructions_NB'
            ]; 

            if (this.filters.selectedLenders && this.filters.selectedLenders !== 'All') {
                filtered = filtered.filter(doc => {

                    if (!lenderVisibleDocTypes.includes(doc.docType)) {
                        return false; // Skip documents whose docType doesn't show lender
                    }
                    
                    // doc.lender is an array, selectedLenders is a string
                    if (Array.isArray(doc.lender)) {
                        return doc.lender.includes(this.filters.selectedLenders);
                    } else {
                        return doc.lender === this.filters.selectedLenders;
                    }
                });
            }

            return filtered;
        },
        userPermissions() {
            if (this.currentUser?.trackerManager === 'yes') {
                return permissions.Manager;
            }
            // Use trackerView to determine permissions
            return permissions[this.currentUser?.trackerView] || {};
        },
        showAccountingColumns() {
            if(
                this.currentUser?.trackerManager === 'yes' ||
                this.currentUser?.trackerView === 'Support' || 
                this.currentUser?.trackerView === 'Accounting'
            ) {
                return true;
            }

            return false;
        },
        showOriginationColumn() {
            // Show Origination for Accounting and Manager users
            return this.currentUser?.trackerView === 'Accounting' || this.currentUser?.trackerManager === 'yes';
        },
        showGrossAmountColumn() {
            // Show Gross Amount for Accounting and Manager users
            return this.currentUser?.trackerView === 'Accounting' || this.currentUser?.trackerManager === 'yes';
        },
        fundedSummary() {
            // Filter rows with grossAmount > 0 and accountingStatus === 'Funded'
            const fundedRows = this.trackerData.filter(
                (doc) =>
                    doc &&
                    doc.accountingStatus === "Funded" &&
                    doc.grossAmount.some((amount) => parseFloat(amount || 0) > 0)
            );

            if (fundedRows.length === 0) return null; // No valid rows, no summary

            let totalCounts = 0;
            const summary = fundedRows.reduce((acc, doc) => {
                doc.lender.forEach((lender, index) => {
                    const grossAmount = parseFloat(doc.grossAmount[index]) || 0;

                    if (!acc[lender]) {
                        acc[lender] = { count: 0, total: 0 };
                    }
                    acc[lender].count += 1;
                    acc[lender].total += grossAmount;
                    totalCounts += 1;
                });

                return acc;
            }, {});

            // Transform into table-friendly format
            const summaryArray = Object.entries(summary).map(([lender, { count, total }]) => ({
                lender,
                count,
                total: total.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
            }));

            const totalGross = Object.values(summary).reduce((sum, lenderData) => sum + lenderData.total, 0);

            return {
                summaryArray,
                totalCounts,
                totalGross: totalGross.toLocaleString("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }),
            };
        },

        allLendersSummary() {
            // Include all lenders, regardless of their funding status
            const allRows = this.trackerData.filter(
                (doc) => doc && doc.grossAmount.some((amount) => parseFloat(amount || 0) > 0)
            );

            if (allRows.length === 0) return null; // No valid rows, no summary

            let totalCounts = 0;
            const summary = allRows.reduce((acc, doc) => {
                doc.lender.forEach((lender, index) => {
                    if(doc.cancelOrder != 'yes' && doc.supportStatus !== 'Not Completed' && doc.accountingStatus !== 'Not Funded') {
                        const grossAmount = parseFloat(doc.grossAmount[index]) || 0;

                        if (!acc[lender]) {
                            acc[lender] = { count: 0, total: 0 };
                        }
                        acc[lender].count += 1;
                        acc[lender].total += grossAmount;
                        totalCounts += 1;
                    }
                });

                return acc;
            }, {});

            // Transform into table-friendly format
            const summaryArray = Object.entries(summary).map(([lender, { count, total }]) => ({
                lender,
                count,
                total: total.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
            }));

            const totalGross = Object.values(summary).reduce((sum, lenderData) => sum + lenderData.total, 0);

            return {
                summaryArray,
                totalCounts,
                totalGross: totalGross.toLocaleString("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }),
            };
        },

        originationGroupSummary() {
            if (!this.trackerData || !this.trackerData.length) return [];

            const summaryMap = {};

            const allTotal = {
                companyAbbr: 'All Total',
                nbCount: 0,
                nbAmount: 0,
                pbCount: 0,
                pbAmount: 0,
                totalCount: 0,
                totalAmount: 0
            };

            this.trackerData.forEach((doc) => {
                if (doc.cancelOrder === 'yes') return;

                const origination = doc.origination;
                const docType = doc.docType;
                let amount = 0;
                if (Array.isArray(doc.grossAmount)) {
                    amount = doc.grossAmount.reduce((sum, val) => sum + parseFloat(val || 0), 0);
                } else {
                    amount = parseFloat(doc.grossAmount) || 0;
                }


                if (!['Funding_NB', 'Funding_PB'].includes(docType)) return;

                if (!summaryMap[origination]) {
                summaryMap[origination] = {
                    companyAbbr: origination,
                    nbCount: 0,
                    nbAmount: 0,
                    pbCount: 0,
                    pbAmount: 0,
                    totalCount: 0,
                    totalAmount: 0
                };
                }

                const row = summaryMap[origination];

                if (docType === 'Funding_NB') {
                row.nbCount += 1;
                row.nbAmount += amount;
                allTotal.nbCount += 1;
                allTotal.nbAmount += amount;
                } else if (docType === 'Funding_PB') {
                row.pbCount += 1;
                row.pbAmount += amount;
                allTotal.pbCount += 1;
                allTotal.pbAmount += amount;
                }

                row.totalCount = row.nbCount + row.pbCount;
                row.totalAmount = row.nbAmount + row.pbAmount;

                allTotal.totalCount += 1;
                allTotal.totalAmount += amount;
            });

            const allRows = Object.values(summaryMap);

            const alpineRows = allRows.filter(row => row.companyAbbr !== 'SQC');
            const sqcRow = allRows.find(row => row.companyAbbr === 'SQC');

            // Create Alpine Total
            const alpineTotal = alpineRows.reduce((acc, row) => {
                acc.nbCount += row.nbCount;
                acc.nbAmount += parseFloat(row.nbAmount);
                acc.pbCount += row.pbCount;
                acc.pbAmount += parseFloat(row.pbAmount);
                acc.totalCount += row.totalCount;
                acc.totalAmount += parseFloat(row.totalAmount);
                return acc;
            }, {
                companyAbbr: 'Alpine Total',
                nbCount: 0,
                nbAmount: 0,
                pbCount: 0,
                pbAmount: 0,
                totalCount: 0,
                totalAmount: 0
            });

            // Format a row's amounts
            const format = (row) => ({
                ...row,
                nbAmount: row.nbAmount.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                pbAmount: row.pbAmount.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                totalAmount: row.totalAmount.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
            });

            return [
                ...alpineRows.map(format),      // e.g., ACL, AMC, AOL
                format(alpineTotal),           // Alpine Total
                ...(sqcRow ? [format(sqcRow)] : []), // SQC (after Alpine Total)
                format(allTotal)               // All Total
            ];
        }
    },

    methods: {
        checkAllSupportCheckboxes(){
            this.defaultFilters.supportStatuses = ["Unassigned", "In Progress", "Awaiting Accounting", "Awaiting Broker", "Completed", "Not Completed"];
            this.tempFilters.supportStatuses = ["Unassigned", "In Progress", "Awaiting Accounting", "Awaiting Broker", "Completed", "Not Completed"];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('support'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            })
        },
        uncheckAllSupportCheckboxes(){
            this.defaultFilters.supportStatuses = [];
            this.tempFilters.supportStatuses = [];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('support'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            })
        },
        checkAllDocumentTypesCheckboxes(){
            this.defaultFilters.documentTypes = ["Credit Bureau", "Funding NB", "Funding PB", "Initial Docs NB", "Initial Docs PB", "Instructions NB", "Instructions PB", "Credit Bureau", "Funding NB", "Funding PB", "Initial Docs NB", "Initial Docs PB", "Instructions NB", "Instructions PB", "Title Docs", "Transfer", "Transmittal Letter", "Title Docs", "Transfer", "Transmittal Letter"];
            this.tempFilters.documentTypes = ["Credit Bureau", "Funding NB", "Funding PB", "Initial Docs NB", "Initial Docs PB", "Instructions NB", "Instructions PB", "Credit Bureau", "Funding NB", "Funding PB", "Initial Docs NB", "Initial Docs PB", "Instructions NB", "Instructions PB", "Title Docs", "Transfer", "Transmittal Letter", "Title Docs", "Transfer", "Transmittal Letter"];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('docType'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            })
        },
        uncheckAllDocumentTypesCheckboxes(){
            this.defaultFilters.documentTypes = [];
            this.tempFilters.documentTypes = [];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('docType'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            })
        },
        checkAllAccountingStatusCheckboxes(){
            this.defaultFilters.accountingStatuses = ["Unassigned", "Editing TACL", "OK to Fund", "Funded", "Not Funded"];
            this.tempFilters.accountingStatuses = ["Unassigned", "Editing TACL", "OK to Fund", "Funded", "Not Funded"];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('accounting'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            })
        },
        uncheckAllAccountingStatusCheckboxes(){
            this.defaultFilters.accountingStatuses = [];
            this.tempFilters.accountingStatuses = [];
            const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).filter(checkbox => checkbox.id.startsWith('accounting'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            })
        },
        getDocumentTypes() {
            this.axios.get("/web/tracker/document-types")
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.documentTypes = response.data.data
                }
            })
            .catch((error) => {
                console.error(error);
            })
        },
        openModal(modalId) {
            this.$nextTick(() => {
                const modalElement = document.getElementById(modalId);
                if (!modalElement) {
                    return;
                }
                this.formData.broker = this.currentUser.fullName;
                this.showModal(modalId); // Use util's showModal

                if (this.formData.docType === "Funding_PB" || this.formData.docType === "Funding_NB") {
                     this.isMortgageSelectable = true;
                     this.fetchMortgageIDs(this.formData.docType);
                 } else {
                     this.isMortgageSelectable = false;
                     this.mortgages = [];
                 }
            });
        },
        closeModal(modalId) {
            this.hideModal(modalId); // Use util's hideModal
            this.resetForm(); // Reset form fields
        },
        openFilterModal() {
            this.defaultFilters.documentTypes = this.documentTypes.length > 0
                    ? [...this.documentTypes.map((doc) => doc.name)] // Use names of document types
                    : []; // Fallback to empty if document types are not fetched

            // Initialize tempFilters based on the current state of filters
            if (!this.filterInitialized) {
                // Use defaultFilters only if filters are not initialized
                this.tempFilters = JSON.parse(JSON.stringify(this.defaultFilters));
                this.tempFilters.dateOrdered = this.filters.dateOrdered; //
            } else {
                // Use the currently applied filters for tempFilters
                this.tempFilters = JSON.parse(JSON.stringify(this.filters));
            }

            // Open the filter modal
            this.showModal("filterModal");
        },
        applyFilters() {

            if (this.tempFilters.applicationId && isNaN(this.tempFilters.applicationId)) {
                this.alertMessage = "TACL# must be numeric!";
                this.showAlert("error");
                return;
            }

            this.tempFilters.selectedLenders = this.tempFilters.selectedLenders ? this.tempFilters.selectedLenders : "All";
            this.filters = JSON.parse(JSON.stringify(this.tempFilters));
            this.filterInitialized = true;
            this.getData();
            this.hideModal("filterModal");
        },
        cancelFilters() {
            // Close the modal without applying the changes
            this.hideModal("filterModal");
        },
        openStatusModal(type, doc) {
            if(type === 'Support') {
                this.currentSelectedUserId = doc.supportId;
                this.currentSelectedUserName = doc.supportName;
            } else {
                this.currentSelectedUserId = doc.accountingId;
                this.currentSelectedUserName = doc.accountingName;
            }
            this.currentBrokerNotes = doc.brokerNotes;

            const config = {
                Support: {
                    statusField: "supportStatus",
                    userField: "supportUser",
                    statusOptions: [
                        { value: "unassigned", label: "Unassigned" },
                        { value: "inProgress", label: "In Progress" },
                        { value: "awaitingAcctg", label: "Awaiting Accounting" },
                        { value: "awaitingBroker", label: "Awaiting Broker" },
                        { value: "completed", label: "Completed" },
                        { value: "notCompleted", label: "Not Completed" },
                    ],
                    userOptions: this.supportUsers,
                },
                Accounting: {
                    statusField: "accountingStatus",
                    userField: "accountingUser",
                    statusOptions: [
                        { value: "unassigned", label: "Unassigned" },
                        { value: "editingTACL", label: "Editing TACL" },
                        { value: "okToFund", label: "OK to Fund" },
                        { value: "funded", label: "Funded" },
                        { value: "notFunded", label: "Not Funded" },
                    ],
                    userOptions: this.fundingUsers,
                },
            };

            const modalConfig = config[type];
            const currentStatus = type === "Support" ? doc.supportStatus : doc.accountingStatus;
            const options = modalConfig.statusOptions;
            const currentIndex = options.findIndex( option => currentStatus && option.label.toLowerCase() === currentStatus.toLowerCase());
            const nextStatus = currentIndex >= 0 && currentIndex < options.length - 1
                ? options[currentIndex + 1].value
                : currentStatus;
            const fallbackUserId = String(this.currentUser.id);
            const selectedUser = type === "Support"
                ? String(doc.supportId || fallbackUserId)
                : String(doc.accountingId || fallbackUserId);

            this.statusModalConfig = {
                ...modalConfig,
                type,
                taclId: doc.applicationId,
                docId: doc.id,
                docType: doc.docType,
                broker: doc.brokerName,
                currentStatus,
                newStatus: nextStatus,
                selectedUser,
            };

            this.openModal("statusModal");
        },

        saveNotes() {
            if (!this.selectedDocument) {
                console.error("saveNotes: selectedDocument is null");
                return;
            }

            const docId = this.selectedDocument.id; // Store ID before closing modal
            const notesToSave = this.editedNotes === null ? "" : this.editedNotes; // Allow null or empty string

            // Optimistically update UI
            const docIndex = this.trackerData.findIndex(doc => doc.id === docId);
            if (docIndex !== -1) {
                this.trackerData[docIndex].brokerNotes = notesToSave;
            }

            this.showPreLoader();
            
            this.axios.put(`/web/tracker/notes/${docId}`, { brokerNotes: notesToSave })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    this.alertMessage = "Notes edited successfully";
                    this.showAlert("success");
                    this.closeEditNotesModal();
                } else {
                    this.alertMessage = "Error editing notes";
                    this.showAlert("error");
                }
            })
            .catch(error => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },

        getCurrentUser() {
            this.axios
            .get("/web/current-user") // Replace with your actual endpoint
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.currentUser = response.data.data;

                    // Dynamically add "Completed" and "Not Completed" for support users
                    if (this.currentUser?.trackerView === "Support" || this.currentUser?.trackerView === "Accounting") {
                        if (!this.defaultFilters.supportStatuses.includes("Completed")) {
                            this.defaultFilters.supportStatuses.push("Completed");
                        }

                        if (!this.defaultFilters.supportStatuses.includes("Not Completed")) {
                            this.defaultFilters.supportStatuses.push("Not Completed");
                        }

                        this.filters.supportStatuses = [...this.defaultFilters.supportStatuses];

                        if(this.currentUser?.trackerView === "Accounting") {
                            this.filters.documentTypes = ["Funding_NB", "Funding_PB"];
                        }
                    } else {
                        // Ensure non-support users use default filters
                        this.filters.supportStatuses = [...this.defaultFilters.supportStatuses];
                    }

                    // Ensure accounting statuses are set
                    this.filters.accountingStatuses = [...this.defaultFilters.accountingStatuses];
                }
            })
            .catch((error) => {
                console.error("Error fetching current user:", error);
            });
        },
        getUsers() {
            this.axios.get('/web/tracker/all-users')
                .then((response) => {
                    if(this.checkApiResponse(response)) {
                        this.users = response.data.data;
                    }
                })
                .catch((error) => {
                    console.error('Error fetching users:', error);
                });
        },
        getStatusClass(status) {
            switch (status) {
                case "Unassigned":
                    return "btn-outline-secondary"; // White
                case "In Progress":
                    return "btn-info"; // Info color
                case "Awaiting Accounting":
                case "Awaiting Broker":
                    return "btn-info"; // Info color
                case "Completed":
                    return "btn-success"; // Success color
                case "Not Completed":
                    return "btn-warning"; // Warning color
                
                // Accounting Statuses
                case "Editing TACL":
                    return "btn-info"; // Info color
                case "OK to Fund":
                    return "btn-warning"; // Warning color
                case "Funded":
                    return "btn-success"; // Success color
                case "Not Funded":
                    return "btn-warning"; // Warning color
                default:
                    return "btn-outline-secondary"; // Default to white for unknown statuses
            }
        },
        getAccountingUsers() {
            this.axios
                .get("/web/users/accounting")
                .then((response) => {
                    if(this.checkApiResponse(response)){
                        this.accountingUsers = response.data.data.map((user) => ({
                            id: user.id,
                            name: user.fullName,
                        }));
                    }
                })
                .catch((error) => {
                    console.error("Error fetching accounting users:", error);
                });
        },
        getFundingUsers() {
            this.axios
                .get("/web/users/funding")
                .then((response) => {
                    if(this.checkApiResponse(response)) {
                        this.fundingUsers = response.data.data.map((user) => ({
                            id: user.id,
                            name: user.fullName,
                        }));
                    }
                })
                .catch((error) => {
                    console.error("Error fetching funding users:", error);
                });
        },
        getSupportUsers() {
            this.axios
                .get("/web/users/support")
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.supportUsers = response.data.data.map((user) => ({
                            id: user.id,
                            name: user.fullName,
                        }));
                    }
                })
                .catch((error) => {
                    console.error("Error fetching support users:", error);
                });
        },
        autoRefresh() {
            setTimeout(() => {
                this.getData(false);
                this.autoRefresh();
            }, 20000)
        },
        formatLenders(lenders) {
            if (Array.isArray(lenders)) {
                return lenders.join('/');
            }
            return lenders || 'N/A';
        },

        getBrokers() {
            this.axios
                .get("/web/users/brokers")
                .then((response) => {
                    if(this.checkApiResponse(response)) {
                        this.brokers = response.data.data.map((broker) => ({
                            id: broker.id,
                            name: broker.fullName,
                        }));
                    }
                })
                .catch((error) => {
                    this.alertMessage = error
                    this.showAlert('error')
                });
        },
        fetchMortgageIDs(tacl) {
            if (!tacl) {
                this.mortgages = [];
                return;
            }

            this.axios
            .get(`/web/applications/${tacl}/mortgages`)
            .then((response) => {
                console.log(response)
                if(this.checkApiResponse(response)) {
                    this.mortgages = response.data.data
                } else {
                    this.mortgages = []
                }
            })
            .catch((error) => {
                this.mortgages = []
            })
        },
        confirmDelete(doc) {
            this.currentDocument = doc;
            this.dialogMessage = `Are you sure you want to delete document #${doc.id}?`;
            this.confirmationType = "delete";
            this.showModal("confirmationDialog" + this.modalId);
        },
        confirmationOnReturn(event, returnMessage) {
            if(returnMessage === "confirmed" && event === "saveStatus") {
                this.saveStatus(true);

            } else if (returnMessage === "confirmed" && this.confirmationType === "delete") {
                this.deleteDocument(event.id);

            } else if(returnMessage === "confirmed" && this.confirmationType === "save") {
                this.saveNotes();

            }
        },
        deleteDocument(docId) {
            this.showPreLoader();

            this.axios.delete(`/web/tracker/${docId}`)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getData();

                        // Validate if modal exists before hiding
                        const modalId = `confirmationDialog${this.modalId}`;
                        if (this.modals?.state?.modals[modalId]) {
                            this.hideModal(modalId);
                        } else {
                            console.warn(`Modal with ID "${modalId}" does not exist.`);
                        }
                    }
                    
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                })
                .catch((error) => {
                    console.error("Error deleting document:", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        lookupTACL() {
            this.axios
                .get(`/web/applications/${this.searchTACL}`)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.lookupResult = response.data.data;
                        this.lookupError = "";

                        this.searchTACL = this.lookupResult.applicationId;

                        // Fetch Mortgage IDs only if the current docType allows it
                        if (this.formData.docType === "Funding_NB" || this.formData.docType === "Funding_PB") {
                            this.fetchMortgageIDs(this.searchTACL);
                        }
                    } else {
                        this.lookupResult = null;
                        this.lookupError = `No record found for Application ID: ${this.searchTACL}`;
                        this.mortgages = []; // Clear the mortgage IDs if the lookup fails
                        this.isMortgageSelectable = false;
                    }
                })
                .catch((error) => {
                    this.lookupResult = null;
                    this.lookupError = `No record found for Application ID: ${this.searchTACL}`;
                    this.mortgages = []; // Clear the mortgage IDs on error
                    this.isMortgageSelectable = false;
                });
        },
        saveDocument() {
            if (!this.isFormValid) {
                console.error("Form is not valid!");
                return;
            }

            // Find the selected broker's ID
            const selectedBroker = this.brokers.find((broker) => broker.name === this.formData.broker);

            // Create the payload
            const newEntry = {
                application_id: this.searchTACL,
                doc_type: this.formData.docType,
                broker_id: selectedBroker.id,
                broker_notes: this.formData.notes || "",
            };

            // Only include mortgage_id if it is provided
            if (this.formData.mortgageId) {
                newEntry.mortgage_id = this.formData.mortgageId;
            }

            this.showPreLoader();

            // Submit the data
            this.axios
                .post("/web/tracker", newEntry)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getData(); // Refresh the tracker data
                        this.closeModal("addOrderModal"); // Close the modal
                        this.resetForm(); // Reset the form fields

                        // Show success alert
                        this.alertMessage = response.data.message;
                        this.showAlert("success");
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert("error");
                    }
                })
                .catch((error) => {
                    this.alertMessage = error.response?.data?.message || "Error saving the document";
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        saveStatus(override) {
            const type = this.statusModalConfig.type; // Either 'Support' or 'Accounting'
            const docId = this.statusModalConfig.docId;
            const updatedBrokerNotes = this.currentBrokerNotes === null ? "" : this.currentBrokerNotes;
            // Construct the payload dynamically
            const payload = {
                [`${type.toLowerCase()}Status`]: this.statusModalConfig.newStatus,
                [`${type.toLowerCase()}Id`]: parseInt(this.statusModalConfig.selectedUser, 10),
                override: override,
                updatedBrokerNotes: updatedBrokerNotes
            };

            this.showPreLoader();

            const endpoint = `/web/tracker/${type.toLowerCase()}-status/${docId}`;
            this.axios
                .put(endpoint, payload)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        let isTakeover = response.data.data?.isTakeover === undefined ? false : response.data.data?.isTakeover;

                        if(isTakeover === true) {
                            this.dialogMessage = `<b>${response.data.data.existingSupportName}</b> is assigned to this order.<br>Please, confirm you wish to assign it to yourself?`;
                            this.confirmationType = 'change';
                            this.currentDocument = 'saveStatus';
                            this.showModal('confirmationDialog' + this.modalId);
                            return;
                        }
                        
                        this.getData(); // Refresh tracker data
                        this.alertMessage = response.data.message;
                        this.showAlert("success");
                        this.closeModal("statusModal");
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert("error");
                    }
                })
                .catch((error) => {
                    this.alertMessage = error
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        resetForm() {
            this.searchTACL = "";
            this.lookupResult = null;
            this.formData = {
                broker: "",
                docType: "",
                notes: "",
                mortgageId: "",
            };
        },

        getData(load = false) {
            if (load) this.showPreLoader()

            this.loading = true

            let filters = {
                dateOrdered: this.filters.dateOrdered,
                dateOrderedOperator: this.filters.dateOrderedOperator,
                supportDate: this.filters.supportDate,
                supportDateOperator: this.filters.supportDateOperator,
                accountingDate: this.filters.accountingDate,
                accountingDateOperator: this.filters.accountingDateOperator,
                selectedUser: this.filters.selectedUser,
                applicationId: this.filters.applicationId,
            };

            this.axios
            .get("/web/tracker", {
                params: filters,
            })
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.trackerData = response.data.data
                }
            })
            .catch((error) => {
                this.alertMessage = error.response?.data?.message || "Error fetching tracker data"
                this.showAlert("error")
            })
            .finally(() => {
                if (load) this.hidePreLoader()
                this.loading = false
            })
        },

        getDocTypeClass(docType) {
            switch (docType) {
            case "Initial_Docs_NB":
                return "bg-light-yellow";
            case "Title_Docs":
                return "bg-light-purple";
            case "Instructions_NB":
                return "bg-light-beige";
            case "Credit_Bureau":
                return "bg-light-orange";
            case "Funding_NB":
            case "Funding_PB":
                return "bg-light-green";
            case "Transfer":
                return "bg-light-blue";
            default:
                return "bg-light-red";
            }
        },
    },
};

const permissions = {
    Accounting: {
        canChangeAccountingStatus: true,
        canChangeSupportStatus: false,
        canAddOrder: false,
        canDeleteOrder: false,
        canViewReport: false, // Not allowed
        canViewFundingTables: true,
    },
    Support: {
        canChangeAccountingStatus: false,
        canChangeSupportStatus: true,
        canAddOrder: false,
        canDeleteOrder: false,
        canViewReport: true, // Allowed
        canViewFundingTables: false,
    },
    Broker: {
        canChangeAccountingStatus: false,
        canChangeSupportStatus: false,
        canAddOrder: true,
        canDeleteOrder: true,
        canViewReport: false,
        canViewFundingTables: false,
    },
    Manager: {
        canChangeAccountingStatus: false,
        canChangeSupportStatus: false,
        canAddOrder: true,
        canDeleteOrder: true,
        canViewReport: true,
        canViewFundingTables: true,
    },
};
</script>

<style scoped>
    .bg-light-yellow {
        background-color: #fff9c4 !important; /* Light yellow */
    }
    .bg-light-purple {
        background-color: #e1bee7 !important; /* Light purple */
    }
    .bg-light-beige {
        background-color: #f5f5dc !important; /* Light beige */
    }
    .bg-light-orange {
        background-color: #ffe0b2 !important; /* Light orange */
    }
    .bg-light-green {
        background-color: #c8facc !important; /* Soft green */
    }
    .bg-light-blue {
        background-color: #d0ebff !important; /* soft light blue */
    }
    .bg-light-red {
        background-color: #e3c596 !important; /* Light red */
    }
</style>