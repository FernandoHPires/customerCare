<template>
    <div class="card mt-3">
        <div class="card-header">
            Mortgages
        </div>

        <div class="card-body p-0">
            <table class="table table-hover text-center mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th>Mortgage</th>
                        <th>Gross Amount</th>
                        <th>Funded Date</th>
                        <th>Position</th>
                        <th>Lender</th>
                        <th>Current LTV</th>
                        <th>OSB</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="mortgages.length === 0">
                        <td colspan="8">No mortgages</td>
                    </tr>
                    <tr v-for="(mortgage, key) in mortgages" :key="key" @click="mortgageDetails(mortgage)" class="cursor-pointer">
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}">
                            <i v-if="selectedMortgage.id === mortgage.id" class="bi bi-arrow-right-circle-fill"></i>
                        </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}">
                            {{ mortgage.mortgageCode }}
                            <span v-if="mortgage.investorCode"> / {{ mortgage.investorCode }}</span>
                            <span v-if="mortgage.paidOut"> (Paid Out)</span>
                            <span v-if="mortgage.foreclosure && !mortgage.paidOut"> (Foreclosure)</span>
                        </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ formatDecimal(mortgage.grossAmount) }} </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ formatDate(mortgage.fundedDate) }} </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ mortgage.positions }} </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ mortgage.lenderName }} </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ mortgage.currentLTV ? formatDecimal(mortgage.currentLTV) + '%' : '' }} </td>
                        <td :class="{'bg-warning': mortgage.foreclosure && !mortgage.paidOut}"> {{ formatDecimal(mortgage.currentBalance) }} </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3" v-if="Object.keys(selectedMortgage).length > 0">

        <div class="card-header" :class="{'bg-warning': selectedMortgage.foreclosure && !selectedMortgage.paidOut}">
            <div class="d-flex">
                <div>
                    {{ selectedMortgage.mortgageCode }}<span v-if="selectedMortgage.investorCode"> / {{ selectedMortgage.investorCode }}</span><span v-if="selectedMortgage.paidOut"> (Paid Out)</span>
                </div>

                <div class="me-auto"></div>

                <i class="bi bi-x-lg cursor-pointer" @click="selectedMortgage = {}"></i>
            </div>
        </div>

        <div class="card-body">
            <div class="row">                
                <div class="col-4">
                    <!-- Mortgage Particulars-->
                    <div class="card mb-3">
                        <div class="card-header">
                            Mortgage Particulars
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-end flex-wrap">
                                <div class="form-group px-1">
                                    <label class="fw-bold">Opened</label>
                                    <small>
                                        <p class="mb-1">{{ formatDate(selectedMortgage.mortgageIntCommDate) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Face Amount</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.grossAmount) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Interest Rate</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.interestRate) }}%</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Payment</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.monthlyPmt) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Amort</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.amortization) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Term Due</label>
                                    <small>
                                        <p class="mb-1">{{ formatDate(selectedMortgage.dueDate) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">NSF</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.nsfFee) }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Assn Rent</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.assignmentOfRent }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Initial LTV</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.initialLtv) }}%</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Current LTV</label>
                                    <small>
                                        <p class="mb-1">{{ formatDecimal(selectedMortgage.currentLtv) }}%</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Mortgage #</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.mortgageCode }} ( {{ selectedMortgage.mtgTransferCode }}) - {{ selectedMortgage.brokerGroup }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Mortgage Type</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.mortgageType }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Type of Loan</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.typeOfLoan }}</p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Loan Category</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.loanCategory }}</p>
                                    </small>
                                </div>

                                <div class="form-group px-1">
                                    <label class="fw-bold">Mtg Reg #</label>
                                    <small>
                                        <p class="mb-1">                                            
                                            {{ selectedMortgage.mortgageRegistrationNumber ? selectedMortgage.mortgageRegistrationNumber : '\u00A0' }}
                                        </p>
                                        
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Assn Rents Reg #</label>
                                    <small>
                                        <p class="mb-1">
                                            {{ selectedMortgage.assignmentOfRentsRegistrationNumber ? selectedMortgage.assignmentOfRentsRegistrationNumber: '\u00A0' }}

                                        </p>
                                    </small>
                                </div>
                                <div class="form-group px-1">
                                    <label class="fw-bold">Funded</label>
                                    <small>
                                        <p class="mb-1">{{ selectedMortgage.isFunded }}</p>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mortgagors -- Applicants-->
                    <div class="card mb-3">
                        <div class="card-header">
                            Mortgagors
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Age</th>
                                            <th>Beacon Score</th>
                                            <th>Phone Number</th>
                                            <th>Email</th>
                                            <th>Type</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(m, key) in mortgagors" :key="key">
                                            <td><small>{{ m.firstName }} {{ m.lastName }}</small></td>
                                            <td><small>{{ m.gender }}</small></td>
                                            <td><small>{{ m.age }}</small></td>
                                            <td><small>{{ m.beacon }}</small></td>
                                            <td><small>{{ m.homePhone }}</small></td>
                                            <td><small>{{ m.email }}</small></td>
                                            <td><small>{{ m.type }}</small></td>
                                        </tr>
                                        <tr v-if="mortgagors.length === 0">
                                            <td colspan="8">No Mortgagors</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div class="card">
                        <div class="card-header">
                            Payments
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Payment</th>
                                            <th>Interest</th>
                                            <th>Service Charge</th>
                                            <th>Principal Payment</th>
                                            <th>Outstanding Balance</th>
                                            <th>Monthly Interest</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(p, key) in payments" :key="key">
                                            <td><small>{{ formatDate(p.processingDate) }}</small></td>
                                            <td><small>{{ formatDecimal(p.paymentAmount) }}</small></td>
                                            <td><small>{{ formatDecimal(p.interest) }}</small></td>
                                            <td><small>{{ formatDecimal(p.serviceCharge) }}</small></td>
                                            <td><small>{{ formatDecimal(p.principalPayment) }}</small></td>
                                            <td><small>{{ formatDecimal(p.outstandingBalance) }}</small></td>
                                            <td><small>{{ formatDecimal(p.monthlyInterest) }}%</small></td>
                                            <td><small>{{ p.comment }}</small></td>
                                        </tr>

                                        <tr v-if="payments.length === 0">
                                            <td colspan="8">No payments</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Payments -->
                    <div class="card mt-3">
                        <div class="card-header">
                            Upcoming Payments
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Original Date</th>
                                        <th>Payment Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, key) in upcomingPayments" :key="key">
                                        <td><small>{{ formatDate(p.processingDate) }}</small></td>
                                        <td><small>{{ formatDate(p.originalDate) }}</small></td>
                                        <td><small>{{ formatDecimal(p.paymentAmount) }}</small></td>
                                    </tr>

                                    <tr v-if="upcomingPayments.length === 0">
                                        <td colspan="3">No upcoming payments</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Investor Tracking -->
                    <div class="card mt-3">
                        <div class="card-header">
                            Investor Tracking
                        </div>
                        <div class="card-body p-0">
                            <div class = "table-responsive">
                                <table class="table table-hover text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Investor</th>
                                            <th>Exp. Date</th>
                                            <th>Committed</th>
                                            <th>Discount</th>
                                            <th>Price</th>
                                            <th>Yield</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(inv, key) in investors" :key="key">
                                            <td><small>{{ formatDate(inv.quoteDate) }}</small></td>
                                            <td><small>{{ inv.investorName }}</small></td>
                                            <td><small>{{ formatDate(inv.expectedDate) }}</small></td>
                                            <td><small>{{ inv.committed }}</small></td>
                                            <td><small>{{ formatDecimal(inv.discount) }}</small></td>
                                            <td><small>{{ formatDecimal(inv.salePrice) }}</small></td>
                                            <td><small>{{ formatDecimal(inv.yield) }}%</small></td>
                                            <td><small>{{ inv.quoteComment }}</small></td>
                                        </tr>
                                        <tr v-if="investors.length === 0">
                                            <td colspan="3">No investors</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <!-- Properties -->
                    <div class="card mb-3">
                        <div class="card-header">
                            Properties
                        </div>
                        <div class="card-body">
                            <div v-for="(property, key) in properties" :key="key">
                                <div v-if="key > 0">
                                    <hr>
                                </div>
                                <div class="mb-2">
                                    <span><b>Property #{{ key + 1 }}</b></span>
                                </div>
                                <div class="d-flex align-items-end flex-wrap">
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Title Holders</label>
                                        <small>
                                            <p class="mb-1">{{ property.titleHolders }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Alpine Interest</label>
                                        <small>
                                            <p class="mb-1">{{ property.interest }}%</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Own/Rent</label>
                                        <small>
                                            <p class="mb-1">{{ property.ownRent }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Same as Mailing</label>
                                        <small>
                                            <p class="mb-1">{{ property.sameAsMailing }}</p>
                                        </small>
                                    </div>                                    
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Position</label>
                                        <small>
                                            <p class="mb-1">{{ property.position }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Address</label>                                        
                                        <small>
                                            <p class="mb-1">{{ property.address }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">How Long</label>
                                        <small>
                                            <p class="mb-1">{{ property.numberOfYears }}</p>
                                        </small>
                                    </div>                                    
                                    <div class="form-group px-1">
                                        <label class="fw-bold">PID</label>
                                        <small>
                                            <p class="mb-1">{{ property.pid }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Legal Description</label>
                                        <small>
                                            <p class="mb-1">{{ property.legal }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Property Taxes</label>
                                        <small>
                                            <p class="mb-1">{{ formatDecimal(property.pptyTax) }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Arrears</label>
                                        <small>
                                            <p class="mb-1">{{ property.pptyTaxArrears }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Ins. Arrears</label>
                                        <small>
                                            <p class="mb-1">{{ property.insArrears }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Cost Price</label>
                                        <small>
                                            <p class="mb-1">{{ formatDecimal(property.costPrice) }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Downpayment</label>
                                        <small>
                                            <p class="mb-1">{{ property.downpayment }}</p>
                                        </small>
                                    </div> 
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Cust. Value</label>
                                        <small>
                                            <p class="mb-1">{{ formatDecimal(property.customerValue) }}</p>
                                        </small>
                                    </div> 
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Ass. Value</label>
                                        <small>
                                            <p class="mb-1">{{ concatenatedValue(property) }}</p>
                                        </small>
                                    </div> 
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Appr. Value</label>
                                        <small>
                                            <p class="mb-1">{{ formatDecimal(property.valueMethod) }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Appr. By</label>
                                        <small>
                                            <p class="mb-1">{{ property.apprBy }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Appr. Ordered Date</label>
                                        <small>
                                            <p class="mb-1">{{ formatDate(property.appraisalDateOrdered) }}</p>
                                        </small>
                                    </div> 
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Appr. Report Date</label>
                                        <small>
                                            <p class="mb-1">{{ formatDate(property.appraisalDateReceived) }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Built</label>
                                        <small>
                                            <p class="mb-1">{{ property.built }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Lot Size</label>
                                        <small>
                                            <p class="mb-1">{{ property.lotSize }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Floor Area</label>
                                        <small>
                                            <p class="mb-1">{{ property.floorArea }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Basement</label>
                                        <small>
                                            <p class="mb-1">{{ property.basement }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Bedrooms</label>
                                        <small>
                                            <p class="mb-1">{{ property.bedrooms }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Bathrooms</label>
                                        <small>
                                            <p class="mb-1">{{ property.bathrooms }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Roofing</label>
                                        <small>
                                            <p class="mb-1">{{ property.roofing }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Exterior Finishing</label>
                                        <small>
                                            <p class="mb-1">{{ property.exteriorFinishing }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">House Style</label>
                                        <small>
                                            <p class="mb-1">{{ property.houseStyle }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Garage</label>
                                        <small>
                                            <p class="mb-1">{{ property.garage }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Out Building</label>
                                        <small>
                                            <p class="mb-1">{{ property.outBuilding }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Water Source</label>
                                        <small>
                                            <p class="mb-1">{{ property.waterSource }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Sewage</label>
                                        <small>
                                            <p class="mb-1">{{ property.sewage }}</p>
                                        </small>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="fw-bold">Other</label>
                                        <small>
                                            <p class="mb-1">{{ property.other }}</p>
                                        </small>                                        
                                    </div>
                                </div>

                                <div v-if="property.showPropertyMortgages" class="card-body p-0 mb-3">
                                    <table class="table table-hover text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Balance</th>
                                                <th>Payment</th>
                                                <th>Payment Type</th>
                                                <th>Term</th>
                                                <th>Rate</th>
                                                <th>Current Rate</th>
                                                <th>Lender</th>
                                                <th>To be Paid Out</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(propertyMortgage, i) in property.propertyMortgages" :key="i">
                                                <td><small>{{ i + 1 }}</small></td>
                                                <td><small>{{ formatDecimal(propertyMortgage.balance) }}</small></td>
                                                <td><small>{{ formatDecimal(propertyMortgage.payment) }}</small></td>
                                                <td><small>{{ propertyMortgage.paymentType }}</small></td>
                                                <td><small>{{ formatDate(propertyMortgage.term) }}</small></td>
                                                <td><small>{{ formatDecimal(propertyMortgage.rate) }}%</small></td>
                                                <td><small>{{ formatDecimal(propertyMortgage.rate) }}%</small></td>
                                                <td><small>{{ propertyMortgage.lenderId }}</small></td>
                                                <td><small>{{ propertyMortgage.payout }}</small></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else mb-3>
                                </div>
                              </div>
                        </div>
                    </div>

                    <!-- Renewals -->
                    <div class="card">
                        <div class="card-header">
                            Renewals
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Interest Rate</th>
                                            <th>Fee</th>
                                            <th>New Payment</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(p, key) in renewals" :key="key">
                                            <td><small>{{ formatDate(p.renewalDate) }}</small></td>
                                            <td><small>{{ formatDecimal(p.newInterestRate) }}%</small></td>
                                            <td><small>{{ formatDecimal(p.renewalFee) }}</small></td>
                                            <td><small>{{ formatDecimal(p.newPaymentAmount) }}</small></td>
                                            <td><small>{{ formatDecimal(p.comments) }}</small></td>
                                        </tr>
                                        <tr v-if="renewals.length === 0">
                                            <td colspan="5">No renewals</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>                   


                    <!-- Payout -->
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Payout</span>
                            <button
                                type="button"
                                class="btn btn-primary"
                                v-tooltip="'Calculate Payout'"
                                @click="calculatePayout()"
                                :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut">
                                Calculate Payout
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start flex-wrap mb-3">
                                <div class="form-group custom-width-date px-1 mb-2">
                                    <label class="fw-bold text-truncate">Payout Date</label>
                                    <input type="date" class="form-control" v-model="payoutDate" :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut"/>
                                </div>
                                <div class="form-group custom-width px-1 mb-2">
                                    <label class="fw-bold text-truncate">MIP</label>
                                    <input type="number" class="form-control" v-model="payoutMIP" :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut"/>
                                </div>
                                <div class="form-group custom-width px-1 mb-2">
                                    <label class="fw-bold text-truncate">Discharge</label>
                                    <input type="number" class="form-control" v-model="payoutDischarge" :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut"/>
                                </div>
                                <div class="form-group custom-width px-1 mb-2">
                                    <label class="fw-bold text-truncate">Legal</label>
                                    <input type="number" class="form-control" v-model="payoutLegal" :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut"/>
                                </div>
                                <div class="form-group custom-width px-1 mb-2">
                                    <label class="fw-bold text-truncate">Misc</label>
                                    <input type="number" class="form-control" v-model="payoutMisc" :disabled="selectedMortgage.foreclosure || selectedMortgage.paidOut"/>
                                </div>
                            </div>
                            <template v-if="Object.keys(payout).length > 0">
                                <table class="table table-hover table-bordered">
                                    <tbody>
                                        <tr>
                                            <td>Last Balance</td>
                                            <td>{{ formatDecimal(payout.lastBalance) }}</td>
                                        </tr>
                                        <tr>
                                            <td>MIP</td>
                                            <td>{{ formatDecimal(payout.mip) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Discharge</td>
                                            <td>{{ formatDecimal(payout.discharge) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Legal</td>
                                            <td>{{ formatDecimal(payout.legal) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Misc</td>
                                            <td>{{ formatDecimal(payout.misc) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Interest Accrued</td>
                                            <td>{{ formatDecimal(payout.interestAccrued) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">*Payout Amount</td>
                                            <td class="fw-bold">{{ formatDecimal(payout.payoutAmount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small>*Estimated value</small>
                            </template>
                        </div>
                    </div>


                    <!-- PAP Bank Information -->
                    <div class="card mt-3">
                        <div class="card-header">
                            PAP Bank Information
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Institution</th>
                                        <th>Transit</th>
                                        <th>Account</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, key) in bankInformation" :key="key">
                                        <td><small>({{ p.institutionCode }}) {{ p.institutionName }}</small></td>
                                        <td><small>{{ p.transit }}</small></td>
                                        <td><small>{{ p.accountRedacted }}</small></td>
                                        <td><small>{{ p.status }}</small></td>
                                    </tr>
                                    <tr v-if="bankInformation.length === 0">
                                        <td colspan="4">No bank information</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-4">

                    <div v-if="selectedMortgage.company === 701" class="card mb-3">
                        <div class="card-body p-0">
                            <div class="d-flex flex-wrap align-items-end">
                                <div class="form-group px-2 mb-2">
                                    <label class="fw-bold">Insurance</label>                                    
                                    <select v-model="selectedMortgage.insurance" class="form-select" @change="updateInsurance(selectedMortgage.id, selectedMortgage.insurance)">
                                        <option value="N/A" key="N/A">N/A</option>
                                        <option value="No"  key="No">No</option>
                                        <option value="Yes" key="Yes">Yes</option>
                                    </select>                                   
                                </div>

                                <div class="form-group px-2 mb-2">
                                    <label class="fw-bold">Earthquake</label>                                    
                                    <select v-model="selectedMortgage.earthquake" class="form-select" @change="updateEarthquake(selectedMortgage.id, selectedMortgage.earthquake)">
                                        <option value="No"  key="No">No</option>
                                        <option value="Yes" key="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Notes</span>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-hover text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Follow-Up Date</th>
                                        <th>Mortgage</th>
                                        <th class="text-start">Note</th>
                                        <th>Assigned To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(note, key) in notes" :key="key" @mouseover="editModeOn(key)" @mouseleave="editModeOff()">
                                        <td>
                                            <small :class="{ 'text-danger': isPastDate(note.followUpDate) }">{{ formatDate(note.followUpDate) }}</small>
                                            <button
                                                type="button"
                                                class="btn btn-primary m-1"
                                                v-tooltip="'Edit Note'"
                                                @click="editNote(note)"
                                                v-if="key == editingKey">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </button>
                                        </td>

                                        <td><small>{{ note.mortgageCode }}</small></td>
                                        <td class="text-start"><small v-html="formatNote(note.noteText)"></small></td>
                                        <td><small>{{ note.delegatedTo }}</small></td>
                                    </tr>
                                </tbody>
                                <tr v-if="payments.notes === 0">
                                        <td>No Notes</td>
                                    </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>           
        </div>
    </div>
    
    <Note
        :objectId="this.$route.params.id"
        :noteData="currentNoteData"
        :refreshCount="refreshCount"
        @refresh="this.getNotes(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)"
    />

</template>

<script>
import { util } from '../mixins/util'
import Note from '../components/Note.vue'

export default {
    mixins: [util],
    components: { Note },
    emits: ['events'],
    data() {
        return {
            payoutDate: '',
            payoutMIP: '',
            payoutDischarge: '',
            payoutLegal: '',
            payoutMisc: '',
            mortgagors: [],
            mortgages: [],
            selectedMortgage: {},
            payments: [],
            upcomingPayments: [],
            renewals: [],
            bankInformation: [],
            properties: [],
            notes: [],
            investors: [],
            payout: [],
            currentNoteData: [],
            refreshCount: 0,
            editingKey: null,
            dischargeFeeArray: {
                '1': 75,
                '3': 0,
                '183': 0,
                '401': 175
            },



        }
    },
    created() {
        this.getData()
    },
    methods: {
        editModeOn(key) {
            //console.log('editModeOn',key)
            this.editingKey = key
        },
        editModeOff: function() {
            this.editingKey = null
        },
        calculatePayout() {

            if (!this.payoutDate) {
                this.alertMessage = 'Payout Date must be selected!'
                this.showAlert('error')
                return
            }

            let data = {
                mortgageId: this.selectedMortgage.investorId,
                payoutDate: this.payoutDate,
                payoutMIP: this.payoutMIP,
                payoutDischarge: this.payoutDischarge,
                payoutLegal: this.payoutLegal,
                payoutMisc: this.payoutMisc,
            }

            this.axios.get(
                '/web/application-dashboard/mortgage/payout',
                {params: data}
            )
            .then(response => {
                this.payout = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
            })          
        },
        formatNote(noteContent) {
            if (!noteContent) return ''

            return noteContent.replace(/\n/g, '<br>')
        },
        concatenatedValue(property) {
            return `${this.formatDecimalAux(property.assValue)} (L: ${this.formatDecimalAux(property.landValue)} B: ${this.formatDecimalAux(property.buildingValue)})`;
        },
        formatDecimalAux(str) {
            if (str === "" || str === undefined || str === null || isNaN(str)) {
                return "";
            } else {
                let value = parseFloat(str.toString()).toFixed(2);

                if (value == 0) {
                    return "0";
                } else if (value < 0) {
                    value = value * -1;
                    value = value.toFixed(2);
                    return (
                        "-" +
                        value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    );
                } else {
                    return value
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
            }
        },                  
        mortgageDetails: function(mortgage) {

            this.payout = [];
            this.payoutDate = '';
            this.payoutMIP = mortgage?.mip ?? '';
            this.payoutDischarge = this.dischargeFeeArray[mortgage?.company] ?? '';
            this.payoutLegal = '';
            this.payoutMisc = '';

            this.selectedMortgage = mortgage
            this.getProperties(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getPayments(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getUpcomingPayments(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getRenewals(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getBankInformation(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getNotes(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getMortgagors(this.selectedMortgage.investorId === null ? this.selectedMortgage.id : this.selectedMortgage.investorId)
            this.getInvestors(this.selectedMortgage.id)
        },

        getProperties: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/properties`)
            .then(response => {
                this.properties = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },

        getPayments: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/payments`)
            .then(response => {
                this.payments = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getUpcomingPayments: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/upcoming-payments`)
            .then(response => {
                this.upcomingPayments = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getRenewals: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/renewals`)
            .then(response => {
                this.renewals = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },

        getBankInformation: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/bank-info`)
            .then(response => {
                this.bankInformation = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getNotes: function() {
            this.axios.get(`/web/application-dashboard/${this.$route.params.id}/notes`)
            .then(response => {
                this.notes = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getMortgagors: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/mortgagors`)
            .then(response => {
                this.mortgagors = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        getInvestors: function(mortgageId) {
            this.axios.get(`/web/application-dashboard/mortgage/${mortgageId}/investors`)
            .then(response => {
                this.investors = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },
        isPastDate(date) {
            const today = new Date();
            const followUpDate = new Date(date);
            return followUpDate < today;
        },        
        getData: function() {
            this.showPreLoader()

            this.axios.get(`/web/application-dashboard/${this.$route.params.id}/mortgages`)
            .then(response => {
                this.mortgages = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        editNote : function(note) {            
            this.currentNoteData = { ...note };
            this.refreshCount++
            this.showModal('Notes')
        },
        addNotes: function() {
            this.currentNoteData = {
                noteId: 0,
                followUpDate: '',
                mortgageCode: '',
                noteText: '',
                delegatedTo: '',
                followerUp: '',
                followedUp: '',
                categoryId: '',
                turnDownId: ''
            };
            this.refreshCount++
            this.showModal('Notes')
        },
        updateInsurance: function(mortgageId, insurance) {
            this.axios({
                method: 'post',
                url: '/web/application-dashboard/mortgage/insurance' ,
                data: {
                    mortgageId: mortgageId,
                    insurance: insurance
                }
            })
            .then((response) => {
                if (response.data.status === 'success') {
                    this.alertMessage = 'Insurance updated!'
                    this.showAlert('success')
                } else {
                    this.alertMessage = response.data.data.message
                    this.showAlert(response.data.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert('error')
            })
           .finally(() => {
                this.hidePreLoader();
            });
        },
        updateEarthquake: function(mortgageId, earthquake) {
            this.axios({
                method: 'post',
                url: '/web/application-dashboard/mortgage/earthquake' ,
                data: {
                    mortgageId: mortgageId,
                    earthquake: earthquake
                }
            })
            .then((response) => {
                if (response.data.status === 'success') {
                    this.alertMessage = 'Earthquake updated!'
                    this.showAlert('success')
                } else {
                    this.alertMessage = response.data.data.message
                    this.showAlert(response.data.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert('error')
            })
           .finally(() => {
                this.hidePreLoader();
            });
        }
    }
}
</script>

<style scoped>
.table > thead > tr:first-child > th {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.table > tbody > tr:last-child > td {
    border: 0px;
    border: 0px;
}

.table > tbody > tr:last-child > td:first-child {
    border-bottom-left-radius: 8px;
}

.table > tbody > tr:last-child > td:last-child {
    border-bottom-right-radius: 8px;
}

.table > tbody > tr > td {
    padding-top: 0px;
    padding-bottom: 0px;
}
.text-start {
    text-align: left !important;
}
.custom-width {
    flex: 1 1 150px;
    max-width: 100px;
    min-width: 100px;
}
.custom-width-date {
    flex: 1 1 150px;
    max-width: 130px;
    min-width: 130px;
}
.text-danger {
  color: red;
}
</style>