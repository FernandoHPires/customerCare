<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\SalesforceBO;

class SalesforceController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getOppurtunityCAP(Request $request) {
        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $result = $salesforceBO->getOppurtunityCAP();

        $response = new Response();
        if($result !== false){
            $response->status = 'success';
            $response->data = $result;
        } else {
            $response->status = 'error';
            $response->message = 'Failed to retrieve opportunity CAP data';
        }
        return response()->json($response, 200);
    }

    public function deleteOpportunityCAP(Request $request, $id) {

        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $result = $salesforceBO->deleteOpportunityCAP($id);

        $response = new Response();
        if($result !== false){
            $response->status = 'success';
            $response->message = 'Successfully deleted';
        } else {
            $response->status = 'error';
            $response->message = 'Failed to delete user';
        }
        return response()->json($response, 200);
    }

    public function storeOpportunityCAPUser(Request $request) {
        $userId = $request->userId;
        $capLimit = $request->capLimit;

        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $res = $salesforceBO->storeOpportunityCAPUser($userId, $capLimit);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            if(is_null($userId)) {
                $response->message = 'Successfully updated standard limit';
            } else {
                $response->message = 'Successfully added/updated user';
            }
        } else {
            $response->status = 'error';
            $response->message = 'Error - User could not be added/updated!';
        }
        return response()->json($response, 200);
    }
}
