<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                Finance
            </li>
            <li class="breadcrumb-item active">
                PAP Updates
            </li>
        </ol>
    </nav>

    <div class="mb-4">
        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        Summary
                    </div>
                    <div class="card-body">

                        <div class="d-flex">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Done</th>
                                        <th>Pending</th>
                                        <th>Rejected</th>
                                    </tr>
                                </thead>
                                
                                <tbody v-if="papCompanies.length == 0">
                                    <tr>
                                        <td colspan="7">No transactions</td>
                                    </tr>
                                </tbody>

                                <tbody v-else>
                                    <tr v-for="(company, key) in papCompanies" :key="key">
                                        <td>{{ company.name }}</td>
                                        <td>{{ company.done }}</td>
                                        <td>{{ company.pending }}</td>
                                        <td>{{ company.rejected }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        Filters
                    </div>
                    <div class="card-body">

                        <div class="d-flex">
                            <div class="pe-1">
                                <label for="" class="form-label">Company</label>
                                <select class="form-select" v-model="companyIdFilter" @change="getData()">
                                    <option value="0">All</option>
                                    <option v-for="(company, key) in papCompanies" :key="key" :value="company.id">{{ company.name }}</option>
                                </select>
                            </div>

                            <div class="pe-1">
                                <label for="" class="form-label">Start Date</label>
                                <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                            @change="getData()"
                                        />
                                    </template>
                                </DatePicker>
                            </div>

                            <div class="pe-1">
                                <label for="" class="form-label">End Date</label>
                                <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                                    <template v-slot="{ inputValue, inputEvents }">
                                        <input
                                            class="form-control"
                                            :value="inputValue"
                                            v-on="inputEvents"
                                            @change="getData()"
                                        />
                                    </template>
                                </DatePicker>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--Split Payment-->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div>
                    Split Payment
                </div>

                <div class="ms-auto"></div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover mb-0">
                <!-- Split Payment -->
            <thead>
                <tr>
                    <th @click="sort(512, 'applicationId')">
                        <i :class="getSortIcon(512, 'applicationId')" class="ms-1"></i>
                        TACL#
                    </th>
                    <th @click="sort(512, 'mortgageCode')">
                        <i :class="getSortIcon(512, 'mortgageCode')" class="ms-1"></i>
                        Mortgage Code
                    </th>
                    <th @click="sort(512, 'clientName')">
                        <i :class="getSortIcon(512, 'clientName')" class="ms-1"></i>
                        Client Name
                    </th>
                    <th @click="sort(512, 'status')">
                        <i :class="getSortIcon(512, 'status')" class="ms-1"></i>
                        Status
                    </th>
                    <th @click="sort(512, 'date')">
                        <i :class="getSortIcon(512, 'date')" class="ms-1"></i>
                        Created At
                    </th>
                    <th></th>
                </tr>
            </thead>
                <tbody v-if="transactions.length == 0">
                    <tr>
                        <td colspan="6">No updates</td> 
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(transaction, key) in getTransactions(512)" :key="key">
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.applicationId }}</span></td>
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.mortgageCode }}</span></td>
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.clientName }}</span></td>

                        <td>
                            <status-badge :status="transaction.status"></status-badge>
                        </td>
                        <td>{{ transaction.displayDate }}</td> 

                        <td class="text-end">
                            <template v-if="transaction.status == 'Done'">
                                <button type="button" class="btn btn-secondary me-2" disabled="true">Validated</button>
                            </template>

                            <template v-else-if="transaction.status != 'Rejected'">
                                <button type="button" class="btn btn-secondary me-2"
                                    @click="showValidate(transaction)">Validate
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- PAP Date Change -->
<div class="card mb-3">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <div>PAP Date Change</div>
            <div class="ms-auto"></div>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th @click="sort(511, 'applicationId')">
                        <i :class="getSortIcon(511, 'applicationId')" class="ms-1"></i>
                        TACL#
                    </th>
                    <th @click="sort(511, 'mortgageCode')">
                        <i :class="getSortIcon(511, 'mortgageCode')" class="ms-1"></i>
                        Mortgage Code
                    </th>
                    <th @click="sort(511, 'clientName')">
                        <i :class="getSortIcon(511, 'clientName')" class="ms-1"></i>
                        Client Name
                    </th>
                    <th @click="sort(511, 'status')">
                        <i :class="getSortIcon(511, 'status')" class="ms-1"></i>
                        Status
                    </th>
                    <th @click="sort(511, 'date')">
                        <i :class="getSortIcon(511, 'date')" class="ms-1"></i>
                        Created At
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody v-if="transactions.length == 0">
                <tr>
                    <td colspan="6">No updates</td> <!-- Already correct -->
                </tr>
            </tbody>
            <tbody v-else>
                <tr v-for="(transaction, key) in getTransactions(511)" :key="key">
                    <td><span :class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.applicationId }}</span></td>
                    <td><span :class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.mortgageCode }}</span></td>
                    <td><span :class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.clientName }}</span></td>
                    <td><status-badge :status="transaction.status"></status-badge></td>
                    <td>{{ transaction.displayDate }}</td>
                    <td class="text-end">
                        <template v-if="transaction.status == 'Done'">
                            <button type="button" class="btn btn-secondary me-2" disabled="true">Validated</button>
                        </template>
                        <template v-else-if="transaction.status != 'Rejected'">
                            <button type="button" class="btn btn-secondary me-2" @click="showValidate(transaction)">Validate</button>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

    <!--PAP Amount Change-->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div>
                    PAP Amount Change
                </div>

                <div class="ms-auto"></div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover mb-0">
                <!-- PAP Amount Change -->
                <thead>
                    <tr>
                        <th @click="sort(534, 'applicationId')">
                            <i :class="getSortIcon(534, 'applicationId')" class="ms-1"></i>
                            TACL#
                        </th>
                        <th @click="sort(534, 'mortgageCode')">
                            <i :class="getSortIcon(534, 'mortgageCode')" class="ms-1"></i>
                            Mortgage Code
                        </th>
                        <th @click="sort(534, 'clientName')">
                            <i :class="getSortIcon(534, 'clientName')" class="ms-1"></i>
                            Client Name
                        </th>
                        <th @click="sort(534, 'status')">
                            <i :class="getSortIcon(534, 'status')" class="ms-1"></i>
                            Status
                        </th>
                        <th @click="sort(534, 'date')">
                            <i :class="getSortIcon(534, 'date')" class="ms-1"></i>
                            Created At
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody v-if="transactions.length == 0">
                    <tr>
                        <td colspan="6">No updates</td> <!-- Changed from 7 to 6 -->
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(transaction, key) in getTransactions(534)" :key="key">
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.applicationId }}</span></td>
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.mortgageCode }}</span></td>
                        <td><span v-bind:class="[transaction.status == 'Done' ? 'text-gray' : '']">{{ transaction.clientName }}</span></td>

                        <td>
                            <status-badge :status="transaction.status"></status-badge>
                        </td>
                        <td>{{ transaction.displayDate }}</td> 
                        <td class="text-end">
                            <template v-if="transaction.status == 'Done'">
                                <button type="button" class="btn btn-secondary me-2" disabled="true">Validated</button>
                            </template>

                            <template v-else-if="transaction.status != 'Rejected'">
                                <button type="button" class="btn btn-secondary me-2"
                                    @click="showValidate(transaction)">Validate
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!--Modals-->
    <pap-validate-update
        :transaction="actualTransaction"
        @refresh="getData()">
    </pap-validate-update>
