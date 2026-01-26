<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\HttpRequest;
use App\Models\SalesforceCap;

class SalesforceBO {

    private $logger;
    private $db;
    private $amurEndpoint;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
        $this->amurEndpoint = env('AMUR_API_ENDPOINT');
    }

    public function syncApplication($applicationId) {

        $fields = [
            'applicationId' => $applicationId,
            'source' => "Strive"
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/application');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->syncApplication', [$applicationId]);
    }


    public function customerCare($mortgageId, $applicationId, $userId) {

        $fields = array(
            'mortgageId'    => $mortgageId,
            'applicationId' => $applicationId,
            'userId'        => $userId
        );

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/customer-care/funded-broker');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->customerCare', [$mortgageId, $applicationId, $userId]);
    }

    public function createMortgageFolder($mortgageId) {

        $fields = array(
            'mortgageId' => $mortgageId
        );

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/mortgage/create-mortgage-folder');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->customerCare', [$mortgageId]);

    }

    public function syncNewLendingFirm($id) {

        $fields = [
            'lendingFirmId' => $id
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/lender-company/new');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->syncNewLendingFirm', [$id]);
    }    

    public function syncNewAppraisalCompany($appraisalCompanyId) {

        $fields = [
            'appraisalCompanyId' => $appraisalCompanyId
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/appraisal-company/new');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->syncNewAppraisalCompany', [$appraisalCompanyId]);
    }

    public function getOppurtunityCAP() {
        try {
            $query = "select a.id, a.user_id, b.user_fname, b.user_lname, a.cap_limit 
                        from salesforce_cap a 
                   left join users_table b on a.user_id = b.user_id
                       where a.deleted_at IS NULL;";
            $res = $this->db->select($query);

            $data = array();
            foreach($res as $key => $value) {
                $data[] = [
                    'id' => $value->id,
                    'userId' => $value->user_id,
                    'userName' => $value->user_fname . ' ' . $value->user_lname,
                    'capLimit' => $value->cap_limit
                ];
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('SalesForceCapBO->getOppurtunityCAP - Error', [json_encode($e)]);
            return false;
        }
    }

    public function deleteOpportunityCAP($salesforceCapId) {
        try {
            if(empty($salesforceCapId) || !is_numeric($salesforceCapId)) {
                return false;
            }

            $salesforceCap = SalesforceCap::query()
            ->where('id', $salesforceCapId)
            ->first();

            if(!$salesforceCap) {
                return false;
            }

            $salesforceCap->delete();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('SalesForceCapBO->deleteOpportunityCAP - Error', [json_encode($e)]);
            return false;
        }
    }

    public function storeOpportunityCAPUser($userId, $capLimit) {
        $this->logger->info('SalesforceBO->storeOpportunityCAPUser', [$userId, $capLimit]);

        try {
            if(empty($capLimit) || !is_numeric($capLimit)) {
                return false;
            } else {
                $salesforceCap = SalesforceCap::query()
                ->where('user_id', $userId)
                ->first();

                if(!$salesforceCap) {
                    $salesforceCap = new SalesforceCap();
                    $salesforceCap->user_id = $userId;
                }

                $salesforceCap->cap_limit = $capLimit;
                $salesforceCap->save();
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('SalesForceCapBO->storeOpportunityCAPUser', [json_encode($e)]);
            return false;
        }
    }

    public function query($query) {
        $fields = [
            'q' => $query
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/salesforce/query');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = $httpRequest->getResponse();

        return json_decode($response);
    }

    public function syncQuote($savedQuoteId) {

        $fields = [
            'savedQuoteId' => $savedQuoteId
        ];

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($this->amurEndpoint . '/sync-quote');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();

        $this->logger->debug('SalesforceBO->syncQuote', [$savedQuoteId]);
    }
}