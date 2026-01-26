<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Processed Renewals
            </li>
        </ol>
    </nav>

    <!-- Processed Renewals -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>Processed Renewals</div>

                <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                    <div class="input-group" style="max-width: 190px;">
                        <span class="input-group-text">Start Date</span>
                        <input type="date" class="form-control" v-model="startDate" @change="getProcessedRenewals()">
                    </div>

                    <div class="input-group" style="max-width: 190px;">
                        <span class="input-group-text">End Date</span>
                        <input type="date" class="form-control" v-model="endDate" @change="getProcessedRenewals()">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Processed Tab Headers -->
            <div class="d-flex flex-row align-items-center justify-content-between gap-2">
                <ul class="nav nav-tabs flex-grow-1" id="processed-renewals-tablist" role="tablist">
                    <li class="nav-item" role="presentation" v-for="(tab, tabKey) in filteredRenewals" :key="tabKey">
                        <a
                            v-bind:class="['nav-link', tabKey == 0 ? 'active' : '']"
                            :id="'processed-renewals-tablist-' + tabKey + '-tab'"
                            data-coreui-toggle="tab"
                            :href="'#processed-renewals-tablist-' + tabKey"
                            role="tab"
                            :aria-controls="'processed-renewals-tablist-' + tabKey"
                            aria-selected="true"
                        >
                            {{ tab.name }} ({{ formatNumber(tab.data.length) }})
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Processed Renewals -->
            <div class="tab-content" id="processedRenewalsTabContent">
                <div v-for="(tab, tabKey) in filteredRenewals" :key="tabKey"
                    v-bind:class="['tab-pane fade show table-responsive px-0', tabKey == 0 ? 'active' : '']"
                    style="max-height: 70dvh;"
                    :id="'processed-renewals-tablist-' + tabKey"
                    role="tabpanel"
                    :aria-labelledby="'processed-renewals-tablist-' + tabKey + '-tab'"
                >
                    <table class="table table-sticky table-hover">
                        <thead>
                            <tr>
                                <th @click="sortProcessedRenewals('applicationId')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('applicationId')]"></i>#
                                </th>
                                <th @click="sortProcessedRenewals('acctNumber')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('acctNumber')]"></i>Acct #
                                </th>
                                <th @click="sortProcessedRenewals('originationCompanyName')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('originationCompanyName')]"></i>Orig Company
                                </th>
                                <th @click="sortProcessedRenewals('lastName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('lastName')]"></i>Last Name
                                </th>
                                <th @click="sortProcessedRenewals('city')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('city')]"></i>City
                                </th>
                                <th @click="sortProcessedRenewals('province')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('province')]"></i>Province
                                </th>
                                <th @click="sortProcessedRenewals('propertyType')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('propertyType')]"></i>Property Type
                                </th>
                                <th @click="sortProcessedRenewals('houseStyle')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('houseStyle')]"></i>House Style
                                </th>
                                <th @click="sortProcessedRenewals('pos')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('pos')]"></i>Position
                                </th>
                                <th @click="sortProcessedRenewals('ltv')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('ltv')]"></i>LTV
                                </th>
                                <th @click="sortProcessedRenewals('termDueDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('termDueDate')]"></i>Term Due Date
                                </th>
                                <th @click="sortProcessedRenewals('processedDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('processedDate')]"></i>Processed Date
                                </th>
                                <th @click="sortProcessedRenewals('priorMtge')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('priorMtge')]"></i>Prior Mortgage
                                </th>
                                <th @click="sortProcessedRenewals('collStatus')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('collStatus')]"></i>Coll Status
                                </th>
                                <th @click="sortProcessedRenewals('origDate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('origDate')]"></i>Orig Date
                                </th>
                                <th @click="sortProcessedRenewals('origBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('origBalance')]"></i>Orig Balance
                                </th>
                                <th @click="sortProcessedRenewals('currentBalance')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('currentBalance')]"></i>Current Balance
                                </th>
                                <th @click="sortProcessedRenewals('org')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('org')]"></i>Orig Rate
                                </th>
                                <th @click="sortProcessedRenewals('rate')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('rate')]"></i>Current Rate
                                </th>
                                <th @click="sortProcessedRenewals('numberOfNSF')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('numberOfNSF')]"></i># of NSF
                                </th>
                                <th @click="sortProcessedRenewals('otherMortgage')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('otherMortgage')]"></i>Other Mortgagee
                                </th>
                                <th @click="sortProcessedRenewals('flag')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('flag')]"></i>Flag
                                </th>
                                <th @click="sortProcessedRenewals('currentMonthlyPayment')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('currentMonthlyPayment')]"></i>Current Payment
                                </th>
                                <th @click="sortProcessedRenewals('renewalApprovalNotes')" class="text-center table-cell-max-width">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('renewalApprovalNotes')]"></i>Comments
                                </th>
                                <th @click="sortProcessedRenewals('assignedName')" class="text-center">
                                    <i class="me-1" v-bind:class="[getProcessedRenewalSortIcon('assignedName')]"></i>Assigned Member
                                </th>
                            </tr>
                        </thead>

                        <tbody v-if="tab.data.length == 0">
                            <tr>
                                <td class="px-2 py-1" colspan="100%">No Processed Renewals</td>
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
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ formatPhpDate(renewal.termDueDate) }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ formatPhpDate(renewal.processedDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.priorMtge) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.collStatus }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ formatPhpDate(renewal.origDate) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.origBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentBalance) }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.org }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.rate }}%</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">{{ renewal.numberOfNSF }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.otherMortgage }}</td>
                                <td class="text-start text-nowrap px-2 py-1 bg-transparent">{{ renewal.flag }}</td>
                                <td class="text-end text-nowrap px-2 py-1 bg-transparent">${{ formatDecimal(renewal.currentMonthlyPayment) }}</td>
                                <td class="text-start px-2 py-1 bg-transparent table-cell-max-width">{{ renewal.renewalApprovalNotes }}</td>
                                <td class="text-wrap px-2 py-1 bg-transparent">{{ renewal.assignedName }}</td>
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
    components : { },
    emits: ['events'],
    data() {
        return {
            startDate: null,
            endDate: null,
            processedRenewalCurrentSort: 'processedDate',
            processedRenewalCurrentSortDir: 'bi-sort-up',
            isFilterEnabled: false,
            processedRenewalsTab: [
                { id: 1, name: 'Fund 1',     data: [] },
                { id: 2, name: 'Fund 2',     data: [] },
                { id: 3, name: 'Fund 3',     data: [] }
                // { id: 4, name: 'AB - Loans', data: [] }
            ],
            filteredRenewals: []
        }
    },
    mounted() {
        let date = new Date((new Date()).setDate((new Date()).getDate() + 90))
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');

        this.endDate = `${year}-${month}-${day}`

        this.getProcessedRenewals()
    },
    computed: {
    },
    watch: {
    },
    methods: {
        applyFilter(filteredData) {
            this.filteredRenewals = JSON.parse(JSON.stringify(filteredData));
        },
        applyInitialFilter() {
            this.filteredRenewals =  this.processedRenewalsTab.map(fund => {
                return {
                    ...fund, 
                    data: fund.data.filter(renewal => {
                        if(renewal.payoutCount == 0 && renewal.noteCount == 0) {
                            return true;
                        }
                        return false;
                    })
                };
            });
        },
        sortProcessedRenewals: function (key) {
            if (key === this.processedRenewalCurrentSort) {
                this.processedRenewalCurrentSortDir =
                    this.processedRenewalCurrentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }
            this.processedRenewalCurrentSort = key;
            this.filteredRenewals.map(fund => {
                return {
                    ...fund,
                    data: fund.data.sort((a, b) => {
                        let modifier = 1;

                        if (this.processedRenewalCurrentSortDir === "bi-sort-up") modifier = -1;
                        if (a[this.processedRenewalCurrentSort] < b[this.processedRenewalCurrentSort])
                            return -1 * modifier;
                        if (a[this.processedRenewalCurrentSort] > b[this.processedRenewalCurrentSort])
                            return 1 * modifier;

                        return 0;
                    })
                }
            })
        },
        getProcessedRenewalSortIcon: function (key) {
            if (this.processedRenewalCurrentSort == key) {
                if (this.processedRenewalCurrentSort == key) {
                    return this.processedRenewalCurrentSortDir + " text-dark";
                } else {
                    return this.processedRenewalCurrentSortDir + " text-gray";
                }
            } else {
                if (this.processedRenewalCurrentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        getProcessedRenewals: function() {
            
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate
            }

            this.axios.get(
                '/web/renewals/processed',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.processedRenewalsTab[0].data = response.data.data.fund1
                    this.processedRenewalsTab[1].data = response.data.data.fund2
                    this.processedRenewalsTab[2].data = response.data.data.fund3
                    this.applyInitialFilter()
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
                this.hidePreLoader()
            })
        },
        investorCardLink: function(renewalObj) {
            window.open('https://tacl-dev-2.amurfinancial.group/TACL/TACL_live/index.php?mortgageId=' + renewalObj.mortgageId + '&userId=' + renewalObj.userId, '_blank', 'noopener,noreferrer');
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
</style>