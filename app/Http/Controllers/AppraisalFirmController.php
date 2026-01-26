<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\AppraisalFirmBO;

class AppraisalFirmController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index() {
        $appraisalFirms = new AppraisalFirmBO($this->logger, $this->db);
        $firms = $appraisalFirms->index();

        if($firms != false) {
            $response = new Response();
            $response->status = 'success';
            $response->data = $firms;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }
        return response()->json($response, 200);
    }

    public function apprDetails($code) {
        $this->logger->info('AppraisalFirmController->apprDetails');

        $appraisalFirmCode = $code;

        $appraisalFirms = new AppraisalFirmBO($this->logger, $this->db);
        $res = $appraisalFirms->apprDetailInformation($appraisalFirmCode);

        if($res != false) {
            $response = new Response();
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }
        return response()->json($response, 200);
    }

    public function addAppraisalFirms(Request $request) {

        $appraisalName = $request->appraisalName;
        $appraisalComments = $request->appraisalComments;
        $appraisalTelephone = $request->appraisalTelephone;
        $appraisalFax = $request->appraisalFax;
        $appraisalEmail = $request->appraisalEmail;
        $appraisalWebsite = $request->appraisalWebsite;
        $appraisalUnitNumber = $request->appraisalUnitNumber;
        $appraisalStreetNumber = $request->appraisalStreetNumber;
        $appraisalStreetName = $request->appraisalStreetName;
        $appraisalStreetType = $request->appraisalStreetType;
        $appraisalDirection = $request->appraisalDirection;
        $appraisalCity = $request->appraisalCity;
        $appraisalProvince = $request->appraisalProvince;
        $appraisalPostalCode = $request->appraisalPostalCode;
        $appraisalPOBox = $request->appraisalPOBox;
        $appraisalSTN = $request->appraisalSTN;
        $appraisalRR = $request->appraisalRR;
        $appraisalSite = $request->appraisalSite;
        $appraisalComp = $request->appraisalComp;
        $appraisalDesignation = $request->appraisalDesignation;
        $appraisalRating = $request->appraisalRating;
        $appraisalAreasCovered = $request->appraisalAreasCovered;

        $appraisalFirms = new AppraisalFirmBO($this->logger, $this->db);
        $res = $appraisalFirms->addAppraisalFirms(
            $appraisalName, $appraisalComments, $appraisalTelephone, $appraisalFax, $appraisalEmail,
            $appraisalWebsite, $appraisalUnitNumber, $appraisalStreetNumber, $appraisalStreetName,
            $appraisalStreetType, $appraisalDirection, $appraisalCity, $appraisalProvince,
            $appraisalPostalCode, $appraisalPOBox, $appraisalSTN, $appraisalRR, $appraisalSite,
            $appraisalComp, $appraisalDesignation, $appraisalRating, $appraisalAreasCovered);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Appraisal Firm Code saved successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Appraisal Firm Code cannot be saved';
        }

        return response()->json($response, 200);        
    }

    public function updateAppraisalFirms(Request $request , $id){
        $this->logger->info('AppraisalFirmController->updateAppraisalFirms', ['id' => $id]);
        
        $appraisalFirmCode = $id;
        //$appraisalName = $request->appraisalName;
        $appraisalComments = $request->appraisalComments;
        $appraisalTelephone = $request->appraisalTelephone;
        $appraisalFax = $request->appraisalFax;
        $appraisalEmail = $request->appraisalEmail;
        $appraisalWebsite = $request->appraisalWebsite;
        $appraisalUnitNumber = $request->appraisalUnitNumber;
        $appraisalStreetNumber = $request->appraisalStreetNumber;
        $appraisalStreetName = $request->appraisalStreetName;
        $appraisalStreetType = $request->appraisalStreetType;
        $appraisalDirection = $request->appraisalDirection;
        $appraisalCity = $request->appraisalCity;
        $appraisalProvince = $request->appraisalProvince;
        $appraisalPostalCode = $request->appraisalPostalCode;
        $appraisalPOBox = $request->appraisalPOBox;
        $appraisalSTN = $request->appraisalSTN;
        $appraisalRR = $request->appraisalRR;
        $appraisalSite = $request->appraisalSite;
        $appraisalComp = $request->appraisalComp;
        //$appraisalDesignation = $request->appraisalDesignation;
        //$appraisalRating = $request->appraisalRating;
        $appraisalAreasCovered = $request->appraisalAreasCovered;

        $appraisalFirms = new AppraisalFirmBO($this->logger, $this->db);
        $res = $appraisalFirms->updateAppraisalFirm(
            $id, $appraisalTelephone, $appraisalFax, $appraisalEmail, $appraisalWebsite,
            $appraisalUnitNumber, $appraisalStreetNumber, $appraisalStreetName,
            $appraisalStreetType, $appraisalDirection, $appraisalCity, $appraisalProvince,
            $appraisalPostalCode, $appraisalPOBox, $appraisalSTN, $appraisalRR,
            $appraisalSite, $appraisalComp, $appraisalAreasCovered, $appraisalComments);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Appraisal Firm Code updated successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Appraisal Firm Code cannot be updated';
        }
    }

    public function sendAppraisalEmail(Request $request) {

        $applicationId = $request->applicationId;
        $orderMethod = $request->orderMethod;
        $appraisalFirmCode = $request->appraisalFirmCode;
        $propertyId = $request->propertyId;
        $payer = $request->payer;

        $contactCenterAppBO = new AppraisalFirmBO($this->logger, $this->db);
        $res = $contactCenterAppBO->sendAppraisalEmail($applicationId, $orderMethod, $appraisalFirmCode, $propertyId, $payer);
        
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
}