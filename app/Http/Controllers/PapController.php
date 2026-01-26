<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\Response;
use App\Amur\BO\PapBO;
use App\Amur\BO\PapFileBO;
use App\Amur\BO\PapFileDailyReportBO;
use App\Models\MortgageTable;
use DateTime;
use App\Models\PapFile;

class PapController extends Controller {

    private $logger;
    private $db;
    private $papFileBO;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
        $this->papFileBO = new PapFileBO($this->logger, $this->db);
    }

    public function getTransactions(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate = new DateTime($request->endDate);
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);
        $companyId = $request->companyIdFilter;
        $category = $request->category;

        $papBO = new PapBO($this->logger, $this->db);
        $transactions = $papBO->getTransactions($startDate, $endDate, $companyId, $category);

        $alerts = $papBO->getPapAlerts();

        $response = new Response();
        $response->status = 'success';
        $response->data = [
            'transactions' => $transactions,
            'alerts' => $alerts
        ];

        return response()->json($response, 200);
    }

    public function getUpdates(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);
        $companyId = $request->companyIdFilter;

        $papBO = new PapBO($this->logger, $this->db);
        $updates = $papBO->getUpdates($startDate, $endDate, $companyId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $updates;

        return response()->json($response, 200);
    }

    public function getTransactionDetails(Request $request, $id) {
        $papBO = new PapBO($this->logger, $this->db);
        $res = $papBO->getTransactionDetails($id, $request->category);

        $response = new Response();
        $response->status = 'success';
        $response->data = $res;

        return response()->json($response, 200);
    }

    public function processTransactionWeb(Request $request, $id) {

        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->processTransaction($id, 'web')) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Transaction was processed!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Transaction could not be processed!';
        }

        return response()->json($response, 200);
    }

    public function processTransactionApi(Request $request, $id) {
        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->processTransaction($id, 'api')) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Transaction was processed!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Transaction could not be processed!';
        }

        return response()->json($response, 200);
    }

    public function rejectTransaction(Request $request, $id) {
        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->rejectTransaction($id, $request->rejectReason)) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Transaction was rejected!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Transaction could not be rejected!';
        }

        return response()->json($response, 200);
    }

    public function rejectBankInfo(Request $request, $id) {
        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->rejectBankInfo($id, $request->rejectReason)) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Transaction was rejected!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Transaction could not be rejected!';
        }

        return response()->json($response, 200);
    }

    public function validadeBankInfo(Request $request, $id) {
        $userId = Auth::user()->user_id;

        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->validadeBankInfo($id, $userId)) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Bank information was validated!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Bank information could not be validated!';
        }

        return response()->json($response, 200);
    }

    public function updateBankInfo(Request $request, $id) {
        $request->validate([
            'id' => 'required',
            'transit' => 'required|max:10',
            'institutionCode' => 'required|max:10',
            'account' => 'required|max:15'
        ]);

        $papBO = new PapBO($this->logger, $this->db);

        if ($papBO->updateBankInfo($id, $request->transit, $request->institutionCode, $request->account)) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Bank information was changed!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Bank information could not be changed!';
        }

        return response()->json($response, 200);
    }

    public function getFiles(Request $request) {
        $startDate = new DateTime($request->startDate);
        $startDate->setTime(0, 0, 0);
        $endDate = new DateTime($request->endDate);
        $endDate->setTime(23, 59, 59);
        $type = $request->type;
        $companyId = $request->companyIdFilter;

        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->getFiles($type, $startDate, $endDate, $companyId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = ['files' => $res['files'], 'lastFileStatus' => $res['lastFileStatus']];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function getPayments(Request $request, $companyId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $payments = $papFileBO->getPayments($companyId);

        if ($payments !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $payments;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payments could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function getSummary(Request $request, $companyId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $summary = $papFileBO->getSummary($companyId);

        if ($summary !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $summary;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Payments could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function requestFile(Request $request, $companyId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->requestFile($companyId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'File requested. You can download it in few minutes.';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'File could not be requested!';
        }

        return response()->json($response, 200);
    }

    public function downloadFile(Request $request, $papFileId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->downloadFile($papFileId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = ['uri' => $res];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'File does not exist!';
        }

        return response()->json($response, 200);
    }

    public function uploadFile(Request $request) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->uploadFile($request);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Success - The bank credit file should be done in a moment!';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error - File could not be uploaded!';
        }

        return response()->json($response, 200);
    }

    public function quickbooks(Request $request, $papFileId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->quickbooks($papFileId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = ['file' => $res];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'File could not be created';
        }
        return response()->json($response, 200);
    }

    public function netsuite(Request $request, $papFileId) {
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $res = $papFileBO->netsuite($papFileId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'File could not be created';
        }
        return response()->json($response, 200);
    }

    public function transactionNotification(Request $request, $id) {
        $papBO = new PapBO($this->logger, $this->db);
        $res = $papBO->transactionNotification($id);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Notification could not be sent';
        }
        return response()->json($response, 200);
    }

    public function bankInfoReminderEmail(Request $request) {
        $papFileDailyReport = new PapFileDailyReportBO($this->logger, $this->db);
        $papFileDailyReport->bankInfoReminder();
    }

    public function institutionName(Request $request) {

        $institutionCod = $request->institutionCod;

        $papBO = new PapBO($this->logger, $this->db);
        $res   = $papBO->institutionName($institutionCod);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Institution not found!';
        }

        return response()->json($response, 200);
    }

    public function updatePayments(Request $request) {
        
        $id = $request->id;
        $transactionId = $request->transactionId;
        $paymentStatus = $request->paymentStatus ?? '';
        $newPaymentDate = $request->newPaymentDate ?? null;

        $papBO = new PapBO($this->logger, $this->db);
        $res = $papBO->updatePayments($id, $transactionId, $paymentStatus, $newPaymentDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Institution not found!';
        }
        

        return response()->json($response, 200);
    }

}
