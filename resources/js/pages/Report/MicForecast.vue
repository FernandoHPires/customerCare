<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports">Reports</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Mic Forecast
            </li>
        </ol>
    </nav>  

    <div class="card" style="max-height : 85vh">
        <div class="card-header">
            <div class="row">
                    <div class="d-flex" style="align-items: center;">

                        <div class="col-6">
                            <h5>MIC Forecast Report</h5>
                        </div>

                        <div class="col-6 d-flex" style="justify-content: end;">
                            <div class="pe-1">
                                <label>Funding Buffer</label>
                                <input
                                    id="fundingBuffer"
                                    type="number"
                                    class="form-control"
                                    v-model="fundingBuffer"
                                />
                            </div>

                            <div class="pe-1">
                                <label>Signing Buffer</label>
                                <input
                                    id="signingBuffer"
                                    type="number"
                                    class="form-control"
                                    v-model="signingBuffer"
                                />
                            </div>

                            <div class="pe-1">
                                <label>Initial Docs Buffer</label>
                                <input
                                    id="initialDocsBuffer"
                                    type="number"
                                    class="form-control"
                                    v-model="initialDocsBuffer"
                                />
                            </div>
                        </div>
                    </div>                        
            </div>
        </div>        

        <div class="card-body table-responsive">
            
            <div> <!--Current month-->
                <div>
                    <h6>{{ getDate }}</h6>
                </div>
                
                <table class="table table-bordered table-hover"> <!--total-->
                    <thead>
                        <tr>
                            <th class="nowrap company-origin text-white bg-dark-blue">Total for MIC</th>
                            <th v-for="company in companies" :key="company" colspan="4" class="text-center text-white bg-dark-blue">
                                {{ company }}
                            </th>
                            <th class="text-center  text-white bg-dark-blue" colspan="2">Total</th>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Gross Amount</th>
                            <th>Weighted Average LTV</th>
                            <th>Weighted Average Yield</th>
                            <th>Count</th>
                            <th>Gross Amount</th>
                            <th>Weighted Average LTV</th>
                            <th>Weighted Average Yield</th>
                            <th>Count</th>
                            <th>Gross Amount</th>
                            <th>Weighted Average LTV</th>
                            <th>Weighted Average Yield</th>
                            <th>Total Count</th>
                            <th>Total Gross Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="status in statusOrder" class="first-row">
                            <td
                                :class="[
                                    status !== 'Funded' && status !== 'Forecast' ? 'indent' : '',
                                    status === 'Forecast' ? 'forecast' : '',
                                    'first-row'
                                ]">
                                    {{ status }}
                            </td>

                            <template v-if="status === 'Forecast'">
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                            </template>

                            <template class="text-end" v-else v-for="investor in investors" :key="investor.id">
                                <template v-if="getMicForecastForInvestor(investor.id, 88888888, status).length > 0">
                                    <template v-for="row in getMicForecastForInvestor(investor.id, 88888888, status)" :key="row.id">
                                        <td class="text-end pe-3">{{ row.count }}</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.grossAmount) }}</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.ltv) }} %</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.yield) }} %</td>
                                    </template>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                            <td class="text-end pe-3">{{ getTotalCount(status, 88888888) }}</td>
                            <td class="text-end pe-3">{{ formatDecimal(getTotalGross(status, 88888888)) }}</td>                            
                        </tr>

                        <tr>
                            <td class="sub-total-row bold sub-total">
                                <span>Sub Total Forecast </span>
                            </td>
                            <template v-for="investor in investors" :key="investor">
                                <td class="sub-total-row bold text-end pe-3">
                                    {{ getSubTotalCount(investor.id, 88888888) }}
                                </td>
                                <td class="sub-total-row bold text-end pe-3">
                                    {{ formatDecimal(getSubTotalGross(investor.id, 88888888)) }}
                                </td>
                                <td class="sub-total-row"></td>
                                <td class="sub-total-row"></td>
                            </template>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ getSubTotalCountCol(88888888) }}
                            </td>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGrossCol(88888888)) }}
                            </td>
                        </tr>

                        <tr> <!-- getTotalWithFunded -->
                            <td class="total-row bold total">
                                <span>Total</span>
                            </td>
                            <template v-for="investor in investors" :key="investor">
                                <td class="total-row bold text-end pe-3">
                                    {{ getTotalContWithFunded(investor.id, 88888888) }}
                                </td>
                                <td class="total-row bold text-end pe-3">
                                    {{ formatDecimal(getTotalGrossWithFunded(investor.id, 88888888)) }}
                                </td>
                                <td class="total-row"></td>
                                <td class="total-row"></td>
                            </template>
                            <td class="total-row bold text-end pe-3">
                                {{ getSubTotalCountColWithFunded(88888888) }}
                            </td>
                            <td class="total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGrossColWithFunded(88888888)) }}
                            </td>
                        </tr>

                        
                        
                    </tbody>
                </table>
            </div>

            <!-- Apline table with all fileds -->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-green">Alpine Credits</th>
                        <th
                            v-for="company in companies"
                            :key="company"
                            colspan="4"
                            class="text-center text-white bg-green"
                        >
                            {{ company }}
                        </th>
                        <th class="text-center text-white bg-green" colspan="2">Total</th>
                    </tr>
                    <tr class="text-grey">
                        <th>Status</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Total Count</th>
                        <th>Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="first-row " v-for="status in statusOrder">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Forecast' ? 'indent' : '',
                                status === 'Forecast' ? 'forecast' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Forecast'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>                     

                        <template class="text-end" v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="getMicForecastForInvestor(investor.id, 1, status).length > 0">
                                <template v-for="row in getMicForecastForInvestor(investor.id,1,status)" :key="row.id">
                                    <td class="text-end pe-3">{{ row.count }}</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.grossAmount) }}</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.ltv) }} %</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.yield) }} %</td>
                                </template>
                            </template>
                            <template v-else>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </template>
                        </template>
                        <td class="text-end pe-3">{{ getTotalCount(status,1) }}</td>
                        <td class="text-end pe-3">{{ formatDecimal(getTotalGross(status,1)) }}</td>
                    </tr>

                    <tr>
                        <td class="sub-total-row bold sub-total">
                            <span>Sub Total Forecast </span>
                        </td>
                        <template v-for="investor in investors" :key="investor">
                            <td class="sub-total-row bold text-end pe-3">
                                {{ getSubTotalCount(investor.id, 1) }}
                            </td>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGross(investor.id, 1)) }}
                            </td>
                            <td class="sub-total-row"></td>
                            <td class="sub-total-row"></td>
                        </template>
                        <td class="sub-total-row bold text-end pe-3">
                            {{ getSubTotalCountCol(1) }}
                        </td>
                        <td class="sub-total-row bold text-end pe-3">
                            {{ formatDecimal(getSubTotalGrossCol(1)) }}
                        </td>
                    </tr>
                    
                    <tr> <!-- getTotalWithFunded -->
                        <td class="total-row bold total">
                            <span>Total</span>
                        </td>
                        <template v-for="investor in investors" :key="investor">
                            <td class="total-row bold text-end pe-3">
                                {{ getTotalContWithFunded(investor.id, 1) }}
                            </td>
                            <td class="total-row bold text-end pe-3">
                                {{ formatDecimal(getTotalGrossWithFunded(investor.id, 1)) }}
                            </td>
                            <td class="total-row"></td>
                            <td class="total-row"></td>
                        </template>
                        <td class="total-row bold text-end pe-3">
                            {{ getSubTotalCountColWithFunded(1) }}
                        </td>
                        <td class="total-row bold text-end pe-3">
                            {{ formatDecimal(getSubTotalGrossColWithFunded(1)) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!---Sequence Table with all fields-->
            <table class="table table-bordered table-hover table1">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-purple">
                            Sequence Capital
                        </th>
                        <th v-for="company in companies" :key="company" colspan="4" class="text-center text-white bg-purple">
                            {{ company }}
                        </th>
                        <th class="text-center text-white bg-purple" colspan="2">
                            Total
                        </th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Count</th>
                        <th class="text-end pe-3">Gross Amount</th>
                        <th>Weighted Average LTV</th>
                        <th>Weighted Average Yield</th>
                        <th>Total Count</th>
                        <th>Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="first-row " v-for="status in statusOrder">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Forecast' ? 'indent' : '',
                                status === 'Forecast' ? 'forecast' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Forecast'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>                     

                        <template class="text-end" v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="getMicForecastForInvestor(investor.id, 701, status).length > 0">
                                <template v-for="row in getMicForecastForInvestor(investor.id,701,status)" :key="row.id">
                                    <td class="text-end pe-3">{{ row.count }}</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.grossAmount) }}</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.ltv) }} %</td>
                                    <td class="text-end pe-3">{{ formatDecimal(row.yield) }} %</td>
                                </template>
                            </template>
                            <template v-else>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </template>
                        </template>
                        <td class="text-end pe-3">{{ getTotalCount(status,701) }}</td>
                        <td class="text-end pe-3">{{ formatDecimal(getTotalGross(status,701)) }}</td>
                    </tr>

                    <tr>
                        <td class="sub-total-row bold sub-total">
                            <span>Sub Total Forecast </span>
                        </td>
                        <template v-for="investor in investors" :key="investor">
                            <td class="sub-total-row bold text-end pe-3">
                                {{ getSubTotalCount(investor.id, 701) }}
                            </td>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGross(investor.id, 701)) }}
                            </td>
                            <td class="sub-total-row"></td>
                            <td class="sub-total-row"></td>
                        </template>
                        <td class="sub-total-row bold text-end pe-3">
                            {{ getSubTotalCountCol(701) }}
                        </td>
                        <td class="sub-total-row bold text-end pe-3">
                            {{ formatDecimal(getSubTotalGrossCol(701)) }}
                        </td>
                    </tr>
                    
                    <tr> <!-- getTotalWithFunded -->
                        <td class="total-row bold total">
                            <span>Total</span>
                        </td>
                        <template v-for="investor in investors" :key="investor">
                            <td class="total-row bold text-end pe-3">
                                {{ getTotalContWithFunded(investor.id, 701) }}
                            </td>
                            <td class="total-row bold text-end pe-3">
                                {{ formatDecimal(getTotalGrossWithFunded(investor.id, 701)) }}
                            </td>
                            <td class="total-row"></td>
                            <td class="total-row"></td>
                        </template>
                        <td class="total-row bold text-end pe-3">
                            {{ getSubTotalCountColWithFunded(701) }}
                        </td>
                        <td class="total-row bold text-end pe-3">
                            {{ formatDecimal(getSubTotalGrossColWithFunded(701)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--end of sequence-->

            <!--Next Month-->
            <div v-if="this.totalMicForecastNextMonth.length > 0">  
                <div>
                    <h6>{{ getNextMonth }}</h6>
                </div>

                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="nowrap company-origin text-white bg-dark-blue">Total for MIC</th>
                            <th v-for="company in companies" :key="company" colspan="4" class="text-center text-white bg-dark-blue">
                                {{ company }}
                            </th>
                            <th class="text-center  text-white bg-dark-blue" colspan="2">Total</th>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <th class="text-center">Count</th>
                            <th class="text-center">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Count</th>
                            <th class="text-center">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Count</th>
                            <th class="text-center">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Total Count</th>
                            <th class="text-center">Total Gross Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="status in statusOrder" class="first-row">
                            <td
                                :class="[
                                    status !== 'Funded' && status !== 'Forecast' ? 'indent' : '',
                                    status === 'Forecast' ? 'forecast' : '',
                                    'first-row'
                                ]">
                                    {{ status }}
                            </td>

                            <template v-if="status === 'Forecast'">
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                                <td class="first-row"></td>
                            </template>

                            <template class="text-end" v-else v-for="investor in investors" :key="investor.id">
                                <template v-if="getMicForecastForInvestor(investor.id, 99999999, status).length > 0">
                                    <template v-for="row in getMicForecastForInvestor(investor.id, 99999999, status)" :key="row.id">
                                        <td class="text-end pe-3">{{ row.count }}</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.grossAmount) }}</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.ltv) }} %</td>
                                        <td class="text-end pe-3">{{ formatDecimal(row.yield) }} %</td>
                                    </template>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                            <td class="text-end pe-3">{{ getTotalCount(status, 99999999) }}</td>
                            <td class="text-end pe-3">{{ formatDecimal(getTotalGross(status, 99999999)) }}</td>                            
                        </tr>

                        <tr>
                            <td class="sub-total-row bold sub-total">
                                <span>Sub Total Forecast </span>
                            </td>
                            <template v-for="investor in investors" :key="investor">
                                <td class="sub-total-row bold text-end pe-3">
                                    {{ getSubTotalCount(investor.id, 99999999) }}
                                </td>
                                <td class="sub-total-row bold text-end pe-3">
                                    {{ formatDecimal(getSubTotalGross(investor.id, 99999999)) }}
                                </td>
                                <td class="sub-total-row"></td>
                                <td class="sub-total-row"></td>
                            </template>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ getSubTotalCountCol(99999999) }}
                            </td>
                            <td class="sub-total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGrossCol(99999999)) }}
                            </td>
                        </tr>

                        <tr> <!-- getTotalWithFunded -->
                            <td class="total-row bold total">
                                <span>Total</span>
                            </td>
                            <template v-for="investor in investors" :key="investor">
                                <td class="total-row bold text-end pe-3">
                                    {{ getTotalContWithFunded(investor.id, 99999999) }}
                                </td>
                                <td class="total-row bold text-end pe-3">
                                    {{ formatDecimal(getTotalGrossWithFunded(investor.id, 99999999)) }}
                                </td>
                                <td class="total-row"></td>
                                <td class="total-row"></td>
                            </template>
                            <td class="total-row bold text-end pe-3">
                                {{ getSubTotalCountColWithFunded(99999999) }}
                            </td>
                            <td class="total-row bold text-end pe-3">
                                {{ formatDecimal(getSubTotalGrossColWithFunded(99999999)) }}
                            </td>
                        </tr>                       
                    </tbody>
                </table>                               
            </div>
            <!--end of total-->
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";

