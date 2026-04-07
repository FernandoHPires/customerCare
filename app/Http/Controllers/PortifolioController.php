<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\DB;
use App\AUni\Bean\Response;
use Illuminate\Http\Request;
use App\AUni\BO\PortifolioBO;

class PortifolioController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getPortfolio(Request $request) {


        $portifolioBO = new PortifolioBO($this->logger, $this->db);
        $res = $portifolioBO->getPortfolio();

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function getPortfolioView(Request $request) {

        $this->logger->info("PortifolioController->getPortfolioView");

        $portifolioBO = new PortifolioBO($this->logger, $this->db);
        $res = $portifolioBO->getPortfolioView();

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }    

   
    
}