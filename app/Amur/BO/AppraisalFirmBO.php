<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Amur\Bean\IDB;
use App\Models\AppraisalFirmsTable;
use App\Amur\Utilities\Utils;
use App\Amur\BO\SalesforceBO;
use App\Amur\BO\PropertyBO;
use App\Models\ApplicantTable;
use App\Models\SpouseTable;
use App\Models\SalesforceIntegration;

class AppraisalFirmBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index() {

        $query = "select * from designation_type_table order by id";
        $res = $this->db->select($query);

        $designations = array();

        foreach ($res as $designation) {
            $designations[$designation->id] = $designation->name;
        }

        $appraisalFirms = AppraisalFirmsTable::query()
        ->orderBy('name', 'asc')
        ->get();

        $data = array();

        foreach($appraisalFirms as $key => $value) {

            $status = '';
            if($value->status == 'A') {
                $status = 'Aproved';
            } elseif ($value->status == 'P') {
                $status = 'Pending';
            } elseif ($value->status == 'N') {
                $status = 'Not Approved';
            }

            $address = Utils::formatAddress(
                $value->unit_number, $value->street_number, $value->street_name,
                $value->street_type, $value->street_direction,
                $value->city, $value->province, $value->postal_code, $value->po_box_number,
                $value->station, $value->rural_route, $value->site, $value->compartment
            );

            $key = 0;
            if (is_numeric($value->designation)) {
                $key = $value->designation;
            }

            $data[] = [
                'appraisalFirmCode' => $value->appraisal_firm_code,
                'name' => $value->name,
                'telephone' => $value->telephone,
                'fax' => $value->fax,
                'email' => $value->email,
                'website' => $value->website,
                'address' => $address,
                'streetNumber' => $value->street_number,
                'unitNumber' => $value->unit_number,
                'streetName' => $value->street_name,
                'streetType' => $value->street_type,
                'streetDirection' => $value->street_direction,
                'city' => $value->city,
                'province' => $value->province,
                'postalCode' => $value->postal_code,
                'poBox' => $value->po_box_number,
                'stn' => $value->station,
                'rr' => $value->rural_route,
                'site' => $value->site,
                'comp' => $value->compartment,
                'rating' => $value->rating,
                'areasCovered' => $value->areas_covered,
                'comments' => $value->comments,
                'designation' => $designations[$key],
                'status' => $status
            ];
        }
        
