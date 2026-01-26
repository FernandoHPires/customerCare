<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\MailingBO;
use Illuminate\Http\Request;

class MailingController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getMailingsSF(Request $request) {
        $opportunityId = $request->opportunityId;
        //$userId = $request->userId;

        $mailingBO = new MailingBO($this->logger, $this->db);
        $res = $mailingBO->getMailingsSF($opportunityId);
        
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
        }
        
        return response()->json($response, 200);
    }

    public function storeMailingsSF(Request $request) {
        $opportunityId = $request->opportunityId;
        //$userId = $request->userId;

        $mailingBO = new MailingBO($this->logger, $this->db);
        $res = $mailingBO->storeMailingsSF($opportunityId, $request->mailings);
        
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Success - Mailings saved!';
        } else {
            $response->status = 'error';
            $response->message = 'Error saving mailings!';
        }
        
        return response()->json($response, 200);
    }

    public function destroyMailingsSF(Request $request, $id) {

        $mailingBO = new MailingBO($this->logger, $this->db);
        $res = $mailingBO->destroyMailingsSF($id);
        
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Success - Mailings deleted!';
        } else {
            $response->status = 'error';
            $response->message = 'Error deleting mailing!';
        }
        
        return response()->json($response, 200);
    }

    public function getTitleHoldersSF(Request $request) {
        $opportunityId = $request->opportunityId;
        //$userId = $request->userId;

        $mailingBO = new MailingBO($this->logger, $this->db);
        $res = $mailingBO->getTitleHoldersSF($opportunityId);
        
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