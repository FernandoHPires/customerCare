<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\PayoutBO;
use App\Amur\BO\ForeclosureBO;
use DateTime;

class PayoutController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getPayouts(Request $request) {
        $companyId = $request->companyId;
        $startDate = new DateTime($request->startDate);
        $endDate = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->getPayouts($companyId, $startDate, $endDate);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!!';
        }

        return response()->json($response, 200);
    }
   
    public function acceptPayout(Request $request, $id) {
        $payout = new PayoutBO($this->logger, $this->db);
        $res    = $payout->acceptPayout($id);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout approved';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be approved';
        }
        
        return response()->json($response, 200);
    }

    public function rejectPayout(Request $request, $id) {       

        $rejectReason = $request->rejectReason;

        $payout  = new PayoutBO($this->logger, $this->db);
        $res     = $payout->rejectPayout($id, $rejectReason);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout rejected';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be rejected';
        }
        
        return response()->json($response, 200);
    }

    public function createPayout(Request $request) {
        $userId     = $request->user_id;
        $mortgageId = $request->mortgage_id;
        $payoutId   = $request->payout_id;

        $payout  = new PayoutBO($this->logger, $this->db);
        $res     = $payout->createPayout($userId,$mortgageId,$payoutId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout created successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout cannot be created';
        }

        return response()->json($response, 200);
    }    

    public function getProcessPayout(Request $request) {
        $companyId = $request->companyId;
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->getProcessPayout($companyId, $startDate, $endDate);     
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!!';
        }

        return response()->json($response, 200);
    }

    public function processPayout(Request $request) {
        $id = $request->id;
        $amountReceived = $request->amountReceived;
        $paymentReceivedDate = new DateTime($request->paymentReceivedDate);

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->processPayout($id, $amountReceived, $paymentReceivedDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout processed successfully!';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be processed!';
        }

        return response()->json($response, 200);
    }    

    public function cancelPayout(Request $request) {
        $id            = $request->id; 
        $mortgageId    = $request->mortgage_id;
        $payoutId      = $request->payout_id;
        $comment       = $request->comment;
        
        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->cancelPayout($id,$mortgageId,$payoutId,$comment);
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout canceled successfully!';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be canceled!';
        }
        
        return response()->json($response, 200);
    }

    public function getCancelPayout(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->getCancelPayout($startDate,$endDate);     
        
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!!';
        }
        
        return response()->json($response, 200);
    }

    public function calculatePayout(Request $request) {
        $paymentReceivedDate = $request->paymentReceivedDate;

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->calculatePayout($request->id, new DateTime($paymentReceivedDate));
       
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be calculated!';
        }
        
        return response()->json($response, 200);
    }

    public function sendLawyer(Request $request, $id) {

        $payout = new PayoutBO($this->logger, $this->db);
        $res = $payout->sendLawyer($id);
       
        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Payout sent to lawyer!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payout could not be sent to lawyer!';
        }
        
        return response()->json($response, 200);
    }

    public function getForeclosures(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new ForeclosureBO($this->logger, $this->db);
        $res = $payout->getForeclosures($startDate, $endDate);     

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function getProcessForeclosure(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new ForeclosureBO($this->logger, $this->db);
        $res = $payout->getProcessForeclosure($startDate, $endDate);     

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function getCancelForeclosures(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $payout = new ForeclosureBO($this->logger, $this->db);
        $res = $payout->getCancelForeclosure($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }    

    public function cancelForeclosure(Request $request) {
        $id            = $request->id; 
        $mortgageId    = $request->mortgage_id;
        $payoutId      = $request->payout_id;
        $comment       = $request->comment;

        $payout = new ForeclosureBO($this->logger, $this->db);
        $res = $payout->cancelForeclosure($id,$mortgageId,$payoutId,$comment);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Foreclosure canceled successfully!';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Foreclosure cannot be canceled!';
        }

        return response()->json($response, 200);
    }

    public function foreclosureReject(Request $request) {
        $id            = $request->id; 
        $mortgageId    = $request->mortgage_id;
        $payoutId      = $request->payout_id;
        $comment       = $request->comment;       

        $payout = new ForeclosureBO($this->logger, $this->db);
        $res = $payout->foreclosureReject($id,$mortgageId,$payoutId,$comment);     

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Foreclosure Rejected Successfully!';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Foreclosure cannot be Rejected!';
        }

        return response()->json($response, 200);
    }
}
