<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\SavedQuoteTable;
use App\Models\PropertyMortgagesTable;
use App\Models\MortgageMortgagorsTable;
use App\Models\CorporationTable;
use App\Models\AppraisalFirmsTable;
use App\Models\MortgageTable;
use App\Models\MortgageBalanceHistory;
use App\Models\MortgagePaymentsTable;
use Carbon\Carbon;

class MortgageBO {

    private $logger;
    private $db;
    private $showPropertyMortgages;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getMortgageTable($mortgageId) {

        $mortgageTable = MortgageTable::query()
            ->where('mortgage_id', $mortgageId)
            ->first();

        return $mortgageTable;
    }

    public function getByProperty($propertyId) {
        return [
            'seniorMortgages' => $this->getExistingMortgages($propertyId),
            'inHouseMortgages' => $this->getInHouseMortgages($propertyId)
        ];
    }

    public function getExistingMortgages($propertyId) {

        $query = "select a.mortgage_id id, a.balance, a.payment, c.firm_name lender_name, a.rate, a.payout, d.alpine_interest
                    from property_mortgages_table a
                    join property_table d on a.property_id = d.property_id
               left join lender_firm_branches_table b on a.lender_id = b.lender_branch_code
               left join lender_firm_table c on b.lender_code = c.lender_code
                   where a.property_id = ?
                     and a.balance > 0
                order by a.mortgage_id";
        $res = $this->db->select($query, [$propertyId]);

        $data = array();
        foreach ($res as $key => $value) {
            $data[] = [
                'id' => $value->id,
                'position' => Utils::toOrdinalAbbr($key + 1),
                'balance' => $value->balance,
                'payment' => $value->payment,
                'lenderName' => $value->lender_name,
                'interestRate' => $value->rate,
                'payout' => $value->payout,
                'interest' => $value->alpine_interest
            ];
        }

        return $data;
    }

    public function getInHouseMortgages($propertyId) {
        
        $query = "select b.mortgage_id id, position, b.current_balance balance, b.monthly_pmt,
                         b.term_length, b.mortgage_code, 
                         ifnull(c.name, 'Private Investor') lender_name, b.interest_rate, b.tobe_paidout
                    from mortgage_properties_table a
                    join mortgage_table b on a.mortgage_id = b.mortgage_id
               left join alpine_companies_table c on b.company_id = c.id
                   where a.property_id = ?
                     and b.transfer_id is not null
                     and b.current_balance > 0
                     and b.is_deleted = 'no'
                order by position";
        $res = $this->db->select($query, [$propertyId]);

        $data = array();
        foreach ($res as $key => $value) {
            $fundNames = Utils::getFundNames();

            $query = "select interest_rate
                        from mortgage_interest_rates_table
                       where mortgage_id = ?
                         and term_start <= now()
                         and term_end >= now()";
            $interestRateRes = $this->db->select($query, [$value->id]);

            $data[] = [
                'id' => $value->id,
                'position' => $value->position,
                'balance' => (float) $value->balance,
                'payment' => $value->monthly_pmt,
                'lenderName' => $fundNames[$value->lender_name] ?? $value->lender_name,
                'interestRate' => $value->interest_rate,
                'currentInterestRate' => $interestRateRes[0]->interest_rate ?? $value->interest_rate,
                'payout' => $value->tobe_paidout == 'no' ? 'No' : 'Yes',
                'term' => $value->term_length,
                'mortgageCode' => $value->mortgage_code
            ];
        }

        return $data;
    }

