<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Renewal Summary
            </li>
        </ol>
    </nav>

    <!-- Summary Table -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>Summary</div>

                <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                    <div class="input-group" style="max-width: 190px;">
                        <span class="input-group-text">Start Date</span>
                        <input type="date" class="form-control" v-model="startDate" @change="updateWebpage()">
                    </div>

                    <div class="input-group" style="max-width: 190px;">
                        <span class="input-group-text">End Date</span>
                        <input type="date" class="form-control" v-model="endDate" @change="updateWebpage()">
                    </div>

                    <button
                        class="btn btn-primary me-2"
                        @click="exportToExcel()"                    
                    >
                        <i class="bi bi-file-earmark-excel me-1"></i><small>Export</small>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-sticky table-hover">
                <thead>
                    <tr>
                        <th class="text-start">
                            Company
                        </th>
                        <th class="text-start">
                            New Renewals
                        </th>
                        <th class="text-start">
                            Pending Renewals
                        </th>
                        <th class="text-start">
                            In Progress Renewals
                        </th>
                        <th class="text-start">
                            Completed Renewals
                        </th>
                    </tr>
                </thead>

                <tbody >
                    <tr>
                        <td class="text-start">
                            Manchester Investments Inc.	(Fund 1)
                        </td>
                        <td class="text-start">
                            {{ formatNumber(newRenewalsCount['Fund1']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(pendingRenewalsCount['Fund1']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(inProgressRenewalsCount['Fund1']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(processedRenewalsCount['Fund1']) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="text-start">
                            Ryan Mortgage Income Fund Inc. (Fund 2)
                        </td>
                        <td class="text-start">
                            {{ formatNumber(newRenewalsCount['Fund2']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(pendingRenewalsCount['Fund2']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(inProgressRenewalsCount['Fund2']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(processedRenewalsCount['Fund2']) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="text-start">
                            Blue Stripe Financial Ltd. (Fund 3)
                        </td>
                        <td class="text-start">
                            {{ formatNumber(newRenewalsCount['Fund3']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(pendingRenewalsCount['Fund3']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(inProgressRenewalsCount['Fund3']) }}
                        </td>
                        <td class="text-start">
                            {{ formatNumber(processedRenewalsCount['Fund3']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- In Progress Renewals -->
    <div class="card mb-3">
        <div class="card-preloader" id="in-progress-renewals-preloader" style="display: none">
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
            <div>In Progress Renewals</div>
        </div>

        <div class="card-body">
            <!-- In Progress Tab Headers -->
            <div>
                <ul class="nav nav-tabs flex-grow-1" id="in-progress-renewals-tablist" role="tablist">
                    <li class="nav-item" role="presentation" v-for="(tab, tabKey) in inProgressRenewalsTab" :key="tabKey">
                        <a
                            v-bind:class="['nav-link', tabKey == 0 ? 'active' : '']"
                            :id="'in-progress-renewals-tablist-' + tabKey + '-tab'"
                            data-coreui-toggle="tab"
                            :href="'#in-progress-renewals-tablist-' + tabKey"
                            role="tab"
                            :aria-controls="'in-progress-renewals-tablist-' + tabKey"
                            aria-selected="true"
                        >
                            {{ tab.name }} ({{ formatNumber(tab.data.length) }})
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- In Progress Renewals -->
            <div class="tab-content" id="inProgressRenewalsTabContent">
                <div v-for="(tab, tabKey) in inProgressRenewalsTab" :key="tabKey"
                    v-bind:class="['tab-pane fade show table-responsive px-0', tabKey == 0 ? 'active' : '']"
                    style="max-height: 70dvh;"
                    :id="'in-progress-renewals-tablist-' + tabKey"
                    role="tabpanel"
                    :aria-labelledby="'in-progress-renewals-tablist-' + tabKey + '-tab'"
                >
                    <table class="table table-sticky table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">
                                    #
                                </th>
                                <th class="text-center">
                                    Acct #
                                </th>
                                <th class="text-center table-cell-max-width">
                                    Orig Company
                                </th>
                                <th class="text-center">
                                    Last Name
                                </th>
                                <th class="text-center table-cell-max-width">
                                    City
                                </th>
                                <th class="text-center">
                                    Province
                                </th>
                                <th class="text-center table-cell-max-width">
                                    Property Type
                                </th>
                                <th class="text-center">
                                    House Style
                                </th>
                                <th class="text-center">
                                    Position
                                </th>
                                <th class="text-center">
                                    LTV
                                </th>
                                <th class="text-center">
                                    Term Due Date
                                </th>
                                <th class="text-center">
                                    Prior Mortgage
                                </th>
                                <th class="text-center table-cell-max-width">
                                    Coll Status
                                </th>
                                <th class="text-center" v-if="tab.isNonRenewal">
                                    Investors
                                </th>
                                <th class="text-center">
                                    Orig Date
                                </th>
                                <th class="text-center">
                                    Orig Balance
                                </th>
                                <th class="text-center">
                                    Current Balance
                                </th>
                                <th class="text-center">
                                    Orig Rate
                                </th>
                                <th class="text-center">
                                    Current Rate
                                </th>
                                <th class="text-center">
                                    New Rate
                                </th>
                                <th class="text-center">
                                    # of NSF
                                </th>
                                <th class="text-center table-cell-max-width">
                                    Other Mortgagee
                                </th>
                                <th class="text-center">
                                    Flag
                                </th>
                                <th class="text-center">
                                    Old Payment
                                </th>
                                <th class="text-center" v-if="!tab.isNonRenewal">
                                    New Payment
                                </th>
                                <th class="text-center" v-if="!tab.isNonRenewal">
                                    Payment Variance
                                </th>
                                <th class="text-center table-cell-max-width">
                                    Comments
                                </th>
                                <th class="text-center">
                                    Assigned Member
                                </th>
                            </tr>
                        </thead>

                        <tbody v-if="tab.data.length == 0">
                            <tr>
                                <td class="px-2 py-1" colspan="100%">No In Progress Renewals</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr v-for="(renewal, key) in tab.data" :key="key" :style="{background: colorRow(renewal)}">
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.applicationId }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">
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
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent" v-if="tab.isNonRenewal">{{ renewal.investors }}</td>
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
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent" v-if="!tab.isNonRenewal">{{ renewal.newMonthlyPayment == null ? 'N/A' : `$${formatDecimal(renewal.newMonthlyPayment)}` }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent" v-if="!tab.isNonRenewal">{{ paymentVariance(renewal) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.assignedName }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],   
    components : {},
    emits: ['events'],
    data() {
        return {
            startDate: null,
            endDate: null,
            newRenewalsCount: {
                'Fund1' : 0,
                'Fund2' : 0,
                'Fund3' : 0
            },
            inProgressRenewalsCount: {
                'Fund1' : 0,
                'Fund2' : 0,
                'Fund3' : 0
            },
            pendingRenewalsCount: {
                'Fund1' : 0,
                'Fund2' : 0,
                'Fund3' : 0
            },
            processedRenewalsCount: {
                'Fund1' : 0,
                'Fund2' : 0,
                'Fund3' : 0
            },
            inProgressRenewalsTab: [
                { id: 1, name: 'Fund 1',     data: [] },
                { id: 2, name: 'Fund 2',     data: [] },
                { id: 3, name: 'Fund 3',     data: [] },
                { id: 4, name: 'Non Renewals',     data: [], isNonRenewal: true }
                // { id: 5, name: 'AB - Loans', data: [] }
            ],
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
        let date = new Date((new Date()).setDate((new Date()).getDate() + 90))
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');

        this.endDate = `${year}-${month}-${day}`;

        this.loadWebpage();
    },
    computed: {
    },
    watch: {
    },
    methods: {
        loadWebpage: function() {
            let data = {
                filterName: "Filter"
            }

            this.axios.get(
                '/web/renewals/filter-options',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    const realFilters = JSON.parse((response.data.data));

                    if(realFilters.termStartDueDateOrdered != null && realFilters.termStartDueDateOrdered != "") {
                        this.startDate = realFilters.termStartDueDateOrdered;
                    }

                    if(realFilters.termEndDueDateOrdered != null && realFilters.termEndDueDateOrdered != "") {
                        this.endDate = realFilters.termEndDueDateOrdered;
                    }
                }

                this.getSummaryData();
                this.getInProgressRenewals();
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
            })
        },
        updateWebpage: function() {
            this.getSummaryData();
            this.getInProgressRenewals();
        },
        getSummaryData: function() {
            this.getNewRenewalsCount();
            this.getInProgressRenewalsCount();
            this.getPendingRenewalsCount();
            this.getProcessedCount();
        },
        getNewRenewalsCount: function() {            
            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                '/web/renewals/count/new-renewals',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.newRenewalsCount['Fund1'] = response.data.data.fund1Count
                    this.newRenewalsCount['Fund2'] = response.data.data.fund2Count
                    this.newRenewalsCount['Fund3'] = response.data.data.fund3Count
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
            })
        },
        getInProgressRenewalsCount: function() {            
            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }
            this.axios.get(
                '/web/renewals/count/in-progress',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.inProgressRenewalsCount['Fund1'] = response.data.data.fund1Count
                    this.inProgressRenewalsCount['Fund2'] = response.data.data.fund2Count
                    this.inProgressRenewalsCount['Fund3'] = response.data.data.fund3Count
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
            })
        },
        getPendingRenewalsCount: function() {            
            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                '/web/renewals/count/pending',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.pendingRenewalsCount['Fund1'] = response.data.data.fund1Count
                    this.pendingRenewalsCount['Fund2'] = response.data.data.fund2Count
                    this.pendingRenewalsCount['Fund3'] = response.data.data.fund3Count
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
            })
        },
        getProcessedCount: function() {            
            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                '/web/renewals/count/processed',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.processedRenewalsCount['Fund1'] = response.data.data.fund1Count
                    this.processedRenewalsCount['Fund2'] = response.data.data.fund2Count
                    this.processedRenewalsCount['Fund3'] = response.data.data.fund3Count
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
            })
        },
        paymentVariance(renewal) {
            if(renewal.newMonthlyPayment != null && renewal.currentMonthlyPayment != null) {
                if((renewal.currentMonthlyPayment - renewal.newMonthlyPayment) < 0) {
                    return '-$' + Math.abs(renewal.currentMonthlyPayment - renewal.newMonthlyPayment).toFixed(2);
                }
                return '$' + (renewal.currentMonthlyPayment - renewal.newMonthlyPayment).toFixed(2);
            }
            return 'N/A';
        },
        showSectionPreLoader(id) {
            document.getElementById(id).style.display = "";
        },
        hideSectionPreLoader(id) {
            document.getElementById(id).style.display = "none";
        },
        exportToExcel() {
            const selectedDate = new Date(this.endDate);
            const sixMonthsFromToday = new Date();
            sixMonthsFromToday.setMonth(sixMonthsFromToday.getMonth() + 6);

            selectedDate.setHours(0, 0, 0, 0);
            sixMonthsFromToday.setHours(0, 0, 0, 0);

            if (selectedDate > sixMonthsFromToday) {
                this.alertMessage = 'End date must be within 6 months.'
                this.showAlert('error')
                return;
            }

            this.showPreLoader()

            this.realFilters.termStartDueDateOrdered = this.startDate;
            this.realFilters.termEndDueDateOrdered = this.endDate;

            let data = {
                filterOptions: this.realFilters,
                pageName: 'renewals-summary'
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
        getInProgressRenewals: function() {
            
            this.showSectionPreLoader('in-progress-renewals-preloader')

            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                '/web/renewals/in-progress',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.inProgressRenewalsTab[0].data = response.data.data.fund1
                    this.inProgressRenewalsTab[1].data = response.data.data.fund2
                    this.inProgressRenewalsTab[2].data = response.data.data.fund3
                    this.inProgressRenewalsTab[3].data = response.data.data.nonRenewals
                    // this.newRenewalsTab[4].data = response.data.data.abLoans
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
                this.hideSectionPreLoader('in-progress-renewals-preloader')
            })
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