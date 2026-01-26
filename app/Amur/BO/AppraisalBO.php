<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationTable;
use App\Amur\Utilities\HttpRequest;
use App\Amur\Utilities\Utils;
use App\Models\AlpineCompaniesTable;
use Illuminate\Support\Facades\Auth;

class AppraisalBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db){
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDocuments($applicationId) {
        $this->logger->info('AppraisalBO->getDocuments',[$applicationId]);

        $application = ApplicationTable::find($applicationId);
        if(!$application) {
            $this->logger->info('AppraisalBO->getDocuments - Could not find application',[$applicationId]);
            return false;
        }

        $company = AlpineCompaniesTable::find($application->company);
        if(!$company) {
            $this->logger->info('AppraisalBO->getDocuments - Could not find company',[$application->company]);
            return false;
        }

        $companyFolder = $company->folder . 'TACL';

        $fields = array(
            'applicationId' => $applicationId,
            'folderName'    => $companyFolder,
            'path'          => ''
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/sharepoint/list-folder');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        $this->logger->debug('AppraisalBO->getDocuments',[$amurEndpoint,$response]);

        $documents = array();
        if(isset($response->data) && $response->data !== false) {
            foreach($response->data as $key => $value) {
                //only pdf files
                if(!Utils::endsWith(strtolower($value->fileName), '.pdf')) {
                    continue;
                }
                
                $documents[] = [
                    'name' => $value->fileName,
                    'serverRelativeUrl' => $value->serverRelativeUrl,
                    'uniqueId' => $value->uniqueId,
                ];
            }
        }

        usort($documents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $documents;
    }

    public function processDocument($uniqueId) {

        $fields = array(
            'uniqueId' => $uniqueId
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/appraisal/process');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        if(isset($response->status) && $response->status === 'error') {
            $this->logger->error('AppraisalBO->processDocument - Error processing document', [$response->message]);
            return false;
        }

        return $response->data ?? [];
    }

}