    public function getLtvByQuote($savedQuoteId) {

        $savedQuoteTable = SavedQuoteTable::query()
            ->where('saved_quote_id', $savedQuoteId)
            ->first();

        if ($savedQuoteTable) {
            $applicationId = $savedQuoteTable->application_id;
            $gross         = $savedQuoteTable->gross;
        } else {
            return null;
        }

        $sql = "select d.balance 
                  from saved_quote_table a
                  join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                  join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                  join property_mortgages_table d on d.property_id = c.property_id 
                 where a.saved_quote_id = ?
                   and c.part_of_security = 'Yes'";
        $res = $this->db->select($sql, [$savedQuoteId]);

        $alpineBalance = 0;
        foreach ($res as $key => $value) {
            $alpineBalance += $value->balance;
        }

        $sql = "select a.property_id, a.alpine_interest, a.appraised_value, a.assessed_value, a.customer_value, a.estimate_value,
                       (select sum(b.balance)
                          from property_mortgages_table b
                         where a.property_id = b.property_id
                           and b.payout = 'No') other_balance
                  from property_table a
                 where a.application_id = ?
                   and a.part_of_security = 'Yes'
              order by a.property_id";
        $res = $this->db->select($sql, [$applicationId]);

        $combinedPropertyValue = 0;
        $propertyValues = [
            'appraised' => 0,
            'assessed' => 0,
            'customer' => 0,
        ];
        foreach ($res as $key => $value) {
            if ($value->appraised_value > 0) {
                $combinedPropertyValue += $value->appraised_value * ($value->alpine_interest / 100);
            } elseif ($value->assessed_value > 0) {
                $combinedPropertyValue += $value->assessed_value * ($value->alpine_interest / 100);
            } elseif ($value->customer_value > 0) {
                $combinedPropertyValue += $value->customer_value * ($value->alpine_interest / 100);
            }

            $propertyValues['appraised'] += $value->appraised_value * ($value->alpine_interest / 100);
            $propertyValues['assessed'] += $value->assessed_value * ($value->alpine_interest / 100);
            $propertyValues['customer'] += $value->customer_value * ($value->alpine_interest / 100);
        }

        $ltv = $combinedPropertyValue == 0 ? 0 : (($alpineBalance + $gross) / $combinedPropertyValue) * 100;

        $byMethod = array();
        $byMethod['appraised'] = $propertyValues['appraised'] == 0 ? 0 : (($alpineBalance + $gross) / $propertyValues['appraised']) * 100;
        $byMethod['assessed'] = $propertyValues['assessed'] == 0 ? 0 : (($alpineBalance + $gross) / $propertyValues['assessed']) * 100;
        $byMethod['customer'] = $propertyValues['customer'] == 0 ? 0 : (($alpineBalance + $gross) / $propertyValues['customer']) * 100;

        return ['ltv' => $ltv, 'byMethod' => $byMethod];
    }

    public function getAllQuoteLtv($applicationId) {
        $query = "select a.saved_quote_id, group_concat(b.position) positions
                    from saved_quote_table a
                    join saved_quote_positions_table b on a.saved_quote_id = b.saved_quote_id
                   where a.application_id = ?
                     and a.disburse = 'Yes'
                group by a.saved_quote_id
                order by a.saved_quote_id";
        $res = $this->db->select($query, [$applicationId]);

        $data = array();
        foreach ($res as $key => $value) {
            $data[] = [
                'id' => $value->saved_quote_id,
                'positions' => $value->positions,
                'ltvs' => $this->getLtvByQuote($value->saved_quote_id)['byMethod']
            ];
        }

        return $data;
    }

    public function getMortgages($applicationId) {
        $sql = "SELECT mortgage_id, mortgage_code 
                  FROM mortgage_table a 
                 WHERE a.application_id = ? 
                   AND a.company_id IN (1, 3, 401, 2022, 701, 801) 
                   AND a.current_balance > 0
                   AND a.is_deleted = 'no'
                   AND a.ab_loan not in ('c_mtg')
              order by mortgage_code";
        $res = $this->db->select($sql, [$applicationId]);

        $data = array();
        foreach ($res as $key => $value) {
            $data[] = [
                'id' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code
            ];
        }

        return $data;
    }

    public function getMortgagePayments($mortgageId) {

        $query = "SELECT a.id, a.payment_id, a.mortgage_id, a.processing_date, a.payment_amount, a.interest,
                         a.service_charge, a.principal_amount, a.balance, a.interest_rate, a.comment
                    FROM mortgage_balance_history a
                   WHERE a.mortgage_id = ?
                ORDER BY a.sequence_id";
        $result = $this->db->select($query, [$mortgageId]);

        $payments = [];
        foreach ($result as $key => $value) {
            $payments[] = [
                'id' => $value->id,
                'paymentId' => $value->payment_id,
                'processingDate' => $value->processing_date,
                'paymentAmount' => $value->payment_amount,
                'interest' => $value->interest,
                'serviceCharge' => $value->service_charge,
                'principalPayment' => $value->principal_amount,
                'outstandingBalance' => $value->balance,
                'monthlyInterest' => $value->interest_rate,
                'comment' => $value->comment,
            ];
        }

        return $payments;
    }

