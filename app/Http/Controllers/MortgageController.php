<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\MergeDocumentsBO;
use App\Amur\BO\MortgageBO;

class MortgageController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getByProperty(Request $request, $propertyId) {

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->getByProperty($propertyId);

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

    public function getMortgages(Request $request, $applicationId) {
        // Validate application ID
        if (empty($applicationId) || !is_numeric($applicationId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid application ID'
            ], 200);
        }

        // Instantiate the ApplicationBO
        $mortgageBO = new MortgageBO($this->logger, $this->db);

        // Call the getMortgageID method
        $mortgages = $mortgageBO->getMortgages($applicationId);

        // Prepare response
        $response = new Response();
        if($mortgages !== false) {
            $response->status = 'success';
            $response->data = $mortgages;
        } else {
            $response->status = 'error';
            $response->message = 'No mortgage ID found for the given application ID';
        }

        return response()->json($response, 200);
    }

    public function saveToBePaidOut(Request $request) {

        $mortgageId = $request->mortgageId;
        $toBePaidOut = $request->toBePaidOut;

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $res = $mortgageBO->saveToBePaidOut($mortgageId, $toBePaidOut);

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


}