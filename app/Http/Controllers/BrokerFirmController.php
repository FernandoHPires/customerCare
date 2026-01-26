<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\BrokerFirmBO;
use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;

class BrokerFirmController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index() {
        try {

            $brokerFirms = new BrokerFirmBO($this->logger, $this->db);
            $firms = $brokerFirms->index();

            $response = [
                "status" => "success",
                "message"=> count($firms)." Broker Firms found",
                "data" => $firms
            ];

            return response()->json($response, 200);

        }catch(\Exception $e){
            $this->logger->error('BrokerFirmController->index - General Error', [json_encode($e)]);
            $data = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            return response()->json($data, 500);
        }
    }
    public function brokerDetails($code)
    {
        $this->logger->info('BrokerFirmController->brokerDetails');
        $brokerFirmCode = $code;
        $brokerFirms = new BrokerFirmBO($this->logger, $this->db);
        $res = $brokerFirms->brokerDetailInformation($brokerFirmCode);
        if ($res != false) {
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

    public function addBrokerFirms(Request $request) {

        $firmName = $request->firmNameInsurance;
        $comments = $request->commentsInsurance;

        if($comments == null){
            $comments = '';
        }

        $brokerFirms = new BrokerFirmBO($this->logger, $this->db);
        $res = $brokerFirms->addBrokerFirms($firmName, $comments);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Insurance Broker saved successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Insurance Broker cannot be saved';
        }

        return response()->json($response, 200);        
    }

}
?>