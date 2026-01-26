<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\LenderFirmBO;

class LenderFirmController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index() {
        try {

            $lenderFirms = new LenderFirmBO($this->logger, $this->db);
            $firms = $lenderFirms->index();

            $response = [
                "status" => "success",
                "message"=> count($firms)." Lender Firms found",
                "data" => $firms
            ];

            return response()->json($response, 200);

        }catch(\Exception $e){
            $this->logger->error('LenderFirmController->index - General Error', [json_encode($e)]);
            $data = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            return response()->json($data, 500);
        }
    }

    public function addLenderFirms(Request $request) {

        $firmName = $request->firmName;
        $abbreviation = $request->abbreviation;
        $comments = $request->commentsLender; 

        $lenderFirms = new LenderFirmBO($this->logger, $this->db);
        $res = $lenderFirms->addLenderFirms($firmName, $abbreviation, $comments);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Lender saved successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Lender cannot be saved';
        }

        return response()->json($response, 200);        
    }

}
?>