<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\RenewalApproval;
use App\Models\FilterOption;
use App\Models\AdobeAgreement;
use App\Models\MortgageRenewalsTable;
use App\Models\MortgageRenewalPaymentsTable;
use App\Amur\BO\UserBO;
use App\Amur\BO\ExcelReportBO;
use DateTime;
use DateInterval;

class RenewalApprovalBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getRenewals($endDate) {
        $this->logger->info('RenewalApprovalBO->getRenewals',[$endDate]);

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT 
                m.application_id,
                m.company_id,
                act.abbr AS origination_company_name, 
                m.ltv,
                m.mortgage_code,
                s.l_name, 
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                pt.type AS property_type, 
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                mi.term_end, 
                coll.collection,
                m.int_comm_date AS orig_date,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                m.monthly_pmt, 
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                m.mortgage_id,
                a.company,
                -- m.amortization,
                -- m.term_length,
                pt.house_style,
                sqt.second_prime,
                sqt.second_year,
                sqt.loan,
                sqt.int_comm_date,
                ra.id AS 'renewal_approval_id', 
                ra.new_interest_rate, 
                ra.status AS 'renewal_status',
                ra.new_interest_rate_ap, 
                ra.new_interest_rate_bp, 
                ra.new_interest_rate_cp, 
                ra.new_monthly_payment, 
                ra.new_monthly_payment_ap, 
                ra.new_monthly_payment_bp, 
                ra.new_monthly_payment_cp, 
                ra.renewal_fee, 
                ra.renewal_fee_ap, 
                ra.renewal_fee_bp, 
                ra.renewal_fee_cp, 
                ra.renewal_fee_to_be_paid_over, 
                ra.director_review,
                ra.notes AS 'renewal_approval_notes', 
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count
            FROM mortgage_table m
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN renewal_approval ra ON ra.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN saved_quote_table sqt ON sqt.application_id = m.application_id AND sqt.ready_buy = 'yes'
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id 
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            WHERE m.is_deleted = 'no' 
            AND (ra.status NOT IN ('A', 'R', 'P') OR ra.status IS NULL)
            AND m.current_balance > 0 
            AND m.payout_at IS NULL
            AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv')
            AND mi.term_end <= ?
            AND m.company_id <> 0
            GROUP BY m.mortgage_id
            ORDER BY mi.term_end DESC";
        $res = $this->db->select($query,[$endDate]);

        $fund1 = array();
        $fund2 = array();
        $fund3 = array();
        $abLoans = array();

        foreach($res as $value) {
            $province = "";
            $flag = "-";

            if($value->note_flag_count > 0) {
                $flag = "Yes";
            }

            if($value->other_mortgage == null) {
                $value->other_mortgage = "Alpine";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'propertyType' => $value->property_type,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'origDate' => $value->orig_date,
                'origBalance' => $value->gross_amt,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'numberOfNSF' => $value->nsf_count,
                'otherMortgage' => $value->other_mortgage,
                'flag' => $flag,
                'pmtVariance' => (($value->new_monthly_payment ?? 0) - $value->monthly_pmt),
                'currentInterestRate' => $value->current_int,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'renewalApprovalId' => $value->renewal_approval_id,
                'newInterestRate' => $value->new_interest_rate,
                'newInterestRateAp' => $value->new_interest_rate_ap,
                'newInterestRateBp' => $value->new_interest_rate_bp,
                'newInterestRateCp' => $value->new_interest_rate_cp,
                'newMonthlyPayment' => $value->new_monthly_payment,
                'newMonthlyPaymentAp' => $value->new_monthly_payment_ap,
                'newMonthlyPaymentBp' => $value->new_monthly_payment_bp,
                'newMonthlyPaymentCp' => $value->new_monthly_payment_cp,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'renewalFee' => $value->renewal_fee,
                'renewalFeeAp' => $value->renewal_fee_ap,
                'renewalFeeBp' => $value->renewal_fee_bp,
                'renewalFeeCp' => $value->renewal_fee_cp,
                'renewalFeeToBePaidOver' => $value->renewal_fee_to_be_paid_over,
                'mortgageId' => $value->mortgage_id,
                'noteCount' => $value->note_count,
                'payoutCount' => $value->payout_count,
                'companyId' => $value->company,
                'originationCompanyName' => $value->origination_company_name,
                'houseStyle' => $value->house_style,
                'additionalReviewCategory' => $value->director_review,
                'loanTerm' => $value->loan,
                'intCommDate' => $value->int_comm_date,
                'secondPrime' => $value->second_prime ?? 0,
                'secondYear' => $value->second_year ?? 0,
                'userId' => $userId
            ];

            $secondPrime = $value->second_prime ?? 0;
            $secondYear = $value->second_year ?? 0;

            if($value->company_id == 16) { // Fund 1
                $fund1[] = $tempArr;
            } else if ($value->company_id == 5) { // Fund 2
                $fund2[] = $tempArr;
            } else if ($value->company_id == 182) { // Fund 3
                $fund3[] = $tempArr;
            } else {
                $abLoans[] = $tempArr;
            }
        }

