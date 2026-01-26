<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\SavedQuoteTable;
use App\Models\SaleInvestorTable;
use App\Models\CategoryValue;

use DateTime;

class ApplicationDashboardBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getMortgages($applicationId) {

        $categoryArray = CategoryValue::where('live', 'Y')
        ->whereIn('category_id', [43, 44, 50, 54])
        ->pluck('name', 'id')
        ->toArray();       

        $query = "SELECT m.mortgage_id, m.mortgage_code, b.period_date, m.gross_amt, m.current_balance, m.ltv, m.payout_at,
                         c.ltv investor_ltv, c.mortgage_code investor_code, c.mortgage_id investor_id, c.current_balance,
                         m.mortgage_int_comm_date,m.gross_amt,m.interest_rate,m.monthly_pmt,m.amortization,m.due_date,m.nsf_fee,m.assignment_of_rent,
                         m.brokerGroup,m.mortgage_type,m.type_of_loan,m.loan_category,
                         m.ab_loan,m.pari_passu,m.company_id,m.cpsInstruct,m.mortgage_registration_number,m.assignment_of_rents_registration_number,m.is_funded,
                         m.initial_ltv_method,m.initial_ltv,d.mortgage_code mtgTransferCode,a.company,sq.mip,m.insurance,m.earthquake,a.company
                    from mortgage_table m
                    join mortgage_payments_table b on m.mortgage_id = b.mortgage_id and b.payment_id = 1
                    join application_table a on m.application_id = a.application_id
               left join mortgage_table c on c.transfer_id = m.mortgage_id
               left join mortgage_table d on d.transfer_id = m.mortgage_id and transfer_mortgage = 'yes'
               left join saved_quote_table sq on sq.mortgage_id = m.mortgage_id
                   where m.application_id = ?
                     and m.is_deleted = 'no'
                     and m.company_id in (1, 3, 301, 601, 401, 701, 801, 2022)
                order by mortgage_id desc";
        $result = $this->db->select($query, [$applicationId]);

        $data = [];

        foreach($result as $value) {

            $query2 = "select * from notes_table d
                        where (d.application_id = ? or d.mortgage_id = ?)
                          and category_id in (7,69)
                          and followed_up = 'no'";
            $res = $this->db->select($query2, [$applicationId, $value->mortgage_id]);

            $foreclosure = count($res) > 0 ? true : false;

            $initialLtv = null;

            $currentLtv = $value->ltv;

            if ($value->initial_ltv_method != "auto") {
                $initialLtv = $value->initial_ltv;
            }

            if (empty($value->due_date) || ($value->due_date == "0000-00-00")) {
                $queryTerm = 'SELECT MAX(term_end) AS last_term_end FROM mortgage_interest_rates_table WHERE mortgage_id="' . $value->mortgage_id . '"';
                $rowTerm = $this->db->select($queryTerm);
                $dueDate = $rowTerm[0]->last_term_end;
            } else {
                $dueDate = $value->due_date;
            }

            if ($value->earthquake == 'Yes') {
                $earthquake = 'Yes';
            }else {
                $earthquake = 'No';
            }

            $data[] = [
                'id' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'grossAmount' => $value->gross_amt,
                'fundedDate' => $value->period_date,
                'positions' => $this->getMortgagePositions($value->mortgage_id),
                'lenderName' => $this->getLenderName($value->mortgage_id),
                'paidOut' => is_null($value->payout_at) ? false : true,
                'foreclosure' => $foreclosure,
                'currentLTV' => is_null($value->investor_ltv) ? $value->ltv : $value->investor_ltv,
                'investorCode' => $value->investor_code,
                'investorId' => $value->investor_id,
                'currentBalance' => $value->current_balance,
                'mortgageIntCommDate' => $value->mortgage_int_comm_date,
                'interestRate' => $value->interest_rate,
                'monthlyPmt' => $value->monthly_pmt,
                'amortization' => $value->amortization,
                'dueDate' => $dueDate,
                'nsfFee' => $value->nsf_fee,
                'assignmentOfRent' => $value->assignment_of_rent,
                'brokerGroup' => $value->brokerGroup,
                'mortgageType' => $categoryArray[$value->mortgage_type] ?? '',
                'typeOfLoan' => $categoryArray[$value->type_of_loan] ?? '',
                'loanCategory' => $categoryArray[$value->loan_category] ?? '',
                'abLoan' => $value->ab_loan,
                'pariPassu' => $value->pari_passu,
                'companyId' => $value->company_id,
                'cpsInstruct' => $value->cpsInstruct,
                'mortgageRegistrationNumber' => $value->mortgage_registration_number,
                'assignmentOfRentsRegistrationNumber' => $value->assignment_of_rents_registration_number,
                'isFunded' => $value->is_funded,
                'initialLtv' => $initialLtv,
                'currentLtv' => $currentLtv,
                'company' => $value->company,
                'mip' => $value->mip,
                'insurance' => $value->insurance,
                'earthquake' => $earthquake,
                'company' => $value->company,
            ];
        }
        
