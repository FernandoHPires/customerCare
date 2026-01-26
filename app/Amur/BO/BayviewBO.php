<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Storage;
use gnupg;

class BayviewBO {

    private $logger;
    private $db;
    //private $companyId = 5;
    //private $investorId = 31;
    private $companyId = 2042;
    private $investorId = 2042;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function transferDailyReports() {
        $this->logger->info('BayviewBO->transferDailyReports - Transfer started');

        $commitment = $this->getCommitmentReport('E');
        $acquisition = $this->getAcquisitionReport('E');

        //$commitment = $this->getCommitmentReport('C');
        //$acquisition = $this->getAcquisitionReport('C');

        $cFileName = (new DateTime())->format('YmdHis') . '-commitment.csv.pgp';
        $aFileName = (new DateTime())->format('YmdHis') . '-acquisition.csv.pgp';

        Storage::disk('bayview')->put('/inbound/' . $cFileName, Storage::get($commitment['fileName']));
        Storage::disk('bayview')->put('/inbound/' . $aFileName, Storage::get($acquisition['fileName']));

        $this->logger->info('BayviewBO->transferDailyReports - Transfer finished',[$commitment['fileName'], $cFileName, $acquisition['fileName'], $aFileName]);
    }

    //Remittance---------------------------------------------
    public function getRemittanceReport($type = null) {
        if(is_null($type)) {
            return $this->getRemittanceData();

        } elseif($type == 'C') {
            return $this->getCSV('A');

        } elseif($type == 'E') {
            return $this->getPGP('A');

        }

        return null;
    }

