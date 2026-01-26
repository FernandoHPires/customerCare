<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Reports</li>
            <li class="breadcrumb-item">Sales</li>
            <li class="breadcrumb-item active">Commercial Loans Tracker</li>
        </ol>
    </nav>

    <div class="card" style="max-height: 80vh">
        <!--<div class="card-header">
            <div class="d-flex">
                <div class="pe-2">
                    <label>Start Date</label>
                    <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                        <template v-slot="{ inputValue, inputEvents }">
                            <input
                                class="form-control"
                                :value="inputValue"
                                v-on="inputEvents"
                            />
                        </template>
                    </DatePicker>
                </div>

                <div class="pe-2">
                    <label>End Date</label>
                    <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                        <template v-slot="{ inputValue, inputEvents }">
                            <input
                                class="form-control"
                                :value="inputValue"
                                v-on="inputEvents"
                            />
                        </template>
                    </DatePicker>
                </div>

                <div class="me-auto"></div>

            </div>
        </div>-->

        <div class="card-body table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Funded Date</th>
                        <th>TACL#</th>
                        <th>Mortgage Code</th>
                        <th>Investor Code</th>
                        <th>ABL</th>
                        <th>Borrower</th>
                        <th>City</th>
                        <th>Province</th>
                        <th>Property Type</th>
                        <th>Syndicated</th>
                        <!--<th>Construction</th>-->
                        <th>Position</th>
                        <th>Priority</th>
                        <th>Pari Passu</th>
                        <th>Authorized Amount</th>
                        <th>Funded Balance</th>
                        <th>Current Balance</th>
                        <th>Appraised Value</th>
                        <th>Total Prior Mortgage </th>
                        <th>Current LTV</th>
                        <th>Yield</th>
                        <th>Fees %</th>
                        <th>Fees</th>
                        <th>Annual Interest</th>
                        <th>Annual Interest Rate</th>
                        <th>Maturity Date</th>
                        <th>Direct Referral</th>
                        <th>Paid Out</th>
                        <!--<th>Referring Brokerage</th>-->
                        <th>Broker</th>
                        <th>Collection Status</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(row, key) in rows" :key="key">
                        <td>{{ row.fundedDate }}</td>
                        <td>{{ row.applicationId }}</td>
                        <td>{{ row.mortgageCode }}</td>
                        <td>{{ row.investorCode }}</td>
                        <td>{{ row.ablCode }}</td>
                        <td>{{ row.applicantName }}</td>
                        <td style="max-width: 180px;">{{ row.cities }}</td>
                        <td style="max-width: 120px;">{{ row.provinces }}</td>
                        <td style="max-width: 180px;">{{ row.propertyTypes }}</td>
                        <td>{{ row.syndicated }}</td>
                        <!--<td>{{ row.construction }}</td>-->
                        <td style="max-width: 100px;">{{ row.positions }}</td>
                        <td>{{ row.priority }}</td>
                        <td>{{ row.pariPassu }}</td>
                        <td>{{ formatDecimal(row.authorizedAmount) }}</td>
                        <td>{{ formatDecimal(row.fundedBalance) }}</td>
                        <td>{{ formatDecimal(row.currentBalance) }}</td>
                        <td>{{ formatDecimal(row.appraisedValue) }}</td>
                        <td>{{ formatDecimal(row.totalPriorMortgage)  }}</td>
                        <td>{{ row.currentLTV }}</td>
                        <td>{{ row.yield }}</td>
                        <td>{{ row.feesPerc }}</td>
                        <td>{{ formatDecimal(row.fees) }}</td>
                        <td>{{ formatDecimal(row.annualInterest) }}</td>
                        <td>{{ row.interestRate }}</td>
                        <td>{{ row.maturityDate }}</td>
                        <td>{{ row.directReferral }}</td>
                        <td>{{ row.paidOut }}</td>
                        <!--<td>{{ row.referringBrokerage }}</td>-->
                        <td>{{ row.broker }}</td>
                        <td>{{ row.collectionStatus }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import { DatePicker } from 'v-calendar'

export default {
    mixins: [util],
    emits: ['events'],
    components: { DatePicker },
    watch: {
        /*startDate: {
            handler(newValue, oldValue) {
                if(oldValue !== null) this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                if(oldValue !== null) this.getData()
            },
            deep: true
        }*/
    },
    data() {
        return {
            columns: [],
            rows: [],
            startDate: null,
            endDate: null,
        }
    },
    beforeMount() {
        //var d = new Date()
        //const month = d.getMonth();
        //while(d.getMonth() === month) {
        //    d.setDate(d.getDate() - 1);
        //}
        //this.startDate = new Date(d.getFullYear(), d.getMonth() + 1, 1)
        //this.endDate = new Date(d.getFullYear(), d.getMonth() + 2, 0)
    },
    mounted() {
        this.getData()
    },
    methods: {
        getData: function() {
            this.showPreLoader()

            let data = {
                //startDate: this.startDate,
                //endDate: this.endDate,
            }

            this.axios.get(
                '../web/reports/commercial-loans-tracker',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.rows = response.data.data
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
        }
    }
}
</script>
