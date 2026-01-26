<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Renewal Tracker
            </li>
        </ol>
    </nav>

    <!-- New Renewals -->
    <div class="card mb-3 position-relative">
        <div class="card-preloader" id="new-renewals-preloader" style="display: none">
            <div class="d-flex justify-content-center h-100">
                <div
                    class="spinner-border text-primary align-self-center"
                    role="status"
                >
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>New Renewals</div>

                <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                    <button
                        class="btn btn-primary"
                        @click="exportToExcel()"
                        :disabled="!isFilterEnabled"
                        
                    >
                        <i class="bi bi-file-earmark-excel me-1"></i><small>Export</small>
                    </button>

                    <button
                        class="btn btn-primary"
                        @click="openHighlightModal"
                        :disabled="!isFilterEnabled"
                    >
                        <i class="bi bi-highlights me-1"></i><small>Highlight</small>
                    </button>

                    <button
                        class="btn btn-primary"
                        @click="openFilterModal"
                        :disabled="!isFilterEnabled"
                    >
                        <i class="bi bi-funnel me-1"></i><small>Filter</small>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- New Renewals Tab Headers -->
            <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                <ul class="nav nav-tabs flex-grow-1" id="new-renewals-tablist" role="tablist">
                    <li class="nav-item" role="presentation" v-for="(tab, tabKey) in filteredRenewals" :key="tabKey">
                        <a
                            v-bind:class="['nav-link', tabKey == 0 ? 'active' : '']"
                            :id="'new-renewals-tablist-' + tabKey + '-tab'"
                            data-coreui-toggle="tab"
                            :href="'#new-renewals-tablist-' + tabKey"
                            role="tab"
                            :aria-controls="'new-renewals-tablist-' + tabKey"
                            aria-selected="true"
                        >
                            {{ tab.name }} ({{ formatNumber(tab.data.length) }})
                        </a>
                    </li>
                </ul>
            </div>

            <!-- New Renewals -->
            <div class="tab-content" id="newRenewalsTabContent">
                <div v-for="(tab, tabKey) in filteredRenewals" :key="tabKey"
                    v-bind:class="['tab-pane fade show table-responsive px-0', tabKey == 0 ? 'active' : '']"
                    style="max-height: 70dvh;"
                    :id="'new-renewals-tablist-' + tabKey"
                    role="tabpanel"
                    :aria-labelledby="'new-renewals-tablist-' + tabKey + '-tab'"
                >
                    <table class="table table-sticky table-hover">
                        <thead>
                            <tr>
                                <th @click="sortNewRenewals('applicationId')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('applicationId')]"></i>#
                                </th>
                                <th @click="sortNewRenewals('acctNumber')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('acctNumber')]"></i>Acct #
                                </th>
                                <th @click="sortNewRenewals('originationCompanyName')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('originationCompanyName')]"></i>Orig Company
                                </th>
                                <th @click="sortNewRenewals('lastName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('lastName')]"></i>Last Name
                                </th>
                                <th @click="sortNewRenewals('city')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('city')]"></i>City
                                </th>
                                <th @click="sortNewRenewals('province')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('province')]"></i>Province
                                </th>
                                <th @click="sortNewRenewals('propertyType')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('propertyType')]"></i>Property Type
                                </th>
                                <th @click="sortNewRenewals('houseStyle')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('houseStyle')]"></i>House Style
                                </th>
                                <th @click="sortNewRenewals('pos')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('pos')]"></i>Position
                                </th>
                                <th @click="sortNewRenewals('ltv')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('ltv')]"></i>LTV
                                </th>
                                <th @click="sortNewRenewals('termDueDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                </th>
                                <th @click="sortNewRenewals('priorMtge')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('priorMtge')]"></i>Prior Mortgage
                                </th>
                                <th @click="sortNewRenewals('collStatus')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('collStatus')]"></i>Coll Status
                                </th>
                                <th @click="sortNewRenewals('origDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('origDate')]"></i>Orig Date
                                </th>
                                <th @click="sortNewRenewals('origBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('origBalance')]"></i>Orig Balance
                                </th>
                                <th @click="sortNewRenewals('currentBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('currentBalance')]"></i>Current Balance
                                </th>
                                <th @click="sortNewRenewals('org')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('org')]"></i>Orig Rate
                                </th>
                                <th @click="sortNewRenewals('rate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('rate')]"></i>Current Rate
                                </th>
                                <th @click="sortNewRenewals('numberOfNSF')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('numberOfNSF')]"></i># of NSF
                                </th>
                                <th @click="sortNewRenewals('otherMortgage')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('otherMortgage')]"></i>Other Mortgagee
                                </th>
                                <th @click="sortNewRenewals('flag')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('flag')]"></i>Flag
                                </th>
                                <th @click="sortNewRenewals('currentMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getNewRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                </th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>

                        <tbody v-if="tab.data.length == 0">
                            <tr>
                                <td class="px-2 py-1" colspan="100%">No New Renewals</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in tab.data" :key="key" :style="{'background-color': colorRow(renewal), background: highlightFilter(renewal)}">
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent" >{{ renewal.applicationId }}</td>
                                <td class="text-end text-nowrap px-2 py-1" :style="{'background-color': colorRow(renewal), background: highlightFilter(renewal)}">
                                    <a class="text-danger cursor-pointer text-decoration-none" @click="investorCardLink(renewal)">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>{{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.originationCompanyName }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.propertyType }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.houseStyle }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" style="width: 40px;">{{ renewal.pos }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ Math.round(renewal.ltv*100)/100 }}%</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.priorMtge) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.origDate)}">{{ formatPhpDate(renewal.origDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.origBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.numberOfNSF }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.otherMortgage }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.flag }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="calculation(renewal, tab.name, 'new-renewals')"
                                    >
                                        <i class="bi-calculator me-1"></i>Calculate
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Renewals -->
    <div class="card mb-3">
        <div class="card-preloader" id="pending-renewals-preloader" style="display: none">
            <div class="d-flex justify-content-center h-100">
                <div
                    class="spinner-border text-primary align-self-center"
                    role="status"
                >
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <div class="card-header">
            <div>Pending Renewals</div>
        </div>

        <div class="card-body">
            <!-- Pending Renewals Tab Headers -->
            <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                <ul class="nav nav-tabs flex-grow-1" id="pending-renewals-tablist" role="tablist">
                    <li class="nav-item" role="presentation" v-for="(tab, tabKey) in pendingRenewalsTab" :key="tabKey">
                        <a
                            v-bind:class="['nav-link', tabKey == 0 ? 'active' : '']"
                            :id="'pending-renewals-tablist-' + tabKey + '-tab'"
                            data-coreui-toggle="tab"
                            :href="'#pending-renewals-tablist-' + tabKey"
                            role="tab"
                            :aria-controls="'pending-renewals-tablist-' + tabKey"
                            aria-selected="true"
                        >
                            {{ tab.name }} ({{ formatNumber(tab.data.length) }})
                        </a>
                    </li>
                </ul>
            </div>
            <!-- Pending Renewals -->
            <div class="tab-content" id="pendingRenewalsTabContent">
                <div v-for="(tab, tabKey) in pendingRenewalsTab" :key="tabKey"
                    v-bind:class="['tab-pane fade show table-responsive px-0', tabKey == 0 ? 'active' : '']"
                    style="max-height: 70dvh;"
                    :id="'pending-renewals-tablist-' + tabKey"
                    role="tabpanel"
                    :aria-labelledby="'pending-renewals-tablist-' + tabKey + '-tab'"
                >
                    <table class="table table-sticky table-hover">
                        <thead>
                            <tr>
                                <th @click="sortPendingRenewals('applicationId')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('applicationId')]"></i>#
                                </th>
                                <th @click="sortPendingRenewals('acctNumber')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('acctNumber')]"></i>Acct #
                                </th>
                                <th @click="sortPendingRenewals('originationCompanyName')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('originationCompanyName')]"></i>Orig Company
                                </th>
                                <th @click="sortPendingRenewals('lastName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('lastName')]"></i>Last Name
                                </th>
                                <th @click="sortPendingRenewals('city')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('city')]"></i>City
                                </th>
                                <th @click="sortPendingRenewals('province')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('province')]"></i>Province
                                </th>
                                <th @click="sortPendingRenewals('propertyType')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('propertyType')]"></i>Property Type
                                </th>
                                <th @click="sortPendingRenewals('houseStyle')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('houseStyle')]"></i>House Style
                                </th>
                                <th @click="sortPendingRenewals('pos')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('pos')]"></i>Position
                                </th>
                                <th @click="sortPendingRenewals('ltv')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('ltv')]"></i>LTV
                                </th>
                                <th @click="sortPendingRenewals('termDueDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                </th>
                                <th @click="sortPendingRenewals('priorMtge')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('priorMtge')]"></i>Prior Mortgage
                                </th>
                                <th @click="sortPendingRenewals('collStatus')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('collStatus')]"></i>Coll Status
                                </th>
                                <th @click="sortPendingRenewals('origDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('origDate')]"></i>Orig Date
                                </th>
                                <th @click="sortPendingRenewals('origBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('origBalance')]"></i>Orig Balance
                                </th>
                                <th @click="sortPendingRenewals('currentBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('currentBalance')]"></i>Current Balance
                                </th>
                                <th @click="sortPendingRenewals('org')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('org')]"></i>Orig Rate
                                </th>
                                <th @click="sortPendingRenewals('rate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('rate')]"></i>Current Rate
                                </th>
                                <th @click="sortPendingRenewals('newInterestRate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('newInterestRate')]"></i>New Rate
                                </th>
                                <th @click="sortPendingRenewals('numberOfNSF')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('numberOfNSF')]"></i># of NSF
                                </th>
                                <th @click="sortPendingRenewals('otherMortgage')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('otherMortgage')]"></i>Other Mortgagee
                                </th>
                                <th @click="sortPendingRenewals('flag')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('flag')]"></i>Flag
                                </th>
                                <th @click="sortPendingRenewals('currentMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                </th>
                                <th @click="sortPendingRenewals('newMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('newMonthlyPayment')]"></i>New Payment
                                </th>
                                <th @click="sortPendingRenewals('pmtVariance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('pmtVariance')]"></i>Payment Variance
                                </th>
                                <th @click="sortPendingRenewals('renewalApprovalNotes')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getPendingRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                </th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody v-if="tab.data.length == 0">
                            <tr>
                                <td class="px-2 py-1" colspan="100%">No Pending Renewals</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in tab.data" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-end text-nowrap px-2 py-1" :style="{'background-color': colorRow(renewal)}">
                                    <a class="text-danger cursor-pointer text-decoration-none" @click="investorCardLink(renewal)">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>{{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.originationCompanyName }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.propertyType }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.houseStyle }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" style="width: 40px;">{{ renewal.pos }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ Math.round(renewal.ltv*100)/100 }}%</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.priorMtge) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.origDate)}">{{ formatPhpDate(renewal.origDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.origBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newInterestRate ? `${renewal.newInterestRate}%` : 'N/A' }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.numberOfNSF }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.otherMortgage }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.flag }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newMonthlyPayment == null ? 'N/A' : `$${formatDecimal(renewal.newMonthlyPayment)}` }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ paymentVariance(renewal) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">
                                    <div class ="d-flex flex-row align-items-center justify-content-end gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            @click="calculation(renewal, tab.name, 'pending-renewals')"
                                        >
                                            <i class="bi-calculator me-1"></i>Calculate
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <RenewalTrackerFilterModal
        :newRenewalsTab="newRenewalsTab"
        :realFilters="realFilters"
        @applyFilters="applyFilters"
    />

    <HighlightModal
        :newRenewalsTab="newRenewalsTab"
        :highlightFilters="highlightFilters"
        @applyHighlight="applyHighlight"
    />

    <RenewalTrackerCalculator
        :selectedRenewalData="selectedRenewalData"
        @updateRenewals="updateRenewals"
    />
