<?php

namespace App\Amur\BO;

use App\Amur\Bean\DB;
use App\Amur\Bean\ILogger;
use App\Amur\BO\ApplicationBO;
use App\Amur\BO\MortgageBO;
use App\Amur\Utilities\Utils;
use App\Models\ApplicationTable;
use App\Models\FeeTable;
use App\Models\IlaTable;
use App\Models\PropertyTable;
use App\Models\SavedQuoteTable;
use App\Models\SalesJourney;
use App\Models\SavedQuotePositionsTable;
use App\Models\MortgageTable;
use App\Models\SalesforceIntegration;
use DateTime;
use App\Amur\BO\SalesforceBO;

class QuoteBO {

    private $logger;
    private $db;
    private $errors = array();

    public function __construct(ILogger $logger, DB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($objectId) {

        if(substr($objectId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $objectId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if(!$sfi) {
                return false;
            }

            $applicationId = $sfi->object_id;
            
        } else {
            $applicationId = $objectId;
        }

        $properties = PropertyTable::query()
        ->where('application_id',$applicationId)
        ->where('part_of_security','Yes')
        ->orderBy('idx')
        ->get();

        $activeProperties = array();
        foreach($properties as $key => $value) {
            $activeProperties[$value->idx] = [
                'id' => $value->property_id,
                'idx' => $value->idx,
                'position' => 'N/A'
            ];
        }

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('application_id',$applicationId)
        ->orderBy('saved_quote_id')
        ->get();

        $quotes = array();
        foreach($savedQuoteTable as $key => $value) {

            $positions = $this->getPosition($value->saved_quote_id, $activeProperties);

            if ($value->net == 0 ||
                $value->gross == 0 ||
                $value->month == 0 || 
                $value->month == '' ||
                $value->int == 0 ||
                $value->client_auth_date == '0000-00-00' ||
                $value->int_comm_date == '0000-00-00' ||
                $value->first_pmt_date == '0000-00-00'
            ) {
                if($value->mortgage_type == 537) {
                    $disabled = false;
                } else {
                    $disabled = true;
                }                    
            } else {
                $disabled = false;
            }

            $mortgageCode = '';
            if($value->mortgage_id > 0) {
                $mortgageCode = MortgageTable::query()
                ->where('mortgage_id', $value->mortgage_id)
                ->value('mortgage_code');
            }

            $query = "select a.sale_investor_id, b.first_name, a.fm_committed, a.investor_id, a.comment
                        from sale_investor_table a
                   left join investor_table b on a.investor_id = b.investor_id
                       where a.saved_quote_id = ?
                    order by if(fm_committed = 'Yes',0,if(fm_committed = 'Looking',0,1))
                       limit 1";
            $data = $this->db->select($query,[$value->saved_quote_id]);

            $lender = '';
            $fundManager = '';
            $fmComment = '';

            foreach ($data as $row) { 
       
                if(in_array($row->investor_id, Utils::micCompanies())) {
                    $lender = $row->first_name;
                } else {
                    $lender = 'ACL';
                }

                if($row->fm_committed == 'No') {
                    $lender = '';
                }

                $fundManager = substr($row->fm_committed, 0, 1);

                $fmComment = $row->comment;
            }

            $mortgageBO = new MortgageBO($this->logger, $this->db);
            $apprLtv = $mortgageBO->getLtvByQuote($value->saved_quote_id);
            
            $quotes[] = [
                'id' => $value->saved_quote_id,
                'type' => $value->evaluate_by,
                'date' => new DateTime($value->date),
                'grossAmount' => $value->gross,
                'legalFee' => $value->legal,
                'applicationFee' => $value->appr,
                'brokerageFee' => $value->broker,
                'discountFee' => $value->discount,
                'appraisalFee' => $value->misc,
                'netAmount' => $value->net,
                'otherFee' => $value->other_fee,
                'loanTerm' => $value->loan,
                'amortization' => $value->amort,
                'interestRate' => $value->int,
                'interestRateType' => is_null($value->interest_rate_type) ? 'F' : $value->interest_rate_type,
                'secondYear' => $value->prime_plus,
                'monthlyPayment' => $value->month,
                'ltv' => $value->ltv,
                'propsInv' => $value->props,
                'enableQuote' => $value->disburse,
                'readyToBuy' => $value->ready_buy,
                'positions' => $positions,
                'matureBalance' => $value->mature_bal,
                'mortgageType' => $value->mortgage_type,
                'loanCategory' => $value->loan_category,
                'primeRate2ndYear' => $value->second_year,
                'primeRateTotal' => $value->prime_plus,
                'compounded' => $value->comp,
                'retainer' => $value->retainer,
                'quoted' => $value->quoted,
                'clientAuthDate' => $value->client_auth_date,
                'interestCommDate' => $value->int_comm_date,
                'firstPaymentDueDate' => $value->first_pmt_date,
                'monthlyPayment2ndYear' => $value->{'2nd_pmt_year'},
                'mip' => is_null($value->mip) ? '' : $value->mip,
                'typeLoan' => $value->type_of_loan,
                'documentation' => $value->documentation,
                'assignmentRent' => $value->assn_rent,
                'holdBackRequired' => $value->hold_back,
                'holdBack' => $value->hold_back_amount,
                'specialPricing' => $value->special_pricing,
                'quoteComments' => $value->quote_comments,
                'apprLtv' => $apprLtv,
                'lender' => $lender,
                'fm' => $fundManager,
                'mortgageCode' => $mortgageCode,
                'mortgageId' => $value->mortgage_id,
                'disabled' => $disabled,
                'fmComment' => $fmComment,
                'gdsr' => $value->gdsr,
                'tdsr' => $value->tdsr,
                'dscr' => $value->dscr,
            ];
        }

        $cityClassification = $this->cityClassification($applicationId);
        $company = $this->getCompany($applicationId);
        $mortgageGroup = $this->getMortgageGroup($applicationId);

        return [
            'quotes' => $quotes,
            'cityClassification' => $cityClassification,
            'company' => $company,
            'mortgageGroup' => $mortgageGroup
        ];
    }

    public function getFees($companyId) {
        $feeDB = FeeTable::query()
        ->where('company_id', $companyId)
        ->orderBy('net_amt', 'desc')
        ->get();
        
        $fees = array();
        $finalGrossAmount = 9999999;
        foreach($feeDB as $key => $value) {
            if($value->net_amt == 0) {
                continue;
            }

            if(isset($feeDB[$key + 1])) {
                $initialNetAmount = $feeDB[$key + 1]->net_amt + 1;
            } else {
                $initialNetAmount = 1;
            }

            $finalNetAmount = $value->net_amt;

            $totalFees = $value->legal + $value->application + $value->misc;

            $initialGrossAmount = 
                $initialNetAmount +
                $totalFees +
                ($initialNetAmount * ($value->brokerage / 100)) +
                ($initialNetAmount * ($value->discount / 100));

            $fees[] = [
                'increase' => round($value->net_amt * (1 + ($value->increase / 100)),0),
                'legalFee' => $value->legal,
                'applicationFee' => $value->application,
                'appraisalFee' => $value->misc,
                'brokeragePerc' => $value->brokerage,
                'discountPerc' => $value->discount,
                
                'initialNetAmount' => $initialNetAmount,
                'finalNetAmount' => $finalNetAmount,
                'initialGrossAmount' => $initialGrossAmount,
                'finalGrossAmount' => $finalGrossAmount
            ];

            $finalGrossAmount = $initialGrossAmount - 0.01;
        }

        return $fees;
    }

    public function getPosition($savedQuoteId, $activeProperties) {
    
        $savedQuotePositionsTable = SavedQuotePositionsTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->orderBy('idx')
        ->get();
    
        foreach($savedQuotePositionsTable as $value) {
            if(isset($activeProperties[$value->idx])) {
                $activeProperties[$value->idx]['position'] = $value->position;
            }
        }
    
        return array_values($activeProperties);
    }    
 
    public function destroy($savedQuoteId) {
        
        $this->db->beginTransaction();
        try {
            $savedQuote = SavedQuoteTable::find($savedQuoteId);

            if($savedQuote && $savedQuote->mortgage_id == 0) {
                SavedQuotePositionsTable::where('saved_quote_id', $savedQuote->id)->delete();

                $savedQuote->delete();
                
                $this->db->commit();
                return true;
            }
        } catch (\Throwable $e) {
            $this->logger->error('QuoteBO->updateQuote', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }

        return false;
    }

    public function store($quote, $properties) {

        $savedQuoteId = 0;

        if (!isset($quote['appraisalFee'])) {
            $quote['appraisalFee'] = 0;
        }

        $sum = round($quote['netAmount'] + $quote['legalFee'] + $quote['applicationFee'] + $quote['appraisalFee'] + $quote['brokerageFee'] + $quote['discountFee'],2);

        if($sum != round($quote['grossAmount'],2)) {
            $this->logger->error('QuoteBO->store - Sum of the values does not match the Gross Amount',[
                'applicationId' => $quote['applicationId'],
                'sum' => $sum,
                'grossAmount' => $quote['grossAmount'],
            ]);

            $this->errors[] = 'Sum of the values does not match the Gross Amount!';
            return false;
        }

        $netAmount = round($quote['netAmount'],2);
        $grossAmount = round($quote['grossAmount'],2);
        $legalFee = round($quote['legalFee'],2);
        $applicationFee = round($quote['applicationFee'],2);
        $appraisalFee = round($quote['appraisalFee'],2);
        $brokerageFee = round($quote['brokerageFee'],2);
        $discountFee = $grossAmount - $netAmount - $legalFee - $applicationFee - $appraisalFee - $brokerageFee;

        if($quote['amortization'] != 999) {
            $amortization = $this->checkAmortization($quote);

            if (abs($amortization - $quote['amortization']) > 0.001) {
                $this->logger->error('QuoteBO->store - amortization does not match the calculated value',[
                    'applicationId' => $quote['applicationId'],
                    'amortization' => $quote['amortization'],
                    'calcAmortization' => $amortization,
                ]);

                $this->errors[] = 'Quote could not be saved!';
                return false;
            }
        }

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $newProperties = $propertyBO->getDataByApplicationId($quote['applicationId']);

        //LTV
        $doubleCheckLtv = $this->calculateLTV($quote, $properties, $newProperties);

        if(abs(round($doubleCheckLtv,2) - round($quote['ltv'],2)) > 0.01) {
            $this->logger->warning('QuoteBO->store - LTV does not match the calculated value', [
                'applicationId' => $quote['applicationId'],
                'calcLtv' => round($doubleCheckLtv,2),
                'ltv' => round($quote['ltv'],2)
            ]);

            $this->errors[] = 'Quote could not be saved!';
            return false;
        }

        try {
            //APR
            $aprData = $this->calculateAPR($quote);
            if($aprData && isset($aprData['apr'])) {
                $this->logger->debug('QuoteBO->store Validation - APR Double Check', [
                    'applicationId' => $quote['applicationId'],
                    'calcAPR' => $aprData['apr'],
                    'APR' => $quote['apr']
                ]);
            }

            //AER
            $aerData = $this->calculateAnnualEffectiveRate($quote);
            if(is_numeric($quote['aerPercentage'])) {
                if($aerData && isset($aerData['aer'])) {
                    $this->logger->debug('QuoteBO->store Validation - AER Double Check', [
                        'applicationId' => $quote['applicationId'],
                        'calcAER' => $aerData['aer'],
                        'AER' => $quote['aerPercentage']
                    ]);
                }
            } else {
                $this->logger->debug('QuoteBO->store Validation - AER Double Check', [
                    'applicationId' => $quote['applicationId'],
                    'AER' => $quote['aerPercentage']
                ]);
            }

            //Monthly Payment
            $doubleCheckMonthlyPayment = $this->calculateMonthlyPayment($quote);
            $this->logger->debug('QuoteBO->store Validation - Monthly Payment', [
                'applicationId' => $quote['applicationId'],
                'calcMonthlyPayment' => $doubleCheckMonthlyPayment,
                'monthlyPayment' => $quote['monthlyPayment']
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('QuoteBO->store', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }

        try {

            if(abs(round($aprData['apr'],2) - round($quote['apr'],2)) > 0.02) {
                $this->logger->warning('QuoteBO->store - APR does not match the calculated value', [
                    'applicationId' => $quote['applicationId'],
                    'calcAPR' => round($aprData['apr'],2),
                    'apr' => round($quote['apr'],2)
                ]);
    
                $this->errors[] = 'Quote could not be saved!';
                return false;
            }

            
            /*if(abs(round($aerData['aer'],2) - round($quote['aerPercentage'],2)) > 0.01) {
                $this->logger->warning('QuoteBO->store - AER does not match the calculated value', [
                    'applicationId' => $quote['applicationId'],
                    'calcAER' => round($aerData['aer'],2),
                    'aer' => round($quote['aerPercentage'],2)
                ]);
    
                $this->errors[] = 'Quote could not be saved!';
                return false;
            }*/

            if(abs(round($doubleCheckMonthlyPayment,2) - round($quote['monthlyPayment'],2)) > 0.1) {
                $this->logger->warning('QuoteBO->store - Monthly Payment does not match the calculated value', [
                    'applicationId' => $quote['applicationId'],
                    'calcMonthyPayment' => round($doubleCheckMonthlyPayment,2),
                    'monthyPayment' => round($quote['monthlyPayment'],2)
                ]);
    
                $this->errors[] = 'Quote could not be saved!';
                return false;
            }            


        } catch (\Throwable $e) {
            $this->logger->error('QuoteBO->store - Double check validation', [
                'applicationId' => $quote['applicationId'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }

        $this->db->beginTransaction();
        try {

            $props = '';

            if(isset($quote['propertyltvSelected'])) {
                foreach ($quote['propertyltvSelected'] as $row) {
                    if ($props !== '') {
                        $props .= ', ';
                    }

                    if ($row['ltvSelected'] == 'A') {
                        $props .= $row['idx'].'a';
                    } elseif ($row['ltvSelected'] == 'S') {
                        $props .= $row['idx'].'b';
                    } elseif ($row['ltvSelected'] == 'C') {
                        $props .= $row['idx'].'c';
                    } elseif ($row['ltvSelected'] == 'E') {
                        $props .= $row['idx'].'d';
                    }
                }
            }

            $secondPrime = 0;

            if($quote['interestRateType'] != 'F') {
                $secondPrime = $quote['primeRate'];
            }

            // Add to Sales Journey
            $saleJourney = SalesJourney::query()
                ->where('application_id',$quote['applicationId'])
                ->whereNotIn('status_id',[18,9])
                ->orderBy('id', 'desc')
                ->first();

            if($saleJourney) {
                $saleJourneyId = $saleJourney->id;
            } else {
                $saleJourneyId = null;
            }            

            $savedQuote = new SavedQuoteTable();
            $savedQuote->application_id = $quote['applicationId'];
            $savedQuote->evaluate_by = $quote['type'];
            $savedQuote->date = new DateTime();
            $savedQuote->gross = $grossAmount;
            $savedQuote->legal = $legalFee;
            $savedQuote->appr = $applicationFee;
            $savedQuote->broker = $brokerageFee;
            $savedQuote->discount = $discountFee;
            $savedQuote->misc = $appraisalFee;
            $savedQuote->net = $netAmount;
            $savedQuote->other_fee = empty($quote['otherFee']) ? 0 : $quote['otherFee'];
            $savedQuote->loan = $quote['loanTerm'];
            $savedQuote->amort = $quote['amortization'];
            $savedQuote->int = $quote['interestRate'];
            $savedQuote->month = round($quote['monthlyPayment'],2);
            $savedQuote->ltv = round($quote['ltv'],2);
            $savedQuote->disburse = 'Yes';
            $savedQuote->ready_buy = 'No';
            $savedQuote->mortgage_type = $quote['mortgageType'];
            $savedQuote->loan_category = $quote['loanCategory'];
            $savedQuote->prime_plus = is_null($quote['primeRateTotal']) ? 0 : $quote['primeRateTotal'];
            $savedQuote->second_year = is_null($quote['primeRate2ndYear']) ? 0 : $quote['primeRate2ndYear'];
            $savedQuote->second_prime = $secondPrime;
            $savedQuote->comp = $quote['compounded'];
            $savedQuote->retainer = is_null($quote['retainer']) ? 0 : $quote['retainer'];
            $savedQuote->quoted = $quote['quoted'];
            $savedQuote->client_auth_date = empty($quote['clientAuthDate']) ? '0000-00-00' : $quote['clientAuthDate'];
            $savedQuote->int_comm_date = empty($quote['interestCommDate']) ? '0000-00-00' : $quote['interestCommDate'];
            $savedQuote->first_pmt_date = empty($quote['firstPaymentDueDate']) ? '0000-00-00' : $quote['firstPaymentDueDate'];
            $savedQuote->mip = $quote['mip'];
            $savedQuote->type_of_loan = $quote['typeLoan'];
            $savedQuote->documentation = $quote['documentation'];
            $savedQuote->assn_rent = $quote['assignmentRent'];
            $savedQuote->hold_back = $quote['holdBackRequired'];
            $savedQuote->hold_back_amount = is_null($quote['holdBack']) ? 0 : $quote['holdBack'];
            $savedQuote->special_pricing = $quote['specialPricing'];
            $savedQuote->quote_comments = is_null($quote['quoteComments']) ? '' : $quote['quoteComments'];
            $savedQuote->mortgage_id = 0;
            $savedQuote->{'1st_month_pmt'} = round($quote['monthlyPayment'],2);
            $savedQuote->{'2nd_pmt_year'} = isset($quote['monthlyPayment2ndYear']) ? round($quote['monthlyPayment2ndYear'],2) : 0;
            $savedQuote->mature_bal = round($quote['balanceAtTermEnd'],2);
            $savedQuote->aer = round($quote['aer'],2);
            $savedQuote->apr = round($quote['apr'],2);
            $savedQuote->discountP = round($quote['discountFeePerc'],2);
            $savedQuote->brokerP = round($quote['brokerageFeePerc'],2);
            $savedQuote->props = $props;
            $savedQuote->gdsr = round($quote['gdsr'] ?? 0,2);
            $savedQuote->tdsr = round($quote['tdsr'] ?? 0,2);
            $savedQuote->dscr = round($quote['dscr'] ?? 0,2);
            $savedQuote->retainer_disb = 'false';
            $savedQuote->interest_rate_type = $quote['interestRateType'];
            $savedQuote->cheque_location = 0;
            $savedQuote->init_amt = $quote['netAmount'];
            $savedQuote->source = 'ST';
            $savedQuote->sales_journey_id = $saleJourneyId;
            $savedQuote->save();

            $savedQuoteId = $savedQuote->saved_quote_id;

            $this->updateApplication($quote['applicationId'], $quote['ila'], $secondPrime, $quote['primeRate2ndYear'], $quote['primeRateTotal']);

        } catch (\Throwable $e) {
            $this->logger->error('QuoteBO->store', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }       

        $this->db->commit();

        if ($savedQuoteId > 0) {

            $this->logger->info('QuoteBO->store - Sync quote to salesforce', [$savedQuoteId]);

            $salesforceBO = new SalesforceBO($this->logger, $this->db);
            $salesforceBO->syncQuote($savedQuoteId);
        }

        return true;
    }

    public function update($quote) {

        $this->db->beginTransaction();
        try {
            $savedQuote = SavedQuoteTable::find($quote['id']);

            if(!$savedQuote) {
                return false;
            }

            if($quote['readyToBuy'] == 'No') $savedQuote->ready_buy = $quote['readyToBuy'];
            $savedQuote->disburse = $quote['enableQuote'];
            $savedQuote->save();

            foreach($quote['positions'] as $position) {
                $savedQuotePositionsTable = SavedQuotePositionsTable::query()
                ->where('saved_quote_id',$quote['id'])
                ->where('idx',$position['idx'])
                ->first();

                if($savedQuotePositionsTable) {
                    $savedQuotePositionsTable->position = $position['position'];
                    $savedQuotePositionsTable->save();
                } else {
                    $savedQuotePositionsTable = new SavedQuotePositionsTable();
                    $savedQuotePositionsTable->saved_quote_id = $quote['id'];
                    $savedQuotePositionsTable->idx = $position['idx'];
                    $savedQuotePositionsTable->position = $position['position'];
                    $savedQuotePositionsTable->save();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('QuoteBO->update', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();

        if (isset($savedQuote->saved_quote_id) && $savedQuote->saved_quote_id > 0) {

            $this->logger->info('QuoteBO->update - Sync quote to salesforce', [$savedQuote->saved_quote_id]);

            $salesforceBO = new SalesforceBO($this->logger, $this->db);
            $salesforceBO->syncQuote($savedQuote->saved_quote_id);
        }

        return true;
    }

    public function getPrimeRate() {
        $query = "select round(interest_rate * 100,3) interest_rate
                    from prime_rate_table
                order by start_date desc
                   limit 1";
        $res = $this->db->select($query);

        if(count($res) > 0) {
            return $res[0]->interest_rate;
        }

        return false;
    }

    public function getIla($applicationId) {

        $applicationTable = ApplicationTable::find($applicationId);

        if($applicationTable) {
            
            $ilaTable = IlaTable::find($applicationTable->ila);

            $ilaName = '';
            if($ilaTable) {
                $ilaName = $ilaTable->firm_name;
            }

            return [
                'ila' => $applicationTable->ila,
                'ilaName' => $ilaName
            ];
        }

        return false;
    }

    public function updateApplication($applicationId, $ila, $secondPrime, $primeRate2ndYear, $primeRateTotal) {

        $applicationTable = ApplicationTable::find($applicationId);

        if (empty($secondPrime)) {
            $secondPrime = 0;
        }
        if (empty($primeRate2ndYear)) {
            $primeRate2ndYear = 0;
        }
        if (empty($primeRateTotal)) {
            $primeRateTotal = 0;
        }

        if($applicationTable) {

            $applicationTable->ila = $ila;
            $applicationTable->quote_param_2nd_year_prime = $secondPrime . '%';
            $applicationTable->quote_param_2nd_year = $primeRate2ndYear . '%';
            $applicationTable->quote_param_2nd_year_prime_plus = $primeRateTotal . '%';
            $applicationTable->save();

            return true;
        }

        return false;
    }

    public function calGdsr($applicationId) {

        $housingCost = $this->calHousingCost($applicationId);
        $income = $this->calIncome($applicationId);

        $gdsr = $income == 0 ? 0 : $housingCost / $income;

        return $this->nf($gdsr * 100);
    }

    public function calHousingCost($applicationId = 0, $type = '') {

        $hosingCost = $this->calMortgagePayment($applicationId, $type) + $this->calPropertyTaxHeatingStrataFee($applicationId, $type);

        return $hosingCost;
    }

    public function calIncome($applicationId) {        

        $sql = "select fn_GetIncomeByApplicationID($applicationId) AS income";
        $res = $this->db->query($sql);

        $income = 0;
        foreach ($res as $row) {
            $income += $row->income;
        }

        return $income;
    }  

    public function nf($number){
        $number = number_format($number, 2);
        if ($number == '0.00') $number = '';
        return $number;
    }

    public function calMortgagePayment($applicationId, $type = '') {        
        $query = "SELECT p.property_id, p.appraised_value, 	p.assessed_value, p.customer_value
                    FROM property_table p 
                   WHERE p.application_id = ?";
        
        if($type == 'Dscr') {
            $query .= ' AND p.part_of_security = "Yes" AND p.type = "Rental"';
        }    
        $propertyArray = $this->db->select($query,[$applicationId]);

        $propertyMortgagePaymentTotal = 0;
        foreach($propertyArray as $propertyRow) {
            //From other bank
            $query = "SELECT payment, payment_type,
                             CASE WHEN payment_type = 'Bi-monthly' THEN 2 * payment
                                  WHEN payment_type = 'Bi-weekly' THEN (26.0/12.0) * payment
                                  WHEN payment_type = 'Weekly'    THEN (52.0/12.0) * payment
                                  WHEN payment_type = 'Monthly'   THEN payment
                             END AS monthly_payment 
                       FROM property_mortgages_table
                      WHERE balance > 0
                        AND property_id = ?
                        AND payout = 'No'";
            $propertyMortgagePaymentAssoc = $this->db->select($query,[$propertyRow->property_id]);

            foreach($propertyMortgagePaymentAssoc as $key => $row){
                $propertyMortgagePaymentTotal += $row->monthly_payment;
            }
        }

        $query = "SELECT sum(monthly_pmt) monthly_pmt 
                    FROM mortgage_table 
                   WHERE application_id = ?
                     AND is_deleted = 'no'
                     AND payout_at IS NULL
                     AND current_balance > 0
                     AND transfer_id IS NOT NULL
                ORDER BY mortgage_id DESC";
        $alpineMortgagePaymentTotal = $this->db->select($query,[$applicationId]);
        
        return $propertyMortgagePaymentTotal + (is_null($alpineMortgagePaymentTotal[0]->monthly_pmt) ? 0 : $alpineMortgagePaymentTotal[0]->monthly_pmt);
    }

    public function calPropertyTaxHeatingStrataFee($applicationId, $type = '') {
        $query = "SELECT p.property_id, p.ppty_tax, p.strata_fee, p.heating_cost
                    FROM property_table p 
                   WHERE p.application_id = $applicationId";

        if($type == 'Dscr') {
            $query .= ' AND p.part_of_security = "Yes" AND p.type = "Rental" ';
        }
        $propertyArray = $this->db->query($query);

        $propertyExpense = 0;
        foreach($propertyArray as $key => $row) {
            $propertyExpense += (($row->ppty_tax) / 12) + $row->strata_fee + $row->heating_cost;
        }
        
        return $propertyExpense;
    }

    public function calTdsr($applicationId = 0) {
        //TDSR = (Total mortgage payments, property taxes, heating and condo fees) + (total  payments for all other loans & credit cards) / gross household income
        $income = $this->calIncome($applicationId);

        $tdsr = $income == 0 ? 0 : ($this->calHousingCost($applicationId) + $this->calPaymentOtherLoanCreditCard($applicationId)) / $income;

        return $this->nf($tdsr * 100);
    }

    public function calPaymentOtherLoanCreditCard($applicationId = 0) {
        $query = "SELECT sum(payment) liability_payment
                    FROM liability_table
                   WHERE application_id = ?
                     AND payout in ('No','Postponement')";
        $res = $this->db->select($query, [$applicationId]);

        if(count($res) > 0) {
            if(empty($res[0]->liability_payment) || is_null($res[0]->liability_payment)) return 0;
            
            return $res[0]->liability_payment;
        }

        return 0;
    }

    public function calDscr($applicationId = 0) {
        //DSCR = 100% market rent / PITHA (Principle, Interest, Taxes, Heat, Association/strata/condo fees)
        $housingCost = $this->calHousingCost($applicationId, 'Dscr');
        
        $dscr = $housingCost == 0 ? 0 : $this->calRental($applicationId) / $housingCost;
        
        return $this->nf($dscr * 100);
    }

    public function calRental($applicationId = 0) {
        //Find application property rental
        $query = "SELECT sum(income) rental_income
                    FROM property_rentals_table pr
               LEFT JOIN property_table p ON pr.property_id = p.property_id
                   WHERE part_of_security = 'Yes'
                     AND p.type = 'Rental'
                     AND p.application_id = ?";
        $res = $this->db->select($query,[$applicationId]);

        $propertyRental = 0;

        foreach ($res as $row) {
            $propertyRental += $row->rental_income;
        }

        return $propertyRental;
    }    

    public function getErrors() {
        return $this->errors;
    }

    public function cityClassification($applicationId) {

        $this->logger->debug('QuoteBO->cityClassification',[$applicationId]);

        $query = "select a.rural_urban, a.province,
                         (select count(*) from property_mortgages_table b where b.property_id = a.property_id and balance > 0 and payout = 'No') senior_mortgages,
                         (select count(*) from mortgage_table c
                            join mortgage_properties_table d on c.mortgage_id = d.mortgage_id and d.property_id = a.property_id
                           where c.is_deleted = 'no' and c.current_balance > 0) alpine_mortgage
                    from property_table a
                   where a.application_id = ?
                     and a.part_of_security = 'Yes'
                order by a.idx";
        $results = $this->db->select($query,[$applicationId]);

        $cityClassification = '';

        foreach($results as $r) {
            if($r->rural_urban == 'Rural') {
                $cityClassification = 'Rural';
            } elseif($r->rural_urban == 'Urban') {
                $cityClassification = 'Urban';
            } else {
                $cityClassification = $r->rural_urban;
            }

            break;
        }

        return $cityClassification;
    }

    public function getCompany($applicationId) {

        $applicationBO = new ApplicationBO($this->logger, $this->db);
        $applicationData = $applicationBO->getApplicationTable($applicationId);

        $company = '';

        if ($applicationData) {
            if ($applicationData->company == 701) {
                $company = 'SQC';
            }else {
                $company = 'ACL';
            }
        }

        return $company;
    }

    public function getMortgageGroup($applicationId) {

        $mortgageTable = MortgageTable::query()
        ->where('application_id', $applicationId)
        ->where('is_deleted', 'no')
        ->get();
        
        $mortgageGroup = 'NB';

        foreach($mortgageTable as $r) {
            $mortgageGroup = 'PB';
        }        

        return $mortgageGroup;
    }

    public function checkAmortization($quote) {
        
        $amortization = 0;

        try {

            $grossAmount    = $quote['grossAmount'];
            $interestRate   = $quote['interestRate'];
            $monthlyPayment = $quote['monthlyPayment'];
            $compounded     = $quote['compounded'];
            
            $tmp1 = (pow(($interestRate / ($compounded * 100)) + 1, $compounded / 2) - 1) * 200;        

            $tmp2 = pow(1 + ($tmp1 / 200), 1 / 6);

            $amortization = log(1 - (($grossAmount * ($tmp2 - 1)) / $monthlyPayment)) / log($tmp2) / -12;

        } catch (\Throwable $e) {

            $this->logger->info('QuoteBO->checkAmortization', [$e->getMessage(),json_encode($e->getTraceAsString())]);

        }

        return round($amortization, 4);

    }

    public function calculateLTV($quote, $properties, $newProperties) {
        $combinedPropertyValue  = 0;
        $inHouseMortgageBalance = 0;
        $seniorMortgageBalance  = 0;

        $duplicateInHouse = [];

        foreach($newProperties as $property) {

            if($property['partOfSecurity'] != 'Yes') {
                continue;
            }

            foreach($property['propertyMortgages'] as $mortgage) {
                if($mortgage['toBePaidOut'] == 'No') {
                    $seniorMortgageBalance += floatval($mortgage['mortgageBalance']) * ($property['alpineInterest'] / 100);
                }
            }

            $mortgageBO = new MortgageBO($this->logger, $this->db);
            $inHouseMortgages = $mortgageBO->getByProperty($property['id']);

            foreach($inHouseMortgages['inHouseMortgages'] as $mortgage) {
                if(isset($duplicateInHouse[$mortgage['id']])) {
                    continue;
                }

                if($mortgage['payout'] == 'No') {
                    $inHouseMortgageBalance += floatval($mortgage['balance']);
                }
                
                $duplicateInHouse[$mortgage['id']] = 'x';
            }

            $ltvSelected = 'A';
            foreach($properties as $p) {
                if($p['id'] == $property['id']) {
                    $ltvSelected = $p['ltvSelected'];
                    break;
                }
            }

            if($ltvSelected == 'A') {
                $combinedPropertyValue += (floatval($property['appraisedValue']) * ($property['alpineInterest'] / 100));
            } elseif ($ltvSelected == 'S') {
                $combinedPropertyValue += (floatval($property['assessedValue']) * ($property['alpineInterest'] / 100));
            } elseif ($ltvSelected == 'C') {
                $combinedPropertyValue += (floatval($property['customerValue']) * ($property['alpineInterest'] / 100));
            } else {
                $combinedPropertyValue += (floatval($property['estimateValue']) * ($property['alpineInterest'] / 100));
            }
        }

        $this->logger->debug('QuoteBO->calculateLTV',[$combinedPropertyValue, $inHouseMortgageBalance, $seniorMortgageBalance, $quote['grossAmount']]);

        $ltv = ($combinedPropertyValue == 0) ? 0 : (($quote['grossAmount'] + $inHouseMortgageBalance + $seniorMortgageBalance) / $combinedPropertyValue) * 100;
    
        return $ltv;
    }

    public function calculateAPR($quote) {

        $data = array();
        $firstPayment = 0;

        try {

            if (!empty($quote['firstPaymentDueDate']) && !empty($quote['interestCommDate'])) {

                $fpdTime = strtotime($quote['firstPaymentDueDate']);
                $icdTime = strtotime($quote['interestCommDate']);
        
                $diff = $fpdTime - $icdTime;
                $days = ($diff / 86400) - 1;
        
                if ($days > 30) {
                    $dailyPmt = $this->toFloat($quote['monthlyPayment']) / 30;
                    $firstPayment = round($days * $dailyPmt, 2);
                } else {
                    $firstPayment = $this->toFloat($quote['monthlyPayment']);
                }
            }
        
            if ($firstPayment < 0.01) {
                $firstPayment = $this->toFloat($quote['monthlyPayment']);
            }
        
            $valA2a = (11 * $this->toFloat($quote['monthlyPayment'])) + $firstPayment;
            $valA2b = ($quote['loanTerm'] - 12) * $this->toFloat($quote['monthlyPayment2ndYear']);
            $valA2 = $quote['loanTerm'];
        
            $valA4 = $this->toFloat($quote['balanceAtTermEnd']);
            $totalPayment = $this->toFloat($quote['otherFee']) + $valA4 + $valA2a + $valA2b;
        
            $valA1 = $this->toFloat($quote['netAmount']);
            $costOfCredit = $totalPayment - $valA1;
        
            $valTmp1 = 100 * $costOfCredit;
            $valTmp2 = $valA2 / 12;
            $valA5 = $this->toFloat($quote['grossAmount']);
            $valTmp3 = ($valA5 + $valA4) / 2;
        
            $valApr = ($valTmp3 == 0) ? 0 : $valTmp1 / ($valTmp2 * $valTmp3);
        
            $n = 12 * $valTmp2;
            $osb = $valA5;
            $int1 = $this->toFloat($quote['interestRate']);
            $int2 = $this->toFloat($quote['primeRateTotal']);
            $pmt_amt = $this->toFloat($quote['monthlyPayment']);
            $pmt_amt2 = $this->toFloat($quote['monthlyPayment2ndYear']);
        
            $totalAmt = 0;
            $num = 0;
            $interest = 0;
        
            while ($osb > $pmt_amt && $num < $quote['loanTerm']) {
                $num++;
                if ($num > 12) {
                    $interest = $osb * $int2 / 100 / 12;
                    $pmt_amt = $pmt_amt2;
                } else {
                    $interest = $osb * $int1 / 100 / 12;
                }
        
                $osb = $interest - $pmt_amt + $osb;
                $totalAmt += $osb;
            }
        
            $averageOSB = ($n == 0) ? 0 : $totalAmt / $n;
            $valTmp4 = $valTmp2 * $averageOSB;
            $valApr = ($valTmp4 == 0) ? 0 : (100 * $costOfCredit) / $valTmp4;
        
            $data = [
                'apr' => $valApr,
                'aprNetAmount' => $valA1,
                'aprOtherFee' => $quote['otherFee'],
                'aprInstallmentPayments' => ($valA2a + $valA2b),
                'aprOSBAtMaturity' => $quote['balanceAtTermEnd'],
                'aprCostOfCredit' => $costOfCredit,
                'aprAverageOSB' => $averageOSB
            ];

            $this->logger->debug('QuoteBO->calculateAPR', [
                'applicationId' => $quote['applicationId'],
                'apr' => $valApr,
                'aprNetAmount' => $valA1,
                'aprOtherFee' => $quote['otherFee'],
                'aprInstallmentPayments' => ($valA2a + $valA2b),
                'aprOSBAtMaturity' => $quote['balanceAtTermEnd'],
                'aprCostOfCredit' => $costOfCredit,
                'aprAverageOSB' => $averageOSB
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->info('QuoteBO->calculateAPR', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }
    
        return $data;
        
    }
    
    public function toFloat($value) {

        if ($value == '' || $value == null) {
            return 0.0;
        }
    
        return floatval(str_replace(',', '', strval($value)));
    }
    
    public function calculateAnnualEffectiveRate($quote) {
    
        $na = $this->toFloat($quote['netAmount']);
        $pv = $this->toFloat($quote['grossAmount']);
        $i = $this->toFloat($quote['interestRate']);
        $c = $this->toFloat($quote['compounded']);
        $pmt = $this->toFloat($quote['monthlyPayment']);
        $n = $this->toFloat($quote['loanTerm']);
        $i2ndYear = $this->toFloat($quote['primeRateTotal']);
        $y = $this->toFloat($quote['amortization']);
    
        if ($pv == 0 || $c == 0 || $n == 0 || $na == 0 || ($n > 12 && $i2ndYear == 0)) {
            return [
                'balanceAtTermEnd' => 0,
                'aer' => 0,
                'monthlyPayment2ndYear' => ''
            ];
        }

        $mthPay = $pmt;
        $mthIntPay = ($i / 100) * $pv / 12;
    
        $i1 = (pow(($i / ($c * 100)) + 1, $c / 2) - 1) * 200;
        $i1 = pow(1 + ($i1 / 200), 1 / 6) - 1;
        $i2 = $i1 + 0.005;
    
        if ($n > 12) {
            $n1stYear = 12;
            $n2ndYear = ($y * $n1stYear) - 12;
        } else {
            $n1stYear = $n;
            $n2ndYear = 0;
        }
    
        $j = pow(1 + $i1, $n1stYear);
        $osb = $i1 == 0 ? 0 : ($pv * $j) - ($pmt * (($j - 1) / $i1));
    
        $k = pow(1 + $i2, - $n1stYear);
        $na1 = $pv;
        $na2 = ($pmt * $this->aux($n1stYear, $i2)) + ($osb * $k);
    
        if ($n > 12) {
            $i2ndYear = (pow(($i2ndYear / ($c * 100)) + 1, $c / 2) - 1) * 200;
            $j = pow(1 + ($i2ndYear / 200), 1 / 6);
            $pmt2ndYear = $osb * (($j - 1) / (1 - pow($j, - $n2ndYear)));
    
            if ($mthPay <= $mthIntPay) {
                $pmt2ndYear = $mthPay;
            }
    
            $i2ndYear = pow(1 + ($i2ndYear / 200), 1 / 6) - 1;
            $j = pow(1 + $i2ndYear, $n - $n1stYear);
            $osb = $i2ndYear == 0 ? 0 : ($osb * $j) - ($pmt2ndYear * (($j - 1) / $i2ndYear));
    
            $pmt = ($pmt + $pmt2ndYear) / 2;
        } else {
            $pmt2ndYear = null;
        }
    
        $count = 0;
        while($na != $this->roundAccuracy($na2, 10)) {

            $diff = $na2 - $na1;
                
            // Validation to avoid division by zero
            if (abs($diff) < 1e-10) { //1e-10  =  1 × 10⁻¹⁰  =  0.0000000001
              $i = $i2;
              break;
            }

            $i = ($na2 - $na1) == 0 ? 0 : ((($na - $na1) * ($i2 - $i1)) / ($na2 - $na1)) + $i1;
    
            $i1 = $i2;
            $i2 = $i;
            $na1 = $na2;
    
            $k = pow(1 + $i2, -$n);
            $na2 = ($pmt * $this->aux($n, $i2)) + ($osb * $k);
    
            if($count++ > 10000) {
                break;
            }
        }
    
        return [
            'balanceAtTermEnd' => $osb,
            'aer' => is_nan($i) ? 0 : (pow(1 + $i, 12) - 1) * 100,
            'monthlyPayment2ndYear' => $pmt2ndYear ?? ''
        ];
    }
    
    public function roundAccuracy($num, $acc) {
        $factor = pow(10, $acc);
        return round($num * $factor) / $factor;
    }
    
    public function aux($n, $i) {
        $k = pow(1 + $i, -$n);
        return $i == 0 ? 0 : (1 - $k) / $i;
    }
    
    
    public function calculateMonthlyPayment($quote) {
        
        $payment      = 0;
        $grossAmount  = $this->toFloat($quote['grossAmount']);
        $interestRate = $this->toFloat($quote['interestRate']);
        $amortization = $this->toFloat($quote['amortization']) * 12;
        $compounded   = $this->toFloat($quote['compounded']);

        if ($compounded > 0) {
            $interestRate = (pow(($interestRate / ($compounded * 100)) + 1, $compounded / 2) - 1) * 200;
        }    
        
        $j = pow(1 + ($interestRate / 200), 1 / 6);
        
        
        $payment = ($amortization == 0) ? 0 : $grossAmount * (($j - 1) / (1 - pow($j, -$amortization)));
    
        return $payment;
    }

    public function getCosts($applicationId) {
        return [
            'mortgagePayment' => $this->calMortgagePayment($applicationId),
            'propertyTax' => $this->calPropertyTaxHeatingStrataFee($applicationId),
            //'housingCost' => (float) $this->calHousingCost($applicationId),
            'income' => (float) $this->calIncome($applicationId),
            'liabilityPayments' => (float) $this->calPaymentOtherLoanCreditCard($applicationId),
        ];
    }

}