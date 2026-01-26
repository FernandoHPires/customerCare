<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationTable;
use App\Models\ApplicantTable;
use App\Models\SpouseTable;
use App\Models\PropertyTable;
use App\Models\PropertyMortgagesTable;
use App\Models\SavedQuoteTable;
use App\Models\SavedQuoteMissingChequesTable;

class DoubleCheckStuffBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDoubleCheck($applicationId) {

        $this->logger->info('DoubleCheckStuffBO->getDoubleCheck', [$applicationId]);

        $checks = array();

        $application = $this->getApplication($applicationId);

        if($application) {

            $applicants = $this->getApplicant($applicationId);
            $properties = $this->getProperties($applicationId);
            $savedQuoteTable = $this->getSavedQuoteTable($applicationId);

            if ($application->source == '' || $application->source == '0') {
                $checks[] = [
                    'name' => 'Source code',
                    'message' => 'Not entered',
                ];
            }

            foreach ($applicants as $applicant) {

                $spouse1 = $this->getSpouseTable($applicant->spouse1_id);
                $spouse2 = $this->getSpouseTable($applicant->spouse2_id);


                if(trim($applicant->credit_bureau_recvd) == "") {
                    $date = "bogus";
                }else {
                    $date = $applicant->credit_bureau_recvd;
                }

        
                $cbrDate = strtotime($date);
                $tdyDate = strtotime("now");
        
                $diff = $tdyDate - $cbrDate;
                $days = round(($diff/86400) - 1);

                $name = '';
                if ($spouse1) {
                    if ($spouse1->f_name != '' && $spouse1->l_name != '') {
                        $name = $spouse1->f_name . ' ' . $spouse1->l_name;
                    }
                }
                if ($spouse2) {
                    if ($spouse2->f_name != '' && $spouse2->l_name != '') {
                        $name .= ' & '. $spouse2->f_name . ' ' . $spouse2->l_name;
                    }
                }            
                
                if (empty($applicant->credit_bureau_recvd) || $applicant->credit_bureau_recvd == '0000-00-00') {
                    $checks[] = [
                        'name' => $name . " - Cr. Bureau",
                        'message' => 'Not received',
                    ];
                } else {   
                    if ($days > 30) {
                        $checks[] = [
                            'name' => $name . " - Cr. Bureau",
                            'message' => "Older than 30 days ({$days} days old)",
                        ];
                    }
                }    

                if ((empty($spouse1->dob) || $spouse1->dob == '0000-00-00') && $spouse1->f_name != '') {
                    $checks[] = [
                        'name' => $spouse1->f_name . ' ' .$spouse1->l_name,
                        'message' => 'Birth date not entered',
                    ];
                }
    
                if (empty($spouse1->sin) && $spouse1->f_name != '') {
                    $checks[] = [
                        'name' => $spouse1->f_name. ' ' .$spouse1->l_name,
                        'message' => 'SIN not entered',
                    ];
                }   
    
                if ($spouse2) {
                    if ((empty($spouse2->dob) || $spouse2->dob == '0000-00-00') && $spouse2->f_name != '') {
                        $checks[] = [
                            'name' => $spouse2->f_name.' '.$spouse2->l_name,
                            'message' => 'Birth date not entered',
                        ];
                    }
                    if (empty($spouse2->sin)  && $spouse2->f_name != '') {
                        $checks[] = [
                            'name' => $spouse2->f_name.' '.$spouse2->l_name,
                            'message' => 'SIN not entered',
                        ];
                    }
                }

                if (empty($applicant->home_email)) {
                    $checks[] = [
                        'name' => $name,
                        'message' => 'Email not entered',
                    ];
                }
            }

            foreach ($properties as $property) {

                $addr = trim("{$property->unit_number} {$property->street_number} {$property->street_name} {$property->street_type} {$property->street_direction}, {$property->city}, {$property->province} {$property->postal_code}");
            
                if ($property->part_of_security == "No") {
                    continue;
                }            
                
                if (!empty($property->ppty_tax_arrears) && $property->ppty_tax_arrears != 'UTD') {
                    $checks[] = [
                        'name' => "Ppty Tax Arrears - $addr",
                        'message' => $property->ppty_tax_arrears,
                    ];
                }
            
                if (!empty($property->ins_arrears) && $property->ins_arrears != 'UTD') {
                    $checks[] = [
                        'name' => "Insurance Arrears - $addr",
                        'message' => $property->ins_arrears,
                    ];
                }
            
                if ($property->strata_arrears > 0) {
                    $checks[] = [
                        'name' => "Strata Arrears - $addr",
                        'message' => $property->strata_arrears,
                    ];
                }
            
                if ($property->value_method == "Appraisal") {

                    $date = $property->appraisal_date_ordered;
                    if(trim($date) == "") {
                        $date = "bogus";
                    }


                    $appr_date = strtotime($date);
                    $tdy_date = strtotime("now");
        
                    $diff = $tdy_date - $appr_date;
                    $days = ($diff/86400) - 1;
        
                    $ordered = '';
                    if($appr_date == -1) {
                        $ordered = "Appraisal not ordered";
                    } else if($days > 90) {
                        $ordered = "Appraisal older than 90 days (".round($days)." days old)";
                    }

                    if($ordered != "") {
                        $checks[] = [
                            'name' => $addr,
                            'message' => $ordered,
                        ];                        
                    }elseif ($property->appraisal_firm_id == '') {
                        $checks[] = [
                            'name' => $addr,
                            'message' => "Appraisal Firm code not entered",
                        ];
                    }
            
                    if ($property->appraisal_recvd == 'No') {
                        $checks[] = [
                            'name' => $addr,
                            'message' => "Appraisal not received",
                        ];
                    }elseif (empty($property->appraised_value) || $property->appraised_value == '0.00') {
                        $checks[] = [
                            'name' => $addr,
                            'message' => "Appraisal value not entered",
                        ];
                    }
                }
            
                if (empty($property->ins_broker_code) || $property->ins_broker_code == '0') {
                    $checks[] = [
                        'name' => "Insurance - $addr",
                        'message' => "Insurance Broker code not entered",
                    ];
                }
            
                if (empty($property->ins_expiry) || $property->ins_expiry == '0') {
                    $checks[] = [
                        'name' => "Insurance - $addr",
                        'message' => "Insurance Expiry Date not entered",
                    ];
                }
                
                $mortgageBalance = $this->getMortgageBalance($property->property_id);

                $mNum = 0;

                foreach ($mortgageBalance as $key => $mortgage) {
                    
                    $mNum++;

                    if (empty($mortgage->mort_balance) || $mortgage->mort_balance == '0.00') {
                        continue;
                    }

                    if ($mortgage->lender_id == '' || $mortgage->lender_id == '0') {
                        $checks[] = [
                            'name' => $addr,
                            'message' => 'Mort # '. $mNum .' Lender code not entered',
                        ];
                    }

                    if ($mortgage->payout != 'By Alpine') {
                        if ($mortgage->confirmed == 'No') {
                            $checks[] = [
                                'name' => $addr,
                                'message' => 'Mort # '. $mNum .' not confirmed',
                            ];
                        }else {
                            $mortNotEntered = '';
                            if (empty($mortgage->balance) || $mortgage->balance == '0.00') {
                                $mortNotEntered .= 'balance, ';
                            }
                            if (empty($mortgage->payment) || $mortgage->payment == '0.00') {
                                $mortNotEntered .= 'payment, ';
                            }
                            if (empty($mortgage->term) || strlen($mortgage->term) == 4) {
                                $mortNotEntered .= 'term, ';
                            }
                            if (empty($mortgage->rate) || $mortgage->rate == '0%') {
                                $mortNotEntered .= 'rate, ';
                            }
                            if (empty($mortgage->arrears)) {
                                $mortNotEntered .= 'arrears, ';
                            }

                            if($mortNotEntered != '') {
                                $checks[] = [
                                    'name' => $addr,
                                    'message' => 'Mort # '. $mNum .' confirmed, but the following may be blank or incorrectly entered '. $mortNotEntered,
                                ];
                            }
                        }
                        
                    }
                }

                if ($property->unit_type != 'N/A' && empty($property->strata_arrears)) {
                    $checks[] = [
                        'name' => "Strata - $addr",
                        'message' => "Strata Arrears not entered",
                    ];
                }
            }

            $quoteNum = 0;
            foreach ($savedQuoteTable as $quote) {
                
                $quoteNum++;

                $getMissingCheques = $this->getMissingCheques($quote->saved_quote_id);
            
                if ($quote->disburse == 'No') {
                    continue;
                }            

                if ($quote->client_auth_date == '') {
                    $checks[] = [
                        'name' => "Quote #{$quoteNum} - Client Auth. Date",
                        'message' => "Not entered",
                    ];
                }
                if (empty($quote->int_comm_date)) {
                    $checks[] = [
                        'name' => "Quote #{$quoteNum} - Int. Comm. Date",
                        'message' => "Not entered",
                    ];
                }
                if (empty($quote->first_pmt_date)) {
                    $checks[] = [
                        'name' => "Quote #{$quoteNum} - 1st Payment Date",
                        'message' => "Not entered",
                    ];
                }
            

                if ((isset($quote->first_cheque_date) && $quote->first_cheque_date != '' && $quote->first_cheque_date != '0000-00-00') || (isset($quote->last_cheque_date) && $quote->last_cheque_date != '' && $quote->last_cheque_date != '0000-00-00')) {

                    $df = strtotime($quote->first_cheque_date);
                    $dl = strtotime($quote->last_cheque_date);

                    $missing = array();

                    foreach ($getMissingCheques as $missingCheque) {
                        $missing[] = $missingCheque->missing_cheque_date;
                    }

                    $cheques = array();
                    while($df <= $dl) {
                        if(!in_array($df, $missing)) {
                            $cheques[] = date('Y-m-d', $df);
                        }
        
                        $df = strtotime('+1 month', $df);
                    }

                    $numCheques = $quote->loan - sizeof($cheques);                    
                } else {
                    $numCheques = $quote->loan;

                }

                if($numCheques != 0) {
                    $checks[] = [
                        'name' => "Quote #{$quoteNum} - Cheques",
                        'message' => "Missing {$numCheques} cheques",
                    ];
                }                
            }
        }