    public function getUpcomingPayments($mortgageId) {

        $query = "SELECT a.payment_id, a.processing_date, a.original_date, a.pmt_amt
                    FROM mortgage_payments_table a
                   WHERE a.mortgage_id = ?
                     AND a.is_processed = 'no'
                ORDER BY a.processing_date, a.payment_id";
        $result = $this->db->select($query, [$mortgageId]);

        $payments = [];
        foreach ($result as $key => $value) {
            $payments[] = [
                'paymentId' => $value->payment_id,
                'processingDate' => $value->processing_date,
                'originalDate' => $value->original_date,
                'paymentAmount' => $value->pmt_amt
            ];
        }

        return $payments;
    }

    public function getRenewals($mortgageId) {

        $query = "SELECT a.renewal_id, a.renewal_date, a.renewal_fee, a.new_interest_rate, a.new_monthly_pmt, a.comments
                    FROM mortgage_renewals_table a
                   WHERE a.mortgage_id = ?
                ORDER BY a.renewal_date, a.renewal_id";
        $result = $this->db->select($query, [$mortgageId]);

        $payments = [];
        foreach ($result as $key => $value) {
            $payments[] = [
                'renewalId' => $value->renewal_id,
                'renewalDate' => $value->renewal_date,
                'renewalFee' => $value->renewal_fee,
                'newInterestRate' => $value->new_interest_rate,
                'newPaymentAmount' => $value->new_monthly_pmt,
                'comments' => $value->comments,
            ];
        }

        return $payments;
    }

    public function getProperties($mortgageId) {

        $query = "select a.*, b.*
                    from mortgage_properties_table a
                    join property_table b on a.property_id = b.property_id
                   where a.mortgage_id = ?
                order by b.idx";
        $result = $this->db->select($query, [$mortgageId]);

        $properties = [];

        foreach ($result as $key => $value) {

            $address = Utils::oneLineAddress($value->unit_number, $value->street_number, $value->street_name, $value->street_type, $value->street_direction, $value->city, $value->province, $value->postal_code);

            $propertyMortgages = $this->getPropertyMortgagesTable($value->property_id);

            if (isset($value->value_method) && $value->value_method == 'Appraisal') {
                $valueMethod = $value->appraised_value;
                $apprBy = AppraisalFirmsTable::where('appraisal_firm_code', $value->appraisal_firm_id)->value('name');
                $appraisalDateOrdered = $value->appraisal_date_ordered;
                $appraisalDateReceived = $value->appraisal_date_received;
            } else {
                $valueMethod = 'N/A';
                $apprBy = 'N/A';
                $appraisalDateOrdered = 'N/A';
                $appraisalDateReceived = 'N/A';
            }

            $customerValue = $value->customer_value;

            $properties[] = [
                'id' => $value->property_id,
                'titleHolders' => $value->title_holders,
                'interest' => $value->alpine_interest,
                'ownRent' => $value->own_rent,
                'sameAsMailing' => $value->same_as_mailing,
                'position' => $value->position,
                'address' => $address,
                'numberOfYears' => $value->number_of_years,
                'pid' => $value->pid,
                'legal' => $value->legal,
                'pptyTax' => $value->ppty_tax,
                'pptyTaxArrears' => $value->ppty_tax_arrears,
                'insArrears' => $value->ins_arrears,
                'costPrice' => $value->cost_price,
                'downpayment' => $value->downpayment,
                'customerValue' => $customerValue,
                'assValue' => $value->assessed_value,
                'landValue' => $value->land_value,
                'buildingValue' => $value->building_value,

                'valueMethod' => $valueMethod,
                'apprBy' => $apprBy,
                'appraisalDateOrdered' => $appraisalDateOrdered,
                'appraisalDateReceived' => $appraisalDateReceived,
                'built' => $value->built,
                'lotSize' => $value->lot_size,
                'floorArea' => $value->floor_area,
                'basement' => $value->basement,
                'bedrooms' => $value->bedrooms,
                'bathrooms' => $value->bathrooms,
                'roofing' => $value->roofing,
                'exteriorFinishing' => $value->exterior_finishing,
                'houseStyle' => $value->house_style,
                'garage' => $value->garage,
                'outBuilding' => $value->out_building,
                'waterSource' => $value->water_source,
                'sewage' => $value->sewage,
                'other' => $value->comments,
                'showPropertyMortgages' => $this->showPropertyMortgages,
                'propertyMortgages' => $propertyMortgages
            ];
        }

        return $properties;
    }

