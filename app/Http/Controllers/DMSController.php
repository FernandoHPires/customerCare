<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\DMSBO;
use DateTime;

class DMSController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request) {
        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->index();

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function store(Request $request) {
        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->store($request);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Success - Template uploaded!';
        } else {
            $response->status = 'error';
            $response->message = 'Error - File could not be uploaded!';
        }

        return response()->json($response, 200);
    }

    public function download(Request $request, $id) {
        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->download($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - File could not download file!';
        }

        return response()->json($response, 200);
    }

    public function destroy(Request $request, $id) {
        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->destroy($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Success - Template deleted!';
        } else {
            $response->status = 'error';
            $response->message = 'Error - File could not be deleted!';
        }

        return response()->json($response, 200);
    }

    public function getTemplatesApproval(Request $request) {

        $this->logger->info('DMSController->getTemplatesApproval',[$request->all()]);

        $status    = $request->status;
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->getTemplatesApproval($startDate, $endDate, $status);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }
    
    public function storeS3(Request $request) {

        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->storeS3($request);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Success - Template sent for approval!';
        } else {
            $response->status = 'error';
            $response->message = 'Error - File could not be uploaded!';
        }

        return response()->json($response, 200);
    }
    
    public function downloadApproval(Request $request, $id) {

        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->downloadApproval($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - File could not download file!';
        }

        return response()->json($response, 200);
    }

    public function setupTemplateApproval(Request $request) {

        $id     = $request->id;
        $status = $request->status;
        $reason = $request->reason;

        $dmsBO = new DMSBO($this->logger, $this->db);
        $res = $dmsBO->setupTemplateApproval($id, $status, $reason);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            if ($status == 'a') {
                $response->message = 'Template Approved!';
            } else {
                $response->message = 'Template Rejected!';
            }            
        } else {
            $response->status = 'error';
            $response->message = 'Template cannot be updated!';
        }

        return response()->json($response, 200);
    }

    
    
}