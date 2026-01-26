<?php

namespace App\Http\Controllers;

use DateTime;
use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use Illuminate\Http\Request;
use App\Amur\BO\CMS\AgentSetupBO;
use Illuminate\Support\Facades\Auth;
use App\Amur\BO\CMS\CommissionSetupBO;
use App\Amur\BO\CMS\CommissionSummaryBO;


class CmsController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function commissionSetup(Request $request) {
        $cmSetupBO = new CommissionSetupBO($this->logger, $this->db);
        $res       = $cmSetupBO->getCommissionSetup();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function cmsSetupApproval(Request $request) {

        $startDate = new DateTime($request->startDate);
        $endDate   = new DateTime($request->endDate);
        $startDate->setTime(0,0,0);
        $endDate->setTime(23,59,59);

        $cmSetupBO =  new CommissionSetupBO($this->logger, $this->db);
        $res = $cmSetupBO->cmsSetupApproval($startDate, $endDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function commissionSetupDepartmentApproval(Request $request) {

        $id         = $request->id;
        $tableId    = $request->tableId;
        $status     = $request->status;
        $department = $request->department;

        $cmSetupBO = new CommissionSetupBO($this->logger, $this->db);
        $res = $cmSetupBO->commissionSetupDepartmentApproval($id, $tableId, $status, $department);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function  commissionCustomSetupDepartmentApproval(Request $request) {

        $tableId = $request->tableId;
        $status = $request->status;
        $department = $request->department;

        $cmSetupBO =  new CommissionSetupBO($this->logger, $this->db);
        $res       = $cmSetupBO->commissionCustomSetupDepartmentApproval($tableId, $status, $department);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }
    public function commissionSetupStructureDepartmentApproval(Request $request) {
        $tableId = $request->tableId;
        $status = $request->status;
        $department = $request->department;

        $cmSetupBO =  new CommissionSetupBO($this->logger, $this->db);
        $res       = $cmSetupBO->commissionSetupStructureDepartmentApproval($tableId, $status, $department);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function saveSetup(Request $request) {

        if (isset($request->cms_agent_id)) {
            $cms_agent_id = $request->cms_agent_id;
        } else {
            $cms_agent_id = null;
        }

        $cmsTypeId      = $request->cmsTypeId;
        $effectiveAt    = $request->effectiveAt;
        $amount         = $request->amount;        
        $percentage     = $request->percentage;
        $effectiveUntil = $request->effectiveUntil;

        $cmSetupBO = new CommissionSetupBO($this->logger, $this->db);
        $res = $cmSetupBO->save($cmsTypeId, $cms_agent_id, $effectiveAt, $amount, $percentage, $effectiveUntil);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Record saved';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Record cannot be saved!';
        }

        return response()->json($response, 200);
    }

    public function getType(Request $request) {
        $company = $request->company;
        $cmsType = new CommissionSetupBO($this->logger, $this->db);
        $res       = $cmsType->getType($company);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function getTypes(Request $request) {
        $cmsType = new CommissionSetupBO($this->logger, $this->db);
        $res = $cmsType->getTypes();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Files could not be retrieved!';
        }

        return response()->json($response, 200);
    }

    public function saveType(Request $request) {
        $cmsType = new CommissionSetupBO($this->logger, $this->db);
        $name = $request->name;
        $res       = $cmsType->saveType($name);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Record saved';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Record cannot be saved!';
        }

        return response()->json($response, 200);
    }

    public function getCommissions(Request $request) {

        $company = $request->company;
        $referenceDate = (new DateTime($request->referenceDate))->format('Y-m-d');

        $cmsCalculete = new CommissionSummaryBO($this->logger, $this->db);
        $res = $cmsCalculete->getCommissions($company, $referenceDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getCommissionsById(Request $request, $id) {
        $cmsCalculete = new CommissionSummaryBO($this->logger, $this->db);

        $company       = $request->company;
        $d             = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');

        $res = $cmsCalculete->getCommissions($company, $referenceDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function calculateCommission(Request $request) {

        $startDate = new DateTime($request->reference);
        $startDate = new DateTime($startDate->format('Y-m-1'));
        $endDate = new DateTime($startDate->format('Y-m-t'));
        $company = $request->company;

        $cmsCalculete = new CommissionSummaryBO($this->logger, $this->db);
        $res = $cmsCalculete->calculateCommission($startDate, $endDate, $company);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Unable to save record!';
        }

        return response()->json($response, 200);
    }

    public function getUsers() {

        $agentsBO = new AgentSetupBO($this->logger, $this->db);
        $agents = $agentsBO->getUsers();

        if ($agents !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $agents;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }
        // $this->logger->info('CMS controller -> getUsers', ['response' => $response]);
        return response()->json($response, 200);
    }

    public function getAgents() {
        $agentsBO = new AgentSetupBO($this->logger, $this->db);
        $agents = $agentsBO->getAgents();

        if ($agents !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $agents;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function getManagers() {

        $managerBO = new AgentSetupBO($this->logger, $this->db);
        $managers = $managerBO->getManagers();

        $this->logger->info('CMS controller -> getManagers', ['response' => $managers]);

        if ($managers !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $managers;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function managerApproval(Request $request) {

        $agentId = $request->agentId;
        $referenceDate = (new DateTime($request->referenceDate))->format('Y-m-d');

        $managerBO = new AgentSetupBO($this->logger, $this->db);
        $managers = $managerBO->managerApproval($agentId, $referenceDate);

        if ($managers !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function getAccounting() {

        $accountingBO = new AgentSetupBO($this->logger, $this->db);
        $accounting = $accountingBO->getAccounting();

        $this->logger->info('CMScontroller->getAccounting', ['response' => $accounting]);

        if ($accounting !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $accounting;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function getExecutives() {

        $executiveBO = new AgentSetupBO($this->logger, $this->db);
        $executive = $executiveBO->getExecutives();

        $this->logger->info('CMScontroller->Executives', ['response' => $executive]);

        if ($executive !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $executive;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function accountingApproval(Request $request) {

        $agentId = $request->agentId;
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');

        $managerBO = new AgentSetupBO($this->logger, $this->db);
        $managers = $managerBO->accountingApproval($agentId, $referenceDate);

        if ($managers !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $managers;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function executiveApproval(Request $request) {

        $executiveApprovalStatus = $request->executiveApprovalStatus;
        $agentId = $request->agentId;
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');

        $executiveBO = new AgentSetupBO($this->logger, $this->db);
        $executives = $executiveBO->executiveApproval($executiveApprovalStatus, $agentId, $referenceDate);

        if ($executives !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $executives;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function resetAgentStatus(Request $request) {

        $agentId       = $request->agentId;
        $referenceDate = (new DateTime($request->referenceDate))->format('Y-m-d');
        $action        = $request->action;

        $agentBO = new AgentSetupBO($this->logger, $this->db);
        $agent = $agentBO->resetAgentStatus($agentId, $referenceDate, $action);

        if ($agent !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function getAgentSetup(Request $request, $id, $type) {

        $agentSetupBO = new AgentSetupBO($this->logger, $this->db);
        $agentSetup   = $agentSetupBO->getAgentSetup($id, $type);

        if ($agentSetup !== false) {
            $response          = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $agentSetup;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }
    public function getAgentSetupForCommissionInfo(Request $request, $id, $type) {
        $agentSetupBO = new AgentSetupBO($this->logger, $this->db);
        $agentSetup   = $agentSetupBO->getAgentSetupForCommissionInfo($id, $type);

        if ($agentSetup !== false) {
            $response          = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $agentSetup;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getCustomSetup(Request $request, $id, $type) {
        $agentSetupBO = new AgentSetupBO($this->logger, $this->db);
        $agentSetup   = $agentSetupBO->getCustomSetup($id, $type);

        if ($agentSetup !== false) {
            $response          = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $agentSetup;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function saveAgentSetup(Request $request) {

        $this->logger->info('CMScontroller->saveAgentSetup ', [$request->all()]);

        $userId       = $request->userId;
        $userType     = $request->userType;
        $saveGroups   = $request->saveGroups;
        $customAmt    = $request->customAmt;
        $companyGroup = $request->companyGroup;

        $agentSetupBO = new AgentSetupBO($this->logger, $this->db);       
        $agentSetupBO->saveAgentSetup($userId, $userType, $saveGroups, $customAmt, $companyGroup);

        if ($agentSetupBO !== false) {
            $response          = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $agentSetupBO;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function removeAgent(Request $request) {

        $this->logger->info('CMScontroller->removeAgent ', [$request->all()]);

        $agent = $request->agent;

        $agentBO = new AgentSetupBO($this->logger, $this->db);
        $agentBO->deleteAgent($agent);

        if ($agentBO !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Agent Removed';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Agent cannot be removed';
        }

        return response()->json($response, 200);
    }

    public function disputeAgent(Request $request) {
        // $agent        = $request->agent;
        $removeReason = $request->removeReason;
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');


        $agentBO = new AgentSetupBO($this->logger, $this->db);
        $agentBO->disputeAgent($removeReason, $referenceDate);

        if ($agentBO !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Agent Disputed Successfully';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Agent cannot add the dispute';
        }

        return response()->json($response, 200);
    }

    public function commissionApproval(Request $request) {

        $this->logger->info('CMScontroller->commissionApproval', []);
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');
        $company  = $request->company;

        $commissionApproval = new AgentSetupBO($this->logger, $this->db);
        $res = $commissionApproval->commissionsApproval($referenceDate, $company);

        $this->logger->info('CMScontroller->commissionApproval res, company', [$res, $company]);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            if (count($commissionApproval->getErrors()) > 0) {
                $response->message = implode(', ',$commissionApproval->getErrors());
            }else {
                $response->message = '';
            }
        }

        return response()->json($response, 200);
    }

    public function commissionAgentDetail(Request $request) {

        $this->logger->info('CMScontroller->commissionAgentDetail', []);
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');
        $agent =    $request->agent;

        $commissionApproval = new AgentSetupBO($this->logger, $this->db);
        $res = $commissionApproval->commissionAgentDetail($referenceDate, $agent);

        $this->logger->info('CMScontroller->commissionAgentDetail res, company', [$res]);


        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function saveCommissionApproval(Request $request) {

        $this->logger->info('CMScontroller->saveCommissionApproval', []);
        $d = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');

        $commissionApproval = new AgentSetupBO($this->logger, $this->db);
        $this->logger->info('CMScontroller->saveCommissionApproval refernece Date', [$referenceDate]);

        $res = $commissionApproval->saveCommissionApproval($referenceDate);

        $this->logger->info('CMScontroller->saveCommissionApproval', ['response' => $res]);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Commission approved successfully';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error approving commission';
        }

        return response()->json($response, 200);
    }

    public function summaryCommissionApproval(Request $request) {

        $d  = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');
        $cmsTypeId = $request->cmsTypeId;
        $cmsCommissionDetailId = $request->cmsDetailId;
        $agentName = $request->agentName;

        $commissionApproval = new CommissionSummaryBO($this->logger, $this->db);
        $res = $commissionApproval->summaryCommissionApproval($referenceDate, $cmsTypeId, $cmsCommissionDetailId, $agentName);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error';
        }

        return response()->json($response, 200);
    }

    public function approveByDepartment(Request $request) {

        $userId = Auth::user()->user_id;
        $department = $request->department;
        //$commissions = $request->commissions;
        $company = $request->company;
        $referenceDate = new DateTime($request->referenceDate);

        $commissionApproval = new CommissionSummaryBO($this->logger, $this->db);
        $res = $commissionApproval->approveByDepartment($userId, $department, $referenceDate, $company);

        if ($department == 'accounting') {
            $commissionApproval = new CommissionSummaryBO($this->logger, $this->db);
            $commissionApproval->sendEmail($company, $referenceDate);
        }

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Commission approved successfully';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error approving commission';
        }
        return response()->json($response, 200);
    }

    public function sendNotificationAgent(Request $request) {

        $to            = $request->to;
        $name          = $request->name;
        $referenceDate = $request->referenceDate;

        $cmsSendNotification = new CommissionSummaryBO($this->logger, $this->db);
        $res = $cmsSendNotification->sendNotificationAgent($to, $name, $referenceDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Notification sent successfully';
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error sending notification';
        }

        return response()->json($response, 200);
    }


    public function commissionDownloadToCSV(Request $request) {

        $commissionSummaryBO = new CommissionSummaryBO($this->logger, $this->db);
        $company = $request->company;

        $d  = new DateTime($request->referenceDate);
        $referenceDate = $d->format('Y-m-d');
        $res = $commissionSummaryBO->commissionDownloadToCSV($company, $referenceDate);
        $this->logger->info('CmsController->commissionDownloadToCSV res is', $res);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function userNameFromId(Request $request) {

        $userId = $request->id;
        $agentBO = new AgentSetupBO($this->logger, $this->db);
        $res = $agentBO->userNameFromId($userId);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            // $response->data = $res;
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function getCompanies() {

        $commissionSetup = new CommissionSetupBO($this->logger, $this->db);
        $res = $commissionSetup->getCompanies();

        if ($res !== false) {
            $response          = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function approveAllAgentsCMS(Request $request) {
        $CommissionSetupBO = new CommissionSetupBO($this->logger, $this->db);
        $res = $CommissionSetupBO->approveAllAgents();
    }

    public function export(Request $request) {

        $request = $request->all();

        $types      = $request['params']['types'];
        $colunms    = $request['params']['colunms'];
        $commission = $request['params']['commission'];
        $total      = $request['params']['total'];

        $commissionSummaryBO = new CommissionSummaryBO($this->logger, $this->db);
        $res = $commissionSummaryBO->export($colunms, $commission, $total, $types);

        $response = new Response();

        if ($res !== false) {            
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function getReconciliations(Request $request) {

        $referencePeriod = new DateTime($request->referencePeriod);

        $commissionSummaryBO = new CommissionSummaryBO($this->logger, $this->db);
        $res = $commissionSummaryBO->getReconciliations($referencePeriod);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }
}