export default {
    mixins: [util],
    emits: ['events'],
    watch: {
        fundingBuffer: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        signingBuffer: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        initialDocsBuffer: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        }        
    },     
    data() {
        return {
            fundingBuffer: 0,
            signingBuffer: 0,
            initialDocsBuffer: 0,
            micForecast: [],
            totalMicForecast: [],
            totalMicForecastNextMonth: [],
            companies: [
                "Ryan Mortgage Income Fund Inc.",
                "Manchester Investments Inc",
                "Blue Stripe Financial Ltd.",
            ],
            investors: [
                { name: "Ryan Mortgage Income Fund Inc.", id: 31 },
                { name: "Manchester Investments Inc", id: 248 },
                { name: "Blue Stripe Financial Ltd.", id: 100 },
            ],
            statusOrder: [
                "Funded",
                "Forecast",
                "Funding",
                "Signing",
                "Initial Docs",
            ]
        };
    },
    mounted() {
        this.getData()
    },
    computed: {
        getDate() {
            const today = new Date();
            const options = { year: "numeric", month: "long" };
            return today.toLocaleDateString(undefined, options);
        },
        getNextMonth() {
            const today = new Date();
            today.setMonth(today.getMonth() + 1);
            const options = { year: "numeric", month: "long" };
            return today.toLocaleDateString(undefined, options);
        },
    },
    methods: {
        getData: function () {

            this.showPreLoader();

            if (this.fundingBuffer === '') {
                this.fundingBuffer = 0;
            }
            if (this.signingBuffer === '') {
                this.signingBuffer = 0;
            }
            if (this.initialDocsBuffer === '') {
                this.initialDocsBuffer = 0;
            }

            let data = {
                fundingBuffer: this.fundingBuffer,
                signingBuffer: this.signingBuffer,
                initialDocsBuffer: this.initialDocsBuffer,
            };

            this.axios
                .get("/web/reports/mic-forecast", 
                    { params: data }
                ).then((response) => {
                    if (this.checkApiResponse(response)) {

                        this.micForecast = response.data.data.micForecast;
                        this.totalMicForecast = response.data.data.totalMicForecast;
                        this.totalMicForecastNextMonth = response.data.data.totalMicForecastNextMonth;

                        console.log(this.micForecast);

                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        getMicForecastForInvestor(investorId, company, status) {
            if (company === 88888888) {
                return this.totalMicForecast.filter(row => row.investorId === investorId && row.statusOrder === status);
            } else if(company === 99999999) {
                return this.totalMicForecastNextMonth.filter(row => row.investorId === investorId && row.companyId === company && row.statusOrder === status);
            } else {
                return this.micForecast.filter(row => row.investorId === investorId && row.companyId === company && row.statusOrder === status);
            }
            
        },
        getMicSubTotal(investorId, company) {
            if (company === 88888888) {
                return this.totalMicForecast.filter(row => row.investorId === investorId && row.statusOrder !== 'Funded');
            } else if(company === 99999999) {
                return this.totalMicForecastNextMonth.filter(row => row.investorId === investorId && row.statusOrder !== 'Funded');
            } else {
                return this.micForecast.filter(row => row.investorId === investorId && row.companyId === company && row.statusOrder !== 'Funded');
            }            
        },
        getMicSubTotalWithFunded(investorId, company) {
            if (company === 88888888) {
                return this.totalMicForecast.filter(row => row.investorId === investorId);
                
            } else if(company === 99999999) {
                return this.totalMicForecastNextMonth.filter(row => row.investorId === investorId);
            } else {
                return this.micForecast.filter(row => row.investorId === investorId && row.companyId === company);
            }
            
        },       
        getMicSubTotalCol(company) {
            if (company === 88888888) {
                return this.totalMicForecast.filter(row => row.statusOrder !== 'Funded');
            } else if(company === 99999999) {
                return this.totalMicForecastNextMonth.filter(row => row.statusOrder !== 'Funded');
            }else {
                return this.micForecast.filter(row => row.companyId === company && row.statusOrder !== 'Funded');
            }
            
        },
        getMicSubTotalColWithFunded(company) {
            if (company === 88888888) {
                return this.totalMicForecast.filter(row => row.companyId === company);
            } else if(company === 99999999) {
                return this.totalMicForecastNextMonth.filter(row => row.companyId === company);
            } else {
                return this.micForecast.filter(row => row.companyId === company);
            }            
        },
        getTotalCount(status,company) {
            return this.investors.reduce((total, investor) => {
                const micForecasts = this.getMicForecastForInvestor(investor.id, company, status);
                return total + micForecasts.reduce((sum, row) => sum + row.count, 0);
            }, 0);
        },
        getTotalGross(status,company) {
            return this.investors.reduce((total, investor) => {
                const micForecasts = this.getMicForecastForInvestor(investor.id, company, status);
                return total + micForecasts.reduce((sum, row) => sum + row.grossAmount, 0);
            }, 0);
        },
        getSubTotalCount(investorId, company) {
            const micForecasts = this.getMicSubTotal(investorId, company);
            return micForecasts.reduce((sum, row) => sum + row.count, 0);
        },
        getSubTotalGross(investorId, company) {
            const micForecastsAuxC = this.getMicSubTotal(investorId, company);
            return micForecastsAuxC.reduce((sum, row) => sum + row.grossAmount, 0);
        },
        getSubTotalCountCol(company) {
            const micForecastsAux = this.getMicSubTotalCol(company);
            return micForecastsAux.reduce((sum, row) => sum + row.count, 0);
        },
        getSubTotalGrossCol(company) {
            const micForecastsAuxB = this.getMicSubTotalCol(company);
            return micForecastsAuxB.reduce((sum, row) => sum + row.grossAmount, 0);
        },
        getTotalContWithFunded(investorId, company) {
            const micForecasts = this.getMicSubTotalWithFunded(investorId, company);
            return micForecasts.reduce((sum, row) => sum + row.count, 0);
        },        
        getTotalGrossWithFunded(investorId, company) {
            const micForecasts = this.getMicSubTotalWithFunded(investorId, company);
            return micForecasts.reduce((sum, row) => sum + row.grossAmount, 0);
        },
        getSubTotalCountColWithFunded(company) {
            const micForecasts = this.getMicSubTotalColWithFunded(company);
            return micForecasts.reduce((sum, row) => sum + row.count, 0);
        },
        getSubTotalGrossColWithFunded(company) {
            const micForecasts = this.getMicSubTotalColWithFunded(company);
            return micForecasts.reduce((sum, row) => sum + row.grossAmount, 0);
        }             
    },
}
</script>

<style scoped>
.total {
    width: 100%;
    gap: 2rem;
    padding: 0.5rem;
    font-weight: 600;
    background: #80808014;
    color: #2e2b24;
    align-items: center;
    margin: 1rem auto;
    padding-left: 1rem;
}

.indent {
    padding-left: 1rem;
}
.card.mic-pipeline tr.text-grey > th:not(:first-child) {
    color: #4a4a4a;
    text-align: right;
    padding-right: 0.5rem;
}
.card.mic-pipeline tr.text-grey th:first-child {
    color: #4a4a4a;
    text-align: left;
}
.card.mic-pipeline tr th.company-origin {
    text-align: left;
}
.sub-total-row {
    vertical-align: bottom;
    background: #fff2d9;
}
.sub-total {
    display: flex;
    flex-direction: column;
}
.total-row {
    background: #ffd486;
}
table tr.first-row:first-child {
    background: #fff2d9;
    font-weight: 600;
}
.mic-pipeline table {
    border: 1px solid #ededed;
}
th:nth-child(1),
td:nth-child(1) {
    width: 150px;
}
</style>