    public function getRemittanceData() {
        $sql = "";
        $res = $this->db->select($sql,[$this->investorId]);

        $maxProperties = 0;
        $rows = array();
        foreach($res as $key => $value) {
            $properties = $this->getProperties(null, $value->mortgage_id);
            if(count($properties) > $maxProperties) $maxProperties = count($properties);

            $maturityDate = new DateTime($value->first_pmt_due_date);
            $maturityDate->add(new DateInterval('P' . ($value->term_length > 0 ? $value->term_length - 1 : 0) . 'M'));

            $quoteDate = new DateTime($value->quote_date);

            $purchaseFactor = (($value->yield - $value->interest_rate) * ($value->term_length / 12)) + 100;

            $acquisitonDate = $value->funding_date == '0000-00-00' ? '' : (new DateTime($value->funding_date))->format('m/d/Y');

            $selfEmployed = $this->getSelfEmployed($value->application_id) ? 'Y' : 'N';

            $interestAdjustment = $this->getInterestAdjustment(
                $value->gross_amt,
                $value->interest_rate,
                $value->compounding,
                $value->funding_date,
                $value->int_comm_date
            );

            $rows[] = [
                (new DateTime())->format('m/d/Y'),
                
            ];
        }

        foreach($rows as $key => $row) {
            for($i = 0; $i < $maxProperties; $i++) {
                $rows[$key][] = $row['properties'][$i]['address'] ?? '';
                $rows[$key][] = $row['properties'][$i]['city'] ?? '';
                $rows[$key][] = $row['properties'][$i]['province'] ?? '';
                $rows[$key][] = $row['properties'][$i]['postal_code'] ?? '';
                $rows[$key][] = is_null($row['properties'][$i]['appraisal_date'] ?? null) ? '' : $row['properties'][$i]['appraisal_date']->format('m/d/Y');
                $rows[$key][] = $row['properties'][$i]['appraised_value'] ?? '';
                $rows[$key][] = Utils::toOrdinal($row['properties'][$i]['position'] ?? '');
                $rows[$key][] = $row['properties'][$i]['property_type'] ?? '';
                $rows[$key][] = $row['properties'][$i]['occupancy_type'] ?? '';
                $rows[$key][] = 'N/A';
            }

            unset($rows[$key]['properties']);
        }

        $columns = array();
        $columns[] = ['type' => '', 'name' => 'ReportDate'];
        

        for($i = 1; $i <= $maxProperties; $i++) {
            $columns[] = ['type' => '', 'name' => 'Address' . $i];
            $columns[] = ['type' => '', 'name' => 'City' . $i];
            $columns[] = ['type' => '', 'name' => 'Province' . $i];
            $columns[] = ['type' => '', 'name' => 'PostalCode' . $i];
            $columns[] = ['type' => '', 'name' => 'DtOriginalAppraisal' . $i];
            $columns[] = ['type' => 'D', 'name' => 'ValAppraisalOrig' . $i];
            $columns[] = ['type' => '', 'name' => 'LienPosition' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyType' . $i];
            $columns[] = ['type' => '', 'name' => 'OccupancyType' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyNumUnits' . $i];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    //Trial Balance---------------------------------------------
    public function getTrialBalanceReport($type = null) {
        if(is_null($type)) {
            return $this->getTrialBalanceData();

        } elseif($type == 'C') {
            return $this->getCSV('T');

        } elseif($type == 'E') {
            return $this->getPGP('T');

        }

        return null;
    }

    public function getTrialBalanceData() {
        $today = new DateTime();

        if($today->format('d') >= 15) {
            $startDate = new DateTime($today->format('Y-m-1'));
            $endDate = new DateTime($today->format('Y-m-14'));
        } else {
            $startDate = new DateTime($today->format('Y-m-1'));
            $startDate->sub(new DateInterval('P1M'));
            $startDate = new DateTime($today->format('Y-m-15'));
            $endDate = new DateTime($today->format('Y-m-t'));
        }

        $sql = "select c.comment,
                       a.application_id, b.mortgage_code, a.mortgage_id, a.mortgage_code lender_code, c.processing_date, a.funding_date, a.gross_amt,
                       fn_GetOSBByMortgageID(a.mortgage_id) osb, a.monthly_pmt, a.interest_rate, a.amortization, a.initial_ltv, a.ltv, a.gdsr, a.tdsr, a.dscr,
                       e.name loan_purpose, a.term_length, a.first_pmt_due_date
                  from mortgage_table a
                  join mortgage_table b on a.transfer_id = b.mortgage_id
                  join mortgage_payments_table c on a.mortgage_id = c.mortgage_id
                  join application_table d on a.application_id = d.application_id
             left join category_value e on d.purpose_category_id = e.id
                 where a.company_id = ?
                   and c.processing_date between '2023-05-01' and '2023-05-31'
                   and c.is_processed = 'yes'
                   and c.initial_pmt = 'no'
                   and c.is_sale = 'no'
                   and c.transfer_mortgage = 'no'
              order by c.comment, a.mortgage_id, c.processing_date";
        $res = $this->db->select($sql,[$this->companyId]);

        $maxProperties = 0;
        $rows = array();
        foreach($res as $key => $value) {
            $properties = $this->getProperties(null, $value->mortgage_id);
            if(count($properties) > $maxProperties) $maxProperties = count($properties);

            $closingDate = $value->funding_date == '0000-00-00' ? '' : (new DateTime($value->funding_date))->format('m/d/Y');

            $nextPayment = $this->getNextPayment($value->mortgage_id);

            $maturityDate = new DateTime($value->first_pmt_due_date);
            $maturityDate->add(new DateInterval('P' . ($value->term_length > 0 ? $value->term_length - 1 : 0) . 'M'));

            $collection = $this->getCollectionStatus($value->application_id, $value->mortgage_id);
            if(!is_null($collection['collectionDate'])) {
                $collectionStatus = $collection['collectionStatus'];
                $collectionDays = ((new DateTime())->diff($collection['collectionDate']))->days;
            } else {
                $collectionStatus = '';
                $collectionDays = '';
            }

            $futureOSB = $this->getFutureOSB($value->mortgage_id, $value->interest_rate, $value->osb);
            $futureOSBAmount = $futureOSB['futureOSBAmount'];
            $remainingPayments = $futureOSB['remainingPayments'];

            $rows[] = [
                $value->comment,
                (new DateTime())->format('m/d/Y'),
                $value->application_id,
                $value->mortgage_code,
                $value->lender_code,
                $closingDate,
                $value->gross_amt,
                $value->osb,
                $value->osb,
                $value->monthly_pmt,
                'N/A',
                'pending to confim',
                12,
                is_null($nextPayment['paymentDate']) ? '' : $nextPayment['paymentDate']->format('m/d/Y'),
                $value->interest_rate,
                'Fixed',
                $value->loan_purpose,
                'Residential',
                $value->term_length,
                $value->amortization * 12,
                $remainingPayments, //TermRemaining
                'pending Sonal', //RemainingAmortization
                $value->initial_ltv,
                $value->ltv,
                $this->getLowestBeaconScore($value->application_id),
                $maturityDate->format('m/d/Y'),
                'N/A',
                'N/A',
                'pending Sonal', //ServiceFeeRate
                $value->gdsr,
                $value->tdsr,
                $value->dscr,
                $collectionStatus,
                'N/A',
                'pending', //ArrearsAmount
                'pending', //NumberOfPaymentsMissed
                $collectionDays,
                $futureOSBAmount,

                'properties' => $properties
            ];
        }

        foreach($rows as $key => $row) {
            for($i = 0; $i < $maxProperties; $i++) {
                $rows[$key][] = $row['properties'][$i]['address'] ?? '';
                $rows[$key][] = $row['properties'][$i]['city'] ?? '';
                $rows[$key][] = $row['properties'][$i]['province'] ?? '';
                $rows[$key][] = $row['properties'][$i]['postal_code'] ?? '';
                $rows[$key][] = $row['properties'][$i]['property_type'] ?? '';
                $rows[$key][] = $row['properties'][$i]['occupancy_type'] ?? '';
                $rows[$key][] = Utils::toOrdinal($row['properties'][$i]['position'] ?? '');
                
                //$rows[$key][] = is_null($row['properties'][$i]['appraisal_date'] ?? null) ? '' : $row['properties'][$i]['appraisal_date']->format('m/d/Y');
                //$rows[$key][] = $row['properties'][$i]['appraised_value'] ?? '';
                //$rows[$key][] = 'N/A';
            }

            unset($rows[$key]['properties']);
        }

        $columns = array();
        $columns[] = ['type' => '', 'name' => 'Comment'];
        $columns[] = ['type' => '', 'name' => 'ReportDate'];
        $columns[] = ['type' => '', 'name' => 'LoanNumberTPO'];
        $columns[] = ['type' => '', 'name' => 'MortgageCode'];
        $columns[] = ['type' => '', 'name' => 'InvestorLoanNumber '];
        $columns[] = ['type' => '', 'name' => 'ClosingDate'];
        $columns[] = ['type' => 'D', 'name' => 'LoanOriginalPrincipal'];
        $columns[] = ['type' => 'D', 'name' => 'CurrentBalance'];
        $columns[] = ['type' => 'D', 'name' => 'PrincipalBalance'];
        $columns[] = ['type' => 'D', 'name' => 'PrinIntPayment'];
        $columns[] = ['type' => '', 'name' => 'TaxPayment'];
        $columns[] = ['type' => '', 'name' => 'PaidThroughDate'];
        $columns[] = ['type' => '', 'name' => 'PaymentFreqMths'];
        $columns[] = ['type' => '', 'name' => 'NextPaymentDueDate'];
        $columns[] = ['type' => 'D', 'name' => 'InterestRate'];
        $columns[] = ['type' => '', 'name' => 'RateType'];
        $columns[] = ['type' => '', 'name' => 'LoanPurpose'];
        $columns[] = ['type' => '', 'name' => 'LoanType'];
        $columns[] = ['type' => '', 'name' => 'TermOriginal'];
        $columns[] = ['type' => 'D', 'name' => 'AmortizationOriginal'];
        $columns[] = ['type' => '', 'name' => 'TermRemaining'];
        $columns[] = ['type' => '', 'name' => 'RemainingAmortization'];
        $columns[] = ['type' => 'D', 'name' => 'LTVOriginal'];
        $columns[] = ['type' => 'D', 'name' => 'LTVCurrent'];
        $columns[] = ['type' => '', 'name' => 'QualifyingFICO'];
        $columns[] = ['type' => '', 'name' => 'CurrentMaturityDate'];
        $columns[] = ['type' => '', 'name' => 'CurrentTaxBalance'];
        $columns[] = ['type' => '', 'name' => 'SuspenseBalance'];
        $columns[] = ['type' => '', 'name' => 'ServiceFeeRate'];
        $columns[] = ['type' => 'D', 'name' => 'GDS'];
        $columns[] = ['type' => 'D', 'name' => 'TDS'];
        $columns[] = ['type' => 'D', 'name' => 'DSCR'];
        $columns[] = ['type' => '', 'name' => 'AccountStatus'];
        $columns[] = ['type' => '', 'name' => 'DischargeReason'];
        $columns[] = ['type' => '', 'name' => 'ArrearsAmount'];
        $columns[] = ['type' => '', 'name' => 'NumberOfPaymentsMissed'];
        $columns[] = ['type' => '', 'name' => 'DaysInArrears'];
        $columns[] = ['type' => 'D', 'name' => 'RenewalAmount'];
        

        for($i = 1; $i <= $maxProperties; $i++) {
            $columns[] = ['type' => '', 'name' => 'Address' . $i];
            $columns[] = ['type' => '', 'name' => 'City' . $i];
            $columns[] = ['type' => '', 'name' => 'Province' . $i];
            $columns[] = ['type' => '', 'name' => 'PostalCode' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyType' . $i];
            $columns[] = ['type' => '', 'name' => 'OccupancyType' . $i];
            $columns[] = ['type' => '', 'name' => 'LienPosition' . $i];

            //$columns[] = ['type' => '', 'name' => 'DtOriginalAppraisal' . $i];
            //$columns[] = ['type' => 'D', 'name' => 'ValAppraisalOrig' . $i];
            //$columns[] = ['type' => '', 'name' => 'PropertyNumUnits' . $i];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    //Acquisition---------------------------------------------
    public function getAcquisitionReport($type = null) {
        if(is_null($type)) {
            return $this->getAcquisitionData();

        } elseif($type == 'C') {
            return $this->getCSV('A');

        } elseif($type == 'E') {
            return $this->getPGP('A');

        }

        return null;
    }

    public function getAcquisitionData() {
        $sql = "select a.application_id,
                       a.mortgage_id,
                       b.sale_price,
                       a.gross_amt,
                       a.mortgage_code,
                       a.interest_rate,
                       a.term_length,
                       a.amortization,
                       a.ltv,
                       e.name loan_purpose,
                       f.name product_category_low,
                       g.date quote_date,
                       d.funding_date,
                       fn_GetIncomeByApplicationID(a.application_id) total_income,
                       fn_GetIncomeOthersByApplicationID(a.application_id) rental_income,
                       b.yield,
                       a.monthly_pmt,
                       a.first_pmt_due_date,
                       b.discount,
                       h.name loan_category,
                       a.gdsr,
                       a.tdsr,
                       a.dscr,
                       a.int_comm_date,
                       a.compounding
                  from mortgage_table a
                  join mortgage_investor_tracking_table b on a.mortgage_id = b.mortgage_id
             left join mortgage_table c on c.transfer_id = a.mortgage_id and c.is_deleted = 'no'
                  join application_table d on d.application_id = a.application_id
             left join category_value e on d.purpose_category_id = e.id
             left join category_value f on a.mortgage_type = f.id
                  join saved_quote_table g on a.mortgage_id = g.mortgage_id
             left join category_value h on a.loan_category = h.id
                 where b.investor_id = ?
                   and b.committed = 'Yes'
                   and c.transfer_id is null
                   and a.company_id in (1,3,401,2022,701)
                   and a.is_deleted = 'no'
                   and a.ab_loan = 'No'
              order by a.application_id, a.mortgage_id";
        $res = $this->db->select($sql,[$this->investorId]);

        $maxProperties = 0;
        $rows = array();
        foreach($res as $key => $value) {
            $properties = $this->getProperties(null, $value->mortgage_id);
            if(count($properties) > $maxProperties) $maxProperties = count($properties);

            $maturityDate = new DateTime($value->first_pmt_due_date);
            $maturityDate->add(new DateInterval('P' . ($value->term_length > 0 ? $value->term_length - 1 : 0) . 'M'));

            $quoteDate = new DateTime($value->quote_date);

            $purchaseFactor = (($value->yield - $value->interest_rate) * ($value->term_length / 12)) + 100;

            $acquisitonDate = $value->funding_date == '0000-00-00' ? '' : (new DateTime($value->funding_date))->format('m/d/Y');

            $selfEmployed = $this->getSelfEmployed($value->application_id) ? 'Y' : 'N';

            $interestAdjustment = $this->getInterestAdjustment(
                $value->gross_amt,
                $value->interest_rate,
                $value->compounding,
                $value->funding_date,
                $value->int_comm_date
            );

            $rows[] = [
                (new DateTime())->format('m/d/Y'),
                'Amur Financial Group',
                $value->application_id,
                $value->mortgage_code,
                'Sequence Acquisitions',
                $value->loan_category,
                'Non-Prime',
                $value->product_category_low,
                $value->loan_purpose,
                ($value->term_length / 12) . ' Year Fixed',
                $quoteDate->format('m/d/Y'),
                (new DateTime($value->funding_date))->format('m/d/Y'),
                $maturityDate->format('m/d/Y'),
                $value->gross_amt,
                $value->gross_amt,
                $value->term_length,
                $value->amortization,
                $this->getLowestBeaconScore($value->application_id),
                $selfEmployed,
                $value->total_income,
                $value->rental_income,
                $this->getApplicantsCount($value->application_id, 'B'),
                $this->getApplicantsCount($value->application_id, 'G'),
                $value->interest_rate,
                'Fixed',
                str_pad('',($value->term_length / 12),'5'),
                $value->term_length,
                $value->product_category_low,
                'In Funding',
                round($value->ltv,2),
                $value->gdsr,
                $value->tdsr,
                $value->dscr,
                'Amur Financial Group',
                $purchaseFactor,
                $quoteDate->format('m/d/Y'),
                'Amur Financial Group',
                'N/A',
                'N/A',
                $value->term_length,
                $value->monthly_pmt,
                (new DateTime($value->first_pmt_due_date))->format('m/d/Y'),
                $acquisitonDate,
                $value->sale_price,
                $value->discount * -1,
                round($interestAdjustment,2),
                $value->sale_price - $value->discount - $interestAdjustment,
                'properties' => $properties
            ];
        }

        foreach($rows as $key => $row) {
            for($i = 0; $i < $maxProperties; $i++) {
                $rows[$key][] = $row['properties'][$i]['address'] ?? '';
                $rows[$key][] = $row['properties'][$i]['city'] ?? '';
                $rows[$key][] = $row['properties'][$i]['province'] ?? '';
                $rows[$key][] = $row['properties'][$i]['postal_code'] ?? '';
                $rows[$key][] = is_null($row['properties'][$i]['appraisal_date'] ?? null) ? '' : $row['properties'][$i]['appraisal_date']->format('m/d/Y');
                $rows[$key][] = $row['properties'][$i]['appraised_value'] ?? '';
                $rows[$key][] = Utils::toOrdinal($row['properties'][$i]['position'] ?? '');
                $rows[$key][] = $row['properties'][$i]['property_type'] ?? '';
                $rows[$key][] = $row['properties'][$i]['occupancy_type'] ?? '';
                $rows[$key][] = 'N/A';
            }

            unset($rows[$key]['properties']);
        }

        $columns = array();
        $columns[] = ['type' => '', 'name' => 'ReportDate'];
        $columns[] = ['type' => '', 'name' => 'Originator'];
        $columns[] = ['type' => '', 'name' => 'LoanNumTPO'];
        $columns[] = ['type' => '', 'name' => 'MortgageCode'];
        $columns[] = ['type' => '', 'name' => 'OriginationChannel'];
        $columns[] = ['type' => '', 'name' => 'LoanType'];
        $columns[] = ['type' => '', 'name' => 'ProductCategoryHigh'];
        $columns[] = ['type' => '', 'name' => 'ProductCategoryLow'];
        $columns[] = ['type' => '', 'name' => 'LoanPurpose'];
        $columns[] = ['type' => '', 'name' => 'ProductTypeDetail'];
        $columns[] = ['type' => '', 'name' => 'CommitIssueDate'];
        $columns[] = ['type' => '', 'name' => 'ClosingDate'];
        $columns[] = ['type' => '', 'name' => 'MaturityDate'];
        $columns[] = ['type' => 'D', 'name' => 'LoanOriginalPrincipal'];
        $columns[] = ['type' => 'D', 'name' => 'LoanAcquisitionPrincipal'];
        $columns[] = ['type' => '', 'name' => 'TermOriginal'];
        $columns[] = ['type' => 'D', 'name' => 'AmortizationOriginal'];
        $columns[] = ['type' => '', 'name' => 'QualifyingFICO'];
        $columns[] = ['type' => '', 'name' => 'BorrSelfEmployedFlag'];
        $columns[] = ['type' => 'D', 'name' => 'QualifyingIncomeMthly'];
        $columns[] = ['type' => 'D', 'name' => 'DscrGrossRent'];
        $columns[] = ['type' => '', 'name' => 'NumBorrowers'];
        $columns[] = ['type' => '', 'name' => 'NumGuarantors'];
        $columns[] = ['type' => 'D', 'name' => 'InterestRate'];
        $columns[] = ['type' => '', 'name' => 'RateType'];
        $columns[] = ['type' => '', 'name' => 'PrepayPenaltyType'];
        $columns[] = ['type' => '', 'name' => 'PrepayPenaltyTerm'];
        $columns[] = ['type' => '', 'name' => 'QualifyingProgram'];
        $columns[] = ['type' => '', 'name' => 'LoanStatus'];
        $columns[] = ['type' => 'D', 'name' => 'LTV'];
        $columns[] = ['type' => 'D', 'name' => 'GDS'];
        $columns[] = ['type' => 'D', 'name' => 'TDS'];
        $columns[] = ['type' => 'D', 'name' => 'DSCR'];
        $columns[] = ['type' => '', 'name' => 'Originator'];
        $columns[] = ['type' => 'D', 'name' => 'PurchaseFactor'];
        $columns[] = ['type' => '', 'name' => 'PurchaseFactorDate'];
        $columns[] = ['type' => '', 'name' => 'SubServicerName'];
        $columns[] = ['type' => '', 'name' => 'LoanNumberServicer'];
        $columns[] = ['type' => '', 'name' => 'ServicingRate'];
        $columns[] = ['type' => '', 'name' => 'PaymentFrequency'];
        $columns[] = ['type' => 'D', 'name' => 'PmtPiOrig'];
        $columns[] = ['type' => '', 'name' => 'FirstPaymentDue'];
        $columns[] = ['type' => '', 'name' => 'AcquisitonDate'];
        $columns[] = ['type' => 'D', 'name' => 'LoanPurchasePrice'];
        $columns[] = ['type' => 'D', 'name' => 'DiscountPremium'];
        $columns[] = ['type' => 'D', 'name' => 'LoanAccruedInterest'];
        $columns[] = ['type' => 'D', 'name' => 'AcquisitonWireAmount'];

        for($i = 1; $i <= $maxProperties; $i++) {
            $columns[] = ['type' => '', 'name' => 'Address' . $i];
            $columns[] = ['type' => '', 'name' => 'City' . $i];
            $columns[] = ['type' => '', 'name' => 'Province' . $i];
            $columns[] = ['type' => '', 'name' => 'PostalCode' . $i];
            $columns[] = ['type' => '', 'name' => 'DtOriginalAppraisal' . $i];
            $columns[] = ['type' => 'D', 'name' => 'ValAppraisalOrig' . $i];
            $columns[] = ['type' => '', 'name' => 'LienPosition' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyType' . $i];
            $columns[] = ['type' => '', 'name' => 'OccupancyType' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyNumUnits' . $i];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    //Commitment---------------------------------------------
    public function getCommitmentReport($type = null) {
        if(is_null($type)) {
            return $this->getCommitmentData();

        } elseif($type == 'C') {
            return $this->getCSV('C');

        } elseif($type == 'E') {
            return $this->getPGP('C');

        }

        return null;
    }

    public function getCommitmentData() {
        $sql = "select a.saved_quote_id,
                       a.application_id,
                       a.created_at commit_issue_date,
                       c.funding_date closing_date,
                       a.first_pmt_date,
                       a.gross principal_balance,
                       a.loan term,
                       a.amort amortization,
                       a.int interest_rate,
                       'Fixed' rate_type,
                       d.name loan_purpose,
                       if(a.mortgage_id = 0, 'Commited', 'In Funding') status,
                       a.ltv ltv,
                       a.gdsr,
                       a.tdsr,
                       a.dscr,
                       g.name product_category_low,
                       a.date quote_date,
                       b.yield,
                       h.name loan_type
                  from saved_quote_table a
                  join sale_investor_table b on a.saved_quote_id = b.saved_quote_id
                  join application_table c on a.application_id = c.application_id
             left join category_value d on c.purpose_category_id = d.id
             left join category_value g on a.mortgage_type = g.id
             left join category_value h on a.loan_category = h.id
             left join mortgage_table e on a.mortgage_id = e.mortgage_id and e.is_deleted = 'no'
             left join mortgage_table f on f.transfer_id = e.mortgage_id and f.is_deleted = 'no'
                 where a.ready_buy = 'Yes'
                   and b.fm_committed = 'Yes'
                   and b.investor_id = ?
                   and (
                        (a.disburse = 'Yes' and a.mortgage_id = 0) 
                        or 
                        (e.mortgage_id is not null and f.transfer_id is null)
                       )
              order by a.application_id";
        $res = $this->db->select($sql,[$this->investorId]);

        $maxProperties = 0;
        $rows = array();
        foreach($res as $key => $value) {
            $properties = $this->getProperties($value->saved_quote_id, null);
            if(count($properties) > $maxProperties) $maxProperties = count($properties);
            $maturityDate = new DateTime($value->first_pmt_date);
            $maturityDate->add(new DateInterval('P' . ($value->term > 0 ? $value->term - 1 : 0) . 'M'));
            $quoteDate = new DateTime($value->quote_date);
            $purchaseFactor = (($value->yield - $value->interest_rate) * ($value->term / 12)) + 100;

            $rows[] = [
                (new DateTime())->format('m/d/Y'),
                'Amur Financial Group',
                $value->application_id,
                $value->loan_type,
                'Non-Prime',
                $value->product_category_low,
                $value->loan_purpose,
                (new DateTime($value->commit_issue_date))->format('m/d/Y'),
                (new DateTime($value->closing_date))->format('m/d/Y'),
                $maturityDate->format('m/d/Y'),
                $value->principal_balance,
                $value->term,
                round($value->amortization,1),
                $this->getLowestBeaconScore($value->application_id),
                $value->interest_rate,
                $value->rate_type,
                str_pad('',($value->term / 12),'5'),
                $value->product_category_low,
                $value->status,
                $value->ltv,
                $value->gdsr,
                $value->tdsr,
                $value->dscr,
                $purchaseFactor,
                $quoteDate->format('m/d/Y'),
                'properties' => $properties
            ];
        }

        foreach($rows as $key => $row) {
            for($i = 0; $i < $maxProperties; $i++) {
                $rows[$key][] = $row['properties'][$i]['address'] ?? '';
                $rows[$key][] = $row['properties'][$i]['city'] ?? '';
                $rows[$key][] = $row['properties'][$i]['province'] ?? '';
                $rows[$key][] = $row['properties'][$i]['postal_code'] ?? '';
                $rows[$key][] = $row['properties'][$i]['property_type'] ?? '';
                $rows[$key][] = $row['properties'][$i]['occupancy_type'] ?? '';
            }

            unset($rows[$key]['properties']);
        }

        $columns = array();
        $columns[] = ['type' => '', 'name' => 'ReportDate'];
        $columns[] = ['type' => '', 'name' => 'Originator'];
        $columns[] = ['type' => '', 'name' => 'LoanNumTPO'];
        $columns[] = ['type' => '', 'name' => 'LoanType'];
        $columns[] = ['type' => '', 'name' => 'ProductCategoryHigh'];
        $columns[] = ['type' => '', 'name' => 'ProductCategoryLow'];
        $columns[] = ['type' => '', 'name' => 'LoanPurpose'];
        $columns[] = ['type' => '', 'name' => 'CommitIssueDate'];
        $columns[] = ['type' => '', 'name' => 'ClosingDate'];
        $columns[] = ['type' => '', 'name' => 'MaturityDate'];
        $columns[] = ['type' => 'D', 'name' => 'LoanOriginalPrincipal'];
        $columns[] = ['type' => '', 'name' => 'TermOriginal'];
        $columns[] = ['type' => 'D', 'name' => 'AmortizationOriginal'];
        $columns[] = ['type' => '', 'name' => 'QualifyingFICO'];
        $columns[] = ['type' => 'D', 'name' => 'InterestRate'];
        $columns[] = ['type' => '', 'name' => 'RateType'];
        $columns[] = ['type' => '', 'name' => 'PrepayPenaltyType'];
        $columns[] = ['type' => '', 'name' => 'QualifyingProgram'];
        $columns[] = ['type' => '', 'name' => 'LoanStatus'];
        $columns[] = ['type' => 'D', 'name' => 'LTV'];
        $columns[] = ['type' => 'D', 'name' => 'GDS'];
        $columns[] = ['type' => 'D', 'name' => 'TDS'];
        $columns[] = ['type' => 'D', 'name' => 'DSCR'];
        $columns[] = ['type' => 'D', 'name' => 'PurchaseFactor'];
        $columns[] = ['type' => '', 'name' => 'PurchaseFactorDate'];

        for($i = 1; $i <= $maxProperties; $i++) {
            $columns[] = ['type' => '', 'name' => 'Address' . $i];
            $columns[] = ['type' => '', 'name' => 'City' . $i];
            $columns[] = ['type' => '', 'name' => 'Province' . $i];
            $columns[] = ['type' => '', 'name' => 'PostalCode' . $i];
            $columns[] = ['type' => '', 'name' => 'PropertyType' . $i];
            $columns[] = ['type' => '', 'name' => 'OccupancyType' . $i];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    public function getPGP($reportType = 'C') {
        $res = $this->getCSV($reportType);

        $gpg = new gnupg();
        $gpg->clearencryptkeys();
        $gpg->clearencryptkeys();
        $gpg->clearsignkeys();

        $allKeys = $gpg->keyinfo('');
        foreach($allKeys as $value) {
            foreach($value['subkeys'] as $key) {
                if(isset($key['fingerprint'])) {
                    $r = $gpg->deletekey($key['fingerprint']);
                    //$this->logger->debug('-->', [$r, $key['fingerprint']]);
                }
            }
        }
        
        $publicKey = Storage::disk('local')->get('keys/publicKey-bayview.asc');

        $gpg = new gnupg();
        $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
        $info = $gpg->import($publicKey);
        $gpg->addencryptkey($info['fingerprint']);
        //$gpg->setarmor(0); //output will be binary

        $file = Storage::disk('local')->get($res['fileName']);

        $encryptFile = $gpg->encrypt($file);
        
        Storage::disk('local')->put($res['fileName'] . '.pgp', $encryptFile);

        //decrypt
        /*$file = Storage::disk('local')->get($res['fileName'] . '.pgp');
        $gpg = new gnupg();
        $pk = Storage::disk('local')->get('keys/pk.asc');
        $info = $gpg->import($pk);
        $gpg->addencryptkey($info['fingerprint']);
        $plain = $gpg->decrypt($file);
        $this->logger->debug('plain', [$plain]);*/

        return [
            'fileName' => $res['fileName'] . '.pgp',
            'file' => base64_encode(Storage::get($res['fileName'] . '.pgp'))
        ];
    }

    public function getCSV($reportType) {
        if($reportType == 'C') {
            $data = $this->getCommitmentData();
        } elseif($reportType == 'A') {
            $data = $this->getAcquisitionData();
        } elseif($reportType == 'T') {
            $data = $this->getTrialBalanceData();
        } else {
            $data = $this->getRemittanceData();
        }

        $filePath =  Storage::disk('local')->path('tmp');
        $fileName = md5(uniqid()) . '.csv';

        $file = fopen($filePath . '/' . $fileName, 'w');

        fputcsv($file, array_column($data['columns'], 'name'));

        foreach($data['rows'] as $key => $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);

        return [
            'fileName' => 'tmp/' . $fileName,
            'file' => base64_encode(Storage::get('tmp/' . $fileName))
        ];
    }

    public function getProperties($savedQuoteId, $mortgageId) {
        if(!is_null($savedQuoteId)) {
            $sql = "select c.street_number, c.street_name, c.street_type, c.city, c.province, c.postal_code,
                           c.house_style property_type, c.type,
                           c.appraised_value, c.appraisal_date_received, a.position
                      from saved_quote_positions_table a
                      join saved_quote_table b on a.saved_quote_id = b.saved_quote_id
                      join property_table c on b.application_id = c.application_id and a.idx = c.idx
                     where a.saved_quote_id = ?
                       and a.position <> 'N/A'
                  order by a.idx";
            $res = $this->db->select($sql,[$savedQuoteId]);
        } else {
            $sql = "select c.street_number, c.street_name, c.street_type, c.city, c.province, c.postal_code,
                           c.house_style property_type, c.type,
                           c.appraised_value, c.appraisal_date_received, a.position
                      from mortgage_properties_table a
                      join property_table c on a.property_id = c.property_id
                     where a.mortgage_id = ?
                  order by c.idx";
            $res = $this->db->select($sql,[$mortgageId]);
        }

        $properties = array();
        foreach($res as $key => $value) {
            $properties[] = [
                'address' => trim($value->street_number) . ' ' . trim($value->street_name) . ' ' . trim($value->street_type),
                'city' => $value->city,
                'province' => $value->province,
                'postal_code' => $value->postal_code,
                'property_type' => $this->convertPropertyType($value->property_type),
                'occupancy_type' => $value->type,
                'owner_occupancy' => $value->type == 'Residence' ? 'Y' : 'N',
                'appraised_value' => $value->appraised_value,
                'appraisal_date' => $value->appraisal_date_received == '0000-00-00' ? null : new DateTime($value->appraisal_date_received),
                'position' => $value->position
            ];
        }

        return $properties;
    }

    public function convertPropertyType($propertyType) {
        $data = array();
        $data['2 Floor'] = 'Detached Single Family';
        $data['Bungalo'] = 'Detached Single Family';
        $data['Condo'] = 'Condo (townhouse or high-rise)';
        $data['Duplex'] = 'Detached Single Family';
        $data['Mobile'] = 'N/A';
        $data['Other'] = 'N/A';
        $data['Rancher'] = 'Detached Single Family';
        $data['Split level'] = 'Detached Single Family';
        $data['Townhouse'] = 'Freehold Townhouse';

        return $data[$propertyType] ?? '';
    }

    public function convertOccupancyType($occupancyType) {
        $data = array();
        $data['Residence'] = 'Owner-Occupied';
        $data['2nd Residence'] = 'Second Home';
        $data['Rental'] = 'Rental';
        
        return $data[$occupancyType] ?? '';
    }

    public function getLowestBeaconScore($applicationId) {
        $sql = "select b.type type_1, b.beacon_score beacon_score_1,
                       c.type type_2, c.beacon_score beacon_score_2
                  from applicant_table a
             left join spouse_table b on a.spouse1_id = b.spouse_id
             left join spouse_table c on a.spouse2_id = c.spouse_id
                 where a.application_id = ?";
        $res = $this->db->select($sql,[$applicationId]);

        $types = ['Applicant', 'Co-Applicant', 'Guarantor', 'Power of Attorney'];
        $beaconScore = 999;
        foreach($res as $key => $value) {
            if(in_array($value->type_1, $types)) {
                if($value->beacon_score_1 != 0 && $value->beacon_score_1 < $beaconScore) {
                    $beaconScore = $value->beacon_score_1;
                }
                if($value->beacon_score_2 != 0 && $value->beacon_score_2 < $beaconScore) {
                    $beaconScore = $value->beacon_score_2;
                }
            }
        }

        return $beaconScore;
    }

    public function getApplicantsCount($applicationId, $type) {
        if($type == 'B') {
            $types = "'Applicant','Co-Applicant','Power of Attorney'";
        } else {
            $types = "'Guarantor'";
        }

        $sql = "select sum(spouse) total
                  from (
                        select count(*) spouse from applicant_table a
                          join spouse_table b on a.spouse1_id = b.spouse_id
                         where a.application_id = ?
                           and b.type in ($types)
                           and b.l_name <> ''
                         union all
                        select count(*) spouse from applicant_table a
                          join spouse_table b on a.spouse2_id = b.spouse_id
                         where a.application_id = ?
                           and b.type in ($types)
                           and b.l_name <> ''
                       ) aa";
        $res = $this->db->select($sql,[$applicationId, $applicationId]);

        if(count($res) > 0) {
            return $res[0]->total;
        }

        return 0;
    }

    public function getSelfEmployed($applicationId) {
        $sql = "select * from applicant_table a
             left join present_employer_table b on a.spouse1_id = b.spouse_id
             left join present_employer_table c on a.spouse1_id = c.spouse_id
                 where a.application_id = ?
                   and (b.self_employed = 'yes' or c.self_employed = 'yes')";
        $res = $this->db->select($sql,[$applicationId]);

        if(count($res) > 0) {
            return true;
        }

        return false;
    }

    public function getCollectionStatus($applicationId, $mortgageId) {
        $sql = "select category_id, note_date_time
                  from notes_table
                 where application_id = ?
                   and (mortgage_id = ? or mortgage_id = 0)
                   and followed_up = 'no'
                   and category_id in (7,32,36,37,39)
              order by if(category_id = 7, 0, 1),
                    if(category_id = 39, 0, 1),
                    if(category_id = 37, 0, 1),
                    if(category_id = 36, 0, 1),
                    if(category_id = 32, 0, 1)";
        $res = $this->db->select($sql,[$applicationId, $mortgageId]);

        if(count($res) > 0) {
            if($res[0]->category_id == '32') {
                $category = 'Coll 30';
            } elseif($res[0]->category_id == '36') {
                $category = 'Coll 60';
            } elseif($res[0]->category_id == '37') {
                $category = 'Coll 90';
            } elseif($res[0]->category_id == '39') {
                $category = 'Coll 90+';
            } elseif($res[0]->category_id == '7') {
                $category = 'Foreclosure';
            }
            $noteDate = new DateTime($res[0]->note_date_time);
        } else {
            $category = '';
            $noteDate = null;
        }

        return [
            'collectionStatus' => $category,
            'collectionDate' => $noteDate
        ];
    }

    public function getFutureOSB($mortgageId, $interestRate, $initialOSB, $untilDate = null) {
        $sql = "select * from mortgage_payments_table
                where mortgage_id = ?
                and is_processed = 'no'
                and is_payout = 'no'
                and is_renewal = 'no'
            order by processing_date";
        $res = $this->db->select($sql,[$mortgageId]);

        $remainingPayments = 0;
        foreach($res as $key => $value) {
            if(!is_null($untilDate) && new DateTime($value->processing_date) > new DateTime($untilDate)) {
                return $initialOSB;
            }
            $interest = ($initialOSB * ($interestRate / 100)) / 12;
            $initialOSB -= $value->pmt_amt + $interest;
            $remainingPayments++;
        }

        return [
            'futureOSBAmount' => $initialOSB,
            'remainingPayments' => $remainingPayments
        ];
    }

    public function getNextPayment($mortgageId) {
        $sql = "select * from mortgage_payments_table
                 where mortgage_id = ?
                   and is_processed = 'no'
                   and is_payout = 'no'
                   and is_renewal = 'no'
              order by processing_date
                 limit 1";
        $res = $this->db->select($sql,[$mortgageId]);

        if(count($res) > 0) {
            return [
                'paymentDate' => new DateTime($res[0]->processing_date),
                'paymentAmount' => $res[0]->pmt_amt
            ];
        }

        return [
            'paymentDate' => null,
            'paymentAmount' => null
        ];
    }

    //Amur
    /*public function getInterestAdjustment($grossAmount, $interestRate, $compounding, $fundingDate, $interestDate) {
        $fundingDate = new DateTime($fundingDate);
        $interestDate = new DateTime($interestDate);
        $interestAdjustmentDays = ($fundingDate->diff($interestDate))->days;

        //if negative - client should receive interest days
        //if positive - client should pay interest days

        if($interestAdjustmentDays == 0) {
            return 0;
        } else {
            $monthlyInterest = ($grossAmount * ($interestRate / 100)) / 12;
            $dailyInterest = $monthlyInterest / 30;

            return $interestAdjustmentDays < 0
                ? ($interestAdjustmentDays * $dailyInterest) * -1
                : $interestAdjustmentDays * $dailyInterest;
        }
    }*/

    //ILG
    public function getInterestAdjustment($grossAmount, $interestRate, $compounding, $fundingDate, $interestDate) {
        $fundingDate = new DateTime($fundingDate);
        $interestDate = new DateTime($interestDate);
        $interestAdjustmentDays = (int) ($fundingDate->diff($interestDate))->format('%r%a');
        $interestRate = $interestRate / 100;

        $annualEffectiveRate = pow(1 + $interestRate / $compounding, $compounding) - 1;
        $dailyCompouingRate = pow(1 + $annualEffectiveRate, (1/365)) - 1;
        $ratePerTotalDays = $interestAdjustmentDays < 0
            ? (pow(1 + $dailyCompouingRate, ($interestAdjustmentDays * -1)) - 1)
            : (pow(1 + $dailyCompouingRate, $interestAdjustmentDays) - 1);

        //$this->logger->info('-->',[$interestAdjustmentDays,$grossAmount,$fundingDate->format('Y-m-d'),$interestDate->format('Y-m-d'),$ratePerTotalDays]);
        return $interestAdjustmentDays > 0
            ? ($grossAmount * $ratePerTotalDays) * -1
            : $grossAmount * $ratePerTotalDays;
    }
}