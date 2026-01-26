<template>
    <div class="mb-4" style="overflow-x: hidden;">

        <div v-if="showInitialize" >

            <div class="text-center text-danger">
                <p>Double-check quote information. This will now become the mortgage contract.</p>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">
                    Mortgage Parameters
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">
                            <div class="label">Net Amount</div>
                            <div class="value">{{ formatDecimal(this.quoteData.net) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Loan Term (months)</div>
                            <div class="value">{{ this.quoteData.loan }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Client's Auth. Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.clientAuthDate) }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="label">Legal (Incl Title Ins)</div>
                            <div class="value">{{ formatDecimal(this.quoteData.legal) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Amortization (years)</div>
                            <div class="value">{{ formatDecimal(this.quoteData.amort) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Interest Comm. Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.intCommDate) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Mortgage Interest Comm. Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.intCommDate) }}</div>
                        </div>        
                    </div>


                    <div class="row">
                        <div class="col-3">
                            <div class="label">Application Fee</div>
                            <div class="value">{{ formatDecimal(this.quoteData.appr) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Interest Rate</div>
                            <div class="value">{{ this.quoteData.interestRate }}%</div>
                        </div>

                        <div class="col-3">
                            <div class="label">First Payment Due Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.firstPmtDate) }}</div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="label">Brokerage Fee</div>
                            <div class="value">{{ formatDecimal(this.quoteData.broker) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">2nd Year Prime + Rate</div>
                                <div class="value">{{ formatDecimal(this.quoteData.secondPrime) }}%
                                <span> + </span>
                                <span class="value">{{ formatDecimal(this.quoteData.secondYear) }}%</span>
                                <span> = </span>
                                <span class="value">{{ formatDecimal(this.quoteData.primePlus) }}%</span>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="label">MIP (months)</div>
                            <div class="value">{{ this.quoteData.mip }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Variable Mortgage</div>
                            <div class="value">{{ this.quoteData.variableMtg }}</div>
                        </div>        
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="label">Brokerage / Discount</div>
                            <div class="value">{{ formatDecimal(this.quoteData.discount) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Compounded</div>
                            <div class="value">{{ this.quoteData.comp }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Documentation</div>
                            <div class="value">{{ this.quoteData.documentation }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Variable Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.variableDate) }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="label">Appraisal</div>
                            <div class="value">{{ formatDecimal(this.quoteData.misc) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Monthly Payment</div>
                            <div class="value">{{ formatDecimal(this.quoteData.month) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Assignment of Rent?</div>
                            <div class="value">{{ this.quoteData.assnRent }}</div>
                        </div>      
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="label">Gross Amount</div>
                            <div class="value">{{ formatDecimal(this.quoteData.gross) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label text-danger">Term Due Date</div>
                            <div class="value">{{ formatPhpDate(this.quoteData.termDueDate) }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Hold Back Required?</div>
                            <div class="value">{{ this.quoteData.holdBack }}</div>
                        </div>

                        <div class="col-3">
                            <div class="label">Special Pricing</div>
                            <div class="value">{{ this.quoteData.specialPricing }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    Over Properties
                </div>
                
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title Holders</th>
                                <th>Address</th>
                                <th>Position</th>
                                <th>Interest</th>
                                <th>PID</th>
                                <th>Legal Description</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(property, key) in properties" :key="key">
                                <td>{{ property.titleHolders }}</td>
                                <td>
                                    <a :href="property.link" target="_blank">{{ property.address }}</a>
                                </td>
                                <td>{{ property.position }}</td>
                                <td>{{ property.alpineInterest }}</td>
                                <td>{{ property.propertyPid }}</td>
                                <td>{{ property.legal }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Agents Involved -->
            <div class="card mb-3">
                <div class="card-header">
                    Agents Involved
                </div>
                
                <div class="card-body">
                    <div class="row">

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Agent</div>
                                <select v-model="applicationData.agent" disabled class="form-select">
                                    <option v-for="(agent, key) in agents" :key="key" :value="agent.userId">{{ agent.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Signing Agent</div>
                                <select v-model="applicationData.signingAgent" disabled class="form-select">
                                    <option v-for="(agent, key) in agents" :key="key" :value="agent.userId">{{ agent.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Broker Group</div>
                                <select v-model="groupSelected" class="form-select">
                                    <option value="Gp1" v-if="applicationData.company !== 701">Gp1</option>
                                    <option value="Gp2">Gp2</option>
                                    <option value="Gp3" v-if="applicationData.company !== 701">Gp3</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3" v-if="applicationData.company === 701 || applicationData.company === 401">
                            <div>
                                <div class="label">CPS Instruct</div>
                                <select v-model="cpsInstruct" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <!-- Post dated cheques -->
            <div class="card mb-3">
                <div class="card-header">
                    Post Dated Cheques
                </div>
                
                <div class="card-body">
                    <div class="row mt-1">
                        <div class="col">
                            <div>
                                <div class="label">Start Date</div>
                                <div> 
                                    <DatePicker v-model="quoteData.firstChequeDate" :model-config="modelConfig" :timezone="timezone">
                                        <template v-slot="{ inputValue, inputEvents }">
                                            <input
                                                class="form-control"
                                                :value="inputValue"
                                                v-on="inputEvents"
                                            />
                                        </template>
                                    </DatePicker>
                                </div> 
                            </div>
                        </div>

                        <div class="col">
                            <div>
                                <div class="label">1st Payment</div>
                                <div>
                                    <input 
                                        class="form-control"
                                        type="text"
                                        v-model="this.quoteData.firstChequeAmt"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div>
                                <div class="label">End Date</div>
                                <div>
                                    <DatePicker v-model="quoteData.lastChequeDate" :model-config="modelConfig" :timezone="timezone">
                                        <template v-slot="{ inputValue, inputEvents }">
                                            <input
                                                class="form-control"
                                                :value="inputValue"
                                                v-on="inputEvents"
                                            />
                                        </template>
                                    </DatePicker>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div>
                                <div class="label">Reg Payment</div>
                                <div>
                                    <input
                                        class="form-control"
                                        type="text"
                                        v-model="this.quoteData.regularChequeAmt"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div>
                                <div class="label">Location</div>
                                <select v-model="quoteData.chequeLocation" class="form-select">
                                    <option v-for="(location, key) in locations" :key="key" :value="location.id">{{ location.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col">
                            <div>
                                <div class="label">Erate</div>
                                <div>{{ applicationData.ey }}</div>
                            </div>
                        </div>            
                    </div>
                </div>
            </div>
            
            <!-- PAP BANK Info -->
            <div class="card mb-3" v-if="quoteData.isMR === 'Yes'">
                <div class="card-header">
                    PAP Bank Info
                </div>

                <div class="card-body">
                    <div class="row mt-1">
                        <div class="col-12 col-md mb-3">
                            <div class="label">Transit</div>
                            <div>
                                <input
                                    class="form-control"
                                    type="text"
                                    id="transit"
                                    name="transit"
                                    v-model="transitValue"
                                    required
                                    @blur="transitValidate(transitValue)"
                                >
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Institution</div>
                                <div>
                                    <input
                                        class="form-control"
                                        type="text"
                                        id="institution"
                                        name="institution"
                                        v-model="institutionCod"
                                        required
                                        @blur="getInstitutionName(institutionCod)"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div class="label">Account</div>
                            <div>
                                <input
                                    class="form-control"
                                    type="text"
                                    id="account"
                                    name="account"
                                    v-model="accountValue"
                                    required
                                    @blur="accountValidate(accountValue)"
                                >
                            </div>
                        </div>
                        
                        <div class="col-12 col-md mb-3">
                            <div class="label">Payee Name</div>
                            <div>
                                <input
                                    class="form-control"
                                    type="text"
                                    id="payeeName"
                                    name="payeeName"
                                    v-model="payeeNameValue"
                                    required
                                >
                            </div>
                        </div>
                        
                    </div>

                    <div class="row">
                        <span>{{ this.institutionName }}</span>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row mt-3">

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Expected Funding Date</div>
                                <div>{{ formatPhpDate(this.applicationData.fundingDate) }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Company</div>
                                <select v-model="applicationData.company" class="form-select">
                                    <option v-for="(company, key) in companies" :key="key" :value="company.id">{{ company.abbreviation }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">NSF Fee</div>
                                <div>
                                    <input
                                        class="form-control"
                                        type="text"
                                        v-model="this.applicationData.nsfFee"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Solicit at Term</div>
                                <select v-model="this.applicationData.solicit" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md mb-3">
                            <div>
                                <div class="label">Er</div>
                                <div></div>
                                <span>{{ this.applicationData.er }}%</span>
                            </div>
                        </div>
                            
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <button 
                    class="btn btn-primary" 
                    @click="initialize()">
                    <i class="bi bi-save"></i>
                    Initialize
                </button>
            </div>

        </div>

        <div v-if="showQuote" class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="card">
                <div class="card-header">
                    Select the quote to be initialized
                </div>
                <div class="card-body">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="col-md-6" style="min-width: 150px;">Gross Amount</th>
                                <th class="col-md-6"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(quote, key) in quotes" :key="key">
                                <td class="col-md-6">{{ formatDecimal(quote.gross) }}</td>
                                <td class="col-md-6">
                                    <button 
                                        class="btn btn-primary" 
                                        @click="quoteSelected(quote)">
                                        <i class="bi bi-save"></i>
                                        Initialize
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showPending" class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="card">
                <div class="card-header">
                    Check the information below before proceeding
                </div>
                <div class="card-body">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <tr v-for="(pendency, key) in pendencies" :key="key">
                                <td class="col-md-6">
                                    <i class="bi bi-exclamation-triangle me-1 text-warning"></i>{{ pendency.name }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showMtgMsg" class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="card">
                <div class="card-body">
                    <i class="bi bi-check-circle-fill me-1 text-success"></i> Mortgage created successfully! {{ this.mortgageCode }}
                </div>
            </div>
        </div>
    </div>

</template>

<script>

import { util } from "../mixins/util";
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ["events"],
    components: { DatePicker },
    data() {
        return {
            transitValue: null,
            institutionCod: null,
            institutionName: null,
            accountValue: null,
            payeeNameValue: '',
            showQuote: false,
            showPending: false,
            showInitialize: false,
            showMtgMsg: false,
            quotes: [],
            pendencies: [],
            quoteData:[],
            properties:[],
            applicationData:[],
            agents:[],
            companies:[],
            validations:[],
            locations:[],
            positions:[],
            groupSelected: '',
            cpsInstruct: null,
            mortgageCode: null,
        };
    },
    mounted() {
        this.getData();
    },
    methods: {
        getData: function () {
            this.showPreLoader();

            let data = {
                opportunityId: this.$route.params.opportunityId
            }

            this.axios.post(
                "/web/initialization/check-active-quotes", 
                data
            )
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    if (response.data.data.type == 'quoteData') {

                        this.showInitialize = true;
                        this.showQuote      = false;
                        this.showPending    = false;
                        this.showMtgMsg     = false;

                        this.quoteData       = response.data.data.quoteData;
                        this.properties      = response.data.data.propertyData;
                        this.applicationData = response.data.data.applicationData;
                        this.agents          = response.data.data.agents;
                        this.companies       = response.data.data.companies;
                        this.locations       = response.data.data.locations
                        this.positions       = response.data.data.positions
                    };

                    if (response.data.data.type == 'activeQuotes') {

                        this.showInitialize = false;
                        this.showQuote      = true;
                        this.showPending    = false;
                        this.showMtgMsg     = false;

                        this.quotes = response.data.data.activeQuotes;
                    };

                    if (response.data.data.type == 'pendingIssues') {

                        this.showInitialize = false;
                        this.showQuote      = false;
                        this.showPending    = true;
                        this.showMtgMsg     = false;

                        this.pendencies = response.data.data.pendingIssues;
                    }

                } else {

                    this.showInitialize = false;
                    this.showQuote      = false;
                    this.showPending    = false;
                    this.showMtgMsg     = false;

                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            })
        },

        quoteSelected: function (quote) {

            this.showPreLoader();

            let data = {
                savedQuoteId: quote.savedQuoteId,
                applicationId: quote.applicationId,
            };

            this.axios.post(
                "/web/initialization/quote-selected",
                data
            )
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    if (response.data.data.type == 'quoteData') {

                        this.showInitialize = true;
                        this.showQuote      = false;
                        this.showPending    = false;
                        this.showMtgMsg     = false;

                        this.quoteData       = response.data.data.quoteData;
                        this.properties      = response.data.data.propertyData;
                        this.applicationData = response.data.data.applicationData;
                        this.agents          = response.data.data.agents;
                        this.companies       = response.data.data.companies;
                        this.locations       = response.data.data.locations
                        this.positions       = response.data.data.positions

                    };
                    if (response.data.data.type == 'pendingIssues') {

                        this.showInitialize = false;
                        this.showQuote      = false;
                        this.showPending    = true;
                        this.showMtgMsg     = false;

                        this.pendencies = response.data.data.pendingIssues;
                    }

                } else {

                    this.showInitialize = false;
                    this.showQuote      = false;
                    this.showPending    = false;
                    this.showMtgMsg     = false;

                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            })
        },

        getInstitutionName: function (institutionCod) {

            this.validations = [];

            if (isNaN(institutionCod)) {
                this.validations.push('Please enter numeric characters only for institution! (Allowed input: 0-9)');
            }

            if (institutionCod.length !== 3) {
                this.validations.push('Institution length should be 3 digits');
            }

            if (this.validations.length > 0) {
                this.institutionName = null;
                this.alertMessage = this.validations.join('<br>');
                this.showAlert('error');
                return;
            }

            this.showPreLoader();

            let data = {
                institutionCod: institutionCod,
            };

            this.axios.post(
                "/web/pap/bank-info/institution-name",
                data
            )
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    this.institutionName = response.data.data.name

                } else {
                    this.institutionName = '';
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            })
        },

        transitValidate: function (transitValue) {

            this.validations = [];

            if (isNaN(transitValue)) {
                this.validations.push('Please enter numeric characters only for Transit! (Allowed input: 0-9)');
            }

            if (transitValue.length !== 5) {
                this.validations.push('Transit length should be 5 digits long');
            }

            if (this.validations.length > 0) {
                this.institutionName = null;
                this.alertMessage = this.validations.join('<br>');
                this.showAlert('error');
                return;
            }
        },

        accountValidate: function (accountValue) {

            this.validations = [];

            if (accountValue.length < 6) {
                this.validations.push('Account length should be bigger than 6 digits');
            }

            if (this.validations.length > 0) {
                this.institutionName = null;
                this.alertMessage = this.validations.join('<br>');
                this.showAlert('error');
                return;
            }
        },

        initialize: function () {
            
            if(this.quoteData.isMR === 'Yes') {

                this.validations = [];

                if(this.institutionCod === null || this.institutionCod === '') {
                    this.validations.push('Institution must be informed');
                }

                if(this.transitValue === null || this.transitValue === '') {
                    this.validations.push('Transit must be informed');
                }

                if(this.accountValue === null || this.accountValue === '') {
                    this.validations.push('Account must be informed');
                }

                if(this.validations.length > 0) {
                    this.institutionName = null;
                    this.alertMessage = this.validations.join('<br>');
                    this.showAlert('error');
                    return;
                }

                if(isNaN(this.institutionCod)) {
                    this.validations.push('Please enter numeric characters only for institution! (Allowed input: 0-9)');
                }

                if(this.institutionCod.length !== 3) {
                    this.validations.push('Institution length should be 3 digits long');
                }           

                if(isNaN(this.transitValue)) {
                    this.validations.push('Please enter numeric characters only for institution! (Allowed input: 0-9)');
                }

                if(this.transitValue.length !== 5) {
                    this.validations.push('Institution length should be 5 digits long');
                }

                if(this.accountValue.length >= 15 || this.accountValue.length <= 5) {
                    this.validations.push('Account length is between 5 to 15 digits');
                }

                if(!this.payeeNameValue || this.payeeNameValue.trim() === '') {
                    this.validations.push('Payee Name must be informed');
                }

                if(this.groupSelected === '' || this.groupSelected == null) {
                    this.validations.push('Broker Group must be selected');
                }

                if (this.validations.length > 0) {
                    this.institutionName = null;
                    this.alertMessage = this.validations.join('<br>');
                    this.showAlert('error');
                    return;
                }
            }

            if(this.groupSelected === '' || this.groupSelected == null) {
                this.alertMessage = 'Broker Group must be selected';
                this.showAlert('error');
                return;
            }

            if (this.applicationData.company === 701 || this.applicationData.company === 401) {
                if (this.cpsInstruct == null || this.cpsInstruct == '') {
                    this.alertMessage = 'Select an item in CPS Instruct';
                    this.showAlert('error');
                    return;
                }
            }

            this.showPreLoader();
            
            let data = {
                savedQuoteId: this.quoteData.savedQuoteId,
                applicationId: this.quoteData.applicationId,
                agent: this.applicationData.agent,
                signingAgent: this.applicationData.signingAgent,
                brokerGroup: this.groupSelected,
                institution: this.institutionCod,
                transit: this.transitValue,
                account: this.accountValue,
                payeeName: this.payeeNameValue, 
                firstChequeDate: this.quoteData.firstChequeDate,
                firstChequeAmt: this.quoteData.firstChequeAmt,
                lastChequeDate: this.quoteData.lastChequeDate,
                regularChequeAmt: this.quoteData.regularChequeAmt,
                chequeLocation: this.quoteData.chequeLocation,
                company: this.applicationData.company,
                nsfFee: this.applicationData.nsfFee,
                solicitTerm: this.applicationData.solicit,
                cpsInstruct: this.cpsInstruct,
                userId: this.$route.params.userId,
            };

            this.axios.post(
                "/web/initialization/initialize",
                data
            )
            .then((response) => {
                if (this.checkApiResponse(response)) {

                    this.mortgageCode = response.data.data.mortgageCode
                    this.showInitialize = false;
                    this.showQuote      = false;
                    this.showPending    = false;
                    this.showMtgMsg     = true;

                } else {

                    if (response.data.message != 'PAP Bank Institution code is incorrect') {
                        this.showInitialize = false;
                        this.showQuote      = false;
                        this.showPending    = false;
                        this.showMtgMsg     = false;
                    }

                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            })
        }
    }
};
</script>

<style>
.label {
    font-weight: bold;
    margin-right: 5px;
}

.value {
    text-align: left;
}
</style>