<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\FinanceBO;
use DateTime;

class FinanceController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function nsfToQb(Request $request) {
        $financeBO = new FinanceBO($this->logger, $this->db);
        $res = $financeBO->nsfToQb($request);

        $response = new Response();
        $response->status = 'success';
        $response->data = [
            'file' => $res,
            'name' => 'NSF-' . (new DateTime())->format('Y-m-d') . '.txt'
        ];

        return response()->json($response, 200);
    }

    public function nsfToNetsuite(Request $request) {
        $financeBO = new FinanceBO($this->logger, $this->db);
        $res = $financeBO->nsfToNetsuite($request);

        $response = new Response();
        $response->status = 'success';
        $response->message = 'NSF file generated successfully';
        $response->data = [
            'file' => $res,
            'name' => 'PAP-NSF ' . (new DateTime())->format('Y-m-d') . '.csv'
        ];

        return response()->json($response, 200);
    }
}
