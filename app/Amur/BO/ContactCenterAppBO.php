<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\BO\ApplicationBO;
use App\Amur\BO\AssetBO;
use App\Amur\BO\IncomeBO;
use App\Amur\BO\VehicleBO;
use App\Amur\BO\LiabilityBO;
use App\Amur\BO\PropertyBO;
use App\Amur\BO\ApplicantBO;
use App\Amur\BO\MailingBO;
use App\Amur\BO\SalesforceBO;
use App\Amur\BO\AppraisalFirmBO;
use App\Models\MortgagePropertiesTable;
use App\Models\IlaTable;
use App\Models\PropertyTable;
use App\Models\GroupMembersTable;
use DateTime;
use App\Amur\Utilities\Utils;
use App\Models\SalesJourney;
use App\Models\SalesforceIntegration;
use App\Amur\BO\SalesJourneyBO;

class ContactCenterAppBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }
    
    public function store($objects, $applicationId, $type) {

        $this->logger->info('ContactCenterAppBO->store',[$applicationId,$type]);

        $errors = array();

        if(in_array($type, ['all', 'application'])) {

            $applicationBO = new ApplicationBO($this->logger, $this->db);
            $res = $applicationBO->store($objects->application, $applicationId);
    
            if($res === false) {
                $errors[] = 'Error saving Application.<br>';
            }
        }

        if(in_array($type, ['all', 'applicants'])) {

            $applicantBO = new ApplicantBO($this->logger, $this->db);
            $res = $applicantBO->store($objects->applicants, $applicationId);
    
            if($res === false) {
                $errors[] = 'Error saving applicants.<br>';
            }
    
            $corporationBO = new CorporationBO($this->logger, $this->db);
            $res = $corporationBO->store($objects->corporations, $applicationId);
    
            if($res === false) {
                $errors[] = 'Error saving companies<br>';
            }
        }

        if(in_array($type, ['all', 'properties'])) {
            $errors = $this->validate($objects);
            if(count($errors) > 0) {
                return ['validation' => $errors];
            }

            $propertyBO = new PropertyBO($this->logger, $this->db);
            $res = $propertyBO->storeProperty($objects->properties, $applicationId);

            if($res === false) {
                $errors[] = 'Error saving properties<br>';
            }

            $mailingBO = new MailingBO($this->logger, $this->db);
            $res = $mailingBO->store($objects->mailings, $applicationId);
    
            if($res === false) {
                $errors[] = 'Error saving mailings<br>';
            }
        }

        if(in_array($type, ['all', 'vehicles'])) {
            $vehicleBO = new VehicleBO($this->logger);
            $res = $vehicleBO->updateByVehicleId($objects->vehicles, $applicationId);

            if($res === false) {
                $errors[] = 'Error saving vehicles<br>';
            }

            $liabilityBO = new LiabilityBO($this->logger);
            $res = $liabilityBO->updateByLiabilityId($objects->liabilities, $applicationId);

            if($res === false) {
                $errors[] = 'Error saving liabilities<br>';
            }
        }

        if(in_array($type, ['all', 'income'])) {
            $incomeBO = new IncomeBO($this->logger, $this->db);
            $res = $incomeBO->storeOtherIncome($objects->otherIncomes, $applicationId);

            if ($res === false) {
                $errors[] = 'Error saving other incomes<br>';
            }

            $incomeBO = new IncomeBO($this->logger, $this->db);
            $res = $incomeBO->storePreviousEmployerTable($objects->previousEmployments);

            if ($res === false) {
                $errors[] = 'Error saving previous employments<br>';
            }

            $incomeBO = new IncomeBO($this->logger, $this->db);
            $res = $incomeBO->storePresentEmployerTable($objects->presentEmployments);
            
            if ($res === false) {
                $errors[] = 'Error saving present employments<br>';
            }
            
            $assetsBO = new AssetBO($this->logger, $this->db);
            $res = $assetsBO->store($objects->assets, $applicationId);

            if ($res === false) {
                $errors[] = 'Error saving assets<br>';
            }
        }

        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $salesforceBO->syncApplication($applicationId);

        if(count($errors) > 0) {
            return $errors;
        }

        return true;
    }

    public function validate($objects) {

        $errors = [];
        
        foreach ($objects->properties as $property) {
            if (isset($property['appraisersFirmCode']) && $property['appraisersFirmCode'] > 0) {

                $appraisalFirmBO = new AppraisalFirmBO($this->logger, $this->db);
                $status = $appraisalFirmBO->statusValidation($property['appraisersFirmCode']);

                if ($status != 'Aproved') {
                    $errors[] = 'Appraisal Firm '.$property['appraisersFirmCode'].' is not approved!';
                }
            }
        }

        return $errors;
    }

    public function nearbyMortgages($applicationId, $propertyId, $postalCode, $city) {

        $this->logger->info('ContactCenterAppBO->nearbyMortgages',[$applicationId,$propertyId,$postalCode,$city]);

        $postalCode1    = array();
        $postalCode2    = array();
        $postalCode3    = array();
        $investorPayout = array();
        $propertiesData = array();
        $fullAddress    = $this->getFullAddress($propertyId);

        $startDate = (new DateTime())->modify('-1 year')->format('Y-m-d');
        $endDate = (new DateTime())->modify('+1 day')->format('Y-m-d');

        for ($i=0; $i < 4; $i++) { 
            if ($i == 0) {
                $type = 'postalCode';
                $filter = substr($postalCode, 0, 4);
                $postalCode1 = $this->getData($type, $filter, $startDate, $endDate);
            }elseif ($i == 1) {
                $type = 'postalCode';
                $filter = substr($postalCode, 0, 5);
                $postalCode2 = $this->getData($type, $filter, $startDate, $endDate);
            }elseif ($i == 2) {
                $type = 'postalCode';
                $filter = substr($postalCode, 0, 6);
                $postalCode3 = $this->getData($type, $filter, $startDate, $endDate);
            }elseif ($i == 3) {
                $type = 'city';
                $filter = $city;
                $investorPayout = $this->getData($type, $filter, $startDate, $endDate);
            }
        }

        

        $propertiesData = [
            'postalCode1' => $postalCode1,
            'postalCode2' => $postalCode2,
            'postalCode3' => $postalCode3,
            'investorPayout' => $investorPayout,
            'fullAddress' => $fullAddress
        ];

        return $propertiesData;


    }

    public function getData($type, $filter, $startDate, $endDate) {

        $postalCode1 = array();

        $query = 'SELECT *,1*appraised_value/assessed_value tmp1 
                FROM property_table p, application_table a 
                left join mortgage_table m on m.application_id=a.application_id 
                WHERE m.company_id in("301","401","1","3","601") 
                and m.client_auth_date > "' . $startDate . '" 
                and m.client_auth_date < "' . $endDate . '" 
                and m.is_deleted<>"yes" 
                and a.application_id = p.application_id';
        if ($type == 'postalCode') {
            $query .= ' AND p.postal_code LIKE "' . $filter . '%" ';
        } elseif ($type == 'city') {
            $query .= ' AND p.city = "' . $filter . '" ';
        }
        $query .= 'limit 20';
                
        $res = $this->db->query($query);

        foreach ($res as $key => $value) {

            if (isset($value->mortgage_code) && $value->mortgage_code != '' && $value->mortgage_code != null) {          

                $ilaTable = IlaTable::query()
                ->where('ila_code', $value->ila)
                ->first();

                if($ilaTable) {
                    $ila = $ilaTable->ila_code . ' ' . $ilaTable->firm_name . ' ' . $ilaTable->telephone;
                } else {
                    $ila = '';
                }

                $queryAppraiser = 'SELECT * FROM 
                                application_table a, 
                                property_table b, 
                                appraisal_firms_table c 
                                WHERE b.application_id = a.application_id 
                                and b.appraised_value > 0 
                                and b.appraisal_firm_id = c.appraisal_firm_code 
                                and a.application_id = ' . $value->application_id;
                $resAppraiser = $this->db->query($queryAppraiser);

                $appraiser = '';
                foreach ($resAppraiser as $rowAppraiser) {
                    $appraiser = $rowAppraiser->appraisal_firm_code . ' ' . $rowAppraiser->name . ' ' . $rowAppraiser->telephone;
                }

                $queryInvestor = 'SELECT * FROM mortgage_investor_tracking_table a,investor_table b 
                                where b.investor_id = a.investor_id 
                                and a.committed = "Yes"  
                                and a.mortgage_id = '.$value->mortgage_id;
                $resInvestor = $this->db->query($queryInvestor);

                $lender = '';
                foreach ($resInvestor as $rowInvestor) {
                    $lender = $rowInvestor->first_name . " " . $rowInvestor->last_name;  
                }

                $position = null;

                $mortgagePropertiesTable = MortgagePropertiesTable::query()
                ->where('mortgage_id', $value->mortgage_id)
                ->where('property_id', $value->property_id)
                ->first();
                
                if ($mortgagePropertiesTable) {
                    $position = $mortgagePropertiesTable->position;
                }

                $postalCode1[] = [
                    'applicationId' => $value->application_id,
                    'mortgageCode' => $value->mortgage_code,
                    'dateOrdered' => $value->client_auth_date,
                    'ltv' => $value->ltv,
                    'assessment' => $value->assessed_value,
                    'appraisal' => $value->appraised_value,
                    'appraisalAssessment' => $value->tmp1,
                    'mortgageAmount' => $value->net_amt,
                    'position' => $position,
                    'interestRate' => $value->interest_rate,
                    'postalCode' => $value->postal_code,
                    'city' => $value->city,
                    'ila' => $ila,
                    'yield' => $value->yield,
                    'appraiser' => $appraiser,
                    'lender' => $lender,
                ];

            }
        }

        return $postalCode1;

    }

    public function getFullAddress($propertyId) {

        $data = PropertyTable::query()
        ->where('property_id', $propertyId)
        ->first();

        $fullAddress = '';

        if ($data) {
            $fullAddress = Utils::oneLineAddress($data->unit_number, $data->street_number, $data->street_name, $data->street_type, $data->street_direction, $data->city, $data->province, $data->postal_code);
        }

        return $fullAddress;
    }

    public function getInvestorTracking($savedQuoteId, $applicationId) {

        $this->logger->info('ContactCenterAppBO->getInvestorTracking',[$savedQuoteId,$applicationId]);

        $investorsTrackingData = array();


        $query = "SELECT sale_investor_id, concat(first_name, ' ', b.last_name) investor_name, a.investor_id, a.lp_date, a.purchase_date,
                    fm_committed, gm_committed, a.discount, a.price, a.yield, a.comment, a.interest_rate, 
                    ap_inv_co,ap_priv_inv_id,ap_percent,ap_payment,ap_rate,ap_amount,ap_rate,ap_payment,ap_discount,ap_price,ap_yield,
                    bp_inv_co,bp_priv_inv_id,bp_percent,bp_payment,bp_rate,bp_amount,bp_rate,bp_payment,bp_discount,bp_price,bp_yield,
                    cp_inv_co,cp_priv_inv_id,cp_percent,cp_payment,cp_rate,cp_amount,cp_rate,cp_payment,cp_discount,cp_price,cp_yield,
                    e.evaluate_by, fm.user_fname fm_appr_user_fname, fm.user_lname fm_appr_user_lname,
                    gm.user_fname gm_appr_user_fname, gm.user_lname gm_appr_user_lname,
                    d.user_fname last_user_fname, d.user_lname last_user_lname, e.mortgage_id
                FROM sale_investor_table a
                join saved_quote_table e on a.saved_quote_id = e.saved_quote_id
            left join investor_table b on a.investor_id = b.investor_id
            left join users_table fm on fm.user_id = a.fm_approved_id
            left join users_table gm on gm.user_id = a.gm_approved_id
                join users_table d on d.user_id = a.last_updated_user_id
                where a.saved_quote_id = $savedQuoteId
            order by case a.investor_id when 31 then -4 when 248 then -3 when 100 then -2 when 1971 then -1 else a.investor_id end";
        $data = $this->db->query($query);
        
        foreach ($data as $key => $value) {
            $investorsTrackingData[] = [
                'lpDate' => $value->lp_date,
                'purchase' => $value->purchase_date,
                'investor' => $value->investor_name,
                'fmCommitted' => $value->fm_committed,
                'fundManager' => substr($value->fm_appr_user_fname, 0, 1) . substr($value->fm_appr_user_lname, 0, 1),
                'discount' => $value->discount,
                'price' => $value->price,
                'yield' => $value->yield,
                'comment' => $value->comment,
                'gmCommitted' => $value->gm_committed,
                'generalManager' => substr($value->gm_appr_user_fname, 0, 1) . substr($value->gm_appr_user_lname, 0, 1),
                'lastUpdate' => substr($value->last_user_fname, 0, 1) . substr($value->last_user_lname, 0, 1),
            ];
        }

        return $investorsTrackingData;

    }

    public function updatePartOfSecurity($propertyId, $partOfSecurity) {
        
        $this->logger->info('ContactCenterAppBO->updatePartOfSecurity',[$propertyId, $partOfSecurity]);

        $propertyTable = PropertyTable::query()
        ->where('property_id', $propertyId)
        ->first();

        if ($propertyTable) {
            $propertyTable->part_of_security = empty($partOfSecurity) ? 'No' : $partOfSecurity;
            $propertyTable->save();
        }

        return true;
    }

    public function getApplicantTitleHolder($applicationId, $propertyId) {

        $this->logger->info('ContactCenterAppBO->getApplicantTitleHolder',[$applicationId, $propertyId]);

        $applicants = array();
        $corporations = array();
        $titleHolders = '';

        if ($propertyId > 0) {
            $propertyTable = PropertyTable::query()
            ->where('property_id', $propertyId)
            ->first();
    
            if ($propertyTable) {
                $titleHolders = $propertyTable->title_holders;
            }
        }

        $query = "select b.f_name, b.l_name, b.`type`
                    from applicant_table a 
                    join spouse_table b on b.spouse_id = a.spouse1_id or b.spouse_id = a.spouse2_id
                   where a.application_id = ?
                     and b.f_name <> ''
                order by b.spouse_id";
        $res = $this->db->select($query, [$applicationId]);

        foreach ($res as $key => $value) {
            $applicants[] = [
                'name' => $value->f_name . ' ' . $value->l_name,
                'type' => $value->type
            ];
        }

        $query = 'select name 
                    from corporation_table 
                   where application_id = ?';
        $res = $this->db->select($query, [$applicationId]);

        foreach ($res as $key => $value) {
            if (!is_null($value->name && !empty($value->name))) {
                $corporations[] = [
                    'name' => $value->name,
                    'type' => 'Corporation'
                ];
            }
        }

        $data = [
            'applicants' => $applicants,
            'corporations' => $corporations,
            'titleHolders' => $titleHolders,
            'totalApplicants' => count($applicants) + count($corporations)
        ];

        return $data;
    }

    /*public function getSalesJourneyStatus($opportunityId) {
        $this->logger->info('ContactCenterAppBO->getSalesJourneyStatus',[$opportunityId]);
        
        $saleIntegration = SalesforceIntegration::query()
        ->where('salesforce_id',$opportunityId )
        ->first();
        
        if($saleIntegration){
            $salesJourney = SalesJourney::query()
            ->where('application_id',$saleIntegration->object_id )
            ->whereNotIn('status_id',[18,9])
            ->first();

            if($salesJourney) {
                $status = intval($salesJourney->status_id);
                if($status == 18 || $status == 9) {
                    return ['status' => false, 'message' => 'Sales Journey has expired'];
                } else {
                    return ['status' => true, 'message' => 'Active Sales Journey exist'];
                }
            }
        }

        return ['status' => false, 'message' => 'Sales Journey does not exist'];
    }*/

    public function getRestrictedValuationGroup($userId) {

        $groupMembersTable = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('group_id', 47)
        ->first();

        if($groupMembersTable) {
            return true;
        } else {
            return false;
        }
    }
}