    public function getPropertyMortgagesTable($propertyId) {

        $propertyMortgages = array();
        $this->showPropertyMortgages = false;
        $propertyMortgagesTable = PropertyMortgagesTable::query()
            ->where('property_id', $propertyId)
            ->get();

        foreach ($propertyMortgagesTable as $key => $value) {

            if ($value->balance > 0) {
                $this->showPropertyMortgages = true;
            }

            $propertyMortgages[] = [
                'id' => $value->mortgage_id,
                'balance' => $value->balance,
                'payment' => $value->payment,
                'paymentType' => $value->payment_type,
                'term' => $value->term,
                'rate' => $value->rate,
                'lenderId' => $value->lender_id,
                'payout' => $value->payout
            ];
        }

        return $propertyMortgages;
    }

    public function getMortgagors($mortgageId) {

        $this->logger->info('MortgageBO->getMortgagors', [$mortgageId]);

        $mortgageMortgagorsTable = MortgageMortgagorsTable::query()
            ->where('mortgage_id', $mortgageId)
            ->get();

        $mortgagors = [];

        foreach ($mortgageMortgagorsTable as $rowClientInfo) {

            if ($rowClientInfo->applicant_id == 0) {

                $corporationTable = $this->getCorporationTable($rowClientInfo->corp_id);

                if ($corporationTable) {
                    $mortgagors[] = [
                        'firstName' => $corporationTable->name,
                        'lastName' => '',
                        'gender' => '',
                        'age' => '',
                        'beacon' => '',
                        'homePhone' => $corporationTable->phone,
                        'email' => $corporationTable->email,
                        'type' => $rowClientInfo->type,
                    ];
                }
            } else {

                $query = 'select a.home_phone, a.home_fax, a.home_email, b.f_name, b.l_name, b.p_name, b.gender, b.dob, b.beacon_score 
                          from applicant_table a
                          join spouse_table b on (a.spouse1_id = b.spouse_id or a.spouse2_id = b.spouse_id)
                          where a.applicant_id = ?
                          and b.spouse_id = ?';
                $data = $this->db->select($query, [$rowClientInfo->applicant_id, $rowClientInfo->spouse_id]);

                foreach ($data as $key => $value) {

                    $age = '';
                    if ($value->dob && $value->dob != '0000-00-00' && $value->dob != '0000-00-00 00:00:00') {
                        $dob = Carbon::parse($value->dob);
                        $age = $dob->age;
                    }
                    $mortgagors[] = [
                        'firstName' => $value->f_name,
                        'lastName' => $value->l_name,
                        'gender' => $value->gender,
                        'age' => $age,
                        'beacon' => $value->beacon_score,
                        'homePhone' => $value->home_phone,
                        'email' => $value->home_email,
                        'type' => $rowClientInfo->type,
                    ];
                }
            }
        }

        return $mortgagors;
    }

    public function getCorporationTable($corporationId) {

        $corporationTable = CorporationTable::query()
            ->where('corporation_id', $corporationId)
            ->first();

        return $corporationTable;
    }

    public function getInvestorTracking($mortgageId) {

        $investors = array();

        $query = "select * 
                    from mortgage_investor_tracking_table
                   where mortgage_id = ?
                order by
                         case investor_id
                             WHEN 31 THEN 1
                             WHEN 248 THEN 2
                             WHEN 100 THEN 3
                             WHEN 1971 THEN 4
                             ELSE investor_id
                         end";
        $res = $this->db->select($query, [$mortgageId]);

        foreach ($res as $key => $value) {
            $investor = $this->getInvestorName($mortgageId, $value->investor_tracking_id);

            $investors[] = [
                'quoteDate' => $value->quote_date,
                'investorName' => $investor,
                'expectedDate' => $value->expected_date,
                'committed' => $value->committed,
                'salePrice' => $value->sale_price,
                'discount' => $value->discount,
                'yield' => $value->yield,
                'quoteComment' => $value->quote_comment,
            ];
        }

        return $investors;
    }

