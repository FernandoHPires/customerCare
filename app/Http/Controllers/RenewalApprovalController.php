<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use App\Amur\Bean\Response;
use App\Amur\BO\RenewalApprovalBO;
use App\Amur\BO\ApiBO;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RenewalApprovalController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function getRenewals(Request $request) {
        $this->logger->info('RenewalController->getRenewals',[$request->all()]);

        if($request->endDate) {
            $endDate = $request->endDate;
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getRenewals($endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getRenewalsCount(Request $request) {
        $this->logger->info('RenewalController->getRenewalsCount',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getRenewalsCount($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'New renewals count could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getInProgressRenewals(Request $request) {
        $this->logger->info('RenewalController->getInProgressRenewals',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate  . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getInProgressRenewals($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'In progress renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getInProgressRenewalsCount(Request $request) {
        $this->logger->info('RenewalController->getInProgressRenewalsCount',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getInProgressRenewalsCount($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'In progress renewals count could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getPendingReviews(Request $request) {
        $this->logger->info('RenewalController->getPendingReviews',[$request->all()]);

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getPendingReviews();

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Pending renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getPendingReviewsCount(Request $request) {
        $this->logger->info('RenewalController->getPendingReviewsCount',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getPendingReviewsCount($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Pending renewals count could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getProcessedRenewals(Request $request) {
        $this->logger->info('RenewalController->getProcessedRenewals',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getProcessedRenewals($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Historic renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getProcessedRenewalsCount(Request $request) {
        $this->logger->info('RenewalController->getProcessedRenewalsCount',[$request->all()]);

        if($request->startDate) {
            $startDate = $request->startDate;
        } else {
            $startDate = '0001-01-01';
        }

        if($request->endDate) {
            $endDate = $request->endDate . ' 23:59:59';
        } else {
            $endDate = '9999-12-31';
        }

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getProcessedRenewalsCount($startDate, $endDate);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Processed renewals count could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getApprovedRenewals(Request $request) {
        $this->logger->info('RenewalController->getApprovedRenewals',[$request->all()]);

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getApprovedRenewals();

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getBrokerRequestedRenewals(Request $request) {
        $this->logger->info('RenewalController->getBrokerRequestedRenewals',[$request->all()]);

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getBrokerRequestedRenewals();

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewals could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getCategories(Request $request) {
        $this->logger->info('RenewalController->getCategories',[$request->all()]);

        $categoryId = $request->categoryId;

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->getCategories($categoryId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Director review categories could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function calculate(Request $request) {
        $this->logger->info('RenewalController->calculate',[$request->all()]);

        $mortgageId = $request->mortgageId ?? null;
        $renewalId = $request->renewalId ?? null;
        $calculateRenewalData = new RenewalApprovalBO($this->logger, $this->db);
        $res = $calculateRenewalData->calculate($renewalId, $mortgageId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Data could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getDocuments(Request $request) {

        $this->logger->info('RenewalController->getDocuments',[$request->all()]);

        $applicationId = $request->applicationId ?? null;
        $mortgageId = $request->mortgageId ?? null;
        $renewalApprovalId = $request->renewalApprovalId ?? null;

        $documents = new RenewalApprovalBO($this->logger, $this->db);
        $res = $documents->getDocuments($applicationId, $mortgageId, $renewalApprovalId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Documents could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function getDocumentSentDate(Request $request) {

        $this->logger->info('RenewalController->getDocumentSentDate',[$request->all()]);

        $mortgageId = $request->mortgageId ?? null;
        $renewalId = $request->renewalId ?? null;

        $documents = new RenewalApprovalBO($this->logger, $this->db);
        $res = $documents->getDocumentSentDate($mortgageId, $renewalId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Document Sent Date could not be acquired';
        }

        return response()->json($response, 200);
    }

    public function setDocumentSentDate(Request $request) {

        $this->logger->info('RenewalController->setDocumentSentDate',[$request->all()]);

        $mortgageId = $request->mortgageId ?? null;
        $renewalId = $request->renewalId ?? null;

        $documents = new RenewalApprovalBO($this->logger, $this->db);
        $res = $documents->setDocumentSentDate($mortgageId, $renewalId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Document sent date could not be set';
        }

        return response()->json($response, 200);
    }

    public function recreateDocuments(Request $request) {

        $this->logger->info('RenewalController->recreateDocuments',[$request->all()]);

        $applicationDocumentId = $request->applicationDocumentId;
        $documentId = $request->documentId;
        $applicationId = $request->applicationId;
        $mortgageId = $request->mortgageId;


        $apiBO = new ApiBO($this->logger);
        $res = $apiBO->recreateDocuments($applicationDocumentId, $documentId, $applicationId, $mortgageId);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Documents could not be recreated';
        }

        return response()->json($response, 200);
    }

    public function sendDocuments(Request $request) {

        $this->logger->info('RenewalController->sendDocuments',[$request->all()]);

        $applicationDocumentId = $request->applicationDocumentId;
        $documentId = $request->documentId;
        $applicationId = $request->applicationId;
        $mortgageId = $request->mortgageId;
        $applicants = $request->applicants ?? [];

        $apiBO = new ApiBO($this->logger);
        $res = $apiBO->sendDocuments($applicationDocumentId, $documentId, $applicationId, $mortgageId, $applicants);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Documents sent successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Documents could not be sent';
        }

        return response()->json($response, 200);
    }

    public function brokerRequest(Request $request) {
        $this->logger->info('RenewalController->brokerRequest',[$request->all()]);

        $renewalApprovalId = $request->renewalApprovalId;
        $emailObj = $request->emailObj ?? null;

        $remewaApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $remewaApprovalBO->brokerRequest($renewalApprovalId, $emailObj);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Renewal successfully requested to broker';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewal was not requested to broker';
        }

        return response()->json($response, 200);
    }

    public function brokerApproval(Request $request) {
        $this->logger->info('RenewalController->brokerApproval',[$request->all()]);

        $renewalApprovalId = $request->renewalApprovalId;
        $emailObj = $request->emailObj ?? null;

        $remewaApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $remewaApprovalBO->brokerApproval($renewalApprovalId, $emailObj);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Renewal successfully approved by broker';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewal was not approved by broker';
        }

        return response()->json($response, 200);
    }

    public function insert(Request $request) {
        $this->logger->info('RenewalController->insert',[$request->all()]);

        $renewalApproval = $request->renewalApproval;
        $mortgageRenewal = $request->mortgageRenewal;
        
        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewalApprovalBO->insert($renewalApproval, $mortgageRenewal);

        if($res['status'] !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = $res['message'];
            $response->data = $res['data'];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = $res['message'];
        }

        return response()->json($response, 200);
    }

    public function nonRenewal(Request $request) {
        $this->logger->info('RenewalController->nonRenewal',[$request->all()]);

        $renewalApproval = $request->renewalApproval;
        
        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewalApprovalBO->nonRenewal($renewalApproval);

        if($res['status'] !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = $res['message'];
            $response->data = $res['data'];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = $res['message'];
        }

        return response()->json($response, 200);
    }

    public function pending(Request $request) {
        $this->logger->info('RenewalController->pending',[$request->all()]);

        $renewalApproval = $request->renewalApproval;
        
        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewalApprovalBO->pending($renewalApproval);

        if($res['status'] !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = $res['message'];
            $response->data = $res['data'];
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = $res['message'];
        }

        return response()->json($response, 200);
    }

    public function insertFilterOption(Request $request) {
        $this->logger->info('RenewalController->insertFilterOption',[$request->all()]);

        $filterName = $request->filterName;
        $filterOptions = $request->filterOptions;
        
        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewalApprovalBO->insertFilterOption($filterName, $filterOptions);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res === true ? null : $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Filter could not be saved';
        }

        return response()->json($response, 200);
    }

    public function getFilterOption(Request $request) {
        $this->logger->info('RenewalController->getFilterOption',[$request->all()]);

        $filterName = $request->filterName;
        
        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewalApprovalBO->getFilterOption($filterName);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Filter Options could not be retrieved';
        }

        return response()->json($response, 200);
    }

    public function generateExcelFile(Request $request){
        $filterOptions = $request->filterOptions;
        $pageName = $request->pageName ?? null;

        $renewalApprovalBO = new RenewalApprovalBO($this->logger, $this->db);
        $spreadsheet = $renewalApprovalBO->generateExcelFile($filterOptions, $pageName);

        if (!$spreadsheet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Excel file could not be generated'
            ], 500);
        }

        $filename = 'renewals_' . time() . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');  // stream directly
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }


    public function update(Request $request) {
        $this->logger->info('RenewalController->update',[$request->all()]);

        $mortgageRenewal = $request->mortgageRenewal;

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->update($mortgageRenewal);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Mortgage successfully updated';
            $response->data = $res === true ? null : $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Renewal could not be updated';
        }

        return response()->json($response, 200);
    }

    public function assignAgents(Request $request) {
        $this->logger->info('RenewalController->assignAgents',[$request->all()]);

        $selectedRenewalsId = $request->selectedRenewalsId ?? null;
        $brokerId = $request->brokerId ?? null;
        $emailObj = $request->emailObj ?? null;

        $renewal = new RenewalApprovalBO($this->logger, $this->db);
        $res = $renewal->assignAgents($selectedRenewalsId, $brokerId, $emailObj);

        if($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Agents successfully assigned';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Agents not assigned';
        }

        return response()->json($response, 200);
    }
}
