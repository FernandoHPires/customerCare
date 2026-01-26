<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\InitializationBO;

class InitializationController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function checkActiveQuotes(Request $request) {    
        $this->logger->info('InitializationController->checkActiveQuotes ',[$request->all()]);

        $opportunityId = $request->opportunityId;

        $initializationBO = new InitializationBO($this->logger, $this->db);
        $response = $initializationBO->checkActiveQuotes($opportunityId);
        
        return response()->json($response, 200);
    }

    public function quoteSelected(Request $request) {
        $this->logger->info('InitializationController->quoteSelected ',[$request->all()]);

        $applicationId = $request->applicationId;
        $savedQuoteId  = $request->savedQuoteId;

        $initializationBO = new InitializationBO($this->logger, $this->db);
        $response = $initializationBO->quoteSelected($applicationId,$savedQuoteId);

        return response()->json($response, 200);
    }

    public function initialize(Request $request) { 
        $this->logger->info('InitializationController->initialize ',[$request->all()]);

        $initializationBO = new InitializationBO($this->logger, $this->db);
        $res = $initializationBO->initialize($request->all());

        if($res !== false) {
            if(isset($res['status'])) {
                if($res['status'] == 'error') {
                    $response = new Response();
                    $response->status  = 'error';
                    $response->message = $res['message'];
                }
            } else {
                $response = new Response();
                $response->status  = 'success';
                $response->message = 'Mortgage Created Successfully!';
                $response->data    = $res;
            }

        } else {
            $response = new Response();
            $response->status  = 'error';
            $response->message = 'Initialization Cannot be Completed!';
        }

        return response()->json($response, 200);
    }
}