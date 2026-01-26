<template>
    <template v-if="selectedTab === 'quote'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="table-header">Type</label>
                                    <select v-model="type" class="form-select" @change="typeOnChange">
                                        <option v-for="(option, key) in types" :key="key" :value="option.value">{{ option.text }}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="table-header">Net Amount</label>
                                    <CurrencyInput v-model="netAmount" @change="netAmountOnChange" />
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header">Legal Fee</label>
                                        <CurrencyInput v-model="legalFee" @change="legalFeeOnChange" />
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header">Application Fee</label>
                                        <CurrencyInput v-model="applicationFee" @change="applicationFeeOnChange" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header">Appraisal Fee</label>
                                        <CurrencyInput v-model="appraisalFee" @change="appraisalFeeOnChange" />
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header">Other Fee</label>
                                        <CurrencyInput v-model="otherFee" @change="otherFeeOnChange" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-8">
                                        <label class="table-header">{{ brokerageFeeLabel }}</label>
                                        <CurrencyInput v-model="brokerageFee" @change="brokerageFeeOnChange" :disabled="specialPricing === 'NB - 55% - Urban'"/>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">%</label>
                                        <CurrencyInput v-model="brokerageFeePerc" :decimals="3" @change="brokerageFeePercOnChange" :disabled="specialPricing === 'NB - 55% - Urban'"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-8">
                                        <label class="table-header">{{ brokerageDiscountLabel }}</label>
                                        <CurrencyInput v-model="discountFee" @change="discountFeeOnChange" :disabled="specialPricing === 'NB - 55% - Urban'"/>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">%</label>
                                        <CurrencyInput v-model="discountFeePerc" :decimals="3" @change="discountFeePercOnChange" :disabled="specialPricing === 'NB - 55% - Urban'"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="table-header">Gross Amount</label>
                                    <CurrencyInput v-model="grossAmount" @change="grossAmountOnChange" />
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header"><span class="text-danger">*</span>Loan Term</label>
                                        <select v-model="loanTerm" class="form-select" @change="loanTermOnChange">
                                            <option v-for="n in 5" :key="n" :value="n * 12">
                                                <template v-if="n == 1">{{ n }} year</template>
                                                <template v-else>{{ n }} years</template>
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header"><span class="text-danger">*</span>Amortization</label>
                                        <input class="form-control" v-model="amortization" @change="amortizationOnChange" :disabled="isAmortizationDisabled" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header"><span class="text-danger">*</span>Interest Rate</label>
                                        <CurrencyInput v-model="interestRate" @change="interestRateOnChange" :isPercentage="true" />
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header"><span class="text-danger">*</span>Compounded</label>
                                        <select v-model="compounded" class="form-select" @change="compoundedOnChange" >
                                            <option v-for="(option, key) in compoundeds" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="table-header">Monthly Payment</label>
                                    <CurrencyInput v-model="monthlyPayment" @change="monthlyPaymentOnChange" :disabled="isMonthlyPaymentDisabled" />
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="row">
                                    <div class="form-group col-4">
                                        <label class="table-header">Mortgage Type</label>
                                        <select v-model="mortgageType" class="form-select" @change="mortgageTypeOnChange">
                                            <option v-for="(option, key) in mortgageTypes" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Loan Category</label>
                                        <select v-model="loanCategory" class="form-select">
                                            <option v-for="(option, key) in loanCategories" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Interest Rate Type</label>
                                        <select v-model="interestRateType" class="form-select">
                                            <option v-for="(option, key) in interestRateTypes" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-4">
                                        <label class="table-header">Prime</label>
                                        <CurrencyInput v-model="primeRate" :readOnly="true" :isPercentage="true" />
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">2nd Year</label>
                                        <CurrencyInput v-if="interestRateType == 'V'" v-model="primeRate2ndYear" @change="primeRateOnChange('2ndYear')" />
                                        <CurrencyInput v-else v-model="primeRate2ndYear" :readOnly="true" :isPercentage="true" />
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Prime + 2nd Year</label>
                                        <CurrencyInput v-if="!(interestRateType == 'F' && loanTerm <= 12)" v-model="primeRateTotal" @change="primeRateOnChange('total')" />
                                        <CurrencyInput v-else v-model="primeRateTotal" :readOnly="true" :isPercentage="true" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header">Retainer</label>
                                        <CurrencyInput v-model="retainer" />
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header">Special Pricing</label>
                                        <select v-model="specialPricing" class="form-select" @change="typeOnChange">
                                            <option v-for="(option, key) in specialPrices" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="row">
                                    <div class="form-group col-4">
                                        <label class="table-header">Client's Auth. Date</label>
                                        <input v-model="clientAuthDate" type="date" class="form-control">
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Interest Comm. Date</label>
                                        <input v-model="interestCommDate" type="date" class="form-control" @change="validateDate(interestCommDate,'I')">
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">First Payment Due Date</label>
                                        <input v-model="firstPaymentDueDate" type="date" class="form-control" @change="validateDate(firstPaymentDueDate,'F')">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-3">
                                        <label class="table-header">MIP</label>
                                        <select v-model="mip" class="form-select">
                                            <option key="0" value="0">0</option>
                                            <option key="1" value="1">1</option>
                                            <option key="2" value="2">2</option>
                                            <option key="3" value="3">3</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-9">
                                        <label class="table-header">ILA</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ ilaName }}</span>
                                            <input 
                                                v-model="ila" 
                                                type="number" 
                                                class="form-control"
                                                @change="onSelectIla(ila)">
                                            <button 
                                                class="btn btn-secondary"
                                                type="button"
                                                @click="selectIla(key, i)">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="table-header">Type of Loan</label>
                                        <select v-model="typeLoan" class="form-select">
                                            <option v-for="(option, key) in typeLoans" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-6">
                                        <label class="table-header">Documentation</label>
                                        <select v-model="documentation" class="form-select">
                                            <option v-for="(option, key) in documentations" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-4">
                                        <label class="table-header">Assignment of Rent?</label>
                                        <select v-model="assignmentRent" class="form-select">
                                            <option v-for="(option, key) in assignmentRents" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Hold Back Required?</label>
                                        <select v-model="holdBackRequired" class="form-select">
                                            <option v-for="(option, key) in holdBacksRequired" :key="key" :value="option.value">{{ option.text }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-4">
                                        <label class="table-header">Hold Back Amount</label>
                                        <CurrencyInput v-model="holdBack" />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="table-header">Quote Comments</label>
                                    <textarea v-model="quoteComments" type="text" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-3"></div>

                                    <div class="col-6">
                                        <div class="card text-bg-secondary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-center">
                                                    <strong>Suggested Interest Rate</strong>
                                                </div>

                                                <div class="d-flex justify-content-center">
                                                    <span v-if="pricebook == null">---</span>
                                                    <span v-else>
                                                    <strong>{{ pricebook.interestRate }}<span v-if="pricebook.interestRate">%</span></strong>
                                                    </span>
                                                    <i class="bi bi-info-circle mx-1" v-tooltip="{ content: generateTooltipLtv(quote), html: true }"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <button :disabled="!isSalesJourney && application.companyId != 701" class="btn btn-success" @click="store()">
                                <i class="bi bi-save me-1"></i>Save Quote
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <span>Quotes</span>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <table class="table table-hover mb-0 table-fix-bottom-rounded">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th><small>Date</small></th>
                                            <th><small>Gross Amount</small></th>
                                            <th><small>Net Amount</small></th>
                                            <th><small>Term</small></th>
                                            <th><small>Amort</small></th>
                                            <th><small>Interest</small></th>
                                            <th><small>Monthly</small></th>
                                            <th><small>LTV</small></th>
                                            <th><small>Enable Quote</small></th>
                                            <th><small>Ready to Buy</small></th>
                                            <template v-if="quotes.length > 0">
                                                <th v-for="(position, key) in quotes[0].positions" :key="key">
                                                    <small>#{{ key + 1 }} Pos</small>
                                                </th>
                                            </template>
                                            <th><small>Lender</small></th>
                                            <th><small>FM</small></th>
                                            <th><small>Mortgage</small></th>
                                            <th></th>
                                        </tr>
                                    </thead>

                                    <tbody v-if="quotes.length == 0">
                                        <tr>
                                            <td class="text-center" colspan="21">No quotes</td>
                                        </tr>
                                    </tbody>

                                    <tbody v-else>
                                        <tr v-for="(quote, k) in quotes" :key="k">
                                            <td class="nowrap">
                                                <i class="bi bi-info-circle mx-1" v-tooltip="{ content: generateTooltipContent(quote), html: true }"></i>
                                                <a @click="copyQuote(quote, this.application.companyId)" href="#" v-tooltip="{ content: 'Copy Quote', html: false }">
                                                    <i class="bi bi-copy ms-1"></i>
                                                </a>
                                            </td>
                                            <td><small>{{ formatPhpDate(quote.date) }}</small></td>
                                            <td><small>{{ formatDecimal(quote.grossAmount) }}</small></td>
                                            <td><small>{{ formatDecimal(quote.netAmount) }}</small></td>
                                            <td><small>{{ quote.loanTerm }}</small></td>
                                            <td><small>{{ quote.amortization }}</small></td>
                                            <td><small>{{ quote.interestRate }}%</small></td>
                                            <td><small>{{ formatDecimal(quote.monthlyPayment) }}</small></td>
                                            <td><small>{{ formatDecimal(quote.ltv) }}%</small></td>
                                            <td>
                                                <select v-model="quote.enableQuote" class="form-select" @change="storeQuoteChanges(quote)">
                                                    <option value="No">No</option>
                                                    <option value="Yes">Yes</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select v-model="quote.readyToBuy" class="form-select" :disabled="quote.disabled || quote.mortgageId > 0" @change="readyToBuyOnChange(quote)">
                                                    <option value="No">No</option>
                                                    <option value="Yes">Yes</option>
                                                </select>
                                            </td>
                                            <template v-if="quotes.length > 0">
                                                <td v-for="(position, key) in quote.positions" :key="key">
                                                    <select v-model="position.position" class="form-select" @change="storeQuoteChanges(quote)" :disabled="quote.mortgageId > 0">
                                                        <option value="1st">1st</option>
                                                        <option value="2nd">2nd</option>
                                                        <option value="3rd">3rd</option>
                                                        <option value="4th">4th</option>
                                                        <option value="N/A">N/A</option>
                                                    </select>
                                                </td>
                                            </template>
                                            <td><small>{{ quote.lender }}</small></td>
                                            <td><small>{{ quote.fm }}</small></td>
                                            <td><small>{{ quote.mortgageCode }}</small></td>
                                            <td class="nowrap text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary me-1"
                                                    @click="use(quote)"
                                                    v-tooltip="{ content: 'Use quote content', html: false }">
                                                    <i class="bi bi-box-arrow-up"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger"
                                                    @click="destroy(quote)"
                                                    :disabled="quote.mortgageId > 0">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card text-bg-secondary mb-2">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3"></div>

                            <div class="col-6 d-flex justify-content-center align-items-center">
                                <strong>LTV {{ formatDecimal(ltv) }}%</strong>
                            </div>

                            <div class="col-3 d-flex justify-content-end">
                                <input v-model="targetLtv" type="number" class="form-control" placeholder="Target LTV" @change="targetLtvOnChange">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2" v-for="(property, key) in allProperties" :key="key" >
                            <input 
                                type="checkbox" 
                                :id="property.id"
                                :value="property.id"
                                :checked="property.partOfSecurity === 'Yes'"
                                @change="updatePartyOfSecurity(property.id, $event.target.checked)"                                    
                            />
                            <label class="ms-2">
                                P#{{ property.propertyOrder }} - {{ property.fullAddress }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="card mb-3" v-for="(property, key) in properties" :key="key">
                            <table class="table table-hover mb-0 table-fix-top-rounded table-fix-bottom-rounded">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <strong>Property #{{ property.propertyOrder }}</strong>
                                            <span v-if="property.alpineInterest < 100" class="ms-2 text-danger">(Alpine Interest: {{ property.alpineInterest }}%)</span>
                                        </th>
                                        <th>Property Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <input type="radio" class="me-1" v-model="property.ltvSelected" value="A" @change="calculateLTV" :checked="appraisedValueRadioSelect[key]" />Appraisal
                                        </td>
                                        <td>{{ formatCurrency(property.appraisedValue) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="radio" class="me-1" v-model="property.ltvSelected" value="S" @change="calculateLTV" :checked="assessedValueRadioSelect[key]" />Assessment
                                        </td>
                                        <td>{{ formatCurrency(property.assessedValue) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="radio" class="me-1" v-model="property.ltvSelected" value="C" @change="calculateLTV" :checked="customerValueRadioSelect[key]" />Customer
                                        </td>
                                        <td>{{ formatCurrency(property.customerValue) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="radio" class="me-1" v-model="property.ltvSelected" value="E" @change="calculateLTV" :checked="estimateValueRadioSelect[key]" />Estimate
                                        </td>
                                        <td>{{ formatCurrency(property.estimateValue) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <template v-if="property.seniorMortgages !== undefined && property.seniorMortgages.length > 0">
                                <div class="d-flex justify-content-center mt-2">
                                    <strong>Existing Mortgages</strong>
                                </div>
                                <table class="table table-hover mb-0 table-fix-bottom-rounded">
                                    <tbody>
                                        <tr>
                                            <td>Position</td>
                                            <td>Balance</td>
                                            <td>Payment</td>
                                            <td>Interest Rate</td>
                                            <td>Lender</td>
                                            <td>Payout?</td>
                                        </tr>
                                        <tr v-for="(seniorMortgage, k) in property.seniorMortgages" :key="k">
                                            <td>{{ seniorMortgage.position }}</td>
                                            <td>{{ formatCurrency(seniorMortgage.balance * (seniorMortgage.interest / 100)) }}</td>
                                            <td>{{ formatCurrency(seniorMortgage.payment) }}</td>
                                            <td>{{ seniorMortgage.interestRate }}%</td>
                                            <td>{{ seniorMortgage.lenderName }}</td>
                                            <td>{{ seniorMortgage.payout }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>

                            <template v-if="property.inHouseMortgages !== undefined && property.inHouseMortgages.length > 0">
                                <div class="d-flex justify-content-center mt-2">
                                    <strong>In House Mortgages</strong>
                                </div>
                                <table class="table table-hover mb-0 table-fix-bottom-rounded">
                                    <tbody>
                                        <tr>
                                            <td>Position</td>
                                            <td>Balance</td>
                                            <td>Payment</td>
                                            <td>Interest Rate</td>
                                            <td>Lender</td>
                                            <td>Payout?</td>
                                        </tr>
                                        <tr v-for="(inHouseMortgage, k) in property.inHouseMortgages" :key="k">
                                            <td>{{ inHouseMortgage.position }}</td>
                                            <td>{{ formatCurrency(inHouseMortgage.balance) }}</td>
                                            <td>{{ formatCurrency(inHouseMortgage.payment) }}</td>
                                            <td>{{ inHouseMortgage.interestRate }}%</td>
                                            <td>{{ inHouseMortgage.lenderName }}</td>
                                            <td>{{ inHouseMortgage.payout }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                        </div>

                        <table class="table table-hover mb-0 mt-3 table-fix-bottom-rounded">
                            <tbody>
                                <tr>
                                    <td><strong>Other Mortgage Balance</strong></td>
                                    <td>{{ formatCurrency(seniorMortgageBalance) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alpine Mortgage Balance</strong></td>
                                    <td>{{ formatCurrency(inHouseMortgageBalance) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Quote Gross Amount</strong></td>
                                    <td>{{ formatCurrency(grossAmount) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Combined Property Value</strong></td>
                                    <td>{{ formatCurrency(combinedPropertyValue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-header d-flex justify-content-center">
                        <strong>Other Info</strong>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 table-fix-bottom-rounded">
                            <tbody>
                                <tr>
                                    <td><strong>Balance at End of Term</strong></td>
                                    <td>{{ formatDecimal(balanceAtTermEnd) }}</td>
                                </tr>
                                <tr v-if="loanTerm > 12">
                                    <td><strong>2nd Year Monthly Payment</strong></td>
                                    <td>{{ formatDecimal(monthlyPayment2ndYear) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Annual Effective Rate</strong></td>
                                    <td v-if="aer === '---'">{{ aer }}</td>
                                    <td v-else v-bind:class="[aer >= 35 ? 'text-danger' : 'text-success']">{{ formatDecimal(aer) }}%</td>
                                </tr>
                                <tr>
                                    <td><strong>Semi-Annual Comp Equiv</strong></td>
                                    <td>{{ formatDecimal(semiAnnualCompEquiv) }}%</td>
                                </tr>
                                <tr>
                                    <td><strong>APR</strong></td>
                                    <td v-bind:class="[apr >= 35 ? 'text-danger' : 'text-success']">{{ formatDecimal(apr) }}%</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>Net Amount</strong></td>
                                    <td>{{ formatDecimal(aprNetAmount) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>Other Fee</strong></td>
                                    <td>{{ formatDecimal(aprOtherFee) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>Installment Payments</strong></td>
                                    <td>{{ formatDecimal(aprInstallmentPayments) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>OSB at Maturity</strong></td>
                                    <td>{{ formatDecimal(aprOSBAtMaturity) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>Cost of Credit</strong></td>
                                    <td>{{ formatDecimal(aprCostOfCredit) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><strong>Average OSB</strong></td>
                                    <td>{{ formatDecimal(aprAverageOSB) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>GDSR</strong></td>
                                    <td>{{ formatDecimal(gdsr) }}%</td>
                                </tr>
                                <tr>
                                    <td><strong>TDSR</strong></td>
                                    <td>{{ formatDecimal(tdsr) }}%</td>
                                </tr>
                                <tr>
                                    <td><strong>DSCR</strong></td>
                                    <td>{{ formatDecimal(dscr) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    <Checklist
        :application="application"
        :quote="quoteTmp"
        :checkListType="'RTB'"
        :refreshCount="refreshCount"
        @close="checklistClosed"
    />

    <IlaModal :applicationId="selectedApplicationId" :refreshCount="refreshCount" @selected="onSelectIla" />

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        :type="dialogType"
        :parentModalId="modalId"
        :key="modalId"
        @return="dialogOnReturn"
    />
</template>

<script>
import { util } from '../mixins/util'
import { quote } from '../mixins/quote'
import CurrencyInput from '../components/CurrencyInput.vue'
import ConfirmationDialog from '../components/ConfirmationDialog'
import Checklist from '../components/Checklist'
import IlaModal from '../components/modals/IlaModal.vue'
import ApplicantsInfo from '../components/ApplicantsInfo.vue'

export default {
    emits: ['refreshData'],
    mixins: [util, quote],
    components: { 
        CurrencyInput,
        ConfirmationDialog,
        Checklist,
        IlaModal,
        ApplicantsInfo,
    },
    props: ['application', 'applicants', 'selectedTab', 'isSalesJourney', 'agentOptions', 'salesJourney'],
    watch: {
        application: function(newValue, oldValue) {
            this.getData()
            this.getFees()
            this.getPrimeRate()
            this.getIla()
            this.getProperties()
        },
        netAmount: function(newValue, oldValue) {
            this.calculateApr()
        },
        grossAmount: function(newValue, oldValue) {
            this.calculateLTV()
            this.calculateMonthlyPayment()
            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },
        monthlyPayment: function(newValue, oldValue) {
            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
            this.getCosts()
        },
        primeRateTotal: function(newValue, oldValue) {
            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
            this.getCosts()
        },
        otherFee: function(newValue, oldValue) {
            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },
        loanTerm: function(newValue, oldValue) {
            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },
        ltv: function(newValue, oldValue) {
            this.getPricebook()
            this.checkFivePercent()
        },
        mortgageType: {
            handler: function(newValue, oldValue) {
                if(newValue === '519') {
                    // Interest Only
                    this.amortization = '999';

                } else if(newValue === '537') {
                    //Reverse Mortgage
                    this.amortization = '0';
                    this.monthlyPayment = '0';
                }
            },
            deep: true
        },
    },
    data() {
        return {
            showChecklist: false,
            type: 'n',
            netAmount: 0,
            legalFee: 0,
            applicationFee: 0,
            appraisalFee: 0,
            brokerageFee: 0,
            brokerageFeePerc: 0,
            discountFee: 0,
            discountFeePerc: 0,
            grossAmount: 0,
            otherFee: 0,

            mortgageType: '518',
            loanCategory: '545',
            interestRateType: 'F',
            loanTerm: '',
            amortization: '',
            amortizationDisabled: false,
            interestRate: '',
            noInterest: false,
            primeRate: 0,
            primeRate2ndYear: 0,
            primeRateTotal: 0,
            compounded: '12',
            monthlyPayment: '',
            retainer: 0,
            retainerDisb: false,
            quoted: 'N/A',
            specialPricing: 'N/A',
            clientAuthDate: new Date().toISOString().split('T')[0],
            interestCommDate: this.getFirstOfMonth(),
            firstPaymentDueDate: '',
            mip: 3,
            typeLoan: '496',
            documentation: 'ILG',
            assignmentRent: 'No',
            holdBackRequired: 'No',
            holdBack: 0,
            quoteComments: '',
            quotes: [],
            quote: {},

            balanceAtTermEnd: 0,
            monthlyPayment2ndYear: 0,
            aer: null,
            semiAnnualCompEquiv: 0,

            apr: 0,
            aprNetAmount: 0,
            aprOtherFee: 0,
            aprInstallmentPayments: 0,
            aprOSBAtMaturity: 0,
            aprCostOfCredit: 0,
            aprAverageOSB: 0,
            gdsr: 0,
            tdsr: 0,
            dscr: 0,
            cityClassification: '',
            company: '',
            mortgageGroup: '',

            ila: '',
            ilaName: '',
            pricebook: {},
            pricebookError: null,

            targetLtv: '',
            ltv: 0,
            seniorMortgageBalance: 0,
            inHouseMortgageBalance: 0,
            combinedPropertyValue: 0,
            properties: [],
            allProperties: [],
            refreshCount: 0,
            selectedIla: 0,
            selectedApplicationId: 0,

            types: [
                { value: 'n', text: 'Net Amount' },
                { value: 'g', text: 'Gross Amount' },
                { value: 'dn', text: 'DN' },
                { value: 'dg', text: 'DG' },
            ],
            mortgageTypes: [
                { value: '518', text: 'Regular' },
                { value: '519', text: 'Interest Only' },
                { value: '537', text: 'Reverse Mortgage' },
                { value: '547', text: 'DSCR' },
                { value: '548', text: 'BFS' },
            ],
            loanCategories: [
                { value: '545', text: 'Residential' },
                { value: '546', text: 'Commercial' },
            ],
            interestRateTypes: [
                { value: 'F', text: 'Fixed' },
                { value: 'V', text: 'Variable' },
            ],
            specialPrices: [
                { value: 'N/A', text: 'N/A' },
                { value: 'NB - SP - NO', text: 'NB - SP - NO' },
                { value: 'NB - SP - Yes', text: 'NB - SP - Yes' },
                { value: 'PB - SP - NO', text: 'PB - SP - NO' },
                { value: 'PB - SP - Yes', text: 'PB - SP - Yes' },
                { value: '700+ - SP - NB', text: '700+ - SP - NB' },
                { value: '700+ - SP - PB', text: '700+ - SP - PB' },
                { value: 'NB - 55% - Urban', text: 'NB - 55% - Urban' },
            ],
            typeLoans: [
                { value: '496', text: 'Closed' },
                { value: '497', text: 'Open' },
                { value: '498', text: 'Open after 3 Months' },
                { value: '499', text: 'Open after 6 Months' },
                { value: '500', text: 'Open after 12 Months' },
            ],
            documentations: [
                { value: 'A&C', text: 'A&C' },
                { value: 'AAC', text: 'AAC' },
                { value: 'ACL', text: 'ACL' },
                { value: 'AMC', text: 'AMC' },
                { value: 'Hendrix', text: 'Hendrix' },
                { value: 'ILG', text: 'ILG' },
                { value: 'JA', text: 'JA' },
                { value: 'Lovatt', text: 'Lovatt' },
                { value: 'Lunny', text: 'Lunny' },
                { value: 'P&P', text: 'P&P' },
                { value: 'R&R', text: 'R&R' },
                { value: 'SCL', text: 'SCL' },

            ],
            compoundeds: [
                { value: '1', text: 'Annual' },
                { value: '365', text: 'Daily' },
                { value: '12', text: 'Monthly' },
                { value: '4', text: 'Quarterly' },
                { value: '2', text: 'SA' },
                { value: '52', text: 'Weekly' },
            ],
            quoteds: [
                { value: 'Fully', text: 'Fully' },
                { value: 'N/A', text: 'N/A' },
                { value: 'Payments', text: 'Payments' },
                { value: 'Pmt / Rate', text: 'Pmt / Rate' },
            ],
            assignmentRents: [
                { value: 'No', text: 'No' },
                { value: 'Yes', text: 'Yes' },
            ],
            holdBacksRequired: [
                { value: 'No', text: 'No' },
                { value: 'Yes', text: 'Yes' },
            ],
            event: '',
            quoteTmp: {},
            modalId: 'contactCenterQuote',
            dialogMessage: '',
            dialogType: '',
            showChecklist: false,
            refreshCount: 0,
            quoteTmp: {},
            debounceTimer: null,
            appraisedValueRadioSelect: [],
            assessedValueRadioSelect: [],
            customerValueRadioSelect: [],
            estimateValueRadioSelect: []
        }
    },
    mounted() {
        this.validateDate(this.interestCommDate,'I');
    },
    computed: {
        selectedLtvValues() {
            return this.properties.map((property, index) => ({
                id: property.id || index,
                idx: property.idx,
                ltvSelected: property.ltvSelected,
            }));
        },
        isAmortizationDisabled() {
            return this.mortgageType == '537' || this.mortgageType == '519'
        },
        isMonthlyPaymentDisabled() {
            return this.mortgageType == '537' || this.mortgageType == '519'
        },
        brokerageFeeLabel() {
            if (this.company === 'SQC') {
                return 'Lender Fee / Discount';

            } else {
                return 'Brokerage Fee';
            }
        },
        brokerageDiscountLabel() {
            if (this.company === 'SQC') {
                return 'Broker Fee';

            } else {
                return 'Brokerage / Discount';
            }
        },
    },
    methods: {
        updatePartyOfSecurity: function(propertyId, isChecked) {

            let partOfSecurity = 'No'

            if (isChecked) {
                partOfSecurity = 'Yes'
            }

            this.axios({
                method: 'put',
                url: '/web/contact-center/part-of-security/' + propertyId,
                data: {
                    partOfSecurity: partOfSecurity
                }
            })
            .then(response => {
                this.getData()
                this.getFees()
                this.getPrimeRate()
                this.getIla()
                this.getProperties()
                this.$emit('refreshData');
            })
            .catch(error => {
                console.log(error)
            })
        },
        getFirstOfMonth() {
            const today = new Date();
            return new Date(today.getFullYear(), today.getMonth(), 1)
                .toISOString()
                .slice(0, 10);
        },
        validateDate: function(date, type) {
            if (date !== null && date !== '') {

                const dateParts = date.split('-');
                const parsedDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
                const day = parsedDate.getDate();

                if (type === 'I') {
                    if (day !== 1 && day !== 15) {
                        this.alertMessage = 'The date must be either the 1st or 15th of the month.'
                        this.showAlert('error')
                        this.interestCommDate = '';
                    } else {
                        let newDate = new Date(parsedDate);
                        newDate.setMonth(newDate.getMonth() + 1);
                        this.firstPaymentDueDate = newDate.toISOString().split('T')[0];
                    }
                } else if (type === 'F') {
                    if (day !== 1 && day !== 15) {

                        this.alertMessage = 'The date must be either the 1st or 15th of the month.'
                        this.showAlert('error')

                        this.firstPaymentDueDate = '';
                    }
                }
            }
        },
        selectIla: function() {
            this.selectedApplicationId = this.application.id
            this.refreshCount++;
            this.showModal('ilaModal');
        },
        onSelectIla(ila) {
            this.ila = ila

            this.axios({
                method: 'put',
                url: '/web/contact-center/ila',
                data: {
                    applicationId: this.application.id,
                    ila: this.ila
                }
            })
            .then(response => {
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
                this.getIla()
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getIla: function() {
            if(this.application.id === undefined) return

            this.axios({
                method: 'get',
                url: '/web/quote/ila/' + this.application.id
            })
            .then(response => {
                this.ila = response.data.data.ila
                this.ilaName = response.data.data.ilaName
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        use: function(quote) {
            this.type = quote.type
            this.tmpNetAmount = quote.netAmount
            this.netAmount = quote.netAmount
            this.legalFee = quote.legalFee
            this.applicationFee = quote.applicationFee
            this.appraisalFee = quote.appraisalFee
            this.brokerageFee = quote.brokerageFee
            this.discountFee = quote.discountFee
            if(this.type == 'dn' || this.type == 'dg') {
                this.brokerageFeePerc = (quote.brokerageFee / quote.grossAmount) * 100
                this.discountFeePerc = (quote.discountFee / quote.grossAmount) * 100
            } else {
                this.brokerageFeePerc = (quote.brokerageFee / quote.netAmount) * 100
                this.discountFeePerc = (quote.discountFee / quote.netAmount) * 100
            }
            this.grossAmount = quote.grossAmount
            this.otherFee = quote.otherFee
            this.monthlyPayment = quote.monthlyPayment

            this.mortgageType = quote.mortgageType
            this.loanCategory = quote.loanCategory
            this.interestRateType = quote.interestRateType
            this.loanTerm = quote.loanTerm
            this.amortization = quote.amortization
            this.interestRate = quote.interestRate

            this.primeRate2ndYear = quote.primeRate2ndYear
            this.primeRateTotal = quote.primeRateTotal
            this.compounded = quote.compounded
            this.retainer = quote.retainer
            this.quoted = quote.quoted
            this.specialPricing = quote.specialPricing

            this.clientAuthDate = new Date().toISOString().split('T')[0]
            this.interestCommDate = quote.interestCommDate
            // this.validateDate(this.interestCommDate,'I')
            this.firstPaymentDueDate = quote.firstPaymentDueDate

            this.mip = quote.mip
            this.typeLoan = quote.typeLoan
            this.documentation = quote.documentation
            this.assignmentRent = quote.assignmentRent
            this.holdBackRequired = quote.holdBackRequired
            this.holdBack = quote.holdBack
            this.quoteComments = quote.quoteComments

            this.calculateLTV()
            this.calculateMonthlyPayment()
            this.calculateAmortization()            
        },
        destroy(quote) {
            this.dialogMessage = "Are you sure you want to DELETE this Quote?"
            this.quoteTmp = quote
            this.event = 'destroy'
            this.dialogType = 'danger'
            this.showModal('confirmationDialog' + this.modalId)
        },
        destroyOnConfirm: function() {
            this.showPreLoader()

            this.axios({
                method: 'delete',
                url: '/web/quote/' + this.quoteTmp.id
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                    this.getData()
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
        dialogOnReturn: function (event, returnMessage, returnRemoveReason) {
            if(event == 'destroy' && returnMessage === 'confirmed') {
                this.destroyOnConfirm()
            } else if(event == 'store' && returnMessage === 'confirmed') {
                this.storeOnConfirm()
            }
        },
        store: function() {
            this.dialogMessage = "Are you sure you want to save a new quote?"
            this.event = 'store'
            this.dialogType = 'success'
            this.showModal('confirmationDialog' + this.modalId)
        },
        storeOnConfirm: function() {

            if(this.apr >= 35) {
                this.alertMessage = 'APR must be less than 35%!'
                this.showAlert('error')
                return
            }

            if(this.aer >= 35) {
                this.alertMessage = 'Annual Effective Rate must be less than 35%!'
                this.showAlert('error')
                return
            }

            if(this.specialPricing == 'N/A') {
                this.alertMessage = 'Special Pricing is required'
                this.showAlert('error')
                return
            }

            if (this.loanTerm == '' || this.loanTerm == null || this.loanTerm <= 0) {
                this.alertMessage = 'Loan Term is required'
                this.showAlert('error')
                return
            }

            if (this.amortization == '' || this.amortization == null || (this.amortization <= 0 && this.mortgageType != '537')) {
                this.alertMessage = 'Amortization is required'
                this.showAlert('error')
                return
            }

            if (this.interestRate == '' || this.interestRate == null || this.interestRate <= 0) {
                this.alertMessage = 'Interest Rate is required'
                this.showAlert('error')
                return
            }

            if (this.compounded == '' || this.compounded == null || this.compounded <= 0) {
                this.alertMessage = 'Compounded is required'
                this.showAlert('error')
                return
            }

            if (this.ltv == '' || this.ltv == null || this.ltv <= 0) {
                this.alertMessage = 'LTV cannot be zero!'
                this.showAlert('error')
                return
            }

            /*if (this.aer == '' || this.aer == null || this.aer <= 0 || this.aer == '---') {
                this.alertMessage = 'AER invalid! Please check the field values.'
                this.showAlert('error')
                return
            }*/

            if (this.apr == '' || this.apr == null || this.apr <= 0 || this.apr == '---') {
                this.alertMessage = 'APR invalid! Please check the field values.'
                this.showAlert('error')
                return
            }

            this.showPreLoader()

            let quote = {
                applicationId: this.application.id,
                type: this.type,
                netAmount: this.netAmount,
                legalFee: this.legalFee,
                applicationFee: this.applicationFee,
                appraisalFee: this.appraisalFee,
                brokerageFee: this.brokerageFee,
                brokerageFeePerc: this.brokerageFeePerc,
                discountFee: this.discountFee,
                discountFeePerc: this.discountFeePerc,
                grossAmount: this.grossAmount,
                otherFee: this.otherFee,
                loanCategory: this.loanCategory,
                mortgageType: this.mortgageType,
                interestRateType: this.interestRateType,
                loanTerm: this.loanTerm,
                amortization: this.amortization,
                amortizationDisabled: this.amortizationDisabled,
                interestRate: this.interestRate,
                noInterest: this.noInterest,
                primeRate: this.primeRate,
                primeRate2ndYear: this.primeRate2ndYear,
                primeRateTotal: this.primeRateTotal,
                compounded: this.compounded,
                monthlyPayment: this.monthlyPayment,
                retainer: this.retainer,
                retainerDisb: this.retainerDisb,
                quoted: this.quoted,
                specialPricing: this.specialPricing,
                clientAuthDate: this.clientAuthDate,
                interestCommDate: this.interestCommDate,
                firstPaymentDueDate: this.firstPaymentDueDate,
                mip: this.mip,
                typeLoan: this.typeLoan,
                documentation: this.documentation,
                assignmentRent: this.assignmentRent,
                holdBackRequired: this.holdBackRequired,
                holdBack: this.holdBack,
                quoteComments: this.quoteComments,
                ltv: this.ltv,
                ila: this.ila,
                balanceAtTermEnd: this.balanceAtTermEnd,
                monthlyPayment2ndYear: this.monthlyPayment2ndYear,
                aer: this.aprNetAmount,
                apr: this.apr,
                discountFeePercent: this.discountFeePerc,
                propertyltvSelected: this.selectedLtvValues,
                gdsr: this.gdsr,
                tdsr: this.tdsr,
                dscr: this.dscr,
                aerPercentage: this.aer
            }

            this.axios({
                method: 'post',
                url: '/web/quote',
                data: {
                    quote: quote,
                    properties: this.properties
                }
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                    this.getData()
                } else {
                    this.getProperties()
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
        storeQuoteChanges: function(quote) {
            this.axios({
                method: 'put',
                url: '/web/quote',
                data: {
                    quote: quote
                }
            })
            .then(response => {
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
                this.getData()
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        readyToBuyOnChange(quote) {
            if(quote.readyToBuy === 'Yes') {
                this.quoteTmp = quote
                this.refreshCount++
                this.showChecklist = true;
                this.showModal('checklistRTB');
            } else {
                this.showChecklist = false;
                this.storeQuoteChanges(quote)
            }
        },
        checklistClosed: function() {
            this.showChecklist = false;
            this.quoteTmp.readyToBuy = 'No'
        },
        getData: function() {
            if(this.application.id === undefined) {
                return
            }
            
            this.axios({
                method: 'get',
                url: '/web/quote/' + this.application.id
            })
            .then(response => {
                this.quotes = response.data.data.quotes
                this.cityClassification = response.data.data.cityClassification
                this.company = response.data.data.company
                this.mortgageGroup = response.data.data.mortgageGroup
                if(this.quotes.length > 0) {
                    this.use(this.quotes[this.quotes.length - 1])
                    this.documentation = 'ILG'
                } else {
                    this.netAmount = this.application.amountRequested
                    this.netAmountOnChange()
                }

                this.getCosts()
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        generateTooltipContent(quote) {
            return `
                <table style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Date</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatPhpDate(quote.date)}</td>
                        <td style="padding: 4px"></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Gross Amount</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.grossAmount)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Legal</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.legalFee)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Application</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.applicationFee)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Brokerage</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.brokerageFee)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Discount</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.discountFee)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Appraisal</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.appraisalFee)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Net Amount</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.netAmount)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Term</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.loanTerm}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Amortization</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.amortization}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Interest</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.interestRate}%</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">2nd Year</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.secondYear ? `${quote.secondYear}%` : 'N/A'}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Monthly</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.monthlyPayment)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">LTV</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.ltv)}%</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Enable Quote</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.enableQuote}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Ready to Buy</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.readyToBuy}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Mature Balance</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.matureBalance)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Lender</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.lender}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">FM</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.fm}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Mortgage Code</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${quote.mortgageCode}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Client Auth Date</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatPhpDate(quote.clientAuthDate)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Interest Comm Date</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatPhpDate(quote.interestCommDate)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">First Payment Due Date</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatPhpDate(quote.firstPaymentDueDate)}</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">Second Year Montly Payment</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.monthlyPayment2ndYear)}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">GDSR</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.gdsr)}%</td>
                        <td></td>
                        <td style="border: 1px solid #ddd; padding: 4px;">TDSR</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.formatDecimal(quote.tdsr)}%</td>
                    </tr>
                </table>
            `;
        }, 
        generateTooltipLtv() {

             if(this.pricebook === null) return this.pricebookError

            if (!this.pricebook || Object.keys(this.pricebook).length === 0) {
                    return `No data is available`;
                }

            return `
                <table style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <td>Suggestion based on:</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">LTV</td>
                    <td style="border: 1px solid #ddd; padding: 4px;">
                        ${this.pricebook.ltv ? this.formatDecimal(this.pricebook.ltv) + '%' : ''}
                    </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Position</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.pricebook.position} Mortgage</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">Province</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.pricebook.province}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 4px;">City Class</td>
                        <td style="border: 1px solid #ddd; padding: 4px;">${this.pricebook.cityClassification}</td>
                    </tr>
                </table>
            `
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