<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\TrackerBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackerController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request) {
        $filters = $request->only([
            'dateOrdered',
            'dateOrderedOperator',
            'supportDate',
            'supportDateOperator',
            'accountingDate',
            'accountingDateOperator',
            'selectedUser',
            'applicationId',
        ]);

        $trackerBO = new TrackerBO($this->logger, $this->db);
        $res = $trackerBO->index($filters);
    
        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = implode(', ', $trackerBO->getErrors());
        }
        return response()->json($response, 200);
    }

    public function store(Request $request) {
        // Log the incoming request data
        $this->logger->info("TrackerController->store - Incoming save request", $request->all());

        // Validate input
        $validated = $request->validate([
            'application_id' => 'required|string',
            'mortgage_id' => 'nullable',
            'doc_type' => 'required|string',
            'broker_id' => 'required',
            'broker_notes' => 'nullable|string',
        ]);

        // Log the validated data
        $this->logger->info("TrackerController->store - Validated data", $validated);

        // Delegate to the TrackerBO for saving
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->store($validated);

        $response = new Response();
        if($result) {
            $response->status = 'success';
            $response->message = 'Record saved successfully';
        } else {
            $response->status = 'error';
            $response->message = implode(', ',$trackerBO->getErrors());
        }
        return response()->json($response, 200);
    }

    public function destroy(Request $request, $id) {
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->destroy($id);
        
        $this->logger->info("TrackerController->destroy - Validated data", ['result' => true]);
        $response = new Response();
        if ($result) {
            $response->status = 'success';
            $response->message = 'Document successfully deleted';
        } else {
            $response->status = 'error';
            $response->message = 'Error canceling the document';
        }
        return response()->json($response, 200);
    }
    
    public function updateAccountingStatus(Request $request, $id) {
        // Validate incoming request
        $validated = $request->validate([
            'accountingStatus' => 'required|string',
            'accountingId' => 'required|integer',
        ]);
    
        // Extract validated data
        $newStatus = $validated['accountingStatus'];
        $accountingId = $validated['accountingId'];
        $updatedBrokerNotes = $request->updatedBrokerNotes;
    
        // Log incoming data
        $this->logger->info("TrackerController->updateAccountingStatus - Updating accounting status", [
            'docId' => $id,
            'newStatus' => $newStatus,
            'accountingId' => $accountingId,
        ]);
    
        // Call the TrackerBO to update the accounting status
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $trackerBO->updateNotes($id, $updatedBrokerNotes);
        $result = $trackerBO->updateAccountingStatus($id, $newStatus, $accountingId);
    
        // Prepare the response object
        $response = new Response();
        if ($result) {
            $response->status = 'success';
            $response->message = 'Accounting status updated successfully.';
        } else {
            $response->status = 'error';
            $response->message = 'Error updating accounting status.';
        }
        return response()->json($response, 200);
    }

    public function updateSupportStatus(Request $request, $id) {
        $validated = $request->validate([
            'supportStatus' => 'required|string',
            'supportId' => 'required|integer', // Validate support_id
        ]);
    
        $newStatus = $validated['supportStatus'];
        $supportId = $validated['supportId'];
        $override = $request->override;
        $updatedBrokerNotes = $request->updatedBrokerNotes;
    
        $this->logger->info("TrackerController->updateSupportStatus - Updating support status", [
            'docId' => $id,
            'newStatus' => $newStatus,
            'supportId' => $supportId,
            'override' => $override,
        ]);
    
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $trackerBO->updateNotes($id, $updatedBrokerNotes);
        $result = $trackerBO->updateSupportStatus($id, $newStatus, $supportId, $override);
    
        // Prepare the response object
        $response = new Response();
        if ($result !== false) {
            $response->status = 'success';
            $response->message = 'Support status updated successfully.';
            $response->data = $result;
        } else {
            $response->status = 'error';
            $response->message = 'Error updating accounting status.';
        }
        return response()->json($response, 200);
    }

    public function getDocumentTypes(Request $request) {
        $userId = Auth::user()->user_id;

        $trackerBO = new TrackerBO($this->logger, $this->db);
        $res = $trackerBO->getDocumentTypes($userId);

        $response = new Response();
        if($res) {
            $response->status = 'success';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error getting document types';
        }
        return response()->json($response, 200);
    }

    public function getAllUsers(Request $request) {
        
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $users = $trackerBO->getAllUsers();
    
        $response = new Response();
        if(!empty($users)) {
            $response->status = 'success';
            $response->data = $users;
        } else {
            $response->status = 'error';
            $response->message = 'No users found.';
        }
        return response()->json($response, 200);
    }    
    
    public function generateReport(Request $request) {
        // Validate input parameters
        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'companyId' => 'nullable|integer',
        ]);

        // Extract validated values
        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];
        $companyId = $validated['companyId'] ?? null; 
    
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->generateReport($startDate, $endDate, $companyId);
    
        $response = new Response();
        if (!empty($result)) {
            $response->status = 'success';
            $response->message = 'Report generated successfully.';
            $response->data = $result; // Add the result data to the response
        } else {
            $response->status = 'error';
            $response->message = 'No data found for the given date range.';
        }
        return response()->json($response, 200);
    }

    public function getSupportDocsPerMonth(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->getSupportDocsPerMonth($year, $month);

        $response = new Response();
        $response->status = 'success';
        $response->data = $result;
        $response->message = empty($result)
            ? 'No support data found for this month.'
            : 'Monthly support document data retrieved.';

        return response()->json($response, 200);
    }

    public function getSupportFundingsPerWeek()
    {
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->getSupportFundingsPerWeek();

        $response = new Response();
        $response->status = 'success';
        $response->data = $result;
        $response->message = empty($result)
            ? 'No support data found.'
            : 'Weekly support funding data retrieved.';
        
        return response()->json($response, 200);
    }

    public function getReportPerDay(Request $request) {

          $year = $request->input('year');
          $month = $request->input('month');

        // Call the TrackerBO to get the report for the date range
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->getReportPerDay($year, $month);

        // Prepare the response object
        $response = new Response();
        if ($result) {
            $response->status = 'success';
            $response->data = $result;
            $response->message = 'Daily report retrieved successfully.';
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving daily report.';
        }

        return response()->json($response, 200);
    }

    public function updateNotes(Request $request, $docId) {
        // Validate the incoming request
        $validated = $request->validate([
            'brokerNotes' => 'nullable|string|max:1000',
        ]);
    
        // Extract broker notes
        $brokerNotes = $validated['brokerNotes'];
    
        // Call the TrackerBO to update broker notes
        $trackerBO = new TrackerBO($this->logger, $this->db);
        $result = $trackerBO->updateNotes($docId, $brokerNotes);
    
        // Prepare the response object
        $response = new Response();
        if ($result) {
            $response->status = 'success';
            $response->message = 'Broker notes updated successfully.';
        } else {
            $response->status = 'error';
            $response->message = 'Error updating broker notes.';
        }
    
        return response()->json($response, 200);
    }
}