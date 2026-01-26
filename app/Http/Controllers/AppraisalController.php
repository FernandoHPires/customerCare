<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\AppraisalBO;
use Illuminate\Http\Request;

class AppraisalController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getDocuments(Request $request) {

        $applicationId = $request->applicationId;

        $this->logger->info('AppraisalController->getDocuments',[$applicationId]);

        $appraisalBO = new AppraisalBO($this->logger, $this->db);
        $documents = $appraisalBO->getDocuments($applicationId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $documents;

        return response()->json($response, 200);
    }

    public function processDocument(Request $request) {

        $uniqueId = $request->uniqueId;

        $this->logger->info('AppraisalController->processDocument',[$uniqueId]);

        $appraisalBO = new AppraisalBO($this->logger, $this->db);
        $appraisalData = $appraisalBO->processDocument($uniqueId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $appraisalData;

        return response()->json($response, 200);
    }
}