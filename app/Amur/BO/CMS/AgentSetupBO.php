<?php

namespace App\Amur\BO\CMS;

use DateTime;
use Exception;
use App\Amur\Bean\IDB;
use App\Models\CmsAgent;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\CmsAgentSetup;
use App\Models\CmsCommission;
use App\Models\UsersTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\CmsSetupApproval;
use LDAP\Result;
use App\Amur\BO\CMS\CommissionSummaryBO;
use App\Models\GroupMembersTable;
use App\Amur\BO\CMS\CommissionSetupBO;

class AgentSetupBO {
    private $logger;
    private $db;
    private $errors = array();

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getUsers() {

        $this->logger->info('AgentSetupBO->getUsers');

        $query = "SELECT users_table.user_id, users_table.user_name, users_table.user_fname, users_table.user_lname  
                    FROM users_table
                    WHERE inuse = 'yes'
                    AND agent = 'yes'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM cms_agent
                        WHERE cms_agent.user_id = users_table.user_id
                        AND cms_agent.deleted_at IS NULL
                    )
                    ORDER BY user_fname";
        
        $usersObj = $this->db->select($query);

        if ($usersObj) {
            foreach ($usersObj as $key => $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                ];
            }
        }

        return $users;
    }

    public function getAgentData($userId) {
        try {
            $agent = CmsAgent::where('user_id', $userId)->where('deleted_at', null)->first();
            return $agent;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAgents() {

        $query = "select b.user_name, b.user_fname, b.user_lname, a.id, a.company_group
                    from cms_agent a
               left join users_table b on b.user_id = a.user_id
                   where b.inuse = 'yes'
                     and a.deleted_at is null
                order by a.company_group, b.user_name";

        //  and a.status = 'A' so both excutives and accounting approved to get the agents

        $agentsObj = $this->db->select($query);

        $users = array();
        if ($agentsObj) {
            foreach ($agentsObj as $key => $value) {
                $companyGroupName = '';

                if ($value->company_group == 'ACL') {
                    $companyGroupName = 'Alpine Credits Limited';
                } elseif ($value->company_group == 'SQC') {
                    $companyGroupName = 'Sequence Capital';
                } elseif ($value->company_group == 'SNR') {
                    $companyGroupName = 'Alpine Credits Limited Senior Broker';
                } elseif ($value->company_group == 'SON') {
                    $companyGroupName = 'Sequence Capital Ontario';
                }

                $users[] = [
                    'id' => $value->id,
                    'name' => $value->user_fname . ' ' . $value->user_lname,
                    'companyGroup' => $value->company_group,
                    'companyGroupName' => $companyGroupName
                ];
            }
        }

        return $users;
    }

    public function saveAgent($userId, $companyGroup) {

        try {
            $cmsAgent = new CmsAgent;
            $cmsAgent->user_id = $userId;
            $cmsAgent->status = 'P';
            $cmsAgent->company_group = $companyGroup;
            $cmsAgent->save();

            $agentName = $this->getAgentName($userId);

            //add to the cms_setup_approval table
            $setupApproval = new CmsSetupApproval;
            $setupApproval->table_id = $cmsAgent->id;
            $setupApproval->table_name = 'cms_agent';
            $setupApproval->description =  $agentName;
            $setupApproval->accounting_status = 'P';
            $setupApproval->executive_status = 'P';
            $setupApproval->type = 'NA';
            $setupApproval->created_by = Auth::user()->user_id;
            $setupApproval->created_at = Carbon::now();
            $setupApproval->updated_by = Auth::user()->user_id;
            $setupApproval->accounting_at = Carbon::now();
            $setupApproval->accounting_by = Auth::user()->user_id;
            $setupApproval->save();

            $this->logger->info('AgentSetupBO->saveAgent setupapproval', [$setupApproval->id]);

            return $cmsAgent->id;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAgentName($userId) {
        $query  = "select user_fname, user_lname from users_table where user_id = ?";

        $res = $this->db->select($query, [$userId]);

        $agentName = $res[0]->user_fname . ' ' . $res[0]->user_lname;
        return $agentName;
    }

    public function getAgentNameFromAgentId($agentId) {
        try {
            $queryAgent = "SELECT user_id FROM cms_agent WHERE id = ?";
            $userId = $this->db->select($queryAgent, [$agentId]);

            if (empty($userId)) {
                // Agent not found in cms_agent table
                return "Agent not found";
            }

            $query = "SELECT user_fname, user_lname FROM users_table WHERE user_id = ?";

            $res = $this->db->select($query, [$userId[0]->user_id]);

            if (empty($res)) {
                // User not found in users_table
                return "User not found";
            }

            $agentName = $res[0]->user_fname . ' ' . $res[0]->user_lname;
            return $agentName;
        } catch (Exception $e) {
            // Handle any exceptions, log errors, or return an appropriate error message.
            return "Error: " . $e->getMessage();
        }
    }


    public function updateAgent($agent, $companyGroup) {
        $agentData = [
            "company_group" => $companyGroup,
        ];

        CmsAgent::query()
            ->where('id', $agent)
            ->update($agentData);

        // add to the cms_setup_approval ?

        CmsSetupApproval::query()
            ->where('table_id', $agent)
            ->where('table_name', 'cms_agent')
            ->update(['accounting_at' => 'P', 'accounting_by' => null, 'accounting_status' => 'P', 'executive_at' => null, 'executive_by' => null, 'executive_status' => 'P',]);
    }

    public function deleteAgent($agent) {

        $this->logger->info('AgentSetupBO->deleteAgent ', [$agent]);

        $this->db->beginTransaction();

        try {

            CmsAgent::query()
            ->where('id', $agent['id'])
            ->delete();

            // update the cms_setup_approval table as well with the deleted agent
            $agentName = $this->getAgentNameFromAgentId($agent['id']);

            $setupApproval =  CmsSetupApproval::where('table_id', $agent['id'])->first();
            $setupApproval->table_name = 'cms_agent';
            $setupApproval->description = 'Agent' . $agentName . ' was deleted ';
            $setupApproval->accounting_status = null;
            $setupApproval->executive_status = null;
            $setupApproval->created_by = Auth::user()->user_id;
            $setupApproval->created_at = Carbon::now();
            $setupApproval->deleted_at = Carbon::now();
            $setupApproval->updated_by = Auth::user()->user_id;
            $setupApproval->accounting_at = Carbon::now();
            $setupApproval->accounting_by = Auth::user()->user_id;
            $setupApproval->save();


            CmsAgentSetup::query()
            ->where('cms_agent_id', $agent['id'])
            ->update(['deleted_at' => Carbon::now()]);

            $queryAgentSetup = "select id from cms_agent_setup where cms_agent_id = ?";
            $res = $this->db->select($queryAgentSetup, [$agent['id']]);

            $this->logger->info('AgentSetupBO->deleteAgent res from agent_setup', [$res]);

            foreach ($res as $record) {

                $setupApprovals = CmsSetupApproval::where('table_id', $record->id)
                ->where('table_name', 'cms_agent_setup')
                ->get();

                foreach ($setupApprovals as $setupApproval) {
                    $agentName = $this->getAgentNameFromAgentId($agent['id']);
                    $setupApproval->table_id = $record->id;
                    $setupApproval->description = 'cms agent ' . $agentName . ' was deleted';
                    $setupApproval->created_by = Auth::user()->user_id;
                    $setupApproval->created_at = Carbon::now();
                    $setupApproval->accounting_status = null;
                    $setupApproval->executive_status = null;
                    $setupApproval->deleted_at = Carbon::now();
                    $setupApproval->updated_by = Auth::user()->user_id;
                    $setupApproval->save();
                }
            }

            $queryCmsCommissionSetup = "select id from cms_commission_setup where cms_agent_id = ?";

            $result = $this->db->select($queryCmsCommissionSetup, [$agent['id']]);

            $this->logger->info('AgentSetupBO->deleteAgent res from queryCmsCommissionSetup', [$result]);

            if ($result) {
                foreach ($result as $record) {
                    $setupApprovalTable = CmsSetupApproval::where('table_id', $record->id)
                        ->where('table_name', 'cms_commission_setup')
                        ->first();

                    if ($setupApprovalTable) {
                        $agentName = $this->getAgentNameFromAgentId($agent['id']);
                        $setupApprovalTable->table_id = $record->id;
                        $setupApprovalTable->description = 'cms agent ' . $agentName . ' was deleted.';
                        $setupApprovalTable->created_by = $this->userNameFromId(Auth::user()->user_id);
                        $setupApprovalTable->created_at = Carbon::now();
                        $setupApprovalTable->accounting_status = null;
                        $setupApprovalTable->executive_status = null;
                        $setupApprovalTable->deleted_at = Carbon::now();
                        $setupApprovalTable->save();
                    }
                }
            }


            $this->db->commit();
            return true;

        } catch (\Exception $e) {

            $this->logger->error('AgentSetupBO->deleteAgent', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }
    }

    public function disputeAgent($removeReason, $referenceDate) {
        $userId = Auth::user()->user_id;

        $this->logger->info('AgentSetupBO->disputeAgent', [$referenceDate, $userId]);

        if (!is_null($referenceDate)) {

            $query = "Select a.id,a.cms_agent_id,b.user_id  
                     From cms_commission a
                     Inner join cms_agent b on b.id = a.cms_agent_id 
                     Where (a.agent_status <> 'A' or a.agent_status is null)
                     and b.user_id = ?
                     and a.reference_date = ?
                     and a.deleted_at is null";

            $res = $this->db->select($query, [$userId, $referenceDate]);

            $this->logger->info('AgentSetupBO->disputeAgent to DB if approved userId, ref date, res', [$userId, $referenceDate, $res, $removeReason]);

            if ($res) {
                foreach ($res as $key => $value) {
                    $commission = [
                        "agent_status"       => 'J',
                        "agent_approved_at"  => new DateTime(),
                        "agent_approved_by"  => $userId,
                        "agent_dispute_reason" => $removeReason
                    ];

                    $update = CmsCommission::query()
                        ->where('id', $value->id)
                        ->update($commission);
                }
                return true;
            }
        }

        return false;
    }

    public function index() {
        try {
            $setup = CmsAgentSetup::query('cms_agent_setup.*, cms_type.name')
                ->join('cms_type', 'cms_agent_setup.cms_type_id', '=', 'cms_type.id')
                ->get();
            $agentSetup = array();

            foreach ($setup as $key => $value) {

                $agentSetup[] = array(
                    'cms_type_id' => $value->cms_type_id,
                    'name' => $value->name,
                    'commission_by' => $value->commission_by == null || $value->commission_by == '' ? 'p' : $value->commission_by,
                    'setup_by' => $value->setup_by == null || $value->setup_by == '' ? 'd' : $value->setup_by
                );
            }

            return $agentSetup;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAgentSetup($id, $type) {

        try {

            $agentId = null;

            if ($type == 'agent') {
                $agentId = $id;
            } else {
                $agentBO = new AgentSetupBO($this->logger, $this->db);
                $getId = $agentBO->getAgentData($id);
                if ($getId != null) {
                    $agentId = $getId->id;
                }
            }

            $customSetup = array();
            $agentSetup  = array();

            $commissionSetupBO = new CommissionSetupBO($this->logger, $this->db);

            $cmsTypes = new CommissionSetupBO($this->logger, $this->db);
            $commissionSetups = $cmsTypes->getCommissionTypesByAgentId($agentId);

            $this->logger->info('AgentSetupBO->getAgentSetup->getCommissionTypesByAgentId : commissionSetups', [$commissionSetups]);

            if ($agentId) {

                $customSetup = $commissionSetupBO->getCommissionSetupByAgentId($agentId);
                
                $setup = CmsAgentSetup::select(
                    'cms_agent_setup.commission_by',
                    'cms_agent_setup.setup_by',
                    'cms_agent_setup.id AS setupId',
                    'cms_type.name',
                    'cms_agent_setup.cms_type_id'
                )
                    ->join('cms_type', 'cms_agent_setup.cms_type_id', '=', 'cms_type.id')
                    ->where('cms_agent_setup.cms_agent_id', $agentId)
                    ->get();

                $agentSetup = array();

                if ($setup) {

                    foreach ($setup as $key => $value) {
                        $agentSetup[] = array(
                            'setup_id' => $value->setupId,
                            'cms_type_id' => $value->cms_type_id,
                            'name' => $value->name,
                            'commission_by' => $value->commission_by == null || $value->commission_by == '' ? 'p' : $value->commission_by,
                            'setup_by' => $value->setup_by == null || $value->setup_by == '' ? 'd' : $value->setup_by,
                        );
                    }
                }

                if (count($agentSetup) > 0) {
                    $arr = array();
                    foreach ($commissionSetups as $key => $value) {

                        $commissionSetups[$key]['commission_by'] = 'p';
                        $commissionSetups[$key]['setup_by'] = 'd';

                        foreach ($agentSetup as $akey => $avalue) {
                            if ($value['id'] == $avalue['cms_type_id']) {
                                $commissionSetups[$key]['commission_by'] = $avalue['commission_by'];
                                $commissionSetups[$key]['setup_by'] = $avalue['setup_by'];
                                $commissionSetups[$key]['agent_setup_id'] = $avalue['setup_id'];
                            }
                        }
                    }
                }
            } else {
                foreach ($commissionSetups as $key => $value) {
                    if ($value->id == 12) {
                        $commissionSetups[$key]['commission_by'] = 'n';
                    }else {
                        $commissionSetups[$key]['commission_by'] = 'p';
                    }                    
                    $commissionSetups[$key]['setup_by'] = 'd';
                }
            }


            $data = array(
                'userId' => $agentId,
                'setups' => $commissionSetups,
                'customSetup' => $customSetup,
                'agentSetup' => $agentSetup,
            );

            return $data;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function getAgentSetupForCommissionInfo($id, $type) {

        try {

            $agentId = null;

            if ($type == 'agent') {
                $agentId = $id;
            } else { // Add Agent
                $agentBO = new AgentSetupBO($this->logger, $this->db);
                $getId = $agentBO->getAgentData($id);
                if ($getId != null) {
                    $agentId = $getId->id;
                }
            }

            $customSetup = array();
            $agentSetup  = array();

            $commissionSetupBO = new CommissionSetupBO($this->logger, $this->db);

            // get commission setups for user
            $cmsTypes = new CommissionSetupBO($this->logger, $this->db);
            $commissionSetups = $cmsTypes->getCommissionSetupsFromAgentId($agentId);

            $this->logger->info('AgentSetupBO->getAgentSetup->getCommissionTypesByAgentId : commissionSetups', [$commissionSetups]);

            // get all commission setups and agent setup by agent id
            if ($agentId) {
                $customSetup = $commissionSetupBO->getCommissionSetupByAgentId($agentId);
                // get all agent setup
                $setup = CmsAgentSetup::select(
                    'cms_agent_setup.commission_by',
                    'cms_agent_setup.setup_by',
                    'cms_agent_setup.id AS setupId',
                    'cms_type.name',
                    'cms_agent_setup.cms_type_id'
                )
                    ->join('cms_type', 'cms_agent_setup.cms_type_id', '=', 'cms_type.id')
                    ->where('cms_agent_setup.cms_agent_id', $agentId)
                    ->get();

                $agentSetup = array();

                if ($setup) {
                    foreach ($setup as $key => $value) {
                        $agentSetup[] = array(
                            'setup_id' => $value->setupId,
                            'cms_type_id' => $value->cms_type_id,
                            'name' => $value->name,
                            'commission_by' => $value->commission_by == null || $value->commission_by == '' ? 'p' : $value->commission_by,
                            'setup_by' => $value->setup_by == null || $value->setup_by == '' ? 'd' : $value->setup_by,
                        );
                    }
                }

                if (count($agentSetup) > 0) {
                    $arr = array();
                    foreach ($commissionSetups as $key => $value) {

                        $commissionSetups[$key]['commission_by'] = 'p'; // percentage
                        $commissionSetups[$key]['setup_by'] = 'd'; // default

                        foreach ($agentSetup as $akey => $avalue) {
                            if ($value['id'] == $avalue['cms_type_id']) {
                                $commissionSetups[$key]['commission_by'] = $avalue['commission_by']; // assign data from db
                                $commissionSetups[$key]['setup_by'] = $avalue['setup_by']; // assign data from db
                                $commissionSetups[$key]['agent_setup_id'] = $avalue['setup_id'];
                            }
                        }
                    }
                }
            } else {
                foreach ($commissionSetups as $key => $value) {
                    $commissionSetups[$key]['commission_by'] = 'p'; // percentage
                    $commissionSetups[$key]['setup_by'] = 'd'; // default
                }
            }


            $data = array(
                'userId' => $agentId,
                'setups' => $commissionSetups,
                'customSetup' => $customSetup,
                'agentSetup' => $agentSetup,
            );

            return $data;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    public function getCustomSetup($id, $type) {
        try {
            $agentId = $id;
            if ($type == 'user') {
                // get agent id
                $agentBO = new AgentSetupBO($this->logger, $this->db);
                $agentData = $agentBO->getAgentData($id);
                $agentId = $agentData->id; // assign returned id
            }

            // get the custom setup from commission_setup
            $commissionSetupBO = new CommissionSetupBO($this->logger, $this->db);
            $customSetup = $commissionSetupBO->getCommissionSetupByAgentId($agentId);

            $data = count($customSetup) > 0 ? $customSetup[0] : array();

            return $data;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveAgentSetup($userId, $userType, $saveGroups, $customAmt, $companyGroup) {
        try {
            if ($userType == 'user') {

                // insert into cms_agents, return agentId                
                $agentId = $this->saveAgent($userId, $companyGroup);
                $this->addGroupMembersTable($userId);
                $this->logger->info('AgentSetupBO -> saveAgentSetup: agentId, companyGroup ', [$agentId, $companyGroup]);

                // insert into cms_agent_setup
                foreach ($saveGroups as $key => $value) {
                    $setup                = new CmsAgentSetup;
                    $setup->cms_agent_id  = $agentId;
                    $setup->cms_type_id   = $value['id'];
                    $setup->commission_by = $value['commission_by'];
                    $setup->setup_by      = $value['setup_by'];
                    if ($value['setup_by'] == 'c') {
                        $setup->status = 'P';
                    } else {
                        $setup->status = 'A';
                    }

                    $setup->save();
                }

                if (count($customAmt) > 0) {
                    $commissions = $customAmt;
                    $this->logger->info('AgentSetupBO -> saveAgentSetup: and user type  customAmt', [$customAmt]);

                    $commissionSetupBO = new CommissionSetupBO($this->logger, $this->db);

                    foreach ($commissions as $key => $value) {
                        $commissionSetupBO->save($value['typeId'], $agentId, $value['effective'], $value['amount'], $value['percentage'], $value['effectiveUntil']);
                    }
                    $this->logger->info('AgentSetupBO -> saveAgentSetup: customAmt', [$customAmt]);
                }
            } else {

                $agentId = $userId;

                //$this->updateAgent($agentId, $companyGroup);

                // update cms_agent_setup
                foreach ($saveGroups as $key => $value) {

                    $setup = CmsAgentSetup::where('id', $value['agent_setup_id'])->first();

                    $oldCommissionBy = $setup->commission_by;
                    $oldSetupBy      = $setup->setup_by;
                    $oldValueId      = $setup->cms_type_id;
                    $oldSetup        = $this->commissionAcronymDetails($oldCommissionBy, $oldSetupBy, $oldValueId, $value['agent_setup_id']);
                    $newSetup        = $this->commissionAcronymDetails($value['commission_by'], $value['setup_by'], $value['id'], $value['agent_setup_id']);

                    if ($setup->cms_agent_id != $agentId ||  $setup->cms_type_id != $value['id'] || $setup->commission_by != $value['commission_by'] || $setup->setup_by != $value['setup_by']) {

                        $setupApproval = new CmsSetupApproval;
                        $userId = Auth::user()->user_id;                       

                        if ($setupApproval) {                            
                            $setupApproval->table_id          = $value['agent_setup_id'];
                            $setupApproval->table_name        = 'cms_agent_setup';
                            $setupApproval->description       = $oldSetup;
                            $setupApproval->new_description   = $newSetup;
                            $setupApproval->cms_type_id       = $value['id'];
                            $setupApproval->accounting_status = 'P';
                            $setupApproval->executive_status  = 'P';
                            $setupApproval->type              = 'NS';
                            $setupApproval->created_by        = $userId;
                            $setupApproval->updated_by        = $userId;
                            $setupApproval->created_at        = Carbon::now();
                            $setupApproval->save();
                        }

                        if ($setup->commission_by != $value['commission_by'] || $setup->setup_by != $value['setup_by']) {
                            CmsAgentSetup::where('id', $value['agent_setup_id'])->update(['status' => 'P', 'commission_by' => $value['commission_by'], 'setup_by' => $value['setup_by']]);
                        } else {
                            CmsAgentSetup::where('id', $value['agent_setup_id'])->update(['status' => 'P']);
                        }
                    }
                }

                if (count($customAmt) > 0) {
                    $commissions = $customAmt;
                    $commissionSetupBO = new CommissionSetupBO($this->logger, $this->db);
                    foreach ($commissions as $key => $value) {
                        if (isset($value['typeId'])) {
                            $commissionSetupBO->save($value['typeId'], $agentId, $value['effective'], $value['amount'], $value['percentage'], $value['effectiveUntil']);
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {

            return $e->getMessage();
        }
    }
    public function userNameFromId($userId) {
        $query = "select user_fname, user_lname from users_table where user_id = ?";
        $res = $this->db->select($query, [$userId]);
        $this->logger->info('AgentSetupBO->userNameFromId', [$res]);
        return $res[0]->user_fname . ' ' . $res[0]->user_lname;
    }
    public function save($agentId, $groupId) {
        try {
            $setup = new CmsAgentSetup;
            $setup->cms_agent_id = $agentId;
            $setup->cms_type_id = $groupId;
            $setup->save();

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function commissionAcronymDetails($commissionBy, $setupBy, $typeId, $agentId) {

        if ($commissionBy == 'p') {
            $commissionByValue = 'Percentage';
        } else if ($commissionBy == 'a') {
            $commissionByValue = 'Amount';
        } else {
            $commissionByValue = 'Both';
        }

        if ($setupBy === 'd') {
            $setupByValue = 'Deafult';
        } else {
            $setupByValue = 'Custom';
        }

        $result = 'Commissiont Type: ' . $commissionByValue . ', Setup Type: ' . $setupByValue;

        return $result;


        if ($result === 'Percentage deafult') {
            $query = "SELECT * FROM cms_commission_setup WHERE cms_type_id = ? AND cms_agent_id IS NULL";
            $res = $this->db->select($query, [$typeId]);
            if ($res) {
                $result = $commissionByValue . ' ' . $setupByValue . ' ' . $res[0]->percentage . '%';
            }
        }

        if ($result === 'Amount deafult') {
            $query = "SELECT * FROM cms_commission_setup WHERE cms_type_id = ? AND cms_agent_id IS NULL";
            $res = $this->db->select($query, [$typeId]);
            if ($res) {
                $result = $commissionByValue . ' ' . $setupByValue . ' ' . $res[0]->amount . '$';
            }
        }

        if ($result === 'Both deafult') {
            $this->logger->info('test Both deafult', [$result, $agentId, $typeId]);
            $query = "SELECT * FROM cms_commission_setup WHERE cms_type_id = ? AND cms_agent_id IS NULL";
            $res = $this->db->select($query, [$typeId]);
            if ($res) {
                $result = $commissionByValue . ' ' . $setupByValue . ' Amount ' . $res[0]->amount . '$' . ' Percentage ' . $res[0]->percentage . '%';
            }
        }

        if ($result === 'Both custom' || $result === 'Both Percentage' || $result === 'Both Amount') {
            // $this->logger->info('testing');
            $this->logger->info('AgentSetupBO -> commissionAcronymDetails ***', [$result, $agentId, $typeId]);

            $queryCmsAgenId = 'select cms_agent_id from cms_agent_setup where id = ?';

            $cmsAgenId = $this->db->select($queryCmsAgenId, [$agentId]);
            $this->logger->info('cmsAgenId ***', [$cmsAgenId]);
            $result = $commissionByValue . ' ' . $setupByValue;
        }

        $this->logger->info('AgentSetupBO->commissionAcronymDetails', [$commissionByValue, $setupByValue, $result]);

        if ($result) {
            return $result;
        } else {
            return $commissionByValue . ' ' . $setupByValue;
        }
    }

    public function getTypeGroupFromId($id) {
        $query = "SELECT * FROM cms_type where id = ?";
        $res = $this->db->select($query, [$id]);
        return $res[0];
    }

    public function getManagers() {
        $userId = Auth::user()->user_id;

        $query = "SELECT a.* FROM group_members_table a
                    JOIN groups_table b ON a.group_id = b.id AND a.user_id = ?
                    WHERE (a.group_id = 32 or a.group_id = 36 or a.group_id = 52)";
        $res = $this->db->select($query, [$userId]);

        if ($res) {
            return $res;
        }

        return false;
    }

    public function managerApproval($agent, $referenceDate) {

        $this->logger->info('AgentSetupBO->managerApproval', [$agent, $referenceDate]);

        $userId = Auth::user()->user_id;

        if (!is_null($referenceDate)) {
            $query = "select a.id, a.cms_agent_id,b.user_id, a.accounting_status, a.manager_status,
                      u.user_email,u.user_fname,u.user_lname,c.user_id
                      from cms_commission a
                      inner join cms_agent b on b.id = a.cms_agent_id 
                      inner join cms_agent c on a.cms_agent_id = c.id
                      inner join users_table u on c.user_id = u.user_id
                      where a.cms_agent_id = ?
                      and a.reference_date = ?
                      order by a.id desc
                      limit 1";
            $res = $this->db->select($query, [$agent, $referenceDate]);            

            if (count($res) > 0) {
                $commission = [
                    "manager_approved_by" => $userId,
                    "manager_approved_at" => new DateTime(),
                    "manager_status"      => 'A',
                    "updated_by"          => $userId
                ];

                CmsCommission::query()
                ->where('id', $res[0]->id)
                ->update($commission);

                if ($res[0]->accounting_status == 'A') {

                    $to   = array($res[0]->user_email);
                    $name = $res[0]->user_fname.' '.$res[0]->user_lname;

                    $cms = new CommissionSummaryBO($this->logger, $this->db);
                    $cms->sendNotificationAgent($to, $name, $referenceDate);

                }

                return true;
            }
        }

        return false;
    }

    public function resetAgentStatus($agent, $referenceDate, $action) {

        $userIdManager = Auth::user()->user_id;

        $query = "select a.id, a.reference_date, a.cms_agent_id, b.user_id, a.accounting_status, a.manager_status, u.user_fname, u.user_lname, u.user_email, a.agent_dispute_reason
                  from cms_commission a
                  inner join cms_agent b on b.id = a.cms_agent_id 
                  inner join users_table u on u.user_id = b.user_id
                  where a.cms_agent_id = ?
                  and a.reference_date = ?
                  order by a.id desc
                  limit 1";
        $res = $this->db->select($query, [$agent, $referenceDate]);

        if (count($res) > 0) {

            if ($action == 'R') {
                $commission = [
                    "agent_approved_by"    => null,
                    "agent_approved_at"    => null,
                    "agent_status"         => null,
                    "agent_dispute_reason" => null,
                ];
            }else {

                $agentDisputeReason = $res[0]->agent_dispute_reason;

                $commission = [
                    "agent_approved_by"    => $userIdManager,
                    "agent_approved_at"    => new DateTime(),
                    "agent_status"         => 'A',
                    "agent_dispute_reason" => 'Dispute approved by Manager',
                ];
            }

            CmsCommission::query()
            ->where('id', $res[0]->id)
            ->update($commission);            

            if ($action == 'A') {
                $this->sendEmailAccountTeam($res[0]->reference_date, $res[0]->user_fname, $res[0]->user_lname, $userIdManager, $agentDisputeReason);
            }

            return true;
        }

        return false;
    }

    public function accountingApproval($agent, $referenceDate) {

        $this->logger->info('AgentSetupBO->accountingApproval', [$agent, $referenceDate]);

        $userId = Auth::user()->user_id;        

        if (!is_null($referenceDate)) {

            $query =
                "Select a.id,a.cms_agent_id,b.user_id, a.accounting_status, a.manager_status,u.user_email,u.user_fname,u.user_lname,c.user_id
                     From cms_commission a
                     Inner join cms_agent b on b.id = a.cms_agent_id 
                     Inner join cms_agent c on a.cms_agent_id = c.id
                     Inner join users_table u on c.user_id = u.user_id
                     and a.cms_agent_id = ?
                     and a.reference_date = ?
                     order by a.id desc
                     limit 1";
            $res = $this->db->select($query, [$agent, $referenceDate]);

            if ($res) {
                foreach ($res as $key => $value) {
                    $commission = [
                        "accounting_approved_by" => $userId,
                        "accounting_approved_at" => new DateTime(),
                        "accounting_status"      => 'A',
                        "updated_by"             => $userId
                    ];

                    CmsCommission::query()
                    ->where('id', $value->id)
                    ->update($commission);

                    if ($value->manager_status == 'A') {

                        $to   = array($value->user_email);
                        $name = $value->user_fname.' '.$value->user_lname;
    
                        $cms = new CommissionSummaryBO($this->logger, $this->db);
                        $cms->sendNotificationAgent($to, $name, $referenceDate);

                    }
                }

                return true;
            }
        }
    }

    public function executiveApproval($status, $agent, $referenceDate) {

        $this->logger->info('AgentSetupBO->executiveApproval', [$status, $agent, $referenceDate]);

        $userId = Auth::user()->user_id;

        if (!is_null($referenceDate)) {

            $query = "Select a.id,a.cms_agent_id,b.user_id, a.accounting_status, a.manager_status, a.executive_status, b.company_group
                     From cms_commission a
                     Inner join cms_agent b on b.id = a.cms_agent_id 
                     and a.cms_agent_id = ?
                     and a.reference_date = ?
                      order by a.id desc
                      limit 1";
            $res = $this->db->select($query, [$agent, $referenceDate]);

            if ($res) {
                foreach ($res as $key => $value) {
                    $commission = [
                        "executive_approved_by" => $userId,
                        "executive_approved_at" => new DateTime(),
                        "executive_status"      => 'A',
                        "updated_by"            => $userId
                    ];

                    $update = CmsCommission::query()
                    ->where('id', $value->id)
                    ->update($commission);
                }

                $this->sendSpreadsheet($referenceDate, $value->company_group);

                return true;
            }
        }
    }

    public function getAccounting() {
        $userId = Auth::user()->user_id;

        $query = "SELECT a.*, u.user_fname FROM group_members_table a
                    JOIN groups_table b ON a.group_id = b.id
                    join users_table u on a.user_id = u.user_id
                    AND a.user_id = ?
                    WHERE a.group_id = ?";

        $res = $this->db->select($query, [$userId, 31]);

        $this->logger->info('AgentSetupBO->getAccounting', [$res]);

        if ($res) {
            return $res;
        }

        return false;
    }

    public function getExecutives() {
        $userId = Auth::user()->user_id;

        $query = "SELECT a.* FROM group_members_table a
                    JOIN groups_table b ON a.group_id = b.id
                    AND a.user_id = ?
                    WHERE a.group_id = ?";

        $result = $this->db->select($query, [$userId, 34]);

        if ($result) {
            return $result;
        }

        return false;
    }

    public function commissionsApproval($referenceDate, $company) {

        $userId = Auth::user()->user_id;

        $cmsAgent = CmsAgent::where('user_id', $userId)->first();

        if ($cmsAgent) {

            $cmsCommission = CmsCommission::query()
            ->where('reference_date', $referenceDate)
            ->where('cms_agent_id', $cmsAgent->id)
            ->first();

            if ($cmsCommission) {

                $date = new DateTime($referenceDate);
                $month = $date->format('F Y');

                if ($cmsCommission->accounting_status != 'A' || $cmsCommission->manager_status != 'A') {
                    $this->setErros('The commission for '. $month .' has not yet been approved. Please check the Reference Month Filter!');
                    return false;
                }
            }
        }


        $this->logger->info('AgentSetupBO->commissionsApproval commissions user, date', [$userId, $referenceDate]);
        

        if ($referenceDate !== 'null') {
            $query = "Select e.id, e.name, (COALESCE(a.total_count, 0) + COALESCE(a.total_count_percentage, 0)) as count,    (COALESCE(a.total_amount, 0) + COALESCE(a.total_amount_percentage, 0)) as amount, b.agent_status, f.user_fname, f.user_lname, a.id as commission_detail_id
                  From cms_commission_detail a
                  Inner join cms_commission b on b.id = a.cms_commission_id 
                  Inner join cms_agent_setup c on c.cms_agent_id = b.cms_agent_id 
                  Inner join cms_agent d on d.id = c.cms_agent_id
                  Inner join cms_type e on e.id = a.cms_type_id 
                  Inner join users_table f on f.user_id = d.user_id
                  Where d.user_id = ?
                  and b.reference_date = ?
                  and a.deleted_at is null
                  and b.deleted_at is null
                  Group by a.cms_type_id
                  Order by a.cms_type_id";
            $res = $this->db->select($query, [$userId, $referenceDate]);

            $commissions = array();
            $totalCount  = 0;
            $totalAmount = 0;

            if ($res) {
                foreach ($res as $key => $value) {

                    $totalCount  += $value->count;
                    $totalAmount += $value->amount;

                    $commissions[] = [
                        'id'     => $value->id,
                        'group'  => $value->name,
                        'count'  => $value->count,
                        'amount' => $value->amount,
                        'status' => $value->agent_status,
                        'agentName' => $value->user_fname . ' ' . $value->user_lname,
                        'cmsDetailId' => $value->commission_detail_id,
                        'referenceDate' => $referenceDate,
                    ];
                }
                if ($totalAmount > 0) {
                    $commissions[] = [
                        'id'     => 0,
                        'group'  => 'Total',
                        'count'  => $totalCount,
                        'amount' => $totalAmount,
                        'status' => '',
                        'agentName' => $value->user_fname . ' ' . $value->user_lname,
                        'cmsDetailId' => $value->commission_detail_id,
                        'referenceDate' => $referenceDate,
                    ];
                }
            }

            $approval = array();

            $query = "Select a.id, a.cms_agent_id, b.user_id, a.agent_status, a.manager_status, a.accounting_status
                  From cms_commission a
                  Inner join cms_agent b on b.id = a.cms_agent_id 
                  Where b.user_id = ?
                  and a.reference_date = ?
                  and manager_status = 'A'
				  and accounting_status = 'A'
                  and a.deleted_at is null";
            $result = $this->db->select($query, [$userId, $referenceDate]);

            if ($result) {
                foreach ($result as $row) {
                    if ($row->agent_status === null || $row->agent_status == '') {
                        $approval[] = [
                            'approval' => 'review'
                        ];
                    }
                    if ($row->agent_status == 'J') {
                        $approval[] = [
                            'approval' => 'no'
                        ];
                    }
                    if ($row->agent_status == 'A') {
                        $approval[] = [
                            'approval' => 'yes'
                        ];
                    }
                }
            }

            return [
                'commissions' => $commissions,
                'commissionsApproval' => $approval
            ];
        }
    }

    public function commissionAgentDetail($referenceDate, $agent) {

        if ($agent !== null) {
            $query = 'select user_id from cms_agent where id = ?';
            $user = $this->db->select($query, [$agent]);

            $this->logger->info('AgentSetupBO->commissionAgentDetail commissions user, date, agent', [$user, $referenceDate, $agent]);

            $userId = $user[0]->user_id;

            $this->logger->info('AgentSetupBO->commissionAgentDetail commissions user, date', [$userId, $referenceDate]);

            if ($referenceDate !== null && $userId !== null) {
                $query = "Select e.id, e.name, (COALESCE(a.total_count, 0) + COALESCE(a.total_count_percentage, 0)) as count,
                                 (COALESCE(a.total_amount, 0) + COALESCE(a.total_amount_percentage, 0)) as amount, b.agent_status,
                                 f.user_fname, f.user_lname, a.id as commission_detail_id
                        From cms_commission_detail a
                        Inner join cms_commission b on b.id = a.cms_commission_id 
                        Inner join cms_agent_setup c on c.cms_agent_id = b.cms_agent_id 
                        Inner join cms_agent d on d.id = c.cms_agent_id
                        Inner join cms_type e on e.id = a.cms_type_id 
                        Inner join users_table f on f.user_id = d.user_id
                        Where d.user_id = ?
                        and b.reference_date = ?
                        and a.deleted_at is null
                        and b.deleted_at is null
                        Group by a.cms_type_id
                        Order by a.cms_type_id";
                $res = $this->db->select($query, [$userId, $referenceDate]);

                $commissions = array();
                $totalCount  = 0;
                $totalAmount = 0;

                if ($res) {
                    foreach ($res as $key => $value) {

                        $totalCount  += $value->count;
                        $totalAmount += $value->amount;

                        $commissions[] = [
                            'id'     => $value->id,
                            'group'  => $value->name,
                            'count'  => $value->count,
                            'amount' => $value->amount,
                            'status' => $value->agent_status,
                            'agentName' => $value->user_fname . ' ' . $value->user_lname,
                            'cmsDetailId' => $value->commission_detail_id,
                            'referenceDate' => $referenceDate,
                        ];
                    }
                    if ($totalAmount > 0) {
                        $commissions[] = [
                            'id'     => 0,
                            'group'  => 'Total',
                            'count'  => $totalCount,
                            'amount' => $totalAmount,
                            'status' => '',
                            'agentName' => $value->user_fname . ' ' . $value->user_lname,
                            'cmsDetailId' => $value->commission_detail_id,
                            'referenceDate' => $referenceDate,
                        ];
                    }
                }

                return $commissions;
            }
        }
    }

    public function saveCommissionApproval($referenceDate) {
        $userId = Auth::user()->user_id;

        $this->logger->info('AgentSetupBO->saveCommissionApproval', [$referenceDate, $userId]);

        if(!is_null($referenceDate)) {

            $query = "select a.id, a.cms_agent_id, b.user_id  
                        from cms_commission a
                  inner join cms_agent b on b.id = a.cms_agent_id 
                         and a.agent_status is null
                         and a.manager_status = 'A'
                         and a.accounting_status = 'A'
                         and a.reference_date = ?
                       where a.deleted_at is null
                         and b.user_id = ?";
            $res = $this->db->select($query, [$referenceDate, $userId]);

            if(count($res) > 0) {
                $commission = [
                    "agent_status"       => 'A',
                    "agent_approved_at"  => new DateTime(),
                    "agent_approved_by"  => $userId,
                ];

                CmsCommission::query()
                ->where('id', $res[0]->id)
                ->update($commission);

                return true;
            }
        }

        return false;
    }

    public function sendSpreadsheet($referenceDate, $companyGroup) {

        $this->logger->info('AgentSetupBO->sendSpreadsheet', [$referenceDate, $companyGroup]);

         if(env('APP_ENV') == 'production') {

            $query = "select * 
                      from cms_commission a
                      join cms_agent b on b.id = a.cms_agent_id
                      where a.reference_date = ?
                      and b.company_group = ?";
            $cmsCommission = $this->db->select($query, [$referenceDate, $companyGroup]);
    
            $allApproved = true;
    
            foreach ($cmsCommission as $key => $value) {
                if ($value->accounting_status != 'A' && $value->manager_status != 'A' && $value->agent_status != 'A' && $value->executive_status != 'A') {
                    $allApproved = false;
                    break;
                }
            }           

            if ($companyGroup == 'ACL') {
                $companyGroupName = 'Alpine Credits Limited';
            } elseif ($companyGroup == 'SQC') {
                $companyGroupName = 'Sequence Capital';
            } elseif ($companyGroup == 'SNR') {
                $companyGroupName = 'Alpine Credits Limited Senior Broker';
            } elseif ($companyGroup == 'SON') {
                $companyGroupName = 'Sequence Capital Ontario';
            }

            if ($allApproved) {

                $date      = new DateTime($referenceDate);                
                $dateAux   = Carbon::parse($referenceDate);
                $reference = $dateAux->format('M/Y');

                $csvName  = 'cms_commission_'.$date->format('F').'_'.$date->format('Y').'.csv';
    
                $commissionSummary = new CommissionSummaryBO($this->logger, $this->db);
                $data = $commissionSummary->commissionDownloadToCSV($companyGroup, $referenceDate);
    
                if (isset($data['fileName'])) {
                    $toAddresses = array('payroll@amurgroup.ca','jelena@amurgroup.ca');
                    $subject     = 'CMS - Commission ' . $reference;
                    $body        = "Hi, <br><br>
                    Please find attached commission spreadsheet for payment for the month of ".$reference." for ". $companyGroupName." <br><br>
                    <b><i>This is an automatic message. No response is required.</i></b><br><br>
                    Best regards,<br>
                    IT Team";
                    
                    $attachments = array();
            
                    $attachments[] = [
                        'name' => $csvName,
                        'attachmentType' => "xlsx",
                        'contentBytesBase64' => $data['file'],
                    ];
                    
                    $this->logger->info('AgentSetupBO->sendSpreadsheet email sent to', [$toAddresses]);

                    Utils::sendEmail($toAddresses, $subject, $body,'', $attachments);
                }
            }
        }
    }

    public function sendEmailAccountTeam($referenceDate, $userFname, $userLname, $userIdManager, $agentDisputeReason) {

        if(env('APP_ENV') == 'production') {

            $userTable = UsersTable::query()
            ->where('user_id',$userIdManager)
            ->first();

            if ($userTable) {
                $managerName = $userTable->user_fname.' '.$userTable->user_lname;
            }

            $name      = $userFname.' '.$userLname;
            $date      = new DateTime($referenceDate);
            $reference = $date->format('F').' '.$date->format('Y');

            $toAddresses = array('jelena@amurgroup.ca');
            $subject     = 'CMS - Dispute Approved';
            $body        = "Hi, <br><br>        
            The commission dispute opened by Agent ".$name." for the month of ".$reference.", citing the reason '<strong>".$agentDisputeReason."</strong>', has been approved by ".$managerName." <br>
            Please make the necessary changes to Harvey and recalculate the commission.";

            Utils::sendEmail($toAddresses, $subject, $body, 'local');
        }
    }

    public function addGroupMembersTable($userId) {

        $this->logger->info('AgentSetupBO->addGroupMembersTable', [$userId]);

        $groupMembersTable = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('group_id', 33)
        ->first();

        if (!$groupMembersTable) {
            $groupMembersTable = new GroupMembersTable;
            $groupMembersTable->date_created     = Carbon::now();
            $groupMembersTable->date_modified    = Carbon::now();
            $groupMembersTable->modified_user_id = 99;
            $groupMembersTable->created_by       = 99;
            $groupMembersTable->deleted          = 0;
            $groupMembersTable->group_id         = 33;
            $groupMembersTable->user_id          = $userId;
            $groupMembersTable->save();
        }
    }

    public function setErros($errors) {
        $this->errors[] = $errors;
    }
    
    public function getErrors() {
        return $this->errors;
    }

}