        return $checks;
    }

    public function getApplication($applicationId) {

        $application = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        return $application;
    }

    public function getApplicant($applicationId) {

        $applicant = ApplicantTable::query()
        ->where('application_id', $applicationId)
        ->get();

        return $applicant;
    }

    public function getSpouseTable($spouseId) {

        $spouse = SpouseTable::query()
        ->where('spouse_id', $spouseId)
        ->first();

        return $spouse;
    }

    public function getProperties($applicationId) {

        $properties = PropertyTable::query()
        ->where('application_id', $applicationId)
        ->orderBy('idx')
        ->orderBy('property_id')
        ->get();

        return $properties;
        
    }

    public function getMortgageBalance($propertyId) {
        
        $mortgages = PropertyMortgagesTable::query()
        ->where('property_id', $propertyId)
        ->orderBy('mortgage_id')
        ->get();

        return $mortgages;
    }


    public function getSavedQuoteTable($applicationId) {

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('application_id', $applicationId)
        ->orderBy('saved_quote_id')
        ->get();       
        
        return $savedQuoteTable;
    }

    public function getMissingCheques($quoteId) {

        $missingCheques = SavedQuoteMissingChequesTable::query()
        ->where('saved_quote_id', $quoteId)
        ->get();

        return $missingCheques;
    }

}