    public function getInvestorName($mortgageId, $InvestorTrackingId) {

        $investorName =  '';

        $query = 'select b.first_name, b.last_name 
                  from mortgage_investor_tracking_investors_table a
                  join investor_table b on b.investor_id = a.investor_id
                  where mortgage_id = ?
                  and investor_tracking_id = ?';
        $res = $this->db->select($query, [$mortgageId, $InvestorTrackingId]);

        if ($res && isset($res[0]->first_name)) {
            $investorName =  $res[0]->first_name . ' ' . $res[0]->last_name;
        }

        return $investorName;
    }

    public function calculatePayout($mortgageId, $payoutDate, $payoutMIP, $payoutDischarge, $payoutLegal, $payoutMisc) {

        $this->logger->info('MortgageBO->calculatePayout', [$mortgageId, $payoutDate, $payoutMIP, $payoutDischarge, $payoutLegal, $payoutMisc]);

        $mortgageTable = $this->getMortgageTable($mortgageId);
        $mortgagePaymentsTable = $this->getMortgagePaymentsTable($mortgageId);
        $payout = [];

        if ($mortgageTable && $mortgagePaymentsTable) {

            foreach ($mortgagePaymentsTable as $payment) {
                if ($payment->is_processed == 'yes') {
                    $lastPayment = $payment;
                    $processingDate = $payment->processing_date;
                }
            }

            $mortgageBalanceHistory = $this->getLastMortgageBalanceHistory($mortgageId, $lastPayment->payment_id);

            $grossAmount = $mortgageTable->gross_amt;
            $interestRate = $mortgageBalanceHistory->interest_rate;

            //Interest Accrued
            $lastBalanceAux = $mortgageBalanceHistory->balance;
            $monthlyRate = 0;

            //Check for any pending payments up to the Payout date
            foreach ($mortgagePaymentsTable as $payment) {
                if ($payment->is_processed == 'no' && $payment->processing_date < $payoutDate) {
                    $monthlyRate = (($lastBalanceAux * ($interestRate / 100)) / 360) * 30;
                    $lastBalanceAux += $monthlyRate;
                    $processingDate = $payment->processing_date;
                }
            }

            if ($lastBalanceAux > 0) {
                // If there are any pending payments up to the Payout date
                $lastBalance = $lastBalanceAux;
            } else {
                // If there are no pending payments up to the Payout date
                $lastBalance = $mortgageBalanceHistory->balance;
            }

            $monthlyIntAccrued = (($lastBalance * ($interestRate / 100)) / 360) * 30;
            $days = Carbon::parse($payoutDate)->diffInDays(Carbon::parse($processingDate));
            $interestAccrued = $monthlyIntAccrued / 30 * $days;

            //MIP
            $this->logger->info('interestRate', [$interestRate]);
            $monthlyInt   = ((($lastBalance + $interestAccrued) * ($interestRate / 100)) / 360) * 30;
            $this->logger->info('monthlyInt', [$monthlyInt]);
            $this->logger->info('payoutMIP', [$payoutMIP]);
            $mip = $monthlyInt * $payoutMIP;
            $this->logger->info('mip', [$mip]);


            $payoutAmount = $lastBalance + $mip + $payoutDischarge + $payoutLegal + $payoutMisc + $interestAccrued;

            if (is_null($mip)) {
                $mip = 0;
            }
            if (is_null($payoutDischarge)) {
                $payoutDischarge = 0;
            }
            if (is_null($payoutLegal)) {
                $payoutLegal = 0;
            }
            if (is_null($payoutMisc)) {
                $payoutMisc = 0;
            }
            if (is_null($interestAccrued)) {
                $interestAccrued = 0;
            }
            if (is_null($lastBalance)) {
                $lastBalance = 0;
            }

            $payout = [
                'lastBalance' => $lastBalance,
                'mip' => $mip,
                'discharge' => $payoutDischarge,
                'legal' => $payoutLegal,
                'misc' => $payoutMisc,
                'interestAccrued' => $interestAccrued,
                'payoutAmount' => $payoutAmount
            ];
        }

        return $payout;
    }

