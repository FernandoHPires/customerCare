<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\NoteBO;

class NoteController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function show(Request $request) {

        $noteId = $request->noteId;
        $opportunityId = $request->opportunityId;

        $noteBO = new NoteBO($this->logger, $this->db);
        $res = $noteBO->show($noteId, $opportunityId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not get notes';
        }
        return response()->json($response, 200);
    }

    public function store(Request $request) {

        $noteData = $request->noteData;
        $applicationId = $request->applicationId;

        $noteBO = new NoteBO($this->logger, $this->db);
        $res = $noteBO->store($applicationId, $noteData);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Note saved successfully';
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not save notes';
        }
        return response()->json($response, 200);
    }

    public function destroy(Request $request, $id) {

        $noteBO = new NoteBO($this->logger, $this->db);
        $res = $noteBO->destroy($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Note deleted successfully';
        } else {
            $response->status = 'error';
            $response->message = 'Error - Could not delete notes';
        }
        return response()->json($response, 200);
    }

}