<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationTable;
use App\Amur\Utilities\ConvertDate;
use App\Amur\Utilities\Utils;
use App\Models\IlaTable;
use App\Models\SalesforceIntegration;
use App\Models\CategoryValue;
use App\Models\SavedQuoteTable;

class ApplicationBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDataByApplicationId($applicationId) {
        $sql = "SELECT a.date, a.purpose_category_id, a.licenseChecked, cv.name as categoryName, a.purpose, a.amt_required, 
                       a.new_application_id, a.old_application_id,
                       a.company, d.name company_name, d.abbr, i.salesforce_id,
                       a.branch, a.agent, CONCAT(b.user_fname, ' ', b.user_lname) AS agent_name,
                       a.signing_agent, CONCAT(c.user_fname, ' ', c.user_lname) AS signing_agent_name,
                       a.solicitation_campaign_id, a.bdm_id,
                       concat(k.user_fname, ' ', k.user_lname) AS bdm_name,
                       a.broker_id,
                       l.name AS broker_name,
                       a.source, e.source AS sourceName,
                       a.source2, f.source AS source2Name,
                       a.initial_source,
                       g.source AS initialSourceName,
                       a.insuranceStatus, a.insuranceAmt, a.insuranceComm, a.ila,
                       h.firm_name AS ila_name,
                       a.solicit, a.presell, a.signed_date, a.funding_date, a.signing_datetime,
                       a.uw_asst_id,
                       concat(j.user_fname, ' ', j.user_lname) AS uw_asst_name,
                       a.broker_office_id,
                       m.name AS broker_office_name, 
                       a.national_broker_id,
                       n.name AS national_broker_name,
                       a.cmos_number, a.cmos_name, 
                       a.lender_id,
                       o.firm_name AS lender_name,
                       m.cps,
                       a.decision_timeline,
                       a.business_channel_id
                FROM application_table a
                LEFT JOIN users_table b ON a.agent = b.user_id
                LEFT JOIN users_table c ON a.signing_agent = c.user_id
                JOIN alpine_companies_table d ON a.company = d.id
                LEFT JOIN sources_table e ON a.source = e.id
                LEFT JOIN sources_table f ON a.source2 = f.id
                LEFT JOIN sources_table g ON a.initial_source = g.id
                LEFT JOIN ila_table h ON a.ila = h.ila_code
                LEFT JOIN salesforce_integration i ON a.application_id = i.object_id AND i.object = 'application_table'
                LEFT JOIN category_value cv on cv.id = a.purpose_category_id
                LEFT JOIN users_table j on j.user_id = a.uw_asst_id
                LEFT JOIN users_table k on k.user_id = a.bdm_id
                LEFT JOIN broker l on l.broker_id = a.broker_id
                LEFT JOIN broker_office m on m.broker_office_id = a.broker_office_id
                LEFT JOIN national_broker n on n.national_broker_id = a.national_broker_id
                LEFT JOIN lender_firm_table o on o.lender_code = a.lender_id
                WHERE a.application_id = ?";    
        $res = $this->db->select($sql, [$applicationId]);
    
        if (count($res) == 0) {
            return [];
        }
    
        $appObj = $res[0];
    
        // Initialize Salesforce ID variables
        $salesforceIdOld = null;
        $salesforceIdNew = null;
    
        // Fetch Salesforce ID for old application if it exists
        if (!empty($appObj->old_application_id) && $appObj->old_application_id != 0) {
            $sqlSalesforceOld = "SELECT salesforce_id FROM salesforce_integration WHERE object_id = ? AND object = 'application_table'";
            $oldResult = $this->db->select($sqlSalesforceOld, [$appObj->old_application_id]);
            $salesforceIdOld = !empty($oldResult) ? $oldResult[0]->salesforce_id : null;
        }
    
        // Fetch Salesforce ID for new application if it exists
        if (!empty($appObj->new_application_id) && $appObj->new_application_id != 0) {
            $sqlSalesforceNew = "SELECT salesforce_id FROM salesforce_integration WHERE object_id = ? AND object = 'application_table'";
            $newResult = $this->db->select($sqlSalesforceNew, [$appObj->new_application_id]);
            $salesforceIdNew = !empty($newResult) ? $newResult[0]->salesforce_id : null;
        }
    

        if ($appObj->company == 701) {
            $businessChannelId = $appObj->business_channel_id;
            if ($businessChannelId == null || $businessChannelId == 0) {
                $businessChannelId = 560; 
            }
        }else {
            $businessChannelId = null;
        }