    public function getLastMortgageBalanceHistory($mortgageId, $paymentId) {

        $this->logger->info('MortgageBO->getLastMortgageBalanceHistory', [$mortgageId, $paymentId]);

        $mortgageBalanceHistory = MortgageBalanceHistory::query()
            ->where('mortgage_id', $mortgageId)
            ->where('payment_id', $paymentId)
            ->orderBy('id', 'desc')
            ->first();

        return $mortgageBalanceHistory;
    }

    public function getMortgagePaymentTable($mortgageId, $paymentId)
    {

        $mortgagePaymentsTable = MortgagePaymentsTable::query()
            ->where('mortgage_id', $mortgageId)
            ->where('payment_id', $paymentId)
            ->first();

        return $mortgagePaymentsTable;
    }

    public function getMortgagePaymentsTable($mortgageId)
    {

        $mortgagePaymentsTable = MortgagePaymentsTable::query()
            ->where('mortgage_id', $mortgageId)
            ->get();

        return $mortgagePaymentsTable;
    }

    public function getActiveMortgages($applicationId) {

        $activeMortgages = array();

        $data = MortgageTable::query()
            ->where('application_id', $applicationId)
            ->where('is_deleted', 'no')
            ->where('current_balance', '>', 0)
            ->get();

        foreach ($data as $mortgageTable) {

            $query = 'SELECT interest_rate FROM mortgage_interest_rates_table WHERE mortgage_id = ' . $mortgageTable->mortgage_id . ' AND term_start<=NOW() AND term_end>=NOW()';
            $res = $this->db->select($query);

            $currentRate = null;

            if ($res) {
                $currentRate = $res[0]->interest_rate;
            }

            $fpdTime = strtotime($mortgageTable->first_pmt_due_date);
            $tmpstr = date("m/d/Y", $this->dateAdd("m", $mortgageTable->term_length - 1, $fpdTime));

            $activeMortgages[] = [
                'id' => $mortgageTable->mortgage_id,
                'mortgageCode' => $mortgageTable->mortgage_code,
                'grossAmount' => $mortgageTable->gross_amt,
                'currentBalance' => $mortgageTable->current_balance,
                'monthlyPayment' => $mortgageTable->monthly_pmt,
                'interestRate' => $mortgageTable->interest_rate,
                'firstPaymentDueDate' => $mortgageTable->first_pmt_due_date,
                'termLength' => $mortgageTable->term_length,
                'toBePaidOut' => $mortgageTable->tobe_paidout,
                'currentRate' => $currentRate,
                'term' => $tmpstr
            ];
        }

        return $activeMortgages;
    }

    public function dateAdd($interval, $number, $date) {

        $dateTimeArray = getdate($date);
        $hours = $dateTimeArray['hours'];
        $minutes = $dateTimeArray['minutes'];
        $seconds = $dateTimeArray['seconds'];
        $month = $dateTimeArray['mon'];
        $day   = $dateTimeArray['mday'];
        $year  = $dateTimeArray['year'];

        switch ($interval) {
            case 'm':
                $month += $number;
                break;
        }

        $timestamp = mktime($hours, $minutes, $seconds, $month, $day, $year);

        return $timestamp;
    }

    public function saveToBePaidOut($mortgageId, $toBePaidOut) {

        $this->logger->info('MortgageBO->saveToBePaidOut', [$mortgageId, $toBePaidOut]);

        $mortgageTable = MortgageTable::query()
            ->where('mortgage_id', $mortgageId)
            ->first();

        if ($mortgageTable) {

            $mortgageTable->tobe_paidout = $toBePaidOut;
            $mortgageTable->save();

            return true;
        }

        return false;
    }


    public function updateInsurance($mortgageId, $insurance) {

        $this->logger->info('MortgageBO->updateInsurance', [$mortgageId, $insurance]);

        $mortgageTable = MortgageTable::query()
            ->where('mortgage_id', $mortgageId)
            ->first();

        if ($mortgageTable) {

            $mortgageTable->insurance = $insurance;
            $mortgageTable->save();

            return true;
        }

        return false;
    }

    public function updateEarthquake($mortgageId, $earthquake) {

        $this->logger->info('MortgageBO->updateEarthquake', [$mortgageId, $earthquake]);

        $mortgageTable = MortgageTable::query()
            ->where('mortgage_id', $mortgageId)
            ->first();

        if ($mortgageTable) {

            $mortgageTable->earthquake = $earthquake;
            $mortgageTable->save();

            return true;
        }

        return false;
    }


}
