<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationTable;
use App\Amur\Utilities\HttpRequest;
use App\Amur\Utilities\Utils;
use App\Models\AlpineCompaniesTable;
use Illuminate\Support\Facades\Auth;

class MergeDocumentsBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db){
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDocuments($opportunityId) {

        $applicationId = $this->getApplicationId($opportunityId);
        
        $this->logger->info('MergeDocumentsBO->getDocuments',[$opportunityId, $applicationId]);

        $application = ApplicationTable::find($applicationId);

        if(!$application) {
            $this->logger->info('MergeDocumentsBO->getDocuments - Could not find application',[$applicationId]);
            return false;
        }

        $company = AlpineCompaniesTable::find($application->company);
        if(!$application) {
            $this->logger->info('MergeDocumentsBO->getDocuments - Could not find company',[$application->company]);
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

        $documents = array();
        if(isset($response->data) && $response->data !== false) {
            foreach($response->data as $key => $value) {
                $documents[] = [
                    'name' => $value->fileName,
                    'serverRelativeUrl' => $value->serverRelativeUrl
                ];
            }
        }

        usort($documents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $documents;
    }

    public function getOtherDocuments() {

        $fields = array(
            'applicationId' => 0,
            'folderName'    => 'TEMPLATES/OpportunityMgmt/Merge',
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

        $documents = array();
        if(isset($response->data) && $response->data !== false) {
            foreach($response->data as $key => $value) {
                $documents[] = [
                    'name' => $value->fileName,
                    'serverRelativeUrl' => $value->serverRelativeUrl
                ];
            }
        }

        usort($documents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $documents;
    }

    public function mergeDocuments($opportunityId, $docsToMerge, $type, $applicants) {

        $applicationId = $this->getApplicationId($opportunityId);

        $this->logger->debug('MergeDocumentsBO->mergeDocuments',[$applicationId, $docsToMerge]);

        foreach($docsToMerge as $key => $value) {
            $documents[] = [
                'serverRelativeUrl' => $value['serverRelativeUrl'],
                'name'              => $value['name']
            ];
        }

        $this->logger->debug('MergeDocumentsBO->mergeDocuments',[$documents]);

        $fields = array(
            'applicationId' => $applicationId,
            'documents'     => $documents,
            'type'          => $type,
            'applicants'    => $applicants,
            'userId'        => Auth::user()->user_id,
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/merge/merge-documents');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        return $response;
    }  

    public function viewDocuments($opportunityId) {

        $applicationId = $this->getApplicationId($opportunityId);

        if ($applicationId == 0) {
            return false;
        }
        return $applicationId;
    }

    public function getApplicationId($opportunityId) {

        $applicationId = 0;

        if(strlen($opportunityId) == 15) {
            $opportunityId = Utils::to18Id($opportunityId);
        }

        $query = "select * from salesforce_integration where salesforce_object = 'Opportunity' and salesforce_id = ?";
        $res = $this->db->select($query, [$opportunityId]);

        foreach ($res as $key => $value) {
            $applicationId = $value->object_id;
        }

        return $applicationId;
    }

    public function getApplicantsEmail($opportunityId) {

        $this->logger->debug('MergeDocumentsBO->getApplicantsEmail',[$opportunityId]);

        if(is_numeric($opportunityId)) {
            $applicationId = $opportunityId;
        } else {
            $applicationId = $this->getApplicationId($opportunityId);
        }

        if ($applicationId == 0) {
            return false;
        }

        $this->logger->debug('MergeDocumentsBO->getApplicantsEmail',[$applicationId]);

        $query = "select b.applicant_id, a.spouse_id, a.`type`, a.f_name, a.l_name, a.signer  
                  from spouse_table a
                  join applicant_table b on b.spouse1_id = a.spouse_id or b.spouse2_id = a.spouse_id
                  where b.application_id = ?
                  and a.`type` <> 'Do not contact' 
                  and a.`type` <> 'Not a co-applicant'";
        $res = $this->db->select($query, [$applicationId]);

        $data = array();
        $applicants = array();
        $emailOptions = array();

        foreach ($res as $key => $value) {

            if (empty($value->f_name) || $value->signer > 0) {
                continue;
            }

            $applicants[] = [
                'applicantId' => $value->applicant_id,
                'spouseId' => $value->spouse_id,
                'type' => $value->type,
                'name' => $value->f_name . ' ' . $value->l_name,
                'email' => ''
            ];
        }

        $query = "select DISTINCT(b.home_email) as email
                  from spouse_table a
                  join applicant_table b on b.spouse1_id = a.spouse_id or b.spouse2_id = a.spouse_id
                  where b.application_id = ?
                  and a.`type` <> 'Do not contact' 
                  and a.`type` <> 'Not a co-applicant'
                  UNION 
                  select DISTINCT(info) as email  
                  from applicant_contacts_table a
                  join applicant_table b on b.applicant_id = a.applicant_id 
                  where b.application_id = ?
                  and a.type like '%Email'
                  and a.info like '%@%'";
        $res = $this->db->select($query, [$applicationId, $applicationId]);

        foreach ($res as $key => $value) {
            if (!empty($value->email)) {
                $emailOptions[] = [
                    'id' => $value->email,
                    'name' => $value->email,
                ];
            }
        }

        $data = [
            'applicants' => $applicants,
            'emailOptions' => $emailOptions
        ];

        return $data;
    }

}
