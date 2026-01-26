<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\ILABO;



class ILAController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request) {

        $applicationId = $request->applicationId;

        $ilaBO = new ILABO($this->logger, $this->db);
        $res = $ilaBO->index($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function show(Request $request, $code) {

        $ilaBO = new ILABO($this->logger, $this->db);
        $res = $ilaBO->show($code);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Could not load ILA';
        }

        return response()->json($response, 200);
    }

    public function addIlaFirms(Request $request) {

        $ilaFirmName = $request->ilaFirmName;
        $ilaName = $request->ilaName;
        $ilaPosition = $request->ilaPosition;
        $ilaTelephone = $request->ilaTelephone;
        $ilaFax = $request->ilaFax;
        $ilaEmail = $request->ilaEmail;
        $ilaUnitNumber = $request->ilaUnitNumber;
        $ilaStreetNumber = $request->ilaStreetNumber;
        $ilaStreetName = $request->ilaStreetName;
        $ilaStreetType = $request->ilaStreetType;
        $ilaDirection = $request->ilaDirection;
        $ilaCity = $request->ilaCity;
        $ilaProvince = $request->ilaProvince;
        $ilaPostalCode = $request->ilaPostalCode;
        $ilaPOBox = $request->ilaPOBox;
        $ilaSTN = $request->ilaSTN;
        $ilaRR = $request->ilaRR;
        $ilaSite = $request->ilaSite;
        $ilaComp = $request->ilaComp;
        $ilaRating = $request->ilaRating;
        $useAsIla = $request->useAsIla;
        $ilaComments = $request->ilaComments;

        $appraisalFirms = new ILABO($this->logger, $this->db);
        $res = $appraisalFirms->addIlaFirms(
            $ilaFirmName, $ilaName, $ilaPosition,
            $ilaTelephone, $ilaFax, $ilaEmail, $ilaUnitNumber,
            $ilaStreetNumber, $ilaStreetName, $ilaStreetType,
            $ilaDirection, $ilaCity, $ilaProvince,
            $ilaPostalCode, $ilaPOBox, $ilaSTN, $ilaRR, $ilaSite,
            $ilaComp, $ilaRating, $useAsIla, $ilaComments);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Ila Firm Code saved successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Ila Firm Code cannot be saved';
        }

        return response()->json($response, 200);        
    }
    
    public function updateILA(Request $request, $id) {
    
        $firmName = $request->firmName ?? '';
        $name = $request->name ?? '';
        $position = $request->position ?? '';
        $telephone = $request->telephone ?? '';
        $fax = $request->fax ?? '';
        $email = $request->email ?? '';
        $useAsIla = $request->useAsIla ?? '';
        $comments = $request->comments ?? '';
        $rating = $request->rating ?? '';
        $unitNumber = $request->unitNumber ?? '';
        $streetNumber = $request->streetNumber ?? '';
        $streetName = $request->streetName ?? '';
        $streetType = $request->streetType ?? '';
        $direction = $request->direction ?? '';
        $city = $request->city ?? '';
        $province = $request->province ?? '';
        $postalCode = $request->postalCode ?? '';

        $ilaBO = new ILABO($this->logger, $this->db);
        $res = $ilaBO->updateIlaFirm(
            $id, $firmName, $name, $position, $telephone, $fax, $email, $useAsIla, $comments, $rating,
            $unitNumber, $streetNumber, $streetName, $streetType, $direction, $city, $province, $postalCode
        );

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'ILA Firm updated successfully';
        } else {
            $response->status = 'error';
            $response->message = 'Failed to update ILA Firm';
        }
        return response()->json($response, 200);
    } 
}