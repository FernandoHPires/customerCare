<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Amur\Utilities\HttpRequest;
use Illuminate\Support\Facades\Auth;

class ApiBO {

    private $logger;
    private $amurEndpoint;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
        $this->amurEndpoint = env('AMUR_API_ENDPOINT');
    }

    public function readyBuy($applicationId, $userId) {
        $this->logger->debug('ApiBO->readyBuy', [$applicationId, $userId]);

        $fields = [
            'applicationId' => $applicationId,
            'userId' => $userId,
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/ready-buy');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();
    }

    public function notes($noteId) {
        $this->logger->debug('ApiBO->notes', [$noteId]);

        $fields = [
            'noteId' => $noteId
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/note');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();
    }

    public function destroyNotes($noteId) {
        $this->logger->debug('ApiBO->destroyNotes', [$noteId]);

        $fields = [
            'noteId' => $noteId
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/destroy-note');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();
    }

    public function recreateDocuments($applicationDocumentId, $documentId, $applicationId, $mortgageId) {
        
        $this->logger->debug('ApiBO->recreateDocuments', [$applicationDocumentId, $documentId, $applicationId, $mortgageId]);

        $userId = Auth::user()->user_id;

        $fields = [
            'applicationDocumentId' => $applicationDocumentId,
            'documentId' => $documentId,
            'applicationId' => $applicationId,
            'mortgageId' => $mortgageId,
            'userId' => $userId
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/webhook/renewal/create-documents');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        $this->logger->info("Full recreateDocuments Response Body", [$httpRequest->getResponse()]);

        if(isset($response->status) && $response->status == "success") {
            return true;
        }

        return false;
    }

    public function sendDocuments($applicationDocumentId, $documentId, $applicationId, $mortgageId, $applicants) {
        
        $this->logger->debug('ApiBO->sendDocuments', [$applicationDocumentId, $documentId, $applicationId, $mortgageId, $applicants]);

        $userId = Auth::user()->user_id;

        $fields = [
            'applicationDocumentId' => $applicationDocumentId,
            'documentId' => $documentId,
            'applicationId' => $applicationId,
            'mortgageId' => $mortgageId,
            'userId' => $userId,
            'applicants' => $applicants
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/webhook/renewal/send-documents');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        if(isset($response->status) && $response->status == "success") {
            return true;
        }

        return false;
    }


}