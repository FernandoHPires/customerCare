<template>
    <template v-if="selectedTab === 'disbursement'">
        <div class="card mb-3" v-for="(quote, key) in savedQuotes" :key="key">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Mortgage {{ key + 1 }}</b></span>
                    </div>
                </div>
            </div>                         
            <div class="card-body">
                <div class="d-flex">
                    <div class="form-group px-1">
                        <label class="fw-bold">Client's Authorized Date</label>
                        <small>
                            <p class="mb-1"> {{ formatDate(quote.clientAuthDate) }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1">
                        <label class="fw-bold">Documentation</label>
                        <small>
                            <p class="mb-1"> {{ quote.documentation }}</p>
                        </small>
                    </div>
                </div>

                <hr>

                <div class="d-flex align-items-end flex-wrap" v-for="(prop, p) in quote.savedQuotePosition" :key="p">
                    <div class="form-group px-1">
                        <label class="fw-bold">RE: {{ prop.position }} Mortgage of (Property Address)</label>
                        <small>
                            <p class="mb-1"> 
                                {{ prop.address }}
                                known as (PID / Legal Description)
                                {{ prop.pid }}
                                {{ prop.legal }} 
                                placed by
                                {{ prop.companyName }}
                            </p>
                        </small>
                    </div>  
                </div>
                
                <hr>

                <div class="d-flex align-items-end flex-wrap">
                    <div class="form-group px-1">
                        <label class="fw-bold">Mortgagor(s)</label>
                        <small>
                            <p class="mb-1"> {{ quote.mortgagorsName }}</p>
                        </small>
                    </div> 
                </div>

                <div class="d-flex align-items-end flex-wrap">
                    <div class="form-group px-1">
                        <label class="fw-bold">Mailing Address(es)</label>
                        <small v-for="(address, a) in quote.propertyAddresses" :key="a">
                            <p class="mb-1"> {{ address }}</p>
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-end flex-wrap">
                    <div class="form-group px-1">
                        <label class="fw-bold">Guarantors</label>
                        <small>
                            <p class="mb-1"> {{ this.guarantors }}</p>
                        </small>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold">Mortgage Terms</h6>

                <div class="d-flex">
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Amount of Mortgage</label>
                        <small>
                            <p class="mb-1"> {{ formatDecimal(quote.amountofMortgage) }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Interest Rate</label>
                        <small>
                            <p class="mb-1"> {{ formatDecimal(quote.interestRate) }}%</p>
                        </small>
                    </div>
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Interest Commences</label>
                        <small>
                            <p class="mb-1"> {{ formatDate(quote.interestCommences) }}</p>
                        </small>
                        </div>
                </div>
                <div class="d-flex">
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Mortgage Interest Commencement</label>
                        <small>
                            <p class="mb-1"> {{ formatDate(quote.interestCommences) }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">First Payment Due</label>
                        <small>
                            <p class="mb-1"> {{ formatDate(quote.firstPaymentDue) }}</p>
                        </small>
                        </div>
                        <div class="form-group px-1 col-2">
                        <label class="fw-bold">Monthly Payments</label>
                        <small>
                            <p class="mb-1"> {{ formatDecimal(quote.monthlyPayments) }}</p>
                        </small>
                        </div>
                </div>
                <div class="d-flex align-items-end flex-wrap">
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Amortization</label>
                        <small>
                            <p class="mb-1"> {{ quote.amortization }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">Term</label>
                        <small>
                            <p class="mb-1"> {{ quote.term }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1 col-2">
                        <label class="fw-bold">MIP</label>
                        <small>
                            <p class="mb-1"> {{ quote.mip }}</p>
                        </small>
                    </div>
                </div>

                <hr>

                <div class="d-flex align-items-end flex-wrap">
                    <div class="form-group px-1">
                        <label class="fw-bold">Assignment of Rent</label>
                        <small>
                            <p class="mb-1"> {{ quote.assignmentofRent }}</p>
                        </small>
                    </div>
                    <div class="form-group px-1">
                        <label class="fw-bold">	Hold Back Required</label>
                        <small>
                            <p class="mb-1"> {{ quote.holdBackRequired }}</p>
                        </small>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold">Mortgage Proceed Disbursements</h6>

                <div class="row">
                    <div class="col-2"><label>Legal Fees and Disbursements (Estimated)</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.legalFeesDisbursements) }}</small></div>
                </div>
                <div class="row">
                    <div class="col-2"><label>Application Fee</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.applicationFee) }}</small></div>
                </div>
                <div class="row">
                    <div class="col-2"><label>Brokerage Fee</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.brokerageFee) }}</small></div>
                </div>
                <div class="row">
                    <div class="col-2"><label>Lender Fee</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.lenderFee) }}</small></div>
                </div>
                <div class="row">
                    <div class="col-2"><label>Appraisal Fee</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.appraisalFee) }}</small></div>
                </div>
                <div class="row">
                    <div class="col-2"><label>Approximate Net Proceeds Available</label></div>
                    <div class="col-1 text-end"><small>{{ formatDecimal(quote.approximateNetProceedsAvailable) }}</small></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-2 fw-bold">
                    <div class="col-4"><label>Approximate Total Net Proceeds Available</label></div>
                    <div class="col-1 text-end"><small> {{ formatDecimal(this.totalNetProceedsAvailable) }}</small></div>
                </div>

                <hr>

                <div v-for="(row, w) in propertyArreas" :key="w">
                    <div v-if="row.propertyArreas != 'UTD'" class="row">
                        <div class="col-4"><label>Property Taxes - {{ row.address }} (Estimated)</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.propertyArreas) }} </small></div>
                    </div>
                    <div v-if="row.mortgageArreas != 'UTD'" class="row">
                        <div class="col-4"><label>Mortgage Arrears - {{ row.address }} (Estimated)</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.mortgageArreas) }} </small></div>
                    </div>
                    <div v-if="row.insuranceArreas != 'UTD'" class="row">
                        <div class="col-4"><label>House Insurance Payable - {{ row.address }} (Estimated)</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.insuranceArreas) }}</small></div>
                    </div>
                    <div v-if="row.strataArreas != 'UTD'" class="row">
                        <div class="col-4"><label>Strata Arrears - {{ row.address }} (Estimated)</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.strataArreas) }}</small></div>
                    </div>
                </div>

                <div v-if="holdBackAmount > 0" class="row">
                    <div class="col-4"><label>Hold Back (Estimated)</label></div>
                    <div class="col-1 text-end"><small> {{ formatDecimal(holdBackAmount) }} </small></div>
                </div>

                <div v-for="(row, w) in payouts" :key="w">
                    <div class="row">
                        <div class="col-4"><label>Approx Payout Existing ( {{ row.position }} ) Mortgage</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.currentBalance) }} </small></div>
                    </div>
                </div>

                <div v-for="(row, w) in propertyMortgages" :key="w">
                    <div class="row">
                        <div class="col-4"><label>{{ row.lenderName }}</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.balance) }} </small></div>
                    </div>
                </div>

                <div v-for="(row, w) in liabilities" :key="w">
                    <div class="row">
                        <div class="col-4"><label>Borrower & {{ row.lenderName }} (Estimated)</label></div>
                        <div class="col-1 text-end"><small> {{ formatDecimal(row.balance) }} </small></div>
                    </div>
                </div>


                <hr>

                <div class="row mb-2 fw-bold">
                    <div class="col-4"><label>Approximate Net Proceeds to Borrower</label></div>
                    <div class="col-1 text-end"><small> {{ formatDecimal(this.aproximateNetProceedstoBorrower) }}</small></div>
                </div>

                <hr>

                <!-- <div class="mb-2">
                    I/We hereby irrevocably authorize and direct you to pay from the net proceeds of my/our mortgage on the above property the sum or sums listed above and for doing so this will be your good and sufficient authority. I/We authorize and request you to prepare the necessary documents and to perform all necessary services as may be required to complete the registration of the above mentioned mortgage. I/We hereby authorize you to deduct from the aforesaid net mortgage proceeds sufficient funds to obtain fire insurance coverage, showing loss payable firstly to Alpine Mortgage Corp. and any arrears of prior mortgages and other charges, including property taxes. I/We also authorize Alpine Mortgage Corp. or its potential assignee to obtain a credit bureau report.
                </div>
                <div class="mb-2">
                    In the event Alpine Mortgage Corp. approves my/our application and any of the information contained in the application proves to be either inaccurate or incomplete, Alpine Mortgage Corp. has the right to cancel its approval and I/we agree to immediately pay to Alpine Mortgage Corp. all costs and losses suffered by Alpine Mortgage Corp. in the handling or processing of my/our application.The undersigned borrower(s) specifically charge the property described in this Agreement in favor of the Credit grantor in its own behalf and on behalf of its agents and lawyer, for all fees and charges in this agreement, including all enforcement expenses on a solicitor and his own client(indemnity) basis and authorize the Credit grantor and its agents and lawyer to file a Caveat to protect the same. All fees and charges herein contained are deemed to be earned as at the date of execution of this agreement by the undersigned borrower(s).
                </div>
                <div class="mb-2">
                    I/We acknowledge Alpine Mortgage Corp. has completed this form relying on details provided by me/us concerning my/our debts and charges registered against title. In the event any of the details provided above do not agree with the amount claimed by the creditor or with a search of the title, I/we authorize Alpine Mortgage Corp., its solicitors or agents, to pay the full amount being claimed by the creditor or chargeholder to discharge the debt or registered charge.
                </div>
                <div class="mb-2">
                    AGREEMENT & CONSENT to USE of PERSONAL INFORMATION
                </div>
                <div class="mb-2">
                    I/WE consent to and accept this as written notice of Alpine Mortgage Corp., its affiliates, service providers, agents, professional advisors and successors and / or assignees (combined 'AMC') receiving, disclosing, exchanging and using any personal information contained in this application and obtained as a result of this application.
                </div>
                <div class="mb-2">
                    I/We consent that my personal information in the application will be kept on file and used by AMC in order to evaluate my credit application, monitor my ongoing credit status and collect any monies I/We may owe AMC. Furthermore, I/We consent that AMC may use my personal information to (1) determine my eligibility for other products and/or services offered by AMC and (2) promote and market products and/or services offered by AMC and selected service providers (including by means of direct marketing)
                </div>
                <div class="mb-3">
                    I/We also consent to the retention of personal information about me for as long as is needed for the purposes described above, even after I/WE cease to be a customer. By writing to Alpine Mortgage Corp, 1 - 10104 111th Avenue Edmonton, AB T5G 0B3, I/WE can tell AMC to stop using and / or exchanging information about me in order to market their products and services. However I/WE agree that during the term of any loan or credit facility, I/WE may not withdraw my/our consent to the ongoing collection, use or disclosure of my/our personal information in connection with the loan or credit facility.
                </div> -->
                <!-- <div class="mb-3 text-end">
                    Acknowledged and Accepted this___day of________, 20___
                </div> -->

                <!-- <div v-for="(wit, w) in witnesses" :key="w">
                    <div class="row text-center">
                        <div class="col-6"><hr></div>
                        <div class="col-6"><hr></div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">Witness</div>
                        <div class="col-6"> {{ wit }} </div>
                    </div>
                </div> -->
            </div>
        </div>
    </template>