</template>

<script>
import { util } from '../../mixins/util'
import PapValidateUpdate from '../../components/PAP/ValidateUpdate'
import StatusBadge from '../../components/StatusBadge'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { PapValidateUpdate, DatePicker, StatusBadge },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        }
    },
    data() {
        return {
            transactions: [],
            actualTransaction: {},
            companyId: '',
            endDate: new Date(),
            startDate: new Date(),
            companyIdFilter: 0,
            sortStates: {
                512: { currentSort: '', currentSortDir: 'bi-sort-down' }, // Split Payment
                511: { currentSort: '', currentSortDir: 'bi-sort-down' }, // PAP Date Change
                534: { currentSort: '', currentSortDir: 'bi-sort-down' }  // PAP Amount Change
            },
            papCompanies: []
        }
    },
    mounted() {
        this.getData()
    },
    methods: {
        getTransactions(categoryId) {
            const catId = Number(categoryId);
            let transactions = this.transactions.filter(row => row.requestTypeId === catId);

            const state = this.sortStates[catId] || { currentSort: '', currentSortDir: 'bi-sort-down' };
            if (state.currentSort) {
                transactions.sort((a, b) => {
                    let modifier = 1;
                    if (state.currentSortDir === 'bi-sort-up') modifier = -1;

                    let aValue = a[state.currentSort];
                    let bValue = b[state.currentSort];

                    if (state.currentSort === 'date') {
                        aValue = a.createdAt && a.createdAt.date ? new Date(a.createdAt.date) : null;
                        bValue = b.createdAt && b.createdAt.date ? new Date(b.createdAt.date) : null;
                        if (aValue === null && bValue === null) return 0;
                        if (aValue === null) return 1 * modifier;
                        if (bValue === null) return -1 * modifier;
                        return (aValue - bValue) * modifier;
                    }

                    if (aValue < bValue) return -1 * modifier;
                    if (aValue > bValue) return 1 * modifier;
                    return 0;
                });
            }

            return transactions.map(transaction => ({
                ...transaction,
                displayDate: transaction.createdAt && transaction.createdAt.date 
                    ? new Date(transaction.createdAt.date).toLocaleDateString() 
                    : 'N/A'
            }));
        },
        sort(categoryId, key) {
            const catId = Number(categoryId);
            if (!this.sortStates[catId]) {
                this.sortStates[catId] = { currentSort: '', currentSortDir: 'bi-sort-down' };
            }

            const state = this.sortStates[catId];
            if (key === state.currentSort) {
                state.currentSortDir =
                    state.currentSortDir === 'bi-sort-down'
                        ? 'bi-sort-up'
                        : 'bi-sort-down';
            } else {
                state.currentSort = key;
                state.currentSortDir = 'bi-sort-down';
            }
        },
        getSortIcon(categoryId, key) {
            const catId = Number(categoryId);
            const state = this.sortStates[catId] || { currentSort: '', currentSortDir: 'bi-sort-down' };
            if (state.currentSort === key) {
                return `${state.currentSortDir} text-dark`;
            }
            return 'bi-sort-down text-gray';
        },
        showValidate(transaction) {
            this.actualTransaction = transaction;
            this.showModal('validateUpdate');
        },
        getDoneSummary() {
            this.papCompanies.forEach(company => {
                company.done = 0;
                company.pending = 0;
                company.rejected = 0;
            });

            this.transactions.forEach(transaction => {
                this.papCompanies.forEach((company, index) => {
                    if (company.id === transaction.companyId) {
                        if (transaction.status === 'Done') {
                            this.papCompanies[index].done++;
                        } else if (transaction.status === 'Pending') {
                            this.papCompanies[index].pending++;
                        } else {
                            this.papCompanies[index].rejected++;
                        }
                    }
                });
            });
        },
        getData() {
            this.showPreLoader();
            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
                companyIdFilter: this.companyIdFilter,
            };
            this.axios.get('web/pap/updates', { params: data })
                .then(response => {
                    if (this.checkApiResponse(response)) {
                        this.transactions = response.data.data;
                        this.getDoneSummary();
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch(error => {
                    this.alertMessage = error;
                    this.showAlert('error');
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        }
    }
}
</script>