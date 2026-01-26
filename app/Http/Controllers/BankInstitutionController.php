<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use Illuminate\Http\Request;
use App\Amur\BO\BankInstitutionBO;


class BankInstitutionController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function showByCode(Request $request, $code) {
        $bankInstitutionBO = new BankInstitutionBO($this->logger, $this->db);
        $institution = $bankInstitutionBO->showByCode($code);

        $response = new Response();
        if($institution !== false) {
            $response->status = 'success';
            $response->data = $institution;
        } else {
            $response->status = 'error';
        }
        
        return response()->json($response, 200);
    }
}