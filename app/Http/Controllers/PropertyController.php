<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\PropertyBO;

class PropertyController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getAppraisalDate(Request $request, $id) {

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getAppraisalDate($id);
        
        if($res !== false) {
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
    
    public function saveAppraisalDate(Request $request) {
        $id = $request->id;
        $applicationId = $request->applicationId;
        $propertyId = $request->propertyId;
        $required = $request->required;
        $received = $request->received;
        $appraisalValue = $request->appraisalValue;
        $stage = $request->stage;

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->saveAppraisalDate($id, $applicationId, $propertyId, $required, $received, $appraisalValue, $stage);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Appraisal saved successfully';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getStreetTypes(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getStreetType();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getDirectionTypes(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getDirection();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getValueMethodOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getValueMethod();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getOrderMethodOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getOrderMethod();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getWhoWillPayOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getWhoWillPay();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }
 
    public function getResidentialMarketValuation(Request $request) {
        $propertyId = $request->propertyId;

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getResidentialMarketValuation($propertyId);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }
        return response()->json($response, 200);
    }

    public function getEstimatedValueRange(Request $request) {
        $propertyId = $request->propertyId;

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getEstimatedValueRange($propertyId);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }
        return response()->json($response, 200);
    }

    public function getPropertyTypes(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getProperty();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getUnitTypeOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getUnitType();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getBasementOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getBasement();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getHeatOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getHeat();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getRoofingOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getRoofing();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getExteriorOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getExterior();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getHouseOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getHouse();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getWaterOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getWater();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getSewageOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getSewage();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getRentalOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getRental();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getApplicantTypeOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getApplicantType();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function getMaritalStatusOptions(Request $request) {
        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->getmaritalStatus();

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving street types!';
        }

        return response()->json($response, 200);
    }

    public function saveTitleHolder(Request $request) {

        $applicationId = $request->applicationId;
        $propertyId = $request->propertyId;
        $titleHolders = $request->titleHolders;

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $res = $propertyBO->saveTitleHolder($applicationId, $propertyId, $titleHolders);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }
}