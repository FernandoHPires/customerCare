<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\BO\ApplicationBO;
use App\Models\SavedQuoteTable;
use App\Models\AlpineCompaniesTable;
use App\Models\MailingTable;
use App\Amur\Utilities\Utils;
use App\Models\MortgagePropertiesTable;
use App\Models\MortgageTable;
use App\Models\LiabilityTable;
use DateTime;

class DisbursementBO {

    private $logger;
    private $db;
    private $guarantors;
    private $netAmount;
    private $titleHolders = array();
    private $propertyAddresses = array();
    private $witnesses = array();

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }
    
    public function getDisbursement($applicationId) {
        $this->logger->info('DisbursementBO->getDisbursement',[$applicationId]);

        $applicationBO = new ApplicationBO($this->logger, $this->db);
        $applicationTable = $applicationBO->getApplicationTable($applicationId);
        $this->getMailingTable($applicationId);

        $company = $applicationTable->company ?? '';

        $savedQuoteData = SavedQuoteTable::query()
        ->where('application_id', $applicationId)
        ->where('disburse', 'Yes')
        ->get();

        $totalNetAmount  = 0;
        $this->netAmount = 0;
        $holdBackAmount  = 0;
        $savedQuotes = array();
        $propertyArreas = array();
        foreach($savedQuoteData as $key => $value) {

            $savedQuotePositionData = $this->getSavedQuotePositionTable($applicationId, $value->saved_quote_id, $company);
            
            $propertyArreas = $this->getPropertyArrears($value->saved_quote_id);

            $t = implode(', ', $this->titleHolders);
            $titH = $this->titleHolders($applicationId, $t);

            $mortgagorsName = null;
            if(count($titH) > 0) {
                foreach($titH as $keyA => $applName) {
                    if($mortgagorsName == null) {
                        $mortgagorsName = $keyA;
                    } else {
                        $mortgagorsName .= ', ' . $keyA;
                    }
                    $this->witnesses[] = $keyA;
                }
            }

            $amortization = $value->amort . ' years';

            $TmpDate = '';
            if ($value->first_pmt_date != "0000-00-00") {
                $TmpDate = new DateTime($value->first_pmt_date);
                $TmpDate->modify('+' . (($value->loan * 1) - 1) . ' months');
            }

            if ($TmpDate != '') {
                $term = $value->loan . ' months / ' . $TmpDate->format('m/d/Y');
            } else {
                $term = $value->loan . ' months';
            }

            $thisNetAmount = 0;

            if (isset($value->gross)) {
                $thisNetAmount += $value->gross;
            }
            if (isset($value->legal)) {
                $thisNetAmount -= $value->legal;
            }
            if (isset($value->appr)) {
                $sq_appr = $value->appr;
                if (isset($value->retainer_disb) && $value->retainer_disb != 'false' && $value->retainer != '' && $value->retainer != '0.00') {
                    $sq_appr -= $value->retainer;
                }
                $thisNetAmount -= $sq_appr;
            }
            if (isset($value->broker)) {
                $thisNetAmount -= $value->broker;
            }
            if (isset($value->discount)) {
                $thisNetAmount -= $value->discount;
            }
            if (isset($value->misc)) {
                $thisNetAmount -= $value->misc;
            }
            $this->netAmount += $thisNetAmount;
            $totalNetAmount += $thisNetAmount;

            if ($value->hold_back == 'Yes') {
                $holdBackAmount += $value->hold_back_amount;
                $this->netAmount -= $value->hold_back_amount;
            }

            if ($value->client_auth_date == '0000-00-00') {
                $clientAuthDate = '';
            } else {
                $clientAuthDate = $value->client_auth_date;
            }

            if ($value->int_comm_date == '0000-00-00') {
                $intCommDate = '';
            }else {
                $intCommDate = $value->int_comm_date;
            }

            if ($value->first_pmt_date == '0000-00-00') {
                $firstPaymentDue = '';
            }else {
                $firstPaymentDue = $value->first_pmt_date;
            }

            $savedQuotes[] = [
                'clientAuthDate' => $clientAuthDate,
                'documentation' => $value->documentation,
                'savedQuotePosition' => $savedQuotePositionData,
                'mortgagorsName' => $mortgagorsName,
                'propertyAddresses' => $this->propertyAddresses,
                'amountofMortgage' => $value->gross,
                'interestCommences' => $intCommDate,
                'interestRate' => $value->int,
                'firstPaymentDue' => $firstPaymentDue,
                'monthlyPayments' => $value->month,
                'amortization' => $amortization,
                'term' => $term,
                'mip' => $value->mip,
                'assignmentofRent' => $value->assn_rent,
                'holdBackRequired' => $value->hold_back,
                'legalFeesDisbursements' => $value->legal,
                'applicationFee' => $value->appr,
                'brokerageFee' => $value->broker,
                'lenderFee' => $value->discount,
                'appraisalFee' => $value->misc,
                'approximateNetProceedsAvailable' => $thisNetAmount
            ];
        }   

        $this->getApplicantTable($applicationId);

        $payouts = $this->getPayouts($applicationId);
        $liabilities = $this->getLiabilities($applicationId);
        $propertyMortgages = $this->getPropertyMortgages($applicationId);

        return [
            'savedQuotes' => $savedQuotes,
            'guarantors' => $this->guarantors,
            'totalNetProceedsAvailable' => $totalNetAmount,
            'propertyArreas' => $propertyArreas,
            'aproximateNetProceedstoBorrower' => $this->netAmount,
            'witnesses' => $this->witnesses,
            'payouts' => $payouts,
            'liabilities' => $liabilities,
            'propertyMortgages' => $propertyMortgages,
            'holdBackAmount' => $holdBackAmount,
        ];
    }

    public function getSavedQuotePositionTable($applicationId, $savedQuoteId, $company) {
        
        $query = "select a.position, b.unit_number, b.street_number, b.street_name, b.street_type, b.street_direction,
                            b.city, b.province, b.postal_code, b.pid, b.legal, b.title_holders, b.property_id, b.alpine_interest
                    from saved_quote_positions_table a
                    join property_table b on b.idx = a.idx and b.application_id = ?
                    where a.saved_quote_id = ?
                        and a.position <> 'N/A'";
        $quotePositionTable = $this->db->select($query, [$applicationId, $savedQuoteId]);

        $this->titleHolders = array();
        $this->propertyAddresses = array();
        $quotePosition = array();

        foreach($quotePositionTable as $keyPt => $valuePt) {
            if($valuePt->position != 'N/A') {
                $addrPt = Utils::oneLineAddress(
                    $valuePt->unit_number,
                    $valuePt->street_number,
                    $valuePt->street_name,
                    $valuePt->street_type,
                    $valuePt->street_direction,
                    $valuePt->city,
                    $valuePt->province,
                    $valuePt->postal_code
                );
                $this->propertyAddresses[] = $addrPt;

                $propertyPid = $valuePt->pid;
                $propertyLegalDescription = $valuePt->legal;
                $propertyId = $valuePt->property_id;

                $tmp = explode(', ', $valuePt->title_holders);
                foreach($tmp as $valueT) {
                    $this->titleHolders[$valueT] = $valueT;
                }

                $alpineCompaniesTable = AlpineCompaniesTable::query()
                ->where('id', $company)
                ->get();

                foreach($alpineCompaniesTable as $keyAct => $valueAct) {
                    $companyName = $valueAct->name;
                }
                
                $quotePosition[] = [
                    'position' => $valuePt->position,
                    'address' => $addrPt,
                    'pid' => $propertyPid,
                    'legal' => $propertyLegalDescription,
                    'companyName' => $companyName,
                    'alpineInterest' => $valuePt->alpine_interest,
                    'guarantors' => $this->guarantors
                ];     

                //$this->getSavedQuotePositionsTable($propertyId,$valuePt->position,$section,$font,$fontBold);
            }
        }

        return $quotePosition;
    }

    public function getApplicantTable($applicationId) {

        $query = 'select A.applicant_id,A.home_phone,A.home_fax,A.home_email,A.marital_status,A.marital_how_long,A.children,A.ages,A.credit_bureau_recvd,
                    B.spouse_id AS spouse1_A,B.gender AS gender_A, B.l_name AS l_name_A,B.f_name AS f_name_A,B.m_name AS m_name_A,B.p_name AS p_name_A,
                    B.dob AS dob_A, B.sin AS sin_A, B.type AS type_A, B.beacon_score AS beacon_score_A, B.tu_score AS tu_score_A, B.main_contact AS main_contact_A, B.relation AS relation_A,	   
                    C.spouse_id AS spouse1_B,C.gender AS gender_B, C.l_name AS l_name_B,C.f_name AS f_name_B,C.m_name AS m_name_B,C.p_name AS p_name_B,
                    C.dob AS dob_B, C.sin AS sin_B, C.type AS type_B, C.beacon_score AS beacon_score_B, C.tu_score AS tu_score_B, C.main_contact AS main_contact_B, C.relation AS relation_B   
                  from applicant_table A 
                  left join spouse_table B on B.spouse_id = A.spouse1_id
                  left join spouse_table C on C.spouse_id = A.spouse2_id
                  where A.application_id = ?';

        $applicantTable = $this->db->select($query, [$applicationId]);


        $applicants = array();
        $applicants[0] = array(); //applicants
        $applicants[1] = array(); //guarantors

        foreach($applicantTable as $keyAt => $valueAt) {
            if(strcmp($valueAt->f_name_A, "") != 0) {
                if (strcmp($valueAt->type_A, "Guarantor") == 0) {
                    $applicants[1][] = $valueAt->f_name_A . " " . $valueAt->m_name_A . " " . $valueAt->l_name_A;
                } else if (strcmp($valueAt->type_A, "Power of Attorney") == 0) {
                    $applicants[2][] = $valueAt->f_name_A . " " . $valueAt->m_name_A . " " . $valueAt->l_name_A;
                } else if (strcmp($valueAt->type_A, "Not Co-Applicant") != 0) {
                    $applicants[0][] = $valueAt->f_name_A . " " . $valueAt->m_name_A . " " . $valueAt->l_name_A;
                }
            }

            if(strcmp($valueAt->f_name_B, "") != 0) {
                if (strcmp($valueAt->type_B, "Guarantor") == 0) {
                    $applicants[1][] = $valueAt->f_name_B . " " . $valueAt->m_name_B . " " . $valueAt->l_name_B;
                } else if (strcmp($valueAt->type_B, "Power of Attorney") == 0) {
                    $applicants[2][] = $valueAt->f_name_B . " " . $valueAt->m_name_B . " " . $valueAt->l_name_B;
                } else if (strcmp($valueAt->type_B, "Not Co-Applicant") != 0) {
                    $applicants[0][] = $valueAt->f_name_B . " " . $valueAt->m_name_B . " " . $valueAt->l_name_B;
                }
            }
        }

        if(sizeof($applicants[1]) > 0) {
            $this->guarantors = $this->printGuarantors($applicants[1]);
        } else {
            $this->guarantors = "N/A";
        }

        if (isset($applicants[1])) {
            if (count($applicants[1]) > 0) {
                foreach ($applicants[1] as $keyA => $applName) {
                    $this->witnesses[] = $applName . ' (Guarantor)';
                }
            }
        }

        if (isset($applicants[2])) {
            if (count($applicants[2]) > 0) {
                foreach ($applicants[2] as $keyA => $applName) {
                    $this->witnesses[] = $applName . ' (Power of Attorney)';
                }
            }
        }
    }

    public function titleHolders($applicationId, $th) {

        $titH = array();

        if($th != '') {
            $th_array = explode(",", $th);

            foreach($th_array as $i => $v) {
    
                $th_array[$i] = trim($th_array[$i]);
    
                $skip = false;
    
                $hold_name = explode(' ', $th_array[$i]);
    
                $fNameTmp = '';
                $mNameTmp = '';
                $lNameTmp = '';
    
                $query = 'select a.application_id, a.applicant_id,
                                 b.spouse_id, b.f_name, b.m_name, b.l_name, b.type
                            from applicant_table a
                            join spouse_table b on b.spouse_id = a.spouse1_id or b.spouse_id = a.spouse2_id 
                            where a.application_id = ?';
    
                if(count($hold_name) == 1) {
    
                    $fNameTmp = $hold_name[0];
                    $mNameTmp = '';
                    $lNameTmp = '';
    
                    $query .= ' and b.f_name = ?';
                    $thAux = $this->db->select($query, [$applicationId, $fNameTmp]);
                
                } elseif(count($hold_name) == 2) {
                    $fNameTmp = $hold_name[0];
                    $mNameTmp = '';
                    $lNameTmp = $hold_name[1];
    
                    $query .= ' and b.f_name = ?
                                and b.l_name = ?';
                    $thAux = $this->db->select($query, [$applicationId, $fNameTmp, $lNameTmp]);

                } elseif(count($hold_name) == 3) {
                    $fNameTmp = $hold_name[0];
                    $mNameTmp = $hold_name[1];
                    $lNameTmp = $hold_name[2];
    
                    $query .= ' and b.f_name = ?
                                and b.l_name = ?
                                and b.m_name = ?';
                    $thAux = $this->db->select($query, [$applicationId, $fNameTmp, $lNameTmp, $mNameTmp]);
                }
    
                $mName = '';
                if(isset($thAux)) {
                    foreach ($thAux as $keyTh => $valueTh) {
                        $mName = $valueTh->m_name;
                        if ($valueTh->type == 'Not a co-applicant') {
                            $skip = true;
                        }
                    }
                }
    
                if(!$skip) {
                    $full_name = $th_array[$i];
    
                    if ($mName != "" && $mName != null && isset($mName) && $mName != 'undefined') {
                        if (count($hold_name) == 2) {
                            $full_name = $hold_name[0] . " " . $mName . " " . $hold_name[1];
                        }
                    }
    
                    $titH[$full_name] = $full_name; //unique entries
                }
            }
        }

        return $titH;
    }
    
    public function getMailingTable($applicationId) {

        $mailingTable = MailingTable::query()
        ->where('application_id', $applicationId)
        ->get();

        foreach($mailingTable as $keyMt => $valueMt) {
            if($valueMt->other == "") {
                $address = $this->formatAddress(
                    $valueMt->unit_number,
                    $valueMt->street_number,
                    $valueMt->street_name,
                    $valueMt->street_type,
                    $valueMt->street_direction,
                    $valueMt->city,
                    $valueMt->province,
                    $valueMt->postal_code,
                    $valueMt->box_number,
                    $valueMt->station,
                    $valueMt->rr_number,
                    $valueMt->site,
                    $valueMt->compartment
                );
                $this->propertyAddresses[] = $address;
            } else {
                $this->propertyAddresses[] = $valueMt->other;
            }
        }
    }

    public function formatAddress(

        $unitNumber,
        $streetNumber,
        $streetName,
        $streetType,
        $direction,
        $city,
        $province,
        $postalCode,
        $poBoxNumber,
        $station,
        $ruralRoute,
        $site,
        $compartment) {

        $address = "";

        if (strcmp($unitNumber, "") != 0)
            $address .= $unitNumber . "-";

        //street address
        $address .= $streetNumber . " " . $streetName . " " . $streetType;

        //optional street direction
        if (strcmp($direction, "N/A") != 0)
            $address .= " " . $direction;

        //end of first line
        $address .= "\n";

        //optional po box
        if (strcmp($poBoxNumber, "") != 0) {
            $address .= "PO Box " . $poBoxNumber . " ";

            if (strcmp($station, "") != 0) {
                $address .= "STN " . $station;
            }

            $address .= "\n";
        }

        //optional site compartment
        if (strcmp($site, "") != 0) {
            $address .= "SITE " . $site . " COMPARTMENT " . $compartment;
            $address .= "\n";
        }

        //optional rural route
        if (strcmp($ruralRoute, "") != 0) {
            $address .= "RR " . $ruralRoute . " ";

            if (strcmp($station, "") != 0) {
                $address .= "STN " . $station;
            }

            $address .= "\n";
        }

        //city province postal code
        $address .= $city . " " . $province . "  " . $postalCode;

        return $address;
    }
    
    public function printGuarantors($guarantors) {
        $str = "";
        foreach($guarantors as $i => $guarantor) {
            $str .= $guarantor . ", ";
        }

        return substr($str, 0, -2);
    }    

    public function getPropertyArrears($savedQuoteId) {

        $query = "select c.property_id, c.part_of_security, c.ppty_tax_arrears, d.arrears, c.ins_arrears, c.strata_arrears,
                         c.unit_number, c.street_number, c.street_name, c.street_type, c.street_direction, c.city,
                         c.province, c.postal_code
                    from saved_quote_positions_table a
                    join saved_quote_table b on a.saved_quote_id = b.saved_quote_id
                    join property_table c on c.application_id = b.application_id and a.idx = c.idx
               left join property_mortgages_table d on c.property_id = d.property_id
                   where a.saved_quote_id = ?
                group by c.property_id, c.part_of_security, c.ppty_tax_arrears, d.arrears, c.ins_arrears, c.strata_arrears,
                         c.unit_number, c.street_number, c.street_name, c.street_type, c.street_direction, c.city,
                         c.province, c.postal_code
                order by a.idx";
        $res = $this->db->select($query,[$savedQuoteId]);

        $properties = array();
        foreach($res as $key => $value) {

            if($value->part_of_security == 'No') {
                continue;
            }
            
            $address = Utils::oneLineAddress(
                $value->unit_number,
                $value->street_number,
                $value->street_name,
                $value->street_type,
                $value->street_direction,
                $value->city,
                $value->province,
                $value->postal_code);

            //Property Taxes    
            $propertyArreas = null;
            if(!empty($value->ppty_tax_arrears)) {
                if(strtoupper($value->ppty_tax_arrears) == 'UTD') {
                    $propertyArreas = 'UTD';
                } else {
                    $propertyArreasAux = str_replace(',','',$value->ppty_tax_arrears);

                    try {
                        $this->netAmount -= $propertyArreasAux;
                        $propertyArreas += $propertyArreasAux;
                    } catch(\Throwable $e) {
                        //$propertyArreas = 0;
                    }
                }
            }

            //Mortgage Arrears;
            $mortgageArreas = null;
            if(!empty($value->arrears)) {
                if (strtoupper($value->arrears) == 'UTD') {
                    $mortgageArreas = 'UTD';
                } else {
                    $mortgageArreasAux = str_replace(',','',$value->arrears);

                    try {
                        $this->netAmount -= $mortgageArreasAux;
                        $mortgageArreas += $mortgageArreasAux;
                    } catch(\Throwable $e) {
                        //$mortgageArreas = 0;
                    }
                }

            }

            //House Insurance Payable
            $insuranceArreas = null;
            if(!empty($value->ins_arrears)) {
                if (strtoupper($value->ins_arrears) == 'UTD') {
                    $insuranceArreas = 'UTD';
                } else {
                    $insuranceArreasAux = str_replace(',','',$value->ins_arrears);

                    try {
                        $this->netAmount -= $insuranceArreasAux;
                        $insuranceArreas += $insuranceArreasAux;
                    } catch(\Throwable $e) {
                        //$insuranceArreas = 0;
                    }
                }
            }

            //Strata Arrears
            $strataArreas = null;
            if (strtoupper($value->strata_arrears) == 'UTD' || empty($value->strata_arrears)) {
                $strataArreas = 'UTD';
            } else {
                $strataArreasAux = str_replace(',','',$value->strata_arrears);

                try {
                    $this->netAmount -= $strataArreasAux;
                    $strataArreas += $strataArreasAux;
                } catch(\Throwable $e) {
                    //$strataArreas = 0;
                }
            }            

            if (is_null($propertyArreas)) {
                $propertyArreas = 0.00;
            }

            if (is_null($mortgageArreas)) {
                $mortgageArreas = 0.00;
            }

            if (is_null($insuranceArreas)) {
                $insuranceArreas = 0.00;
            }

            if (is_null($strataArreas)) {
                $strataArreas = 0.00;
            }

            if(isset($properties[$value->property_id])) {
                if (!is_null($mortgageArreas)) {
                    try {
                        $properties[$value->property_id]['mortgageArreas'] += $mortgageArreas;
                    } catch(\Throwable $e) {}
                }                
            } else {
                $properties[$value->property_id] = [
                    'id' => $value->property_id,
                    'address' => $address,
                    'propertyArreas' => $propertyArreas,
                    'mortgageArreas' => $mortgageArreas,
                    'insuranceArreas' => $insuranceArreas,
                    'strataArreas' => $strataArreas
                ];
            }
        }

        return array_values($properties);
    }

    public function getPayouts($applicationId) {

        $totalBalance = 0;
        $payouts = array();

        $mortgageTable = MortgageTable::query()
        ->where('application_id', $applicationId)
        ->where('is_deleted', 'no')
        ->where('current_balance', '>', 0)
        ->where('tobe_paidout', 'yes')
        ->orderBy('client_auth_date')
        ->get();

        foreach ($mortgageTable as $key => $value) {
            
            if ($value->ab_loan != 'c_mtg' && $value->ab_loan != 'c_inv') {
                $mortgagePropertiesTable = MortgagePropertiesTable::query()
                ->where('mortgage_id', $value->mortgage_id)
                ->where('position', '<>', 'N/A')
                ->get();

                $positions = array();
                foreach ($mortgagePropertiesTable as $row) {
                    $positions[] = $row->position;
                }

                $totalBalance += $value->current_balance;

                $payouts[] = [
                    'mortgageId' => $value->mortgage_id,
                    'currentBalance' => $value->current_balance,
                    'position' => implode('/', $positions),
                ];
            }
        }

        $this->netAmount -= $totalBalance;

        return $payouts;
    }


    public function getLiabilities($applicationId) {

        $liabilityTable = LiabilityTable::query()
        ->where('application_id', $applicationId)
        ->get();

        $liabilities = array();

        foreach ($liabilityTable as $key => $value) {
            if (!empty($value->lender)) {
                if ($value->payout == 'By Alpine' || $value->payout == 'By AAR' || $value->payout == 'By GET' || $value->payout == 'By AOL') {
                    $liabilities[] = [
                        'liabilityId' => $value->liability_id,
                        'lenderName' => $value->lender,
                        'balance' => $value->balance,
                        'payout' => $value->payout,
                        'comment' => $value->comment
                    ];
                    $this->netAmount -= $value->balance;
                }
            }
        }

        return $liabilities;        
    }

    public function getPropertyMortgages($applicationId) {

        $propertyMortgages = array();

        $query = 'select b.* 
                  from property_table a 
                  join property_mortgages_table b on b.property_id = a.property_id 
                  where a.application_id = ?
                  order by a.idx, a.property_id, b.mortgage_id';
        $mortgagePropertiesTable = $this->db->select($query, [$applicationId]);

        foreach ($mortgagePropertiesTable as $key => $value) {

            $query = 'select f.firm_name, b.branch_name from lender_firm_table f, lender_firm_branches_table b 
            where b.lender_branch_code = ? 
            and f.lender_code = b.lender_code';
            $lenderFirm = $this->db->select($query, [$value->lender_id]);

            if ($value->setting == 'master' && ($value->payout == 'By Alpine' || $value->payout == 'By AAR' || $value->payout == 'By GET' || $value->payout == 'By AOL') ) {              

                $lenderName = $lenderFirm[0]->firm_name ?? '';

                $propertyMortgages[] = [
                    'lenderName' => $lenderName,
                    'balance' => $value->balance,
                ];

                $this->netAmount -= $value->balance;

            } elseif ($value->payout == 'Postponement') {

                $lenderName = ($lenderFirm[0]->firm_name ?? '') . ' (Postponement)';

                $propertyMortgages[] = [
                    'lenderName' => $lenderName,
                    'balance' => $value->balance,
                ];
            }
        }

        return $propertyMortgages;
    }

}