        $sortOption = array('desc' => SORT_DESC, 'asc' => SORT_ASC, 'string' => SORT_STRING, 'numeric' => SORT_NUMERIC);

        $columns = array();
        $columns[0] = array_column($data, 'paidOut');
        $param[] = &$columns[0];
        $param[] = &$sortOption['asc'];

        $columns[1] = array_column($data, 'mortgageCode');
        $param[] = &$columns[1];
        $param[] = &$sortOption['asc'];

        $param[] = &$data;

        call_user_func_array('array_multisort', $param);
    
        return $data;
    }

    private function getLenderName($mortgageId) {
        $query = "SELECT investor_tracking_id FROM mortgage_payments_table
                   WHERE mortgage_id = ? AND is_sale = 'yes'";
        $res = $this->db->select($query, [$mortgageId]);
    
        if(count($res) > 0) {
            $investorTrackingId = $res[0]->investor_tracking_id;
    
            // Use the investor_tracking_id to find the associated investor_id
            $queryInvestorTracking = "SELECT investor_id FROM mortgage_investor_tracking_table 
                                      WHERE mortgage_id = ? AND investor_tracking_id = ?";
            $resultInvestorTracking = $this->db->select($queryInvestorTracking, [$mortgageId, $investorTrackingId]);
    
            if (count($resultInvestorTracking) > 0) {
                $investorId = $resultInvestorTracking[0]->investor_id;
    
                // Fetch investor's name using investor_id
                $queryInvestor = "SELECT first_name, last_name FROM investor_table WHERE investor_id = ?";
                $resultInvestor = $this->db->select($queryInvestor, [$investorId]);
    
                if (count($resultInvestor) > 0) {
                    return $resultInvestor[0]->first_name . ' ' . $resultInvestor[0]->last_name;
                }
            }
        }

        return '';
    }
    
    /*private function determineStatusAndLender($mortgageId, $currentBalance) {
        $status = 'Active';
        $paidOut = ""; // Initialize empty string for "Paid out" marker
    
        // 1. Check for Payout Status
        $queryPayout = "SELECT * FROM mortgage_payments_table WHERE mortgage_id = ? AND is_payout = 'yes'";
        $resultPayout = $this->db->select($queryPayout, [$mortgageId]);
        if (count($resultPayout) > 0) {
            $status = 'Paid Out';
        }
    
        // 2. Check if the mortgage is sold and retrieve investor details
        $querySale = "SELECT investor_tracking_id FROM mortgage_payments_table WHERE mortgage_id = ? AND is_sale = 'yes'";
        $resultSale = $this->db->select($querySale, [$mortgageId]);
    
        if (count($resultSale) > 0) {
            $investorTrackingId = $resultSale[0]->investor_tracking_id;
    
            // Use the investor_tracking_id to find the associated investor_id
            $queryInvestorTracking = "SELECT investor_id FROM mortgage_investor_tracking_table 
                                      WHERE mortgage_id = ? AND investor_tracking_id = ?";
            $resultInvestorTracking = $this->db->select($queryInvestorTracking, [$mortgageId, $investorTrackingId]);
    
            if (count($resultInvestorTracking) > 0) {
                $investorId = $resultInvestorTracking[0]->investor_id;
    
                // Fetch investor's name using investor_id
                $queryInvestor = "SELECT first_name, last_name FROM investor_table WHERE investor_id = ?";
                $resultInvestor = $this->db->select($queryInvestor, [$investorId]);
    
                if (count($resultInvestor) > 0) {
                    $investorName = $resultInvestor[0]->first_name . ' ' . $resultInvestor[0]->last_name;
                    $status = 'Sold (' . $investorName . ')';
                } else {
                    // No investor found; set to default as per TACL logic
                    $status = 'Sold ( )';
                }
            } else {
                // No investor_id found based on investor_tracking_id
                $status = 'Sold ( )';
            }
        }
    
        // 3. Check if there's a next payout status (similar to TACL logic)
        $queryNextPayout = "SELECT mp.mortgage_id 
                            FROM mortgage_payments_table mp
                            JOIN mortgage_table m ON m.mortgage_id = mp.mortgage_id
                            WHERE m.is_deleted = 'no'
                            AND mp.transfer_mortgage_id = ?";
        $resultNextPayout = $this->db->select($queryNextPayout, [$mortgageId]);
    
        if (count($resultNextPayout) > 0) {
            $nextMortgageId = $resultNextPayout[0]->mortgage_id;
            $queryPayoutCheck = "SELECT * FROM mortgage_payments_table 
                                 WHERE mortgage_id = ? AND is_payout = 'yes'";
            $payoutCheckResult = $this->db->select($queryPayoutCheck, [$nextMortgageId]);
    
            if (count($payoutCheckResult) > 0) {
                $paidOut = " - Paid out";
            }
        }
    
        // 4. Set status to "Zeroed Out" if active but no balance
        if ($status === 'Active' && $currentBalance == 0) {
            $status = 'Zeroed Out';
        }
    
        // Combine status with paidOut indicator if applicable
        return $status . $paidOut;
    }*/
    
    private function getMortgagePositions($mortgageId) {
        $positions = [];
        $query = "SELECT position FROM mortgage_properties_table WHERE mortgage_id = ?";
        $result = $this->db->select($query, [$mortgageId]);
    
        foreach ($result as $value) {
            $positions[] = $value->position;
        }
    
        return implode('/', $positions);
    }

    public function getNotes($applicationId) {

        $data = [];
    
        if($applicationId <= 0) {
            $this->logger->warning("ApplicationDashboardBO->getApplicationNotes - Invalid application ID", [$applicationId]);
            return $data;
        }
    
        $query = "SELECT n.note_id, n.followup_date, n.note_text, u.user_fname, u.user_lname,
                         n.mortgage_id, m.mortgage_code, n.follower_up, n.followed_up, n.category_id, n.turndown_id
                    FROM notes_table n
                    JOIN users_table u ON n.follower_up = u.user_id
               LEFT JOIN mortgage_table m ON n.mortgage_id = m.mortgage_id
                   WHERE n.application_id = ?
                ORDER BY followed_up desc, n.followup_date asc";
        $result = $this->db->select($query, [$applicationId]);

        foreach($result as $key => $note) {
            
            $noteText = $note->note_text;

            $noteText = preg_replace('/(\\r\\n){3,}/', "\r\n\r\n", $note->note_text);
            $noteText = preg_replace('/^(\\r\\n){1,}/', '', $noteText);

            $date = '';
            if (isset($note->followup_date) && !empty($note->followup_date) && $note->followup_date != '0000-00-00' && $note->followup_date != '0000-00-00 00:00:00') {
                $date = date('Y-m-d', strtotime($note->followup_date));
            }

            $data[] = [
                'noteId' => $note->note_id,
                'followUpDate' => $date,
                'mortgageCode' => $note->mortgage_code,
                'noteText' => $noteText,
                'delegatedTo' => $note->user_fname . ' ' . $note->user_lname,
                'followerUp' => $note->follower_up,
                'followedUp' => $note->followed_up,
                'categoryId' => $note->category_id,
                'turnDownId' => $note->turndown_id
            ];
        }

        return $data;
    }

    public function getMyApps($userId) {
        $query = "SELECT ac.abbr, st.name status, a.application_id, sfi.salesforce_id,
                         concat(s.f_name, ' ', s.l_name) client_name, ap.home_phone,
                         concat(u.user_fname, ' ', u.user_lname) agent,
                         concat(sa.signing_fname, ' ', sa.signing_lname) signing_agent,
                         a.signing_datetime,
                         a.funding_date,
                         concat(n.u_fname, ' ', n.u_lname) followup_name, 
                         n.followup_date,
                         a.amt_required, 
                         c.category_name,
                         a.application_id
                    from application_table a
               LEFT JOIN (SELECT application_id, followed_up, followup_date, follower_up, ut.user_fname u_fname, ut.user_lname u_lname, category_id,last_updated FROM notes_table nt LEFT JOIN users_table ut ON nt.follower_up = ut.user_id WHERE follower_up = ? AND followed_up = 'no' ORDER BY followup_date) n ON a.application_id = n.application_id
               LEFT JOIN applicant_table ap ON a.application_id=ap.application_id
               LEFT JOIN spouse_table s ON ap.spouse1_id = s.spouse_id
               LEFT JOIN status_table st ON a.status = st.id
               LEFT JOIN users_table u ON a.agent = u.user_id
               LEFT JOIN (SELECT user_id, user_fname signing_fname, user_lname signing_lname FROM users_table u) sa ON a.signing_agent = sa.user_id
               LEFT JOIN note_categories_table c ON n.category_id = c.category_id
               LEFT JOIN mortgage_table m ON a.application_id = m.application_id
               left join salesforce_integration sfi on sfi.object_id = a.application_id and sfi.salesforce_object = 'Opportunity'
                    join alpine_companies_table ac on ac.id = a.company
                   where (a.agent = ? or a.signing_agent = ? or n.follower_up = ?)
                     AND last_updated > '2004-01-01 11:11:11'
                     AND a.company in (1,3,301,401,601,701,801,2022)
                GROUP BY a.application_id, n.followup_date, n.category_id
                ORDER BY n.followup_date asc, status asc";
        $res = $this->db->select($query, [$userId,$userId,$userId,$userId]);

        $data = array();
        foreach($res as $key => $value) {
            $today = new DateTime();

            $followUpDate = null;
            $followUpDateOverdue = false;
            if($value->followup_date != '0000-00-00 00:00:00') {
                $followUpDate = new DateTime($value->followup_date);
                $followUpDateOverdue = $followUpDate->format('Ymd') == $today->format('Ymd') ? null : ($followUpDate < new DateTime() ? true : false);
            }

            $fundingDate = null;
            $fundingDateOverdue = false;
            if($value->funding_date != '0000-00-00' && $value->status != 'Funded') {
                $fundingDate = new DateTime($value->funding_date);
                $fundingDateOverdue = $fundingDate->format('Ymd') == $today->format('Ymd') ? null : ($fundingDate < new DateTime() ? true : false);
            }

            $signingDateTime = null;
            $signingDateTimeOverdue = false;
            if($value->signing_datetime != '0000-00-00 00:00:00') {
                $signingDateTime = new DateTime($value->signing_datetime);
                $signingDateTimeOverdue = $signingDateTime->format('Ymd') == $today->format('Ymd') ? null : ($signingDateTime < new DateTime() ? true : false);
            }

            if(($value->status != 'Signing' && $value->status != 'Active') || $value->status == 'Signed' || $value->status == 'Funded') {
                $signingDateTime = null;
            }

            $readyBuy = 'N';
            $investor = '';
            $fmCommitted = '';
            $quoteData = $this->getQuoteData($value->application_id);
            if(isset($quoteData['readyBuy'])) {
                $readyBuy = $quoteData['readyBuy'];
                $investor = $quoteData['investor'];
                $fmCommitted = $quoteData['fmCommitted'];
            }

            $data[] = [
                'company' => $value->abbr,
                'status' => $value->status,
                'applicationId' => $value->application_id,
                'salesforceId' => $value->salesforce_id,
                'clientName' => $value->client_name,
                'homePhone' => $value->home_phone,
                'agent' => $value->agent,
                'signingAgent' => $value->signing_agent,
                'signingDateTime' => is_null($signingDateTime) ? '' : $signingDateTime->format('m/d/Y h:ia'),
                'signingDateTimeS' => is_null($signingDateTime) ? '' : $signingDateTime->format('Y-m-d H:i:s'),
                'signingDateTimeOverdue' => $signingDateTimeOverdue,
                'fundingDate' => is_null($fundingDate) ? '' : $fundingDate->format('m/d/Y'),
                'fundingDateS' => is_null($fundingDate) ? '' : $fundingDate->format('Y-m-d'),
                'fundingDateOverdue' => $fundingDateOverdue,
                'followupName' => $value->followup_name,
                'followupDate' => is_null($followUpDate) ? '' : $followUpDate->format('m/d/Y h:ia'),
                'followupDateS' => is_null($followUpDate) ? '' : $followUpDate->format('Y-m-d H:i:s'),
                'followUpDateOverdue' => $followUpDateOverdue,
                'amountRequired' => $value->amt_required,
                'categoryName' => $value->category_name,
                'readyBuy' => $readyBuy,
                'investor' => $investor,
                'fmCommitted' => $fmCommitted
            ];
        }

        return $data;
    }

    public function getQuoteData($applicationId) {

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('application_id', $applicationId)
        ->where('disburse', 'Yes')
        ->get();

        $readyBuy = '';
        $investor = '';
        $fmCommitted = '';

        $yesNoArray = array(
            'Yes' => 'Y',
            'No' => 'N'
        );

        $committedArray = array(
            'If Available' => 'IA',
            'Looking' => 'L',
            'Yes' => 'Y',
            'No' => 'N'
        );  
        
        $investorArray = array(
            '0' => 'ACL',
            '31' => 'RMIF',
            '100' => 'BSF',
            '248' => 'MII',
            '1971' => 'ABL'
        );        

        $quoteData = array();

        foreach($savedQuoteTable as $key => $value) {

            $saleInvestorTable = SaleInvestorTable::query()
            ->where('saved_quote_id', $value->saved_quote_id)
            ->get();

            if ($readyBuy !== '') {
                $readyBuy .= ' ';
            }
            $readyBuy .= $yesNoArray[$value->ready_buy] ?? '';

            foreach($saleInvestorTable as $rowInvestor){
                $investor .= ($investorArray[$rowInvestor['investor_id']] ?? '') . ' ';
                $fmCommitted .= ($committedArray[$rowInvestor['fm_committed']] ?? '') . ' ';
            }            
            $readyBuy    = trim($readyBuy);
            $investor    = trim($investor);
            $fmCommitted = trim($fmCommitted);
        }

        $quoteData = [
            'readyBuy' => $readyBuy,
            'investor' => $investor,
            'fmCommitted' => $fmCommitted
        ];

        return $quoteData;

    }    
    
}