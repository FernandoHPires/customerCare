export const renewal = {
    data() {
    },
    methods: {
        AER: function(yr,n) { 
            var aer = Math.pow((1+yr/100/n),n) - 1;
            return aer*100;
        },
        SAER: function(yr,n) {
            var saer = (Math.pow((1+yr/100/n),n/2) - 1)*2;
            return saer*100;
        },
        calculate_monthly_pmt: function(fee, osb_fee) {

            var pvf = this.renewalData.propertyValuationFee;
            if (pvf === undefined){
                pvf = 0;
            }else{
                pvf = pvf*1;
            }

            var p = this.renewalData.renewalOsbBox*1;
            var i = this.renewalData.intBox*1;
            var n = (this.renewalData.amortBox*1 -1)*12;
            var c = this.renewalData.compBox*1;
            i = (Math.pow((i / (c*100)) + 1, c/2) - 1) * 200; //converts to SAi
            var j = Math.pow(1 + (i/200), 1/6);
            this.renewalData.pmt = (p+osb_fee+pvf) * ((j-1) / (1 - Math.pow(j, -n)));
            var term = this.renewalData.renewalFeeToBePaidOver;

            this.renewalData.pmt += (fee/12);
            if (term !== 'term') {
                pvf = 0;
            }

            this.renewalData.pmt += (pvf/12);

            if (!this.renewalData.ab){
                if(!this.renewalData.newMonthlyPmtMaster || this.renewalData.newMonthlyPmtMaster <= 0) {
                    this.renewalData.pmt = this.renewalData.suggestedNewPayment;
                } else {
                    this.renewalData.pmt = this.renewalData.newMonthlyPmtMaster;
                }
            }else{
                this.renewalData.pmt = this.renewalData.suggestedNewPayment;
            }
            
            this.renewalData.pmtBox = (this.renewalData.pmt);
        },
        calculate_balance: function(osb_fee) {
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var i = this.renewalData.intBox*1;
            } else {
                var i = this.renewalData.newInterestRate*1;
            }
            var p = this.renewalData.renewalOsbBox*1;
            var n = this.renewalData.loanBox*1;
            var m = this.renewalData.pmtBox*1;
            var c = this.renewalData.compBox*1;

            i = (Math.pow((i / (c*100)) + 1, c/2) - 1) * 200; 
            i = Math.pow((1 + (i/200)), (1/6));
            i = i-1;

            var j = Math.pow(1+i, n);

            var pvf = this.renewalData.propertyValuationFee;
            if (pvf === undefined){
                pvf = 0;
            }else{
                pvf = pvf*1;
            }        

            var osb = ((p+osb_fee+pvf)*j) - (m * ((j-1) / i));
            this.renewalData.osbBox = osb;
            this.renewalData.osbAtNextTermEndMaster = osb;
            this.renewalData.osbRenewalFeeMaster = (p+osb_fee+pvf);
        },
        MAER: function() {
            var n = this.renewalData.n;
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var i = this.renewalData.intBox*1;
            } else {
                var i = this.renewalData.newInterestRate*1;
            }
            var maer = this.AER(i,n);
            this.renewalData.annualEffectiveRateMaster = maer
            return maer;
        },
        calculate_semi_annual_comp_equiv: function() {
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var i = this.renewalData.intBox*1;
            } else {
                var i = this.renewalData.newInterestRate*1;
            }
            var c = this.renewalData.compBox*1;

            var sai = (Math.pow((i / (c*100)) + 1, c/2) - 1) * 200;

            this.renewalData.semiAnnualEquivalentIntRateMaster = sai;
        },
        roundAccuracy: function(num, acc){
            var factor=Math.pow(10,acc);
            return Math.round(num*factor)/factor;
        },
        calculate_eff_annual_rate: function() {
            var na = this.renewalData.osbAtRenewal * 1;
            var pv = this.renewalData.osbRenewalFeeMaster * 1;
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var i = this.renewalData.intBox*1;
            } else {
                var i = this.renewalData.newInterestRate*1;
            }
            var c = this.renewalData.compBox * 1;
            var pmt = this.renewalData.pmtBox * 1;
            var n = this.renewalData.loanBox * 1;
            var y = this.renewalData.amortBox * 1;
            var i1 = (Math.pow((i / (c * 100)) + 1, c / 2) - 1) * 200;
            i1 = Math.pow((1 + (i1 / 200)), (1 / 6));
            i1 = i1 - 1;
            var i2 = i1 + 0.005;
            var j = Math.pow(1 + i1, n);
            var osb = (pv * j) - (pmt * ((j - 1) / i1));
            var k = Math.pow(1 + i2, -n);
            var na1 = pv;
            var na2 = (pmt * this.a(n, i2)) + (osb * k);
            var count = 0;
            while (Math.abs(na - na2) > 1e-9) {
                var denominator = na2 - na1;
                if (Math.abs(denominator) < 1e-12) { 
                    console.error("Breaking loop: denominator too small");
                    break;
                }
                i = (((na - na1) * (i2 - i1)) / (na2 - na1)) + i1;


                i1 = i2;
                i2 = i;
                na1 = na2;

                k = Math.pow(1 + i2, -n);
                na2 = (pmt * this.a(n, i2)) + (osb * k);

                if (count++ > 10000) {
                    console.error("Breaking after 10,000 iterations");
                    break;
                }
            }

            var aer = (Math.pow(1 + i, 12) - 1) * 100;

            this.renewalData.annualEffectiveRateMaster = aer;

        },
        calculateAmortizationYears: function(loanAmount, annualInterestRate, monthlyPayment) {
            const monthlyInterestRate = annualInterestRate / 12 / 100;
            const calPayment = Number((loanAmount * monthlyInterestRate));
            if (monthlyPayment < calPayment) {
                return 1000; 
            }else if (monthlyPayment == calPayment) {
                return 999; 
            }
            const totalPayments = Math.log(monthlyPayment / (monthlyPayment - monthlyInterestRate * loanAmount)) / Math.log(1 + monthlyInterestRate);
            const totalYears = totalPayments / 12;
            return totalYears;
        },
        a: function(n, i) {
            var k = Math.pow(1+i, -n); 
            var a = (1-k) / i; 

            return a;
        },
        calculate_apr: function() {
            if(!this.renewalData.newMonthlyPmtMaster || this.renewalData.newMonthlyPmtMaster <= 0) {
                // var pmtBox = this.renewalData.oldMonthlyPayment;
                var monthBox = this.renewalData.oldMonthlyPayment;
            } else {
                // var pmtBox = this.renewalData.newMonthlyPmtMaster;
                var monthBox = this.renewalData.newMonthlyPmtMaster;
            }
            
            var osbBox = this.renewalData.osbAtNextTermEndMaster;
            var osb = osbBox*1;
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var intBox = this.renewalData.intBox*1;
            } else {
                var intBox = this.renewalData.newInterestRate*1;
            }
            var amortBox = this.renewalData.amortBox * 1;
            var remain_amortization = this.renewalData.remainingAmortization;
            var grossBox = this.renewalData.grossBox;
            var loanBox = this.renewalData.loanBox;
            var renewal_osbBox = this.renewalData.osbAtRenewal;
            var compBox = this.renewalData.compBox;
            var nextTerm_enddate 	= this.renewalData.nextTermDueDate;
            var nextTerm_startdate	= this.renewalData.renewalDate;
            var term_length2 = this.renewalData.aprNewTermLength;
            var renewal_fee2 = this.renewalData.renewalFee*1;
            var pvfElement = this.renewalData.propertyValuationFee;
            if (pvfElement != null && pvfElement.length > 0) {
                var property_valuation_fee_2 = this.renewalData.propertyValuationFee*1;
            }
            
            if(!this.renewalData.newMonthlyPmtMaster || this.renewalData.newMonthlyPmtMaster <= 0) {
                var new_monthly_pmt2 = this.renewalData.oldMonthlyPayment*1;
            } else {
                var new_monthly_pmt2 = this.renewalData.newMonthlyPmtMaster*1;
            }

            var tmpSum1 = new_monthly_pmt2*term_length2+osbBox*1;
            var tmpSum2 = tmpSum1-renewal_osbBox*1;

            this.renewalData.aprOsbAtRenewal = (renewal_osbBox); 
            this.renewalData.aprRenewalFee = (renewal_fee2); 
            if (pvfElement != null && pvfElement.length > 0) {
                this.renewalData.propertyValuationFee = property_valuation_fee_2;
            }
            this.renewalData.aprNewMonthlyPayment = (new_monthly_pmt2);
            this.renewalData.aprNewTotalPayment = (new_monthly_pmt2*term_length2); 
            this.renewalData.aprBalanceAtNextTermEnd = (osbBox); 
            this.renewalData.aprTotalPayments = (tmpSum1);
            this.renewalData.totalCostOfCredit = (tmpSum2);
            this.renewalData.apr = (100*tmpSum2*24/(term_length2*(osbBox*1+renewal_osbBox*1+renewal_fee2)));
            this.renewalData.aprNewTermLength = term_length2;

            var p = renewal_osbBox*1+renewal_fee2;
            var i = intBox*1;
            var m = monthBox;
            var c_ = compBox*1;
            var c = c_;
            var n;
            
            i = (Math.pow((i / (c*100)) + 1, c/2) - 1) * 200; 

            var j = Math.pow(1 + (i/200), 1/6);
            n = Math.log(1 - ((p*(j-1))/m)) / Math.log(j) / -12;
             
            var paid_over = this.renewalData.renewalFeeToBePaidOver;
            var renewal_fee = this.renewalData.renewalFee;

            // if(!this.renewalData.newMonthlyPmtMaster || this.renewalData.newMonthlyPmtMaster <= 0) {
            //     var m_payment = this.renewalData.oldMonthlyPayment;
            // } else {
            //     var m_payment = this.renewalData.newMonthlyPmtMaster;
            // }

            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var rate = this.renewalData.intBox*1;
            } else {
                var rate = this.renewalData.newInterestRate*1;
            }
            var gross_amount = this.renewalData.osbRenewalFeeMaster;

            //New amortization
            var p = renewal_osbBox + renewal_fee2;
            var i = intBox;
            var m = monthBox;
            var c = compBox * 1;
            var n;
            i = (Math.pow((i / (c * 100)) + 1, c / 2) - 1) * 200;
            var j = Math.pow(1 + (i / 200), 1 / 6);
            n = Math.log(1 - ((p * (j - 1)) / m)) / Math.log(j) / -12;

            if(!this.renewalData.newMonthlyPmtMaster || this.renewalData.newMonthlyPmtMaster <= 0) {
                var n = this.calculateAmortizationYears(gross_amount, rate, this.renewalData.oldMonthlyPayment);
            } else {
                var n = this.calculateAmortizationYears(gross_amount, rate, this.renewalData.newMonthlyPmtMaster);
            }

            if(isNaN(n)) {
                this.renewalData.remainingAmortization = n;
            } else if(n == '1000') {
                this.renewalData.remainingAmortization = "";
                this.errorMsg = "Monthly payment won't cover interest amount";
                if (this.intOnlyMsg) {
                    this.intOnlyMsg = "";
                }
            } else if(n == '999') {
                this.renewalData.remainingAmortization = 999;
                this.intOnlyMsg = "Monthly payment just cover interest amount, Interest Only Loan";
                if (this.errorMsg) {
                    this.errorMsg = "";
                }
            } else {
                this.renewalData.remainingAmortization = this.roundAccuracy(n, 4);
                if (this.errorMsg) {
                    this.errorMsg = "";
                }
                if (this.intOnlyMsg) {
                    this.intOnlyMsg = "";
                } 
            }
        },
        calculateRenewal: function() {

            var n = this.renewalData.n;
            var ab = this.renewalData.ab;
            var fee = this.renewalData.renewalFee*1;
            var osb_fee = fee;

            if(this.renewalData.renewalFeeToBePaidOver != 'term'){
                fee = 0;
            }
            
            if(this.renewalData.renewalFeeToBePaidOver == 'upfront'){
                osb_fee = 0; 
                fee = 0
            }

            if (ab){

                this.abCalculate();

                var c_rate0 = this.renewalData.apInterestRate;
                var c_rate1 = this.renewalData.bpInterestRate;
                var c_rate2 = this.renewalData.cpInterestRate;

                this.renewalData.annualEffectiveRateAP = this.AER(c_rate0,n)
                this.renewalData.annualEffectiveRateBP = this.AER(c_rate1,n)
                this.renewalData.annualEffectiveRateCP = this.AER(c_rate2,n)

                this.renewalData.semiAnnualEquivalentIntRateAP = this.SAER(c_rate0,n)
                this.renewalData.semiAnnualEquivalentIntRateBP = this.SAER(c_rate1,n)
                this.renewalData.semiAnnualEquivalentIntRateCP = this.SAER(c_rate2,n)

                this.calculate_balance(osb_fee);
                this.calculate_monthly_pmt(fee, osb_fee);
                this.MAER();
                this.calculate_semi_annual_comp_equiv();
                this.calculate_apr();

            }else{

                this.calculate_monthly_pmt(fee, osb_fee);
                this.calculate_balance(osb_fee);
                this.calculate_eff_annual_rate();
                this.calculate_semi_annual_comp_equiv();
                this.calculate_apr();

            }
        },
        calPmt: function(ir, np, pv, fv=0, type=0) {
            /*
            * ir   - interest rate per month
            * np   - number of periods (months)
            * pv   - present value
            * fv   - future value
            * type - when the payments are due:
            *        0: end of the period, e.g. end of month (default)
            *        1: beginning of period
            */
            ir = ir / 1200;
            np = np * 12;

            var pmt, pvif;

            if (ir === 0)
                return -(pv + fv)/np;

            pvif = Math.pow(1 + ir, np);
            pmt = - ir * (pv * pvif + fv) / (pvif - 1);

            if (type === 1)
                pmt /= (1 + ir);

            
            pmt = -1*pmt;
            pmt = pmt;

            return pmt;
        },
        calFv: function(pv,pmt,r,n){
            var ratePerPayment = r / (12 * 100);
            var onePlusRatePow = Math.pow(1+ratePerPayment,n);
            var fv = pv*onePlusRatePow - pmt*((onePlusRatePow - 1)/ratePerPayment);
            return fv;
        },
        abCalculate: function(amort='') {
        
            var paid_over = this.renewalData.renewalFeeToBePaidOver;
            var n = Number(this.renewalData.numberOfMonthInTerm);
            var renewal_fee = Number(this.renewalData.renewalFee);

            var cn = this.renewalData.cInvCardCount
            var gross_amount = Number(this.renewalData.osbRenewalFeeMaster);
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var rate = this.renewalData.intBox*1;
            } else {
                var rate = this.renewalData.newInterestRate*1;
            }
            var m_payment = Number(this.renewalData.suggestedNewPayment);

            var suggested_pmt = this.renewalData.mPmt;
            var default_renewal_fee = this.renewalData.renewalFee;

            if (default_renewal_fee != renewal_fee){
                var amort = this.renewalData.amortBox * 1;
                var m_payment_renewal = this.calPmt(rate, amort-1, gross_amount, 0, 0);
                if (paid_over == 'term') {
                    m_payment_renewal = Number(m_payment_renewal) + renewal_fee / 12;
                    m_payment_renewal = Number(m_payment_renewal);
                }
                var m_payment = Number(this.renewalData.suggestedNewPayment);
            }

            if (m_payment != suggested_pmt && m_payment != m_payment_renewal) {
                var amort = this.calculateAmortizationYears(gross_amount, rate, m_payment);
                amort= amort + 1;
                amort = Number(amort);
            }else{
                amort = this.renewalData.amortBox * 1;
            }

            var ap_amount = Number(this.renewalData.osbRenewalFeeAP);
            var ap_pv = ap_amount;
            var ap_rate = Number(this.renewalData.apInterestRate);
            var ap_payment = this.calPmt(ap_rate, amort-1, ap_amount, 0, 0);
            
            var a_piece_percentage = ap_amount/gross_amount;
            if (paid_over == 'term'){
                ap_payment = Number(ap_payment) + (renewal_fee/12)*a_piece_percentage;
            }

            var bp_amount = Number(this.renewalData.osbRenewalFeeBP);
            var bp_pv = bp_amount;
            var bp_rate = Number(this.renewalData.bpInterestRate);
            var bp_payment = this.calPmt(bp_rate, amort-1, bp_amount, 0, 0);
            var b_piece_percentage = bp_amount/gross_amount;
            if (paid_over == 'term'){
                bp_payment = Number(bp_payment) + (renewal_fee/12)*b_piece_percentage;
            }

            if (cn == 3) {            
                var cp_amount = Number(this.renewalData.osbRenewalFeeCP);
                var cp_pv = cp_amount;
                var cp_rate = Number(this.renewalData.cpInterestRate);
                var cp_payment = Number(this.renewalData.suggestedNewPaymentCP);
            }

            var m_fv = this.calFv(gross_amount, m_payment, rate,n);
            var ap_fv = this.calFv(ap_pv, ap_payment, ap_rate, n);
            var bp_fv = this.calFv(bp_pv, bp_payment, bp_rate, n);

            this.renewalData.newMonthlyPmtMaster = m_payment;
            this.renewalData.newMonthlyPmtAP = ap_payment;
            this.renewalData.newMonthlyPmtBP = bp_payment;

            if (cn == 2) {//AB

                var bp_amount = gross_amount - ap_amount;
                bp_payment = Number(m_payment) - Number(ap_payment);

                this.renewalData.osbRenewalFeeBP = Number(bp_amount);
                this.renewalData.newMonthlyPmtBP = bp_payment;

                bp_fv = m_fv - ap_fv;
                bp_rate = 100*this.excelRate(n, -1*bp_payment, bp_amount, -1*bp_fv, 0);
                this.renewalData.bpInterestRate = Number(bp_rate);

                bp_fv = Number(m_fv) - Number(ap_fv);
                this.renewalData.osbAtNextTermEndMaster = Number(m_fv);
                this.renewalData.osbAtNextTermEndAP = Number(ap_fv);
                this.renewalData.osbAtNextTermEndBP = bp_fv;
                
            } 
            
            if (cn == 3) {

                var cp_amount = gross_amount - ap_amount - bp_amount;
                var cp_payment = Number(m_payment) - Number(ap_payment) - Number(bp_payment);
                this.renewalData.osbRenewalFeeBP = Number(bp_amount);
                this.renewalData.newMonthlyPmtBP = bp_payment;
                this.renewalData.osbRenewalFeeCP = Number(cp_amount);
                this.renewalData.newMonthlyPmtCP = cp_payment;

                var cp_fv = m_fv - ap_fv - bp_fv;
                cp_rate = 100*this.excelRate(n, -1*cp_payment, cp_amount, -1*cp_fv, 0);
                this.renewalData.cpInterestRate = Number(cp_rate);

                cp_fv = Number(m_fv) - Number(ap_fv) - Number(bp_fv);
                this.renewalData.osbAtNextTermEndMaster = Number(m_fv);
                this.renewalData.osbAtNextTermEndAP = Number(ap_fv);
                this.renewalData.osbAtNextTermEndBP = bp_fv;
            }        

        },
        excelRate: function(periods, payment, present, future, type, guess) {

            guess = (guess === undefined) ? 0.01 : guess;
            future = (future === undefined) ? 0 : future;
            type = (type === undefined) ? 0 : type;

            // Set maximum epsilon for end of iteration
            const epsMax = 1e-6;

            // Set maximum number of iterations
            const iterMax = 100;
            let iter = 0;
            let close = false;
            let rate = guess;

            while (iter < iterMax && !close) {
                const t1 = Math.pow(rate + 1, periods);
                const t2 = Math.pow(rate + 1, periods - 1);
                const f1 = future + t1 * present + payment * (t1 - 1) * (rate * type + 1) / rate;
                const f2 = periods * t2 * present - payment * (t1 - 1) * (rate * type + 1) / Math.pow(rate, 2);
                const f3 = periods * payment * t2 * (rate * type + 1) / rate + payment * (t1 - 1) * type / rate;
                const newRate = rate - f1 / (f2 + f3);
                if (Math.abs(newRate - rate) < epsMax) {
                    close = true;
                }
                iter++
                rate = newRate;
            }
            if (!close) {
                return Number.NaN + rate;
            }
            return rate*12;
        },
        update_term: function() {
            var mon1 = this.renewalData.renewalDate;
            var mon2 = this.renewalData.nextTermDueDate;
            this.DateDiff(mon2,mon1);
        },
        DateDiff: function(date1,date2) { 
            var rg=/-/g;
            date1=date1.replace(rg,"/");
            date2=date2.replace(rg,"/");
            var d1=new Date(date1);
            var d2=new Date(date2);
            var t=(d1-d2)/(24*60*60*1000);
            t=12*(d1.getYear()-d2.getYear())+d1.getMonth()-d2.getMonth();
            this.renewalData.aprNewTermLength = t; 
            this.renewalData.numberOfMonthInTerm = t; 
            this.renewalData.loanBox = t;
        },
        updatePVF: function() {
            var dropdown = this.renewalData.propertyValuation;

            if (dropdown === "yes") {
                if (isNaN(parseFloat(this.renewalData.propertyValuationFee))) {
                    this.renewalData.propertyValuationFee = this.renewalData.defaultPvf
                }
            } else {
                this.renewalData.propertyValuationFee = null
            }
            
            var osb_n_renewal = this.renewalData.osbVar + this.renewalData.renewalFee
            var pvf = this.renewalData.propertyValuationFee
            var osb_n_renewal_n_pv = osb_n_renewal + parseFloat(pvf);

            if (parseFloat(pvf) > 0){
                this.renewalData.osbRenewalFeeMaster = osb_n_renewal_n_pv
            }else{
                this.renewalData.osbRenewalFeeMaster = osb_n_renewal
            }
        },
        calPmt: function(ir, np, pv, fv=0, type=0) {
            /*
            * ir   - interest rate per month
            * np   - number of periods (months)
            * pv   - present value
            * fv   - future value
            * type - when the payments are due:
            *        0: end of the period, e.g. end of month (default)
            *        1: beginning of period
            */
            ir = ir / 1200;
            np = np * 12;

            var pmt, pvif;

            if (ir === 0) {
                return -(pv + fv)/np;
            }

            pvif = Math.pow(1 + ir, np);
            pmt = - ir * (pv * pvif + fv) / (pvif - 1);

            if (type === 1) {
                pmt /= (1 + ir);
            }
            
            pmt = -1*pmt;
            pmt = this.roundAccuracy(pmt, 2);

            return pmt;
        },
        newInterestChange: function() {
            var paid_over = this.renewalData.renewalFeeToBePaidOver;
            var cn = this.renewalData.cInvCardCount;
            var amort = this.renewalData.amortBox * 1;

            var gross_amount = this.renewalData.osbRenewalFeeMaster;
            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var rate = this.renewalData.intBox*1;
            } else {
                var rate = this.renewalData.newInterestRate*1;
            }
            var m_payment = this.calPmt(rate, amort-1, gross_amount, 0, 0);//lifetime
            if (paid_over == 'term'){
                m_payment = Number(m_payment) + this.renewalData.renewalFee/12;
            }

            this.renewalData.suggestedNewPayment = m_payment; // Suggested New Payment

            var ap_payment = this.renewalData.suggestedNewPaymentAP;
            var bp_payment = this.renewalData.suggestedNewPaymentBP;
                
            if (cn == 2) {
                bp_payment = m_payment - ap_payment;
                this.renewalData.suggestedNewPaymentBP = bp_payment; // Suggested New Payment
            } else if (cn == 3) {
                var cp_payment = m_payment - ap_payment - bp_payment;
                this.renewalData.suggestedNewPaymentCP = cp_payment; // Suggested New Payment
            }
        },
        newInterestAPChange: function() {
            var paid_over = this.renewalData.renewalFeeToBePaidOver;
            var cn = this.renewalData.cInvCardCount;
            var amort = this.renewalData.amortBox * 1;

            var ap_amount = this.renewalData.osbRenewalFeeAP;
            var ap_rate = this.renewalData.apInterestRate;
            var ap_payment = this.calPmt(ap_rate, amort-1, ap_amount, 0, 0); //lifetime

            if (paid_over == 'term'){
                ap_payment = Number(ap_payment) + this.renewalData.renewalFeeCalc/12*this.renewalData.c_osb_per0;
            }
            
            this.renewalData.suggestedNewPaymentAP = ap_payment; // Suggested New Payment
            this.renewalData.newMonthlyPmtAP = ap_payment; // New Monthly AP Payment

            var m_payment = this.renewalData.suggestedNewPayment;
            var bp_payment = this.renewalData.suggestedNewPaymentBP;
                
            if (cn == 2) {
                bp_payment = m_payment - ap_payment;
                this.renewalData.suggestedNewPaymentBP = bp_payment; // Suggested New Payment
            } else if (cn == 3) {
                var cp_payment = m_payment - ap_payment - bp_payment;
                this.renewalData.suggestedNewPaymentCP = cp_payment;  // Suggested New Payment
            }
        },
        newInterestBPChange: function() {
            var paid_over = this.renewalData.renewalFeeToBePaidOver;
            var cn = this.renewalData.cInvCardCount;
            var amort = this.renewalData.amortBox * 1;

            var bp_amount = this.renewalData.osbRenewalFeeBP;
            var bp_rate = this.renewalData.bpInterestRate;
            var bp_payment = this.calPmt(bp_rate, amort-1, bp_amount, 0, 0); //lifetime

            if (paid_over == 'term'){
                bp_payment = Number(bp_payment) + this.renewalData.renewalFeeCalc/12*this.renewalData.c_osb_per1;
            }
            
            this.renewalData.suggestedNewPaymentBP = bp_payment; // Suggested New Payment
            this.renewalData.newMonthlyPmtBP = bp_payment; // New Monthly BP Payment

            var m_payment = this.renewalData.suggestedNewPayment; 
            var ap_payment = this.renewalData.suggestedNewPaymentAP;
                
            if (cn == 3) {
                var cp_payment = m_payment - ap_payment - bp_payment;
                this.renewalData.suggestedNewPaymentCP = cp_payment;  // Suggested New Payment
            }
        },
        newRenewalFee: function() {
            this.updateOsbs();
            this.updatePayments('renewal_fee'); //use the original calculated amortization
        },
        newRenewalFeeToPaidOver: function() {
            this.updatePayments('renewal_paid_over')
        },
        updateOsbs: function() {
            var cn = this.renewalData.cInvCardCount*1;
            var m_osb = this.renewalData.osbAtRenewal*1;
            var ap_osb = this.renewalData.osbAtRenewalAP*1;
            var bp_osb = this.renewalData.osbAtRenewalBP*1;

            var renewal_fee = this.renewalData.renewalFee*1;
            var m_pv = m_osb + renewal_fee;

            var ap_pv = ap_osb + renewal_fee * ap_osb / m_osb;
            var bp_pv = bp_osb + renewal_fee * bp_osb / m_osb;
            this.renewalData.osbRenewalFeeMaster = m_pv
            this.renewalData.osbRenewalFeeAP = ap_pv
            this.renewalData.osbRenewalFeeBP = bp_pv

            var ap_renewal_fee = renewal_fee * ap_osb / m_osb;
            var bp_renewal_fee = renewal_fee * bp_osb / m_osb;
            this.renewalData.renewalFeeAP = ap_renewal_fee
            this.renewalData.renewalFeeBP = bp_renewal_fee

            if (cn == 3){
                var cp_renewal_fee = renewal_fee - ap_renewal_fee - bp_renewal_fee;
                this.renewalData.renewalFeeCP = cp_renewal_fee
            }

            if (cn == 2) {//AB
                bp_pv = m_pv - ap_pv;
                this.renewalData.osbRenewalFeeBP = bp_pv
            } else if (cn == 3) {//ABC
                var cp_pv = m_pv - ap_pv - bp_pv;
                this.renewalData.osbRenewalFeeCP = cp_pv
            }
        },
        updatePayments: function(onChange) {
            var n = this.renewalData.numberOfMonthInTerm*1;
            var cn = this.renewalData.cInvCardCount*1;
            var paid_over = this.renewalData.renewalFeeToBePaidOver
            var renewal_fee = this.renewalData.renewalFee*1;
            var renewal_osb = this.renewalData.osbAtRenewal*1;
            this.renewalData.osbRenewalFeeMaster = renewal_osb + renewal_fee;
            var gross_amount = this.renewalData.osbRenewalFeeMaster*1;
            var amort = this.renewalData.amortBox * 1;

            if(!this.renewalData.newInterestRate || this.renewalData.newInterestRate <= 0) {
                var rate = this.renewalData.intBox*1;
            } else {
                var rate = this.renewalData.newInterestRate*1;
            }

            if (onChange == 'renewal_paid_over') {

                var m_payment = this.calPmt(rate, amort-1, gross_amount, 0, 0);

                var m_osb = this.renewalData.osbAtRenewal*1;
                var m_pv = m_osb + renewal_fee;
                this.renewalData.osbRenewalFeeMaster = m_pv

                var ap_osb = this.renewalData.osbAtRenewalAP*1;
                var ap_pv = ap_osb + renewal_fee * ap_osb / m_osb;
                this.renewalData.osbRenewalFeeAP = ap_pv

                var bp_osb = this.renewalData.osbAtRenewalBP*1;
                var bp_pv = bp_osb + renewal_fee * bp_osb / m_osb;
                this.renewalData.osbRenewalFeeBP = bp_pv

                if (cn == 3) {
                    var cp_osb = this.renewalData.osbAtRenewalCP*1;
                    var cp_pv = cp_osb + renewal_fee * cp_osb / m_osb;
                    this.renewalData.osbRenewalFeeCP = cp_pv
                }
                
                if (paid_over == 'term') {//lifetime to term

                    m_payment = Number(m_payment) + renewal_fee / 12;

                } else if (paid_over == 'lifetime') {//term to lifetime

                    // m_payment = Number(m_payment) - renewal_fee / 12;

                } else if (paid_over == 'upfront') {
                    
                    var m_payment_renewal = this.calPmt(rate, amort-1, renewal_osb, 0, 0); //upfront
                    m_payment = m_payment_renewal*1;

                    // this.renewalData.suggestedNewPayment = m_payment; // Suggested New Payment

                    var m_osb = this.renewalData.osbAtRenewal*1;
                    this.renewalData.osbRenewalFeeMaster = m_osb
                    
                    var ap_osb = this.renewalData.osbAtRenewalAP*1;
                    this.renewalData.osbRenewalFeeAP = ap_osb

                    var bp_osb = this.renewalData.osbAtRenewalBP*1;
                    this.renewalData.osbRenewalFeeBP = bp_osb

                    if (cn == 3) {
                        var cp_osb = this.renewalData.osbAtRenewalCP*1;
                        this.renewalData.osbRenewalFeeCP = cp_osb
                    }
                }          
                
                var suggested_pmt = this.renewalData.mPmt
                var default_renewal_fee = this.renewalData.orgRenewalFee

                //Here calculate changed payments when the renewal fee has changed, find updated m_payment .
                if (default_renewal_fee != renewal_fee) {
                    var m_payment_renewal = this.calPmt(rate, amort-1, gross_amount, 0, 0);//lifetime
                    if (paid_over == 'term') {
                        m_payment_renewal = Number(m_payment_renewal) + renewal_fee / 12;
                        m_payment_renewal = m_payment_renewal.toFixed(2);
                    }
                }

                if (m_payment != suggested_pmt && m_payment != m_payment_renewal) {
                    amort = this.calculateAmortizationYears(gross_amount, rate, m_payment);//loanAmount, annualInterestRate, monthlyPayment
                    amort = amort + 1;//adjustment for following code using amort - 1;
                    amort = amort.toFixed(4);
                } else {
                    amort = this.renewalData.amortBox * 1;
                }

            } else if (onChange == 'renewal_fee') {

                var m_payment = this.calPmt(rate, amort-1, gross_amount, 0, 0);//lifetime
                if (paid_over == 'term') {
                    m_payment = Number(m_payment) + renewal_fee / 12;
                }
            }
            
            var ap_amount = this.renewalData.osbRenewalFeeAP

            var ap_pv = ap_amount;
            var ap_rate = this.renewalData.apInterestRate
            var ap_payment = this.calPmt(ap_rate, amort-1, ap_amount, 0, 0); //lifetime

            var bp_amount = this.renewalData.osbRenewalFeeBP
            var bp_pv = bp_amount;
            var bp_rate = this.renewalData.bpInterestRate
            var bp_payment = this.calPmt(bp_rate, amort-1, bp_amount, 0, 0); //lifetime

            if (paid_over == 'term') {
                ap_payment = Number(ap_payment) + (renewal_fee / 12) * this.renewalData.c_osb_per0;
                bp_payment = Number(bp_payment) + (renewal_fee / 12) * this.renewalData.c_osb_per1;
            }
            if (cn == 3 && paid_over == 'term') {
                cp_payment = Number(cp_payment) + (renewal_fee / 12) * this.renewalData.c_osb_per2;
            }

            this.renewalData.suggestedNewPayment = m_payment // Suggested New Payment
            this.renewalData.suggestedNewPaymentAP = ap_payment // Suggested New Payment
            this.renewalData.suggestedNewPaymentBP = bp_payment // Suggested New Payment

            // this.renewalData.newMonthlyPmtMaster = m_payment
            this.renewalData.newMonthlyPmtAP = ap_payment
            this.renewalData.newMonthlyPmtBP = bp_payment

            if (cn == 2) {//AB
                bp_payment = Number(m_payment) - Number(ap_payment)

                this.renewalData.suggestedNewPaymentBP = bp_payment; // Suggested New Payment
                this.renewalData.newMonthlyPmtBP = bp_payment
            } else if (cn == 3) {//ABC
                cp_payment = Number(m_payment) - Number(ap_payment) - Number(bp_payment)

                this.renewalData.suggestedNewPaymentCP = cp_payment; // Suggested New Payment
                this.renewalData.newMonthlyPmtCP = cp_payment
            }
        },
    }
}