        return [
            'fund1' => $fund1,
            'fund2' => $fund2,
            'fund3' => $fund3,
            'abLoans' => $abLoans,
        ];
    }

    public function getRenewalsCount($startDate, $endDate) {
        $this->logger->info('RenewalApprovalBO->getRenewalsCount',[$startDate, $endDate]);

        $query = 
            "SELECT 
                m.company_id,
                sqt.second_prime,
                sqt.second_year,
                sqt.loan,
                sqt.int_comm_date
            FROM mortgage_table m
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN renewal_approval ra ON ra.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN saved_quote_table sqt ON sqt.application_id = m.application_id AND sqt.ready_buy = 'yes'
            WHERE m.is_deleted = 'no' 
            AND (ra.status NOT IN ('A', 'R', 'P') OR ra.status IS NULL)
            AND m.current_balance > 0 
            AND m.payout_at IS NULL
            AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv')
            AND mi.term_end >= ? 
            AND mi.term_end <= ?
            AND m.company_id <> 0
            GROUP BY m.mortgage_id
        ";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1Count = 0;
        $fund2Count = 0;
        $fund3Count = 0;

        foreach($res as $value) {

            $secondPrime = $value->second_prime ?? 0;
            $secondYear = $value->second_year ?? 0;

            if($this->isVariableByDate($secondPrime, $secondYear, $value->loan, $value->int_comm_date, $endDate)) {
                continue;
            } else {
                if($value->company_id == 16) { // Fund 1
                    $fund1Count += 1;
                } else if ($value->company_id == 5) { // Fund 2
                    $fund2Count += 1;
                } else if ($value->company_id == 182) { // Fund 3
                    $fund3Count += 1;
                }
            }
        }

        return [
            'fund1Count' => $fund1Count,
            'fund2Count' => $fund2Count,
            'fund3Count' => $fund3Count,
        ];
    }

    public function getInProgressRenewals($startDate, $endDate) {
       $this->logger->info('RenewalApprovalBO->getInProgressRenewals',[$startDate, $endDate]);

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT 
                m.application_id,
                act.abbr AS origination_company_name, 
                fn_GetLenders(m.mortgage_id) AS investors,
                m.mortgage_code,
                s.l_name, 
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                pt.type AS property_type, 
                pt.house_style,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                m.ltv,
                mi.term_end, 
                coll.collection,
                m.int_comm_date,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                m.monthly_pmt,
                mrt.new_interest_rate,
                mrt.new_monthly_pmt,
                ra.notes AS 'renewal_approval_notes', 
                ra.status AS 'renewal_status',
                ut.user_fname AS assigned_fname,
                ut.user_lname AS assigned_lname,
                m.mortgage_id,
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count,
                m.company_id,
                a.company
                -- ra.id AS 'renewal_approval_id', 
                -- ra.status AS 'renewal_status',
                -- ra.renewal_fee, 
                -- ra.renewal_fee_ap, 
                -- ra.renewal_fee_bp, 
                -- ra.renewal_fee_cp, 
                -- ra.renewal_fee_to_be_paid_over, 
                -- ra.notes AS 'renewal_approval_notes', 
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN users_table ut ON ra.assigned_id = ut.user_id
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            WHERE (ra.status IS NOT NULL AND ra.status <> 'P')
            AND mrt.processed_at IS NULL
            AND mi.term_end >= ? 
            AND mi.term_end <= ?
            GROUP BY ra.id
            ORDER BY ra.due_date DESC
        ";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1 = array();
        $fund2 = array();
        $fund3 = array();
        $abLoans = array();
        $nonRenewals = array();
        $fund1Count = 0;
        $fund2Count = 0;
        $fund3Count = 0;
        foreach($res as $value) {
            $province = "";
            $flag = "-";

            if($value->note_flag_count > 0) {
                $flag = "Yes";
            }

            if($value->other_mortgage == null) {
                $value->other_mortgage = "Alpine";
            }

            if($value->assigned_fname && $value->assigned_lname) {
                $assignedName = $value->assigned_fname . " " . $value->assigned_lname;
            } else {
                $assignedName = "Unassigned";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'propertyType' => $value->property_type,
                'houseStyle' => $value->house_style,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'origDate' => $value->int_comm_date,
                'origBalance' => $value->gross_amt,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'newInterestRate' => $value->new_interest_rate,
                'numberOfNSF' => $value->nsf_count,
                'otherMortgage' => $value->other_mortgage,
                'flag' => $flag,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'newMonthlyPayment' => $value->new_monthly_pmt,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'mortgageId' => $value->mortgage_id,
                'userId' => $userId,
                'payoutCount' => $value->payout_count,
                'noteCount' => $value->note_count,
                'originationCompanyName' => $value->origination_company_name,
                'assignedName' => $assignedName,
                'investors' => $value->investors
                // 'pmtVariance' => (($value->new_monthly_payment ?? 0) - $value->monthly_pmt),
                // 'currentInterestRate' => $value->current_int,
                // 'renewalApprovalId' => $value->renewal_approval_id,
                // 'renewalApprovalNotes' => $value->renewal_approval_notes,
                // 'renewalFee' => $value->renewal_fee,
                // 'renewalFeeAp' => $value->renewal_fee_ap,
                // 'renewalFeeBp' => $value->renewal_fee_bp,
                // 'renewalFeeCp' => $value->renewal_fee_cp,
                // 'renewalFeeToBePaidOver' => $value->renewal_fee_to_be_paid_over,
                // 'companyId' => $value->company
            ];

            if($value->mortgage_id != null) {
                if($value->company_id == 16 && $value->renewal_status != 'R' ) { // Fund 1
                    $fund1[] = $tempArr;
                    $fund1Count++;
                } else if ($value->company_id == 5 && $value->renewal_status != 'R' ) { // Fund 2
                    $fund2[] = $tempArr;
                    $fund2Count++;
                } else if ($value->company_id == 182 && $value->renewal_status != 'R' ) { // Fund 3
                    $fund3[] = $tempArr;
                    $fund3Count++;
                } else if ($value->renewal_status != 'R' ) {
                    $abLoans[] = $tempArr;
                } else {
                    if($value->company_id == 16 ) { // Fund 1
                        $fund1Count++;
                    } else if ($value->company_id == 5 ) { // Fund 2
                        $fund2Count++;
                    } else if ($value->company_id == 182 ) { // Fund 3
                        $fund3Count++;
                    }
                    $nonRenewals[] = $tempArr;
                }
            }
        }

        return [
            'fund1' => $fund1,
            'fund2' => $fund2,
            'fund3' => $fund3,
            'abLoans' => $abLoans,
            'nonRenewals' => $nonRenewals,
            'fund1Count' => $fund1Count,
            'fund2Count' => $fund2Count,
            'fund3Count' => $fund3Count
        ];
    }

    public function getInProgressRenewalsCount($startDate, $endDate) {
        $this->logger->info('RenewalApprovalBO->getInProgressRenewalsCount',[$startDate, $endDate]);

        $query = 
            "SELECT m.company_id
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            WHERE (ra.status IS NOT NULL AND ra.status <> 'P')
            AND mrt.processed_at IS NULL
            AND mi.term_end >= ? 
            AND mi.term_end <= ?
        ";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1Count = 0;
        $fund2Count = 0;
        $fund3Count = 0;
        foreach($res as $value) {
            if($value->company_id == 16) { // Fund 1
                $fund1Count += 1;
            } else if ($value->company_id == 5) { // Fund 2
                $fund2Count += 1;
            } else if ($value->company_id == 182) { // Fund 3
                $fund3Count += 1;
            }
        }

        return [
            'fund1Count' => $fund1Count,
            'fund2Count' => $fund2Count,
            'fund3Count' => $fund3Count,
        ];
    }

    public function getPendingReviews() {
       $this->logger->info('RenewalApprovalBO->getPendingReviews',[]);

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT 
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                act.abbr AS origination_company_name, 
                m.application_id,
                m.company_id,
                m.ltv,
                m.mortgage_code,
                s.l_name, 
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                pt.type AS property_type, 
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                mi.term_end, 
                coll.collection,
                m.int_comm_date,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                m.monthly_pmt, 
                m.mortgage_id,
                a.company,
                pt.house_style,
                ra.id AS 'renewal_approval_id', 
                ra.new_interest_rate, 
                ra.status AS 'renewal_status',
                ra.new_interest_rate_ap, 
                ra.new_interest_rate_bp, 
                ra.new_interest_rate_cp, 
                ra.new_monthly_payment, 
                ra.new_monthly_payment_ap, 
                ra.new_monthly_payment_bp, 
                ra.new_monthly_payment_cp, 
                ra.renewal_fee, 
                ra.renewal_fee_ap, 
                ra.renewal_fee_bp, 
                ra.renewal_fee_cp, 
                ra.renewal_fee_to_be_paid_over, 
                ra.notes AS 'renewal_approval_notes', 
                ra.director_review,
                cv.name AS director_review_name,
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN category_value cv ON ra.director_review = cv.id
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            WHERE ra.status = 'P'
            AND mrt.processed_at IS NULL
            GROUP BY ra.id
            ORDER BY ra.due_date DESC
        ";
        $res = $this->db->select($query,[]);

        $renewalGroups = [];
        $fund1 = array();
        $fund2 = array();
        $fund3 = array();
        $abLoans = array();
        foreach($res as $value) {
            $province = "";
            $flag = "-";

            if($value->note_flag_count > 0) {
                $flag = "Yes";
            }

            if($value->other_mortgage == null) {
                $value->other_mortgage = "Alpine";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'propertyType' => $value->property_type,
                'houseStyle' => $value->house_style,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'origDate' => $value->int_comm_date,
                'origBalance' => $value->gross_amt,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'newInterestRate' => $value->new_interest_rate,
                'newInterestRateAp' => $value->new_interest_rate_ap,
                'newInterestRateBp' => $value->new_interest_rate_bp,
                'newInterestRateCp' => $value->new_interest_rate_cp,
                'numberOfNSF' => $value->nsf_count,
                'otherMortgage' => $value->other_mortgage,
                'flag' => $flag,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'newMonthlyPayment' => $value->new_monthly_payment,
                'newMonthlyPaymentAp' => $value->new_monthly_payment_ap,
                'newMonthlyPaymentBp' => $value->new_monthly_payment_bp,
                'newMonthlyPaymentCp' => $value->new_monthly_payment_cp,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'payoutCount' => $value->payout_count,
                'noteCount' => $value->note_count,
                'renewalFeeToBePaidOver' => $value->renewal_fee_to_be_paid_over,
                'mortgageId' => $value->mortgage_id,
                'userId' => $userId,
                'originationCompanyName' => $value->origination_company_name,
                'renewalApprovalId' => $value->renewal_approval_id,
                'renewalFee' => $value->renewal_fee,
                'renewalFeeAp' => $value->renewal_fee_ap,
                'renewalFeeBp' => $value->renewal_fee_bp,
                'renewalFeeCp' => $value->renewal_fee_cp,
                'additionalReviewCategory' => $value->director_review,
                'pmtVariance' => $value->new_monthly_payment ? ($value->monthly_pmt - $value->new_monthly_payment) : 'N/A'
                // 'currentInterestRate' => $value->current_int,
                // 'renewalApprovalNotes' => $value->renewal_approval_notes,
                // 'companyId' => $value->company
            ];

            if($value->director_review) {
                if (!isset($renewalGroups[$value->director_review_name])) {
                    $renewalGroups[$value->director_review_name] = [];
                }

                $renewalGroups[$value->director_review_name][] = $tempArr;
            } else {
                if($value->company_id == 16) { // Fund 1
                    $fund1[] = $tempArr;
                } else if ($value->company_id == 5) { // Fund 2
                    $fund2[] = $tempArr;
                } else if ($value->company_id == 182) { // Fund 3
                    $fund3[] = $tempArr;
                } else {
                    $abLoans[] = $tempArr;
                }
            }
        }

        return [
            'fund1' => $fund1,
            'fund2' => $fund2,
            'fund3' => $fund3,
            'abLoans' => $abLoans,
            'renewalGroups' => $renewalGroups
        ];
    }

    public function getPendingReviewsCount($startDate, $endDate) {
        $this->logger->info('RenewalApprovalBO->getPendingReviewsCount',[$startDate, $endDate]);

        $query = 
            "SELECT m.company_id
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            WHERE ra.status = 'P'
            AND mrt.processed_at IS NULL
            AND mi.term_end >= ? 
            AND mi.term_end <= ?
        ";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1Count = 0;
        $fund2Count = 0;
        $fund3Count = 0;
        foreach($res as $value) {
            if($value->company_id == 16) { // Fund 1
                $fund1Count += 1;
            } else if ($value->company_id == 5) { // Fund 2
                $fund2Count += 1;
            } else if ($value->company_id == 182) { // Fund 3
                $fund3Count += 1;
            }
        }

        return [
            'fund1Count' => $fund1Count,
            'fund2Count' => $fund2Count,
            'fund3Count' => $fund3Count,
        ];
    }

    public function getProcessedRenewals($startDate, $endDate) {
       $this->logger->info('RenewalApprovalBO->getProcessedRenewals',[$startDate, $endDate]);

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT 
                m.application_id,
                act.abbr AS origination_company_name, 
                m.mortgage_code,
                s.l_name,
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                pt.type AS property_type, 
                pt.house_style,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                m.ltv,
                mrt.processed_at,
                coll.collection,
                m.int_comm_date,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int,
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                m.monthly_pmt, 
                ra.notes AS 'renewal_approval_notes',
                ut.user_fname AS assigned_fname,
                ut.user_lname AS assigned_lname,
                m.mortgage_id,
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count,
                a.company,
                mi.term_end,
                m.company_id
                -- ra.id AS 'renewal_approval_id', 
                -- ra.new_interest_rate, 
                -- ra.status AS 'renewal_status',
                -- ra.new_interest_rate_ap, 
                -- ra.new_interest_rate_bp, 
                -- ra.new_interest_rate_cp, 
                -- ra.new_monthly_payment, 
                -- ra.new_monthly_payment_ap, 
                -- ra.new_monthly_payment_bp, 
                -- ra.new_monthly_payment_cp, 
                -- ra.renewal_fee, 
                -- ra.renewal_fee_ap, 
                -- ra.renewal_fee_bp, 
                -- ra.renewal_fee_cp, 
                -- ra.renewal_fee_to_be_paid_over, 
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN users_table ut ON ra.assigned_id = ut.user_id
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            INNER JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            WHERE ra.status IS NOT NULL
            AND mrt.processed_at IS NOT NULL 
            AND mrt.processed_at >= ? 
            AND mrt.processed_at <= ?
            GROUP BY ra.id
            ORDER BY ra.due_date DESC
        ";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1 = array();
        $fund2 = array();
        $fund3 = array();
        $abLoans = array();
        foreach($res as $value) {
            $province = "";
            $flag = "-";

            if($value->note_flag_count > 0) {
                $flag = "Yes";
            }

            if($value->other_mortgage == null) {
                $value->other_mortgage = "Alpine";
            }

            if($value->assigned_fname && $value->assigned_lname) {
                $assignedName = $value->assigned_fname . " " . $value->assigned_lname;
            } else {
                $assignedName = "Unassigned";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'propertyType' => $value->property_type,
                'houseStyle' => $value->house_style,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'processedDate' => $value->processed_at,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'origDate' => $value->int_comm_date,
                'origBalance' => $value->gross_amt,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'numberOfNSF' => $value->nsf_count,
                'otherMortgage' => $value->other_mortgage,
                'originationCompanyName' => $value->origination_company_name,
                'flag' => $flag,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'mortgageId' => $value->mortgage_id,
                'userId' => $userId,
                'assignedName' => $assignedName,
                'payoutCount' => $value->payout_count,
                'noteCount' => $value->note_count
                // 'pmtVariance' => (($value->new_monthly_payment ?? 0) - $value->monthly_pmt),
                // 'currentInterestRate' => $value->current_int,
                // 'renewalApprovalId' => $value->renewal_approval_id,
                // 'newInterestRate' => $value->new_interest_rate,
                // 'newInterestRateAp' => $value->new_interest_rate_ap,
                // 'newInterestRateBp' => $value->new_interest_rate_bp,
                // 'newInterestRateCp' => $value->new_interest_rate_cp,
                // 'newMonthlyPayment' => $value->new_monthly_payment,
                // 'newMonthlyPaymentAp' => $value->new_monthly_payment_ap,
                // 'newMonthlyPaymentBp' => $value->new_monthly_payment_bp,
                // 'newMonthlyPaymentCp' => $value->new_monthly_payment_cp,
                // 'renewalFee' => $value->renewal_fee,
                // 'renewalFeeAp' => $value->renewal_fee_ap,
                // 'renewalFeeBp' => $value->renewal_fee_bp,
                // 'renewalFeeCp' => $value->renewal_fee_cp,
                // 'renewalFeeToBePaidOver' => $value->renewal_fee_to_be_paid_over,
                // 'companyId' => $value->company,
            ];

            if($value->company_id == 16) { // Fund 1
                $fund1[] = $tempArr;
            } else if ($value->company_id == 5) { // Fund 2
                $fund2[] = $tempArr;
            } else if ($value->company_id == 182) { // Fund 3
                $fund3[] = $tempArr;
            } else {
                $abLoans[] = $tempArr;
            }
        }

        return [
            'fund1' => $fund1,
            'fund2' => $fund2,
            'fund3' => $fund3,
            'abLoans' => $abLoans,
        ];
    }

    public function getProcessedRenewalsCount($startDate, $endDate) {
        $this->logger->info('RenewalApprovalBO->getProcessedRenewalsCount',[$startDate, $endDate]);


        $query = 
            "SELECT m.company_id
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            INNER JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            WHERE ra.status IS NOT NULL
            AND mrt.processed_at IS NOT NULL 
            AND mrt.processed_at >= ? 
            AND mrt.processed_at <= ?";
        $res = $this->db->select($query,[$startDate, $endDate]);

        $fund1Count = 0;
        $fund2Count = 0;
        $fund3Count = 0;
        foreach($res as $value) {
            if($value->company_id == 16) { // Fund 1
                $fund1Count += 1;
            } else if ($value->company_id == 5) { // Fund 2
                $fund2Count += 1;
            } else if ($value->company_id == 182) { // Fund 3
                $fund3Count += 1;
            }
        }

        return [
            'fund1Count' => $fund1Count,
            'fund2Count' => $fund2Count,
            'fund3Count' => $fund3Count,
        ];
    }

    public function getCategories($categoryId) {
       $this->logger->info('RenewalApprovalBO->getCategories',[$categoryId]);

        $query = 
            "SELECT id, abbr, name 
               FROM category_value
              WHERE category_id = ?
           ORDER BY position";
        $res = $this->db->select($query,[$categoryId]);

        if(count($res) > 0) {
            return $res;
        }

        return false;
    }

    public function getApprovedRenewals() {

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT
                ra.id renewal_approval_id,
                act.name AS origination_company_name, 
                act.new_name AS origination_company_new_name, 
                ra.status renewal_approval_status,
                mrt.new_interest_rate,
                mrt.new_monthly_pmt,
                ra.new_monthly_payment,
                ra.notes renewal_approval_notes,
                ra.assigned_id renewal_approval_assigned_id,
                ra.renewal_id mortgage_renewal_table_id,
                ra.approved_by,
                ra.broker_approval_status,
                m.application_id,
                m.company_id,
                m.mortgage_code,
                fn_GetSpouse1LastNameByApplicationID(m.application_id) l_name,
                m.cities,
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                m.monthly_pmt, 
                m.mortgage_id,
                fn_GetLenders(m.mortgage_id) AS investors,
                a.company,
                ut.user_fname AS assigned_fname,
                ut.user_lname AS assigned_lname,
                -- m.amortization,
                -- m.term_length,
                (
                    SELECT GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/')
                    FROM notes_table a
                    JOIN note_categories_table b ON a.category_id = b.category_id
                    WHERE a.followed_up = 'no'
                    AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                    AND a.application_id = m.application_id AND a.mortgage_id = m.mortgage_id
                ) collection,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count,
                mi.term_end,
                mrt.processed_at
            FROM renewal_approval ra
            JOIN mortgage_table m on ra.mortgage_id = m.mortgage_id
            JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            JOIN (
                SELECT mortgage_id, max(term_end) term_end
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN users_table ut ON ra.assigned_id = ut.user_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            WHERE m.is_deleted = 'no' 
            AND mrt.processed_at IS NULL
            AND (ra.status IS NOT NULL AND ra.status <> 'P')
            AND m.current_balance > 0 
            AND m.payout_at IS NULL
            AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv')
            AND m.company_id <> 0
            AND ra.deleted_at IS NULL
            GROUP BY ra.id
            ORDER BY ra.due_date DESC
            
        "; //AND (ra.broker_approval_status <> 'A' OR ra.broker_approval_status IS NULL)
        $res = $this->db->select($query,[]);

        foreach($res as $value) {
            $province = "";

            if($value->assigned_fname && $value->assigned_lname) {
                $assignedName = $value->assigned_fname . " " . $value->assigned_lname;
            } else {
                $assignedName = "Unassigned";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'pos' => $value->pos,
                'termDueDate' => $value->term_end,
                'collStatus' => $value->collection,
                'origBalance' => $value->gross_amt,
                'org' => $value->interest_rate,
                "investors" => $value->investors,
                'rate' => $value->current_int,
                'currentInterestRate' => $value->current_int,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'renewalApprovalId' => $value->renewal_approval_id,
                'newInterestRate' => $value->new_interest_rate,
                'newMonthlyPayment' => $value->new_monthly_pmt,
                'renewalApprovalStatus' => $value->renewal_approval_status,
                'mortgageRenewalTableId' => $value->mortgage_renewal_table_id,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'renewalApprovalassignedId' => $value->renewal_approval_assigned_id,
                'approvedBy' => $value->approved_by,
                'mortgageId' => $value->mortgage_id,
                'noteCount' => $value->note_count,
                'payoutCount' => $value->payout_count,
                'companyId' => $value->company,
                'brokerApprovalStatus' => $value->broker_approval_status,
                'userId' => $userId,
                'originationCompanyFullName' => $value->origination_company_new_name ?? $value->origination_company_name,
                'assignedName' => $assignedName
            ];

            if($value->mortgage_id != null) { 
                if($value->renewal_approval_id) {
                    if ($value->renewal_approval_status == "R" && is_null($value->processed_at)) {
                        // Non Renewals
                        if(!is_null($value->renewal_approval_assigned_id)) {
                            // Assigned
                            $nonRenewals[] = $tempArr;
                        } else {
                            // Unassigned
                            $unassignedNonRenewals[] = $tempArr;
                        }
                    } else if($value->renewal_approval_status == "A" && is_null($value->processed_at)) {
                        // Approved
                        if(!is_null($value->renewal_approval_assigned_id)) {
                            // Assigned
                            $assigned[] = $tempArr;
                        } else {
                            // Unassigned
                            $unassignedRenewals[] = $tempArr;
                        }
                    }
                }
            }
        }

        if(isset($assigned)) {
            $sortOption = array('desc' => SORT_DESC, 'asc' => SORT_ASC, 'string' => SORT_STRING, 'numeric' => SORT_NUMERIC);

            $columns = array();
            $columns[0] = array_column($assigned, 'brokerApprovalStatus');
            $param[] = &$columns[0];
            $param[] = &$sortOption['asc'];

            $columns[1] = array_column($assigned, 'termDueDate');
            $param[] = &$columns[1];
            $param[] = &$sortOption['asc'];

            $param[] = &$assigned;

            call_user_func_array('array_multisort', $param);
        }

        return [
            'unassignedRenewals' => $unassignedRenewals ?? [],
            'unassignedNonRenewals' => $unassignedNonRenewals ?? [],
            'assigned' => $assigned ?? [],
            'nonRenewals' => $nonRenewals ?? []
        ];
    }

    public function getBrokerRequestedRenewals() {

        $userId = Auth::user()->user_id;

        $query = 
            "SELECT
                ra.id renewal_approval_id,
                ra.status renewal_approval_status,
                mrt.new_interest_rate,
                mrt.new_monthly_pmt,
                ra.notes renewal_approval_notes,
                ra.assigned_id renewal_approval_assigned_id,
                ra.renewal_id mortgage_renewal_table_id,
                ra.approved_by,
                m.application_id,
                m.company_id,
                m.mortgage_code,
                fn_GetSpouse1LastNameByApplicationID(m.application_id) l_name,
                m.cities,
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                m.gross_amt, 
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                fn_GetLenders(m.mortgage_id) AS investors,
                m.monthly_pmt, 
                m.mortgage_id,
                a.company,
                -- m.amortization,
                -- m.term_length,
                (
                    SELECT GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/')
                    FROM notes_table a
                    JOIN note_categories_table b ON a.category_id = b.category_id
                    WHERE a.followed_up = 'no'
                    AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                    AND a.application_id = m.application_id AND a.mortgage_id = m.mortgage_id
                ) collection,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count,
                mi.term_end
            FROM renewal_approval ra
            JOIN mortgage_table m on ra.mortgage_id = m.mortgage_id
            JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            JOIN (
                SELECT mortgage_id, max(term_end) term_end
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            WHERE m.is_deleted = 'no' 
            AND m.current_balance > 0 
            AND m.payout_at IS NULL
            AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv')
            AND m.company_id <> 0
            AND ra.broker_approval_status = 'R'
            GROUP BY ra.id
            ORDER BY ra.due_date DESC
        ";
        $res = $this->db->select($query,[]);

        foreach($res as $value) {
            $province = "";

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                "investors" => $value->investors,
                'province' => $province,
                'pos' => $value->pos,
                'termDueDate' => $value->term_end,
                'collStatus' => $value->collection,
                'origBalance' => $value->gross_amt,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'currentInterestRate' => $value->current_int,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'renewalApprovalId' => $value->renewal_approval_id,
                'newInterestRate' => $value->new_interest_rate,
                'newMonthlyPayment' => $value->new_monthly_pmt,
                'renewalApprovalStatus' => $value->renewal_approval_status,
                'mortgageRenewalTableId' => $value->mortgage_renewal_table_id,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'renewalApprovalassignedId' => $value->renewal_approval_assigned_id,
                'approvedBy' => $value->approved_by,
                'mortgageId' => $value->mortgage_id,
                'noteCount' => $value->note_count,
                'payoutCount' => $value->payout_count,
                'companyId' => $value->company,
                'userId' => $userId
            ];

            if ($value->renewal_approval_status == "R") {
                // Non Renewals
                $assignedNonRenewals[] = $tempArr;
            } else if($value->renewal_approval_status == "A") {
                // Approved
                $assignedRenewals[] = $tempArr;
            }
        }

        return [
            'assignedRenewals' => $assignedRenewals ?? [],
            'assignedNonRenewals' => $assignedNonRenewals ?? []
        ];
    }

    private function generateExcelData($filterOptions) {
        $this->logger->info('RenewalApprovalBO->generateExcelData',[$filterOptions]);
        
        $foreclosureRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];

        $paidOutRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];

        $nonRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];

        $pendingRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];

        $generalRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];
        
        $additionalReviewRenewals = [
            'Fund 1' => [],
            'Fund 2' => [],
            'Fund 3' => []
        ];

        $query = 
            "SELECT 
                m.application_id,
                m.mortgage_code,
                act.abbr AS origination_company_name, 
                s.l_name, 
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                pt.type AS property_type, 
                pt.house_style AS property_house_style,
                m.ltv,
                mi.term_end, 
                coll.collection,
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                ut.user_fname AS assigned_fname,
                ut.user_lname AS assigned_lname,
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                m.monthly_pmt,
                mrt.new_interest_rate AS 'mortgage_renewal_new_interest_rate',
                mrt.new_monthly_pmt AS 'mortgage_renewal_new_monthly_payment',
                ra.new_interest_rate  AS 'renewal_approval_new_interest_rate',
                ra.new_monthly_payment AS 'renewal_approval_new_monthly_payment',
                ra.notes AS 'renewal_approval_notes', 
                ra.status AS 'renewal_status',
                ra.director_review,
                cv.name AS director_review_name,
                m.mortgage_id,
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count,
                m.company_id
            FROM renewal_approval ra
            LEFT JOIN mortgage_table m ON ra.mortgage_id = m.mortgage_id AND m.is_deleted = 'no' AND m.current_balance > 0 AND m.payout_at IS NULL AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv') AND m.company_id <> 0
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN users_table ut ON ra.assigned_id = ut.user_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN category_value cv ON ra.director_review = cv.id
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            LEFT JOIN mortgage_renewals_table mrt ON ra.mortgage_id = mrt.mortgage_id and ra.renewal_id = mrt.renewal_id
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            WHERE mrt.processed_at IS NULL
            AND m.company_id <> 0";
        $res = $this->applyExcelFilters($query, $filterOptions, "renewal_approval");

        foreach($res as $value) {
            $province = "";

            if($value->assigned_fname && $value->assigned_lname) {
                $assignedName = $value->assigned_fname . " " . $value->assigned_lname;
            } else {
                $assignedName = "Unassigned";
            }

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'numberOfNSF' => $value->nsf_count,
                // 'newMortgageRenewalInterestRate' => $value->mortgage_renewal_new_interest_rate,
                // 'newRenewalApprovalInterestRate' => $value->renewal_approval_new_interest_rate,
                'currentMonthlyPayment' => $value->monthly_pmt,
                // 'newMortgageRenewalMonthlyPayment' => $value->mortgage_renewal_new_monthly_payment,
                // 'newRenewalApprovalMonthlyPayment' => $value->renewal_approval_new_monthly_payment,
                'renewalApprovalNotes' => $value->renewal_approval_notes,
                'noteCount' => $value->note_count,
                'assignedName' => $assignedName,
                'payoutCount' => $value->payout_count,
                'directorReview' => $value->director_review,
                'directorReviewName' => $value->director_review_name,
                'renewalStatus' => $value->renewal_status
            ];

            if($value->renewal_status == 'A') {
                $tempArr['newInterestRate'] = $value->mortgage_renewal_new_interest_rate;
                $tempArr['newMonthlyPayment'] = $value->mortgage_renewal_new_monthly_payment;
            } else {
                $tempArr['newInterestRate'] = $value->renewal_approval_new_interest_rate;
                $tempArr['newMonthlyPayment'] = $value->renewal_approval_new_monthly_payment;
            }

            // Property Types Filter
            if (count($filterOptions['propertyTypes']) > 0) {
                if (!in_array($value->property_type, $filterOptions['propertyTypes'])) {
                    continue;
                }
            }

            // House Styles Filter
            if (count($filterOptions['houseStyles']) > 0) {
                if (!in_array($value->property_house_style, $filterOptions['houseStyles'])) {
                    continue;
                }
            }

            if($value->company_id == 16) { // Fund 1
                if($value->note_count > 0) {
                    $foreclosureRenewals['Fund 1'][] = $tempArr;
                }

                if($value->payout_count > 0) {
                    $paidOutRenewals['Fund 1'][] = $tempArr;
                }

                if($value->renewal_status == 'R') {
                    $nonRenewals['Fund 1'][] = $tempArr;
                }

                if ($value->renewal_status == 'P' && !empty($value->director_review)) {
                    $category = $value->director_review_name;

                    if (!isset($additionalReviewRenewals['Fund 1'][$category])) {
                        $additionalReviewRenewals['Fund 1'][$category] = [];
                    }

                    $additionalReviewRenewals['Fund 1'][$category][] = $tempArr;
                } elseif ($value->renewal_status == 'P') {
                    $pendingRenewals['Fund 1'][] = $tempArr;
                }

                if(($value->renewal_status == 'A')) {
                    $generalRenewals['Fund 1'][] = $tempArr;
                }
            } else if ($value->company_id == 5) { // Fund 2
                if($value->note_count > 0) {
                    $foreclosureRenewals['Fund 2'][] = $tempArr;
                }

                if($value->note_count > 0) {
                    $paidOutRenewals['Fund 2'][] = $tempArr;
                }

                if($value->renewal_status == 'R') {
                    $nonRenewals['Fund 2'][] = $tempArr;
                }

                if ($value->renewal_status == 'P' && !empty($value->director_review)) {
                    $category = $value->director_review_name;

                    if (!isset($additionalReviewRenewals['Fund 2'][$category])) {
                        $additionalReviewRenewals['Fund 2'][$category] = [];
                    }

                    $additionalReviewRenewals['Fund 2'][$category][] = $tempArr;
                } elseif ($value->renewal_status == 'P') {
                    $pendingRenewals['Fund 2'][] = $tempArr;
                }

                if(($value->renewal_status == 'A')) {
                    $generalRenewals['Fund 2'][] = $tempArr;
                }
            } else if ($value->company_id == 182) { // Fund 3
                if($value->note_count > 0) {
                    $foreclosureRenewals['Fund 3'][] = $tempArr;
                }

                if($value->note_count > 0) {
                    $paidOutRenewals['Fund 3'][] = $tempArr;
                }

                if($value->renewal_status == 'R') {
                    $nonRenewals['Fund 3'][] = $tempArr;
                }

                if ($value->renewal_status == 'P' && !empty($value->director_review)) {
                    $category = $value->director_review_name;

                    if (!isset($additionalReviewRenewals['Fund 3'][$category])) {
                        $additionalReviewRenewals['Fund 3'][$category] = [];
                    }

                    $additionalReviewRenewals['Fund 3'][$category][] = $tempArr;
                } elseif ($value->renewal_status == 'P') {
                    $pendingRenewals['Fund 2'][] = $tempArr;
                }

                if(($value->renewal_status == 'A')) {
                    $generalRenewals['Fund 3'][] = $tempArr;
                }
            }
        }

        $query = 
            "SELECT 
                m.application_id,
                m.company_id,
                m.ltv,
                m.mortgage_code, 
                act.abbr AS origination_company_name, 
                pt.type AS property_type, 
                pt.house_style AS property_house_style,
                s.l_name, 
                m.cities, 
                fn_GetCitiesByMortgageID(m.mortgage_id) cities_b,
                m.province,
                pt.province AS property_province,
                fn_GetPositionsByMortgageID(m.mortgage_id) AS pos,
                prop_mortgages.mortgage_firm_names AS other_mortgage,
                mi.term_end, 
                coll.collection,
                m.current_balance, 
                m.interest_rate,
                m.current_int, 
                m.monthly_pmt, 
                m.mortgage_id,
                sqt.second_prime,
                sqt.second_year,
                sqt.loan,
                sqt.int_comm_date,
                COALESCE(mv.prior_mtge, 0) AS prior_mtge,
                COALESCE(nt.note_flag_count, 0) AS note_flag_count,
                COALESCE(mpt.nsf_count, 0) AS nsf_count,
                COALESCE(nt2.note_count, 0) AS note_count,
    			COALESCE(pa.payout_count, 0) AS payout_count
            FROM mortgage_table m
            LEFT JOIN (
                SELECT interest_rate, max(term_end) term_end, mortgage_id 
                FROM mortgage_interest_rates_table 
                GROUP BY mortgage_id
            ) mi ON mi.mortgage_id = m.mortgage_id
            LEFT JOIN (
                SELECT
                    mp.mortgage_id,
                    SUM(
                        pm.balance * (mp.alpine_interest / 100)
                    ) AS prior_mtge
                FROM mortgage_properties_table mp
                JOIN property_mortgages_table pm
                    ON pm.property_id = mp.property_id
                WHERE
                    mp.position <> 'N/A'
                    AND pm.setting = 'master'
                    AND pm.payout = 'No'
                    AND CAST(pm.position AS DECIMAL(5,2))
                        < CAST(mp.position AS DECIMAL(5,2))
                GROUP BY mp.mortgage_id
            ) mv ON mv.mortgage_id = m.mortgage_id
            LEFT JOIN application_table a on m.application_id = a.application_id
            LEFT JOIN applicant_table ap on m.application_id = ap.application_id
            LEFT JOIN renewal_approval ra ON ra.mortgage_id = m.mortgage_id AND mi.term_end = ra.due_date
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN (
                SELECT a.application_id, a.mortgage_id, GROUP_CONCAT(DISTINCT b.category_name SEPARATOR '/') AS collection
                FROM notes_table a
                JOIN note_categories_table b ON a.category_id = b.category_id
                WHERE a.followed_up = 'no'
                AND a.category_id IN (7,32,36,37,39,33,56,34,3,69,71)
                GROUP BY a.application_id, a.mortgage_id
            ) AS coll ON coll.application_id = m.application_id AND coll.mortgage_id = m.mortgage_id
            LEFT JOIN (
			    SELECT mortgage_id,  COUNT(*) AS nsf_count
			    FROM mortgage_payments_table
			    WHERE is_nsf = 'yes'
			    GROUP BY mortgage_id
			) mpt ON mpt.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT application_id,  COUNT(*) AS note_flag_count
			    FROM notes_table
			    WHERE follower_up = '4824'
			    GROUP BY application_id
			) nt ON nt.application_id = m.application_id
            LEFT JOIN (
			    SELECT application_id, mortgage_id, COUNT(*) AS note_count
			    FROM notes_table
			    WHERE followed_up = 'no'
			    AND category_id = 7
			    GROUP BY application_id, mortgage_id
			) nt2 ON nt2.application_id = m.application_id AND nt2.mortgage_id = m.mortgage_id
			LEFT JOIN (
			    SELECT mortgage_id, COUNT(*) AS payout_count
			    FROM payout_approval
			    WHERE deleted_at IS NULL
			    AND (admin_status = 'A' OR broker_status = 'A')
			    GROUP BY mortgage_id
			) pa ON pa.mortgage_id = m.mortgage_id
            LEFT JOIN mortgage_table is_investment ON is_investment.mortgage_id = m.mortgage_id AND is_investment.mortgage_code = 'dummy card' AND is_investment.is_deleted = 'no'
            LEFT JOIN mortgage_table is_mortgage_and_is_investment ON is_mortgage_and_is_investment.application_id = m.application_id AND is_mortgage_and_is_investment.is_deleted = 'no'
            LEFT JOIN alpine_companies_table act ON act.id = a.company
            LEFT JOIN mortgage_properties_table mptyt ON mptyt.mortgage_id = m.mortgage_id
            LEFT JOIN saved_quote_table sqt ON sqt.application_id = m.application_id AND sqt.ready_buy = 'yes'
            LEFT JOIN property_table pt ON pt.property_id = mptyt.property_id
            LEFT JOIN (
                SELECT p.property_id, GROUP_CONCAT(b.firm_name SEPARATOR ', ') AS mortgage_firm_names
                FROM property_table p
                LEFT JOIN property_mortgages_table c ON c.property_id = p.property_id
                LEFT JOIN lender_firm_branches_table a ON c.lender_id = a.lender_branch_code
                LEFT JOIN lender_firm_table b ON a.lender_code = b.lender_code
                GROUP BY p.property_id
            ) AS prop_mortgages ON prop_mortgages.property_id = pt.property_id
            WHERE m.is_deleted = 'no' 
            AND (ra.status NOT IN ('A', 'R', 'P') OR ra.status IS NULL)
            AND m.current_balance > 0 
            AND m.payout_at IS NULL
            AND (m.ab_loan = 'No' or m.ab_loan = 'm_inv')
            AND m.company_id <> 0";
        $res = $this->applyExcelFilters($query, $filterOptions, "mortgage_table");

        foreach($res as $value) {
            $province = "";
            $secondPrime = $value->second_prime ?? 0;
            $secondYear = $value->second_year ?? 0;

            if($value->province == null || $value->province == ', ') {
                $province = $value->property_province;
            } else {
                $province = $value->province;
            }

            $tempArr = [
                'applicationId' => $value->application_id,
                'acctNumber' => $value->mortgage_code,
                'lastName' => $value->l_name,
                'city' => $value->cities_b,
                'province' => $province,
                'pos' => $value->pos,
                'ltv' => $value->ltv ?? 0,
                'termDueDate' => $value->term_end,
                'priorMtge' => $value->prior_mtge,
                'collStatus' => $value->collection,
                'currentBalance' => $value->current_balance,
                'org' => $value->interest_rate,
                'rate' => $value->current_int,
                'numberOfNSF' => $value->nsf_count,
                'currentMonthlyPayment' => $value->monthly_pmt,
                'mortgageId' => $value->mortgage_id,
                'noteCount' => $value->note_count,
                'payoutCount' => $value->payout_count,
            ];

            // Property Types Filter
            if (count($filterOptions['propertyTypes']) > 0) {
                if (!in_array($value->property_type, $filterOptions['propertyTypes'])) {
                    continue;
                }
            }

            // House Styles Filter
            if (count($filterOptions['houseStyles']) > 0) {
                if (!in_array($value->property_house_style, $filterOptions['houseStyles'])) {
                    continue;
                }
            }

            if($this->isVariableByDate($secondPrime, $secondYear, $value->loan, $value->int_comm_date, $filterOptions['termEndDueDateOrdered'])) {
                continue;
            }

            if($value->company_id == 16) { // Fund 1
                if(($value->payout_count > 0)) {
                    $paidOutRenewals['Fund 1'][] = $tempArr;
                }

                if(($value->note_count > 0)) {
                    $foreclosureRenewals['Fund 1'][] = $tempArr;
                }

                if(($value->payout_count == 0) && ($value->note_count == 0)) {
                    $generalRenewals['Fund 1'][] = $tempArr;
                }
            } else if ($value->company_id == 5) { // Fund 2
                if(($value->payout_count > 0)) {
                    $paidOutRenewals['Fund 2'][] = $tempArr;
                }

                if(($value->note_count > 0)) {
                    $foreclosureRenewals['Fund 2'][] = $tempArr;
                }

                if(($value->payout_count == 0) && ($value->note_count == 0)) {
                    $generalRenewals['Fund 2'][] = $tempArr;
                }
            } else if ($value->company_id == 182) { // Fund 3
                if(($value->payout_count > 0)) {
                    $paidOutRenewals['Fund 3'][] = $tempArr;
                }

                if(($value->note_count > 0)) {
                    $foreclosureRenewals['Fund 3'][] = $tempArr;
                }

                if(($value->payout_count == 0) && ($value->note_count == 0)) {
                    $generalRenewals['Fund 3'][] = $tempArr;
                }
            }
        }

        return [
            'foreclosureRenewals' => $foreclosureRenewals,
            'paidOutRenewals' => $paidOutRenewals,
            'nonRenewals' => $nonRenewals,
            'pendingRenewals' => $pendingRenewals,
            'generalRenewals' => $generalRenewals,
            'additionalReviewRenewals' => $additionalReviewRenewals,
        ];
    }

    public function generateExcelFile($filterOptions, $pageName) {
        $this->logger->info('RenewalApprovalBO->generateExcelFile',[$filterOptions, $pageName]);
        
        $excelData = $this->generateExcelData($filterOptions);
        $foreclosureRenewals = $excelData['foreclosureRenewals'];
        $paidOutRenewals     = $excelData['paidOutRenewals'];
        $nonRenewals         = $excelData['nonRenewals'];
        $pendingRenewals     = $excelData['pendingRenewals'];
        $generalRenewals     = $excelData['generalRenewals'];
        $additionalReviewRenewals = $excelData['additionalReviewRenewals'];

        $excel = new ExcelReportBO($this->logger, $this->db);
        $sheets = array();

        // Columns
        $excelHeaders = array();
        $excelHeaders = [
            ['name' => '#'],
            ['name' => 'Acct #'],
            ['name' => 'Last Name'],
            ['name' => 'City'],
            ['name' => 'Province'],
            ['name' => 'Pos'],
            ['name' => 'LTV'],
            ['name' => 'Term Due Date'],
            ['name' => 'Prior Mortgage'],
            ['name' => 'Coll Status'],
            ['name' => 'Current Bal'],
            ['name' => 'Orig Rate'],
            ['name' => 'Current Rate', 'bg' => 'FFFF00'],
            ['name' => 'NumOf NSF'],
            ['name' => 'New Rate', 'bg' => 'FFFF00'],
            ['name' => 'Marginable (1=Yes/0=No)'],
            ['name' => 'Old Pmt', 'bg' => 'B5E6A2'],
            ['name' => 'New Pmt', 'bg' => 'B5E6A2'],
            ['name' => 'Pmt Variance', 'bg' => 'B5E6A2'],
            ['name' => 'Comments'],
            ['name' => 'Variance'],
            ['name' => 'Weighted Average Variance'],
            ['name' => 'Old Weighted Avg Rate', 'bg' => 'A6C9EC'],
            ['name' => 'New Weighted Avg Rate', 'bg' => 'A6C9EC'],
        ];

        if($pageName == "renewals-summary") {
            $excelHeaders[] = ['name' => 'Assigned Member'];
        }

        // Populate Excel Rows
        foreach ($generalRenewals as $fundName => $fundData) {
            $tableCellIndex = 1;
            $rows = array();

            // New Renewals & In-progress Renewals
            foreach ($fundData as $rowData) {
                $tableCellIndex += 1;

                $content = array();
                // New Renewals & In-progress Renewals Row
                $content[] = [
                    'value'     => $rowData['applicationId'] ?? "",
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['acctNumber'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['lastName'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['city'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['province'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['pos'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['termDueDate'] ?? "",
                    'type'      => 'date',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['priorMtge'] ?? "",
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['collStatus'] ?? "",
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['currentBalance'] ?? "",
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                    'bg'    => 'FFFF00',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['numberOfNSF'] ?? "",
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                    'bg'    => 'FFFF00',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = ""; // marginable
                $content[] = [
                    'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                    'bg'    => 'B5E6A2',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                $content[] = [
                    'value' => $rowData['newMonthlyPayment'] ?? "",
                    'bg'    => 'B5E6A2',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                if(!empty($rowData['newMonthlyPayment'])) {
                    $content[] = [
                        'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                        'bg'    => 'B5E6A2',
                        'type'  => 'currency',
                        'textColor' => 'FF0000',
                        'align' => 'right',
                        'textColorComparison' => [
                            'gt' => 0,
                            'lt' => 0,
                            'gtTextColor' => '000000', 
                            'ltTextColor' => 'FF0000', 
                        ]
                    ];
                } else {
                    $content[] = [
                        'value' => "",
                        'bg'    => 'B5E6A2',
                        'type'  => 'currency',
                        'textColor' => '000000',
                        'align' => 'right'
                    ];
                }
                $content[] = [
                    'value'     => $rowData['renewalApprovalNotes'] ?? "",
                    'align'     => 'left'
                ];
                if(!empty($rowData['newInterestRate'])) {
                    $content[] = [
                        'formula' => '=O' . $tableCellIndex . '-M' . $tableCellIndex,
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'formula' => '=((O' . $tableCellIndex . '-M' . $tableCellIndex . ')*K' . $tableCellIndex . ')/K' . count($fundData) + 2,
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'formula' => '=(M' . $tableCellIndex . '*K' . $tableCellIndex . ')/K' . count($fundData) + 2,
                        'type'  => 'percent',
                        'bg'    => 'A6C9EC',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'formula' => '=(K' . $tableCellIndex . '*O' . $tableCellIndex . ')/K' . count($fundData) + 2,
                        'type'  => 'percent',
                        'bg'    => 'A6C9EC',
                        'align' => 'right'
                    ];
                } else {
                    $content[] = [
                        'value' => "",
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value' => "",
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value' => "",
                        'type'  => 'percent',
                        'bg'    => 'A6C9EC',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value' => "",
                        'type'  => 'percent',
                        'bg'    => 'A6C9EC',
                        'align' => 'right'
                    ];
                }

                if($pageName == "renewals-summary") {
                    $content[] = [
                        'value'     => $rowData['assignedName'] ?? "",
                        'align'     => 'left'
                    ];
                }

                $rows[]['content'] = $content;
            }

            $tableCellIndex += 1;

            // Summary Row
            $content = array();
            $content = array_merge($content, array_fill(0, 9, ""));
            $content[] = [
                'value'     => "Total Mtg Bal",
                'isBold'     => true,
                'align'     => 'left'
            ];
            $content[] = [
                'formula' => '=SUM(K2:K' . (count($fundData) + 1) . ')',
                'isBold'     => true,
                'type'  => 'currency',
                'align'     => 'right'
            ];
            $content = array_merge($content, array_fill(0, 7, ""));
            $content[] = [
                'formula' => '=SUM(S2:S' . (count($fundData) + 1) . ')',
                'type'  => 'currency',
                'isBold' => true,
                'textColor' => 'FF0000',
                'align' => 'right'
            ];
            $content = array_merge($content, array_fill(0, 2, ""));
            $content[] = [
                'formula' => '=SUM(V2:V' . (count($fundData) + 1) . ')',
                'bg'     => 'FFFF00',
                'isBold' => true,
                'type'   => 'percent',
                'align'  => 'right'
            ];
            $content[] = [
                'formula' => '=SUM(W2:W' . (count($fundData) + 1) . ')',
                'bg'     => 'FFFF00',
                'isBold' => true,
                'type'   => 'percent',
                'align'  => 'right'
            ];
            $content[] = [
                'formula' => '=SUM(X2:X' . (count($fundData) + 1) . ')',
                'bg'     => 'FFFF00',
                'isBold' => true,
                'type'   => 'percent',
                'align'  => 'right'
            ];
            $rows[]['content'] = $content;

            $tableCellIndex += 1;

            // Empty Row
            $rows[]['content'] = [];

            $tableCellIndex += 1;

            // Foreclosure Header Row
            $rows[]['content'] = $this->createExcelHeaderRow("Foreclosure", count($excelHeaders));

            // Foreclosure
            foreach ($foreclosureRenewals[$fundName] as $rowData) {
                $tableCellIndex += 1;

                // Foreclosure Renewals Row
                $content = array();
                $content[] = [
                    'value'     => $rowData['applicationId'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['acctNumber'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['lastName'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['city'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['province'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['pos'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['termDueDate'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'date',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['priorMtge'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['collStatus'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['currentBalance'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['numberOfNSF'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                ];
                $content[] = [
                    'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                $content[] = [
                    'value' => $rowData['newMonthlyPayment'] ?? "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'currency',
                    'align' => 'right'
                ];

                if(!empty($rowData['newMonthlyPayment'])) {
                    $content[] = [
                        'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                        'bg'    => 'D9D9D9',
                        'type'  => 'currency',
                        'textColor' => 'FF0000',
                        'align' => 'right',
                        'textColorComparison' => [
                            'gt' => 0,
                            'lt' => 0,
                            'gtTextColor' => '000000', 
                            'ltTextColor' => 'FF0000', 
                        ]
                    ];
                } else {
                    $content[] = [
                        'value' => "",
                        'bg'    => 'D9D9D9',
                        'type'  => 'currency',
                        'textColor' => '000000',
                        'align' => 'right'
                    ];
                }
                $content[] = [
                    'value'     => $rowData['renewalApprovalNotes'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];

                if($pageName == "renewals-summary") {
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'D9D9D9',
                        'align'     => 'left'
                    ];
                }
                $rows[]['content'] = $content;
            }

            $tableCellIndex += 1;

            // Payout Header Row
            $rows[]['content'] = $this->createExcelHeaderRow("Payout", count($excelHeaders));

            // Payout
            foreach ($paidOutRenewals[$fundName] as $rowData) {
                $tableCellIndex += 1;

                // Payout Renewals Row
                $content = array();
                $content[] = [
                    'value'     => $rowData['applicationId'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['acctNumber'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['lastName'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['city'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['province'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['pos'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['termDueDate'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'date',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['priorMtge'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['collStatus'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['currentBalance'] ?? "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                    'bg'        => 'D9D9D9',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['numberOfNSF'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                ];
                $content[] = [
                    'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                $content[] = [
                    'value' => $rowData['newMonthlyPayment'] ?? "",
                    'bg'    => 'D9D9D9',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                if(!empty($rowData['newMonthlyPayment'])) {
                    $content[] = [
                        'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                        'bg'    => 'D9D9D9',
                        'type'  => 'currency',
                        'textColor' => 'FF0000',
                        'align' => 'right',
                        'textColorComparison' => [
                            'gt' => 0,
                            'lt' => 0,
                            'gtTextColor' => '000000', 
                            'ltTextColor' => 'FF0000', 
                        ]
                    ];
                } else {
                    $content[] = [
                        'value' => "",
                        'bg'    => 'D9D9D9',
                        'type'  => 'currency',
                        'textColor' => '000000',
                        'align' => 'right'
                    ];
                }
                $content[] = [
                    'value'     => $rowData['renewalApprovalNotes'] ?? "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'D9D9D9',
                    'align'     => 'right'
                ];
                if($pageName == "renewals-summary") {
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'D9D9D9',
                        'align'     => 'left'
                    ];
                }
                $rows[]['content'] = $content;
            }

            $tableCellIndex += 1;

            // Non Renewal Header Row
            $rows[]['content'] = $this->createExcelHeaderRow("Non Renewal Approved", count($excelHeaders));

            // Non Renewal
            foreach ($nonRenewals[$fundName] as $rowData) {
                $tableCellIndex += 1;

                // Non Renewal Renewals Row
                $content = array();
                $content[] = [
                    'value'     => $rowData['applicationId'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['acctNumber'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['lastName'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['city'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['province'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['pos'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                    'bg'        => 'FFC000',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['termDueDate'] ?? "",
                    'bg'        => 'FFC000',
                    'type'      => 'date',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['priorMtge'] ?? "",
                    'bg'        => 'FFC000',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['collStatus'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => $rowData['currentBalance'] ?? "",
                    'bg'        => 'FFC000',
                    'type'      => 'currency',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                    'bg'        => 'FFC000',
                    'type'      => 'percent',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                    'bg'    => 'FFC000',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => $rowData['numberOfNSF'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                    'bg'    => 'FFC000',
                    'type'  => 'percent',
                    'align' => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'FFC000',
                ];
                $content[] = [
                    'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                    'bg'    => 'FFC000',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                $content[] = [
                    'value' => $rowData['newMonthlyPayment'] ?? "",
                    'bg'    => 'FFC000',
                    'type'  => 'currency',
                    'align' => 'right'
                ];
                if(!empty($rowData['newMonthlyPayment'])) {
                    $content[] = [
                        'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                        'bg'    => 'FFC000',
                        'type'  => 'currency',
                        'textColor' => 'FF0000',
                        'align' => 'right',
                        'textColorComparison' => [
                            'gt' => 0,
                            'lt' => 0,
                            'gtTextColor' => '000000', 
                            'ltTextColor' => 'FF0000', 
                        ]
                    ];
                } else {
                    $content[] = [
                        'value' => "",
                        'bg'    => 'FFC000',
                        'type'  => 'currency',
                        'textColor' => '000000',
                        'align' => 'right'
                    ];
                }
                $content[] = [
                    'value'     => $rowData['renewalApprovalNotes'] ?? "",
                    'bg'        => 'FFC000',
                    'align'     => 'left'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];
                $content[] = [
                    'value'     => "",
                    'bg'        => 'FFC000',
                    'align'     => 'right'
                ];

                if($pageName == "renewals-summary") {
                    $content[] = [
                        'value'     => $rowData['assignedName'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                }
                $rows[]['content'] = $content;
            }

            // Fund Manager Only View - Pending Renewals
            if($pageName == "new-renewals") {
                $tableCellIndex += 1;

                // Pending Header Row
                $rows[]['content'] = $this->createExcelHeaderRow("Pending", count($excelHeaders));

                // Pending Renewals
                foreach ($pendingRenewals[$fundName] as $rowData) {
                    $tableCellIndex += 1;

                    // Pending Renewals Row
                    $content = array();
                    $content[] = [
                        'value'     => $rowData['applicationId'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => $rowData['acctNumber'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['lastName'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['city'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['province'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['pos'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                        'bg'        => 'FFC000',
                        'type'      => 'percent',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => $rowData['termDueDate'] ?? "",
                        'bg'        => 'FFC000',
                        'type'      => 'date',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['priorMtge'] ?? "",
                        'bg'        => 'FFC000',
                        'type'      => 'currency',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => $rowData['collStatus'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => $rowData['currentBalance'] ?? "",
                        'bg'        => 'FFC000',
                        'type'      => 'currency',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                        'bg'        => 'FFC000',
                        'type'      => 'percent',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                        'bg'    => 'FFC000',
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value'     => $rowData['numberOfNSF'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                        'bg'    => 'FFC000',
                        'type'  => 'percent',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'FFC000',
                    ];
                    $content[] = [
                        'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                        'bg'    => 'FFC000',
                        'type'  => 'currency',
                        'align' => 'right'
                    ];
                    $content[] = [
                        'value' => $rowData['newMonthlyPayment'] ?? "",
                        'bg'    => 'FFC000',
                        'type'  => 'currency',
                        'align' => 'right'
                    ];
                    if(!empty($rowData['newMonthlyPayment'])) {
                        $content[] = [
                            'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                            'bg'    => 'FFC000',
                            'type'  => 'currency',
                            'textColor' => 'FF0000',
                            'align' => 'right',
                            'textColorComparison' => [
                                'gt' => 0,
                                'lt' => 0,
                                'gtTextColor' => '000000', 
                                'ltTextColor' => 'FF0000', 
                            ]
                        ];
                    } else {
                        $content[] = [
                            'value' => "",
                            'bg'    => 'FFC000',
                            'type'  => 'currency',
                            'textColor' => '000000',
                            'align' => 'right'
                        ];
                    }
                    $content[] = [
                        'value'     => $rowData['renewalApprovalNotes'] ?? "",
                        'bg'        => 'FFC000',
                        'align'     => 'left'
                    ];
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];
                    $content[] = [
                        'value'     => "",
                        'bg'        => 'FFC000',
                        'align'     => 'right'
                    ];

                    $rows[]['content'] = $content;
                }

                // Flagged Renewals
                foreach ($additionalReviewRenewals[$fundName] as $categoryName => $categoryData) {
                    $tableCellIndex += 1;

                    // Flagged Header Row
                    $rows[]['content'] = $this->createExcelHeaderRow($categoryName, count($excelHeaders));

                    foreach ($categoryData as $rowData) {
                        $tableCellIndex += 1;

                        // Flagged Renewals Row
                        $content = array();
                        $content[] = [
                            'value'     => $rowData['applicationId'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => $rowData['acctNumber'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['lastName'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['city'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['province'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['pos'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => isset($rowData['ltv']) ? (round((float) $rowData['ltv'],2))/100 : "",
                            'bg'        => 'F29D92',
                            'type'      => 'percent',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => $rowData['termDueDate'] ?? "",
                            'bg'        => 'F29D92',
                            'type'      => 'date',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['priorMtge'] ?? "",
                            'bg'        => 'F29D92',
                            'type'      => 'currency',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => $rowData['collStatus'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => $rowData['currentBalance'] ?? "",
                            'bg'        => 'F29D92',
                            'type'      => 'currency',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => isset($rowData['org']) ? (round((float) $rowData['org'],2))/100 : "",
                            'bg'        => 'F29D92',
                            'type'      => 'percent',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value' => isset($rowData['rate']) ? (round((float) $rowData['rate'],2))/100 : "",
                            'bg'    => 'F29D92',
                            'type'  => 'percent',
                            'align' => 'right'
                        ];
                        $content[] = [
                            'value'     => $rowData['numberOfNSF'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value' => !empty($rowData['newInterestRate']) ? (round((float) $rowData['newInterestRate'],2))/100 : "",
                            'bg'    => 'F29D92',
                            'type'  => 'percent',
                            'align' => 'right'
                        ];
                        $content[] = [
                            'value'     => "",
                            'bg'        => 'F29D92',
                        ];
                        $content[] = [
                            'value' => !empty($rowData['currentMonthlyPayment']) ? $rowData['currentMonthlyPayment'] : "",
                            'bg'    => 'F29D92',
                            'type'  => 'currency',
                            'align' => 'right'
                        ];
                        $content[] = [
                            'value' => $rowData['newMonthlyPayment'] ?? "",
                            'bg'    => 'F29D92',
                            'type'  => 'currency',
                            'align' => 'right'
                        ];
                        if(!empty($rowData['newMonthlyPayment'])) {
                            $content[] = [
                                'formula' => '=Q' . $tableCellIndex . '-R' . $tableCellIndex,
                                'bg'    => 'F29D92',
                                'type'  => 'currency',
                                'textColor' => 'FF0000',
                                'align' => 'right',
                                'textColorComparison' => [
                                    'gt' => 0,
                                    'lt' => 0,
                                    'gtTextColor' => '000000', 
                                    'ltTextColor' => 'FF0000', 
                                ]
                            ];
                        } else {
                            $content[] = [
                                'value' => "",
                                'bg'    => 'F29D92',
                                'type'  => 'currency',
                                'textColor' => '000000',
                                'align' => 'right'
                            ];
                        }
                        $content[] = [
                            'value'     => $rowData['renewalApprovalNotes'] ?? "",
                            'bg'        => 'F29D92',
                            'align'     => 'left'
                        ];
                        $content[] = [
                            'value'     => "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];
                        $content[] = [
                            'value'     => "",
                            'bg'        => 'F29D92',
                            'align'     => 'right'
                        ];

                        $rows[]['content'] = $content;
                    }
                }
            }

            $sheets[] = [
                'title' => $fundName,
                'columns' => $excelHeaders,
                'rows' => $rows
            ];
        }
        
        return $excel->formatMultipleSheets($sheets); 
    }

    private function createExcelHeaderRow($name, $columnCount) {
        $content = array();
        $content[] = [
            'value'     => $name,
            'isBold'     => true,
            'align'     => 'left'
        ];
        $content = array_merge($content, array_fill(0, ($columnCount - 1), ""));
        return $content;
    }

    private function applyExcelFilters($query, $filterOptions, $sourceTable) {
        $query_filters = [];

        // Application ID
        if (!empty($filterOptions['applicationId']) && !empty($filterOptions['applicationId']['id'])) {
            $query .= " AND m.application_id = ?";
            $query_filters[] = $filterOptions['applicationId']['id'];
        }

        // Account Numbers
        if (!empty($filterOptions['acctNumbers']) && !empty($filterOptions['acctNumbers']['id'])) {
            $query .= " AND m.mortgage_code = ?";
            $query_filters[] = $filterOptions['acctNumbers']['id'];
        }

        // Last Names
        if (!empty($filterOptions['lastNames']) && !empty($filterOptions['lastNames']['id'])) {
            $query .= " AND s.l_name = ?";
            $query_filters[] = $filterOptions['lastNames']['id'];
        }

        // Cities
        if (!empty($filterOptions['cities']) && !empty($filterOptions['cities']['id'])) {
            $query .= " AND m.cities LIKE ?";
            $query_filters[] = $filterOptions['cities']['id'] . '%';
        }

        // Origination Company
        if (count($filterOptions['originationCompanyNames']) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['originationCompanyNames']), '?'));
            $query .= " AND act.abbr IN ($placeholders)";
            foreach ($filterOptions['originationCompanyNames'] as $val) {
                $query_filters[] = $val;
            }
        }

        // Province
        if (count($filterOptions['provinces']) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['provinces']), '?'));
            $query .= " AND m.province IN ($placeholders)";
            foreach ($filterOptions['provinces'] as $val) {
                $query_filters[] = $val;
            }
        }

        // Position
        if (count($filterOptions['positions']) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['positions']), '?'));
            $query .= " AND pos IN ($placeholders)";
            foreach ($filterOptions['positions'] as $val) {
                $query_filters[] = $val;
            }
        }

        // Coll Status
        if (count($filterOptions['collStatuses'] ?? []) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['collStatuses']), '?'));
            $query .= " AND coll.collection IN ($placeholders)";
            foreach ($filterOptions['collStatuses'] as $val) {
                $query_filters[] = $val;
            }
        }

        // NSF
        if (count($filterOptions['nsfs'] ?? []) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['nsfs']), '?'));
            $query .= " AND nsf_count IN ($placeholders)";
            foreach ($filterOptions['nsfs'] as $val) {
                $query_filters[] = $val;
            }
        }

        // Flag
        if (count($filterOptions['flags'] ?? []) == 1) {
            if($filterOptions['flags'][0] == "Yes") {
                $query .= " AND note_flag_count > 0";
            } else {
                $query .= " AND note_flag_count <= 0";
            }
        }

        // Other Mortgagee
        if (count($filterOptions['otherMortgagees'] ?? []) > 0) {
            $placeholders = implode(',', array_fill(0, count($filterOptions['otherMortgagees']), '?'));
            $query .= " AND other_mortgage IN ($placeholders)";
            foreach ($filterOptions['otherMortgagees'] as $val) {
                if($val == "Alpine") {
                    $query_filters[] = null;
                } else {
                    $query_filters[] = $val;
                }
            }
        }

        // LTV
        if (!empty($filterOptions['ltvOrdered'])) {
            $operator = $filterOptions['ltvOperator']; 

            $query .= " AND m.ltv " . $operator . " ?";
            $query_filters[] = $filterOptions['ltvOrdered'];
        }

        // Prior Mortgage
        if (!empty($filterOptions['priorMtgeOrdered'])) {
            $operator = $filterOptions['priorMtgeOperator']; 

            $query .= " AND m.prior_mtge " . $operator . " ?";
            $query_filters[] = $filterOptions['priorMtgeOrdered'];
        }

        // Current Bal
        if (!empty($filterOptions['currentBalanceOrdered'])) {
            $operator = $filterOptions['currentBalanceOperator']; 

            $query .= " AND m.current_balance " . $operator . " ?";
            $query_filters[] = $filterOptions['currentBalanceOrdered'];
        }

        // Orig Rate
        if (!empty($filterOptions['orgOrdered'])) {
            $operator = $filterOptions['orgOperator']; 

            $query .= " AND m.interest_rate " . $operator . " ?";
            $query_filters[] = $filterOptions['orgOrdered'];
        }

        // Current Rate
        if (!empty($filterOptions['rateOrdered'])) {
            $operator = $filterOptions['rateOperator']; 

            $query .= " AND m.current_int " . $operator . " ?";
            $query_filters[] = $filterOptions['rateOrdered'];
        }

        // Old Pmt
        if (!empty($filterOptions['currentMonthlyPaymentOrdered'])) {
            $operator = $filterOptions['currentMonthlyPaymentOperator']; 

            $query .= " AND m.monthly_pmt " . $operator . " ?";
            $query_filters[] = $filterOptions['currentMonthlyPaymentOrdered'];
        }

        // Term Start Due Date
        if (!empty($filterOptions['termStartDueDateOrdered'])) {
            $date = DateTime::createFromFormat('Y-m-d', $filterOptions['termStartDueDateOrdered']);
            $query .= " AND mi.term_end >= ?";
            $query_filters[] = $date->format('Y-m-d');
        }

        // Term End Due Date
        if (!empty($filterOptions['termEndDueDateOrdered'])) {
            $date = DateTime::createFromFormat('Y-m-d', $filterOptions['termEndDueDateOrdered']);
            $query .= " AND mi.term_end <= ?";
            $query_filters[] = $date->format('Y-m-d');
        }

        if($sourceTable == "mortgage_table") {
            $query .="
                GROUP BY m.mortgage_id
                ORDER BY mi.term_end DESC
            ";
        } else if ($sourceTable == "renewal_approval") {
            $query .="
                GROUP BY ra.mortgage_id
                ORDER BY ra.due_date DESC
            ";
        }

        $res = $this->db->select($query, $query_filters);

        return $res;
    }

    public function getDocuments($applicationId, $mortgageId, $renewalApprovalId) {

        $renewalApproval = RenewalApproval::query()
        ->where('id', $renewalApprovalId)
        ->where('mortgage_id', $mortgageId)
        ->first();

        $listDocuments = null;

        if ($renewalApproval) {
            if ($renewalApproval->status == 'R') {
                $listDocuments = '(19)';
            } elseif ($renewalApproval->status == 'A') {
                $listDocuments = '(20)';
            }
        }

        $query = "select a.id document_id, b.name package_name, a.name as document_name 
                    from document a
                    join document_package b on b.id = a.document_package_id 
                   where b.id = 3
                     and a.id in $listDocuments";
        $resDocs = $this->db->select($query);

        $documents = array();
        foreach($resDocs as $key => $value) {

            $query = "select a.id application_document_id ,a.document_id, c.name package_name, b.name document_name, a.docs_sent_at, a.agreement_id,
                            b.dms_template_id, a.saved_quote_id, d.gross, d.disburse, a.sharepoint_document_id, a.sent_type
                        from application_document a
                inner join document b on b.id = a.document_id 
                inner join document_package c on c.id = b.document_package_id
                left join saved_quote_table d on d.saved_quote_id = a.saved_quote_id
                    where a.application_id = ?
                    and a.document_id = ?
                    and a.deleted_at is null
                    order by 
                        if(document_id = 1,1,99),
                        if(document_id = 2,2,99),
                        if(document_id = 3,3,99),
                        if(document_id = 4,4,99),
                        b.name,
                        a.docs_sent_at desc,
                        a.id desc";
            $data = $this->db->select($query,[$applicationId, $value->document_id]);

            $docsSentAt = '';
            $stageDate = '';
            $stage = '';
            $appDoc = false;

            foreach ($data as $dataDoc) {            

                $docsSentAt = '';
                $stageDate = '';
                $stage = '';

                if(!empty($dataDoc->docs_sent_at)) {
                    $d = new DateTime($dataDoc->docs_sent_at);
                    $docsSentAt = $d->format('m/d/Y H:i:s');
                }

                if(!empty($dataDoc->agreement_id)) {

                    $adobeAgreement = AdobeAgreement::query()
                    ->where('agreement_id',$dataDoc->agreement_id)
                    ->first();

                    if($adobeAgreement) {
                        $stage      = str_replace('_',' ',$adobeAgreement->last_event);
                        $stage      = strtolower($stage);
                        $stage      = ucwords($stage);
                        $d          = new DateTime($adobeAgreement->updated_at);
                        $stageDate  = $d->format('m/d/Y H:i:s');
                    }
                }

                $appDoc = true;

                $documents[] = [
                    "applicationDocumentId" => $dataDoc->application_document_id,
                    "documentId"   => $dataDoc->document_id,
                    "packageName"  => $dataDoc->package_name,
                    "documentName" => $dataDoc->document_name,
                    "docSentAt"    => $docsSentAt,
                    "stage"        => $stage,
                    "stageDate"    => $stageDate,
                ];
            }

            if (!$appDoc) {
                $documents[] = [
                    "applicationDocumentId" => 0,
                    "documentId"   => $value->document_id,
                    "packageName"  => $value->package_name,
                    "documentName" => $value->document_name,
                    "docSentAt"    => $docsSentAt,
                    "stage"        => $stage,
                    "stageDate"    => $stageDate,
                ];
            }
        }

        return $documents;
    }

    public function brokerRequest($renewalApprovalId, $emailObj) {
        $this->logger->info('RenewalApprovalBO->brokerRequest', [$renewalApprovalId, $emailObj]);

        $renewalApprovalTable = RenewalApproval::find($renewalApprovalId);

        if (!$renewalApprovalTable) {
            $this->logger->error("brokerRequest failed: renewal_approval record not found", ['id' => $renewalApprovalId]);
            return false;
        }

        $renewalApprovalTable->broker_approval_status = "R";

        if($renewalApprovalTable->save()) {
            // $email = new Email($this->logger);
            // if(isset($emailObj)) {
            //     foreach($emailObj['toAddress'] as $address) {
            //         $email->setToAddress($address);
            //         $email->setSubject($emailObj['subject']);
            //         $email->setBodyType($emailObj['bodyType']);
            //         $email->setBody($emailObj['body']);
            //         $email->send();
            //     }
            // }
            return true;
        }

        $this->logger->error('RenewalApprovalBO->brokerRequest Unable to save new broker request', [$renewalApprovalId]);
        return false;
    }

    public function brokerApproval($renewalApprovalId, $emailObj) {
        $this->logger->info('RenewalApprovalBO->brokerApproval', [$renewalApprovalId]);

        $userId = Auth::user()->user_id;

        $renewalApprovalTable = RenewalApproval::find($renewalApprovalId);

        if (!$renewalApprovalTable) {
            $this->logger->error("brokerApproval failed: renewal_approval record not found", ['id' => $renewalApprovalId]);
            return false;
        }

        $renewalApprovalTable->broker_approval_status = "A";
        $renewalApprovalTable->broker_approval_by = $userId;
        $renewalApprovalTable->broker_approval_at = (new DateTime())->format('Y-m-d H:i:s');
        
        if($renewalApprovalTable->save()) {
            // $email = new Email($this->logger);
            // if(isset($emailObj)) {
            //     foreach($emailObj['toAddress'] as $address) {
            //         $email->setToAddress($address);
            //         $email->setSubject($emailObj['subject']);
            //         $email->setBodyType($emailObj['bodyType']);
            //         $email->setBody($emailObj['body']);
            //         $email->send();
            //     }
            // }
            return true;
        }

        $this->logger->error('RenewalApprovalBO->brokerApproval Unable to save new broker approval', [$renewalApprovalId]);
        return false;
    }

    public function insert($renewalApproval, $mortgageRenewal) {

        $this->logger->info('RenewalApprovalBO->insert',[$renewalApproval, $mortgageRenewal]);

        if($this->isRenewalExist($renewalApproval['mortgageId'], $renewalApproval['dueDate'], 'A')) {
            $this->logger->info('RenewalApprovalBO->insert: Renewal already exists', [$renewalApproval['mortgageId'], $renewalApproval['dueDate']]);
            return [
                "status" => false,
                "message" => "Renewal already exists for this Mortgage and Due Date."
            ];
        }

        $userId = Auth::user()->user_id;

        $interestRates = [
            0 => $renewalApproval['newInterestRateAp'],
            1 => $renewalApproval['newInterestRateBp'],
            2 => $renewalApproval['newInterestRateCp']
        ];

        $osbRenewals = [
            0 => $mortgageRenewal['osbAtRenewalAP'],
            1 => $mortgageRenewal['osbAtRenewalBP'],
            2 => $mortgageRenewal['osbAtRenewalCP']
        ];

        $newMonthlyPmt = [
            0 => $renewalApproval['newMonthlyPaymentAp'],
            1 => $renewalApproval['newMonthlyPaymentBp'],
            2 => $renewalApproval['newMonthlyPaymentCp']
        ];

        $newOsb = [
            0 => $mortgageRenewal['osbAtNextTermEndAP'],
            1 => $mortgageRenewal['osbAtNextTermEndBP'],
            2 => $mortgageRenewal['osbAtNextTermEndCP']
        ];

        $newAer = [
            0 => $mortgageRenewal['annualEffectiveRateAP'],
            1 => $mortgageRenewal['annualEffectiveRateBP'],
            2 => $mortgageRenewal['annualEffectiveRateCP']
        ];

        $newSaeIntRate = [
            0 => $mortgageRenewal['semiAnnualEquivalentIntRateAP'],
            1 => $mortgageRenewal['semiAnnualEquivalentIntRateBP'],
            2 => $mortgageRenewal['semiAnnualEquivalentIntRateCP']
        ];

        $transfer_query = 
            "SELECT 
                mpt.transfer_mortgage_id
            FROM mortgage_payments_table mpt
            WHERE mpt.mortgage_id= ?
            AND transfer_mortgage='yes';
        ";

        $res_transfer = $this->db->select($transfer_query,[$mortgageRenewal['mortgageId']]);
        $cInvCardCount = 0;

        if (count($res_transfer) > 0) {
            $cInvCard = $this->getCInvCard($mortgageRenewal['mortgageId']);
            $cInvCardCount = count($cInvCard);
        }

        if ($mortgageRenewal['loanType'] == 'm_inv' && $cInvCardCount >= 2) {
            
            foreach ($cInvCard as $key => $row) {
                $c_pmt_pct[$key] = $row->monthly_pmt / $mortgageRenewal['originalMonthlyPayment'];
            }
        }

        if ($mortgageRenewal['loanType'] == 'c_inv') {

            $cInvCard = $this->getCInvCard($mortgageRenewal['parent']);
            $cInvCardCount = count($cInvCard);


            $saleInvestorRow = $this->getSalesInvestorRow($mortgageRenewal['applicationId'], $mortgageRenewal['mortgageId']);
            
            if(isset($saleInvestorRow[0])) {
                if (!empty($saleInvestorRow[0]->ap_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->ap_percent;
                } 
                if (!empty($saleInvestorRow[0]->bp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->bp_percent;
                }
                if (!empty($saleInvestorRow[0]->cp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->cp_percent;
                }
            }

            foreach ($cInvCard as $key => $row) {
                $c_percent[$row->mortgage_id] = $piece_percent[$key];
            }
        }

        $query_select_renewal_id = 
            "SELECT 
                MAX(renewal_id) AS last_renewal_id 
            FROM mortgage_renewals_table 
            WHERE mortgage_id= ?";

        $res_select_renewal_id = $this->db->select($query_select_renewal_id,[$mortgageRenewal['mortgageId']]);

        if(isset($res_select_renewal_id[0]->last_renewal_id)) {
            $renewal_id = (($res_select_renewal_id[0]->last_renewal_id * 1) + 1);
        } else {
            $renewal_id = 1;
        }

        $pd_start_date  = (isset($mortgageRenewal['newPostDatedChequesStartDate']) && $mortgageRenewal['newPostDatedChequesStartDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['newPostDatedChequesStartDate']))->format('Y-m-d')
            : "0000-00-00";
        $pd_end_date    = (isset($mortgageRenewal['newPostDatedChequesEndDate']) && $mortgageRenewal['newPostDatedChequesEndDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['newPostDatedChequesEndDate']))->format('Y-m-d')
            : "0000-00-00";
        $first_pmt_date = (isset($mortgageRenewal['postFirstPmtDate']) && $mortgageRenewal['postFirstPmtDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postFirstPmtDate']))->format('Y-m-d')
            : "0000-00-00";
        $term_date      = (isset($mortgageRenewal['postTermDate']) && $mortgageRenewal['postTermDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postTermDate']))->format('Y-m-d')
            : "0000-00-00";
        $start_date     = (isset($mortgageRenewal['postStartDate']) && $mortgageRenewal['postStartDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postStartDate']))->format('Y-m-d')
            : "0000-00-00";

        $mortgageRenewalTable = $this->db->insert('mortgage_renewals_table', [
            'mortgage_id'            => $mortgageRenewal['mortgageId'],
            'renewal_id'             => $renewal_id,
            'renewal_date'           => (new DateTime($mortgageRenewal['renewalDate']))->format('Y-m-d'),
            'next_pmt_date'          => (new DateTime($mortgageRenewal['nextPaymentDate']))->format('Y-m-d'),
            'next_term_due_date'     => (new DateTime($mortgageRenewal['nextTermDueDate']))->format('Y-m-d'),
            'new_interest_rate'      => (float) ($renewalApproval['newInterestRate']),
            'renewal_fee'            => (float) ($renewalApproval['renewalFee']),
            'osb_renewal'            => (float) round($mortgageRenewal['osbAtRenewal'], 2),
            'renewal_paid_over'      => $renewalApproval['renewalFeeToBePaidOver'],
            'signed_by_borrower'     => $mortgageRenewal['signedReceivedByBorrower'] ?? 'no',
            'signed_by_investor'     => $mortgageRenewal['signedReceivedByInvestor'] ?? 'no',
            'pd_start_date'          => $pd_start_date,
            'pd_first_pmt'           => (float) ($mortgageRenewal['newPostDatedCheques1stPmt']),
            'pd_end_date'            => $pd_end_date,
            'pd_reg_pmt'             => (float) ($mortgageRenewal['newPostDatedChequesRegPmt']),
            'new_monthly_pmt'        => (float) ($renewalApproval['newMonthlyPayment']),
            'new_osb'                => (float) round($mortgageRenewal['osbAtNextTermEndMaster'], 2),
            'new_aer'                => (float) round($mortgageRenewal['annualEffectiveRateMaster'], 2),
            'new_apr'                => (float) round($mortgageRenewal['apr'], 2),
            'new_sae_int_rate'       => (float) round($mortgageRenewal['semiAnnualEquivalentIntRateMaster'], 2),
            'completed_by'           => $userId,
            'approved_by'            => $userId,
            'start_value'            => (float) round($mortgageRenewal['postStartValue'], 2),
            'start_date'             => $start_date,
            'int_rate'               => (float) ($mortgageRenewal['postIntRate']),
            'num_pmts'               => (float) ($mortgageRenewal['postNumPmts']),
            'first_pmt'              => (float) ($mortgageRenewal['postFirstPmt']),
            'first_pmt_date'         => $first_pmt_date,
            'omit_first_pmt'         => $mortgageRenewal['postOmitFirstPmt'],
            'term_date'              => $term_date,
            'amort'                  => (float) round($mortgageRenewal['remainingAmortization'], 4),
            'comments'               => $mortgageRenewal['comments'] ?? "",
            'property_valuation'     => $mortgageRenewal['propertyValuation'],
            'property_valuation_fee' => (float) ($mortgageRenewal['propertyValuationFee']),
            'var_renew'              => "no"
        ]);

        if (!$mortgageRenewalTable) {
            $this->logger->error('RenewalApprovalBO->insert Failed to add record to mortgage_renewal_table',[$mortgageRenewal['mortgageId'], $renewal_id]);
            return [
                "status" => false,
                "message" => "Renewal could not be approved."
            ];
        }

        if ($cInvCardCount >= 2) { //have child cards

            foreach ($cInvCard as $key => $row){
                $mortgageRenewalTableChildCard = $this->insert('mortgage_renewals_table', [
                    'mortgage_id'       => $row->mortgage_id,
                    'renewal_id'        => $renewal_id,
                    'new_interest_rate' => $interestRates[$key],
                    'renewal_fee'       => $renewalApproval['renewalFee'] * $piece_percent[$key] / 100,
                    'new_monthly_pmt'   => $newMonthlyPmt[$key],
                    'osb_renewal'       => $osbRenewals[$key],
                    'new_osb'           => $newOsb[$key],
                    'new_sae_int_rate'  => $newSaeIntRate[$key],
                    'new_aer'           => $newAer[$key],
                    'int_rate'          => $row->interest_rate
                ]);

                if(!$mortgageRenewalTableChildCard) {
                    $this->logger->error('RenewalApprovalBO->insert Failed to add record to mortgage_renewal_table child card',[$mortgageRenewal['mortgageId'], $renewal_id]);
                    return [
                        "status" => false,
                        "message" => "Renewal could not be approved."
                    ];
                }
            }
        }

        $pmt_days = $mortgageRenewal['pmtDayArr'];
        $pmt_amts = $mortgageRenewal['pmtAmtArr'];
        
        foreach ($pmt_days as $pmt_idx => $pmt_day) {

            $mortgageRenewalPaymentsTable = $this->db->insert('mortgage_renewal_payments_table', [
                'mortgage_id' => $mortgageRenewal['mortgageId'],
                'renewal_id'  => $renewal_id,
                'pmt_day'     => (float) $pmt_day,
                'pmt_amt'     => (float) $pmt_amts[$pmt_idx],
            ]);

            if(!$mortgageRenewalPaymentsTable) {
                $this->logger->error('RenewalApprovalBO->insert Failed to add record to mortgage_renewal_payments_table',[$mortgageRenewal['mortgageId'], $renewal_id]);
                return [
                    "status" => false,
                    "message" => "Renewal could not be approved."
                ];
            }

            if ($cInvCardCount >= 2) { 
                foreach ($cInvCard as $key => $row) {
                    $mortgageRenewalPaymentsTableChildCard = $this->db->insert('mortgage_renewal_payments_table', [
                        'mortgage_id' => $row->mortgage_id,
                        'renewal_id'  => $renewal_id,
                        'pmt_day'     => (float) $pmt_day,
                        'pmt_amt'     => (float) $row->monthly_pmt,
                    ]);
                }

                if(!$mortgageRenewalPaymentsTableChildCard) {
                    $this->logger->error('RenewalApprovalBO->insert Failed to add record to mortgage_renewal_payments_table child card',[$row->mortgage_id, $renewal_id]);
                    return [
                        "status" => false,
                        "message" => "Renewal could not be approved."
                    ];
                }
            }
        }

        if (empty($mortgageRenewal['dueDate']) || ($mortgageRenewal['dueDate'] == "0000-00-00")) {
            $query_term_dd = 
                "SELECT MAX(term_end) AS last_term_end 
                   FROM mortgage_interest_rates_table 
                  WHERE mortgage_id = ?";
            $res_term_dd = $this->db->select($query_term_dd,[$mortgageRenewal['mortgageId']]);

            $due_date_2 = $res_term_dd[0]->last_term_end;

            if(!isset($res_term_dd[0]->last_term_end)) {
                $this->logger->error('RenewalApprovalBO->insert Due Date can not be found',[$mortgageRenewal['mortgageId']]);
            }
        } else {
            $due_date_2 = $mortgageRenewal['dueDate'];
        }        
        
        $today = date("F j, Y, g:i a");
        $all_investors = $mortgageRenewal['investors'];

        $userBO = new UserBO($this->logger);
        $userDetails = $userBO->show($userId);

        $note_text = $today . " , " . $mortgageRenewal['mortgageCode'] . " Account up for renewal. Calculate and forward to broker for approval." . $all_investors . " " . ($userDetails['fullName'] ??  '') . " Renewal due Date:" . $due_date_2 . " "; 

        $query_task_arrays = 
            "SELECT 
                t.tc_type_id,
                t.tc_operator_id,
                t.tc_company_id,
                t.tc_province_id,
                tc.abbr,
                tc.comments 
            FROM tasks_table t  
            LEFT JOIN task_categories_table tc ON tc.id=t.tc_type_id";
        $res_task_arrays = $this->db->select($query_task_arrays,[]);

        $company = $mortgageRenewal['company'];
        $province = "";

        $follower_id = $this->getOperator2($res_task_arrays, 'RWL', $company, $province);
        if (empty($follower_id)) {
            $follower_id = $mortgageRenewal['agent'];
        } 

        $notesTable = $this->db->insert('notes_table', [
            'note_id'         => null,                 
            'application_id'  => $mortgageRenewal['applicationId'],
            'author_id'       => $userId,
            'last_updated_by' => $userId,
            'category_id'     => 5,
            'note_date_time'  => date('Y-m-d H:i:s'), 
            'last_updated'    => date('Y-m-d H:i:s'),  
            'followup_date'   => date('Y-m-d H:i:s', strtotime('+1 day')), 
            'follower_up'     => $follower_id,
            'followed_up'     => 'no',
            'note_text'       => $note_text,  
        ]);

        if(!$notesTable) {
            $this->logger->error('RenewalApprovalBO->insert Failed to add record to notes_table',[$mortgageRenewal['applicationId']]);
        }

        $query_locked = 
            "SELECT * 
               FROM period_locks_table 
              WHERE company_id = ?
                AND start_date <= ?
                AND end_date >= ?
                AND locked = 'yes'";
        $res_locked = $this->db->select($query_locked,[$mortgageRenewal['applicationId'], $mortgageRenewal['renewalDate'], $mortgageRenewal['renewalDate']]);

        if(count($res_locked) > 0) {
            $alert_message = "WARNING: Renewal date is in a LOCKED month.";
            return [
                "status" => false,
                "message" => $alert_message
            ];
        }
        
        if(RenewalApproval::find($renewalApproval['renewalApprovalId'])) {
            $renewalApprovalTable = RenewalApproval::find($renewalApproval['renewalApprovalId']);
        } else {
            $renewalApprovalTable = new RenewalApproval;
            $renewalApprovalTable->created_by = $userId;
        }

        $renewalApprovalTable->mortgage_id = $renewalApproval['mortgageId'];
        $renewalApprovalTable->due_date = $renewalApproval['dueDate'];
        $renewalApprovalTable->new_interest_rate = $renewalApproval['newInterestRate'];
        $renewalApprovalTable->new_interest_rate_ap = $renewalApproval['newInterestRateAp'];
        $renewalApprovalTable->new_interest_rate_bp = $renewalApproval['newInterestRateBp'];
        $renewalApprovalTable->new_interest_rate_cp = $renewalApproval['newInterestRateCp'];
        $renewalApprovalTable->new_monthly_payment = $renewalApproval['newMonthlyPayment'];
        $renewalApprovalTable->new_monthly_payment_ap = $renewalApproval['newMonthlyPaymentAp'];
        $renewalApprovalTable->new_monthly_payment_bp = $renewalApproval['newMonthlyPaymentBp'];
        $renewalApprovalTable->new_monthly_payment_cp = $renewalApproval['newMonthlyPaymentCp'];
        $renewalApprovalTable->renewal_fee = $renewalApproval['renewalFee'];
        $renewalApprovalTable->renewal_fee_ap = $renewalApproval['renewalFeeAp'];
        $renewalApprovalTable->renewal_fee_bp = $renewalApproval['renewalFeeBp'];
        $renewalApprovalTable->renewal_fee_cp = $renewalApproval['renewalFeeCp'];
        $renewalApprovalTable->renewal_fee_to_be_paid_over = $renewalApproval['renewalFeeToBePaidOver'];
        $renewalApprovalTable->status = "A";
        $renewalApprovalTable->renewal_id = $renewal_id;
        $renewalApprovalTable->notes = $renewalApproval['notes'];
        $renewalApprovalTable->updated_by = $userId;
        $renewalApprovalTable->approved_at = (new DateTime())->format('Y-m-d H:i:s');
        $renewalApprovalTable->approved_by = $userId;

        if(!$renewalApprovalTable->save()) {
            $this->logger->error('RenewalApprovalBO->insert Unable to update renewal_approval table',[$renewalApproval['mortgageId'], $renewal_id]);
            return [
                "status" => false,
                "message" => "Renewal could not be approved."
            ];
        }

        return [
            "status" => true,
            "message" => "Renewal succesfully approved",
            "data" => null
        ];
    }

    public function pending($renewalApproval) {

        $this->logger->info('RenewalApprovalBO->pending',[$renewalApproval]);

        $userId = Auth::user()->user_id;

        if($this->isRenewalExist($renewalApproval['mortgageId'], $renewalApproval['dueDate'], null)) {
            $this->logger->info('RenewalApprovalBO->insert: Renewal already exists', [$renewalApproval['mortgageId'], $renewalApproval['dueDate']]);
            return [
                "status" => false,
                "message" => "Renewal already exists for this Mortgage and Due Date."
            ];
        }

        if(RenewalApproval::find($renewalApproval['renewalApprovalId'])) {
            $renewalApprovalTable = RenewalApproval::find($renewalApproval['renewalApprovalId']);
        } else {
            $renewalApprovalTable = new RenewalApproval;
            $renewalApprovalTable->created_by = $userId;
        }

        $renewalApprovalTable->mortgage_id = $renewalApproval['mortgageId'];
        $renewalApprovalTable->due_date = $renewalApproval['dueDate'];
        $renewalApprovalTable->new_interest_rate = $renewalApproval['newInterestRate'];
        $renewalApprovalTable->new_interest_rate_ap = $renewalApproval['newInterestRateAp'];
        $renewalApprovalTable->new_interest_rate_bp = $renewalApproval['newInterestRateBp'];
        $renewalApprovalTable->new_interest_rate_cp = $renewalApproval['newInterestRateCp'];
        $renewalApprovalTable->new_monthly_payment = $renewalApproval['newMonthlyPayment'];
        $renewalApprovalTable->new_monthly_payment_ap = $renewalApproval['newMonthlyPaymentAp'];
        $renewalApprovalTable->new_monthly_payment_bp = $renewalApproval['newMonthlyPaymentBp'];
        $renewalApprovalTable->new_monthly_payment_cp = $renewalApproval['newMonthlyPaymentCp'];
        $renewalApprovalTable->renewal_fee = $renewalApproval['renewalFee'];
        $renewalApprovalTable->renewal_fee_ap = $renewalApproval['renewalFeeAp'];
        $renewalApprovalTable->renewal_fee_bp = $renewalApproval['renewalFeeBp'];
        $renewalApprovalTable->renewal_fee_cp = $renewalApproval['renewalFeeCp'];
        $renewalApprovalTable->renewal_fee_to_be_paid_over = $renewalApproval['renewalFeeToBePaidOver'];
        $renewalApprovalTable->director_review = $renewalApproval['additionalReviewCategory'] ?? null;
        $renewalApprovalTable->status = "P";
        $renewalApprovalTable->notes = $renewalApproval['notes'];
        $renewalApprovalTable->updated_by = $userId;

        if (!$renewalApprovalTable->save()) {
            $this->logger->error('RenewalApprovalBO->pending Unable to insert new row into renewal_approval table',[$renewalApproval['mortgageId']]);
            return [
                "status" => false,
                "message" => "Renewal could not be transferred to pending."
            ];
        }

        return [
            "status" => true,
            "message" => "Renewal succesfully transferred to pending.",
            "data" => null
        ];
    }

    public function nonRenewal($renewalApproval) {

        $this->logger->info('RenewalApprovalBO->nonRenewal',[$renewalApproval]);

        $userId = Auth::user()->user_id;

        if($this->isRenewalExist($renewalApproval['mortgageId'], $renewalApproval['dueDate'], 'R')) {
            $this->logger->info('RenewalApprovalBO->insert: Renewal already exists', [$renewalApproval['mortgageId'], $renewalApproval['dueDate']]);
            return [
                "status" => false,
                "message" => "Renewal already exists for this Mortgage and Due Date."
            ];
        }

        if(RenewalApproval::find($renewalApproval['renewalApprovalId'])) {
            $renewalApprovalTable = RenewalApproval::find($renewalApproval['renewalApprovalId']);
            $renewalApprovalTable->rejected_at = (new DateTime())->format('Y-m-d H:i:s');
            $renewalApprovalTable->rejected_by = $userId;
        } else {
            $renewalApprovalTable = new RenewalApproval;
            $renewalApprovalTable->created_by = $userId;
        }

        $renewalApprovalTable->mortgage_id = $renewalApproval['mortgageId'];
        $renewalApprovalTable->due_date = $renewalApproval['dueDate'];
        $renewalApprovalTable->new_interest_rate = $renewalApproval['newInterestRate'];
        $renewalApprovalTable->new_interest_rate_ap = $renewalApproval['newInterestRateAp'];
        $renewalApprovalTable->new_interest_rate_bp = $renewalApproval['newInterestRateBp'];
        $renewalApprovalTable->new_interest_rate_cp = $renewalApproval['newInterestRateCp'];
        $renewalApprovalTable->new_monthly_payment = $renewalApproval['newMonthlyPayment'];
        $renewalApprovalTable->new_monthly_payment_ap = $renewalApproval['newMonthlyPaymentAp'];
        $renewalApprovalTable->new_monthly_payment_bp = $renewalApproval['newMonthlyPaymentBp'];
        $renewalApprovalTable->new_monthly_payment_cp = $renewalApproval['newMonthlyPaymentCp'];
        $renewalApprovalTable->renewal_fee = $renewalApproval['renewalFee'];
        $renewalApprovalTable->renewal_fee_ap = $renewalApproval['renewalFeeAp'];
        $renewalApprovalTable->renewal_fee_bp = $renewalApproval['renewalFeeBp'];
        $renewalApprovalTable->renewal_fee_cp = $renewalApproval['renewalFeeCp'];
        $renewalApprovalTable->renewal_fee_to_be_paid_over = $renewalApproval['renewalFeeToBePaidOver'];
        $renewalApprovalTable->status = "R";
        $renewalApprovalTable->notes = $renewalApproval['notes'];
        $renewalApprovalTable->updated_by = $userId;
        $renewalApprovalTable->rejected_at = (new DateTime())->format('Y-m-d H:i:s');
        $renewalApprovalTable->rejected_by = $userId;
        
        if (!$renewalApprovalTable->save()) {
            $this->logger->error('RenewalApprovalBO->insert Unable to insert new row into renewal_approval table',[$renewalApproval['mortgageId']]);
            return [
                "status" => false,
                "message" => "Renewal could not be converted to a non-renewal"
            ];
        }

        return [
            "status" => true,
            "message" => "Renewal succesfully converted to a non-renewal",
            "data" => null
        ];
    }

    public function getFilterOption($filterName) {
        $this->logger->info('RenewalApprovalBO->getFilterOption', [$filterName]);

        $userId = Auth::user()->user_id;

        $result = FilterOption::where('user_id', $userId)
            ->where('name', $filterName)
            ->first();

        if ($result) {
            return $result->config;
        } 

        return false;
    }

    public function insertFilterOption($filterName, $filterOptions) {

        $this->logger->info('RenewalApprovalBO->insertFilterOption',[$filterName, $filterOptions]);

        $userId = Auth::user()->user_id;

        $result = FilterOption::where('user_id', $userId)
            ->where('name', $filterName)
            ->first();

        if ($result) {
            $filterOption = $result;
            $filterOption->config = $filterOptions;
            $filterOption->updated_by = $userId;
        } else {
            $filterOption = new FilterOption;
            $filterOption->created_by = $userId;
            $filterOption->user_id = $userId;
            $filterOption->name = $filterName;
            $filterOption->config = $filterOptions;
            $filterOption->updated_by = $userId;
        }

        if (!$filterOption->save()) {
            $this->logger->error('RenewalApprovalBO->insertFilterOption: Unable to save filter options record',[$filterName, $filterOptions]);
            return false;
        }

        return true;
    }

    public function getDocumentSentDate($mortgageId, $renewalId) {
        $this->logger->info('RenewalApprovalBO->getDocumentSentDate',[$mortgageId, $renewalId]);

        $query_document_sent_date = "SELECT document_sent_date FROM mortgage_renewals_table WHERE mortgage_id = ? AND renewal_id = ?";
        $res_document_sent_date = $this->db->select($query_document_sent_date,[$mortgageId, $renewalId]);

        if(count($res_document_sent_date) > 0) {
            $document_sent_date = $res_document_sent_date[0]->document_sent_date;
            return $document_sent_date;
        } else {
            $this->logger->error('Failed to get document_sent_date', [$mortgageId, $renewalId]);
            return false;
        }
    }

    public function setDocumentSentDate($mortgageId, $renewalId) {
        $this->logger->info('RenewalApprovalBO->setDocumentSentDate',[$mortgageId, $renewalId]);

        $userId = Auth::user()->user_id;

        $mortageRenewalsTableUpdate = MortgageRenewalsTable::where('mortgage_id', $mortgageId)
        ->where('renewal_id', $renewalId)
        ->update([
            'document_sent_date'   => now(),
            'document_sent_by'     => $userId
        ]);

        return true;
    }

    public function assignAgents($selectedRenewalsId, $brokerId, $emailObj) {

        $this->logger->info('RenewalApprovalBO->assignAgents',[$selectedRenewalsId]);

        $userId = Auth::user()->user_id;

        $userBO = new UserBO($this->logger);
        $user = $userBO->getUserName($brokerId);
        // $emailBody = $user . " has been assigned a mortgage renewal";

        $updated = RenewalApproval::whereIn('id', $selectedRenewalsId)->update([
            'assigned_id' => $brokerId,
            'assigned_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'assigned_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($updated > 0) {
            // $email = new Email($this->logger);
            // if(isset($emailObj)) {
            //     foreach($emailObj['toAddress'] as $address) {
            //         $email->setToAddress($address);
            //         $email->setSubject($emailObj['subject']);
            //         $email->setBodyType($emailObj['bodyType']);
            //         $email->setBody($emailBody);
            //         $email->send();
            //     }
            // }
            return true;
        }

        $this->logger->error('RenewalApprovalBO->assignAgents Unable to assign agent to unassigned renewal', [$selectedRenewalsId, $brokerId]);
        return false;
    }

    public function update($mortgageRenewal) {
        $this->logger->info('RenewalApprovalBO->update',[$mortgageRenewal]);

        $userId = Auth::user()->user_id;
        $piece_percent = [];
        $c_percent = [];

        $interestRates = [
            0 => $mortgageRenewal['apInterestRate'],
            1 => $mortgageRenewal['bpInterestRate'],
            2 => $mortgageRenewal['cpInterestRate']
        ];

        $osbRenewals = [
            0 => $mortgageRenewal['osbAtRenewalAP'],
            1 => $mortgageRenewal['osbAtRenewalBP'],
            2 => $mortgageRenewal['osbAtRenewalCP']
        ];

        $newMonthlyPmt = [
            0 => $mortgageRenewal['suggestedNewPaymentAP'],
            1 => $mortgageRenewal['suggestedNewPaymentBP'],
            2 => $mortgageRenewal['suggestedNewPaymentCP']
        ];

        $newOsb = [
            0 => $mortgageRenewal['osbAtNextTermEndAP'],
            1 => $mortgageRenewal['osbAtNextTermEndBP'],
            2 => $mortgageRenewal['osbAtNextTermEndCP']
        ];

        $newAer = [
            0 => $mortgageRenewal['annualEffectiveRateAP'],
            1 => $mortgageRenewal['annualEffectiveRateBP'],
            2 => $mortgageRenewal['annualEffectiveRateCP']
        ];

        $newSaeIntRate = [
            0 => $mortgageRenewal['semiAnnualEquivalentIntRateAP'],
            1 => $mortgageRenewal['semiAnnualEquivalentIntRateBP'],
            2 => $mortgageRenewal['semiAnnualEquivalentIntRateCP']
        ];

        $transfer_query = 
            "SELECT mpt.transfer_mortgage_id
               FROM mortgage_payments_table mpt
              WHERE mpt.mortgage_id= ?
                AND transfer_mortgage='yes'";
        $res_transfer = $this->db->select($transfer_query,[$mortgageRenewal['mortgageId']]);
        $cInvCardCount = 0;

        if (count($res_transfer) > 0) {
            $cInvCard = $this->getCInvCard($mortgageRenewal['mortgageId']);
            $cInvCardCount = count($cInvCard);
        }

        if ($mortgageRenewal['loanType'] == 'm_inv' && $cInvCardCount >= 2) {
            
            $saleInvestorRow = $this->getSalesInvestorRow($mortgageRenewal['applicationId'], $mortgageRenewal['mortgageId']);
            
            if(isset($saleInvestorRow[0])) {
                if (!empty($saleInvestorRow[0]->ap_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->ap_percent;
                } 
                if (!empty($saleInvestorRow[0]->bp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->bp_percent;
                }
                if (!empty($saleInvestorRow[0]->cp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->cp_percent;
                }
            }
            
            foreach ($cInvCard as $key => $row) {
                $c_pmt_pct[$key] = $row->monthly_pmt / $mortgageRenewal['originalMonthlyPayment'];
            }
        }

        if ($mortgageRenewal['loanType'] == 'c_inv') {

            $cInvCard = $this->getCInvCard($mortgageRenewal['parent']);
            $cInvCardCount = count($cInvCard);

            $saleInvestorRow = $this->getSalesInvestorRow($mortgageRenewal['applicationId'], $mortgageRenewal['mortgageId']);
            
            if(isset($saleInvestorRow[0])) {
                if (!empty($saleInvestorRow[0]->ap_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->ap_percent;
                } 
                if (!empty($saleInvestorRow[0]->bp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->bp_percent;
                }
                if (!empty($saleInvestorRow[0]->cp_inv_co)) {
                    $piece_percent[] = $saleInvestorRow[0]->cp_percent;
                }
            }

            foreach ($cInvCard as $key => $row) {
                $c_percent[$row->mortgage_id] = $piece_percent[$key];
            }
        }

        $pd_start_date  = (isset($mortgageRenewal['newPostDatedChequesStartDate']) && $mortgageRenewal['newPostDatedChequesStartDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['newPostDatedChequesStartDate']))->format('Y-m-d')
            : "0000-00-00";
        $pd_end_date    = (isset($mortgageRenewal['newPostDatedChequesEndDate']) && $mortgageRenewal['newPostDatedChequesEndDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['newPostDatedChequesEndDate']))->format('Y-m-d')
            : "0000-00-00";
        $first_pmt_date = (isset($mortgageRenewal['postFirstPmtDate']) && $mortgageRenewal['postFirstPmtDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postFirstPmtDate']))->format('Y-m-d')
            : "0000-00-00";
        $term_date      = (isset($mortgageRenewal['postTermDate']) && $mortgageRenewal['postTermDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postTermDate']))->format('Y-m-d')
            : "0000-00-00";
        $start_date     = (isset($mortgageRenewal['postStartDate']) && $mortgageRenewal['postStartDate'] != "0000-00-00")
            ? (new DateTime($mortgageRenewal['postStartDate']))->format('Y-m-d')
            : "0000-00-00";

        $mortageRenewalsTable = MortgageRenewalsTable::where('mortgage_id', $mortgageRenewal['mortgageId'])
            ->where('renewal_id', $mortgageRenewal['renewalId'])
            ->first();

        // if($mortageRenewalsTable->new_interest_rate !== (float) $mortgageRenewal['newInterestRate']) {
        //     $toAddresses = ["adam@amurgroup.ca", "joy@amurgroup.ca"];
        //     $emailBody = "Mortgage Renewal Interest Rate Change Alert<br><br>";
        //     $emailBody .= "Mortgage Code: " . $mortgageRenewal['mortgageCode'] . "<br>";
        //     $emailBody .= "Old Interest Rate: " . $mortageRenewalsTable->new_interest_rate . "%<br>";
        //     $emailBody .= "New Interest Rate: " . (float) $mortgageRenewal['newInterestRate'] . "%<br>";

        //     foreach($toAddresses as $toAddress) {
        //         $email = new Email($this->logger);
        //         $email->setToAddress($toAddress);
        //         $email->setSubject("Mortgage Renewal Interest Rate Change Alert");
        //         $email->setBodyType("html");
        //         $email->setBody($emailBody);
        //         $email->send();
        //     }
        // }

        $mortageRenewalsTableUpdate = MortgageRenewalsTable::where('mortgage_id', $mortgageRenewal['mortgageId'])
            ->where('renewal_id', $mortgageRenewal['renewalId'])
            ->update([
                'renewal_date'            => (new DateTime($mortgageRenewal['renewalDate']))->format('Y-m-d'),
                'next_pmt_date'           => (new DateTime($mortgageRenewal['nextPaymentDate']))->format('Y-m-d'),
                'next_term_due_date'      => (new DateTime($mortgageRenewal['nextTermDueDate']))->format('Y-m-d'),
                'new_interest_rate'       => (float) $mortgageRenewal['newInterestRate'],
                'renewal_fee'             => (float) $mortgageRenewal['renewalFee'],
                'osb_renewal'             => (float) round($mortgageRenewal['osbAtRenewal'], 2),
                'renewal_paid_over'       => $mortgageRenewal['renewalFeeToBePaidOver'] ?? 'no',
                'signed_by_borrower'      => $mortgageRenewal['signedReceivedByBorrower'] ?? 'no',
                'signed_by_investor'      => $mortgageRenewal['signedReceivedByInvestor'],
                'completed_by'            => $userId,
                'approved_by'             => $mortgageRenewal['approvedBy'],
                'pd_start_date'           => $pd_start_date,
                'pd_first_pmt'            => (float) $mortgageRenewal['newPostDatedCheques1stPmt'],
                'pd_end_date'             => $pd_end_date,
                'pd_reg_pmt'              => (float) $mortgageRenewal['newPostDatedChequesRegPmt'],
                'new_monthly_pmt'         => (float) $mortgageRenewal['newMonthlyPmtMaster'],
                'new_osb'                 => (float) round($mortgageRenewal['osbAtNextTermEndMaster'], 2),
                'new_aer'                 => (float) round($mortgageRenewal['annualEffectiveRateMaster'], 2),
                'new_apr'                 => (float) round($mortgageRenewal['apr'], 2),
                'new_sae_int_rate'        => (float) round($mortgageRenewal['semiAnnualEquivalentIntRateMaster'],2),
                'start_value'             => (float) round($mortgageRenewal['postStartValue'], 2),
                'start_date'              => $start_date,
                'int_rate'                => (float) $mortgageRenewal['postIntRate'],
                'num_pmts'                => (float) $mortgageRenewal['postNumPmts'],
                'first_pmt'               => (float) $mortgageRenewal['postFirstPmt'],
                'first_pmt_date'          => $first_pmt_date,
                'omit_first_pmt'          => $mortgageRenewal['postOmitFirstPmt'],
                'term_date'               => $term_date,
                'amort'                   => (float) round($mortgageRenewal['remainingAmortization'], 4),
                'property_valuation'      => $mortgageRenewal['propertyValuation'],
                'property_valuation_fee'  => (float) $mortgageRenewal['propertyValuationFee'],
                'comments'                => $mortgageRenewal['comments']
            ]);

        if ($mortageRenewalsTableUpdate === 0) {
            $this->logger->error('RenewalApprovalBO->update Failed to update record in mortgage_renewal_table',[$mortgageRenewal['mortgageId'], $mortgageRenewal['renewalId']]);
            return false;
        }

        if ($cInvCardCount>= 2) { // have child cards
            foreach ($cInvCard as $key => $row) {

                $mortageRenewalsTableUpdateChildCard = MortgageRenewalsTable::where('mortgage_id', $row->mortgage_id)
                    ->where('renewal_id', $mortgageRenewal['renewalId'])
                    ->update([
                        'renewal_date'         => (new DateTime($mortgageRenewal['renewalDate']))->format('Y-m-d'),
                        'next_pmt_date'        => (new DateTime($mortgageRenewal['nextPaymentDate']))->format('Y-m-d'),
                        'next_term_due_date'   => (new DateTime($mortgageRenewal['nextTermDueDate']))->format('Y-m-d'),
                        'new_interest_rate'    => (float) $interestRates[$key],
                        'renewal_fee'          => ($piece_percent[$key] / 100) * (float) $mortgageRenewal['renewalFee'],
                        'osb_renewal'          => (float) $osbRenewals[$key],
                        'renewal_paid_over'    => $mortgageRenewal['renewalFeeToBePaidOver'],
                        'signed_by_borrower'   => $mortgageRenewal['signedReceivedByBorrower'],
                        'signed_by_investor'   => $mortgageRenewal['signedReceivedByInvestor'],
                        'completed_by'         => $userId,
                        'approved_by'          => $mortgageRenewal['approvedBy'],
                        'pd_start_date'        => (new DateTime($mortgageRenewal['newPostDatedChequesStartDate']))->format('Y-m-d'),
                        'pd_first_pmt'         => (float) $mortgageRenewal['newPostDatedCheques1stPmt'],
                        'pd_end_date'          => (new DateTime($mortgageRenewal['newPostDatedChequesEndDate']))->format('Y-m-d'),
                        'pd_reg_pmt'           => (float) $mortgageRenewal['newPostDatedChequesRegPmt'],
                        'new_monthly_pmt'      => (float) $newMonthlyPmt[$key],
                        'new_osb'              => (float) $newOsb[$key],
                        'new_aer'              => (float) $newAer[$key],
                        'new_sae_int_rate'     => (float) $newSaeIntRate[$key],
                        'start_value'          => (float) $mortgageRenewal['postStartValue'],
                        'start_date'           => (new DateTime($mortgageRenewal['postStartDate']))->format('Y-m-d'),
                        'int_rate'             => (float) $row->interest_rate,
                        'num_pmts'             => (float) $mortgageRenewal['postNumPmts'],
                        'first_pmt'            => (float) $mortgageRenewal['postFirstPmt'],
                        'first_pmt_date'       => (new DateTime($mortgageRenewal['postFirstPmtDate']))->format('Y-m-d'),
                        'omit_first_pmt'       => $mortgageRenewal['postOmitFirstPmt'],
                        'term_date'            => (new DateTime($mortgageRenewal['postTermDate']))->format('Y-m-d'),
                        'amort'                => (float) $mortgageRenewal['remainingAmortization'],
                        'comments'             => $mortgageRenewal['comments'],
                    ]);

                if ($mortageRenewalsTableUpdateChildCard === 0) {
                    $this->logger->error('RenewalApprovalBO->update Failed to update record in mortgage_renewal_table child card',[$row->mortgage_id, $mortgageRenewal['renewalId']]);
                    return false;
                }
            }
        }

        $mortageRenewalsTableDelete = MortgageRenewalPaymentsTable::where('mortgage_id', $mortgageRenewal['mortgageId'])
            ->where('renewal_id', $mortgageRenewal['renewalId'])
            ->delete();

        if ($mortageRenewalsTableDelete === 0) {
            $this->logger->error('RenewalApprovalBO->update Failed to delete record in mortgage_renewal_table',[$mortgageRenewal['mortgageId'], $mortgageRenewal['renewalId']]);
            return false;
        }

        if ($cInvCardCount >= 2) { // have child cards
            foreach ($cInvCard as $key => $row) {
                $mortageRenewalsTableChildCardDelete = MortgageRenewalPaymentsTable::where('mortgage_id', $row->mortgage_id)
                    ->where('renewal_id', $mortgageRenewal['renewalId'])
                    ->delete();

                if ($mortageRenewalsTableChildCardDelete === 0) {
                    $this->logger->error('RenewalApprovalBO->update Failed to delete record in mortgage_renewal_table child card',[$row->mortgage_id, $mortgageRenewal['renewalId']]);
                    return false;
                }
            }
        }

        // pmts
        $pmt_days = $mortgageRenewal['pmtDayArr'];
        $pmt_amts = $mortgageRenewal['pmtAmtArr'];

        foreach ($pmt_days as $pmt_idx => $pmt_day) {

            // insert parent payment row
            $mortgageRenewalPaymentsTable = $this->db->insert('mortgage_renewal_payments_table', [
                'mortgage_id' => $mortgageRenewal['mortgageId'],
                'renewal_id'  => $mortgageRenewal['renewalId'],
                'pmt_day'     => (float) $pmt_day,
                'pmt_amt'     => (float) $pmt_amts[$pmt_idx],
            ]);

            if (!$mortgageRenewalPaymentsTable) {
                $this->logger->error('RenewalApprovalBO->update Failed to add record to mortgage_renewal_payments_table',[$mortgageRenewal['mortgageId'], $mortgageRenewal['renewalId']]);
                return false;
            }

            // TACL-662: handle child cards
            if ($cInvCardCount >= 2) { // have child cards
                foreach ($cInvCard as $key => $row) {
                    $mortgageRenewalPaymentsTableChildCard = $this->db->insert('mortgage_renewal_payments_table', [
                        'mortgage_id' => $row->mortgage_id,
                        'renewal_id'  => $mortgageRenewal['renewalId'],
                        'pmt_day'     => (float) $pmt_day,
                        'pmt_amt'     => (float) $row->monthly_pmt,
                    ]);

                    if (!$mortgageRenewalPaymentsTableChildCard) {
                        $this->logger->error('RenewalApprovalBO->update Failed to add record to mortgage_renewal_payments_table child card',[$row->mortgage_id, $mortgageRenewal['renewalId']]);
                        return false;
                    }
                }
            }
        }

        $query_locked = 
            "SELECT company_id 
               FROM period_locks_table
              WHERE company_id = ? 
                AND start_date <= ?
                AND end_date >= ?
                AND locked = 'yes'";
        $res_locked = $this->db->select($query_locked,[$mortgageRenewal['applicationId'], $mortgageRenewal['renewalDate'], $mortgageRenewal['renewalDate']]);

        if(count($res_locked) > 0) {
            $alert_message = "WARNING: Renewal date is in a LOCKED month";
            return $alert_message;
        }

        return true;
    }

    public function calculate($renewalId, $mortgageId) {
        $this->logger->info('RenewalApprovalBO->calculateMortgageRenewal',[$renewalId, $mortgageId]);
        
        // renewal am sched form
        $first_pmt_date = null;

        $mortgage_table_start_value = 0;
        $row_renewal_start_value = 0;

        if (isset($mortgageId)) {

            if (!empty($renewalId)) {
                $query_renewal = "SELECT * FROM mortgage_renewals_table WHERE mortgage_id = ? AND renewal_id = ?";
                $row_renewal = $this->db->select($query_renewal,[$mortgageId, $renewalId]);
            }

            $query = "SELECT * FROM mortgage_table WHERE mortgage_id = ?";
            $row = $this->db->select($query,[$mortgageId]);
            $current_balance = $row[0]->current_balance;

            $mortgage_table_start_value = $current_balance;

            $query_la = "SELECT max(processing_date) AS last_activity FROM mortgage_payments_table WHERE mortgage_id = ? AND is_processed = 'yes'";
            $row_la = $this->db->select($query_la,[$mortgageId]);
            $last_activity_date = $row_la[0]->last_activity;

            $query_int = "SELECT * FROM mortgage_interest_rates_table WHERE mortgage_id = ? AND term_start <= ? ORDER BY term_start DESC"; 
            $row_int = $this->db->select($query_int,[$mortgageId, $last_activity_date]);
            $interest_rate = $row_int[0]->interest_rate;

            $query_fp = "SELECT * FROM mortgage_payments_table WHERE mortgage_id = ? AND is_post_dated_cheque = 'yes' AND is_processed = 'no' AND original_date = ?";
            $result_fp = $this->db->select($query_fp,[$mortgageId, $row[0]->first_pmt_due_date]);
            
            if (count($result_fp) > 0) {
                $first_pmt = $result_fp[0]->pmt_amt;
                $first_pmt_date = $result_fp[0]->processing_date;
                $first_pmt_checked = '';
            } else {
                $first_pmt = '';
                $first_pmt_checked = 'checked';
            }

            $time1 = strtotime($last_activity_date);
            $time2 = strtotime($row_int[0]->term_end);
            $year1 = date('Y', $time1);
            $year2 = date('Y', $time2);
            $month1 = date('m', $time1);
            $month2 = date('m', $time2);
            $num_pmts = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

            $term_end = $row_int[0]->term_end;

            $query_pmt = 
                "SELECT * FROM mortgage_payments_table
                  WHERE mortgage_id = ?
                    AND is_post_dated_cheque = 'yes'
                    AND processing_date > ?
                    AND payment_id <> '2'
                    AND original_date <> ?
               order by processing_date";
            $row_pmt = $this->db->select($query_pmt,[$mortgageId, $last_activity_date, $row[0]->first_pmt_due_date]);
            $pmt_amt = $row_pmt[0]->pmt_amt ?? 0;
            $pmt_day = isset($row_pmt[0]->processing_date) ? date('j', strtotime($row_pmt[0]->processing_date)) : null;
        } else {
            $current_balance = 0;
            $last_activity_date = date('m/d/Y');
            $interest_rate = 7.50;
            $num_pmts = 12;
            $term_end = date('m/d/Y', strtotime('+12 months', strtotime($last_activity_date)));
            $pmt_day = "1";
            $pmt_amt = 0;
            $first_pmt = "";
            $first_pmt_checked = 'checked';
        }

        if (isset($row_renewal[0]->start_value)) {
            $row_renewal_start_value = $row_renewal[0]->start_value;
            $start_value = $row_renewal[0]->start_value;
        } else {
			$start_value = $current_balance;
        }

        if (isset($row_renewal[0]->start_date)) {
            $start_date = $row_renewal[0]->start_date;
        } else {
            $start_date = $last_activity_date;
        }

        if (isset($row_renewal[0]->int_rate)) {
            $int_rate = $row_renewal[0]->int_rate;
        } else {
            $int_rate = $interest_rate;
        }

        if (isset($row_renewal[0]->num_pmts)) {
            $num_pmts = $row_renewal[0]->num_pmts;
        }

        if (isset($row_renewal[0]->first_pmt)) {
            $first_pmt = $row_renewal[0]->first_pmt;
        } 	

        if (isset($row_renewal[0])) {
            if(isset($row_renewal[0]->first_pmt_date)) {
                $first_pmt_date = $row_renewal[0]->first_pmt_date;
            } else {
                $first_pmt_date = null;
            }
        }

        $pmt_day_arr = [];
        $pmt_amt_arr = [];

        if (isset($renewalId)) {
            $pmt_query = "SELECT * FROM mortgage_renewal_payments_table WHERE mortgage_id = ? AND renewal_id = ? ORDER BY pmt_day";
            $pmt_result = $this->db->select($pmt_query,[$mortgageId, $renewalId]);

            foreach ($pmt_result as $key => $row_pmt) {
                $pmt_day_arr[] = $row_pmt->pmt_day;
                $pmt_amt_arr[] = $row_pmt->pmt_amt;
            }
        } else {
            $pmt_day_arr[] = $pmt_day;
            $pmt_amt_arr[] = $pmt_amt;
        }

        if (isset($row_renewal[0]->term_date)) {
            $term_date = $row_renewal[0]->term_date;
        } else {
            $term_date = $term_end;
        }

        $post_start_value = $start_value;
        $post_start_date = $start_date;
        $post_int_rate = $int_rate;
        $post_num_pmts = $num_pmts;
        $post_first_pmt = $first_pmt;
        $post_first_pmt_date = $first_pmt_date;
        if( $first_pmt_checked == 'checked') {
            $post_omit_first_pmt = 'on';
        } else {
            $post_omit_first_pmt = '';
        }
        $post_term_date = $term_date;

        // renewal_wizard
        
        $query_mtg = 
            "SELECT 
                mt.application_id, 
                mt.ab_loan,
                mt.parent,
                mt.mortgage_code,
                mt.ltv,
                mt.brokerGroup, 
                mt.amortization,
                mt.current_balance,
                mt.due_date,
                mt.first_pmt_due_date,
                mt.term_length,
                mt.interest_rate,
                mt.compounding,
                mt.monthly_pmt,
                mt.agent,
                s.l_name,
                sqt.gross,
                fn_GetLenders(mt.mortgage_id) AS investors
            FROM mortgage_table mt
            LEFT JOIN applicant_table ap on mt.application_id = ap.application_id
            LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
            LEFT JOIN saved_quote_table sqt ON sqt.application_id = mt.application_id
            WHERE mt.mortgage_id = ?";
        $res_mtg = $this->db->select($query_mtg,[$mortgageId]);

        $investors = $res_mtg[0]->investors;
        $agent = $res_mtg[0]->agent;

        if(count($res_mtg) > 0) {
            $cInvCardCount = 0;
            $piece_percent = [];
            $conn = null;
            // $valA2a = 0;
            // $valA2b = 0;
            // $valA4 = 0;
            // $valA2 = 0;
            // $valA3 = 0; 
            $is_ab_loan = false;

            $piece_payment = [];
            $c_percent = [];
            $c_mtg_id = [];
            $ltvProblem = false;
            $osb = 0;
            $contingentTable = [];
            $cInvCard = $this->getCInvCard($mortgageId);
            $loan_type = $res_mtg[0]->ab_loan; 

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                $is_ab_loan = true;

                $saleInvestorRow = $this->getSalesInvestorRow($res_mtg[0]->application_id, $mortgageId);

                if(isset($saleInvestorRow[0])) {
                    if (!empty($saleInvestorRow[0]->ap_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->ap_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->ap_percent;
                    } 
                    if (!empty($saleInvestorRow[0]->bp_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->bp_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->bp_percent;
                    }
                    if (!empty($saleInvestorRow[0]->cp_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->cp_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->cp_percent;
                    }
                }

                $mortgageIds = array_column($cInvCard, 'mortgage_id');
                $placeholders = implode(',', array_fill(0, count($mortgageIds), '?'));

                $query_last_rate = 
                    "SELECT mir.mortgage_id, mir.interest_rate
                    FROM mortgage_interest_rates_table mir
                    INNER JOIN (
                        SELECT mortgage_id, MAX(term_end) AS latest_end
                        FROM mortgage_interest_rates_table
                        WHERE mortgage_id IN ($placeholders)
                        GROUP BY mortgage_id
                    ) sub ON sub.mortgage_id = mir.mortgage_id AND sub.latest_end = mir.term_end";
                $res_last_rate = $this->db->select($query_last_rate,$mortgageIds);

                $rateMap = [];
                foreach ($res_last_rate as $r) {
                    $rateMap[$r->mortgage_id] = $r->interest_rate;
                }

                foreach ($cInvCard as $key => &$row) {
                    $row->rate = $rateMap[$row->mortgage_id] ?? null;
                    $c_mtg_id[] = $row->mortgage_id;
                }
                unset($row); 

                $placeholders = implode(',', array_fill(0, count($c_mtg_id), '?'));
                $query_cMtgRenew = 
                    "SELECT 
                        new_monthly_pmt, 
                        new_interest_rate, 
                        osb_renewal, 
                        new_osb, 
                        new_aer, 
                        new_apr, 
                        new_sae_int_rate  
                    FROM mortgage_renewals_table 
                    WHERE mortgage_id IN ($placeholders)";
                $res_cMtgRenew = $this->db->select($query_cMtgRenew,$c_mtg_id);

                foreach ($res_cMtgRenew as $key => $row) {

                    $c_edit_pmt = 'c_edit_pmt'.$key;
                    $$c_edit_pmt = $row->new_monthly_pmt;
                    $c_edit_rate = 'c_edit_rate'.$key;
                    $$c_edit_rate = $row->new_interest_rate;
                    $c_edit_pv = 'c_edit_pv'.$key;
                    $$c_edit_pv = $row->osb_renewal;
                    $c_edit_fv = 'c_edit_fv'.$key;
                    $$c_edit_fv = $row->new_osb;
                    $c_edit_aer = 'c_edit_aer'.$key;
                    $$c_edit_aer = $row->new_aer;
                    $c_edit_apr = 'c_edit_apr'.$key;
                    $$c_edit_apr = $row->new_apr;
                    $c_edit_saer = 'c_edit_saer'.$key;
                    $$c_edit_saer = $row->new_sae_int_rate;
                }

            } elseif ($res_mtg[0]->ab_loan == 'c_inv') {
                $is_ab_loan = true;
                $cInvCard = $this->getCInvCard($res_mtg[0]->parent);

                $saleInvestorRow = $this->getSalesInvestorRow($res_mtg[0]->application_id, $res_mtg[0]->parent);

                if(count($saleInvestorRow) > 0) {
                    if (!empty($saleInvestorRow[0]->ap_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->ap_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->ap_percent;
                    } 
                    if (!empty($saleInvestorRow[0]->bp_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->bp_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->bp_percent;
                    }
                    if (!empty($saleInvestorRow[0]->cp_inv_co)) {
                        $piece_payment[] = $saleInvestorRow[0]->cp_payment; 
                        $piece_percent[] = $saleInvestorRow[0]->cp_percent;
                    }
                }

                foreach ($cInvCard as $key => $row){
                    $c_percent[$row->mortgage_id] = $piece_percent[$key];
                }
            }

            if(!empty($renewalId)) {   

                $query_renewal = 
                    "SELECT 
                        mrt.renewal_paid_over,
                        mrt.osb_renewal,
                        mrt.new_osb,
                        mrt.property_valuation_fee,
                        mrt.property_valuation,
                        mrt.renewal_date,
                        mrt.next_pmt_date,
                        mrt.next_term_due_date,
                        mrt.new_interest_rate,
                        mrt.renewal_fee,
                        mrt.renewal_paid_over,
                        mrt.signed_by_borrower,
                        mrt.signed_by_investor,
                        mrt.pd_start_date,
                        mrt.pd_first_pmt,
                        mrt.pd_end_date,
                        mrt.pd_reg_pmt,
                        mrt.start_value,
                        mrt.int_rate,
                        mrt.num_pmts,
                        mrt.first_pmt,
                        mrt.start_date,
                        mrt.first_pmt_date,
                        mrt.term_date,
                        mrt.new_monthly_pmt,
                        mrt.new_sae_int_rate, 
                        mrt.new_aer,
                        mrt.approved_by,
                        mrt.amort
                    FROM mortgage_renewals_table mrt
                   WHERE mrt.mortgage_id = ?
                     AND mrt.renewal_id = ?";
                $row_renewal = $this->db->select($query_renewal,[$mortgageId, $renewalId]);

                $mortgageIds = array_column($cInvCard, 'mortgage_id');
                $placeholders = implode(',', array_fill(0, count($mortgageIds), '?'));

                if (empty($mortgageIds)) {
                    $res_renewals = [];
                } else {
                    $placeholders = implode(',', array_fill(0, count($mortgageIds), '?'));
                    $query = "
                        SELECT *
                        FROM mortgage_renewals_table
                        WHERE renewal_id = ?
                        AND mortgage_id IN ($placeholders)";
                    $params = array_merge([$renewalId], $mortgageIds);
                    $res_renewals = $this->db->select($query, $params);
                }
                $renewalMap = [];
                foreach ($res_renewals as $r) {
                    $renewalMap[$r->mortgage_id] = $r;
                }

                $row_renewal_c = [];
                foreach ($cInvCard as $row) {
                    if (isset($renewalMap[$row->mortgage_id])) {
                        $row_renewal_c[] = $renewalMap[$row->mortgage_id];
                    }
                }
            }

            if(isset($row_renewal[0]->approved_by)){
                $approved_by = $row_renewal[0]->approved_by;
            } else {
                $approved_by = null;
            }

            $query_term_dd = 
                "SELECT MAX(term_end) AS last_term_end, interest_rate, term_start
                   FROM mortgage_interest_rates_table
                  WHERE mortgage_id = ?
               GROUP BY mortgage_id";
            $res_term_dd = $this->db->select($query_term_dd,[$mortgageId]);

            $mtge_type=$res_mtg[0]->mortgage_code;
            if (strcmp($mtge_type,"INVESTOR CARD") == 0) {
                $mtge_type = "Investor Card";
            } else {
                $mtge_type = "In-house";
            }

            $is_mortgage_problem = false;
            $mortgage_problem = [];

            $query_nsf = 
                "SELECT mpt.mortgage_id
                   FROM mortgage_payments_table mpt 
                  WHERE mpt.mortgage_id = ? 
                    AND mpt.is_nsf = 'yes'
                    AND mpt.processing_date >= ?
                    AND mpt.processing_date <= ?";
            $res_nsf = $this->db->select($query_nsf,[$mortgageId, $res_term_dd[0]->term_start, $res_term_dd[0]->last_term_end]);
            
            if(count($res_nsf) > 3) {
                $is_mortgage_problem = true;
                $mortgage_problem[] = 'More than 3 NSF payments during this period';
            }

            if($res_mtg[0]->ltv > 75 && $mtge_type != 'Investor Card') {
                $is_mortgage_problem = true;
                $mortgage_problem[] = 'LTV is greater than 75%';
            }

            $query_mortgage_marginable = 
                "SELECT marginable_date
                   FROM mortgage_marginable 
                  WHERE mortgage_id = ?
               ORDER BY marginable_date DESC LIMIT 1";
            $res_mortgage_marginable = $this->db->select($query_mortgage_marginable,[$mortgageId]);

            $currentDate = time();

            if(isset($res_mortgage_marginable[0]->marginable_date)) {
                $mortgageDate = strtotime($mortgage_marginable_row['marginable_date']);
            } else {
                $mortgageDate = null;
            }

            $oneMonthEarlier = strtotime('-3 month', $currentDate);
            $marginable_message = '';

            if ($mortgageDate >= $oneMonthEarlier && $mortgageDate <= $currentDate) {
                $mortgage_marginable = true;
                $default_pvf = 205;
            } else {
                $mortgage_marginable = false;
                $default_pvf = 0;        
            }

            // Renewal Parameters:
            // Renewal Date
            $term_due_date = strtotime($res_term_dd[0]->last_term_end);
            $renewal_date = strtotime('+1 day', $term_due_date);

            if(isset($row_renewal[0])) {
                $renewal_date_display = (new DateTime($row_renewal[0]->renewal_date))->format('Y-m-d');
            } else {
                $renewal_date_display = (new DateTime('@' . $renewal_date))->format('Y-m-d');
            }

            // Next Pmt Date
            $next_pmt_date = strtotime('+1 month', $term_due_date);

            if(isset($row_renewal[0])) {
                $next_payment_date = (new DateTime($row_renewal[0]->next_pmt_date))->format('Y-m-d');
            } else {
                $next_payment_date = (new DateTime('@' . $next_pmt_date))->format('Y-m-d');
            } 

            // Next Term Due Date
            $next_term_date = strtotime('+'. $res_mtg[0]->term_length .' month', $term_due_date);

            if(isset($row_renewal[0])) {
                $next_term_due_date = (new DateTime($row_renewal[0]->next_term_due_date))->format('Y-m-d');
            } else {
                $next_term_due_date = (new DateTime('@' . $next_term_date))->format('Y-m-d');
            }

            // New Interest Rate
            $query_last_rate = 
                "SELECT term_end, interest_rate, term_start 
                   FROM mortgage_interest_rates_table 
                  WHERE mortgage_id = ?
               ORDER BY term_end desc";
            $res_last_rate = $this->db->select($query_last_rate,[$mortgageId]);
            $new_interest_rate = $res_last_rate[0]->interest_rate;

            if(isset($row_renewal[0]->new_interest_rate)) {
                $new_interest_rate_display = $row_renewal[0]->new_interest_rate;
            } else { 
                $new_interest_rate_display = $new_interest_rate;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if(isset($row_renewal_c[0]->new_interest_rate)) {
                    $new_interest_rate_ap_display = $row_renewal_c[0]->new_interest_rate;
                } else {
                    $new_interest_rate_ap_display = $cInvCard[0]->rate;
                }

                if(isset($row_renewal_c[1]->new_interest_rate)) {
                    $new_interest_rate_bp_display = $row_renewal_c[1]->new_interest_rate;
                } else { 
                    $new_interest_rate_bp_display = $cInvCard[1]->rate;
                }

                if(isset($cInvCard[2]->rate)) {
                    $new_interest_rate_cp_display = $cInvCard[2]->rate;
                } else {
                    $new_interest_rate_cp_display = null;
                }
            } else {
                $new_interest_rate_ap_display = null;
                $new_interest_rate_bp_display = null;
                $new_interest_rate_cp_display = null;
            }
            
            // renewal am sched form

            // Contingent Table

            $prev_date = $curr_date = strtotime($start_date);
            $term_date = strtotime($term_date);
            $osb = $pv = $this->toFloat($start_value)*1;
            $n = $this->toFloat($num_pmts)*1 - 1;
            $i = ($this->toFloat($int_rate)*1)/100;

            $orig_osb = $osb;

            $num   = 0;
            $stop1 = 0;

            if(empty($first_pmt_date)) {
                $curr_dat = strtotime($start_date);
                $loop = true;
                if (count($pmt_day_arr) >= 1){
                    foreach($pmt_day_arr as $idx => $pd) {
                        $curr_dat = strtotime(date('m', $curr_dat).'/'.$pd.'/'.date('Y', $curr_dat));
                        
                        if($curr_date <= $curr_dat) {
                            $first_pmt_date = date('m', $curr_dat).'/'.$pd.'/'.date('Y', $curr_dat);
                            $loop = false;
                            break;
                        }
                    }
                    $curr_dat = strtotime('+1 month', $curr_dat);
                }
            }
            
            $query_mortgage_payment_date =
                "SELECT processing_date
                   FROM mortgage_payments_table 
                  WHERE is_processed = 'no'
                    AND is_post_dated_cheque = 'yes' 
                    AND mortgage_id = ?
               ORDER BY processing_date";
            $res_mortgage_payment_date = $this->db->select($query_mortgage_payment_date,[$mortgageId]);

            $mortgagePaymentDateArray = array_map(function($row) {
                return is_object($row) ? $row->processing_date : $row['processing_date'];
            }, $res_mortgage_payment_date);
            
            if(!isset($first_pmt_checked)) {

                $first_pmt = $this->toFloat($first_pmt);

                $curr_date = strtotime($first_pmt_date);

                $diff = $curr_date - $prev_date;
                $days = ($diff/86400);

                $od = getdate($prev_date);
                $cd = getdate($curr_date);
                $month_diff = $cd['mon']-$od['mon'];
                $monthly_calculation = 0; 
                if($od['mday'] == $cd['mday']) {
                    if($od['mon'] == $cd['mon']) {
                        $days = 0;
                        $stop1 = 1;
                    } else if($month_diff == 1 || $month_diff == -11) {
                        $days = 30;
                        $monthly_calculation = 1; 
                    }
                }

                if ($stop1 ==0) {
                    $days = $days+1;

                    //calculations
                    $interest = $this->interest($osb, $i, $days, $monthly_calculation, false);
                    $princ_amt = $first_pmt - $interest;
                    $osb = $this->osb($interest, 0, $first_pmt, $osb);

                    //format curr_date
                    $curr_date = date('m/d/Y', $curr_date);

                    $contingentTable[] = [
                        'num' => ++$num,
                        'curr_date' => $curr_date,
                        'pmt' => $first_pmt,
                        'interest' => $interest,
                        'princ_amt' => $princ_amt,
                        'osb' => $osb
                    ];

                    //store curr_date as the last date for days calculation for interest computation
                    $prev_date = $curr_date = strtotime($curr_date);
                }
            }

            while($curr_date <= $term_date && $num <= $n) {
            
                foreach($pmt_day_arr as $idx => $pd) {

                    if($num > $n) {
                        break;
                    }

                    //which payment day to start with (depends on last activity date)
                    $curr_day = date('j', $curr_date) *1;
                    if($curr_day <= $pd) {
                        $pmt_amt_arr[$idx] = $this->toFloat($pmt_amt_arr[$idx]);

                        //make curr_date the next pmt_date to be processed
                        $curr_date = date('n', $curr_date).'/'. $pd .'/'.date('Y', $curr_date);
                        $curr_date = strtotime($curr_date);

                        //get exact days
                        $diff = $curr_date - $prev_date;
                        $days = $diff / 86400;

                        //if regular monthly cheque or same day
                        $od = getdate($prev_date);
                        $cd = getdate($curr_date);
                        $month_diff = $cd['mon']-$od['mon'];
                        $monthly_calculation = 0; //false
                        if($od['mday'] == $cd['mday']) {
                            if($od['mon'] == $cd['mon']) {
                                $days = 0;
                                break;
                            } else if($month_diff == 1 || $month_diff == -11) {
                                $days = 30;
                                $monthly_calculation = 1; //true
                            }
                        }

                        //calculations
                        $interest = $this->interest($osb, $i, $days, $monthly_calculation, false);
                        $cur_date = date('Y-m-d', $curr_date);
                        $curr_date = date('m/d/Y', $curr_date);
                        $query_mortgage_payment_amount = 
                            "SELECT pmt_amt
                               FROM mortgage_payments_table 
                              WHERE mortgage_id = ?
                                AND processing_date = ?";
                        $res_mortgage_payment_amount = $this->db->select($query_mortgage_payment_amount,[$mortgageId, $cur_date]);

                        if (!empty($pmt_amt_arr[$idx]) && !empty($res_mortgage_payment_amount) && isset($res_mortgage_payment_amount[0]->pmt_amt)) {
                            $pmt_amt_arr[$idx] = $res_mortgage_payment_amount[0]->pmt_amt;
                        }

                        $princ_amt = $pmt_amt_arr[$idx] - $interest;

                        if (in_array($cur_date,$mortgagePaymentDateArray)) {
                            $osb = $this->osb($interest, 0, $pmt_amt_arr[$idx], $osb);
                        }		

                        if (in_array($cur_date,$mortgagePaymentDateArray)){
                            $contingentTable[] = [
                                'num' => ++$num,
                                'curr_date' => $curr_date,
                                'pmt' => (empty($pmt_amt_arr[$idx]) ? '' : ($pmt_amt_arr[$idx])),
                                'interest' => $interest,
                                'princ_amt' => $princ_amt,
                                'osb' => $osb
                            ];
                        }
                        $prev_date = $curr_date = strtotime($curr_date);
                    }
                }
                $curr_date = date('n', strtotime('+1 month', $curr_date)).'/01/'.date('Y', strtotime('+1 month', $curr_date));
                $curr_date = strtotime($curr_date);
            }

            $osb_var = $osb;

            // Suggested New Payment
            if(strcmp($res_mtg[0]->brokerGroup,"Gp2") == 0) {
                $query_company_id = 
                    "SELECT a.company, rft.rate
                       FROM application_table a
                  LEFT JOIN renewal_fees_table rft ON rft.company_id = a.company AND mtge_type = ? AND broker_group = ?
                      WHERE a.application_id = ?";
                $res_company_id = $this->db->select($query_company_id,[$mtge_type, $res_mtg[0]->brokerGroup, $res_mtg[0]->application_id]);
                $company_name = $res_company_id[0]->company;

                if (isset($row_renewal[0]->osb_renewal)) {
                    $renewalFee = $res_company_id[0]->rate * $row_renewal[0]->osb_renewal / 100;
                } else {
                    $renewalFee = $res_company_id[0]->rate * $osb / 100;
                }
                
            } else {
                $query = 
                    "SELECT a.company, rft.rate, rft.min, rft.max
                       FROM application_table a
                  LEFT JOIN renewal_fees_table rft ON rft.company_id = a.company AND mtge_type = ? AND broker_group <> 'Gp2'
                      WHERE a.application_id = ?";
                $res4 = $this->db->select($query,[$mtge_type, $res_mtg[0]->application_id]);
                $company_name = $res4[0]->company ?? "";

                if (isset($row_renewal[0]->osb_renewal)) {
                    $renewalFee=$res4[0]->rate*$row_renewal[0]->osb_renewal/100;
                } else {
                    $renewalFee=$res4[0]->rate*$osb/100;
                }
            }

            $osb_display = $osb;
            $renewal_fee_display_2 = $renewalFee;
            if ($renewalFee < $res4[0]->min) {
                $renewalFee = $res4[0]->min;
            } 

            if ($renewalFee > $res4[0]->max) {
                $renewalFee = $res4[0]->max;
            }

            $number = ceil($renewalFee / 10) * 10;
            $renewalFee=$number;

            if ($res_mtg[0]->ab_loan == 'c_inv') {
                $renewalFee = $renewalFee * $c_percent[$mortgageId] / 100;
            }

            if (isset($row_renewal[0]->osb_renewal)) {
                $osb_c = $row_renewal[0]->osb_renewal;
            } else {
                $osb_c = $osb;
            }

            if ($osb_c > 1000000) {
                $renewalFee = 2500;
            }

            $m_pmt = $this->EPMT($res_mtg[0]->interest_rate/12/100,($res_mtg[0]->amortization-1)*12,$osb+$renewalFee);
            $m_pmt = abs($m_pmt) + $renewalFee/12;

            $m_pmt = round($m_pmt, 2);
            $m_pv = $osb + $renewalFee;
            $m_fv = $this->EFV($osb+$renewalFee,$m_pmt,$res_mtg[0]->interest_rate/12/100,12);
            $m_aer = $this->AER($res_mtg[0]->interest_rate,12);
            $m_saer = $this->SAER($res_mtg[0]->interest_rate,12);

            $query = 
                "SELECT mpt.payment_id, mpt.mortgage_id
                   FROM mortgage_payments_table mpt
                  WHERE mpt.mortgage_id = ?
                    AND is_processed = 'no'";
            $res5 = $this->db->select($query,[$mortgageId]);
            
            $left_period = count($res5);
            foreach ($cInvCard as $key => &$row) {
                $row->rate = $res_last_rate[0]->interest_rate;
                $c_osb[$key] = $this->EFV($row->current_balance,$row->monthly_pmt,$row->interest_rate/12/100,$left_period);
                $this_piece_percentage = $c_osb[$key]/$osb;
                $c_pv[$key] = $this->EFV($row->current_balance,$row->monthly_pmt,$row->interest_rate/12/100,$left_period) + $renewalFee*$this_piece_percentage;
                $c_d_pmt[$key] = $this->EPMT($row->interest_rate/12/100,($res_mtg[0]->amortization-1)*12,$c_pv[$key]);
                $c_d_pmt[$key] += $renewalFee*$this_piece_percentage/12;
                $c_fv[$key] = $this->EFV($c_pv[$key],$c_d_pmt[$key],$row->interest_rate/12/100,12);
                $c_aer[$key] = $this->AER($row->interest_rate,12);
                $c_saer[$key] = $this->toTwoDigits($this->SAER($row->interest_rate,12));
            }

            if (count($cInvCard) == 2) {
                $c_osb[1] = $this->r($osb) - $this->r($c_osb[0]);
                $c_d_pmt[1] = $this->r($m_pmt) - $this->r($c_d_pmt[0]);
                $c_pv[1] = $this->r($m_pv) - $this->r($c_pv[0]);
                $c_fv[1] = $this->r($m_fv) - $this->r($c_fv[0]);
            } elseif (count($cInvCard) == 3) {
                $c_osb[2] = $this->r($osb) - $this->r($c_osb[0]) - $this->r($c_osb[1]);
                $c_d_pmt[2] = $this->r($m_pmt) - $this->r($c_d_pmt[0]) - $this->r($c_d_pmt[1]);
                $c_pv[2] = $this->r($m_pv) - $this->r($c_pv[0]) - $this->r($c_pv[1]);
                $c_fv[2] = $this->r($m_fv) - $this->r($c_fv[0]) - $this->r($c_fv[1]);
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) {
                if($renewalId >= 1) {
                    $m_edit_pmt = $row_renewal[0]->new_monthly_pmt;
                }
            }
            
            if(isset($m_edit_pmt)) {
                $suggested_new_payment = $m_edit_pmt;
            } else { 
                $suggested_new_payment = $m_pmt;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if(isset($row_renewal_c[0]->new_monthly_pmt)) {
                    $suggested_new_payment_ap = $row_renewal_c[0]->new_monthly_pmt;
                } else {
                    $suggested_new_payment_ap = $c_d_pmt[0];
                }
                if(isset($row_renewal_c[1]->new_monthly_pmt)) { 
                    $suggested_new_payment_bp = $row_renewal_c[1]->new_monthly_pmt;
                } else {
                    $suggested_new_payment_bp = $c_d_pmt[1];
                }
                
                if(isset($row_renewal_c[2]->new_monthly_pmt)) {
                    $suggested_new_payment_cp = $row_renewal_c[2]->new_monthly_pmt;
                } elseif(isset($c_d_pmt) && isset($c_d_pmt[2])) {
                    $suggested_new_payment_cp = $c_d_pmt[2];
                } else {
                    $suggested_new_payment_cp = null;
                }
            } else {
                $suggested_new_payment_ap = null;
                $suggested_new_payment_bp = null;
                $suggested_new_payment_cp = null;
            }
            
            // OSB at Renewal

            if(isset($row_renewal[0]->osb_renewal)) {
                $osb_at_renewal = $row_renewal[0]->osb_renewal;
            } else {
                $osb_at_renewal = $osb;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if(isset($row_renewal_c[0]->osb_renewal)) {
                    $osb_at_renewal_ap = $row_renewal_c[0]->osb_renewal;
                } else { 
                    $osb_at_renewal_ap = $c_osb[0];
                }
                
                if(isset($row_renewal_c[1]->osb_renewal)) {
                    $osb_at_renewal_bp = $row_renewal_c[1]->osb_renewal;
                } else {
                    $osb_at_renewal_bp = $c_osb[1];
                }

                if(isset($row_renewal_c[2]->osb_renewal)) {
                    $osb_at_renewal_cp = $row_renewal_c[2]->osb_renewal;
                } elseif(isset($c_osb) && isset($c_osb[2])) {
                    $osb_at_renewal_cp = $c_osb[2];
                } else {
                    $osb_at_renewal_cp = null;
                }
            } else {
                $osb_at_renewal_ap = null;
                $osb_at_renewal_bp = null;
                $osb_at_renewal_cp = null;
            }
            
            // Property Valuation Fee
            if(isset($row_renewal[0]->property_valuation_fee)) {
                $property_valuation_fee = $row_renewal[0]->property_valuation_fee;
            } else {
                $property_valuation_fee = $default_pvf;
            }
            
            if(isset($row_renewal[0]->property_valuation)) {
                $property_valuation_display = $row_renewal[0]->property_valuation;
            } else {
                $property_valuation_display = "";
            }

            $property_valuation_fee_display = $property_valuation_fee;

            // Renewal Fee
            if(isset($row_renewal[0]->renewal_fee)) {
                $renewal_fee_display = $row_renewal[0]->renewal_fee;
            } else {
                $renewal_fee_display = $renewalFee;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if(isset($row_renewal_c[0]->renewal_fee)) {
                    $renewal_fee_ap_display = $row_renewal_c[0]->renewal_fee;
                } else {
                    $renewal_fee_ap_display = $renewalFee * $c_osb[0] / $osb;
                }
                
                if(isset($row_renewal_c[1]->renewal_fee)) {
                    $renewal_fee_bp_display = $row_renewal_c[1]->renewal_fee;
                } else { 
                    $renewal_fee_bp_display = $renewalFee * $c_osb[1] / $osb;
                }

                if(isset($row_renewal_c[2]->renewal_fee)) {
                    $renewal_fee_cp_display = $row_renewal_c[2]->renewal_fee;
                } elseif(isset($c_osb) && isset($c_osb[2])) {
                    $renewal_fee_cp_display = $renewalFee * $c_osb[2] / $osb;
                } else {
                    $renewal_fee_cp_display = null;
                }
            } else {
                $renewal_fee_ap_display = null;
                $renewal_fee_bp_display = null;
                $renewal_fee_cp_display = null;
            }

            // Renewal Fee to be paid over 
            if(isset($row_renewal[0]->renewal_paid_over)) {
                $renewal_fee_to_be_paid_over = $row_renewal[0]->renewal_paid_over;
            } else {
                $renewal_fee_to_be_paid_over = null;
            }

            // Signed/Received by Borrower
            if(isset($row_renewal[0]->signed_by_borrower)) {
                $signed_received_by_borrower = $row_renewal[0]->signed_by_borrower;
            } else {
                $signed_received_by_borrower = null;
            } 

            // Signed/Received by Investor
            if(isset($row_renewal[0]->signed_by_investor)) {
                $signed_received_by_investor = $row_renewal[0]->signed_by_investor;
            } else {
                $signed_received_by_investor = null;
            } 

            // New Post-Dated Cheques
            if(isset($row_renewal[0])) {
                if(isset($row_renewal[0]->pd_start_date) && $row_renewal[0]->pd_start_date != "0000-00-00") {
                    $new_post_dated_cheques_start_date = (new DateTime($row_renewal[0]->pd_start_date))->format('Y-m-d');
                } else {
                    $new_post_dated_cheques_start_date = null;
                }
                $new_post_dated_cheques_first_pmt = $row_renewal[0]->pd_first_pmt;
                if(isset($row_renewal[0]->pd_end_date) && $row_renewal[0]->pd_end_date != "0000-00-00") {
                    $new_post_dated_cheques_end_date = (new DateTime($row_renewal[0]->pd_end_date))->format('Y-m-d');
                } else {
                    $new_post_dated_cheques_end_date = null;
                }
                $new_post_dated_cheques_reg_pmt = $row_renewal[0]->pd_reg_pmt;
            } else { 
                $new_post_dated_cheques_start_date = '';
                $new_post_dated_cheques_first_pmt = 0;
                $new_post_dated_cheques_end_date = '';
                $new_post_dated_cheques_reg_pmt = 0;
            }

            // Comments
            if(isset($row_renewal[0]->comments)) {
                $mortgage_renewal_comments = $row_renewal[0]->comments;
            } else {
                $mortgage_renewal_comments = '';
            }

            // Set variables for calculation
            $amort_box = $res_mtg[0]->amortization;
            $loan_box = $res_mtg[0]->term_length;
            $gross_box = $osb;
            $comp_box = $res_mtg[0]->compounding;
            $old_pmt = $res_mtg[0]->monthly_pmt;

            // Calculated Parameters:

            // OSB + Renewal Fee
            if(isset($row_renewal[0])) {
                $osb_plus_renewal = ($row_renewal[0]->osb_renewal+$row_renewal[0]->renewal_fee+$row_renewal[0]->property_valuation_fee); 
                if ($row_renewal[0]->renewal_paid_over == 'upfront') {
                    $osb_plus_renewal = ($row_renewal[0]->osb_renewal+$row_renewal[0]->property_valuation_fee); 
                }
            } else {
                $osb_plus_renewal = ($osb+$renewalFee+$default_pvf); 
            }

            $osb_renewal_fee_master = $osb_plus_renewal;

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if(isset($row_renewal_c[0])){ 
                    $c_osb_plus_rene_0 = $row_renewal_c[0]->osb_renewal+$row_renewal_c[0]->renewal_fee+$row_renewal_c[0]->property_valuation_fee; 
                    if ($row_renewal[0]->renewal_paid_over == 'upfront'){
                        $c_osb_plus_rene_0 = $row_renewal_c[0]->osb_renewal+$row_renewal_c[0]->property_valuation_fee; 
                    }
                }else{ 
                    $c_osb_plus_rene_0 = ($c_pv[0]);
                }

                $osb_renewal_fee_ap = $c_osb_plus_rene_0;

                if(isset($row_renewal_c[1])){ 
                    $c_osb_plus_rene_1 = $row_renewal_c[1]->osb_renewal+$row_renewal_c[1]->renewal_fee+$row_renewal_c[1]->property_valuation_fee; 
                    if ($row_renewal[0]->renewal_paid_over == 'upfront'){
                        $c_osb_plus_rene_1 = $row_renewal_c[1]->osb_renewal+$row_renewal_c[1]->property_valuation_fee; 
                    }
                }else{
                    $c_osb_plus_rene_1 = ($c_pv[1]);
                }   

                $osb_renewal_fee_bp = $c_osb_plus_rene_1;
                
                if (count($cInvCard) >= 3){
                    if(isset($row_renewal_c[2])) {
                        $c_osb_plus_rene_2 = $row_renewal_c[2]->osb_renewal+$row_renewal_c[2]->renewal_fee+$row_renewal_c[2]->property_valuation_fee; 
                        if ($row_renewal[0]->renewal_paid_over == 'upfront'){
                            $c_osb_plus_rene_2 = $row_renewal_c[2]->osb_renewal+$row_renewal_c[2]->property_valuation_fee; 
                        }
                    } else {
                        $c_osb_plus_rene_2 = ($c_pv[2]);
                    }
                } else {
                    $c_osb_plus_rene_2 = null;
                }

                $osb_renewal_fee_cp = $c_osb_plus_rene_2;

            } else {
                $osb_renewal_fee_ap = null;
                $osb_renewal_fee_bp = null;
                $osb_renewal_fee_cp = null;
            }
            
            // New Monthly Pmt
            if(isset($row_renewal[0]->new_monthly_pmt))  {
                $new_monthly_pmt_master = $row_renewal[0]->new_monthly_pmt; 
            } else {
                $new_monthly_pmt_master = $m_pmt;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 

                if(isset($row_renewal_c[0]->new_monthly_pmt)) {
                    $new_monthly_pmt_ap = $row_renewal_c[0]->new_monthly_pmt;
                } else { 
                    $new_monthly_pmt_ap = $c_d_pmt[0];
                }

                if(isset($row_renewal_c[1]->new_monthly_pmt)) {
                    $new_monthly_pmt_bp = $row_renewal_c[1]->new_monthly_pmt;
                } else { 
                    $new_monthly_pmt_bp = $c_d_pmt[1];
                }

                if(count($cInvCard) >= 3){
                    if(isset($row_renewal_c[2]->new_monthly_pmt)) {
                        $new_monthly_pmt_cp = $row_renewal_c[2]->new_monthly_pmt;
                    } else { 
                        $new_monthly_pmt_cp = $c_d_pmt[2];
                    }
                } else {
                    $new_monthly_pmt_cp = null;
                }
            } else {
                $new_monthly_pmt_ap = null;
                $new_monthly_pmt_bp = null;
                $new_monthly_pmt_cp = null;
            }

            // No. of Month In term

            if (isset($row_renewal[0]->renewal_date) && isset($row_renewal[0]->next_term_due_date)) {
                $term_length2 = $this->datediff('m',$row_renewal[0]->renewal_date,$row_renewal[0]->next_term_due_date)+1; 
            } else {
                $term_length2 = null;
            }
            
            if (($term_length2) == 0) {
                $term_length2 = $res_mtg[0]->term_length; 
            }  

            $no_of_month_in_term = $term_length2;

            // OSB at
            if (isset($row_renewal[0]->new_osb)) {
                $osb_at_next_term_end_master = $row_renewal[0]->new_osb;
            } else {
                $osb_at_next_term_end_master = $m_fv;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 

                if (isset($c_edit_fv0)) {
                    $osb_at_next_term_end_ap = $c_edit_fv0;
                } else {
                    $osb_at_next_term_end_ap = $c_fv[0];
                }

                if (isset($c_edit_fv1)) {
                    $osb_at_next_term_end_bp = $c_edit_fv1;
                } elseif (isset($c_fv[1])) {
                    $osb_at_next_term_end_bp = $c_fv[1];
                }

                if(count($cInvCard) >= 3){
                    if (isset($c_edit_fv2)) {
                        $osb_at_next_term_end_cp = $c_edit_fv2;
                    } else {
                        $osb_at_next_term_end_cp = $c_fv[2];
                    }
                } else {
                    $osb_at_next_term_end_cp = null;
                }
            } else {
                $osb_at_next_term_end_ap = null;
                $osb_at_next_term_end_bp = null;
                $osb_at_next_term_end_cp = null;
            }

            // Annual Effective Rate
            if (isset($row_renewal[0]->new_aer)) {
                $annual_effective_rate_master = $row_renewal[0]->new_aer;
            } else {
                $annual_effective_rate_master = $m_aer;
            }


            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if (isset($c_edit_aer0)) {
                    $annual_effective_rate_ap = $c_edit_aer0;
                } else {
                    $annual_effective_rate_ap = $c_aer[0];
                }

                if (isset($c_edit_aer1)) {
                    $annual_effective_rate_bp = $c_edit_aer1;
                } else {
                    $annual_effective_rate_bp = $c_aer[1];
                }

                if(count($cInvCard) >= 3){
                    if (isset($c_edit_aer2)) {
                        $annual_effective_rate_cp = $c_edit_aer2;
                    } else {
                        $annual_effective_rate_cp = $c_aer[2];
                    }
                } else {
                    $annual_effective_rate_cp = null;
                }
            } else {
                $annual_effective_rate_ap = null;
                $annual_effective_rate_bp = null;
                $annual_effective_rate_cp = null;
            }

            // Semi-Annual Equivalent Int Rate
            if (isset($row_renewal[0]->new_sae_int_rate)) {
                $semi_annual_equivalent_int_rate_master = $row_renewal[0]->new_sae_int_rate;
            } else {
                $semi_annual_equivalent_int_rate_master = $m_saer;
            }

            if ($res_mtg[0]->ab_loan == 'm_inv' && count($cInvCard) >= 2) { 
                if (isset($c_edit_saer0)) {
                    $semi_annual_equivalent_int_rate_ap = $c_edit_saer0;
                } else {
                    $semi_annual_equivalent_int_rate_ap = $c_saer[0];
                }

                if (isset($c_edit_saer1)) {
                    $semi_annual_equivalent_int_rate_bp = $c_edit_saer1;
                } else {
                    $semi_annual_equivalent_int_rate_bp = $c_saer[1];
                }

                if(count($cInvCard) >= 3){
                    if (isset($c_edit_saer2)) {
                        $semi_annual_equivalent_int_rate_cp = $c_edit_saer2;
                    } else {
                        $semi_annual_equivalent_int_rate_cp = $c_saer[2];
                    }
                } else {
                    $semi_annual_equivalent_int_rate_cp = null;
                }
            } else {
                $semi_annual_equivalent_int_rate_ap = null;
                $semi_annual_equivalent_int_rate_bp = null;
                $semi_annual_equivalent_int_rate_cp = null;
            }

            // APR For BC :

            // OSB at Renewal =
            if(isset($row_renewal[0]->osb_renewal)) {
                $net_amt = $row_renewal[0]->osb_renewal;
            } else {
                $net_amt = 0;
            }

            $apr_osb_at_renewal = $net_amt;

            // (a) 1. Renewal Fee =

            if(isset($row_renewal[0]->renewal_fee)) {
                $renewal_fee2 = $row_renewal[0]->renewal_fee;
            } else {
                $renewal_fee2 = 0;
            }

            $apr_renewal_fee = $renewal_fee2;

            // (a) 2. Property Valuation Fee 

            if(isset($row_renewal[0]->property_valuation_fee)) {
                $property_valuation_fee_2 = $row_renewal[0]->property_valuation_fee;
            } else {
                $property_valuation_fee_2 = null;
            } 

            $apr_property_valuation_fee = $property_valuation_fee_2;

            // (b) Total Installment

            $term_length2 = $this->datediff('m',$row_renewal[0]->renewal_date ?? 0,$row_renewal[0]->next_term_due_date ?? 0)+1;
            if (($term_length2)==0) {
                $term_length2 = $res_mtg[0]->term_length; 
            }
            
            $apr_new_term_length = $term_length2;

            
            if (isset($row_renewal[0]->new_monthly_pmt)) {
                $apr_new_monthly_payment = ($row_renewal[0]->new_monthly_pmt);
            } else {
                $apr_new_monthly_payment = 0;
            }

            if(isset($row_renewal[0]->new_monthly_pmt)){
                $valA2a = $term_length2 *( ($row_renewal[0]->new_monthly_pmt)*1);
            } else {
                $valA2a = 0;
            }

            $apr_new_total_payment = $valA2a;

            // (c) Balance at Next Term End =

            if(isset($row_renewal[0]->new_osb)){
                $valA4 = $row_renewal[0]->new_osb;
            } else {
                $valA4 = 0;
            }

            $apr_balance_at_next_term_end = $valA4;

            // (d) Total Payments(b+c) =

            $valA2b = 0;
            $valD = $valA2a+$valA2b + $valA4;
            $apr_total_payments = $valD;

            // (e) Total cost of Credit=

            $valTotal2 = $valD-$net_amt;
            $total_cost_of_credit = $valTotal2;

            // Average OS Bal =

            $valA2 = 0;
            $valTmp1 = 100 * $valTotal2;
            $valTmp2 = $valA2/12 ;

            $valA5   = $res_mtg[0]->gross ?? 0;
            $valTmp3 = ($valA5+$valA4) / 2;
            if ($valTmp2 * $valTmp3 == 0) {
                $valApr  = 0;
            } else {
                $valApr  = $valTmp1 / ( $valTmp2 * $valTmp3);
            }
            
            $n=$term_length2;
            $num=0;
            $pmt_amt = 0;
            $osb=$renewal_fee2 +$net_amt;
            if(isset($row_renewal[0]->new_monthly_pmt)) {
                $pmt_amt= $row_renewal[0]->new_monthly_pmt ?? 0;
            }    

            if(isset($row_renewal[0]->new_interest_rate)) {
                $int1 = $row_renewal[0]->new_interest_rate;
            } else {
                $int1 = ($new_interest_rate);
            }

            $motgage_id=22222;
            $totalAmt=0;

            $pmt_rows = [];
            while($osb > $pmt_amt && $num < $n) {
                $num++;
                $monthly_calculation = 1; 
                $interest = $osb*$int1/100/12;

                $princ_amt = $pmt_amt - $interest;
                $osb = $this->osb($interest, 0, $pmt_amt, $osb);

                $totalAmt +=$osb;
                $curr_date = date('m/d/Y', time());

                $pmt_rows[] = [
                    'num' => $num,
                    'curr_date' => $curr_date,
                    'pmt_amt' => $pmt_amt,
                    'interest' => $interest,
                    'princ_amt' => $princ_amt,
                    'osb' => $osb
                ];
            }


            $valTmp3 = $totalAmt/$n;
            $valTmp2 = $term_length2/12;
            if ($valTmp2 * $valTmp3 == 0) {
                $valApr = 0;
            } else {
                $valApr = $valTmp1 / ($valTmp2 * $valTmp3);
            }

            $average_os_bal = $valTmp3;

            // APR =

            $apr_display = $valApr;

            // RemainingAmortization =

            if(isset($row_renewal[0]->amort)) {
                $remaining_amortization = $row_renewal[0]->amort;
            } else {
                $remaining_amortization = 0;
            }

            // Assign Fields for calculation

            $cn = count($cInvCard); 
            if ($cn >= 2) {
                $ab = true;
            } else {
                $ab = false;
            }

            if (isset($piece_percent[0])){
                $c_per0 = $piece_percent[0];
            }

            if (isset($piece_percent[1])) {
                $c_per1 = $piece_percent[1];
            }

            if (isset($piece_percent[2])) {
                $c_per2 = $piece_percent[2];
            }

            $m_osb = $osb_at_renewal;

            if ($cn > 0) { // ABL
                $ap_osb = $osb_at_renewal_ap;
                $bp_osb = $osb_at_renewal_bp;
                $c_osb_per0 = $ap_osb / $m_osb;
                $c_osb_per1 = $bp_osb / $m_osb;
            }

            if ($cn == 3) {
                $c_osb_per2 = 1 - $c_osb_per0 - $c_osb_per1;
            }

            $na = $osb_at_renewal*1;
            $pv = $osb_renewal_fee_master*1;
            $int_box = $new_interest_rate_display;
            $i = $int_box*1;
            $pmt_box = $new_monthly_pmt_master;
            $old_monthly_payment = $new_monthly_pmt_master*1;
            $m_d_pmt = $pmt_box;
            $pmt = $pmt_box*1;
            $loanBox = $apr_new_term_length;
            $n = $loanBox;
            $amortBox = $amort_box;
            $y = $amort_box*1;
            $renewal_fee = $renewal_fee_display*1;

            $osb_box = $osb_at_next_term_end_master;
            $grossBox = $gross_box;
            $loanBox = $loan_box;
            $renewal_osb_box = $osb_at_renewal;

            $calculateRenewalData = [
                // Display Fields
                "lName" => $res_mtg[0]->l_name ?? '',
                "applicationId" => $res_mtg[0]->application_id ?? null,
                "investors" => $investors,
                "mortgageCode" => $res_mtg[0]->mortgage_code ?? '',
                "renewalDate" => $renewal_date_display,
                "nextPaymentDate" => $next_payment_date,
                "nextTermDueDate" => $next_term_due_date,
                "amortization" => $res_mtg[0]->amortization ?? null,
                "newInterestRate" => $new_interest_rate_display,
                "apInterestRate" => $new_interest_rate_ap_display,
                "bpInterestRate" => $new_interest_rate_bp_display,
                "cpInterestRate" => $new_interest_rate_cp_display,
                "contingentTable" => $contingentTable ?? [],
                "suggestedNewPayment" => $suggested_new_payment,
                "suggestedNewPaymentAP" => $suggested_new_payment_ap,
                "suggestedNewPaymentBP" => $suggested_new_payment_bp,
                "suggestedNewPaymentCP" => $suggested_new_payment_cp,
                "osbAtRenewal" => $osb_at_renewal,
                "osbAtRenewalAP" => $osb_at_renewal_ap,
                "osbAtRenewalBP" => $osb_at_renewal_bp,
                "osbAtRenewalCP" => $osb_at_renewal_cp,
                "propertyValuation" => $property_valuation_display,
                "propertyValuationFee" => $property_valuation_fee_display,
                "propertyValuationFee2" => $property_valuation_fee_2,
                "renewalFee" => $renewal_fee_display,
                "renewalFeeAP" => $renewal_fee_ap_display,
                "renewalFeeBP" => $renewal_fee_bp_display,
                "renewalFeeCP" => $renewal_fee_cp_display,
                "renewalFeeToBePaidOver" => $renewal_fee_to_be_paid_over,
                "signedReceivedByBorrower" => $signed_received_by_borrower,
                "signedReceivedByInvestor" => $signed_received_by_investor,
                "newPostDatedChequesStartDate" => $new_post_dated_cheques_start_date,
                "newPostDatedCheques1stPmt" => $new_post_dated_cheques_first_pmt,
                "newPostDatedChequesEndDate" => $new_post_dated_cheques_end_date,
                "newPostDatedChequesRegPmt" => $new_post_dated_cheques_reg_pmt,
                "comments" => $mortgage_renewal_comments,
                "osbRenewalFeeMaster" => $osb_renewal_fee_master,
                "osbRenewalFeeAP" => $osb_renewal_fee_ap,
                "osbRenewalFeeBP" => $osb_renewal_fee_bp,
                "osbRenewalFeeCP" => $osb_renewal_fee_cp,
                "newMonthlyPmtMaster" => $new_monthly_pmt_master,
                "newMonthlyPmtAP" => $new_monthly_pmt_ap,
                "newMonthlyPmtBP" => $new_monthly_pmt_bp,
                "newMonthlyPmtCP" => $new_monthly_pmt_cp,
                "numberOfMonthInTerm" => $no_of_month_in_term,
                "osbAtNextTermEndMaster" => $osb_at_next_term_end_master,
                "osbAtNextTermEndAP" => $osb_at_next_term_end_ap,
                "osbAtNextTermEndBP" => $osb_at_next_term_end_bp,
                "osbAtNextTermEndCP" => $osb_at_next_term_end_cp,
                "annualEffectiveRateMaster" => $annual_effective_rate_master,
                "annualEffectiveRateAP" => $annual_effective_rate_ap,
                "annualEffectiveRateBP" => $annual_effective_rate_bp,
                "annualEffectiveRateCP" => $annual_effective_rate_cp,
                "semiAnnualEquivalentIntRateMaster" => $semi_annual_equivalent_int_rate_master,
                "semiAnnualEquivalentIntRateAP" => $semi_annual_equivalent_int_rate_ap,
                "semiAnnualEquivalentIntRateBP" => $semi_annual_equivalent_int_rate_bp,
                "semiAnnualEquivalentIntRateCP" => $semi_annual_equivalent_int_rate_cp,
                "aprOsbAtRenewal" => $apr_osb_at_renewal,
                "aprRenewalFee" => $apr_renewal_fee,
                "aprPropertyValuationFee" => $apr_property_valuation_fee,
                "aprNewTermLength" => $apr_new_term_length,
                "aprNewMonthlyPayment" => $apr_new_monthly_payment,
                "aprNewTotalPayment" => $apr_new_total_payment,
                "aprBalanceAtNextTermEnd" => $apr_balance_at_next_term_end,
                "aprTotalPayments" => $apr_total_payments,
                "totalCostOfCredit" => $total_cost_of_credit,
                "pmtRows" => $pmt_rows,
                "averageOsBal" => $average_os_bal,
                "apr" => $apr_display,
                "remainingAmortization" => $remaining_amortization,
                "isMortgageProblem" => $is_mortgage_problem,
                "mortgageProblem" => $mortgage_problem,
                "mortgageMarginable" => $mortgage_marginable,
                "marginableMessage" => $marginable_message,
                // Fields used to filter
                "cInvCardCount" => count($cInvCard ?? []), 
                "isABLoan" => $is_ab_loan ?? false, 
                "brokerGroup" => $res_mtg[0]->brokerGroup, 
                "loanType" => $loan_type,
                // Fields used for calculation
                "defaultPvf" => $default_pvf,
                "osbVar" => $osb_var,
                // "renewalFee" => $renewalFee,
                "orgRenewalFee" => $renewalFee,
                "renewalOsbBox"=> $renewal_osb_box,
                "intBox" => $int_box,
                "amortBox" => $amort_box,
                "compBox" => $comp_box,
                "ab" => $ab, 
                "pmtBox" => $pmt_box,
                "oldMonthlyPayment" => $old_monthly_payment,
                "pmt" => $pmt,
                "loanBox" => $loan_box,
                "osbBox" => $osb_box,
                "grossBox" => $gross_box,
                "mPmt" => $m_pmt,
                "n" => $n,
                "postStartValue" => $post_start_value,
                "postStartDate" => $post_start_date,
                "postIntRate" => $post_int_rate,
                "postNumPmts" => $post_num_pmts,
                "postFirstPmt" => $post_first_pmt,
                "postFirstPmtDate" => $post_first_pmt_date,
                "postOmitFirstPmt" => $post_omit_first_pmt,
                "postTermDate" => $post_term_date,
                "pmtDayArr" => $pmt_day_arr,
                "pmtAmtArr" => $pmt_amt_arr,
                "cInvCard" => $cInvCard ?? [],
                "mortgageId" => $mortgageId,
                "renewalId" => $renewalId,
                "originalMonthlyPayment" => $old_pmt,
                "parent" => $res_mtg[0]->parent,
                "dueDate" => $res_mtg[0]->due_date,
                "rowRenewal" => $row_renewa ?? null,
                "osbDisplay" => $osb_display,
                "renewalFeeDisplay2" => $renewal_fee_display_2,
                "approvedBy" => $approved_by,
                "company" => $company_name,
                "agent" => $agent,
                "renewalFeeCalc" => $renewal_fee,
                "c_osb_per0 " => $c_osb_per0 ?? 0,
                "c_osb_per1 " => $c_osb_per1 ?? 0,
                "c_osb_per2 " => $c_osb_per2 ?? 0,
                "startValue" => $start_value,
                "origOsb" => $orig_osb,
                "firstPmt" => $first_pmt,
                "mortgageTableStartValue" => $mortgage_table_start_value,
                "rowRenewalStartValue" => $row_renewal_start_value
                // "termLength" => $res_mtg[0]->term_length,
                // "osb" => $osb,
                // "ltv" => round($res_mtg[0]->ltv ?? 0, 2),
                // "oldPmt" => $old_pmt
            ];
            return $calculateRenewalData;
        } else {
            return false;
        }
    }

    private function isRenewalExist($mortgageId, $dueDate, $status = null) {
        $this->logger->info('RenewalApprovalBO->isRenewalExist', [$mortgageId, $dueDate, $status]);

        $query = 
            "SELECT COUNT(*) AS renewal_count
            FROM renewal_approval
            WHERE mortgage_id = ?
            AND due_date = ?
            ";

        $bindings = [$mortgageId, $dueDate];

        if($status !== null) {
            $query.= " AND status = ? ";
            $bindings[] = $status;
        } else {
            $query.= " AND status IN ('A', 'R') ";
        }

        $res = $this->db->select($query, $bindings);

        if(isset($res[0]->renewal_count) && $res[0]->renewal_count > 0) {
            return true;
        }

        return false;
    }

    private function isVariableByDate($secondPrime, $secondYear, $loan_term, $intCommDate, $date) {
        $initial_variable = false;
        
        $loanTerm = $loan_term ? $loan_term : 0;
        if ($loanTerm > 12 && $secondPrime != 0 && $secondYear != 0) {
            $initial_variable = true;
        }
        if (!$initial_variable) {
            return false;
        }

        $loanTerm = $loan_term ? $loan_term : 12;

        [$y, $m, $d] = explode('-', $intCommDate);
        $varEndDate = new DateTime();
        $varEndDate->setDate((int)$y, (int)$m, (int)$d);
        $varEndDate->setTime(0, 0, 0);
        $varEndDate->modify("+$loanTerm months");

        $compareDate = new DateTime($date);
        $compareDate->setTime(0, 0, 0);

        return $varEndDate >= $compareDate;

    }

    private function getSalesInvestorRow($mortgageId, $applicationId) {
        $query = 
            "SELECT 
                sit.ap_inv_co,
                sit.bp_inv_co,
                sit.cp_inv_co,
                sit.ap_payment,
                sit.bp_payment,
                sit.cp_payment,
                sit.ap_percent,
                sit.bp_percent,
                sit.cp_percent
            FROM mortgage_payments_table mpt
            LEFT JOIN saved_quote_table sqt ON sqt.mortgage_id = mpt.transfer_mortgage_id AND sqt.application_id = ?
            LEFT JOIN sale_investor_table sit ON sit.investor_id = 1971 AND sit.fm_committed = 'Yes' and sit.saved_quote_id = sqt.saved_quote_id
            WHERE mpt.mortgage_id = ? 
            AND mpt.transfer_mortgage = 'yes'
        ";
        $res = $this->db->select($query,[$applicationId, $mortgageId]);

        return $res;
    }

    private function getCInvCard($mortgageId) {
        $query = 
            "SELECT 
                m.mortgage_id, 
                m.mortgage_code, 
                m.company_id, 
                m.interest_rate, 
                m.monthly_pmt, 
                m.current_balance
            FROM mortgage_table m 
            WHERE m.is_deleted = 'no' 
            AND m.ab_loan = 'c_inv' 
            AND m.parent = ?
            ORDER BY mortgage_id ASC
        ";
        $res = $this->db->select($query,[$mortgageId]);

        return $res;
    }

    private function AER($yr, $n) {
        $aer = pow(1 + $yr / 100 / $n, $n) - 1;
        return $aer * 100;
    }

    private function SAER($yr, $n) {
        $saer = (pow(1 + $yr / 100 / $n, $n / 2) - 1) * 2;
        return $saer * 100;
    }

    private function EFV($pv, $pmt, $r, $n) {
        $xr = pow(1 + $r, $n);
        $fv = $pv * $xr - $pmt * (($xr - 1) / $r);
        return $fv;
    }

    private function toTwoDigits($number) {
        return number_format($number, 2, '.', '');
    }

    private function r($number) {
        $number = round($number, 2);
        if ($number == 0.00) {
            $number = '';
        }
        return $number;
    }

    private function EPMT($r, $n, $pv, $fv = 0.00, $type = 0) {
        if ($r == 0) {
            return -($pv + $fv) / $n;
        }

        $xp = pow(1 + $r, $n);
        $payment = ($pv * $r * $xp / ($xp - 1) + $r / ($xp - 1) * $fv);
        return $type == 0 ? $payment : $payment / ($r + 1);
    }

    private function toFloat($number) {
        if ($number == '' || is_null($number)) {
            return null;
        }
        
        return floatval(str_replace('%','',str_replace(',','',preg_replace("[^0-9.]", '', $number))));
    }

    private function interest($gross_amt, $interest_rate, $days, $monthly_calculation, $add_one) {

        $prod1 = $gross_amt * $interest_rate;

        if($monthly_calculation) {
            $quot1 = $prod1 / 12;
            $quot2 = $quot1 / 30;
        } else { 
            $quot2 = $prod1 / 365;
        }

        if($add_one) {
            $days++;
        }

        return $quot2 * $days;
    }

    private function osb($interest_amt, $nsf, $pmt, $osb_old) {
        return $interest_amt + $nsf - $pmt + $osb_old;
    }

    private function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
        /*
        $interval can be:
        yyyy - Number of full years
        q - Number of full quarters
        m - Number of full months
        y - Difference between day numbers
            (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
        d - Number of full days
        w - Number of full weekdays
        ww - Number of full weeks
        h - Number of full hours
        n - Number of full minutes
        s - Number of full seconds (default)
        */

        if(!$using_timestamps) {
            $datefrom = strtotime($datefrom, 0);
            $dateto = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds

        switch($interval) {
            case 'yyyy': // Number of full years
                $years_difference = floor($difference / 31536000);
                if(mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                    $years_difference--;
                }
                if(mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                    $years_difference++;
                }
                $datediff = $years_difference;
                break;

            case "q": // Number of full quarters
                $months_difference = 0;
                $quarters_difference = floor($difference / 8035200);
                while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($quarters_difference * 3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                    $months_difference++;
                }
                $quarters_difference--;
                $datediff = $quarters_difference;
                break;

            case "m": // Number of full months
                $months_difference = floor($difference / 2678400);
                while(mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + $months_difference, date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                    $months_difference++;
                }
                $months_difference--;
                $datediff = $months_difference;
                break;

            case 'y': // Difference between day numbers
                $datediff = date("z", $dateto) - date("z", $datefrom);
                break;

            case "d": // Number of full days
                $datediff = floor($difference / 86400);
                break;

            case "w": // Number of full weekdays
                $days_difference = floor($difference / 86400);
                $weeks_difference = floor($days_difference / 7); // Complete weeks
                $first_day = date("w", $datefrom);
                $days_remainder = floor($days_difference % 7);
                $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
                if($odd_days > 7) { // Sunday
                    $days_remainder--;
                }
                if($odd_days > 6) { // Saturday
                    $days_remainder--;
                }
                $datediff = ($weeks_difference * 5) + $days_remainder;
                break;

            case "ww": // Number of full weeks
                $datediff = floor($difference / 604800);
                break;

            case "h": // Number of full hours
                $datediff = floor($difference / 3600);
                break;

            case "n": // Number of full minutes
                $datediff = floor($difference / 60);
                break;

            default: // Number of full seconds (default)
                $datediff = $difference;
                break;
        }

        return $datediff;
    }

    private function getOperator2($taskArrays,$task,$company,$province) {
        if (0 == strcmp("2",$company)) {
            $company = "1";
        }

        foreach($taskArrays as $key1 => $value1) {
            $B = $value1->abbr;
            $C = $value1->tc_company_id;
            $E = $value1->tc_operator_id;

            if (0 == strcmp($B,$task)) {
                if (0 == strcmp($C,$company)) {
                    $default_operator = $E;
                    return ($default_operator);
                }
            }
        }
        return "99";
    }
}
