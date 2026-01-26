<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\ApplicantBO;
use Illuminate\Http\Request;

class ApplicantController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getApplicantsSF(Request $request) {
        $opportunityId = $request->opportunityId;
        $userId = $request->userId;

        $applicantBO = new ApplicantBO($this->logger, $this->db);
        $res = $applicantBO->getApplicantsSF($opportunityId, $userId);
        
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }
        
        return response()->json($response, 200);
    }

    /*public function updateApplicantsSF(Request $request, $opportunityId, $userId) {
        $applicantBO = new ApplicantBO($this->logger, $this->db);
        $res = $applicantBO->updateApplicantsSF($opportunityId, $userId, $request->applicants);
        
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
        } else {
            $response->status = 'error';
        }
        
        return response()->json($response, 200);
    }*/
}