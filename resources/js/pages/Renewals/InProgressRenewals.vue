<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                Renewals
            </li>
            <li class="breadcrumb-item active">
                In Progress Renewals
            </li>
        </ol>
    </nav>

    <!-- Approved/Rejected Renewals -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>Renewals</div>

                <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search" v-model="search" @input="applyFilters(inProgressFilters)" >
                    </div>

                    <button
                        class="btn btn-primary"
                        @click="openFilterModal"
                        style="min-width: 60px;"
                    >
                        <i class="bi bi-funnel me-1"></i><small>Filter</small>
                    </button>
                </div>
            </div>
        </div>

        <div class = "card-body table-responsive">
            <!-- Approved / Rejected Renewals Tab Headers -->
            <ul class="nav nav-tabs" id="approved-renewals-tablist" role="tablist">
                <li class="nav-item" role="presentation" v-if="isRenewalAdmin || isAdmin">
                    <a
                        class="nav-link"
                        id="approved-renewals-tablist-1-tab"
                        data-coreui-toggle="tab"
                        href="#approved-renewals-tablist-1"
                        role="tab"
                        aria-controls="approved-renewals-tablist-1"
                        :class="{ active: isRenewalAdmin || isAdmin }"
                        aria-selected="true"
                    >Unassigned ({{ formatNumber(inProgressFilteredRenewals[0].data.length + inProgressFilteredRenewals[1].data.length)}})</a>
                </li>

                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link"
                        id="approved-renewals-tablist-2-tab"
                        data-coreui-toggle="tab"
                        href="#approved-renewals-tablist-2"
                        role="tab"
                        :class="{ active: !isRenewalAdmin && !isAdmin }"
                        aria-controls="approved-renewals-tablist-2"
                        aria-selected="false"
                    >Renewal ({{ formatNumber(inProgressFilteredRenewals[2].data.length) }})</a>
                </li>

                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link"
                        id="approved-renewals-tablist-3-tab"
                        data-coreui-toggle="tab"
                        href="#approved-renewals-tablist-3"
                        role="tab"
                        aria-controls="approved-renewals-tablist-3"
                        aria-selected="false"
                    >Non Renewal ({{ formatNumber(inProgressFilteredRenewals[3].data.length) }})</a>
                </li>
            </ul>

            <div class="tab-content" id="approvedRenewalsTabContent">
                <!-- Unassigned Renewals -->
                <div
                    class="tab-pane fade table-responsive p-0"
                    :class="{'show active': isRenewalAdmin || isAdmin}"
                    id="approved-renewals-tablist-1"
                    role="tabpanel"
                    aria-labelledby="approved-renewals-tablist-1-tab"
                    v-if="isRenewalAdmin || isAdmin"
                >
                    <div class="card mx-0 m-3">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div>
                                    Renewals
                                    <span v-if="inProgressFilteredRenewals[0].data.length">
                                        ({{ formatNumber(inProgressFilteredRenewals[0].data.length) }})
                                    </span>
                                </div>

                                <div class="me-auto"></div>

                                <div>
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="assignAgent('renewals')"
                                    >
                                        <i class="bi-pencil me-1"></i>Assign
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class = "card-body table-responsive" style="overflow-y: auto; max-height: 40dvh;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center sticky-header">
                                            <span v-if="selectedUnassignedRenewalsId.length" style="font-weight: 400;">
                                                ({{ formatNumber(selectedUnassignedRenewalsId.length) }})
                                            </span>
                                        </th>
                                        <th @click="sortUnassignedRenewals('applicationId')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('applicationId')]"></i>#
                                        </th>
                                        <th @click="sortUnassignedRenewals('acctNumber')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('acctNumber')]"></i>Acct #
                                        </th>
                                        <th @click="sortUnassignedRenewals('lastName')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('lastName')]"></i>Last Name
                                        </th>
                                        <th @click="sortUnassignedRenewals('city')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('city')]"></i>City
                                        </th>
                                        <th @click="sortUnassignedRenewals('province')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('province')]"></i>Province
                                        </th>
                                        <th @click="sortUnassignedRenewals('pos')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('pos')]"></i>Position
                                        </th>
                                        <th @click="sortUnassignedRenewals('termDueDate')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                        </th>
                                        <th @click="sortUnassignedRenewals('collStatus')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('collStatus')]"></i>Coll Status
                                        </th>
                                        <th @click="sortUnassignedRenewals('investors')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('investors')]"></i>Investor
                                        </th>
                                        <th @click="sortUnassignedRenewals('org')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('org')]"></i>Orig Rate
                                        </th>
                                        <th @click="sortUnassignedRenewals('rate')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('rate')]"></i>Current Rate
                                        </th>
                                        <th @click="sortUnassignedRenewals('newInterestRate')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('newInterestRate')]"></i>New Rate
                                        </th>
                                        <th @click="sortUnassignedRenewals('currentMonthlyPayment')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                        </th>
                                        <th @click="sortUnassignedRenewals('newMonthlyPayment')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('newMonthlyPayment')]"></i>New Payment
                                        </th>
                                        <th @click="sortUnassignedRenewals('renewalApprovalNotes')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                        </th>
                                    </tr>
                                </thead>

                                <tbody v-if="inProgressFilteredRenewals[0].data.length == 0">
                                    <tr>
                                        <td class="text-nowrap px-2 py-1" colspan="100%">No Unassigned Renewals</td>
                                    </tr>
                                </tbody>

                                <tbody v-else>
                                    <tr v-for="(renewal, key) in inProgressFilteredRenewals[0].data" :key="key" :style="{background: colorRow(renewal)}">
                                        <td class="text-nowrap px-2 py-1 bg-transparent">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="selectedUnassignedRenewalsId"
                                                :value="renewal.renewalApprovalId"
                                            />
                                        </td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">
                                            <a 
                                                class="text-danger cursor-pointer text-decoration-none" 
                                                @click="investorCardLink(renewal)
                                            ">
                                                <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                        <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                        <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newInterestRate ? `${renewal.newInterestRate}%` : 'N/A' }}</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newMonthlyPayment == null ? 'N/A' : `$${formatDecimal(renewal.newMonthlyPayment)}` }}</td>
                                        <td class="text-start px-2 py-1 bg-transparent table-cell-max-width" style="max-width: 220px;">{{ renewal.renewalApprovalNotes }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mx-0 m-3">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div> 
                                    Non Renewals
                                    <span v-if="inProgressFilteredRenewals[1].data.length">
                                        ({{ formatNumber(inProgressFilteredRenewals[1].data.length) }})
                                    </span>

                                </div>

                                <div class="me-auto"></div>

                                <div>
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="assignAgent('non-renewals')"
                                    >
                                        <i class="bi-pencil me-1"></i>Assign
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class = "card-body table-responsive" style="overflow-y: auto; max-height: 40dvh;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center sticky-header">
                                            <span v-if="selectedUnassignedNonRenewalsId.length" style="font-weight: 400;">
                                                ({{ selectedUnassignedNonRenewalsId.length }})
                                            </span>
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('applicationId')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('applicationId')]"></i>#
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('acctNumber')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('acctNumber')]"></i>Acct #
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('lastName')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('lastName')]"></i>Last Name
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('city')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('city')]"></i>City
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('province')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('province')]"></i>Province
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('pos')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('pos')]"></i>Position
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('termDueDate')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('collStatus')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('collStatus')]"></i>Coll Status
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('investors')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('investors')]"></i>Investor
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('org')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('org')]"></i>Orig Rate
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('rate')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('rate')]"></i>Current Rate
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('currentMonthlyPayment')" class="text-center sticky-header">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                        </th>
                                        <th @click="sortUnassignedNonRenewals('renewalApprovalNotes')" class="text-center sticky-header table-cell-max-width">
                                            <i class="me-1" v-bind:class="[getUnassignedNonRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                        </th>
                                    </tr>
                                </thead>

                                <tbody v-if="inProgressFilteredRenewals[1].data.length == 0">
                                    <tr>
                                        <td class="text-nowrap px-2 py-1" colspan="100%">No Unassigned Non Renewals</td>
                                    </tr>
                                </tbody>

                                <tbody v-else>
                                    <tr v-for="(renewal, key) in inProgressFilteredRenewals[1].data" :key="key" :style="{background: colorRow(renewal)}">
                                        <td class="text-nowrap px-2 py-1 bg-transparent">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="selectedUnassignedNonRenewalsId"
                                                :value="renewal.renewalApprovalId"
                                            />
                                        </td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">
                                            <a 
                                                class="text-danger cursor-pointer text-decoration-none" 
                                                @click="investorCardLink(renewal)
                                            ">
                                                <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                        <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                        <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                        <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                        <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                        <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Renewal -->
                <div
                    class="tab-pane fade table-responsive px-0"
                    :class="{'show active': (isRenewalAgent && (!isRenewalAdmin || !isAdmin))}"
                    id="approved-renewals-tablist-2"
                    role="tabpanel"
                    style="max-height: 70dvh;"
                    aria-labelledby="approved-renewals-tablist-2-tab"
                >
                    <table class="table table-hover mt-3">
                        <thead>
                            <tr>
                                <th @click="sortAssignedRenewals('applicationId')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('applicationId')]"></i>#
                                </th>
                                <th @click="sortAssignedRenewals('acctNumber')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('acctNumber')]"></i>Acct #
                                </th>
                                <th @click="sortAssignedRenewals('lastName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('lastName')]"></i>Last Name
                                </th>
                                <th @click="sortAssignedRenewals('city')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('city')]"></i>City
                                </th>
                                <th @click="sortAssignedRenewals('province')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('province')]"></i>Province
                                </th>
                                <th @click="sortAssignedRenewals('pos')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('pos')]"></i>Position
                                </th>
                                <th @click="sortAssignedRenewals('termDueDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                </th>
                                <th @click="sortAssignedRenewals('collStatus')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('collStatus')]"></i>Coll Status
                                </th>
                                <th @click="sortAssignedRenewals('investors')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('investors')]"></i>Investor
                                </th>
                                <th @click="sortAssignedRenewals('org')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('org')]"></i>Orig Rate
                                </th>
                                <th @click="sortAssignedRenewals('rate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('rate')]"></i>Current Rate
                                </th>
                                <th @click="sortAssignedRenewals('newInterestRate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('newInterestRate')]"></i>New Rate
                                </th>
                                <th @click="sortAssignedRenewals('currentMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                </th>
                                <th @click="sortAssignedRenewals('newMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('newMonthlyPayment')]"></i>New Payment
                                </th>
                                <th @click="sortAssignedRenewals('renewalApprovalNotes')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                </th>
                                <th @click="sortAssignedRenewals('assignedName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedRenewalSortIcon('assignedName')]"></i>Assigned Member
                                </th>
                                <th class="px-2 py-1"></th>
                            </tr>
                        </thead>

                        <tbody v-if="inProgressFilteredRenewals[2].data.length == 0">
                            <tr>
                                <td class="text-nowrap px-2 py-1" colspan="100%">No Assigned Renewals</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in inProgressFilteredRenewals[2].data" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">
                                    <a 
                                        class="text-danger cursor-pointer text-decoration-none" 
                                        @click="investorCardLink(renewal)
                                    ">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.city }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newInterestRate ? `${renewal.newInterestRate}%` : 'N/A' }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.newMonthlyPayment == null ? 'N/A' : `$${formatDecimal(renewal.newMonthlyPayment)}` }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-wrap px-2 py-1 bg-transparent">{{ renewal.assignedName }}</td>
                                <td class="nowrap">
                                    <button
                                        type="button"
                                        class="btn btn-primary me-1"
                                        @click="reassignAgent(renewal.renewalApprovalId)"
                                        v-if="isRenewalAdmin"
                                    >
                                        <i class="bi-pencil me-1"></i>Reassign
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-primary me-1"
                                        @click="calculation(renewal)"
                                    >
                                        <i class="bi-calculator me-1"></i>Calculation
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary me-1"
                                        @click="documents(renewal)"
                                    >
                                        <i class="bi-files me-1"></i>Documents
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="brokerRequested(renewal)"
                                        :disabled="renewal.brokerApprovalStatus == 'R' || renewal.brokerApprovalStatus == 'A'"
                                    >
                                        <i v-if="renewal.brokerApprovalStatus == 'A'" class="bi-award-fill me-1"></i>
                                        <i v-else class="bi-award me-1"></i>
                                        <span v-if="renewal.brokerApprovalStatus == 'R'">Broker Approval Requested</span>
                                        <span v-else-if="renewal.brokerApprovalStatus == 'A'">Approved by Broker</span>
                                        <span v-else>Request Broker Approval</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Non Renewals -->
                <div
                    class="tab-pane fade table-responsive px-0"
                    id="approved-renewals-tablist-3"
                    role="tabpanel"
                    style="max-height: 70dvh;"
                    aria-labelledby="approved-renewals-tablist-3-tab"
                >
                    <table class="table table-hover mt-3">
                        <thead>
                            <tr>
                                <th @click="sortAssignedNonRenewals('applicationId')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('applicationId')]"></i>#
                                </th>
                                <th @click="sortAssignedNonRenewals('acctNumber')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('acctNumber')]"></i>Acct #
                                </th>
                                <th @click="sortAssignedNonRenewals('lastName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('lastName')]"></i>Last Name
                                </th>
                                <th @click="sortAssignedNonRenewals('city')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('city')]"></i>City
                                </th>
                                <th @click="sortAssignedNonRenewals('province')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('province')]"></i>Province
                                </th>
                                <th @click="sortAssignedNonRenewals('pos')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('pos')]"></i>Position
                                </th>
                                <th @click="sortAssignedNonRenewals('termDueDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                </th>
                                <th @click="sortAssignedNonRenewals('collStatus')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('collStatus')]"></i>Coll Status
                                </th>
                                <th @click="sortAssignedNonRenewals('investors')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('investors')]"></i>Investor
                                </th>
                                <th @click="sortAssignedNonRenewals('org')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('org')]"></i>Orig Rate
                                </th>
                                <th @click="sortAssignedNonRenewals('rate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('rate')]"></i>Current Rate
                                </th>
                                <th @click="sortAssignedNonRenewals('currentMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('currentMonthlyPayment')]"></i>Old Payment
                                </th>
                                <th @click="sortAssignedNonRenewals('renewalApprovalNotes')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                </th>
                                <th @click="sortAssignedNonRenewals('assignedName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getAssignedNonRenewalSortIcon('assignedName')]"></i>Assigned Member
                                </th>
                                <th class="px-2 py-1"></th>
                            </tr>
                        </thead>

                        <tbody v-if="inProgressFilteredRenewals[3].data.length == 0">
                            <tr>
                                <td class="text-nowrap px-2 py-1" colspan="100%">No Assigned Non Renewals</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in inProgressFilteredRenewals[3].data" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">
                                    <a 
                                        class="text-danger cursor-pointer text-decoration-none" 
                                        @click="investorCardLink(renewal)
                                    ">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ renewal.acctNumber }}
                                    </a>
                                </td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.lastName }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.city }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.province }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.pos }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent" :style="{color: colorDate(renewal.termDueDate)}">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-nowrap px-2 py-1 bg-transparent">{{ renewal.investors }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-wrap px-2 py-1 bg-transparent">{{ renewal.assignedName }}</td>
                                <td class="nowrap">
                                    <button
                                        type="button"
                                        class="btn btn-primary me-1"
                                        @click="reassignAgent(renewal.renewalApprovalId)"
                                        v-if="isRenewalAdmin"
                                    >
                                        <i class="bi-pencil me-1"></i>Reassign
                                    </button>
                                    
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary me-1"
                                        @click="documents(renewal)"
                                    >
                                        <i class="bi-files me-1"></i>Documents
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="brokerRequested(renewal)"
                                        :disabled="renewal.brokerApprovalStatus == 'R' || renewal.brokerApprovalStatus == 'A'"
                                    >
                                        <i v-if="renewal.brokerApprovalStatus == 'A'" class="bi-award-fill me-1"></i>
                                        <i v-else class="bi-award me-1"></i>
                                        <span v-if="renewal.brokerApprovalStatus == 'R'">Broker Approval Requested</span>
                                        <span v-else-if="renewal.brokerApprovalStatus == 'A'">Approved by Broker</span>
                                        <span v-else>Request Broker Approval</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <AssignRenewalsModal
        :selectedRenewalsId="selectedRenewalsId"
        @updateApprovedRenewals="getApprovedRenewals"
    />

    <RenewalDocumentsModal
        :applicationId="applicationId"
        :borrowerName="borrowerName"
        :mortgageCode="mortgageCode"
        :mortgageId="selectedMortgageId"
        :renewalApprovalId="selectedRenewalApprovalId"
        :selectedMortgageRenewalId="selectedMortgageRenewalId"
        :companyId="companyId"
        :brokerApprovalStatus="brokerApprovalStatus"
        :refreshCount="refreshCount"
        :originationCompanyFullName="originationCompanyFullName"
        :termDueDate="termDueDate"
    />

    <ConfirmationDialog
        :event="brokerRequestEvent"
        :message="brokerRequestDialogMessage"
        type="success"
        :parentModalId="brokerRequestModalId"
        :key="brokerRequestModalId"
        @return="brokerRequestedResponse"
    />

    <inProgressCalculator
        :selectedMortgageId="selectedMortgageId"
        :selectedMortgageRenewalId="selectedMortgageRenewalId"
        :selectedUserId="selectedUserId"
        @updateApprovedRenewals="getApprovedRenewals"
    />

    <InProgressFilterModal
        :inProgressRenewals="inProgressRenewals"
        @applyFilters="applyFilters"
        @resetFilters="resetFilters"
    />

</template>

<script>
import { util } from '../../mixins/util';
import InProgressFilterModal from "../../components/RenewalApproval/InProgressFilterModal.vue";
import AssignRenewalsModal from "../../components/RenewalApproval/AssignRenewalsModal.vue";
import RenewalDocumentsModal from "../../components/RenewalApproval/RenewalDocumentsModal.vue";
import inProgressCalculator from "../../components/RenewalApproval/inProgressCalculator.vue";
import ConfirmationDialog from "../../components/ConfirmationDialog.vue";

export default {
    mixins: [util],   
    components : { AssignRenewalsModal, RenewalDocumentsModal, inProgressCalculator, ConfirmationDialog, InProgressFilterModal },
    emits: ['events'],
    data() {
        return {
            currentUser: { groupIds: [] },
            inProgressRenewals: [
                { id: 1, name: 'Unassigned Renewals',     data: [] },
                { id: 2, name: 'Unassigned Non-Renewals', data: [] },
                { id: 3, name: 'Assigned Renewals',       data: [] },
                { id: 4, name: 'Non-Renewals',            data: [] }
            ],
            inProgressFilteredRenewals: [
                { id: 1, name: 'Unassigned Renewals',     data: [] },
                { id: 2, name: 'Unassigned Non-Renewals', data: [] },
                { id: 3, name: 'Assigned Renewals',       data: [] },
                { id: 4, name: 'Non-Renewals',            data: [] }
            ],
            search: '',
            selectedMortgageId: null,
            selectedUserId: null,
            selectedRenewalApprovalId: null,
            selectedMortgageRenewalId: null,
            applicationId: null,
            brokerRequestDialogMessage: false,
            brokerRequestEvent: null,
            brokerRequestRenewal: null,
            originationCompanyFullName: null,
            termDueDate: null,
            brokerApprovalStatus: "",
            brokerRequestModalId: "brokerRequest",
            selectedRenewalsId: [],
            selectedUnassignedRenewalsId: [],
            selectedUnassignedNonRenewalsId: [],
            companyId: null,
            borrowerName: null,
            mortgageCode: null,
            unassignedRenewalcurrentSort: 'termDueDate',
            unassignedRenewalcurrentSortDir: 'bi-sort-up',
            unassignedNonRenewalcurrentSort: 'termDueDate',
            unassignedNonRenewalcurrentSortDir: 'bi-sort-up',
            assignedRenewalcurrentSort: 'termDueDate',
            assignedRenewalcurrentSortDir: 'bi-sort-up',
            assignedNonRenewalcurrentSort: 'termDueDate',
            assignedNonRenewalcurrentSortDir: 'bi-sort-up',
            unassignedRenewals: [],
            unassignedNonRenewals: [],
            assigned: [],
            nonRenewals: [],
            refreshCount: 0,
        }
    },
    mounted() {
        this.getCurrentUser()
    },
    computed: {
        isAdmin: function() {
            if(this.currentUser?.groupIds.includes(8)) {
                return true
            }
            return false
        }, 
        isRenewalAdmin: function() {
            if(this.currentUser?.groupIds.includes(56)) {
                return true
            }
            return false
        },
        isRenewalAgent: function() {
            if(this.currentUser?.groupIds.includes(57)) {
                return true
            }
            return false
        }
    },
    methods: {
        sortUnassignedRenewals: function (key) {
            if (key === this.unassignedRenewalcurrentSort) {
                this.unassignedRenewalcurrentSortDir =
                    this.unassignedRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.unassignedRenewalcurrentSort = key;
            this.inProgressFilteredRenewals[0].data.sort((a, b) => {
                let modifier = 1;

                if (this.unassignedRenewalcurrentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.unassignedRenewalcurrentSort] < b[this.unassignedRenewalcurrentSort])
                    return -1 * modifier;
                if (a[this.unassignedRenewalcurrentSort] > b[this.unassignedRenewalcurrentSort])
                    return 1 * modifier;

                return 0;
            })
        },
        getUnassignedRenewalSortIcon: function (key) {
            if (this.unassignedRenewalcurrentSort == key) {
                if (this.unassignedRenewalcurrentSort == key) {
                    return this.unassignedRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.unassignedRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.unassignedRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        sortUnassignedNonRenewals: function (key) {
            if (key === this.unassignedNonRenewalcurrentSort) {
                this.unassignedNonRenewalcurrentSortDir =
                    this.unassignedNonRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.unassignedNonRenewalcurrentSort = key;
            this.inProgressFilteredRenewals[1].data.sort((a, b) => {
                let modifier = 1;

                if (this.unassignedNonRenewalcurrentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.unassignedNonRenewalcurrentSort] < b[this.unassignedNonRenewalcurrentSort])
                    return -1 * modifier;
                if (a[this.unassignedNonRenewalcurrentSort] > b[this.unassignedNonRenewalcurrentSort])
                    return 1 * modifier;

                return 0;
            })
        },
        getUnassignedNonRenewalSortIcon: function (key) {
            if (this.unassignedNonRenewalcurrentSort == key) {
                if (this.unassignedNonRenewalcurrentSort == key) {
                    return this.unassignedNonRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.unassignedNonRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.unassignedNonRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        sortAssignedRenewals: function (key) {
            if (key === this.assignedRenewalcurrentSort) {
                this.assignedRenewalcurrentSortDir =
                    this.assignedRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.assignedRenewalcurrentSort = key;
            this.inProgressFilteredRenewals[2].data.sort((a, b) => {
                let modifier = 1;

                if (this.assignedRenewalcurrentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.assignedRenewalcurrentSort] < b[this.assignedRenewalcurrentSort])
                    return -1 * modifier;
                if (a[this.assignedRenewalcurrentSort] > b[this.assignedRenewalcurrentSort])
                    return 1 * modifier;

                return 0;
            })
        },
        getAssignedRenewalSortIcon: function (key) {
            if (this.assignedRenewalcurrentSort == key) {
                if (this.assignedRenewalcurrentSort == key) {
                    return this.assignedRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.assignedRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.assignedRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        sortAssignedNonRenewals: function (key) {
            if (key === this.assignedNonRenewalcurrentSort) {
                this.assignedNonRenewalcurrentSortDir =
                    this.assignedNonRenewalcurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.assignedNonRenewalcurrentSort = key;
            this.inProgressFilteredRenewals[3].data.sort((a, b) => {
                let modifier = 1;

                if (this.assignedNonRenewalcurrentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.assignedNonRenewalcurrentSort] < b[this.assignedNonRenewalcurrentSort])
                    return -1 * modifier;
                if (a[this.assignedNonRenewalcurrentSort] > b[this.assignedNonRenewalcurrentSort])
                    return 1 * modifier;

                return 0;
            })
        },
        getAssignedNonRenewalSortIcon: function (key) {
            if (this.assignedNonRenewalcurrentSort == key) {
                if (this.assignedNonRenewalcurrentSort == key) {
                    return this.assignedNonRenewalcurrentSortDir + " text-dark";
                } else {
                    return this.assignedNonRenewalcurrentSortDir + " text-gray";
                }
            } else {
                if (this.assignedNonRenewalcurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        getCurrentUser: function() {
            this.axios.get(
                '/web/users/current/groups'
            )
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.currentUser = response.data.data
                    this.getApprovedRenewals() 
                }
            })
            .catch((error) => {
                console.log(error)
            })
        },
        getApprovedRenewals: function() {
            this.showPreLoader()

            this.axios.get('/web/renewals/approved')
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.inProgressRenewals[0].data = response.data.data.unassignedRenewals
                    this.inProgressRenewals[1].data = response.data.data.unassignedNonRenewals
                    this.inProgressRenewals[2].data = response.data.data.assigned
                    this.inProgressRenewals[3].data = response.data.data.nonRenewals

                    this.inProgressFilteredRenewals = JSON.parse(JSON.stringify(this.inProgressRenewals));

                    this.selectedRenewalsId = []
                    this.selectedUnassignedRenewalsId = []
                    this.selectedUnassignedNonRenewalsId = []
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
        reassignAgent: function(renewalId) {
            this.selectedRenewalsId = []
            this.selectedRenewalsId.push(renewalId)
            this.showModal("assignRenewalsModal");
        },
        assignAgent: function(type) {

            if(type == 'renewals') {
                this.selectedRenewalsId = this.selectedUnassignedRenewalsId
            } else if(type == 'non-renewals') {
                this.selectedRenewalsId = this.selectedUnassignedNonRenewalsId
            }

            if(this.selectedRenewalsId.length == 0) {
                this.alertMessage = "Please select an unassigned renewal"
                this.showAlert("error")
                return
            }
            
            this.showModal("assignRenewalsModal");
        },
        documents: function(renewalObj) {
            this.applicationId = renewalObj.applicationId;
            this.selectedMortgageId = renewalObj.mortgageId;
            this.selectedMortgageRenewalId = renewalObj.mortgageRenewalTableId;
            this.selectedRenewalApprovalId = renewalObj.renewalApprovalId;
            this.companyId = renewalObj.companyId;
            this.brokerApprovalStatus = renewalObj.brokerApprovalStatus;
            this.borrowerName = renewalObj.lastName;
            this.mortgageCode = renewalObj.acctNumber;

            this.originationCompanyFullName = renewalObj.originationCompanyFullName;
            this.termDueDate = this.formatPhpDate(renewalObj.termDueDate);
            this.refreshCount++
            this.showModal("createdDocsModal");
        },
        calculation: function(renewalObj) {
            console.log(renewalObj);
            this.selectedMortgageId = renewalObj.mortgageId
            this.selectedMortgageRenewalId = renewalObj.mortgageRenewalTableId
            this.selectedUserId = renewalObj.userId
            this.showModal("inProgressCalculator");
        },
        brokerRequested: function(renewalObj) {
            this.brokerRequestDialogMessage = "Are you sure you want to request broker approval for this renewal?"
            this.brokerRequestEvent = 'brokerRequest';
            this.brokerRequestRenewal = renewalObj
            this.showModal("confirmationDialog" + this.brokerRequestModalId);
        },
        brokerRequestedResponse(event, status) {
            if (status !== 'confirmed') {
                return;
            }

            let docLink = "#"
            if(this.brokerRequestRenewal.companyId == 701) {
                docLink = ("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/SQCTACL/" + this.brokerRequestRenewal.applicationId);
            } else {
                docLink = ("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/ACLTACL/" + this.brokerRequestRenewal.applicationId);
            }

            let emailObj = {
                toAddress: ["adam@amurgroup.ca", "joy@amurgroup.ca"],
                subject: "Broker Requested",
                bodyType: "html",
                body: "<div>A mortgage renewal review has been requested. Please click the link to documents - <a href='" + docLink + "'>View Renewal Document</a></div>",
            }

            let data = {
                renewalApprovalId: this.brokerRequestRenewal.renewalApprovalId,
                emailObj: emailObj,
            }

            this.showPreLoader()

            this.axios.put(
                'web/renewals/broker-request',
                data
            )
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.getApprovedRenewals()
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
        openFilterModal: function() {
            this.showModal("inProgressFilterModal");
        },
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data = this.data
            var status = this.status

            data = data.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return (
                        String(row[key]).toLowerCase().indexOf(search) > -1 &&
                        (status === 'all' || row.status === status)
                    )
                })
            })

            return data
        },
        resetFilters() {
            this.search = '';
        },
        applyFilters(inProgressFilters) {     
            this.inProgressFilteredRenewals = JSON.parse(JSON.stringify(this.inProgressRenewals));

            const search = this.search && this.search.toLowerCase();
            
            this.inProgressFilteredRenewals.forEach(renewalType => {
                renewalType.data = renewalType.data.filter(item => {

                    if(inProgressFilters != null) {
                        // Multi-select filters
                        const multiFilters = [
                            { key: 'assignedNames', value: item.assignedName }
                        ];

                        for (const filter of multiFilters) {
                            const selected = inProgressFilters[filter.key];
                            if (selected?.length) {
                                const itemValue = filter.value;
                                if (!selected.includes(itemValue)) {
                                    return false;
                                }
                            }
                        }

                        // End date filter
                        if (inProgressFilters.termEndDueDateOrdered) {
                            var itemDate = this.parseLocalDate(item.termDueDate);
                            var filterDate = this.parseLocalDate(inProgressFilters.termEndDueDateOrdered);

                            if (itemDate.getTime() > filterDate.getTime()) {
                                return false;
                            }
                        } 

                        // Start date filter
                        if (inProgressFilters.termStartDueDateOrdered) {
                            var itemDate = this.parseLocalDate(item.termDueDate);
                            var filterDate = this.parseLocalDate(inProgressFilters.termStartDueDateOrdered);

                            if (itemDate.getTime() < filterDate.getTime()) {
                                return false;
                            }
                        }
                    }

                    // Search filter (only specific fields)
                    if (search) {
                        const matchesSearch = ['applicationId', 'acctNumber', 'lastName'].some(key => {
                            return String(item[key]).toLowerCase().includes(search);
                        });

                        if (!matchesSearch) {
                            return false;
                        }
                    }

                    return true;
                });
            });
        },
        sendDocs: function(applicationId) {
            this.applicationId = applicationId
            this.showModal("sendDocsModal");
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

            if(diffDate >= 1 && diffDate <= 2000) {
                return 'red';
            } else if(diffDate >= 0 && diffDate < 1 ) {
                return 'blue';
            } else if(diffDate >= -40 && diffDate < 0) {
                return 'green';
            } else if(diffDate < -40) {
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

.table-cell-max-width {
    max-width: 120px;
}

.sticky-header{
    position: sticky;
    top: -16px;
    background-color: white;
    z-index: 1;
}
</style>