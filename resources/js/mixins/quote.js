import { Clipboard } from "v-clipboard"

export const quote = {
    directives: {
        clipboard: Clipboard
    },
    data() {
        return {
            fees: [],
            tmpNetAmount: 0,
        }
    },
    methods: {
        typeOnChange: function () {
            if (this.specialPricing == 'NB - 55% - Urban') {
                this.calculateFeeBreakdown(this.type)
                this.calculateMonthlyPayment()
            }
        },
        netAmountOnChange: function () {
            this.tmpNetAmount = this.netAmount
            this.calculateFeeBreakdown('n')
            this.calculateMonthlyPayment()
        },
        grossAmountOnChange: function () {
            this.calculateFeeBreakdown('g')
            this.calculateMonthlyPayment()
        },
        legalFeeOnChange: function () {
            this.redistributeBreakdown()
        },
        applicationFeeOnChange: function () {
            this.redistributeBreakdown()
        },
        appraisalFeeOnChange: function () {
            this.redistributeBreakdown()
        },
        brokerageFeeOnChange: function () {
            this.redistributeBreakdown('bf')
        },
        brokerageFeePercOnChange: function () {
            this.redistributeBreakdown('bfp')
        },
        discountFeeOnChange: function () {
            this.redistributeBreakdown('df')
        },
        discountFeePercOnChange: function () {
            this.redistributeBreakdown('dfp')
        },
        otherFeeOnChange: function () {

        },
        loanTermOnChange: function () {
            if(this.loanTerm == 12) {
                this.primeRate2ndYear = 0
                this.primeRateTotal = 0
                console.log('loanTerm', this.loanTerm, this.primeRate2ndYear, this.primeRateTotal)
            }
        },
        amortizationOnChange: function () {
            this.calculateMonthlyPayment()
        },
        interestRateOnChange: function () {
            this.calculateMonthlyPayment()
        },
        compoundedOnChange: function () {
            this.calculateMonthlyPayment()
        },
        monthlyPaymentOnChange: function () {
            this.calculateAmortization()
        },
        primeRateOnChange: function (from) {
            if (from === '2ndYear') {
                this.primeRateTotal = this.toFloat(this.primeRate) + this.toFloat(this.primeRate2ndYear)
            } else {
                if (this.interestRateType == 'V') {
                    this.primeRate2ndYear = this.toFloat(this.primeRateTotal) - this.toFloat(this.primeRate)
                }
            }
        },
        mortgageTypeOnChange: function () {
            this.calculateAmortization()
            this.calculateLTV()
            this.calculateMonthlyPayment()
        },
        targetLtvOnChange: function () {
            if (this.targetLtv !== '') {
                this.type = 'g'
                this.netAmount = 0
                this.grossAmount = ((this.targetLtv / 100) * this.combinedPropertyValue) - (this.seniorMortgageBalance + this.inHouseMortgageBalance)
                this.targetLtv = ''
                this.grossAmountOnChange()
            }
        },

        getFee: function (amount, type) {
            amount = this.toFloat(amount)

            var f = {}
            this.fees.forEach(fee => {
                if (type === 'n' || type === 'dn') {
                    if (amount >= fee.initialNetAmount && amount <= fee.finalNetAmount) {
                        f = fee
                    }
                } else {
                    if (amount >= fee.initialGrossAmount && amount <= fee.finalGrossAmount) {
                        f = fee
                    }
                }
            })

            return f
        },

        calculateLTV: function () {
            this.combinedPropertyValue = 0
            this.inHouseMortgageBalance = 0
            this.seniorMortgageBalance = 0

            var duplicateInHouse = []

            this.properties.forEach(property => {
                if (property.seniorMortgages !== undefined) {
                    property.seniorMortgages.forEach(mortgage => {
                        if (mortgage.payout == 'No') {
                            this.seniorMortgageBalance += this.toFloat(mortgage.balance) * this.toFloat(mortgage.interest / 100)
                        }
                    })
                }

                if (property.inHouseMortgages !== undefined) {
                    property.inHouseMortgages.forEach(mortgage => {
                        if (duplicateInHouse.includes(mortgage.id)) return

                        if (mortgage.payout == 'No') {
                            this.inHouseMortgageBalance += this.toFloat(mortgage.balance)
                        }
                        duplicateInHouse.push(mortgage.id)
                    })
                }

                if (property.ltvSelected === 'A') {
                    this.combinedPropertyValue += this.toFloat(property.appraisedValue)
                } else if (property.ltvSelected === 'S') {
                    this.combinedPropertyValue += this.toFloat(property.assessedValue)
                } else if (property.ltvSelected === 'C') {
                    this.combinedPropertyValue += this.toFloat(property.customerValue)
                } else {
                    this.combinedPropertyValue += this.toFloat(property.estimateValue)
                }
            })

            this.ltv = this.combinedPropertyValue == 0 ? 0 :
                (this.toFloat(this.grossAmount) + this.inHouseMortgageBalance + this.seniorMortgageBalance) / this.combinedPropertyValue * 100
        },

        calculateMonthlyPayment: function () {
            var grossAmount = this.toFloat(this.grossAmount)
            var interestRate = this.toFloat(this.interestRate)
            var amortization = this.toFloat(this.amortization) * 12
            var compounded = this.toFloat(this.compounded)

            interestRate = (Math.pow((interestRate / (compounded * 100)) + 1, compounded / 2) - 1) * 200
            var j = Math.pow(1 + (interestRate / 200), 1 / 6)
            var payment = grossAmount * ((j - 1) / (1 - Math.pow(j, -amortization)))

            if (amortization == 0) payment = 0

            this.monthlyPayment = payment
        },

        calculateAmortization: function () {
            var grossAmount = this.toFloat(this.grossAmount)
            var interestRate = this.toFloat(this.interestRate)
            var monthlyPayment = this.toFloat(this.monthlyPayment)
            var compounded = this.toFloat(this.compounded)

            interestRate = (Math.pow((interestRate / (compounded * 100)) + 1, compounded / 2) - 1) * 200
            var j = Math.pow(1 + (interestRate / 200), 1 / 6)
            var amortization = Math.log(1 - ((grossAmount * (j - 1)) / monthlyPayment)) / Math.log(j) / -12

            if (isNaN(amortization)) {
                this.amortization = 0
                this.monthlyPayment = 0
            } else {
                this.amortization = this.roundAccuracy(amortization, 4)
            }

            if (this.mortgageType == 519) {
                this.amortization = 999
                this.amortizationDisabled = true
            } else {
                this.amortizationDisabled = false
            }

            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },

        checkFivePercent: async function () {
            if (this.ltv > 55) {
                if (this.specialPricing == 'NB - 55% - Urban') {
                    if (this.cityClassification == 'Urban' && this.company == 'ACL' && this.mortgageGroup == 'NB') {
                        await this.getFeesAsync()
                        alert('This pricing is only available for Alpine - NB / 55% LTV / Urban')
                        this.specialPricing = 'N/A'
                        this.calculateFeeBreakdown(this.type)
                    }
                }
            }
        },

        getFeesAsync: async function () {
            if (this.application.companyId === undefined) return

            await this.axios({
                method: 'get',
                url: '/web/quote/fees/' + this.application.companyId
            })
            .then(response => {
                this.fees = response.data.data
            })
            .catch(error => {
                this.fees = []
            })
        },

        calculateFeeBreakdown: function (from) {
            //console.log('calculateFeeBreakdown', this.type, from)

            if (this.type === 'dn') {

                var fee = this.getFee(this.toFloat(this.netAmount), this.type)
                var fivePercent = false

                if (this.specialPricing == 'NB - 55% - Urban') {

                    if (this.ltv < 55 && this.cityClassification == 'Urban' && this.company == 'ACL' && this.mortgageGroup == 'NB') {

                        if (this.netAmount * 0.05 > 2000) {
                            fee.brokeragePerc = 3
                            fee.discountPerc = 2
                        } else {
                            fivePercent = true
                        }
                    } else {
                        alert('This pricing is only available for Alpine - NB / 55% LTV / Urban')
                        this.specialPricing = 'N/A'
                        this.getFees()
                    }

                }

                this.legalFee = fee.legalFee
                this.applicationFee = fee.applicationFee
                this.appraisalFee = fee.appraisalFee
                this.brokerageFeePerc = fee.brokeragePerc
                this.discountFeePerc = fee.discountPerc

                var tmp1 = this.toFloat(this.netAmount) + this.toFloat(this.legalFee) + this.toFloat(this.applicationFee) + this.toFloat(this.appraisalFee)
                var tmp2 = 100 - this.toFloat(this.brokerageFeePerc) - this.toFloat(this.discountFeePerc)

                this.grossAmount = this.roundAccuracy(100 * Math.ceil(tmp1 / tmp2), -2)


                this.brokerageFee = this.toFloat(this.grossAmount) * (fee.brokeragePerc / 100)
                this.discountFee = this.toFloat(this.grossAmount) * (fee.discountPerc / 100)

                if (fivePercent) {
                    this.netAmount =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) - 2000

                    this.brokerageFee = 1000
                    this.discountFee = 1000
                    this.brokerageFeePerc = ((1000 / this.netAmount) * 100)
                    this.discountFeePerc = ((1000 / this.netAmount) * 100)

                } else {
                    this.netAmount =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) -
                        this.toFloat(this.brokerageFee) -
                        this.toFloat(this.discountFee)
                }
            } else if (this.type === 'dg') {
                var fee = this.fees[0]

                this.legalFee = fee.legalFee
                this.applicationFee = fee.applicationFee
                this.appraisalFee = fee.appraisalFee
                this.brokerageFeePerc = fee.brokeragePerc
                this.discountFeePerc = fee.discountPerc

                var tmp1 = 100 - this.brokerageFeePerc - this.discountFeePerc
                this.netAmount = this.toFloat(this.grossAmount) * tmp1 / 100 - this.toFloat(this.legalFee) - this.toFloat(this.applicationFee) - this.toFloat(this.appraisalFee)

                this.brokerageFee = this.roundAccuracy(this.toFloat(this.grossAmount) * this.brokerageFeePerc / 100, 2)
                this.discountFee = this.roundAccuracy(this.toFloat(this.grossAmount) * this.discountFeePerc / 100, 2)

            } else {
                if (this.type === from) {

                    if (this.type === 'n') {
                        //Net Amount
                        var fee = this.getFee(this.toFloat(this.netAmount), this.type)

                        if (this.specialPricing == 'NB - 55% - Urban') {
                            if (this.ltv < 55 && this.cityClassification == 'Urban' && this.company == 'ACL' && this.mortgageGroup == 'NB') {
                                if (this.netAmount * 0.05 > 2000) {
                                    fee.brokeragePerc = 3
                                    fee.discountPerc = 2
                                } else {
                                    fee.brokeragePerc = ((1000 / this.netAmount) * 100)
                                    fee.discountPerc = ((1000 / this.netAmount) * 100)
                                }
                            } else {
                                alert('This pricing is only available for Alpine - NB / 55% LTV / Urban')
                                this.specialPricing = 'N/A'

                            }

                        }

                        this.legalFee = fee.legalFee
                        this.applicationFee = fee.applicationFee
                        this.appraisalFee = fee.appraisalFee
                        this.brokerageFeePerc = fee.brokeragePerc
                        this.discountFeePerc = fee.discountPerc
                        this.brokerageFee = this.netAmount * (fee.brokeragePerc / 100)
                        this.discountFee = this.netAmount * (fee.discountPerc / 100)

                        this.grossAmount = parseFloat(this.netAmount) + fee.legalFee + fee.applicationFee + fee.appraisalFee + this.brokerageFee + this.discountFee

                    } else {
                        //Gross Amount
                        var fee = this.fees[0]

                        this.legalFee = fee.legalFee
                        this.applicationFee = fee.applicationFee
                        this.appraisalFee = fee.appraisalFee
                        this.brokerageFeePerc = fee.brokeragePerc
                        this.discountFeePerc = fee.discountPerc

                        this.netAmount = (this.toFloat(this.grossAmount) - this.legalFee - this.applicationFee - this.appraisalFee) * 100 / (100 + this.brokerageFeePerc + this.discountFeePerc);

                        this.brokerageFee = this.netAmount * (this.brokerageFeePerc / 100);
                        this.discountFee = this.netAmount * (this.discountFeePerc / 100);
                    }

                } else {
                    this.redistributeBreakdown()
                }
            }

            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },

        redistributeBreakdown: function (from = 'other') {
            //console.log('redistributeBreakdown', this.type, from)

            if (this.type === 'dg') {
                if (from === 'bfp' || from === 'dfp') {
                    this.brokerageFee = this.roundAccuracy(this.toFloat(this.grossAmount) * this.toFloat(this.brokerageFeePerc) / 100, 0)
                    this.discountFee = this.roundAccuracy(this.toFloat(this.grossAmount) * this.toFloat(this.discountFeePerc) / 100, 0)

                    this.netAmount = this.toFloat(this.grossAmount) - this.toFloat(this.legalFee) - this.toFloat(this.applicationFee) - this.toFloat(this.appraisalFee) - this.brokerageFee - this.discountFee;

                } else {
                    this.netAmount =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) -
                        this.toFloat(this.brokerageFee) -
                        this.toFloat(this.discountFee)


                    if (from === 'bf' || from === 'df') {
                        if (from === 'bf') {
                            var broker = this.toFloat(this.brokerageFee)

                            var discount =
                                this.toFloat(this.grossAmount) -
                                this.toFloat(this.netAmount) -
                                this.toFloat(this.legalFee) -
                                this.toFloat(this.applicationFee) -
                                this.toFloat(this.appraisalFee) - broker

                        } else if (from === 'df') {
                            var discount = this.toFloat(this.discountFee)

                            var broker =
                                this.toFloat(this.grossAmount) -
                                this.toFloat(this.netAmount) -
                                this.toFloat(this.legalFee) -
                                this.toFloat(this.applicationFee) -
                                this.toFloat(this.appraisalFee) - discount
                        }

                        this.brokerageFee = broker
                        this.discountFee = discount

                        this.brokerageFeePerc = broker / this.toFloat(this.netAmount) * 100
                        this.discountFeePerc = discount / this.toFloat(this.netAmount) * 100
                    }
                }

            } else if (this.type === 'dn') {

                if (from === 'bfp' || from === 'dfp') {
                    this.brokerageFee = this.roundAccuracy(this.toFloat(this.grossAmount) * this.toFloat(this.brokerageFeePerc) / 100)

                    var tmpVar20 = 100 - 1 * this.toFloat(this.brokerageFeePerc) - 1 * this.toFloat(this.discountFeePerc)
                    var tmpVar21 = (this.toFloat(this.netAmount) + this.toFloat(this.legalFee) + this.toFloat(this.applicationFee) + this.toFloat(this.appraisalFee)) / tmpVar20

                    this.grossAmount = this.roundAccuracy(100 * Math.ceil(tmpVar21), -2)

                    this.brokerageFee = this.toFloat(this.grossAmount) * this.toFloat(this.brokerageFeePerc) / 100;
                    this.discountFee = this.toFloat(this.grossAmount) * this.toFloat(this.discountFeePerc) / 100

                    this.netAmount = this.toFloat(this.grossAmount) - this.toFloat(this.legalFee) - this.toFloat(this.applicationFee) - this.toFloat(this.appraisalFee) - this.brokerageFee - this.discountFee;

                } else {

                    var tmp1 = (
                        this.toFloat(this.tmpNetAmount) +
                        this.toFloat(this.legalFee) +
                        this.toFloat(this.applicationFee) +
                        this.toFloat(this.appraisalFee)
                    )
                    var tmp2 = 100 - this.toFloat(this.brokerageFeePerc) - this.toFloat(this.discountFeePerc)
                    var tmp3 = tmp1 / tmp2

                    this.grossAmount = this.roundAccuracy(100 * Math.ceil(tmp3), -2)

                    this.brokerageFee = this.toFloat(this.grossAmount) * this.toFloat(this.brokerageFeePerc) / 100
                    this.discountFee = this.toFloat(this.grossAmount) * this.toFloat(this.discountFeePerc) / 100

                    this.netAmount =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) -
                        this.toFloat(this.brokerageFee) -
                        this.toFloat(this.discountFee)

                    if (from === 'bf' || from === 'df') {
                        if (from === 'bf') {
                            var broker = this.toFloat(this.brokerageFee)
                            if (from === 'bfp') broker = this.toFloat(this.netAmount) * this.toFloat(this.brokerageFeePerc) / 100

                            var discount =
                                this.toFloat(this.grossAmount) -
                                this.toFloat(this.netAmount) -
                                this.toFloat(this.legalFee) -
                                this.toFloat(this.applicationFee) -
                                this.toFloat(this.appraisalFee) - broker

                        } else if (from === 'df') {
                            var discount = this.toFloat(this.discountFee)

                            var broker =
                                this.toFloat(this.grossAmount) -
                                this.toFloat(this.netAmount) -
                                this.toFloat(this.legalFee) -
                                this.toFloat(this.applicationFee) -
                                this.toFloat(this.appraisalFee) - discount
                        }

                        this.brokerageFee = broker
                        this.discountFee = discount

                        this.brokerageFeePerc = broker / this.toFloat(this.netAmount) * 100
                        this.discountFeePerc = discount / this.toFloat(this.netAmount) * 100
                    }
                }

            } else {
                if (from === 'bf' || from === 'bfp') {
                    var broker = this.toFloat(this.brokerageFee)
                    if (from === 'bfp') broker = this.toFloat(this.netAmount) * this.toFloat(this.brokerageFeePerc) / 100

                    var discount =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.netAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) - broker

                } else if (from === 'df' || from === 'dfp') {
                    var discount = this.toFloat(this.discountFee)
                    if (from === 'dfp') discount = this.toFloat(this.netAmount) * this.toFloat(this.discountFeePerc) / 100

                    var broker =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.netAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee) - discount

                } else {
                    var diff =
                        this.toFloat(this.grossAmount) -
                        this.toFloat(this.netAmount) -
                        this.toFloat(this.legalFee) -
                        this.toFloat(this.applicationFee) -
                        this.toFloat(this.appraisalFee)

                    var broker = this.roundAccuracy(diff * 0.52, -1);
                    var discount = diff - broker;
                }

                this.brokerageFee = broker
                this.discountFee = discount

                this.brokerageFeePerc = broker / this.toFloat(this.netAmount) * 100
                this.discountFeePerc = discount / this.toFloat(this.netAmount) * 100
            }

            this.calculateAnnualEffectiveRate()
            this.calculateSemiAnnualCompEquiv()
            this.calculateApr()
        },

        calculateApr: function () {
            var firstPayment = 0
            if (
                this.firstPaymentDueDate !== '' && this.interestCommDate !== ''
            ) {
                var fpd_time = Date.parse(this.firstPaymentDueDate) / 1000
                var icd_time = Date.parse(this.interestCommDate) / 1000

                var diff = fpd_time - icd_time
                var days = (diff / 86400) - 1

                if (days > 30) {
                    var daily_pmt = this.toFloat(this.monthlyPayment) / 30
                    firstPayment = Math.round(days * daily_pmt) / 100
                } else {
                    firstPayment = this.toFloat(this.monthlyPayment);
                }
            }

            if (firstPayment < 0.01) {
                firstPayment = this.toFloat(this.monthlyPayment);
            }

            firstPayment = this.toFloat(this.monthlyPayment);

            var valA2a = (11 * this.toFloat(this.monthlyPayment)) + firstPayment

            var valA2b = (this.loanTerm - 12) * this.monthlyPayment2ndYear
            var valA2 = this.loanTerm

            //(c) Balance to be paid at maturity date
            var valA4 = this.balanceAtTermEnd

            //(d) Total Payments(a+b+c)
            var totalPayment = this.toFloat(this.otherFee) + this.balanceAtTermEnd + valA2a + valA2b

            //(e) Total cost of Credit 5(d)-3
            var valA1 = this.toFloat(this.netAmount)
            var costOfCredit = totalPayment - valA1

            var valTmp1 = 100 * costOfCredit
            var valTmp2 = valA2 / 12
            var valA5 = this.toFloat(this.grossAmount)
            var valTmp3 = (valA5 + valA4) / 2
            var valApr = valTmp1 / (valTmp2 * valTmp3)

            valTmp2 = valA2 / 12
            valTmp3 = (valA5 + valA4) / 2

            //remaining pmts
            var n = 12 * valTmp2

            var osb = valA5

            var int1 = this.toFloat(this.interestRate)
            var int2 = this.toFloat(this.primeRateTotal)
            var pmt_amt = this.toFloat(this.monthlyPayment)
            var pmt_amt2 = this.toFloat(this.monthlyPayment2ndYear)

            var totalAmt = 0
            var num = 0
            var interest = 0
            while (osb > pmt_amt && num < this.loanTerm) {
                num++;
                if (num > 12) {
                    interest = osb * int2 / 100 / 12;
                    pmt_amt = pmt_amt2;
                } else {
                    interest = osb * int1 / 100 / 12;
                }

                osb = interest - pmt_amt + osb;

                totalAmt += osb;
            }

            var averageOSB = totalAmt / n
            var valTmp4 = valTmp2 * averageOSB
            var valApr = valTmp4 == 0 ? 0 : (100 * costOfCredit) / valTmp4

            this.apr = valApr

            this.aprNetAmount = valA1
            this.aprOtherFee = this.otherFee
            this.aprInstallmentPayments = (valA2a + valA2b)
            this.aprOSBAtMaturity = this.balanceAtTermEnd
            this.aprCostOfCredit = costOfCredit
            this.aprAverageOSB = averageOSB
        },

        calculateAnnualEffectiveRate: function () {

            var na = this.toFloat(this.netAmount)
            var pv = this.toFloat(this.grossAmount)
            var i = this.toFloat(this.interestRate)
            var c = this.toFloat(this.compounded)
            var pmt = this.toFloat(this.monthlyPayment)
            var n = this.toFloat(this.loanTerm)
            var i_2nd_year = this.toFloat(this.primeRateTotal)
            var y = this.amortization * 1;

            var i1;
            var i2;
            var osb;
            var na1;
            var na2;
            var k;

            var mthPay = pmt;
            var mthIntPay = (i / 100) * pv / 12;

            i1 = (Math.pow((i / (c * 100)) + 1, c / 2) - 1) * 200;
            i1 = Math.pow((1 + (i1 / 200)), (1 / 6));
            i1 = i1 - 1;
            i2 = i1 + 0.005;

            var n_1st_year;
            var n_2nd_year;
            if (n > 12) {
                n_1st_year = 12;
                n_2nd_year = (y * n_1st_year) - 12;
            } else {
                n_1st_year = n;
            }

            var j = Math.pow(1 + i1, n_1st_year);
            var osb = (pv * j) - (pmt * ((j - 1) / i1));

            k = Math.pow(1 + i2, -n_1st_year);
            na1 = pv;
            na2 = (pmt * this.a(n_1st_year, i2)) + (osb * k);

            if (n > 12) {
                i_2nd_year = (Math.pow((i_2nd_year / (c * 100)) + 1, c / 2) - 1) * 200;

                j = Math.pow(1 + (i_2nd_year / 200), 1 / 6);
                var pmt_2nd_year = osb * ((j - 1) / (1 - Math.pow(j, -n_2nd_year)));

                if (mthPay <= mthIntPay) pmt_2nd_year = mthPay;

                i_2nd_year = Math.pow((1 + (i_2nd_year / 200)), (1 / 6));
                i_2nd_year = i_2nd_year - 1;
                j = Math.pow(1 + i_2nd_year, n - n_1st_year);
                osb = (osb * j) - (pmt_2nd_year * ((j - 1) / i_2nd_year));

                pmt = (pmt + pmt_2nd_year) / 2;
            }

            var count = 0;

            while (na != this.roundAccuracy(na2, 10)) {

                let diff = na2 - na1;

                // Validation to avoid division by zero
                if (Math.abs(diff) < 1e-10) { //1e-10  =  1 × 10⁻¹⁰  =  0.0000000001
                  i = i2;
                  break;
                }

                i = (((na - na1) * (i2 - i1)) / (na2 - na1)) + i1;

                i1 = i2;
                i2 = i;
                na1 = na2;

                k = Math.pow(1 + i2, -n);
                na2 = (pmt * this.a(n, i2)) + (osb * k);

                if (count++ > 10000) {
                    break;
                }
            }

            /*if(Number(this.mortgageType) == 519) { //Interest Only
                this.balanceAtTermEnd = this.toFloat(this.grossAmount)
            } else {
                this.balanceAtTermEnd = osb
            }*/

            this.balanceAtTermEnd = osb

            if (isNaN(i)) {
                this.aer = '---'
            } else {
                this.aer = (Math.pow(1 + i, 12) - 1) * 100
            }

            /*console.log(
                'AER:', this.aer,
                'Net Amount:', this.netAmount, 'Gross Amount:', this.grossAmount,
                'Interest Rate:', this.interestRate,
                'Compounded:', this.compounded, 'Monthly Payment:', this.monthlyPayment,
                'Loan Term:', this.loanTerm,
                '2nd Year Interest Rate:', this.primeRateTotal, 'Amortization:', this.amortization * 1);*/

            this.monthlyPayment2ndYear = pmt_2nd_year == undefined ? '' : pmt_2nd_year

            if(this.primeRateTotal == this.interestRate && this.monthlyPayment != this.monthlyPayment2ndYear) {
                this.monthlyPayment2ndYear = this.monthlyPayment
            }
        },

        calculateSemiAnnualCompEquiv: function () {
            var interestRate = this.toFloat(this.interestRate)
            var compounded = this.toFloat(this.compounded)

            this.semiAnnualCompEquiv = (Math.pow((interestRate / (compounded * 100)) + 1, compounded / 2) - 1) * 200
        },

        a: function (n, i) {
            var k = Math.pow(1 + i, (n * -1))
            return (1 - k) / i
        },

        toFloat: function (val) {
            if (val === '' || val === undefined) return 0

            return parseFloat(val.toString().replace(/,/g, ''))
        },

        roundAccuracy: function (num, acc) {
            var factor = Math.pow(10, acc)
            return Math.round(num * factor) / factor
        },

        getFees: function () {
            if (this.application.companyId === undefined) return

            this.axios({
                method: 'get',
                url: '/web/quote/fees/' + this.application.companyId
            })
                .then(response => {
                    this.fees = response.data.data
                })
                .catch(error => {
                    this.fees = []
                })
        },

        getPrimeRate: function () {
            this.axios({
                method: 'get',
                url: '/web/quote/prime-rate'
            })
                .then(response => {
                    this.primeRate = response.data.data
                })
                .catch(error => {
                    this.fees = []
                })
        },

        getPricebook: function () {

            let data = {
                ltv: this.ltv,
            }

            this.axios.get(
                '/web/pricebook/' + this.application.id,
                { params: data }
            )
                .then(response => {
                    if (response.data.status === 'success') {
                        this.pricebook = response.data.data
                        this.pricebookError = null
                    } else {
                        this.pricebook = null
                        this.pricebookError = response.data.message
                    }
                })
                .catch(error => {
                    this.alertMessage = error
                    this.showAlert('error')
                })
        },

        getProperties: function () {

            if (this.application.id === undefined) return

            this.axios.get(
                '/web/contact-center/' + this.application.id + '/properties'
            )
                .then(response => {
                    var p = response.data.data.properties

                    this.allProperties = p

                    p = p.filter((property) => property.partOfSecurity == 'Yes')

                    p.forEach(property => {
                        property.ltvSelected = 'A'
                        property.appraisedValue = property.appraisedValue * (property.alpineInterest / 100)
                        property.assessedValue = property.assessedValue * (property.alpineInterest / 100)
                        property.customerValue = property.customerValue * (property.alpineInterest / 100)
                        property.estimateValue = property.estimateValue * (property.alpineInterest / 100)

                        property.propertyMortgages.forEach(mortgage => {
                            mortgage.balance = mortgage.balance * (property.alpineInterest / 100)
                        })
                    })

                    this.properties = p

                    this.getMortgages()

                    this.selectAppraisalType()
                })
                .catch(error => {
                    this.alertMessage = error
                    this.showAlert('error')
                })
        },

        getCosts: function() {
            if(this.application.id === undefined) {
                return
            }
            
            this.axios({
                method: 'get',
                url: '/web/quote/costs/' + this.application.id
            })
            .then(response => {
                let mortgagePayment = response.data.data.mortgagePayment
                let propertyTax = response.data.data.propertyTax
                let income = response.data.data.income
                let liabilityPayments = response.data.data.liabilityPayments

                this.gdsr = income == 0 ? 0 : ((mortgagePayment + propertyTax + Number(this.monthlyPayment)) / income) * 100
                this.tdsr = income == 0 ? 0 : ((mortgagePayment + propertyTax + liabilityPayments + Number(this.monthlyPayment)) / income) * 100
                this.dscr = 0
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        },

        selectAppraisalType: function () {

            this.appraisedValueRadioSelect = [];
            this.assessedValueRadioSelect = [];
            this.customerValueRadioSelect = [];
            this.estimateValueRadioSelect = [];
            for (let i = 0; i < this.properties.length; i++) {
                this.appraisedValueRadioSelect[i] = false;
                this.assessedValueRadioSelect[i] = false;
                this.customerValueRadioSelect[i] = false;
                this.estimateValueRadioSelect[i] = false;
                if( Math.round(this.properties[i].appraisedValue) > 0 ) {
                    this.appraisedValueRadioSelect[i] = true;
                    this.properties[i].ltvSelected = 'A';
                } else if( Math.round(this.properties[i].assessedValue) > 0 ) {
                    this.assessedValueRadioSelect[i] = true;
                    this.properties[i].ltvSelected = 'S';
                } else if( Math.round(this.properties[i].customerValue) > 0 ) {
                    this.customerValueRadioSelect[i] = true;
                    this.properties[i].ltvSelected = 'C';
                } else if( Math.round(this.properties[i].estimateValue) > 0 ) {
                    this.estimateValueRadioSelect[i] = true;
                    this.properties[i].ltvSelected = 'E';
                }
            }
            this.calculateLTV()
        },

        getMortgages: function () {
            this.properties.forEach(property => {
                this.axios.get(
                    '/web/mortgage/property/' + property.id
                )
                    .then(response => {
                        property.inHouseMortgages = response.data.data.inHouseMortgages
                        property.seniorMortgages = response.data.data.seniorMortgages
                    })
                    .catch(error => {
                        this.alertMessage = error
                        this.showAlert('error')
                    })
                    .finally(() => {
                        this.calculateLTV()
                    })
            })
        },

        copyQuote: function(quote, companyId) {

            let headers = [
                "Date",
                "Gross Amount",
                "Net Amount",
                "Term",
                "Interest Rate",
                "2nd Year",
                "Monthly Payment",
                "Amortization",
                ...quote.positions.map((_, key) => `#${key + 1} Pos`),
                "LTV",
                "Ready to Buy",
                "Lender",
                "FM",
                "Other Fee",
                "Appr. LTV",
                "Notes",
            ];

            let secondYear = this.formatDecimal(quote.secondYear) + "%"
            if (quote.loanTerm <= 12) {
                secondYear = ""
            }

            let values = [
                this.formatPhpDate(quote.date),
                this.formatDecimal(quote.grossAmount),
                this.formatDecimal(quote.netAmount),
                quote.loanTerm,
                this.formatDecimal(quote.interestRate) + "%",
                secondYear,
                this.formatDecimal(quote.monthlyPayment),
                this.formatDecimal(quote.amortization),
                ...quote.positions.map(pos => pos.position),
                this.formatDecimal(quote.ltv) + "%",
                quote.readyToBuy,
                quote.lender,
                quote.fm,
                quote.otherFee,
                this.formatDecimal(quote.apprLtv.ltv) + "%",
                quote.quoteComments.trim().replace(/[\r\n]+/g, '').replace(/\t/g, ' ')
            ]

            if(companyId == 701) {
                this.copyQuoteAlpine(headers, values)

                //not used because Salesforce does not support HTML clipboard inside iframes
                //this.copyQuoteSequence(headers, values)
                return
            } else {
                this.copyQuoteAlpine(headers, values)
                return
            }
            
        },
        copyQuoteAlpine: function(headers, values) {
            let textToCopy = `${headers.join("\t")}\n${values.join("\t")}`
            
            Clipboard.copy(textToCopy)

            this.alertMessage = 'Quote copied to clipboard!'
            this.showAlert('success')
        },
        copyQuoteSequence: function(headers, values) {
            let htmlTable = `
                <span>
                    <strong>Quote</strong>
                </span>
                <table style="border-collapse: collapse; border: 1px solid black;" cellspacing="0" cellpadding="3">
                    <thead>
                        <tr>
                            ${headers.map(h => `
                                <th style="border: 1px solid black; text-align: center; vertical-align: middle;min-width: 100px;">
                                    <span>
                                        ${h}
                                    </span>
                                </th>`
                            ).join("")}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            ${values.map(v => `
                                <td style="border: 1px solid black; text-align: center; vertical-align: middle;">
                                    <span style="font-weight:400;">
                                        ${v}
                                    </span>
                                </td>`
                            ).join("")}
                        </tr>
                    </tbody>
                </table>`;

            let plainText = [];
            for (let i = 0; i < headers.length; i++) {
                if(values[i].length == 0) {
                    values[i] = "N/A"
                }
                plainText.push(headers[i] + " : " + values[i] + "\n");
            }

            let plainTextStr = "";
            let data;

            plainTextStr = plainText.join("");
            const blobHTML = new Blob([htmlTable], { type: 'text/html' });
            const blobText = new Blob([plainTextStr], { type: "text/plain" });
            data = [new ClipboardItem({ 'text/html': blobHTML, 'text/plain': blobText })];

            navigator.clipboard.write(data).then(() => {
                this.alertMessage = 'Quote copied to clipboard!';
                this.showAlert('success');
            }, (err) => {
                console.error("Could not copy text: ", err);
            });
        },
    }
}