<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\PricebookBO;

class PricebookController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request) {
        $company  = $request->company;
        $position = $request->position;
        $province = $request->province;
        $cityClassification = $request->cityClassification;
        $display = $request->display;

        $priceBook = new PricebookBO($this->logger, $this->db);
        $res = $priceBook->index($company, $position, $province, $cityClassification, $display);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function newPricebook(Request $request) {
        $id = $request->id;
        $interestRate = $request->interestRate;
        $effectiveAt = $request->effectiveAt;

        $priceBook = new PricebookBO($this->logger, $this->db);
        $res = $priceBook->newPricebook($id, $interestRate, $effectiveAt);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }
    
    public function getByApplication(Request $request, $applicationId) {
        $ltv = $request->ltv;

        $pricebookBO = new PricebookBO($this->logger, $this->db);
        $res = $pricebookBO->getByApplication($applicationId, $ltv);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = implode(', ',$pricebookBO->getErrors());
        }
        return response()->json($response, 200);
    }

}