        return $data;
    }

    public function apprDetailInformation($apprCode){
        $this->logger->info('AppraisalFirmBO->apprDetailInformation', ['apprCode' => $apprCode]);

        $appraisalFirm = AppraisalFirmsTable::query()
        ->where('appraisal_firm_code', $apprCode)
        ->first();

        $apprAddress = Utils::formatAddress(
            $appraisalFirm->unit_number, $appraisalFirm->street_number, $appraisalFirm->street_name,
            $appraisalFirm->street_type, $appraisalFirm->street_direction, $appraisalFirm->city,
            $appraisalFirm->province, $appraisalFirm->postal_code, $appraisalFirm->po_box_number,
            $appraisalFirm->station, $appraisalFirm->rural_route, $appraisalFirm->site, $appraisalFirm->compartment
        );
        $apprName = $appraisalFirm->name;
        $apprTelephone = $appraisalFirm->telephone;
        $apprFax = $appraisalFirm->fax;
        $apprEmail = $appraisalFirm->email;
        $appraisalComments = $appraisalFirm->comments;

        $apprData = [
            'appraisalFirmCode' => $appraisalFirm->appraisal_firm_code,
            'name' => $apprName,
            'telephone' => $apprTelephone,
            'fax' => $apprFax,
            'email' => $apprEmail,
            'website' => $appraisalFirm->website,
            'address' => $apprAddress,
            'streetNumber' => $appraisalFirm->street_number,
            'unitNumber' => $appraisalFirm->unit_number,
            'streetName' => $appraisalFirm->street_name,
            'streetType' => $appraisalFirm->street_type,
            'streetDirection' => $appraisalFirm->street_direction,
            'city' => $appraisalFirm->city,
            'province' => $appraisalFirm->province,
            'postalCode' => $appraisalFirm->postal_code,
            'poBox' => $appraisalFirm->po_box_number,
            'stn' => $appraisalFirm->station,
            'rr' => $appraisalFirm->rural_route,
            'site' => $appraisalFirm->site,
            'comp' => $appraisalFirm->compartment,
            'rating' => $appraisalFirm->rating,
            'areasCovered' => $appraisalFirm->areas_covered,
            'comments' => $appraisalComments,
            'designation' => $appraisalFirm->designation,
        ];

        return $apprData;
    }

    public function addAppraisalFirms(
        $appraisalName, $appraisalComments, $appraisalTelephone, $appraisalFax,
        $appraisalEmail, $appraisalWebsite, $appraisalUnitNumber, $appraisalStreetNumber,
        $appraisalStreetName, $appraisalStreetType, $appraisalDirection, $appraisalCity,
        $appraisalProvince, $appraisalPostalCode, $appraisalPOBox, $appraisalSTN, $appraisalRR,
        $appraisalSite, $appraisalComp, $appraisalDesignation, $appraisalRating, $appraisalAreasCovered
    ) {

        $this->db->beginTransaction();
        try {

            $duplicate = AppraisalFirmsTable::where('name', $appraisalName)
            ->where('telephone', $appraisalTelephone)
            ->where('fax', $appraisalFax)
            ->where('email', $appraisalEmail)
            ->where('website', $appraisalWebsite)
            ->where('unit_number', $appraisalUnitNumber)
            ->where('street_number', $appraisalStreetNumber)
            ->where('street_name', $appraisalStreetName)
            ->where('street_type', $appraisalStreetType)
            ->where('street_direction', $appraisalDirection)
            ->where('city', $appraisalCity)
            ->where('province', $appraisalProvince)
            ->where('postal_code', $appraisalPostalCode)
            ->where('po_box_number', $appraisalPOBox)
            ->where('station', $appraisalSTN)
            ->where('rural_route', $appraisalRR)
            ->where('site', $appraisalSite)
            ->where('compartment', $appraisalComp)
            ->where('areas_covered', $appraisalAreasCovered)
            ->where('rating', $appraisalRating)
            ->where('comments', $appraisalComments)
            ->first();            

            if (!$duplicate) {
                $apprasialFirms = new AppraisalFirmsTable();
                $apprasialFirms->name = $appraisalName ?? '';
                $apprasialFirms->telephone = $appraisalTelephone ?? '';
                $apprasialFirms->fax = $appraisalFax ?? '';
                $apprasialFirms->email = $appraisalEmail ?? '';
                $apprasialFirms->website = $appraisalWebsite ?? '';
                $apprasialFirms->unit_number = $appraisalUnitNumber ?? '';
                $apprasialFirms->street_number = $appraisalStreetNumber ?? '';
                $apprasialFirms->street_name = $appraisalStreetName ?? '';
                $apprasialFirms->street_type = $appraisalStreetType ?? '';
                $apprasialFirms->street_direction = $appraisalDirection ?? '';
                $apprasialFirms->city = $appraisalCity ?? '';
                $apprasialFirms->province = $appraisalProvince ?? '';
                $apprasialFirms->postal_code = $appraisalPostalCode ?? '';
                $apprasialFirms->po_box_number = $appraisalPOBox ?? '';
                $apprasialFirms->station = $appraisalSTN ?? '';
                $apprasialFirms->rural_route = $appraisalRR ?? '';
                $apprasialFirms->site = $appraisalSite ?? '';
                $apprasialFirms->compartment = $appraisalComp ?? '';
                $apprasialFirms->areas_covered = $appraisalAreasCovered ?? '';
                $apprasialFirms->designation = $appraisalDesignation ?? '';
                $apprasialFirms->rating = $appraisalRating ?? '';
                $apprasialFirms->comments = $appraisalComments ?? '';

                if ($apprasialFirms->save()) {
                    $salesforceBO = new SalesforceBO($this->logger, $this->db);
                    $salesforceBO->syncNewAppraisalCompany($apprasialFirms->appraisal_firm_code);
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('AppraisalFirmBO->addAppraisalFirms', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
        
        $this->db->commit();
        return true;
    }
    
    public function updateAppraisalFirm(
        $appraisalFirmCode, $appraisalTelephone, $appraisalFax, $appraisalEmail, $appraisalWebsite,
        $appraisalUnitNumber, $appraisalStreetNumber, $appraisalStreetName, $appraisalStreetType,
        $appraisalDirection, $appraisalCity, $appraisalProvince, $appraisalPostalCode, $appraisalPOBox,
        $appraisalSTN, $appraisalRR, $appraisalSite, $appraisalComp, $appraisalAreasCovered, $appraisalComments
    ) {

        $this->db->beginTransaction();
        try {

            $appraisalFirm = AppraisalFirmsTable::query()
            ->where('appraisal_firm_code', $appraisalFirmCode)
            ->first();

            if ($appraisalFirm) {
                $appraisalFirm->telephone = $appraisalTelephone ?? '';
                $appraisalFirm->fax = $appraisalFax ?? '';
                $appraisalFirm->email = $appraisalEmail ?? '';
                $appraisalFirm->website = $appraisalWebsite ?? '';
                $appraisalFirm->unit_number = $appraisalUnitNumber ?? '';
                $appraisalFirm->street_number = $appraisalStreetNumber ?? '';
                $appraisalFirm->street_name = $appraisalStreetName ?? '';
                $appraisalFirm->street_type = $appraisalStreetType ?? '';
                $appraisalFirm->street_direction = $appraisalDirection ?? '';
                $appraisalFirm->po_box_number = $appraisalPOBox ?? '';
                $appraisalFirm->station = $appraisalSTN ?? '';
                $appraisalFirm->city = $appraisalCity ?? '';
                $appraisalFirm->province = $appraisalProvince ?? '';
                $appraisalFirm->postal_code = $appraisalPostalCode ?? '';
                $appraisalFirm->rural_route = $appraisalRR ?? '';
                $appraisalFirm->site = $appraisalSite ?? '';
                $appraisalFirm->compartment = $appraisalComp ?? '';
                $appraisalFirm->areas_covered = $appraisalAreasCovered ?? '';
                $appraisalFirm->comments = $appraisalComments ?? '';
                $appraisalFirm->save();

                $this->logger->info('AppraisalFirmBO->edited and saved', ['appraisalFirmCode' => $appraisalFirmCode]);
            }

        } catch (\Throwable $e) {
            $this->logger->error('AppraisalFirmBO->updateAppraisalFirm', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
        
        $this->db->commit();
        return true;
    }

    public function statusValidation($appraisalFirmCode) {

        $appraisalFirm = AppraisalFirmsTable::query()
        ->where('appraisal_firm_code', $appraisalFirmCode)
        ->first();

        if ($appraisalFirm === null) {
            $this->logger->error('AppraisalFirmBO->statusValidation', ['appraisalFirmCode' => $appraisalFirmCode, 'error' => 'Appraisal firm not found']);
            return false; 
        }

        if ($appraisalFirm->status == 'A') {
            return 'Aproved';
        } elseif ($appraisalFirm->status == 'P') {
            return 'Pending';
        } elseif ($appraisalFirm->status == 'N') {
            return 'Not Approved';
        }

        return false;
    }

    public function sendAppraisalEmail($applicationId, $orderMethod, $appraisalFirmCode, $propertyId, $payer) {

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if ($sfi) {
                $applicationId = $sfi->object_id;
            }
        }

        $this->logger->info('AppraisalFirmBO->sendAppraisalEmail',[$applicationId,$orderMethod,$appraisalFirmCode,$propertyId,$payer]);
        
        $emailAddress = '';
        $subject = '';
        $message = '';

        if ($orderMethod != 'EM - Appr' && $orderMethod != 'EM - Est' ) {
            $emailData = [
                'emailAddress' => $emailAddress,
                'subject' => $subject,
                'message' => $message
            ];
    
            return $emailData;
        }

        $appraisalFirmsTable = AppraisalFirmsTable::query()
        ->where('appraisal_firm_code', $appraisalFirmCode)
        ->first();

        if(!$appraisalFirmsTable) {
            return false;
        }

        $name = $appraisalFirmsTable->name;
        $emailAddress = $appraisalFirmsTable->email;
        $subject = '';
        $message = '';

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $propertyTable = $propertyBO->getPropertyById($propertyId);

        $propertyAddress = '';
        $contacts = '';
        if($propertyTable) {

            if($propertyTable->unit_number) {
                $propertyAddress .= $propertyTable->unit_number.'-';
            }
            $propertyAddress .= $propertyTable->street_number.' '.$propertyTable->street_name.' '.$propertyTable->street_type.' '.$propertyTable->street_direction.' '.$propertyTable->city.' '.$propertyTable->province.' '.$propertyTable->postal_code;

            $titleHolder = $propertyTable->title_holders;

            $applicantTable = ApplicantTable::query()
            ->where('application_id', $applicationId)
            ->first();

            if($applicantTable) {
                $spouseTable = SpouseTable::query()
                ->where('spouse_id', $applicantTable->spouse1_id)
                ->first();

                $lname = '';
                $fname = '';

                if($spouseTable) {
                    $lname = $spouseTable->l_name;
                    $fname = $spouseTable->f_name;
                }

                if(!empty($applicantTable->home_fax)) {                    
                    $contacts = 'Home Phone: ' . Utils::formatPhone($applicantTable->home_fax);
                } else {
                    $contacts = 'Home Phone: ' . Utils::formatPhone($applicantTable->home_phone);
                }

                if(!empty($applicantTable->home_email)) {
                    $contacts .= '; Home Email: ' . $applicantTable->home_email;
                }
            }
        }

        $position = "";

        if ($orderMethod == 'EM - Est') {
            $subjectEnd = ' - ESTIMATE of Value';
            $orderWhat = 'ESTIMATE of Value';
            $contactsString = '';
        } else {
            $subjectEnd = ' - Appraisal Order';
            $orderWhat = 'appraisal';
            $contactsString = '('.$contacts.')';
        }
        
        $sql = "select position
                  from saved_quote_positions_table a, saved_quote_table b
                 where a.saved_quote_id = b.saved_quote_id
                   and b.application_id = ?
                   and disburse = 'Yes'";
        $res = $this->db->select($sql,[$applicationId]);

        if (!empty($res)) {
            $r = (array) $res[0];
            $position = ", for {$r['position']} mortgage purposes";
        }

        $subject = "#".$applicationId;

        if(isset($lname) && isset($fname)) {
            $lastName = str_replace('\\','',$lname);
            $firstName = str_replace('\\','',$fname);
            $subject .= " $lastName, $firstName".$subjectEnd;
        } else {
            $subject .= $subjectEnd;
        }

        $subject = rawurlencode($subject);


        $message  = "Dear {$name},\n\nWe would like an ".$orderWhat." for the following client$position:\n\n$titleHolder ".$contactsString."\n$propertyAddress\n\n";

        if ($orderMethod != 'EM - Est') {
            $lenderArray = array(
                '31' => 'Amur Capital Income Fund Inc.',
                '248' => 'Amur Capital Conservative Income Fund Inc.',
                '100' => 'Amur Capital High Yield Fund Inc.'
            );
        

            $query = 'SELECT si.investor_id 
                    FROM sale_investor_table si, saved_quote_table sq
                    WHERE sq.application_id = '.$applicationId.'
                    AND sq.disburse = "Yes"
                    AND sq.saved_quote_id = si.saved_quote_id 
                    AND (si.fm_committed = "Yes" OR si.fm_committed = "Looking") 
                    AND si.investor_id IN (248,31,100)';
            $res = $this->db->select($query);

            /*if($res && isset($res[0]->investor_id)) {
                $investorId = $res[0]->investor_id;
                if (empty($investorId)){
                    $investor = 'Alpine Credits';
                } else {
                    $investor = $lenderArray[$investorId];
                }
            }*/

            if ($payer == "Client") {
                $message .= "The Client will pay for this appraisal up front. Please contact the client as soon as possible and reply to this email confirming:\n\n\t- Name of appraiser to complete the report MUST be CRA or AACI designation for appraiser or co-signed by AIC appraiser\n\t- Date and time property is to be viewed\n\t- Cost of the report\n\nIn addition, we require the report to include:\n\n\t- Minimum of 6 interior and 5 exterior photos of all homes on the property. Interior and exterior inspection conducted by appraiser.\n\t- Multiple interior and exterior photos of any other buildings or structures on the property\n\t- Location map noting the subject property as well as all comparables locations relative to the subject\n\t- \"Days on Market\" noted for the comparables\n\t- Land and building dimensions in square feet (not m2)\n\t- Current title search & \"As Is\" Value is required.\n\nPlease note:\n\t- Direct the report to our company with intended user to be: *+DRAFT+* \n\nShould there be difficulty providing any of the above details & requests, please contact me before proceeding.";
            } else {
                $message .= "Our Company will pay for this appraisal. Please contact the client as soon as possible and reply to this email confirming the following:\n\n\t- Name of appraiser to complete the report MUST be CRA or AACI designation for appraiser or co-signed by AIC appraiser\n\t- Date and time property is to be viewed\n\t- Cost of the report\n\nIn addition, we require the report to include:\n\n\t- Minimum of 6 interior and 5 exterior photos of all homes on the property. Interior and exterior inspection conducted by appraiser.\n\t- Multiple interior and exterior photos of any other buildings or structures on the property\n\t- Location map noting the subject property as well as all comparables locations relative to the subject\n\t- \"Days on Market\" noted for the comparables\n\t- Land and building dimensions in square feet (not m2)\n\t- Current title search & \"As Is\" Value is required.";
                $message .= "\n\nPlease note: \n\t- Direct the report to our company with intended user to be: *+DRAFT+*  \n\nShould there be difficulty providing any of the above details & requests, please contact me *before proceeding*";
            }
        }
        if ($orderMethod == 'EMTemp') {
            $message = '';
        }

        $message = rawurlencode($message);

        $emailData = [
            'emailAddress' => $emailAddress,
            'subject' => $subject,
            'message' => $message
        ];

        return $emailData;
    }
}