</template>

<script>
import { util } from '../mixins/util';
import { quote } from '../mixins/quote';

export default {
    mixins: [util, quote],
    components: {},
    props: ['application', 'selectedTab'],
    watch: {
        application: function(newValue, oldValue) {
            this.getData()
        }
    },
    data() {
        return {
            savedQuotes: [],
            witnesses: [],
            propertyArreas: [],
            payouts: [],
            liabilities: [],
            propertyMortgages: [],            
            totalNetProceedsAvailable: 0,
            aproximateNetProceedstoBorrower: 0,
            guarantors: '',
            holdBackAmount: 0
        }
    },
    methods: {        
        getData: function() {
            if(this.application.id === undefined) {
                return
            }            
            this.axios({
                method: 'get',
                url: '/web/contact-center/disbursement/' + this.application.id
            })
            .then(response => {
                this.savedQuotes = response.data.data.savedQuotes
                this.guarantors = response.data.data.guarantors
                this.witnesses = response.data.data.witnesses
                this.propertyArreas = response.data.data.propertyArreas
                this.totalNetProceedsAvailable = response.data.data.totalNetProceedsAvailable
                this.aproximateNetProceedstoBorrower = response.data.data.aproximateNetProceedstoBorrower
                this.payouts = response.data.data.payouts
                this.liabilities = response.data.data.liabilities
                this.propertyMortgages = response.data.data.propertyMortgages
                this.holdBackAmount = response.data.data.holdBackAmount
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        } 
    }
}
</script>

<style scoped>
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
}
.table-header {
    padding-right: 1rem; 
    padding-bottom: 0;
    border: none; 
    font-size: 0.875em;
    font-weight: bold;
    
}
.table-body {
    padding-right: 1rem;
    padding-top: 0;
    border: none;
    font-size: 0.875em;
    font-weight: normal;
}
</style>