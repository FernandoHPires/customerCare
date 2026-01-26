<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1">
        <div class="modal-dialog" style="max-width: 80vw; width: 100%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <a class="text-danger cursor-pointer text-decoration-none" @click="investorCardLink()">
                            {{ renewalData.mortgageCode }}
                        </a> 
                         - <span>{{ renewalData.lName }}</span>
                    </h5>
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
                        <div class="pe-3 border-end w-50">

                            <div class="mb-3 fs-5 fw-bold">Renewal Parameters</div>

                            <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                <div class="mb-3 w-50">
                                    <label class="label-header">Renewal Date</label>
                                    <input v-model="renewalData.renewalDate" type="date" class="form-control">
                                </div>
                                
                                <div class="mb-3 w-50">
                                    <label class="label-header">Next Payment Date</label>
                                    <input v-model="renewalData.nextPaymentDate" type="date" class="form-control"> 
                                </div>
                            </div>

                            <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                <div class="mb-3 w-50">
                                    <label class="label-header">Next Term Due Date</label>
                                    <input v-model="renewalData.nextTermDueDate" type="date" class="form-control" @change="update_term()">
                                </div>
                                
                                <div class="mb-3 w-50">
                                    <label class="label-header">Amortization</label>
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
                                                    <CurrencyInput v-model="renewalData.newInterestRate" :isPercentage="true" @change="newInterestChange(); calculateRenewal();" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.apInterestRate" :isPercentage="true" @change="newInterestAPChange(); calculateRenewal();" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.bpInterestRate" :isPercentage="true" :isDisabled="renewalData.cInvCardCount === 2" @change="newInterestBPChange(); calculateRenewal();" />
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >=3">
                                                    <CurrencyInput v-model="renewalData.cpInterestRate" :isPercentage="true" :isDisabled="true" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start">New Monthly Payment</td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.newMonthlyPmtMaster" @change="calculateRenewal()" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.newMonthlyPmtAP" :isDisabled="true" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.newMonthlyPmtBP" :isDisabled="true" />
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >=3">
                                                    <CurrencyInput v-model="renewalData.newMonthlyPmtCP" :isDisabled="true" />
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
                                                    <CurrencyInput v-model="renewalData.renewalFee" @change="newRenewalFee(); calculateRenewal();" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.renewalFeeAP" @change="calculateRenewal()" />
                                                </td>
                                                <td>
                                                    <CurrencyInput v-model="renewalData.renewalFeeBP" @change="calculateRenewal()"/>
                                                </td>
                                                <td v-if="renewalData.cInvCardCount >= 3">
                                                    <CurrencyInput v-model="renewalData.renewalFeeCP" @change="calculateRenewal()"/>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <label class="label-header">Renewal Fee to be Paid Over</label>
                                    <select class="form-select" v-model="renewalData.renewalFeeToBePaidOver" @change="newRenewalFeeToPaidOver(); calculateRenewal();">
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
                                        <CurrencyInput v-model="renewalData.newInterestRate" :isPercentage="true" @change="newInterestChange(); calculateRenewal();" />
                                    </div>
                                    
                                    <div class="mb-3 w-50">
                                        <label class="label-header">New Monthly Payment</label>
                                        <CurrencyInput v-model="renewalData.newMonthlyPmtMaster" @change="calculateRenewal()" />
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="label-header">OSB at Renewal</label>
                                    <CurrencyInput v-model="renewalData.osbAtRenewal" :isDisabled="true" />
                                </div>

                                <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                    <div class="mb-3 w-50">
                                        <label class="label-header">Renewal Fee {{ renewalData.brokerGroup ? `(Group: ${renewalData.brokerGroup})` : "" }}</label>
                                        <CurrencyInput v-model="renewalData.renewalFee" @change="newRenewalFee(); calculateRenewal();" />
                                    </div>

                                    <div class="mb-3 w-50">
                                        <label class="label-header">Renewal Fee to be Paid Over</label>
                                        <select class="form-select" v-model="renewalData.renewalFeeToBePaidOver" @change="newRenewalFeeToPaidOver(); calculateRenewal();">
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
                                    <select class="form-select w-25" v-model="renewalData.propertyValuation" @change="updatePVF(); calculateRenewal();">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                    <div class="w-75 d-flex flex-row justify-content-between align-items-center align-content-center">
                                        $<input class="form-control" v-model="renewalData.propertyValuationFeeDisplay" type="number" :disabled="renewalData.propertyValuation === 'no'" @change="updatePVF(); calculateRenewal();">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-row justify-content-between align-items-start align-content-start gap-2">
                                <div class="mb-3 w-50">
                                    <label class="label-header">Signed/Received by Borrower</label>
                                    <select class="form-select" v-model="renewalData.signedReceivedByBorrower" @change="calculateRenewal()">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3 w-50">
                                    <label class="label-header">Signed/Received by Investor</label>
                                    <select class="form-select" v-model="renewalData.signedReceivedByInvestor" @change="calculateRenewal()">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="label-header w-100">New Post-Dated Cheques</div>

                            <div class="mb-3 d-flex flex-fill flex-row align-items-center justify-content-start gap-3">
                                <div class="text-center flex-grow-1">
                                    <label class="label-header">Start Date</label>
                                    <input type="date" class="form-control" v-model="renewalData.newPostDatedChequesStartDate">
                                </div>

                                <div class="text-center flex-grow-1">
                                    <label class="label-header">1st Payment</label>
                                    <input type="number" class="form-control" v-model="renewalData.newPostDatedCheques1stPmt">
                                </div>

                                <div class="text-center flex-grow-1">
                                    <label class="label-header">End Date</label>
                                    <input type="date" class="form-control" v-model="renewalData.newPostDatedChequesEndDate">
                                </div>

                                <div class="text-center flex-grow-1">
                                    <label class="label-header">Reg Payment</label>
                                    <input type="number" class="form-control" v-model="renewalData.newPostDatedChequesRegPmt">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="label-header">Comments</label>
                                <textarea class="form-control" rows="3" v-model="renewalData.comments"></textarea>
                            </div>
                        </div>
                    
                        <div class="ps-3 w-50">
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
                                            <td v-if="renewalData.cInvCardCount >= 3">
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
                                            <td v-if="renewalData.cInvCardCount >= 3">
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
                                            <td v-if="renewalData.cInvCardCount >= 3">
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

                            <div class="mb-3">
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
                </div>

                <div class="alert alert-danger mb-3 p-2" v-if="errorMsg || intOnlyMsg">
                    <p>{{ errorMsg }}</p>
                    <p>{{ intOnlyMsg }}</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="updateRenewal()">
                        <i class="bi-save me-1"></i>Update
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
        selectedMortgageId: {
            type: Number,
            default: null
        },
        selectedMortgageRenewalId: {
            type: Number,
            default: null
        },
        selectedUserId: {
            type: Number,
            default: null
        }
    },
    emits: [ "events", "updateApprovedRenewals" ],
    watch: {
    },
    data () {
        return {
            modalId: 'inProgressCalculator',
            renewalData: {},
            errorMsg: null,
            intOnlyMsg: null,
        }
    },
    mounted() {
        this.$nextTick(() => {
            const modalEl = document.getElementById(this.modalId);
            if (modalEl) {
                modalEl.addEventListener('shown.coreui.modal', () => {
                    this.getRenewalData();
                });
            }
        });
    },
    methods: {
        getRenewalData: function() {
            this.showPreLoader();

            let data = {
                mortgageId: this.selectedMortgageId,
                renewalId: this.selectedMortgageRenewalId
            };

            this.axios
                .get("/web/renewals/calculate", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.renewalData = response.data.data;
                        this.calculateRenewal();
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
        updateRenewal: function() {
            var remain_amortization_error = this.errorMsg;
            var interest_error = this.errorMsg;

            if (remain_amortization_error && remain_amortization_error != '' && interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before updating this renewal: \n" 
                    + remain_amortization_error
                    + "\n"
                    + interest_error
                );
                this.showAlert('error')
                return;
            } else if (interest_error && interest_error != '') {
                this.alertMessage = ("Please address the error message before updating this renewal: \n" + interest_error);
                this.showAlert('error')
                return;
            }else if (remain_amortization_error && remain_amortization_error != '') {
                this.alertMessage = ("Please address the error message before updating this renewal: \n" + remain_amortization_error);
                this.showAlert('error')
                return;
            }

            let data = {
                mortgageRenewal: this.renewalData,
            }

            this.showPreLoader()

            this.axios.put('web/renewals', data)
                .then(response => {
                    if (this.checkApiResponse(response)) {
                        if (response.data.data === null) {
                            this.alertMessage = response.data.message
                            this.showAlert(response.data.status)
                            this.$emit('updateApprovedRenewals')
                        } else {
                            this.alertMessage = response.data.data;
                            this.showAlert('warning')
                        }
                    }
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
        investorCardLink: function() {
            window.open('https://tacl-dev-2.amurfinancial.group/TACL/TACL_live/index.php?mortgageId=' + this.selectedMortgageId + '&userId=' + this.selectedUserId, '_blank', 'noopener,noreferrer');
        },
        closeModel(modalId) {
            this.renewalData = {}
            this.hideModal(modalId)
        }
    }
}
</script>