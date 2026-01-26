<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\ApplicationDashboardBO;
use App\Amur\BO\MortgageBO;
use App\Amur\BO\PapBO;
use App\Models\SalesforceIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationDashboardController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getMortgages(Request $request, $objectId) {

        if(substr($objectId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $objectId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if(!$sfi) {
                $response = new Response();
                $response->status = 'error';
                $response->message = 'Error - No matching Salesforce record found';
                return response()->json($response, 200);
            }

            $applicationId = $sfi->object_id;
        } else {
            $applicationId = $objectId;
        }

        $this->logger->info('ApplicationDashboardController->getMortgages', ['objectId' => $objectId, 'applicationId' => $applicationId]);

        $applicationDashboardBO = new ApplicationDashboardBO($this->logger, $this->db);
        $res = $applicationDashboardBO->getMortgages($applicationId);        

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgages';
        }

        return response()->json($response, 200);
    }

    public function getMortgagePayments(Request $request, $mortgageId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getMortgagePayments($mortgageId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgage payments';
        }

        return response()->json($response, 200);
    }

    public function getUpcomingPayments(Request $request, $mortgageId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getUpcomingPayments($mortgageId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgage payments';
        }

        return response()->json($response, 200);
    }

    public function getProperties(Request $request, $mortgageId) {

        $this->logger->info('ApplicationDashboardController->getProperties', ['mortgageId' => $mortgageId]);

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getProperties($mortgageId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgage payments';
        }

        return response()->json($response, 200);
    }

    public function getRenewals(Request $request, $mortgageId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getRenewals($mortgageId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgage payments';
        }

        return response()->json($response, 200);
    }

    public function getPapBankInfo(Request $request, $mortgageId) {

        $papBO = new PapBO($this->logger, $this->db);
        $res = $papBO->getPapBankInfo($mortgageId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get mortgage payments';
        }

        return response()->json($response, 200);
    }


    public function getNotes(Request $request, $objectId) {

        if(substr($objectId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $objectId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            $applicationId = $sfi->object_id;
        }else {
            $applicationId = $objectId;
        }

        $applicationDashboardBO = new ApplicationDashboardBO($this->logger, $this->db);
        $res = $applicationDashboardBO->getNotes($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get notes';
        }

        return response()->json($response, 200);
    }

    public function getMortgagors(Request $request, $mortgageId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getMortgagors($mortgageId);

        $response = new Response();

        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get Mortgagors details';
        }

        return response()->json($response, 200);
    }

    public function getInvestorTracking(Request $request, $mortgageId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getInvestorTracking($mortgageId);

        $response = new Response();

        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get Investors details';
        }

        return response()->json($response, 200);
    }
    
    public function calculatePayout(Request $request) {

        $mortgageId = $request->mortgageId;
        $payoutDate = $request->payoutDate;
        $payoutMIP = $request->payoutMIP;
        $payoutDischarge = $request->payoutDischarge;
        $payoutLegal = $request->payoutLegal;
        $payoutMisc = $request->payoutMisc;

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->calculatePayout($mortgageId, $payoutDate, $payoutMIP, $payoutDischarge, $payoutLegal, $payoutMisc);

        $response = new Response();

        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Payout Could be calculated';
        }

        return response()->json($response, 200);
    }

    public function getApplicationId(Request $request, $objectId) {

        if(substr($objectId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $objectId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if($sfi) {
                $applicationId = $sfi->object_id;
            } else {
                $response = new Response();
                $response->status = 'error';
                $response->message = 'Error - No matching Salesforce record found';
                return response()->json($response, 200);
            }
        } else {
            $applicationId = $objectId;
        }

        $response = new Response();
        $response->status = 'success';
        $response->data = $applicationId;

        return response()->json($response, 200);
    }

    public function getMyApps(Request $request) {
        if(isset($request->userId) && !empty($request->userId)) {
            $userId = $request->userId;
        } else {
            $userId = Auth::user()->user_id;
        }

        $applicationDashboardBO = new ApplicationDashboardBO($this->logger, $this->db);
        $res = $applicationDashboardBO->getMyApps($userId);        

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get apps';
        }

        return response()->json($response, 200);
    }

    public function updateInsurance(Request $request) {

        $mortgageId = $request->mortgageId;
        $insurance = $request->insurance;


        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->updateInsurance($mortgageId, $insurance);

        $response = new Response();

        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Insurance could not be updated';
        }

        return response()->json($response, 200);
    }

    public function updateEarthquake(Request $request) {

        $mortgageId = $request->mortgageId;
        $earthquake = $request->earthquake;


        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->updateEarthquake($mortgageId, $earthquake);

        $response = new Response();

        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Insurance could not be updated';
        }

        return response()->json($response, 200);
    }




}