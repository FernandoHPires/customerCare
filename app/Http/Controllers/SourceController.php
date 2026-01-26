<?php

namespace App\Http\Controllers;

use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\SourceBO;

class SourceController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }


    public function getSources() {
        $sourceBO = new SourceBO($this->logger);
        $sources = $sourceBO->getAllData();

        $response = new Response();
        if (!empty($sources)) {
            $response->status = 'success';
            $response->message = 'Sources retrieved successfully';
            $response->data = $sources;
        } else {
            $response->status = 'error';
            $response->message = 'No sources found';
            $response->data = []; 
        }
    
        return response()->json($response, 200);
    }
}