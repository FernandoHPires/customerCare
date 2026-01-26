<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\ChecklistBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request, $applicationId, $checkListType, $objectId) {

        $this->logger->info('ChecklistController->index',[$applicationId, $checkListType, $objectId]);

        $checklistBO = new ChecklistBO($this->logger, $this->db);
        $res = $checklistBO->index($applicationId, $checkListType, $objectId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function saveAnswers(Request $request, $objectId) {

        $this->logger->info('ChecklistController->saveAnswers',[$objectId]);

        $userId = Auth::user()->user_id ?? 99;
        $fields = $request['questions'];

        $checklistBO = new ChecklistBO($this->logger, $this->db);
        $res = $checklistBO->saveAnswers($fields, $objectId, $userId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Checklist answers saved successfully';
        } else {
            $response->status = 'error';
            $response->message = 'Checklist answers could not be saved';
        }

        return response()->json($response, 200);
    }     
}