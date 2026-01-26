<?php

namespace App\Amur\BO\CMS;

use DateTime;
use Exception;
use Carbon\Carbon;
use App\Amur\Bean\IDB;
use App\Models\CmsType;
use App\Models\CmsAgent;
use App\Amur\Bean\ILogger;
use App\Models\CmsAgentSetup;
use App\Models\CmsCommission;
use App\Models\CmsCommissionSetup;
use App\Models\GroupMembersTable;
use Illuminate\Support\Facades\Auth;
use App\Models\CmsSetupApproval;
use App\Amur\Utilities\Utils;

class CommissionSetupBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getCommissionSetup() {

        $cmsCommissionSetup = CmsCommissionSetup::select(
            'cms_commission_setup.effective_at',
            'cms_commission_setup.amount',
            'cms_commission_setup.minimum_amount',
            'cms_commission_setup.percentage',
            'cms_commission_setup.bonus',
            'cms_type.name'
        )
            ->where('cms_agent_id', null)
            ->where('status', 'A') // to filter by status 'A'
            ->join('cms_type', 'cms_commission_setup.cms_type_id', '=', 'cms_type.id')
            ->orderBy('cms_commission_setup.effective_at', 'DESC')
            ->get();


        $this->logger->info('CommissionSetupBO->getCommissionSetup', ['cmsCommissionSetup' => $cmsCommissionSetup]);
        
        $commissions = array();

        if ($cmsCommissionSetup) {
            foreach ($cmsCommissionSetup as $key => $value) {
                $effectiveAt  = Carbon::createFromFormat('Y-m-d', $value->effective_at)->format('M');
                $effectiveAt .= ' / ' . Carbon::createFromFormat('Y-m-d', $value->effective_at)->format('Y');

                $commissions[] = [
                    'type'          => $value->name,
                    'effective'     => $effectiveAt,
                    'amount'        => $value->amount,
                    'minimumAmount' => $value->minimum_amount,
                    'percentage'    => $value->percentage,
                    'bonus'         => $value->bonus
                ];
            }
        }