</template>

<script>
import { util } from '../../mixins/util'
import RenewalTrackerFilterModal from "../../components/RenewalApproval/RenewalTrackerFilterModal.vue";
import HighlightModal from "../../components/RenewalApproval/HighlightModal.vue";
import RenewalTrackerCalculator from "../../components/RenewalApproval/RenewalTrackerCalculator.vue";

export default {
    mixins: [util],   
    components : { RenewalTrackerFilterModal, RenewalTrackerCalculator, HighlightModal },
    emits: ['events'],
    data() {
        return {
            endDate: null,
            selectedRenewalData: {},
            newRenewalcurrentSort: 'termDueDate',
            newRenewalcurrentSortDir: 'bi-sort-up',
            pendingRenewalcurrentSort: 'termDueDate',
            pendingRenewalcurrentSortDir: 'bi-sort-up',
            isFilterEnabled: false,
            newRenewalsTab: [
                { id: 1, name: 'Fund 1',     data: [] },
                { id: 2, name: 'Fund 2',     data: [] },
                { id: 3, name: 'Fund 3',     data: [] }
                // { id: 4, name: 'AB - Loans', data: [] }
            ],
            pendingRenewalsTab: [
                { id: 1, name: 'Fund 1',     data: [] },
                { id: 2, name: 'Fund 2',     data: [] },
                { id: 3, name: 'Fund 3',     data: [] }
                // { id: 4, name: 'AB - Loans', data: [] }
            ],
            filteredRenewals: [
                { id: 1, name: 'Fund 1',     data: [] },
                { id: 2, name: 'Fund 2',     data: [] },
                { id: 3, name: 'Fund 3',     data: [] }
                // { id: 4, name: 'AB - Loans', data: [] }
            ],
            highlightFilters: {
                provinces: [],
                propertyTypes: [],
                houseStyles: [],
                positions: [],
                collStatuses: [],
                flags: [],
                originationCompanyNames: [],
                otherMortgagees: []
            },
            realFilters:  {
                applicationId: [],     
                acctNumbers: [],       
                lastNames: [],         
                cities: [],            
                provinces: [],
                propertyTypes: [],
                houseStyles: [],
                positions: [],
                collStatuses: [],
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
                foreclosure : false,
                payout: false
            },
        }
    },
    mounted() {
        this.getNewRenewals();
        this.getPendingRenewals();
    },
    computed: {
    },
    watch: {
    },
    methods: {
        getNewRenewals: function() {
            
            this.showSectionPreLoader('new-renewals-preloader')

            // Get date six months from today
            const sixMonthsFromToday = new Date();
            sixMonthsFromToday.setMonth(sixMonthsFromToday.getMonth() + 6);
            sixMonthsFromToday.setHours(0, 0, 0, 0);

            let data = {
                endDate: sixMonthsFromToday
            }

            this.axios.get(
                '/web/renewals',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.newRenewalsTab[0].data = response.data.data.fund1
                    this.newRenewalsTab[1].data = response.data.data.fund2
                    this.newRenewalsTab[2].data = response.data.data.fund3
                    this.getFilterOptions();
                    this.getHighlightFilterOptions();
                    this.isFilterEnabled = true;
                    // this.newRenewalsTab[3].data = response.data.data.abLoans
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
                this.hideSectionPreLoader('new-renewals-preloader')
            })
        },
        getPendingRenewals: function() {     
            this.showSectionPreLoader('pending-renewals-preloader')

            this.axios.get(
                '/web/renewals/pending'
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.pendingRenewalsTab = [
                        { id: 1, name: 'Fund 1',     data: [] },
                        { id: 2, name: 'Fund 2',     data: [] },
                        { id: 3, name: 'Fund 3',     data: [] }
                        // { id: 4, name: 'AB - Loans', data: [] }
                    ],
                    this.pendingRenewalsTab[0].data = response.data.data.fund1
                    this.pendingRenewalsTab[1].data = response.data.data.fund2
                    this.pendingRenewalsTab[2].data = response.data.data.fund3
                    // this.newRenewalsTab[3].data = response.data.data.abLoans
                    var id = 3;
                    var renewalGroups = response.data.data.renewalGroups;

                    Object.entries(renewalGroups).forEach(([key, value], index) => {
                        this.pendingRenewalsTab.push({
                            id: id + index,
                            name: key,
                            data: value
                        });
                    });
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
                this.hideSectionPreLoader('pending-renewals-preloader')
            })
        },
        exportToExcel() {
            this.showPreLoader()

            let data = {
                filterOptions: this.realFilters,
                pageName: 'new-renewals'
            }

            this.axios.post(
                '/web/renewals/excel',
                data,
                { responseType: 'blob'}
            )
                .then(response => {
                    if(response.status == 200) {
                        const blob = new Blob([response.data], {
                            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        });
                        const link = document.createElement("a");
                        link.href = window.URL.createObjectURL(blob);

                        let date = new Date((new Date()).setDate((new Date()).getDate()))

                        link.download = "renewal_" + date.getFullYear() + "-" + date.getMonth().toString().padStart(2, '0') + "-" + date.getDate().toString().padStart(2, '0') + ".xlsx";
                        link.click();

                        this.alertMessage = "Excel exported successfully."
                        this.showAlert("success")
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
        sortNewRenewals: function (key) {
            if (key === this.newRenewalcurrentSort) {
                this.newRenewalcurrentSortDir =
                    this.newRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.newRenewalcurrentSort = key;
            this.filteredRenewals.map(fund => {
                return {
                    ...fund,
                    data: fund.data.sort((a, b) => {
                        let modifier = 1;

                        if (this.newRenewalcurrentSortDir === "bi-sort-up") modifier = -1;
                        if (a[this.newRenewalcurrentSort] < b[this.newRenewalcurrentSort])
                            return -1 * modifier;
                        if (a[this.newRenewalcurrentSort] > b[this.newRenewalcurrentSort])
                            return 1 * modifier;

                        return 0;
                    })
                }
            })
        },
        getNewRenewalSortIcon: function (key) {
            if (this.newRenewalcurrentSort == key) {
                if (this.newRenewalcurrentSort == key) {
                    return this.newRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.newRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.newRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        sortPendingRenewals: function (key) {
            if (key === this.pendingRenewalcurrentSort) {
                this.pendingRenewalcurrentSortDir =
                    this.pendingRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.pendingRenewalcurrentSort = key;
            this.pendingRenewalsTab.map(fund => {
                return {
                    ...fund,
                    data: fund.data.sort((a, b) => {
                        let modifier = 1;

                        if (this.pendingRenewalcurrentSortDir === "bi-sort-up") modifier = -1;

                        if(key == 'pmtVariance') {
                            if(a['pmtVariance'] == "N/A") {
                                a['pmtVariance'] = Number.NEGATIVE_INFINITY;
                            } 

                            if(b['pmtVariance'] == "N/A") {
                                b['pmtVariance'] = Number.NEGATIVE_INFINITY;
                            } 
                        }

                        if (a[this.pendingRenewalcurrentSort] < b[this.pendingRenewalcurrentSort])
                            return -1 * modifier;
                        if (a[this.pendingRenewalcurrentSort] > b[this.pendingRenewalcurrentSort])
                            return 1 * modifier;

                        return 0;
                    })
                }
            })
        },
        getPendingRenewalSortIcon: function (key) {
            if (this.pendingRenewalcurrentSort == key) {
                if (this.pendingRenewalcurrentSort == key) {
                    return this.pendingRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.pendingRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.pendingRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        calculation: function(renewalObj, tabName, sourceTable) {
            this.selectedRenewalData = {
                renewalApprovalId: renewalObj.renewalApprovalId ?? null,
                applicationId: renewalObj.applicationId,
                mortgageCode: renewalObj.acctNumber,
                acctNumber: renewalObj.acctNumber,
                lastName: renewalObj.lastName,
                city: renewalObj.city,
                houseStyle: renewalObj.houseStyle,
                province: renewalObj.province,
                propertyType: renewalObj.propertyType,
                pos: renewalObj.pos,
                ltv: renewalObj.ltv,
                dueDate: renewalObj.termDueDate,
                priorMtge: renewalObj.priorMtge,
                collStatus: renewalObj.collStatus,
                origDate: renewalObj.origDate,
                origBalance: renewalObj.origBalance,
                currentBalance: renewalObj.currentBalance,
                org: renewalObj.org,
                rate: renewalObj.rate,
                numberOfNSF: renewalObj.numberOfNSF,
                otherMortgage: renewalObj.otherMortgage,
                flag: renewalObj.flag,
                newInterestRate: renewalObj.newInterestRate,
                newInterestRateAp: renewalObj.newInterestRateAp,
                newInterestRateBp: renewalObj.newInterestRateBp,
                newInterestRateCp: renewalObj.newInterestRateCp,
                newMonthlyPayment: renewalObj.newMonthlyPayment,
                newMonthlyPaymentAp: renewalObj.newMonthlyPaymentAp,
                newMonthlyPaymentBp: renewalObj.newMonthlyPaymentBp,
                newMonthlyPaymentCp: renewalObj.newMonthlyPaymentCp,
                status: renewalObj.renewalApprovalStatus,
                notes: renewalObj.renewalApprovalNotes,
                renewalFee: renewalObj.renewalFee,
                renewalFeeAp: renewalObj.renewalFeeAp,
                renewalFeeBp: renewalObj.renewalFeeBp,
                renewalFeeCp: renewalObj.renewalFeeCp,
                renewalFeeToBePaidOver: renewalObj.renewalFeeToBePaidOver,
                currentInterestRate: renewalObj.rate,
                currentMonthlyPayment: renewalObj.currentMonthlyPayment,
                mortgageId: renewalObj.mortgageId,
                additionalReviewCategory: renewalObj.additionalReviewCategory,
                tabName:tabName,
                sourceTable: sourceTable,
                userId: renewalObj.userId
            };
            this.showModal("RenewalTrackerCalculator");
        },
        openFilterModal: function() {
            this.showModal("renewalTrackerFilterModal");
        },
        openHighlightModal: function() {
            this.showModal("highlightModal");
        },
        getHighlightFilterOptions: function() {
            
            let data = {
                filterName: "Highlight"
            }

            this.axios.get(
                '/web/renewals/filter-options',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.highlightFilters = JSON.parse((response.data.data));

                    this.applyHighlight(this.highlightFilters, true)
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
            })
        },
        getFilterOptions: function() {
            
            let data = {
                filterName: "Filter"
            }

            this.axios.get(
                '/web/renewals/filter-options',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.realFilters = JSON.parse((response.data.data));
                }

                if(this.realFilters.termEndDueDateOrdered == null || this.realFilters.termEndDueDateOrdered == "") {
                    let date = new Date((new Date()).setDate((new Date()).getDate() + 90));
                    this.endDate = date;
                    
                    let year = this.endDate.getFullYear();
                    let month = String(this.endDate.getMonth() + 1).padStart(2, "0");
                    let day = String(this.endDate.getDate()).padStart(2, "0");

                    this.realFilters.termEndDueDateOrdered = `${year}-${month}-${day}`;
                }

                this.applyFilters(this.realFilters, true)
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
            })
        },
        applyFilters(filters, isInitial = false) {     
            this.filteredRenewals = JSON.parse(JSON.stringify(this.newRenewalsTab));
            this.realFilters = { ...filters };
            
            this.filteredRenewals.forEach(fund => {
                fund.data = fund.data.filter(item => {
                    // Text filters
                    if (Object.keys(this.realFilters.applicationId).length > 0) {
                        if (item.applicationId.toString() !== this.realFilters.applicationId.fullName) {
                            return false;
                        }
                    }

                    if (Object.keys(this.realFilters.acctNumbers).length > 0) {
                        if (item.acctNumber.toString() !== this.realFilters.acctNumbers.fullName) {
                            return false;
                        }
                    }

                    if (Object.keys(this.realFilters.lastNames).length > 0) {
                        if (item.lastName.toLowerCase() !== this.realFilters.lastNames.fullName.toLowerCase()) {
                            return false;
                        }
                    }

                    if (Object.keys(this.realFilters.cities).length > 0) {
                        if (item.city.toLowerCase() !== this.realFilters.cities.fullName.toLowerCase()) {
                            return false;
                        }
                    }

                    // Multi-select filters
                    const multiFilters = [
                        { key: 'provinces', value: item.province },
                        { key: 'propertyTypes', value: item.propertyType },
                        { key: 'houseStyles', value: item.houseStyle },
                        { key: 'positions', value: item.pos },
                        { key: 'collStatuses', value: item.collStatus },
                        { key: 'otherMortgagees', value: item.otherMortgage },
                        { key: 'flags', value: item.flag },
                        { key: 'originationCompanyNames', value: item.originationCompanyName }
                    ];

                    for (const filter of multiFilters) {
                        const selected = this.realFilters[filter.key];
                        if (selected?.length) {
                            const itemValue = filter.value;
                            if (!selected.includes(itemValue)) {
                                return false;
                            }
                        }
                    }

                    // End date filter
                    if (this.realFilters.termEndDueDateOrdered) {
                        var itemDate = this.parseLocalDate(item.termDueDate);
                        var filterDate = this.parseLocalDate(this.realFilters.termEndDueDateOrdered);

                        if (itemDate.getTime() > filterDate.getTime()) {
                            return false;
                        }

                        if(this.isVariableByDate(item.secondPrime, item.secondYear, item.loanTerm, item.intCommDate, filterDate)) {
                            return false;
                        }
                    } 

                    // Start date filter
                    if (this.realFilters.termStartDueDateOrdered) {
                        var itemDate = this.parseLocalDate(item.termDueDate);
                        var filterDate = this.parseLocalDate(this.realFilters.termStartDueDateOrdered);

                        if (itemDate.getTime() < filterDate.getTime()) {
                            return false;
                        }
                    } 

                    if (this.realFilters.origDateOrdered) {
                        var itemDate = this.parseLocalDate(item.origDate);
                        var filterDate = this.parseLocalDate(this.realFilters.origDateOrdered);

                        switch(this.realFilters.origDateOperator) {
                            case '=':
                                if (itemDate.getTime() !== filterDate.getTime()) return false;
                                break;
                            case '>=':
                                if (itemDate.getTime() < filterDate.getTime()) return false;
                                break;
                            case '<=':
                                if (itemDate.getTime() > filterDate.getTime()) return false;
                                break;
                        }
                    } 

                    // Numerical filter
                    const numericFilters = [
                        { key: 'ltv', value: Math.round(item.ltv*100)/100 },
                        { key: 'nsf', value: item.numberOfNSF },
                        { key: 'origBalance', value: item.origBalance },
                        { key: 'currentBalance', value: item.currentBalance },
                        { key: 'org', value: item.org },
                        { key: 'rate', value: item.rate },
                        { key: 'currentMonthlyPayment', value: item.currentMonthlyPayment },
                        { key: 'priorMtge', value: item.priorMtge }
                    ];

                    for (const filter of numericFilters) {
                        const operator = this.realFilters[`${filter.key}Operator`];
                        const filterValue = this.realFilters[`${filter.key}Ordered`];

                        if (filterValue === null || filterValue === undefined || filterValue === '') continue;

                        switch(operator) {
                            case '=':
                                if (filter.value != filterValue) return false;
                                break;
                            case '>=':
                                if (filter.value < filterValue) return false;
                                break;
                            case '<=':
                                if (filter.value > filterValue) return false;
                                break;
                        }
                    }

                    // Foreclosure / Payout filter
                    // if(!this.realFilters.foreclosure && !this.realFilters.payout) {
                    //     if(item.payoutCount > 0 || item.noteCount > 0) {
                    //         return false;
                    //     }
                    // } else if(this.realFilters.foreclosure && this.realFilters.payout) {
                    //     if(item.payoutCount == 0 && item.noteCount == 0) {
                    //         return false;
                    //     }
                    // } else if (this.realFilters.payout) {
                    //     if(item.payoutCount == 0) {
                    //         return false;
                    //     }
                    // } else if (this.realFilters.foreclosure) {
                    //     if(item.noteCount == 0 ) {
                    //         return false;
                    //     }
                    // }
                    return true;
                });
            });

            if(!isInitial) {
                let data = {
                    filterName: "Filter", 
                    filterOptions: JSON.stringify(filters)
                }


                this.axios
                    .post('web/renewals/filter-options',data)
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                        }
                    })
                    .catch(error => {
                        this.alertMessage = error
                        this.showAlert('error')
                    })
                    .finally(() => {
                    })
            }
        },
        applyHighlight(filters, isInitial = false) {
            this.highlightFilters = JSON.parse(JSON.stringify(filters));

            // Province
            if (!Array.isArray(this.highlightFilters.provinces)) {
                this.highlightFilters.provinces = [];
            }

            // Property Type
            if (!Array.isArray(this.highlightFilters.propertyTypes)) {
                this.highlightFilters.propertyTypes = [];
            }

            // House Style
            if (!Array.isArray(this.highlightFilters.houseStyles)) {
                this.highlightFilters.houseStyles = [];
            }

            // Position
            if (!Array.isArray(this.highlightFilters.positions)) {
                this.highlightFilters.positions = [];
            }

            // Collstatus
            if (!Array.isArray(this.highlightFilters.collStatuses)) {
                this.highlightFilters.collStatuses = [];
            }

            // Flags
            if (!Array.isArray(this.highlightFilters.flags)) {
                this.highlightFilters.flags = [];
            }

            // Origination Company
            if (!Array.isArray(this.highlightFilters.originationCompanyNames)) {
                this.highlightFilters.originationCompanyNames = [];
            }

            // Other Mortgagee
            if (!Array.isArray(this.highlightFilters.otherMortgagees)) {
                this.highlightFilters.otherMortgagees = [];
            }

            if(!isInitial) {
                let data = {
                    filterName: "Highlight", 
                    filterOptions: JSON.stringify(filters)
                }


                this.axios
                    .post('web/renewals/filter-options',data)
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                        }
                    })
                    .catch(error => {
                        this.alertMessage = error
                        this.showAlert('error')
                    })
                    .finally(() => {
                    })
            }
        },
        highlightFilter(renewal) {

            if(this.colorRow(renewal) != "#FFFFFF") {
                return this.colorRow(renewal);
            };

            // Province
            if (Object.keys(this.highlightFilters.provinces).length > 0) {
                if(this.highlightFilters.provinces.includes(renewal.province)) {
                    return '#ADD8E6';
                }
            }

            // Property Type
            if (Object.keys(this.highlightFilters.propertyTypes).length > 0) {
                if(this.highlightFilters.propertyTypes.includes(renewal.propertyType)) {
                    return '#ADD8E6';
                }
            }

            // House Style
            if (Object.keys(this.highlightFilters.houseStyles).length > 0) {
                if(this.highlightFilters.houseStyles.includes(renewal.houseStyle)) {
                    return '#ADD8E6';
                }
            }

            // Position
            if (Object.keys(this.highlightFilters.positions).length > 0) {
                if(this.highlightFilters.positions.includes(renewal.pos)) {
                    return '#ADD8E6';
                }
            }

            // Collstatus
            if (Object.keys(this.highlightFilters.collStatuses).length > 0) {
                if(this.highlightFilters.collStatuses.includes(renewal.collStatus)) {
                    return '#ADD8E6';
                }
            }

            // Flags
            if (Object.keys(this.highlightFilters.flags).length > 0) {
                if(this.highlightFilters.flags.includes(renewal.flag)) {
                    return '#ADD8E6';
                }
            }

            // Origination Company
            if (Object.keys(this.highlightFilters.originationCompanyNames).length > 0) {
                if(this.highlightFilters.originationCompanyNames.includes(renewal.originationCompanyName)) {
                    return '#ADD8E6';
                }
            }

            // Other Mortgagee
            if (Object.keys(this.highlightFilters.otherMortgagees).length > 0) {
                if(this.highlightFilters.otherMortgagees.includes(renewal.otherMortgage)) {
                    return '#ADD8E6';
                }
            }
        },
        isVariableByDate(secondPrime, secondYear, loan_term, intCommDate, date) {
            let initial_variable = false;

            let loanTerm = loan_term ? loan_term : 0;
            if (loanTerm > 12 && secondPrime != 0 && secondYear != 0) {
                initial_variable = true;
            }

            if (!initial_variable) {
                return false;
            }

            loanTerm = loan_term ? loan_term : 12;

            const [y, m, d] = intCommDate.split('-').map(Number);
            const varEndDate = new Date(y, m - 1, d);
            varEndDate.setMonth(varEndDate.getMonth() + loanTerm);

            if (varEndDate.getTime() < new Date(date).getTime()) {
                return false;
            } else {
                return true;
            }
        },
        updateRenewals: function(mortgageRenewalObj, updatePending = false) {
            if(updatePending) {
                this.getPendingRenewals()
            }

            if(mortgageRenewalObj.sourceTable === "pending-renewals") {
                if(!updatePending) {
                    this.getPendingRenewals()
                }
                return;
            }
            const acctNumber = mortgageRenewalObj.acctNumber;
            const tabName = mortgageRenewalObj.tabName;
            const applicationId = mortgageRenewalObj.applicationId;
            const mortgageId = mortgageRenewalObj.mortgageId;

            const filteredFund = this.filteredRenewals.find(f => f.name === tabName);
            const unfilteredFund = this.newRenewalsTab.find(f => f.name === tabName);

            if (!filteredFund || !unfilteredFund) {
                console.warn(`No fund found with name: ${tabName}`);
                return;
            }

            const filteredIndex = filteredFund.data.findIndex(item => 
                item.acctNumber === acctNumber &&
                item.applicationId === applicationId &&
                item.mortgageId === mortgageId
            );

            const unfilteredIndex = unfilteredFund.data.findIndex(item => 
                item.acctNumber === acctNumber &&
                item.applicationId === applicationId &&
                item.mortgageId === mortgageId
            );

            if (filteredIndex === -1 || unfilteredIndex === -1) {
                console.warn(`Item not found in ${tabName} with acctNumber=${acctNumber}, applicationId=${applicationId}, mortgageId=${mortgageId}`);
                return;
            }

            filteredFund.data.splice(filteredIndex, 1);
            unfilteredFund.data.splice(unfilteredIndex, 1);
        },
        paymentVariance(renewal) {
            if(renewal.pmtVariance === "N/A" || !renewal.pmtVariance || renewal.pmtVariance === Number.NEGATIVE_INFINITY) {
                return "N/A";
            }

            const variance = parseFloat(renewal.pmtVariance);

            if(variance < 0) {
                return '-$' + Math.abs(variance).toFixed(2);
            }
            return '$' + variance.toFixed(2);
        },
        showSectionPreLoader(id) {
            document.getElementById(id).style.display = "";
        },
        hideSectionPreLoader(id) {
            document.getElementById(id).style.display = "none";
        },
        parseLocalDate(str) {
            const [year, month, day] = str.split('-');
            return new Date(year, month - 1, day);
        },
        investorCardLink: function(renewalObj) {
            window.open('https://tacl-dev-2.amurfinancial.group/TACL/TACL_live/index.php?mortgageId=' + renewalObj.mortgageId + '&userId=' + renewalObj.userId, '_blank', 'noopener,noreferrer');
        },
        colorDate: function(inputDate) {
            const date = new Date(inputDate);
            const todayDate = new Date();

            const diffTime = todayDate - date;
            const diffDate = Math.round(diffTime/(1000 * 60 * 60 * 24))

            if(diffDate >= 1 && diffDate <=2000) { // past due
                return 'red';
            } else if(diffDate >= 0 && diffDate < 1 ) { // today
                return 'blue';
            } else if(diffDate >= -40 && diffDate < 0 ) { // upcoming
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

.card-preloader {
    border-radius: 4px;
    position: absolute;
    left: 0;
    top: 0;
    z-index: 1000;
    width: 100%;
    height: 100%;
    overflow: visible;
    /*background: rgba(169,169,169, .85) no-repeat center center;*/
    background: rgba(0, 0, 0, 0.5) no-repeat center center;
}

.table-cell-max-width {
    max-width: 120px;
}

.table-sticky thead tr:first-child th {
    position: sticky;
    top: -16px;
    background-color: white;
    z-index: 3;
}

.table-sticky tbody td:nth-child(2) {
    position: sticky;
    left: -16px;
    background-color: white;
    z-index: 1;
}

</style>