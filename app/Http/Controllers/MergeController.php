<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\MergeDocumentsBO;
use App\Amur\BO\ApplicationBO;


class MergeController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getDocuments(Request $request) {

        $opportunityId = $request->opportunityId;

        $this->logger->info('MergeController->getDocuments',[$opportunityId]);

        $mergeBO = new MergeDocumentsBO($this->logger, $this->db);
        $documents = $mergeBO->getDocuments($opportunityId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $documents;

        return response()->json($response, 200);
    }

    public function getOtherDocuments(Request $request) {

        $opportunityId = $request->opportunityId;

        $this->logger->info('MergeController->getOtherDocuments',[$opportunityId]);

        $mergeBO = new MergeDocumentsBO($this->logger, $this->db);
        $documents = $mergeBO->getOtherDocuments();

        $response = new Response();
        $response->status = 'success';
        $response->data = $documents;

        return response()->json($response, 200);
    }

    public function mergeDocuments(Request $request) {
        
        $opportunityId = $request->opportunityId;
        $docsToMerge   = $request->docsToMerge;
        $type          = $request->type ?? 'merge';
        $applicants    = $request->applicants ?? [];

        $this->logger->info('MergeController->mergeDocuments',[$request->all()]);

        $mergeBO = new MergeDocumentsBO($this->logger, $this->db);
        $res = $mergeBO->mergeDocuments($opportunityId, $docsToMerge, $type, $applicants);

        foreach($res as $key => $value) {
            if($key == 'status') {
                if ($value == 'success') {
                    $response = new Response();
                    $response->status  = 'success';
                    $response->message = 'Merge completed';
                } else {
                    $response = new Response();
                    $response->status = 'error';
                    $response->message = 'Merge could not be completed';  
                }
            }
        }

        return response()->json($response, 200);
    }

    public function viewDocuments(Request $request) {
        
        $opportunityId = $request->opportunityId;

        $this->logger->info('MergeController->viewDocuments',[$request->all()]);

        $mergeBO = new MergeDocumentsBO($this->logger, $this->db);
        $res = $mergeBO->viewDocuments($opportunityId);

        $applicationBO = new ApplicationBO($this->logger, $this->db);
        $companyID = $applicationBO->getCompanyByApplicationId($res);

        if(is_null($companyID)){
            $this->logger->error('MergeController->viewDocuments Application ID not found',[$request->all()]);
        }

        if($res !== false) {
            $response = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data = [
                'applicationId' => $res,
                'companyId' => $companyID
            ];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Documents not found';
        }

        return response()->json($response, 200);
    }

    public function getApplicantsEmail(Request $request) {

        $opportunityId = $request->opportunityId;

        $this->logger->info('MergeController->getApplicantsEmail',[$request->all()]);

        $mergeBO = new MergeDocumentsBO($this->logger, $this->db);
        $res = $mergeBO->getApplicantsEmail($opportunityId);

        if($res !== false) {
            $response = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Documents not found';
        }

        return response()->json($response, 200);
    }

}