        return ['commissions' => $commissions];
    }

    public function cmsSetupApproval($startDate, $endDate) {
        $this->logger->info('CommissionSetupBO->cmsSetupApproval', [$startDate, $endDate]);

        $query = 'select * from cms_setup_approval
                  where created_at between ? and ?
                  and deleted_at is null';
        $res = $this->db->select($query,[$startDate,$endDate]);

        $setupApproval = array();

        foreach ($res as $key => $value) {

            $agentData = $this->getAgentNameByTableId($value->table_id,$value->table_name);

            $agentName = '';
            $agentId   = 0;

            if (isset($agentData['name'])) {
                $agentName = $agentData['name'];
            }
            if (isset($agentData['id'])) {
                $agentId = $agentData['id'];
            }
            

            $groupName = $this->getGroupName($value->cms_type_id);

            $setupApproval[] = [
                'id'                => $value->id,
                'table_id'          => $value->table_id,
                'table_name'        => $value->table_name,
                'description'       => $value->description,
                'accounting_status' => $value->accounting_status,
                'executive_status'  => $value->executive_status,
                'created_by'        => $this->userNameFromId($value->created_by),
                'created_at'        => date('m/d/Y', strtotime($value->created_at)),
                'updated_at'        => $value->updated_at,
                'updated_by'        => $this->userNameFromId($value->updated_by),
                'agentId'           => $agentId,
                'agentName'         => $agentName,
                'groupName'         => $groupName,
                'newDescription'    => $value->new_description,
                'type'              => $value->type
            ];
        }

        return $setupApproval;
    }

    public function userNameFromId($userId) {
        $query = "select user_fname, user_lname from users_table where user_id = ?";
        $res = $this->db->select($query, [$userId]);
        return $res[0]->user_fname . ' ' . $res[0]->user_lname;
    }

    public function commissionSetupDepartmentApproval($id, $tableId, $status, $department) {
        $this->logger->info('CommissionSetupBO->commissionSetupDepartmentApproval', ['id' => $id, 'tableId' => $tableId, 'status' => $status, 'department' => $department]);

        $existingRecord = CmsSetupApproval::where('id', $id)
        ->latest('updated_at')
        ->first();

        if ($existingRecord) {
            if ($department === 'accounting') {
                $existingRecord->accounting_status = $status;
                $existingRecord->accounting_at     = Carbon::now();
                $existingRecord->accounting_by     = Auth::user()->user_id;
                $this->addStatusTocmsAgentansSetup($existingRecord->table_name, $tableId, $status);

            } else if ($department === 'executive') {
                $existingRecord->executive_status = $status;
                $this->addStatusTocmsAgentansSetup($existingRecord->table_name, $tableId, $status);
            }
        }

        try {
            $existingRecord->save();
        } catch (\Exception $e) {
            $this->logger->error('Error saving commissionSetupDepartmentApproval: ' . $e->getMessage());
        }

        return true;
    }

    public function commissionCustomSetupDepartmentApproval($tableId, $status, $department) {
        $this->logger->info('CommissionSetupBO->commissionCustomSetupDepartmentApproval', ['tableId' => $tableId, 'status' => $status, 'department' => $department]);

        $existingRecord = CmsSetupApproval::where('table_id', $tableId)
        ->latest('updated_at')
        ->first();

        if ($existingRecord) {
            
            $this->logger->info('CommissionSetupBO->commissionCustomSetupDepartmentApproval existing', [$existingRecord]);

            if ($department === 'accounting') {

                $this->db->beginTransaction();

                try {               

                    $existingRecord->accounting_status = $status;
                    $existingRecord->save();
                    
                    if ($existingRecord->table_name == 'cms_agent_setup') {
                        
                        $query = 'select b.id
                                from cms_agent_setup a 
                                join cms_agent b on b.id = a.cms_agent_id
                                where a.id = ?
                                limit 1';
                        $res = $this->db->select($query, [$tableId]);

                        if (count($res) > 0) {
                            CmsAgentSetup::where('cms_agent_id', $res[0]->id)
                            ->where('cms_type_id', $existingRecord->cms_type_id)
                            ->update(['status' => $status]);
                        }

                        CmsSetupApproval::query()
                        ->where('id', '<', $existingRecord->id)
                        ->where('table_id', $tableId)
                        ->where('accounting_status', 'P')
                        ->delete();

                    } else {
                        CmsCommissionSetup::where('id', $tableId)->update(['status' => $status]);
                        $commissionSetup = CmsCommissionSetup::where('id', $tableId)->first();
        
                        if ($commissionSetup) {
                            $cmsAgentId = $commissionSetup->cms_agent_id;
                            $typeId = $commissionSetup->cms_type_id;
        
                            CmsAgentSetup::where('cms_agent_id', $cmsAgentId)
                            ->where('cms_type_id', $typeId)
                            ->update(['status' => $status]);
                        }
        
                        CmsCommissionSetup::where('cms_agent_id', $tableId)->update(['status' => $status]);
                    }

                    $this->db->commit();

                } catch (Exception $e) {
                    $this->db->rollback();
                    $this->logger->error('CommissionSetupBO->commissionCustomSetupDepartmentApproval'. $e->getMessage());
                }
            }
        } else {
            $this->logger->info('CommissionSetupBO->commissionCustomSetupDepartmentApproval No Existing Record');
        }
    }

    public function commissionSetupStructureDepartmentApproval($tableId, $status, $department) {
        $this->logger->info('CommissionSetupBO->commissionSetupStructureDepartmentApproval', ['tableId' => $tableId, 'status' => $status, 'department' => $department]);

        $existingRecord = CmsSetupApproval::where('table_id', $tableId)
            ->latest('updated_at') // Order by the 'updated_at' column in descending order
            ->first();

        if ($existingRecord) {
            $this->logger->info('CommissionSetupBO->commissionSetupStructureDepartmentApproval existing', [$existingRecord]);

            if ($department === 'accounting') {
                $existingRecord->accounting_status = $status;
            }
            if ($department === 'executive') {
                $existingRecord->executive_status = $status;
            }
            //cms commission setup

            if ($existingRecord->accounting_status === 'A' && $existingRecord->executive_status === 'A') {
                CmsCommissionSetup::where('id', $tableId)
                    ->where('cms_agent_id', null)
                    ->update(['status' => 'A']);
            }
        }
        try {
            $existingRecord->save();
        } catch (\Exception $e) {
            $this->logger->error('Error saving commissionCustomSetupDepartmentApproval: ' . $e->getMessage());
        }
    }

    public function addStatusTocmsAgentansSetup($tableName, $tableId, $status) {
        $this->logger->info('CommissionSetupBO->addStatusTocmsAgentansSetup', [$tableName, $tableId, $status]);

        if ($tableName == 'cms_agent') {
            $cmsAgent = CmsAgent::where('id', $tableId)->first();
            if ($cmsAgent) {
                $cmsAgent->status = $status;
                $cmsAgent->save();
            }
        } elseif ($tableName == 'cms_agent_setup') {
            CmsAgentSetup::where('cms_agent_id', $tableId)
                ->where('setup_by', 'd')
                ->update(['status' => $status]);
        } elseif ($tableName == 'cms_commission_setup') {
            CmsCommissionSetup::where('cms_agent_id', null)
                ->where('id', $tableId)
                ->update(['status' => $status]);
        }
    }


    public function save($cmsTypeId, $cmsAgentId, $effectiveAt, $amount, $percentage, $effectiveUntil) {

        $this->logger->info('CommissionSetupBO->save', [
            'cmsTypeId' => $cmsTypeId, 'cmsAgentId' => $cmsAgentId, 'effectiveAt' => $effectiveAt,
            'amount' => $amount, 'percentage' => $percentage, 'effectiveUntil' => $effectiveUntil]);

        if ($cmsTypeId != 12) {
            $effectiveUntil = null;
        }

        try {
            $cmsCommissionSetup                  = new CmsCommissionSetup();
            $cmsCommissionSetup->cms_type_id     = $cmsTypeId;
            $cmsCommissionSetup->cms_agent_id    = $cmsAgentId;
            $cmsCommissionSetup->effective_at    = $effectiveAt;
            $cmsCommissionSetup->amount          = $amount;
            $cmsCommissionSetup->percentage      = $percentage;
            $cmsCommissionSetup->status          = 'P';
            $cmsCommissionSetup->effective_until = $effectiveUntil;
            $cmsCommissionSetup->created_at      = Carbon::now();
            $cmsCommissionSetup->updated_at      = Carbon::now();
            $cmsCommissionSetup->save();
            
            $setupApproval = new CmsSetupApproval();
            $setupApproval->table_id = $cmsCommissionSetup->id;
            $setupApproval->table_name = 'cms_commission_setup';
            $setupApproval->cms_type_id = $cmsTypeId;

            if ($cmsAgentId != null && $cmsAgentId > 0) {
                $setupApproval->description = '';
                $setupApproval->new_description = 'amount $' . number_format($amount,2, ',', ' ') . '  percentage ' . $percentage .'%';
                $setupApproval->type = 'NC';
            } else {
                $previousSetup = $this->getPreviousSetup($cmsTypeId,$effectiveAt);
                $setupApproval->description     = $previousSetup;
                $carbonDate = Carbon::parse($effectiveAt);
                $charAux    = $carbonDate->format('M');
                $charAux   .= ' / ' . $carbonDate->format('Y');
                $setupApproval->new_description = 'Effective At: ' . $charAux .' amount $' . number_format($amount,2, ',', ' ') . ' and percentage ' . $percentage . '%';
                $setupApproval->type = 'CS';
            }
            $setupApproval->accounting_status = 'P';
            $setupApproval->executive_status  = 'P';
            $setupApproval->created_by        = Auth::user()->user_id;
            $setupApproval->created_at        = Carbon::now();
            $setupApproval->updated_at        = Carbon::now();
            $setupApproval->updated_by        = Auth::user()->user_id;
            $setupApproval->accounting_at     = Carbon::now();
            $setupApproval->accounting_by     = Auth::user()->user_id;
            $setupApproval->save();

        } catch (\Exception $e) {
            $this->logger->error('Error saving cmsCommissionSetup: ' . $e->getMessage());
        }
        return true;
    }

    public function getCommissionSetupByAgentId($agentId) {

        $cmsCommissionSetup = CmsCommissionSetup::select('cms_commission_setup.effective_at', 'cms_commission_setup.effective_until', 'cms_commission_setup.amount', 'cms_commission_setup.percentage', 'cms_type.name', 'cms_type.id AS cms_id')
            ->join('cms_type', 'cms_commission_setup.cms_type_id', '=', 'cms_type.id')
            ->where('cms_commission_setup.cms_agent_id', $agentId)
            ->orderBy('cms_commission_setup.effective_at', 'DESC')
            ->get();

        if ($cmsCommissionSetup) {

            $commissions = array();

            foreach ($cmsCommissionSetup as $key => $value) {
                $effectiveAt  = Carbon::createFromFormat('Y-m-d', $value->effective_at)->format('M');
                $effectiveAt .= ' / ' . Carbon::createFromFormat('Y-m-d', $value->effective_at)->format('Y');

                if ($value->effective_until !== null) {
                    $effectiveUntil = Carbon::createFromFormat('Y-m-d', $value->effective_until)->addMonth()->format('M');
                    $effectiveUntil .= ' / ' . Carbon::createFromFormat('Y-m-d', $value->effective_until)->addMonth()->format('Y');
                } else {
                    $effectiveUntil = null;
                }

                $commissions[] = [
                    'cmsTypeId'  => $value->cms_id,
                    'type'       => $value->name,
                    'effective'  => $effectiveAt,
                    'effectiveUntil' => $effectiveUntil,
                    'amount'     => $value->amount,
                    'percentage' => $value->percentage,
                ];
            }
        }

        return $commissions;
    }

    public function getType($company) {
        $this->logger->info('CommissionSetupBO->getType', [$company]);
        
        $cmsType = CmsType::query()
        ->orderBy('id', 'ASC')
        ->get();

        return $cmsType;
    }

    public function getTypes() {

        $query = "SELECT *
                    FROM cms_type";
        $res = $this->db->select($query);

        return $res;
    }

    public function getCommissionTypesByAgentId($agentId = null) {
        try {
            $query = CmsType::query('select * from cms_type a
            left join cms_agent_setup b on a.id = b.cms_type_id and b.cms_agent_id = ' . $agentId . '
            Left join cms_commission_setup c ON c.cms_type_id = b.cms_type_id 
            where deleted_at is NULL
            order by a.name')->get();

            return $query;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getCommissionSetupsFromAgentId($agentId = null) {
        try {

            $query = CmsType::select('a.*', 'c.amount', 'c.percentage')
                ->from('cms_type as a')
                ->leftJoin('cms_agent_setup as b', function ($join) use ($agentId) {
                    $join->on('a.id', '=', 'b.cms_type_id')
                        ->where('b.cms_agent_id', '=', $agentId);
                })
                ->leftJoin('cms_commission_setup as c', 'c.cms_type_id', '=', 'b.cms_type_id')
                ->whereNull('a.deleted_at')
                ->where('c.status', 'A')
                ->whereNull('c.cms_agent_id')
                ->orderBy('a.id', 'asc')
                ->get();


            return $query;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveType($name) {
        $cmsType = new CmsType();
        $cmsType->name = $name;
        $cmsType->save();

        return true;
    }

    public function approveAllAgents() {
        $this->logger->info('CommissionSetupBO->approveAllAgents');

        $currentDate = new DateTime();
        $references  = array();

        if ($currentDate->format('j') == 7) {

            $query = "select agent_status, id 
                      from cms_commission 
                      where deleted_at IS NULL 
                      and (agent_status IS NULL or agent_status = '')";
            $res = $this->db->select($query);

            if (count($res) > 0) {
                $this->logger->info('CommissionSetupBO->approveAllAgents Automatically approved agents',[$res]);

                foreach ($res as $key => $value) {
                    if ($value->agent_status == null) {
    
                        $status = [
                            "agent_status"       => 'A',
                            "agent_approved_at"  => new DateTime(),
                            "agent_approved_by"  => 0,
                        ];
        
                        $data = CmsCommission::query()
                        ->where('id', $value->id)
                        ->update($status);
    
                        $data = CmsCommission::find($value->id);
                        if (!in_array($data->reference_date,$references)) {
                            $references[] = $data->reference_date;
                        }
                    }
                }
            } else {
                $query = "select a.reference_date
                          from cms_commission a
                          join cms_agent b on b.id = a.cms_agent_id
                          where (b.company_group = 'ACL' or b.company_group = 'SNR')
                          and a.executive_status <> 'A'
                          order by reference_date desc
                          limit 1";
                $data = $this->db->select($query);

                foreach ($data as $key => $value) {
                    $this->logger->info('CommissionSetupBO->approveAllAgents last reference',[$data->reference_date]);
                    $references[] = $data->reference_date;
                }
            }

            if (count($references) > 0) {

                $cReference = null;

                foreach ($references as $key => $reference) {

                    $query = "SELECT COUNT(*) AS total,
                                     SUM(CASE WHEN a.accounting_status = 'A' THEN 1 ELSE 0 END) AS accounting_status,
                                     SUM(CASE WHEN a.manager_status = 'A' THEN 1 ELSE 0 END) AS manager_status,
                                     SUM(CASE WHEN a.agent_status = 'A' THEN 1 ELSE 0 END) AS agent_status
                                FROM cms_commission a
                                JOIN cms_agent b on b.id = a.cms_agent_id
                               WHERE a.reference_date = ?
                                 AND (b.company_group = 'ACL' OR b.company_group = 'SNR')";
                    $data = $this->db->select($query, [$reference]);

                    foreach ($data as $keyC => $commission) {

                        if ($commission->total == $commission->accounting_status &&
                            $commission->total == $commission->manager_status    &&
                            $commission->total == $commission->agent_status) {

                            $dateAux = Carbon::parse($reference);       
                            $cDate   = $dateAux->format('M/Y');

                            if ($cReference == null) {
                                $cReference = $cDate;
                            } else {
                                $cReference = $cReference.' | '. $cDate;
                            }
                        }
                    }
                }

                if ($cReference) {
                    $this->sendExecutiveEmail($cReference);
                }
            }
        }
    }

    public function getAgentNameByTableId($tableId, $tableName) {
        
        if ($tableName == 'cms_commission_setup') {
            $query = 'select c.user_fname, c.user_lname, b.id
                      from cms_commission_setup a 
                      join cms_agent b on b.id = a.cms_agent_id
                      join users_table c on c.user_id  = b.user_id
                      where a.id = ?
                      limit 1';
            $res = $this->db->select($query, [$tableId]);
        } elseif ($tableName == 'cms_agent') {
            $query = 'select b.user_fname, b.user_lname, a.id
                      from cms_agent a 
                      join users_table b on b.user_id  = a.user_id
                      where a.id = ?
                      limit 1';
            $res = $this->db->select($query, [$tableId]);
        } elseif ($tableName == 'cms_agent_setup') {
            $query = 'select c.user_fname, c.user_lname, b.id
                      from cms_agent_setup a 
                      join cms_agent b on b.id = a.cms_agent_id
                      join users_table c on c.user_id  = b.user_id
                      where a.id = ?
                      limit 1';
            $res = $this->db->select($query, [$tableId]);
        }

        $agentData = array();
        

        if (isset($res[0]->user_fname)) {
            $agentData = [
                'id'   => $res[0]->id,
                'name' => $res[0]->user_fname . ' ' . $res[0]->user_lname
                
            ];
        } else {
            $this->logger->info('CommissionSetupBO->getAgentNameByTableId', [$tableId, $tableName]);
        }        

        return $agentData;
    }

    public function getGroupName($cmsTypeId) {

        $cmsType = CmsType::query()
        ->where('id', $cmsTypeId)
        ->first();

        if ($cmsType) {
            return $cmsType->name;
        }

        return null;
    }

    public function getPreviousSetup($cmsTypeId,$effectiveAt) {
        
        $query = 'select * 
                  from cms_commission_setup 
                  where cms_type_id = ? 
                  and cms_agent_id IS NULL 
                  and effective_at <= ?
                  order by id DESC
                  LIMIT 1 OFFSET 1';
        $res = $this->db->select($query, [$cmsTypeId,$effectiveAt]);
        
        $previousSetup = '';

        if (count($res) > 0) {
            $charAux  = Carbon::createFromFormat('Y-m-d', $res[0]->effective_at)->format('M');
            $charAux .= ' / ' . Carbon::createFromFormat('Y-m-d', $res[0]->effective_at)->format('Y');
            $previousSetup = 'Effective At: ' . $charAux . ' amount $' . number_format($res[0]->amount,2, ',', ' ') . ' and percentage ' . $res[0]->percentage . '%';;
        }
        
        return $previousSetup;
    }

    public function getCompanies() {

        $userId = Auth::user()->user_id;        

        $gm = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('deleted', 0)
        ->whereIn('group_id', [32,36,52])
        ->get();

        $companies = array();

        if ($gm) {
            if ($gm->contains('group_id', 32) && $gm->contains('group_id', 36) && $gm->contains('group_id', 52)) {

                $companies = [
                    ['id' => 'ACL', 'name' => 'Alpine Credits Limited'],
                    ['id' => 'SNR', 'name' => 'Alpine Credits Limited (Senior Broker)'],
                    ['id' => 'SQC', 'name' => 'Sequence Capital'],
                    ['id' => 'SON', 'name' => 'Sequence Capital (Ontario)']
                ];

            } elseif ($gm->contains('group_id', 32) && $gm->contains('group_id', 36)) {

                $companies = [
                    ['id' => 'ACL', 'name' => 'Alpine Credits Limited'],
                    ['id' => 'SNR', 'name' => 'Alpine Credits Limited (Senior Broker)'],
                    ['id' => 'SQC', 'name' => 'Sequence Capital']
                ];

            } elseif ($gm->contains('group_id', 32) && $gm->contains('group_id', 52)) {

                $companies = [
                    ['id' => 'ACL', 'name' => 'Alpine Credits Limited'],
                    ['id' => 'SNR', 'name' => 'Alpine Credits Limited (Senior Broker)'],
                    ['id' => 'SON', 'name' => 'Sequence Capital (Ontario)']
                ];

            } elseif ($gm->contains('group_id', 36) && $gm->contains('group_id', 52)) {

                $companies = [
                    ['id' => 'SQC', 'name' => 'Sequence Capital'],
                    ['id' => 'SON', 'name' => 'Sequence Capital (Ontario)']
                ];

            } elseif ($gm->contains('group_id', 32)) {

                $companies = [
                    ['id' => 'ACL', 'name' => 'Alpine Credits Limited'],
                    ['id' => 'SNR', 'name' => 'Alpine Credits Limited (Senior Broker)']
                ];

            } elseif ($gm->contains('group_id', 36)) {

                $companies = [
                    ['id' => 'SQC', 'name' => 'Sequence Capital']
                ];
                

            } elseif ($gm->contains('group_id', 52)) {

                $companies = [
                    ['id' => 'SON', 'name' => 'Sequence Capital (Ontario)'],
                ];

            } else {

                $companies = [
                    ['id' => 'ACL', 'name' => 'Alpine Credits Limited'],
                    ['id' => 'SNR', 'name' => 'Alpine Credits Limited (Senior Broker)'],
                    ['id' => 'SQC', 'name' => 'Sequence Capital'],
                    ['id' => 'SON', 'name' => 'Sequence Capital (Ontario)'],
                ];

            }
        }

        return $companies;
    }

    public function sendExecutiveEmail($cReference) {
        if(env('APP_ENV') == 'production') {
            $toAddresses = array('arif@amurgroup.ca');
            $subject = 'CMS - Commission ' . $cReference;
            $body = "Hi,<br><br>
            Please be informed that the commission for the reference $cReference has been approved by accounting team, manager, and all agents.<br>
            Your approval is now needed to proceed with sending it to the payroll team.<br><br>
            <b><i>This is an automatic message. No response is required.</i></b><br><br>";

            $this->logger->info('CommissionSetupBO->sendExecutiveEmail - Email sent',[$toAddresses]);
            Utils::sendEmail($toAddresses, $subject, $body,'local');
        }
    }
}
