<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\ApplicationBO;
use App\Amur\BO\UserBO;
use App\Models\SalesforceIntegration;
use Illuminate\Http\Request;

class ApplicationController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request, $applicationId) {
        if(empty($applicationId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid application ID'
            ], 200);
        }

        if(substr($applicationId,0,3) == '006') {
            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $applicationId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if($sfi) {
                $applicationId = $sfi->object_id;
            }
        }
    
        $applicationBO = new ApplicationBO($this->logger, $this->db);
        $res = $applicationBO->index($applicationId);
    
        $response = new Response();
        if (!empty($res)) {
            $response->status = 'success';
            $response->message = 'Tracker data retrieved successfully';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'No Tracker data found for the given application ID';
        }
    
        return response()->json($response, 200);
    }
    
    
    public function saveIla(Request $request) {

        $applicationId = $request->applicationId;
        $ila = $request->ila;
    
        $applicationBO = new ApplicationBO($this->logger, $this->db);
        $res = $applicationBO->saveIla($applicationId, $ila);
    
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'ILA updated successfully';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'ILA could not be updated';
        }
    
        return response()->json($response, 200);
    } 

}