<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1">
        <div class="modal-dialog" style="max-width: 80vw; width: 100%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ renewalData.mortgageCode }} {{ renewalData.lName }}</h5>
                    <button type="button" class="btn-close" @click="closeModel(modalId)" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-danger mb-3 p-2" v-if="renewalData.isMortgageProblem">
                        <div>Please consult an administrator to renew this problematic file due to:</div>
                        
                        <ul class="mb-0">
                            <li v-for="(problems, key) in renewalData.mortgageProblem" :key="key">{{ problems }}</li>
                        </ul>
                    </div>

                    <div class="d-flex flex-row">
                        <div class="pe-3 border-end w-25">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="p-0" colspan="2"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>Mortgage Code</td>
                                        <td class="text-end">
                                            <a class="text-danger cursor-pointer text-decoration-none" @click="investorCardLink()">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>{{ localRenewalData.mortgageCode }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Application #</td>
                                        <td class="text-end">
                                            {{ localRenewalData.applicationId }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Last Name</td>
                                        <td class="text-end">
                                            {{ localRenewalData.lastName }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>City</td>
                                        <td class="text-end">
                                            {{ localRenewalData.city }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Province</td>
                                        <td class="text-end">
                                            {{ localRenewalData.province }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Property Type</td>
                                        <td class="text-end">
                                            {{ localRenewalData.propertyType }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>House Style</td>
                                        <td class="text-end">
                                            {{ localRenewalData.houseStyle }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Position</td>
                                        <td class="text-end">
                                            {{ localRenewalData.pos }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>LTV</td>
                                        <td class="text-end">
                                            {{ Math.round(localRenewalData.ltv*100)/100 }}%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Term Due Date</td>
                                        <td class="text-end">
                                            {{ formatPhpDate(localRenewalData.dueDate) }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Prior Mortgage</td>
                                        <td class="text-end">
                                            ${{ formatDecimal(localRenewalData.priorMtge) }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Coll Status</td>
                                        <td class="text-end">
                                            {{ localRenewalData.collStatus }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Original Date</td>
                                        <td class="text-end">
                                            {{ formatPhpDate(localRenewalData.origDate) }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Original Balance</td>
                                        <td class="text-end">
                                            ${{ formatDecimal(localRenewalData.origBalance) }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Current Balance</td>
                                        <td class="text-end">
                                            ${{ formatDecimal(localRenewalData.currentBalance) }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Orig Rate</td>
                                        <td class="text-end">
                                            {{ localRenewalData.org }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td># of NSF</td>
                                        <td class="text-end">
                                            {{ localRenewalData.numberOfNSF }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Other Mortgagee</td>
                                        <td class="text-end">
                                            {{ localRenewalData.otherMortgage }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Flag</td>
                                        <td class="text-end">
                                            {{ localRenewalData.flag }} 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <span>Comments</span>
                                            <br>
                                            <textarea class="form-control" rows="3" v-model="localRenewalData.notes"></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="ps-3 pe-3 border-end w-50">
                            <div class="mb-3 fs-5 fw-bold">Renewal Parameters</div>

                            <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                <div class="mb-3 w-50">
                                    <label class="label-header">Renewal Date</label>
                                    <input v-model="renewalData.renewalDate" type="date" class="form-control" disabled>
                                </div>
                                
                                <div class="mb-3 w-50">
                                    <label class="label-header">Next Payment Date</label>
                                    <input v-model="renewalData.nextPaymentDate" type="date" class="form-control" disabled> 
                                </div>
                            </div>

                            <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                <div class="mb-3 w-50">
                                    <label class="label-header">Next Term Due Date</label>
                                    <input v-model="renewalData.nextTermDueDate" type="date" class="form-control" @change="update_term()" disabled>
                                </div>
                                
                                <div class="mb-3 w-50">
                                    <label class="label-header">Original Amortization</label>
                                    <input v-model="renewalData.amortization" type="number" class="form-control" disabled>
                                </div>
                            </div>

                            <div class="label-header">Contingent on these payments clearing the account</div>

                            <div class="mb-3 d-flex flex-fill flex-row align-items-center justify-content-start gap-3" v-if="renewalData.contingentTable && (renewalData.contingentTable.length > 0)">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Payment</th>
                                            <th class="text-center">Interest</th>
                                            <th class="text-center">Principal</th>
                                            <th class="text-center">OSB</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr v-for="(contingentData, key) in renewalData.contingentTable" :key="key">
                                            <td class="text-end">{{ contingentData.num }}</td>
                                            <td class="text-end">{{ (contingentData.curr_date) }}</td>
                                            <td class="text-end">${{ formatDecimal(contingentData.pmt) }}</td>
                                            <td class="text-end">${{ formatDecimal(contingentData.interest) }}</td>
                                            <td class="text-end">${{ formatDecimal(contingentData.princ_amt) }}</td>
                                            <td class="text-end">${{ formatDecimal(contingentData.osb) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex flex-row align-items-start align-content-start gap-2 mb-3">
                                <div class="flex-grow-1">
                                    <div class="label-header">Interest Rate</div>         
                                    <div class="d-flex flex-row gap-2">
                                        <CurrencyInput v-model="localRenewalData.currentInterestRate" :isDisabled="true"/>
                                        <a @click="copyRenewal('interest_rate')" href="javascript:void(0)" v-tooltip="{ content: 'Copy Interest Rate', html: false }">
                                            <i class="bi bi-copy"></i>
                                        </a>
                                    </div> 
                                </div>   

                                <div class="flex-grow-1">
                                    <div class="label-header">Monthly Payment</div>         
                                    <div class="d-flex flex-row gap-2">
                                        <CurrencyInput v-model="localRenewalData.currentMonthlyPayment" :isDisabled="true"/>
                                        <a @click="copyRenewal('monthly_payment')" href="javascript:void(0)" v-tooltip="{ content: 'Copy Monthly Payment', html: false }">
                                            <i class="bi bi-copy"></i>
                                        </a>
                                    </div>  
                                </div>  

                                <div class="flex-grow-1">
                                    <div class="label-header">Suggested New Monthly Payment</div>         
                                    <div class="d-flex flex-row gap-2">
                                        <CurrencyInput v-model="renewalData.suggestedNewPayment" :isDisabled="true"/>
                                        <a @click="copyRenewal('suggested_monthly_payment')" href="javascript:void(0)" v-tooltip="{ content: 'Copy Suggested Monthly Payment', html: false }">
                                            <i class="bi bi-copy"></i>
                                        </a>
                                    </div>  
                                </div>  
                            </div>

                            <div v-if="renewalData.isABLoan">
                                <div class="mb-3">
                                    <table class="table" style="border:transparent;">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="text-center fw-normal">Master</th>
                                                <th class="text-center fw-normal">A Piece</th>
                                                <th class="text-center fw-normal">B Piece</th>
                                                <th class="text-center fw-normal" v-if="renewalData.cInvCardCount >=3">C Piece</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td class="text-start">
                                                    New Interest Rate
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newInterestRate" :isPercentage="true" @change="updateRenewalData(); newInterestChange(); calculatePendingRenewal();" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newInterestRateAp" :isPercentage="true" @change="updateRenewalData(); newInterestAPChange(); calculatePendingRenewal();" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newInterestRateBp" :isPercentage="true" :isDisabled="renewalData.cInvCardCount === 2" @change="updateRenewalData(); newInterestBPChange(); calculatePendingRenewal();" />
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >=3">
                                                    <CurrencyInput v-model="localRenewalData.newInterestRateCp" :isPercentage="true" :isDisabled="true" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start">New Monthly Payment</td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newMonthlyPayment" @change="calculatePendingRenewal()" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newMonthlyPaymentAp"/>
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.newMonthlyPaymentBp" :isDisabled="renewalData.cInvCardCount === 2" />
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >=3">
                                                    <CurrencyInput v-model="localRenewalData.newMonthlyPaymentCp" :isDisabled="true" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start">OSB at Renewal</td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.osbAtRenewal" :isDisabled="true" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.osbAtRenewalAP" :isDisabled="true" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.osbAtRenewalBP" :isDisabled="true" />
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >=3">
                                                    <CurrencyInput v-model="renewalData.osbAtRenewalCP" :isDisabled="true" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start">Renewal Fee {{ renewalData.brokerGroup ? `(Group: ${renewalData.brokerGroup})` : "" }}</td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.renewalFee" @change="updateRenewalFee()" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.renewalFeeAp" @change="calculatePendingRenewal()" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="localRenewalData.renewalFeeBp" @change="calculatePendingRenewal()"/>
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >= 3">
                                                    <CurrencyInput v-model="localRenewalData.renewalFeeCp" @change="calculatePendingRenewal()"/>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <label class="label-header">Renewal Fee to be Paid Over</label>
                                    <select class="form-select" v-model="localRenewalData.renewalFeeToBePaidOver" @change="updateRenewalData(); newRenewalFeeToPaidOver(); calculatePendingRenewal();">
                                        <option value="term">Term</option>
                                        <option value="lifetime">Lifetime</option>
                                        <option value="upfront">Upfront</option>
                                    </select>
                                </div>
                            </div>

                            <div v-else>
                                <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">New Interest Rate</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            v-model="localRenewalData.newInterestRate"
                                            @change="updateRenewalData(); newInterestChange(); calculatePendingRenewal();"
                                        />
                                    </div>
                                    
                                    <div class="mb-3 w-50">
                                        <label class="label-header">New Monthly Payment</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            v-model="localRenewalData.newMonthlyPayment"
                                            @change="calculatePendingRenewal();"
                                        /> 
                                    </div>
                                </div>

                                <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">OSB at Renewal</label>
                                        <CurrencyInput v-model="renewalData.osbAtRenewal" :isDisabled="true" />
                                    </div>

                                    <div class="mb-3 w-50">
                                        <label class="label-header">Payment Variance</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :value="paymentVariance"
                                            disabled
                                        />  
                                    </div>
                                </div>

                                <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">Renewal Fee {{ renewalData.brokerGroup ? `(Group: ${renewalData.brokerGroup})` : "" }}</label>
                                        <CurrencyInput v-model="localRenewalData.renewalFee" @change="updateRenewalFee();" />
                                    </div>

                                    <div class="mb-3 w-50">
                                        <label class="label-header">Renewal Fee to be Paid Over</label>
                                        <select class="form-select" v-model="localRenewalData.renewalFeeToBePaidOver" @change="updateRenewalData(); newRenewalFeeToPaidOver(); calculatePendingRenewal();">
                                            <option value="term">Term</option>
                                            <option value="lifetime">Lifetime</option>
                                            <option value="upfront">Upfront</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3" v-if = "renewalData.mortgageMarginable">
                                <label class="label-header">Property Valuation Fee</label>
                                <div class="d-flex flex-row justify-content-between align-items-center align-content-center gap-2">
                                    <select class="form-select w-25" v-model="renewalData.propertyValuation" @change="updateRenewalData(); updatePVF(); calculatePendingRenewal();" disabled>
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                    <div class="form-select w-75">
                                        $<CurrencyInput
                                            v-model="renewalData.propertyValuationFee"
                                            :isDisabled="renewalData.propertyValuation === 'no'"
                                            @change="updateRenewalData(); updatePVF(); calculatePendingRenewal();" />
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="label-header">Additional Review</label>
                                <div class="d-flex flex-row justify-content-between align-items-center align-content-center gap-2">
                                    <select class="form-select w-25" v-model="localRenewalData.isAdditionalReview">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                    <div class="w-75 d-flex flex-row justify-content-between align-items-center align-content-center">
                                        <select v-model="localRenewalData.additionalReviewCategory" class="form-select" :disabled="localRenewalData.isAdditionalReview === 'no'">
                                            <option v-for="(category, key) in categories" :key="key" :value="category.id">{{ category.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ps-3 flex-grow-1 w-25">
                            <div class="mb-3 fs-5 fw-bold">Calculated Parameters</div>

                            <div class="mb-3" v-if="renewalData.isABLoan">
                                <table class="table" style="border:transparent;">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th class="text-center fw-normal">Master</th>
                                            <th class="text-center fw-normal">A Piece</th>
                                            <th class="text-center fw-normal">B Piece</th>
                                            <th class="text-center fw-normal" v-if="renewalData.cInvCardCount >=3">C Piece</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>OSB + Renewal Fee {{ renewalData.mortgageMarginable === 'yes' ? ' + PVF' : '' }}</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbRenewalFeeMaster" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbRenewalFeeAP" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbRenewalFeeBP" :isDisabled="true" />
                                            </td>
                                            <td v-if="renewalData.cInvCardCount >=3">
                                                <CurrencyInput v-model="renewalData.osbRenewalFeeCP" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>No. of Month In Term</td>
                                            <td colspan="4">
                                                <input v-model="renewalData.numberOfMonthInTerm" class="form-control" disabled>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>OSB at Next Term End</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbAtNextTermEndMaster" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbAtNextTermEndAP" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.osbAtNextTermEndBP" :isDisabled="true" />
                                            </td>
                                            <td v-if="renewalData.cInvCardCount >= 3">
                                                <CurrencyInput v-model="renewalData.osbAtNextTermEndCP" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Annual Effective Rate</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.annualEffectiveRateMaster" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.annualEffectiveRateAP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.annualEffectiveRateBP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td v-if="renewalData.cInvCardCount >=3">
                                                <CurrencyInput v-model="renewalData.annualEffectiveRateCP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Semi-Annual Equivalent Interest Rate</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.semiAnnualEquivalentIntRateMaster" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.semiAnnualEquivalentIntRateAP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.semiAnnualEquivalentIntRateBP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                            <td v-if="renewalData.cInvCardCount >=3">
                                                <CurrencyInput v-model="renewalData.semiAnnualEquivalentIntRateCP" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else>
                                <div class="d-flex flex-row align-items-end gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">OSB + Renewal Fee</label>
                                        <CurrencyInput v-model="renewalData.osbRenewalFeeMaster" :isDisabled="true" />
                                    </div>

                                    <div class="mb-3 w-50">
                                        <label class="label-header">No. of Month In Term</label>
                                        <CurrencyInput v-model="renewalData.numberOfMonthInTerm" :isDisabled="true" />
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="label-header">OSB at Next Term End</label>
                                    <CurrencyInput v-model="renewalData.osbAtNextTermEndMaster" :isDisabled="true" />
                                </div>

                                <div class="d-flex flex-row align-items-end gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">Annual Effective Rate</label>
                                        <CurrencyInput v-model="renewalData.annualEffectiveRateMaster" :isDisabled="true" :isPercentage="true" /> 
                                    </div>

                                    <div class="mb-3 w-50">
                                        <label class="label-header">Semi-Annual Equivalent Interest Rate</label>
                                        <CurrencyInput v-model="renewalData.semiAnnualEquivalentIntRateMaster" :isDisabled="true" :isPercentage="true" />
                                    </div>
                                </div>
                            </div>
                            
                            <hr>

                            <div>
                                <table class="table" style="border:transparent;">
                                    <thead>
                                        <tr>
                                            <th colspan="4"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3">OSB at Renewal</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.aprOsbAtRenewal" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">(a) Renewal Fee</td>
                                            <td>
                                                <div class="d-flex flex-row align-items-center justify-content-start">
                                                    $<CurrencyInput v-model="renewalData.aprRenewalFee" :isDisabled="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="renewalData.mortgageMarginable">
                                            <td colspan="3">(a) Property Valuation Fee</td>
                                            <td>
                                                <div class="d-flex flex-row align-items-center justify-content-start">
                                                    $<CurrencyInput v-model="renewalData.propertyValuationFee2" :isDisabled="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4">(b) Total Installment</td>
                                        </tr>
                                        <tr>
                                            <td class="text-end">(New Term/Payment)</td>
                                            <td>
                                                <input v-model="renewalData.aprNewTermLength" type="number" class="form-control" disabled>
                                            </td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.aprNewMonthlyPayment" :isDisabled="true" />
                                            </td>
                                            <td>
                                                <div class="d-flex flex-row align-items-center justify-content-start">
                                                    $<CurrencyInput v-model="renewalData.aprNewTotalPayment" :isDisabled="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">(c) Balance at Next Term End</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.aprBalanceAtNextTermEnd" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">(d) Total Payments (b + c)</td>
                                            <td>
                                                <div class="d-flex flex-row align-items-center justify-content-start">
                                                    $<CurrencyInput v-model="renewalData.aprTotalPayments" :isDisabled="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">(e) Total Cost of Credit (d - OSB at Renewal)</td>
                                            <td>
                                                <div class="d-flex flex-row align-items-center justify-content-start">
                                                    $<CurrencyInput v-model="renewalData.totalCostOfCredit" :isDisabled="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">Average OSB</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.averageOsBal" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">APR</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.apr" :isPercentage="true" :isDisabled="true" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">Remaining Amortization</td>
                                            <td>
                                                <CurrencyInput v-model="renewalData.remainingAmortization" :isDisabled="true" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger mt-1 mb-3 p-2" v-if="errorMsg || intOnlyMsg">
                        <p class="mb-0">{{ errorMsg }}</p>
                        <p class="mb-0">{{ intOnlyMsg }}</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" @click="nonRenewal()" v-if="localRenewalData.sourceTable == 'pending-renewals'">
                        <i class="bi-x-lg me-1"></i>Non Renewal
                    </button>

                    <button type="button" class="btn btn-outline-success" @click="saveRenewal()">
                        <i class="bi-save me-1"></i>Save & Review
                    </button>

                    <button type="button" class="btn btn-success" @click="insertRenewal()">
                        <i class="bi-check2-circle me-1"></i>Approve
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import { renewal } from '../../mixins/renewal'
import CurrencyInput from '../CurrencyInput.vue'

export default {
    mixins: [util, renewal],
    components: { 
        CurrencyInput
    },
    props: { 
        selectedRenewalData: {
            type: Object,
            default: () => ({})
        }
    },
    emits: [ "events", "updateRenewals" ],
    watch: {
    },
    data () {
        return {
            modalId: 'RenewalTrackerCalculator',
            renewalData: {}, // From Database
            localRenewalData: {}, // From User Input
            categories: [],
            errorMsg: null,
            intOnlyMsg: null,
        }
    },
    mounted() {
        this.$nextTick(() => {
            const modalEl = document.getElementById(this.modalId);
            if (modalEl) {
                modalEl.addEventListener('shown.coreui.modal', () => {
                    this.localRenewalData = { ...this.selectedRenewalData };
                    if(this.localRenewalData.additionalReviewCategory != null) {
                        this.localRenewalData.isAdditionalReview = 'yes';
                    } else {
                        this.localRenewalData.isAdditionalReview = 'no';
                    }
                    this.getRenewalData();
                    this.getAdditionalReviewCategories();
                });
            }
        });
    },
    watch: {
        'localRenewalData.isAdditionalReview'(newVal) {
            if (newVal === 'no') {
                this.localRenewalData.additionalReviewCategory = null
            }
        }
    },
    computed: {
        paymentVariance: function() {
            if(this.localRenewalData.newMonthlyPayment != null && this.localRenewalData.currentMonthlyPayment != null) {
                if((this.localRenewalData.currentMonthlyPayment - this.localRenewalData.newMonthlyPayment) < 0) {
                    return '-$' + Math.abs(this.localRenewalData.currentMonthlyPayment - this.localRenewalData.newMonthlyPayment).toFixed(2);
                }
                return '$' + (this.localRenewalData.currentMonthlyPayment - this.localRenewalData.newMonthlyPayment).toFixed(2);
            }
            return 'N/A';
        }
    },
    methods: {
        getRenewalData: function() {
            this.showPreLoader();

            let data = {
                mortgageId: this.localRenewalData.mortgageId,
                renewalId: null
            };

            this.axios
                .get("/web/renewals/calculate", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.renewalData = response.data.data;
                        this.localRenewalData.renewalFee = this.localRenewalData.renewalFee ?? this.renewalData.renewalFee;

                        if(this.renewalData.isABLoan) {
                            this.localRenewalData.renewalFeeAp = this.localRenewalData.renewalFeeAp ?? this.renewalData.renewalFeeAP;
                            this.localRenewalData.renewalFeeBp = this.localRenewalData.renewalFeeBp ?? this.renewalData.renewalFeeBP;

                            if(this.renewalData.cInvCardCount >=3) {
                                this.localRenewalData.renewalFeeCp = this.localRenewalData.renewalFeeCp ?? this.renewalData.renewalFeeCP;
                            }
                        }

                        this.localRenewalData.renewalFeeToBePaidOver = "term";

                        this.calculatePendingRenewal();

                    } else {
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
                });
        },
        getAdditionalReviewCategories: function() {

            let data = {
                categoryId: 59
            };

            this.axios
                .get("/web/renewals/categories", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.categories = response.data.data;
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error");
                })
                .finally(() => {
                });
        },
        updateRenewalData: function() {

            this.renewalData.newInterestRate = this.localRenewalData.newInterestRate
            this.renewalData.newMonthlyPmtMaster = this.localRenewalData.newMonthlyPayment
            this.renewalData.renewalFee = this.localRenewalData.renewalFee
            this.renewalData.renewalFeeToBePaidOver = this.localRenewalData.renewalFeeToBePaidOver

            if(this.renewalData.isABLoan) {
                this.renewalData.apInterestRate = this.localRenewalData.newInterestRateAp
                this.renewalData.bpInterestRate = this.localRenewalData.newInterestRateBp
                this.renewalData.newMonthlyPmtAP = this.localRenewalData.newMonthlyPaymentAp
                this.renewalData.newMonthlyPmtBP = this.localRenewalData.newMonthlyPaymentBp
                this.renewalData.renewalFeeAP = this.localRenewalData.renewalFeeAp
                this.renewalData.renewalFeeBP = this.localRenewalData.renewalFeeBp

                if(this.renewalData.cInvCardCount >=3 ) {
                    this.renewalData.cpInterestRate = this.localRenewalData.newInterestRateCp
                    this.renewalData.newMonthlyPmtCP = this.localRenewalData.newMonthlyPaymentCp
                    this.renewalData.renewalFeeCP = this.localRenewalData.renewalFeeCp
                }
            }
        },
        updateLocalRenewalData: function() {
            // this.localRenewalData.newInterestRate = this.renewalData.newInterestRate
            // this.localRenewalData.newMonthlyPayment = this.renewalData.newMonthlyPmtMaster
            // this.localRenewalData.renewalFee = this.renewalData.renewalFee
            // this.localRenewalData.renewalFeeToBePaidOver = this.renewalData.renewalFeeToBePaidOver

            if(this.renewalData.isABLoan) {
                this.localRenewalData.newInterestRateAp = this.renewalData.apInterestRate
                this.localRenewalData.newInterestRateBp = this.renewalData.bpInterestRate
                this.localRenewalData.newMonthlyPaymentAp = this.renewalData.newMonthlyPmtAP
                this.localRenewalData.newMonthlyPaymentBp = this.renewalData.newMonthlyPmtBP
                this.localRenewalData.renewalFeeAp = this.renewalData.renewalFeeAP
                this.localRenewalData.renewalFeeBp = this.renewalData.renewalFeeBP

                if(this.renewalData.cInvCardCount >=3 ) {
                    this.localRenewalData.newInterestRateCp = this.renewalData.cpInterestRate
                    this.localRenewalData.newMonthlyPaymentCp = this.renewalData.newMonthlyPmtCP
                    this.localRenewalData.renewalFeeCp = this.renewalData.renewalFeeCP
                }
            }
        },
        nonRenewal: function() {
            if(!this.localRenewalData.notes) {
                this.alertMessage = "Please enter a note"
                this.showAlert("error")
                return
            }
            
            let data = {
                renewalApproval: this.localRenewalData
            }

            this.showPreLoader()

            this.axios
                .post('web/renewals/non-renewal', data)
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.$emit('updateRenewals', this.localRenewalData)
                        this.localRenewalData.newInterestRate = null
                        this.localRenewalData.newMonthlyPayment = null
                        this.localRenewalData.notes = null
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
                    this.hideModal(this.modalId)
                })            
        },
        insertRenewal: function() {
            if(!this.localRenewalData.newMonthlyPayment) {
                this.alertMessage = "Monthly payment not entered"
                this.showAlert('error')
                return;
            } else if(!this.localRenewalData.renewalFee) {
                this.alertMessage = "Renewal Fee not entered"
                this.showAlert('error')
                return;
            } else if(!this.localRenewalData.renewalFeeToBePaidOver) {
                this.alertMessage = "Renewal fee to be paid over not entered"
                this.showAlert('error')
                return;
            }

            this.calculatePendingRenewal();

            var remain_amortization_error = this.errorMsg;
            var interest_error = this.errorMsg;

            if (remain_amortization_error && remain_amortization_error != '' && interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" 
                    + remain_amortization_error
                    + "\n"
                    + interest_error
                );
                this.showAlert('error')
                return;
            } else if (interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" + interest_error);
                this.showAlert('error')
                return;
            }else if (remain_amortization_error && remain_amortization_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" + remain_amortization_error);
                this.showAlert('error')
                return;
            }
            
            let data = {
                renewalApproval: this.localRenewalData,
                mortgageRenewal: this.renewalData
            }

            this.showPreLoader()

            this.axios
                .post('web/renewals',data)
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.$emit('updateRenewals', this.localRenewalData)
                        this.localRenewalData.newInterestRate = null
                        this.localRenewalData.newMonthlyPayment = null
                        this.localRenewalData.notes = null
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
                    this.hideModal(this.modalId)
                })
        },
        calculatePendingRenewal: function() {
            // Update mortgageRenewalTable with new values inputed
            this.updateRenewalData();

            // Calculate Renewal
            this.calculateRenewal();

            // Update localRenewalData with calculated values
            this.updateLocalRenewalData();

            var remain_amortization_error = this.errorMsg;
            var interest_error = this.errorMsg;

            if (remain_amortization_error && remain_amortization_error != '' && interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" 
                    + remain_amortization_error
                    + "\n"
                    + interest_error
                );
                this.showAlert('error')
                return;
            } else if (interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" + interest_error);
                this.showAlert('error')
                return;
            }else if (remain_amortization_error && remain_amortization_error != '') {
                this.alertMessage = ("Please address the error message before approving this renewal: \n" + remain_amortization_error);
                this.showAlert('error')
                return;
            }
        },
        updateRenewalFee: function() {
            this.updateRenewalData(); 
            this.newRenewalFee(); 
            this.calculatePendingRenewal();
        },
        saveRenewal: function() {

            if(this.localRenewalData.isAdditionalReview == 'yes' && !this.localRenewalData.additionalReviewCategory) {
                this.alertMessage = "Please select an additional review category"
                this.showAlert("error")
                return
            }

            if(!this.localRenewalData.notes && this.localRenewalData.isAdditionalReview == 'yes') {
                this.alertMessage = "Please enter a note"
                this.showAlert("error")
                return
            }

            let data = {
                renewalApproval: this.localRenewalData
            }

            this.showPreLoader()

            this.axios
                .post('/web/renewals/pending', data)
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.$emit('updateRenewals', this.localRenewalData, true)
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
                    this.hideModal(this.modalId)
                })
        },
        copyRenewal: function(field) {
            if(field == "interest_rate") {
                this.localRenewalData.newInterestRate = this.localRenewalData.currentInterestRate
            }

            if(field == "monthly_payment") {
                this.localRenewalData.newMonthlyPayment = this.localRenewalData.currentMonthlyPayment
            }

            if(field =="suggested_monthly_payment") {
                this.localRenewalData.newMonthlyPayment = this.renewalData.suggestedNewPayment.toFixed(2)
            }

            this.updateRenewalData();
            if(field == "interest_rate") {
                this.newInterestChange();
            }
            this.calculatePendingRenewal();
        },
        investorCardLink: function() {
            window.open('https://tacl-dev-2.amurfinancial.group/TACL/TACL_live/index.php?mortgageId=' + this.localRenewalData.mortgageId + '&userId=' + this.localRenewalData.userId, '_blank', 'noopener,noreferrer');
        },
        closeModel(modalId) {
            this.renewalData = {}
            this.hideModal(modalId)
        }
    }
}
</script>