        return [
            'id' => $applicationId,
            'newApplicationId' => $appObj->new_application_id,
            'oldApplicationId' => $appObj->old_application_id,
            'salesforceIdOld' => $salesforceIdOld,
            'salesforceIdNew' => $salesforceIdNew,
            'date' => $appObj->date,
            'purposeId' => $appObj->purpose_category_id,
            'purposeDetail' => html_entity_decode($appObj->purpose),
            'amountRequested' => $appObj->amt_required,
            'companyId' => $appObj->company,
            'companyName' => $appObj->company_name,
            'branch' => $appObj->branch,
            'agent' => $appObj->agent,
            'agentName' => $appObj->agent_name,
            'signingAgent' => $appObj->signing_agent,
            'signingAgentName' => $appObj->signing_agent_name,
            'solicitationCampaignId' => $appObj->solicitation_campaign_id,
            'bdmId' => $appObj->bdm_id,
            'bdmName' => $appObj->bdm_name,
            'brokerId' => $appObj->broker_id,
            'brokerName' => $appObj->broker_name,
            'source1' => $appObj->source,
            'source2' => $appObj->source2,
            'initialSource' => $appObj->initial_source,
            'sourceName' => $appObj->sourceName,
            'source2Name' => $appObj->source2Name,
            'initialSourceName' => $appObj->initialSourceName,
            'insuranceStatus' => $appObj->insuranceStatus,
            'insuranceAmt' => $appObj->insuranceAmt,
            'insuranceComm' => $appObj->insuranceComm,
            'ila' => $appObj->ila,
            'ilaName' => $appObj->ila_name,
            'companyAbbr' => $appObj->abbr,
            'solicit' => $appObj->solicit,
            'presell' => $appObj->presell,
            'signedDate' => $appObj->signed_date,
            'fundingDate' => $appObj->funding_date,
            'signingDatetime' => $appObj->signing_datetime,
            'categoryName' => $appObj->categoryName,
            'uwAsstId' => $appObj->uw_asst_id,
            'uwAsstName' => $appObj->uw_asst_name,
            'brokerOfficeId' => $appObj->broker_office_id,
            'brokerOfficeName' => $appObj->broker_office_name,
            'nationalBrokerId' => $appObj->national_broker_id,
            'nationalBrokerName' => $appObj->national_broker_name,
            'cmosNumber' => $appObj->cmos_number,
            'cmosName' => $appObj->cmos_name,
            'lenderId' => $appObj->lender_id,
            'lenderName' => $appObj->lender_name,
            'licenseChecked' => $appObj->licenseChecked ?? "no",
            'cps' => $appObj->cps,
            'urgency' => Utils::convertDecisionTimelineId($appObj->decision_timeline),
            'urgencyValue' => $appObj->decision_timeline,
            'businessChannelId' => $businessChannelId,
            'businessChannel' => Utils::convertCategoryValue($businessChannelId, 56)
        ];
    }
    
    public function updateByApplicationId($id = null, $data = [], $employment) {
        try {
            if( $id == null ) return false;

            $source1 = $data['source'];
            $source2 = $data['source2'];
            
            if( $source1['id'] == 'undefined' || $source1['id'] == null || $source1['id'] == '' ) {
                $source1 = "";
            } else { 
                if( $source1['text'] == "" ) {
                    $source1 = "";
                } else {
                    $source1 = $source1['id'];
                }
            }

            if( $source2['id'] == 'undefined' || $source2['id'] == null || $source2['id'] == '' ) {
                $source2 = "";
            } else { 
                if( $source2['text'] = "" ) {
                    $source2 = "";
                } else {
                    $source2 = $source2['id'];
                }
            }

            $purposeDetail = "";
            if( $data['purposeDetail'] != "" ) {
                $purposeDetail = htmlentities($data['purposeDetail']);
            }

            $applicationData = [
                "amt_required" => floatval($data['amountRequested']),
                "purpose" => $purposeDetail,
                "purpose_category_id" => $data['purpose'],
                "signing_agent" => $data['signingAgent'],
                "solicitation_campaign_id" => $data['solicitationCampaign'],
                "source" => $source1,
                "source2" => $source2,
                "broker_id" => $data['broker'],
                "bdm_id" => $data['bdm'],
                "company" => $data['company'],
                "branch" => $data["branch"],
                "agent" => $data['agent'],
                "insuranceAmt" => isset($employment['insurance']) ? $employment['insurance']['amount'] : 0,
                "insuranceStatus" => isset($employment['insurance']) ? $employment['insurance']['status'] : "",
                "insuranceComm" => isset($employment['insurance']) ? $employment['insurance']['comments'] : ""
            ];
            
            $applicationDate = ConvertDate::convert($data['date']);

            if( isset($data['date']) ) {
                if( $applicationDate ) {
                    $applicationData['date'] = $applicationDate;
                } else {
                    $applicationData['date'] = date("Y-m-d");
                }
            } else {
                $applicationData['date'] = date("Y-m-d");
            }

            ApplicationTable::query()
            ->where("application_id", $id)
            ->update($applicationData);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('ApplicationBO->updateByApplicationId - Update Error', [json_encode($e)]);
            return false;
        }
    }

    public function calLtv($applicationId, $savedQuoteId = 0) {
        //$this->logger->info('ApplicationBO->calLtv',[$applicationId, $savedQuoteId]);

        $props = [];
        if($savedQuoteId > 0) {
            $savedQuoteTable = SavedQuoteTable::query()
            ->where('saved_quote_id', $savedQuoteId)
            ->first();    
            
            if ($savedQuoteTable) {
                $props = explode(',', $savedQuoteTable->props);
            }
        }

        $query = "SELECT property_id, appraised_value, assessed_value, customer_value, alpine_interest, idx, estimate_value
                    FROM property_table
                   WHERE alpine_interest > 0
                     AND part_of_security = 'Yes'
                     AND application_id = ?";
        $propertyArray = $this->db->select($query,[$applicationId]);

        $propertyValueTotal = 0;
        $mortgageValueTotal = 0;

        foreach($propertyArray as $propertyRow) {

            $propertyValue = 0;
            if(count($props) > 0) {

                if(!empty($propertyRow->appraised_value)) {
                    $propertyValue = $propertyRow->appraised_value;
                } else {
                    foreach($props as $prop) {

                        $idxAux = trim(preg_replace('/\D/', '', $prop));
                        $propAux = trim(preg_replace('/\d/', '', $prop));

                        if ($idxAux == $propertyRow->idx) {
                            if ($propAux == 'a') { //Appraisal
                                $propertyValue = $propertyRow->appraised_value;

                            } elseif ($propAux == 'b') { //Assessed
                                $propertyValue = $propertyRow->assessed_value;

                            } elseif ($propAux == 'c') { //Customer
                                $propertyValue = $propertyRow->customer_value;

                            } elseif ($propAux == 'd') { //Estimate
                                $propertyValue = $propertyRow->estimate_value;
                            }

                            break;
                        }
                    }
                }
            } else {
                if (!empty($propertyRow->estimate_value)) {
                    $propertyValue = $propertyRow->estimate_value;
                }
                if (!empty($propertyRow->customer_value)) {
                    $propertyValue = $propertyRow->customer_value;
                }
                if (!empty($propertyRow->assessed_value)) {
                    $propertyValue = $propertyRow->assessed_value;
                }            
                if (!empty($propertyRow->appraised_value)) {
                    $propertyValue = $propertyRow->appraised_value;
                }
            }

            $propertyValue = $propertyValue * $propertyRow->alpine_interest / 100;
            $propertyValueTotal += $propertyValue;

            $query = "SELECT mortgage_id, property_id, balance, balance_date, rate, lender_id
                        FROM property_mortgages_table
                       WHERE balance > 0
                         AND payout = 'No'
                         AND setting != 'dependent'
                         AND property_id = ?
                    ORDER BY mortgage_id ASC";
            $propertyMortgageArray = $this->db->select($query,[$propertyRow->property_id]);

            foreach($propertyMortgageArray as $propertyMortgageRow) {
                $mortgageValueTotal += $propertyMortgageRow->balance;
            }
            $mortgageValueTotal = $mortgageValueTotal * $propertyRow->alpine_interest / 100;
        }

        $query = "SELECT a.current_balance
                    FROM mortgage_table a
                    join mortgage_properties_table b on a.mortgage_id = b.mortgage_id
                    join property_table c on b.property_id = c.property_id and c.part_of_security = 'Yes'
                   WHERE a.application_id = ?
                     AND a.is_deleted = 'no'
                     AND a.tobe_paidout = 'no'
                     AND a.current_balance > 0
                GROUP BY a.mortgage_id
                ORDER BY a.mortgage_id DESC";
        $mortgageArray = $this->db->select($query,[$applicationId]);

        foreach($mortgageArray as $mortgageRow) {
            $mortgageValueTotal += $mortgageRow->current_balance;
        }

        $query = "SELECT gross
                    FROM saved_quote_table
                   WHERE application_id = ?
                     AND disburse = 'Yes'
                     AND mortgage_id = 0
                ORDER BY saved_quote_id 
                DESC LIMIT 0,1";
        $quoteGross = $this->db->select($query,[$applicationId]);

        $gross = 0;
        if (isset($quoteGross[0]->gross)) {
            $gross = $quoteGross[0]->gross;
        }

        $apprLtv = $propertyValueTotal == 0 ? 0 : 100 * (($mortgageValueTotal + $gross) / $propertyValueTotal);

        return $apprLtv;
    }

    public function getApplicationTable($applicationId) {

        $applicationTable = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        return $applicationTable;

    }

    public function index($applicationId) {
        $sql = "
            SELECT 
                CONCAT(b.user_fname, ' ', b.user_lname) AS agent_name,
                CONCAT(c.user_fname, ' ', c.user_lname) AS signing_agent_name
            FROM 
                application_table a
            LEFT JOIN 
                users_table b ON a.agent = b.user_id
            LEFT JOIN 
                users_table c ON a.signing_agent = c.user_id
            WHERE 
                a.application_id = ?";
        $res = $this->db->select($sql, [$applicationId]);
    
        if(count($res) == 0) {
            $this->logger->warning('ApplicationBO->index - Application not found',[$applicationId]);
            return [];
        }
    
        $appObj = $res[0];
    
        return [
            'applicationId' => $applicationId,
            'agentName' => $appObj->agent_name,
            'signingAgentName' => $appObj->signing_agent_name
        ];
    }

    public function saveIla($applicationId, $ila) {

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if($sfi) {
                $applicationId = $sfi->object_id;
            }
        }

        if (!empty($ila)) {

            $ilaTable = IlaTable::query()
            ->where('ila_code', $ila)
            ->first();

            if(!$ilaTable) {
                return false;
            }
        }

        $applicationTable = ApplicationTable::find($applicationId);

        if($applicationTable) {
            $applicationTable->ila = $ila;
            $applicationTable->save();

            return true;
        } else {
            return false;
        }
    }

    public function store($application, $applicationId) {

        $this->logger->info('ApplicationBO->store',[$applicationId]);

        $this->db->beginTransaction();
        try {

            $applicationTable = ApplicationTable::find($applicationId);

            if ($applicationTable) {
                $applicationTable->solicit = $application['solicit'] ?? 'No';
                $applicationTable->presell = $application['presell'] ?? 'No';
                $applicationTable->signed_date = $application['signedDate'] ?? '0000-00-00';
                $applicationTable->funding_date = $application['fundingDate'] ?? '0000-00-00';
                $applicationTable->signing_datetime = $application['signingDatetime'] ?? '0000-00-00 00:00:00';
                $applicationTable->purpose_category_id = $application['purposeId'] ?? '';
                $applicationTable->amt_required = $application['amountRequested'] ?? 0;
                $applicationTable->purpose = $application['purposeDetail'] ?? '';
                $applicationTable->bdm_id = $application['bdmId'] ?? 0;
                $applicationTable->uw_asst_id = $application['uwAsstId'] ?? 0;
                $applicationTable->broker_id = $application['brokerId'] ?? 0;
                $applicationTable->broker_office_id = $application['brokerOfficeId'] ?? 0;
                $applicationTable->national_broker_id = $application['nationalBrokerId'] ?? 0;
                $applicationTable->cmos_number = $application['cmosNumber'] ?? null;
                $applicationTable->cmos_name = $application['cmosName'] ?? null;
                $applicationTable->lender_id = $application['lenderId'] ?? null;
                $applicationTable->licenseChecked = $application['licenseChecked'] ?? 'No';
                $applicationTable->source = $application['source1'] ?? 0;
                $applicationTable->source2 = $application['source2'] ?? 0;
                $applicationTable->date = $application['date'] ?? 0;
                $applicationTable->signing_agent = $application['signingAgent'] ?? 0;
                $applicationTable->agent = $application['agent'] ?? 0;
                $applicationTable->decision_timeline = $application['urgencyValue'] ?? null;
                $applicationTable->business_channel_id = $application['businessChannelId'] ?? null;
                $applicationTable->save();
            }

        } catch (\Throwable $e) {
            $this->logger->error('ApplicationBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function getpurposeDetailOptions() {

        $categoryValue = CategoryValue::query()
        ->where('category_id', 42)
        ->orderBy('position', 'ASC')
        ->get();

        $purposeDetailOptions = array();

        foreach ($categoryValue as $key => $value) {
            $purposeDetailOptions[] = [
                'id' => $value->id,
                'name' => $value->name
            ];            
        }

        return $purposeDetailOptions;
    }

    public function getCompanyByApplicationId($applicationId) {
        $applicationTable = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        if ($applicationTable) {
            return $applicationTable->company;
        } else {
            return null;
        }
    }

    public function getUrgencyOptions() {

        $categoryValue = CategoryValue::query()
        ->where('category_id', 57)
        ->where('live', 'Y')
        ->get();

        $urgencyOptions = [];

        foreach ($categoryValue as $key => $value) {
            $urgencyOptions[] = [
                'id' => $value->id,
                'name' => $value->name
            ];
        }

        return $urgencyOptions;
    }

    public function getBusinessChannelOptions() {

        $categoryValue = CategoryValue::query()
        ->where('category_id', 56)
        ->where('live', 'Y')
        ->orderBy('position', 'ASC')
        ->get();

        $businessChannelOptions = [];

        foreach ($categoryValue as $key => $value) {
            $businessChannelOptions[] = [
                'id' => $value->id,
                'name' => $value->name
            ];
        }

        return $businessChannelOptions;
    }


}