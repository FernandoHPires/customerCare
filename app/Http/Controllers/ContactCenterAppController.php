<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\BO\ApplicantBO;
use App\Amur\BO\ApplicationBO;
use App\Amur\BO\AssetBO;
use App\Amur\BO\IncomeBO;
use App\Amur\BO\LiabilityBO;
use App\Amur\BO\PropertyBO;
use App\Amur\BO\VehicleBO;
use App\Models\SalesforceIntegration;
use Illuminate\Http\Request;
use App\Amur\BO\ContactCenterAppBO;
use App\Amur\BO\MailingBO;
use App\Amur\BO\CorporationBO;
use App\Amur\Bean\Response;
use Illuminate\Support\Facades\Auth;
use App\Amur\BO\UserBO;
use App\Amur\BO\BrokerBO;
use App\Amur\BO\SalesJourneyBO;
use App\Amur\BO\MortgageBO;
use App\Amur\BO\SourceBO;
use App\Amur\BO\DisbursementBO;

class ContactCenterAppController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function show(Request $request, $applicationId, $type) {

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if(!$sfi) {
                $response = [
                    'status' => 'error',
                    'message' => 'Application not found',
                ];
                return response()->json($response, 200);
            }

            $applicationId = $sfi->object_id;
        }
        
        $this->logger->info('ContactCenterAppController->show',[$applicationId]);

        if(in_array($type, ['all', 'view'])) {

            $applicationsBO = new ApplicationBO($this->logger, $this->db);
            $application = $applicationsBO->getDataByApplicationId($applicationId);

            $mortgageBO = new MortgageBO($this->logger, $this->db);
            $activeMortgages = $mortgageBO->getActiveMortgages($applicationId);
            $checks = array();
            $purposeDetailOptions = $applicationsBO->getpurposeDetailOptions($applicationId);
            $urgencyOptions = $applicationsBO->getUrgencyOptions();
            $businessChannelOptions = $applicationsBO->getBusinessChannelOptions($applicationId);

        } else {
            $application = array();
            $activeMortgages = array();
            $checks = array();
            $purposeDetailOptions = array();
            $urgencyOptions = array();
            $businessChannelOptions = array();
        }

        if(in_array($type, ['all', 'applicants', 'view'])) {
            $applicantBO = new ApplicantBO($this->logger, $this->db);
            $applicants = $applicantBO->getDataByApplicationId($applicationId);

            $corporationBO = new CorporationBO($this->logger, $this->db);
            $corporations = $corporationBO->getDataByApplicationId($applicationId);
            
            $signersOptions = $applicantBO->getSigners($applicationId);
            
        } else {
            $applicants = array();
            $corporations = array();
            $signersOptions = array();
        }

        if(in_array($type, ['all', 'properties', 'view'])) {

            $userId = Auth::user()->user_id;

            $propertyBO = new PropertyBO($this->logger, $this->db);
            $ContactCenterAppBO = new ContactCenterAppBO($this->logger, $this->db);

            $properties = $propertyBO->getDataByApplicationId($applicationId);
            $titleHolders = $propertyBO->getTitleHolders($applicationId);
            $data = $ContactCenterAppBO->getApplicantTitleHolder($applicationId, 0);
            $totalApplicants = $data['totalApplicants'];

            $mailingBO = new MailingBO($this->logger, $this->db);
            $mailings = $mailingBO->getDataByApplicationId($applicationId);

            $restrictedValuationGroup = $ContactCenterAppBO->getRestrictedValuationGroup($userId);

        } else {
            $properties = array();
            $titleHolders = array();
            $mailings = array();
            $totalApplicants = 0;
            $restrictedValuationGroup = true;
        }

        if(in_array($type, ['all', 'vehicles', 'view'])) {
            $vehicleBO = new VehicleBO($this->logger);
            $vehicles = $vehicleBO->getDataByApplicationId($applicationId);

            $liabilityBO = new LiabilityBO($this->logger);
            $liabilities = $liabilityBO->getDataByApplicationId($applicationId);
        } else {
            $vehicles = array();
            $liabilities = array();
        }

        if(in_array($type, ['all', 'income', 'view'])) {
            $incomeBO = new IncomeBO($this->logger, $this->db);
            $presentEmployments = $incomeBO->getPresentEmployer($applicationId);
            $previousEmployments = $incomeBO->getPreviousEmployer($applicationId);
            $otherIncomes = $incomeBO->getOtherIncomeById($applicationId);

            $assetsBO = new AssetBO($this->logger, $this->db);
            $assets = $assetsBO->getDataByApplicationId($applicationId);
        } else {
            $presentEmployments = array();
            $previousEmployments = array();
            $otherIncomes = array();
            $assets = array();
        }        

        $userBO = new UserBO($this->logger, $this->db);
        $agentOptions = $userBO->getAllAgents();
        $agentSequenceOptions = $userBO->getSequenceAgents();
        $sequenceAgentList = $userBO->getSequenceAgentList();
        $signingAgentOptions = $userBO->getSigningAgents();

        //$brokerBO = new BrokerBO($this->logger, $this->db);
        //$brokerOptions = $brokerBO->getAllData();

        $salesJourneyBO = new SalesJourneyBO($this->logger, $this->db);
        $salesJourney = $salesJourneyBO->getActiveSalesJourney($applicationId);

        $sourceBO = new SourceBO($this->logger, $this->db);
        $sourceOptions = $sourceBO->getAutoCompleteSourceData();

        $response = [
            'status' => 'success',
            'message' => '',
            'data' => [
                'application' => $application,
                'applicants' => $applicants,
                'mailings' => $mailings,
                'corporations' => $corporations,
                'properties' => $properties,
                'titleHolders' => $titleHolders,
                'vehicles' => $vehicles,
                'liabilities' => $liabilities,
                'presentEmployments' => $presentEmployments,
                'previousEmployments' => $previousEmployments,
                'otherIncomes' => $otherIncomes,
                'assets' => $assets,
                'activeMortgages' => $activeMortgages,
                'checks' => $checks,
                'purposeDetailOptions' => $purposeDetailOptions,
                'totalApplicants' => $totalApplicants,
                'restrictedValuationGroup' => $restrictedValuationGroup,
                'signersOptions' => $signersOptions,
                'agentOptions' => $agentOptions,
                'agentSequenceOptions' => $agentSequenceOptions,
                //'brokerOptions' => $brokerOptions,
                'salesJourney' => $salesJourney,
                'signingAgentOptions' => $signingAgentOptions,
                'sourceOptions' => $sourceOptions,
                'sequenceAgentList' => $sequenceAgentList,
                'urgencyOptions' => $urgencyOptions,
                'businessChannelOptions' => $businessChannelOptions
            ]
        ];

        return response()->json($response, 200);
    }

    public function store(Request $request, $applicationId, $type) {

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            $applicationId = $sfi->object_id;
        }

        $contactCenterApp = new ContactCenterAppBO($this->logger, $this->db);
        $res = $contactCenterApp->store($request, $applicationId, $type);

        if(!is_array($res)) {
            $response = [
                'status' => 'success', 
                'message' => 'Success - Application saved!',
            ];
        } else {
            if(isset($res['validation'])) {
                $response = [
                    'status' => 'error',
                    'message' => $res['validation'],
                ];
            } else {
                $response = [
                    'status' => 'warning',
                    'message' => 'Application saved!<br>We noticed a few issues that need your attention. Please review them and try again<br>'.implode('', $res),
                ];
            }
        }

        return response()->json($response, 200);
    }

    public function nearbyMortgages(Request $request) {

        $applicationId = $request->applicationId;
        $propertyId = $request->propertyId;
        $postalCode = $request->postalCode;
        $city = $request->city;

        $contactCenterAppBO = new ContactCenterAppBO($this->logger, $this->db);
        $res = $contactCenterAppBO->nearbyMortgages($applicationId, $propertyId, $postalCode, $city);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getBrokerOptions(Request $request) {
        $brokerBO = new BrokerBO($this->logger, $this->db);
        $res = $brokerBO->getAllData();

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getInvestorTracking(Request $request) {

        $savedQuoteId = $request->savedQuoteId;
        $applicationId = $request->applicationId;


        $contactCenterAppBO = new ContactCenterAppBO($this->logger, $this->db);
        $res = $contactCenterAppBO->getInvestorTracking($savedQuoteId, $applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getDisbursement(Request $request, $applicationId) {
        $this->logger->info('ContactCenterAppController->getDisbursement',[$applicationId]);

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if(!$sfi) {
                $response = [
                    'status' => 'error',
                    'message' => 'Application not found',
                ];

                return response()->json($response, 200);
            }

            $applicationId = $sfi->object_id;
        }

        $disbursementBO = new DisbursementBO($this->logger, $this->db);
        $res = $disbursementBO->getDisbursement($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }
        return response()->json($response, 200);
    }

    public function updatePartOfSecurity(Request $request, $id) {

        $propertyId = $id;
        $partOfSecurity = $request->partOfSecurity;


        $contactCenterAppBO = new ContactCenterAppBO($this->logger, $this->db);
        $res = $contactCenterAppBO->updatePartOfSecurity($propertyId, $partOfSecurity);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getApplicantTitleHolder(Request $request) {

        $applicationId = $request->applicationId;
        $propertyId = $request->propertyId;       

        $contactCenterAppBO = new ContactCenterAppBO($this->logger, $this->db);
        $res = $contactCenterAppBO->getApplicantTitleHolder($applicationId, $propertyId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);        
    }

    public function getSalesJourneyStatus(Request $request){

        $applicationId = null;
        if(substr($request->applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $request->applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if($sfi) {
                $applicationId = $sfi->object_id;
            }
        } else {
            $applicationId = $request->applicationId;
        }

        if(!is_null($applicationId)) {
            $salesJourneyBO = new SalesJourneyBO($this->logger, $this->db);
            $res = $salesJourneyBO->getActiveSalesJourney($applicationId);
        }

        $response = new Response();
        if(isset($res['id']) && $res['id'] != 0) {
            $response->status = 'success';
            $response->data = true;
        } else {
            $response->status = 'unsuccessfull';
            $response->data = false;
        }

        return response()->json($response, 200);      
    }

    public function updateSalesJourney(Request $request) {

        $salesJourneyId = $request->salesJourneyId;
        $field = $request->field;
        $value = $request->value;

        $salesJourneyBO = new SalesJourneyBO($this->logger, $this->db);
        $res = $salesJourneyBO->updateSalesJourney($salesJourneyId, $field, $value);

        $response = new Response();
        if ($res['status'] === true) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = $res['message'];
        }

        return response()->json($response, 200);
    }

}