<?php

namespace App\Amur\BO;

use DateTime;
use App\Amur\Bean\IDB;
use App\Amur\BO\AwsBO;
use App\Amur\BO\UserBO;
use App\Amur\Bean\ILogger;
use App\Models\DmsTemplate;
use App\Amur\Utilities\Utils;
use App\Amur\Utilities\HttpRequest;
use App\Models\DmsTemplateApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DMSBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index() {

        $dmsTemplates = DmsTemplate::query()
        ->whereNotNull('document_type')
        ->orderBy('province')
        ->orderBy('document_type')
        ->orderBy('name')
        ->get();

        $templates = array();

        foreach($dmsTemplates as $key => $value) {

            $waitingApproval = $this->checkApproval($value->id);

            $templates[] = [
                'id' => $value->id,
                'name' => $value->name,
                'province' => $value->province,
                'documentType' => $value->document_type,
                'updatedAt' => new DateTime($value->updated_at),
                'waitingApproval' => $waitingApproval
            ];
        }

        return $templates;
    }

    public function store($request) {
        if(!$request->hasFile('file')) {
            $this->logger->error('DMSBO->store - File is empty');
            return false;
        }

        $file = $request->file('file');
        //$fileKey = md5(uniqid());

        if(!$request->hasFile('content')) {
            $this->logger->error('DMSBO->uploadFile - Parameters are empty');
            return false;
        }

        $fields = json_decode($request->file('content')->get());

        if(!isset($fields->id)) {
            if(isset($fields->company) && isset($fields->documentType)) {
                $id = 0;
                $company = $fields->company;
                $documentType = $fields->documentType;
            } else {
                $this->logger->error('DMSBO->store - id, company and document type not set',[json_encode($fields)]);
                return false;
            }
        } else {
            $id = $fields->id;
        }

        if($id == 0) {
            if(empty($company)) {
                $sharepointPath = "/sites/appdocument/Shared Documents/TEMPLATES/$documentType";
            } elseif($company == 'SQC') {
                $sharepointPath = "/sites/appdocument/Shared Documents/TEMPLATES/SQCTACL/$company $documentType docs";
            } else {
                $sharepointPath = "/sites/appdocument/Shared Documents/TEMPLATES/ACLTACL/$company $documentType docs";
            }

            $dmsTemplate = new DmsTemplate();
            $dmsTemplate->name = $file->getClientOriginalName();
            $dmsTemplate->file_type = 'xml';
            $dmsTemplate->outbound_file_type = 'xml';
            $dmsTemplate->sharepoint_path = $sharepointPath;
            $dmsTemplate->province = empty($company) ? null : $company;
            $dmsTemplate->document_type = $documentType;
            $dmsTemplate->save();
            
        } else {
            $dmsTemplate = DmsTemplate::find($id);
            $dmsTemplate->name = $file->getClientOriginalName();
            $dmsTemplate->save();

            if(!$dmsTemplate) {
                $this->logger->error('DMSBO->store - Template not found');
                return false;
            }
        }

        $this->logger->debug('DMSBO->store',[$id, $file->getClientOriginalName(),$dmsTemplate->sharepoint_path]);

        $fields = array(
            'file' => base64_encode(file_get_contents($file)),
            'fileName' => $file->getClientOriginalName(),
            'folderName' => str_replace('/sites/appdocument/Shared Documents/', '', $dmsTemplate->sharepoint_path)
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/sharepoint/upload');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        return true;
    }

    public function download($id) {
        $dmsTemplate = DmsTemplate::find($id);
        if(!$dmsTemplate) {
            $this->logger->error('DMSBO->download - Template not found');
            return false;
        }

        $fields = array(
            'relativeUrl' => $dmsTemplate->sharepoint_path . '/' . $dmsTemplate->name
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/sharepoint/download');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        if(isset($response->data) && $response->data !== false) {
            return [
                'fileName' => $dmsTemplate->name,
                'file' => $response->data
            ];
        }

        return false;
    }

    public function destroy($id) {
        $dmsTemplate = DmsTemplate::find($id);
        $dmsTemplate->delete();
    
        DmsTemplateApproval::query()
        ->where('dms_template_id', $id)
        ->where('status', 'p')
        ->delete();

        return true;
    }

    public function getTemplatesApproval($startDate, $endDate, $status) {
        $this->logger->info('DMSBO->getTemplatesApproval',[$startDate, $endDate, $status]);

        $templates = array();

        $dmsTemplateApproval = DmsTemplateApproval::query()
        ->where('created_at', '>=',  $startDate)
        ->where('created_at', '<=',  $endDate);
        if ($status != '' && $status != null) {
            $dmsTemplateApproval->where('status', $status);
        }
        $dmsTemplateApproval = $dmsTemplateApproval->get();

        $userBo = new UserBO($this->logger, $this->db);

        foreach ($dmsTemplateApproval as $key => $value) {

            $approvedAt = null;
            if ($value->approved_at != null) {
                $approvedAt = new DateTime($value->approved_at);
            }

            $templates[] = [
                'id' => $value->id,
                'dmsTemplateId' => $value->dms_template_id,
                'name'       => $value->file_name,
                'status'     => $value->status,
                'createdAt'  => new DateTime($value->created_at),
                'createdBy'  => $userBo->getUserName($value->created_by),
                'approvedAt' => $approvedAt,
                'approvedBy' => $userBo->getUserName($value->approved_by)
            ];
        }

        return $templates;
    }

    public function storeS3($request) {
        $this->logger->info('DMSBO->storeS3',[$request]);

        if(!$request->hasFile('file')) {
            $this->logger->error('DMSBO->storeS3 - File is empty');
            return false;
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $fields = json_decode($request->file('content')->get());

        //save file
        $fileKey = md5(uniqid());
        Storage::disk('local')->put('tmp/' . $fileKey, file_get_contents($file));

        //send file to S3
        $awsBO = new AwsBO($this->logger);
        $awsBO->setBucket('amurgroup-merge-docs');

        if($awsBO->uploadFile($fileKey, Storage::disk('local')->path('tmp/' . $fileKey)) === false) {
            $this->logger->error('DMSBO->storeS3 - Error uploading file to AWS');
            return false;
        }

        if(!isset($fields->id)) {
            if(isset($fields->company) && isset($fields->documentType)) {
                $id = 0;
                $company = $fields->company;
                $documentType = $fields->documentType;
            } else {
                $this->logger->error('DMSBO->store - id, company and document type not set',[json_encode($fields)]);
                return false;
            }
        } else {
            $id = $fields->id;
        }

        if($id == 0) {
            if(env('APP_ENV') == 'production') {
                $site = '/sites/appdocument';
            } else {
                $site = '/sites/appdocument-dev';
            }

            if(empty($company)) {
                $sharepointPath = $site."/Shared Documents/TEMPLATES/$documentType";
            } elseif($company == 'SQC') {
                $sharepointPath = $site."/Shared Documents/TEMPLATES/SQCTACL/$company $documentType docs";
            } else {
                $sharepointPath = $site."/Shared Documents/TEMPLATES/ACLTACL/$company $documentType docs";
            }

            $dmsTemplate = new DmsTemplate();
            $dmsTemplate->name = $file->getClientOriginalName();
            $dmsTemplate->file_type = 'xml';
            $dmsTemplate->outbound_file_type = 'xml';
            $dmsTemplate->sharepoint_path = $sharepointPath;
            $dmsTemplate->province = empty($company) ? null : $company;
            $dmsTemplate->document_type = $documentType;
            $dmsTemplate->save();
            $id = $dmsTemplate->id;
        }

        if($id > 0) {
            $dmsTemplateApproval = new DmsTemplateApproval;
            $dmsTemplateApproval->dms_template_id = $id;
            $dmsTemplateApproval->file_name = $fileName;
            $dmsTemplateApproval->hash = $fileKey;
            $dmsTemplateApproval->status = 'p';
            $dmsTemplateApproval->save();

            if(env('APP_ENV') == 'production') {
                $toAddresses = array('taclmerge@amurgroup.ca');
                $subject = 'Merge Document - New Template is Awaiting Review';
                $body = "Hi, <br><br>
                We would like to inform there is a new template document awaiting for your review: <b>(" . $fileName . ")</b>.<br>
                Please, visit to this link to review it: https://strive-law.amurfinancial.group<br><br>
                <b><i>This is an automatic message. No response is required.</i></b><br><br>";

                Utils::sendEmail($toAddresses, $subject, $body, 'local');
            }
        }
    }

    public function downloadApproval($id) {
        $this->logger->info('DMSBO->downloadApproval',[$id]);

        $dmsTemplate = DmsTemplateApproval::find($id);

        if(!$dmsTemplate) {
            $this->logger->error('DMSBO->downloadApproval - Template not found');
            return false;
        }

        $awsBO = new AwsBO($this->logger);
        $awsBO->setBucket('amurgroup-merge-docs');

        $file = $awsBO->getObject($dmsTemplate->hash);

        if ($file && $file != false) {
            return [
                'fileName' => $dmsTemplate->file_name,
                'file' => base64_encode($file)
            ];
        }

        return false;
    }

    public function setupTemplateApproval($id, $status, $reason) {
        $this->logger->info('DMSBO->setupTemplateApproval',[$id, $status, $reason]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();
        try {

            $dmsTemplateApproval = DmsTemplateApproval::find($id);
            $dmsTemplate = DmsTemplate::find($dmsTemplateApproval->dms_template_id);

            $createdBy = $dmsTemplateApproval->created_by;

            if($status == 'a') {
                $awsBO = new AwsBO($this->logger);
                $awsBO->setBucket('amurgroup-merge-docs');

                $file = $awsBO->getObject($dmsTemplateApproval->hash);

                if($file && $file != false) {
                    
                    if(env('APP_ENV') == 'production') {
                        $site = '/sites/appdocument/Shared Documents/';
                    } else {
                        $site = '/sites/appdocument-dev/Shared Documents/';
                    }

                    $sharepointPath = $dmsTemplate->sharepoint_path;
                    $pos = strpos($sharepointPath, 'TEMPLATES');

                    if($pos !== false) {
                        $substring = substr($sharepointPath, $pos);                   
                        $substring = str_replace(' ', '%20', $substring);
                    
                        $sharepointPath = substr($sharepointPath, 0, $pos) . $substring;
                    }

                    $fields = array(
                        'file' => base64_encode($file),
                        'fileName' => $dmsTemplateApproval->file_name,
                        'folderName' => str_replace($site, '', $sharepointPath)
                    );

                    $amurEndpoint = env('AMUR_API_ENDPOINT');

                    $httpRequest = new HttpRequest($this->logger);
                    $httpRequest->setUrl($amurEndpoint . '/sharepoint/upload');
                    $httpRequest->setMethod('post');
                    $httpRequest->setContentType('json');
                    $httpRequest->setAccept('json');
                    $httpRequest->setFieldType('raw');
                    $httpRequest->setFields(json_encode($fields));
                    $httpRequest->exec();

                    $response = json_decode($httpRequest->getResponse());

                    if($response->status == 'success') {
                        $this->logger->info('DMSBO->setupTemplateApproval uploaded successfully');

                        if(env('APP_ENV') == 'production') {
                            $toAddresses = array('taclmerge@amurgroup.ca');
                            $subject = 'Merge Document - Template Approved';
                            $body = "Hi, <br><br>
                            The template: <b>(" . $dmsTemplateApproval->file_name . ")</b> was approved. It's now available in TACL.<br><br>
                            <b><i>This is an automatic message. No response is required.</i></b><br><br>";
            
                            Utils::sendEmail($toAddresses, $subject, $body,'local');
                        }
                    } else {
                        $this->logger->error('DMSBO->setupTemplateApproval', [$response->message]);
                        $this->db->rollback();
                        return false;
                    }
                }
            }

            $dmsTemplateApproval->status = $status;
            $dmsTemplateApproval->reason = $reason;
            $dmsTemplateApproval->approved_at = new DateTime();
            $dmsTemplateApproval->approved_by = $userId;
            $dmsTemplateApproval->updated_by = $userId;
            $dmsTemplateApproval->save();
            
            if($status == 'a') {
                $dmsTemplate->name = $dmsTemplateApproval->file_name;
                $dmsTemplate->updated_at = new DateTime();
                $dmsTemplate->updated_by = $dmsTemplateApproval->created_by;
                $dmsTemplate->save();

            } elseif($status == 'r') {
                if(env('APP_ENV') == 'production') {
                    $userBo = new UserBO($this->logger, $this->db);
                    $user = $userBo->getUser($createdBy);

                    if($user && $user->user_email !== '' && $user->user_email !== null) {
                        $toAddresses = array($user->user_email);
                        $subject = 'Merge Document - Your Template was Rejected';
                        $body = "Hi, <br><br>
                        We would like to inform your proposal to change the <b>(" . $dmsTemplateApproval->file_name . ")</b> template has been reviewed and has not been approved. <br>
                        <b>Reason:</b> ". $reason. " <br><br>
                        <b><i>This is an automatic message. No response is required.</i></b><br><br>
                        Best regards,<br>
                        IT Team";

                        $this->logger->info('CommissionSetupBO->setupTemplateApproval email sent to',[$toAddresses]);
                        Utils::sendEmail($toAddresses, $subject, $body,'local');
                    }
                }
            }

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->error('DMSBO->setupTemplateApproval', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function checkApproval($dmsTemplateId) {

        $dmsApproval = DmsTemplateApproval::query()
        ->where('dms_template_id', $dmsTemplateId)
        ->where('status', 'p')
        ->first();

        if ($dmsApproval) {
            return true;
        }

        return false;
    }
}