<?php

namespace App\Amur\BO\CMS;

use DateTime;
use Carbon\Carbon;
use App\Amur\Bean\IDB;
use App\Models\CmsType;
use App\Models\CmsAgent;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\CmsAgentSetup;
use App\Models\CmsCommission;
use App\Models\CmsCommissionDeal;
use App\Models\CmsCommissionDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Amur\BO\CMS\AgentSetupBO;

class CommissionSummaryBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }


    public function getCommissions($company, $referenceDate) {

        if ($company == 'ACL' || $company == 'SNR') {
            $companyRule = ' u.default_company_id in (1,3,401,2022)';
        } else {
            $companyRule = ' u.default_company_id = 701';
        }

        $commissions = array();

        $query = "select a.id, a.cms_agent_id, a.agent_status, a.manager_status, a.accounting_status,
                         a.executive_status, a.agent_dispute_reason, u.user_fname, u.user_lname, u.user_email
                    from cms_commission a
              inner join cms_agent e on e.id = a.cms_agent_id 
              inner join users_table u on u.user_id = e.user_id
                   where a.reference_date = ?
                     and a.deleted_at is null
                     and $companyRule
                     and e.company_group = ?
                order by u.user_fname, u.user_lname";                
        $res = $this->db->select($query, [$referenceDate, $company]);

        foreach($res as $key => $value) {
            $data = $this->getValues($value->id);

            $total  = $data['total'];
            $values = $data['values'];

            $commissions[] = [
                'id'                 => $value->id,
                'agent'              => $value->cms_agent_id,
                'name'               => $value->user_fname . ' ' . $value->user_lname,
                'total'              => $total,
                'agentStatus'        => $value->agent_status,
                'managerStatus'      => $value->manager_status,
                'accountingStatus'   => $value->accounting_status,
                'executiveStatus'    => $value->executive_status,
                'agentDisputeReason' => $value->agent_dispute_reason,
                'columns'            => $values,
                'email'              => $value->user_email,
            ];  
        }

        $totalCommission = array();
        $totalAux = 0;

        foreach ($commissions as $element) {

            $totalAux += $element['total'];

            if (isset($element['columns'])) {
                if (empty($totalCommission)) {
                    $totalCommission = $element['columns'];
                }else{
                    $columns = $element['columns'];
                    foreach ($columns as $key => $value) {
                        if (array_key_exists($key, $totalCommission)) {
                            if (isset($totalCommission[$key]['count']) && isset($value['count'])) {
                                $totalCommission[$key]['count'] += $value['count'];
                            }
                            if (isset($totalCommission[$key]['amount']) && isset($value['amount'])) {
                                $totalCommission[$key]['amount'] += $value['amount'];
                            }
                            if (isset($totalCommission[$key]['gross']) && isset($value['gross'])) {
                                $totalCommission[$key]['gross'] += ($value['gross']);
                            }
                        }
                    }
                }
            }
        }

        $totalCommission[] = [
            'total' => $totalAux
        ];

        $pendingAgents = $this->getPendingAgents();
        $allApproved   = $this->checkAllApproved($referenceDate, $company);

        return [
            'commission'       => $commissions,
            'pendingAgents'    => $pendingAgents,
            'totalCommissions' => $totalCommission,
            'allApproved'       => $allApproved
        ];
    }

    public function getValues($commissionId) {

        $cmsType = CmsType::query()
        ->orderBy('id', 'ASC')
        ->get();

        $values = array();
        $total  = 0;

        foreach($cmsType as $key => $value) {
            $cmsCommissionDetail = CmsCommissionDetail::query()
            ->where('cms_commission_id', $commissionId)
            ->where('cms_type_id', $value->id)
            ->first();

            if(in_array($value->id,[7,8,9,10,11,12])) {
                if($cmsCommissionDetail) {
                    $values[] = [
                        'cmsTypeId' => $value->id,
                        'count' => $cmsCommissionDetail->total_count + $cmsCommissionDetail->total_count_percentage,
                        'amount' => $cmsCommissionDetail->total_amount + $cmsCommissionDetail->total_amount_percentage
                    ];
                    $total += $cmsCommissionDetail->total_amount + $cmsCommissionDetail->total_amount_percentage;
                } else {
                    $values[] = [
                        'cmsTypeId' => $value->id,
                        'count' => 0,
                        'amount' => 0
                    ];
                }
            } else {
                if($cmsCommissionDetail) {
                    $sumGrossAmount = CmsCommissionDeal::query()
                    ->where('cms_commission_detail_id', $cmsCommissionDetail->id)
                    ->sum('gross_amount');

                    if(
                        $cmsCommissionDetail->cms_type_id == 2 ||
                        $cmsCommissionDetail->cms_type_id == 4 || 
                        $cmsCommissionDetail->cms_type_id == 6) {
                        $sumGrossAmount = ($sumGrossAmount / 2);
                    }

                    $values[] = [
                        'cmsTypeId' => $value->id,
                        'count' => $cmsCommissionDetail->total_count + $cmsCommissionDetail->total_count_percentage,
                        'amount' => $cmsCommissionDetail->total_amount + $cmsCommissionDetail->total_amount_percentage,
                        'gross' => $sumGrossAmount
                    ];
                    $total += $cmsCommissionDetail->total_amount + $cmsCommissionDetail->total_amount_percentage;
                } else {
                    $values[] = [
                        'cmsTypeId' => null,
                        'count' => 0,
                        'amount' => 0,
                        'gross' => 0
                    ];
                }
            }
        }

        return [
            'values' => $values,
            'total'  => $total
        ];
    }

    public function getGrossAmout($cmsCommissionId) {

        $query = 'select b.gross_amount 
                  from cms_commission_detail a
                  join cms_commission_deal b on b.cms_commission_detail_id = a.id 
                  where a.id = ?
                  and a.cms_type_id <= 4';
        $res = $this->db->select($query, [$cmsCommissionId]);

        $grossAmount = 0;

        foreach ($res as $key => $value) {
            $grossAmount += $value->gross_amount;
        }

        return $grossAmount;
    }


    public function calculateCommission(DateTime $startDate, DateTime $endDate, $company) {

        $allApproved = $this->checkallApproved($startDate, $company);

        if (empty($startDate) || $company == null || $allApproved == true) {
            return false;
        }

        ini_set('max_execution_time', 10800);

        $this->logger->info('CommissionSummaryBO->calculateCommission', [$startDate, $endDate, $company]);

        $queryType = "select * from cms_type";
        $resType   = $this->db->select($queryType);

        $this->db->beginTransaction();
        if(count($resType) > 0) {
            try {
                $this->deleteCommissionsTable($startDate, $company);
                $this->commissionGroup($startDate, $endDate, $company);
                $this->compareChangedCommissions($startDate, $company);
                // $this->sendEmail($company,$startDate);

                $this->db->commit();
            } catch (\Throwable $e) {
                $this->logger->error('CommissionSummaryBO->calculateCommission', [$e->getMessage(), json_encode($e->getTraceAsString())]);
                $this->db->rollback();
                return false;
            }
        }

        return true;
    }

    public function commissionGroup($startDate, $endDate, $company) {

        if ($company == 'ACL' || $company == 'SNR') {
            $companyRule = ' m.company_id in (1,3,401,2022)';
        } else {
            $companyRule = ' m.company_id = 701';
        }

        $query = "SELECT m.agent, m.signing_agent, a.co_agent_id, a.nb_referring_agent_id, a.pb_referring_agent_id, a.bdm_id,
                         m.company_id, m.mortgage_id, m.mortgage_code, a.application_id, m.brokerGroup,
                         m.brokerage_fee, m.discount_fee, mitt.discount, m.gross_amt, m.source, m.source2, a.uw_asst_id
                    FROM mortgage_table m 
               LEFT JOIN mortgage_payments_table p on m.mortgage_id = p.mortgage_id 
               LEFT JOIN mortgage_mortgagors_table mm ON m.mortgage_id = mm.mortgage_id 
               LEFT JOIN spouse_table s ON mm.spouse_id=s.spouse_id 
               LEFT JOIN application_table a ON m.application_id=a.application_id 
               LEFT JOIN status_table st ON a.status = st.id 
               LEFT JOIN mortgage_investor_tracking_table mitt ON m.mortgage_id = mitt.mortgage_id and mitt.committed in ('Yes','Looking')
         LEFT OUTER JOIN mortgage_investor_tracking_table mi ON m.mortgage_id = mi.mortgage_id AND mi.committed = 'yes'
         LEFT OUTER JOIN investor_table i on mi.investor_id = i.investor_id
                   WHERE p.payment_id = 1
                     AND $companyRule
                     AND p.period_date between ? and ?
                     AND (m.ab_loan = 'No' OR m.ab_loan = 'm_mtg') 
                     AND m.is_deleted = 'no' 
                     AND m.brokerGroup in ('Gp1','Gp2','Gp3')
                     AND m.mortgage_code <> 'DUMMY CARD'
                GROUP BY m.mortgage_id
                ORDER BY brokerGroup";
        $res = $this->db->select($query, [$startDate, $endDate]);

        $agentData = array();

        foreach ($res as $key => $value) {

            $amount = ($value->brokerage_fee + $value->discount_fee); //bonus in spreadsheet

            if ($value->brokerGroup == 'Gp1') {
                if (($value->agent == $value->signing_agent) || ($value->agent !== 0 && $value->signing_agent == 0)) {
                    if ($value->source == 13 || $value->source2 == 13) {
                        $cmsType = 5; //PB Funded in Group 3

                    } else {
                        $cmsType = 1; //NB Funded in Group 1
                    }
                } else {
                    $cmsType = 2; //NB Co-Written in Group 1
                    $agentData[] = [
                        'agent'         => $value->agent,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => $cmsType,
                        'applicationId' => $value->application_id,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];
                }

                $agentData[] = [
                    'agent'         => $value->signing_agent,
                    'count'         => 1,
                    'amount'        => $amount,
                    'discount'      => $value->discount,
                    'mortgageId'    => $value->mortgage_id,
                    'cmsType'       => $cmsType,
                    'applicationId' => $value->application_id,
                    'grossAmount'   => $value->gross_amt,
                    'bonus'         => $amount,
                    'companyId'     => $value->company_id
                ];
            } elseif ($value->brokerGroup == 'Gp2') {

                
                if ($company == 'SQC' || $company == 'SON') {

                    if ($value->bdm_id > 0) {
                        $agentData[] = [
                            'agent'         => $value->bdm_id,
                            'count'         => 1,
                            'amount'        => $amount,
                            'discount'      => $value->discount,
                            'mortgageId'    => $value->mortgage_id,
                            'cmsType'       => 11,
                            'applicationId' => $value->application_id,
                            'grossAmount'   => $value->gross_amt,
                            'bonus'         => $amount,
                            'companyId'     => $value->company_id
                        ];
                    }

                    if (($value->agent == $value->signing_agent) || ($value->agent !== 0 && $value->signing_agent == 0)) {
                        $cmsType = 3;
                        $agent = $value->agent;
                    } else {
                        $cmsType = 4;
                        $agent = $value->signing_agent;
                        $agentData[] = [
                            'agent'         => $value->signing_agent,
                            'count'         => 1,
                            'amount'        => $amount,
                            'discount'      => $value->discount,
                            'mortgageId'    => $value->mortgage_id,
                            'cmsType'       => $cmsType,
                            'applicationId' => $value->application_id,
                            'grossAmount'   => $value->gross_amt,
                            'bonus'         => $amount,
                            'companyId'     => $value->company_id
                        ];
                    }

                    $agentData[] = [
                        'agent'         => $value->agent,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => $cmsType,
                        'applicationId' => $value->application_id,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];                    

                    // $agentData[] = [
                    //     'agent'         => $agent,
                    //     'count'         => 1,
                    //     'amount'        => $amount,
                    //     'discount'      => $value->discount,
                    //     'mortgageId'    => $value->mortgage_id,
                    //     'cmsType'       => $cmsType,
                    //     'applicationId' => $value->application_id,
                    //     'grossAmount'   => $value->gross_amt,
                    //     'bonus'         => $amount,
                    //     'companyId'     => $value->company_id
                    // ];


                    if ($value->uw_asst_id > 0) {
                        $agentData[] = [
                            'agent'         => $value->uw_asst_id,
                            'count'         => 1,
                            'amount'        => $amount,
                            'discount'      => $value->discount,
                            'mortgageId'    => $value->mortgage_id,
                            'cmsType'       => 10,
                            'applicationId' => $value->application_id,
                            'grossAmount'   => $value->gross_amt,
                            'bonus'         => $amount,
                            'companyId'     => $value->company_id
                        ];                    
                    }



                } else {

                    if (($value->agent == $value->signing_agent) || ($value->agent !== 0 && $value->signing_agent == 0)) {
                        $cmsType = 3;
                    } else {
                        $cmsType = 4;
                        $agentData[] = [
                            'agent'         => $value->signing_agent,
                            'count'         => 1,
                            'amount'        => $amount,
                            'discount'      => $value->discount,
                            'mortgageId'    => $value->mortgage_id,
                            'cmsType'       => $cmsType,
                            'applicationId' => $value->application_id,
                            'grossAmount'   => $value->gross_amt,
                            'bonus'         => $amount,
                            'companyId'     => $value->company_id
                        ];
                    }
    
                    $agentData[] = [
                        'agent'         => $value->agent,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => $cmsType,
                        'applicationId' => $value->application_id,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];
                }


                
            } elseif ($value->brokerGroup == 'Gp3') {
                if (($value->agent == $value->signing_agent) || ($value->agent !== 0 && $value->signing_agent == 0)) {
                    $cmsType = 5; //PB Funded in Group 3

                } else {
                    $cmsType = 6;
                    $agentData[] = [
                        'agent'         => $value->signing_agent,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => $cmsType,
                        'applicationId' => $value->application_id,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];
                }

                $agentData[] = [
                    'agent'         => $value->agent,
                    'count'         => 1,
                    'amount'        => $amount,
                    'discount'      => $value->discount,
                    'mortgageId'    => $value->mortgage_id,
                    'cmsType'       => $cmsType,
                    'applicationId' => $value->application_id,
                    'grossAmount'   => $value->gross_amt,
                    'bonus'         => $amount,
                    'companyId'     => $value->company_id
                ];
            }

            if ($value->nb_referring_agent_id > 0 && $value->brokerGroup == 'Gp1' ) {
                // if (!($value->source == 13 || $value->source2 == 13)) {
                    $agentData[] = [
                        'agent'         => $value->nb_referring_agent_id,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => 7,
                        'applicationId' => $value->application_id,
                        'mortgageCode'  => $value->mortgage_code,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];
                // }
            }

            if ($value->pb_referring_agent_id > 0 && ($value->brokerGroup == 'Gp2' || $value->brokerGroup == 'Gp3')) {
                // if (($value->source == 13 || $value->source2 == 13)) {
                    $agentData[] = [
                        'agent'         => $value->pb_referring_agent_id,
                        'count'         => 1,
                        'amount'        => $amount,
                        'discount'      => $value->discount,
                        'mortgageId'    => $value->mortgage_id,
                        'cmsType'       => 8,
                        'applicationId' => $value->application_id,
                        'mortgageCode'  => $value->mortgage_code,
                        'grossAmount'   => $value->gross_amt,
                        'bonus'         => $amount,
                        'companyId'     => $value->company_id
                    ];
                // }
            }
            
        }

        $commisions = json_decode(json_encode($agentData));        

        if (count($commisions) > 0) {
            foreach ($commisions as $key => $value) {

                $cmsAgentId = 0;
                $resCmsAgent = $this->cmsAgent($value->agent, $company);

                if ($resCmsAgent) {
                    $cmsAgentId = $resCmsAgent->id;

                    $cmsCommissionData = $this->cmsCommission($cmsAgentId, $startDate);

                    if ($cmsCommissionData) {
                        $status = $cmsCommissionData->agent_status;

                        if (empty($status) || is_null($status)) {
                            $this->calculate(
                                $cmsAgentId,
                                $startDate,
                                $value->amount,
                                $value->discount,
                                $value->cmsType,
                                $cmsCommissionData->id,
                                $value->mortgageId,
                                $value->applicationId,
                                $value->grossAmount,
                                $value->bonus,
                                $value->companyId
                            );
                        }
                    } else {
                        $this->calculate(
                            $cmsAgentId,
                            $startDate,
                            $value->amount,
                            $value->discount,
                            $value->cmsType,
                            0,
                            $value->mortgageId,
                            $value->applicationId,
                            $value->grossAmount,
                            $value->bonus,
                            $value->companyId
                        );
                    }
                }
            }
        }

        $this->guaranteeCommission($startDate, $company);

    }

    public function calculate(
        $agent,
        $startDate,
        $totalAmount,
        $discount,
        $cmsType,
        $cmsCommissionId,
        $mortgageId,
        $applicationId,
        $grossAmount,
        $bonus,
        $companyId
    ) {

        // $cmsAgentApproved = $this->cmsAgentApproved($agent);
        // if ($cmsAgentApproved == false) {
        //     return false;
        // }

        $cmsAgentSetup = $this->cmsAgentSetup($agent, $cmsType);

        $this->logger->info('CommissionSummaryBO->calculate -totalAmount, agent, cmsType', [$totalAmount, $agent, $cmsType]);

        if ($cmsAgentSetup) {
            $commissionBy = $cmsAgentSetup->commission_by;
            $setupBy      = $cmsAgentSetup->setup_by;
        } else {
            return false;
        }

        if ($commissionBy == 'n') {
            return false;
        }

        if ($setupBy == 'd') {
            $query = "select a.amount,a.percentage,a.cms_agent_id
                        from cms_commission_setup a
                       where a.cms_type_id = ?
                         and a.cms_agent_id Is Null
                         and a.effective_at <= ?
                         and a.status = 'A'
                    order by a.effective_at desc
                       limit 1";
            $data = $this->db->select($query, [$cmsType, $startDate]);
        } else {
            $query = "select a.amount,a.percentage,a.cms_agent_id  
                        from cms_commission_setup a
                       where a.cms_type_id = ?
                         and a.cms_agent_id  = ?
                         and a.effective_at <= ?
                         and a.status = 'A'
                    order by a.effective_at desc
                       limit 1";
            $data = $this->db->select($query, [$cmsType, $agent, $startDate]);
        }

        $percentage      = 0;
        $amount          = 0;
        $commissionValue = 0;

        foreach ($data as $key => $value) {
            $amount       = $value->amount;
            $percentage   = $value->percentage;
        }

        if ($commissionBy == 'b') {
            if ($percentage > 0 || $amount > 0) {
                if ((($totalAmount * $percentage) / 100) >= $amount) {
                    $commissionValue = ((($totalAmount - $discount) * $percentage) / 100);
                    if ($commissionValue <= $amount) {
                        $commissionValue = $amount;
                        $calcType = 'A';
                    } else {
                        $calcType = 'P';
                    }
                } else {
                    $commissionValue = $amount;
                    $calcType = 'A';
                }
            } else {
                $this->logger->error('CommissionSummaryBO->calculate - commissionBy', [$agent, $cmsType, $commissionBy, $percentage, $amount]);
                return false;
            }
        } elseif ($commissionBy == 'p') {
            $commissionValue = ((($totalAmount - $discount) * $percentage) / 100);
            $calcType = 'P';
        } elseif ($commissionBy == 'a') {
            $commissionValue = $amount;
            $calcType = 'A';
        } else {
            $this->logger->error('CommissionSummaryBO->calculate - commissionBy', [$commissionBy]);
            return false;
        }

        if ($cmsCommissionId == 0) {

            $userId = Auth::user()->user_id;

            $cmsCommissionData = new CmsCommission;
            $cmsCommissionData->cms_agent_id   = $agent;
            $cmsCommissionData->reference_date = $startDate;
            $cmsCommissionData->created_by     = $userId;
            $cmsCommissionData->updated_by     = $userId;
            $cmsCommissionData->save();

            $this->saveCmsCommissionDetail($cmsCommissionData->id, $cmsType, $commissionValue, $calcType);
        } else {

            $commissionDetail = CmsCommissionDetail::query()
                ->where('cms_commission_id', $cmsCommissionId)
                ->where('cms_type_id', $cmsType)
                ->first();

            if ($commissionDetail) {
                $this->updateCmsCommissionDetail($cmsCommissionId, $cmsType, $commissionValue, $calcType);
            } else {
                $this->saveCmsCommissionDetail($cmsCommissionId, $cmsType, $commissionValue, $calcType);
            }
        }

        $this->saveCmsCommissionDeal(
            $agent,
            $startDate,
            $cmsType,
            $mortgageId,
            $applicationId,
            $grossAmount,
            $bonus,
            $discount,
            $calcType,
            $amount,
            $percentage,
            $commissionValue
        );
    }

    public function saveCmsCommissionDeal($agent, $startDate, $cmsType, $mortgageId, $applicationId,  $grossAmount, $bonus, $discount, $calcType, $amount, $percentage, $commissionValue) {

        $cmsAgent = CmsAgent::query()
        ->where('id', $agent)
        ->first();

        $userId = $cmsAgent->user_id;
        $cmsCommissionDetailId = 0;

        $query = "Select b.id as agent_id, b.user_id, c.id as commission_id, d.id as commission_detail_id  
                  From users_table a
                  Inner join cms_agent b on b.user_id = a.user_id 
                  Inner join cms_commission c on c.cms_agent_id = b.id
                  Inner join cms_commission_detail d on d.cms_commission_id = c.id 
                  Where c.reference_date = ?
                  and d.cms_type_id = ? 
                  and b.user_id = ?";
        $data = $this->db->select($query, [$startDate, $cmsType, $userId]);

        if ($data) {
            foreach ($data as $key => $value) {
                $cmsCommissionDetailId = $value->commission_detail_id;
            }
        }

        $commissionDeal = CmsCommissionDeal::query()
        ->where('cms_commission_detail_id', $cmsCommissionDetailId)
        ->where('mortgage_id', $mortgageId)
        ->first();

        if ($calcType == 'P') {
            $commissionBy = 'Percentage';
        } else {
            $commissionBy = 'Amount';
        }

        if (!$commissionDeal) {
            $cmsCommissionDeal = new CmsCommissionDeal;
            $cmsCommissionDeal->cms_commission_detail_id = $cmsCommissionDetailId;
            $cmsCommissionDeal->application_id   = $applicationId;
            $cmsCommissionDeal->mortgage_id      = $mortgageId;
            $cmsCommissionDeal->gross_amount     = $grossAmount;
            $cmsCommissionDeal->bonus            = $bonus;
            $cmsCommissionDeal->discount         = $discount;
            $cmsCommissionDeal->commission_by    = $commissionBy;
            $cmsCommissionDeal->flat_fee         = $amount;
            $cmsCommissionDeal->percentage       = $percentage;
            $cmsCommissionDeal->commission_value = $commissionValue;
            $cmsCommissionDeal->save();
        }
    }

    public function cmsCommission($agent, $startDate) {

        $data = CmsCommission::query()
        ->where('cms_agent_id', $agent)
        ->where('reference_date', $startDate)
        ->first();

        return $data;
    }

    public function cmsAgent($userId, $company) {

        $data = CmsAgent::query()
        ->where('user_id', $userId)
        ->where('status', 'A')
        ->where('company_group', $company)
        ->whereNull('deleted_at')
        ->first();

        return $data;
    }

    public function cmsAgentSetup($cmsAgentId, $cmsType) {

        $data = CmsAgentSetup::query()
        ->where('cms_agent_id', $cmsAgentId)
        ->where('cms_type_id', $cmsType)
        ->where('status', 'A')
        ->first();

        return $data;
    }

    public function saveCmsCommissionDetail($cmsCommissionId, $cmsType, $calc, $calcType) {

        $commissionData = new CmsCommissionDetail;
        $commissionData->cms_commission_id = $cmsCommissionId;
        $commissionData->cms_type_id = $cmsType;
        if ($calcType == 'P') {
            $commissionData->total_count_percentage = 1;
            $commissionData->total_amount_percentage = $calc;
        } else {
            $commissionData->total_count = 1;
            $commissionData->total_amount = $calc;
        }

        $commissionData->save();
    }

    public function updateCmsCommissionDetail($cmsCommissionId, $cmsType, $calc, $calcType) {

        $cmsDetail = CmsCommissionDetail::query()
            ->where('cms_commission_id', $cmsCommissionId)
            ->where('cms_type_id', $cmsType)
            ->first();

        if ($cmsDetail) {
            if ($calcType == 'P') {
                $commissionData = [
                    "total_count_percentage"  => $cmsDetail->total_count_percentage + 1,
                    "total_amount_percentage" => $cmsDetail->total_amount_percentage + $calc
                ];
            } else {
                $commissionData = [
                    "total_count"  => $cmsDetail->total_count + 1,
                    "total_amount" => $cmsDetail->total_amount + $calc
                ];
            }

            CmsCommissionDetail::query()
                ->where('cms_commission_id', $cmsCommissionId)
                ->where('cms_type_id', $cmsType)
                ->update($commissionData);
        }
    }

    public function summaryCommissionApproval($referenceDate, $cmsTypeId, $commissionDetailId, $agentName) {

        $query = "SELECT a.*, d.user_id,d.id,e.cms_type_id, a.commission_by, a.status, f.amount as commissionAmount,
                         a.percentage, g.application_id, g.mortgage_code,a.discount,h.province
                   FROM cms_commission_deal a 
              LEFT JOIN cms_commission_detail b ON b.id = a.cms_commission_detail_id
              Left join cms_commission_deal j on j.cms_commission_detail_id = b.id
              Left join cms_commission c on c.id = b.cms_commission_id
              Left join cms_agent d on d.id = c.cms_agent_id
              Left join cms_agent_setup e on e.cms_agent_id = d.id
              Left join cms_commission_setup f on e.cms_type_id = f.cms_type_id
              Left join mortgage_table g on g.mortgage_id = a.mortgage_id
              Left join alpine_companies_table h on h.id = g.company_id
                  WHERE a.cms_commission_detail_id = ?
                    and e.cms_type_id = ?
                    and c.reference_date = ?
                    and a.deleted_at is null
               group by g.mortgage_code";
        $res = $this->db->select($query, [$commissionDetailId, $cmsTypeId, $referenceDate]);

        $commissions = array();

        foreach ($res as $key => $value) {

            if ($value->commission_by == 'Percentage') {
                
                $valAux = $value->bonus - $value->discount;

                if ($valAux <= $value->flat_fee) {
                    $bonus = $value->flat_fee;
                }else {
                    $bonus = $valAux;
                }
                $flatFee = $value->percentage;
            }else{
                $bonus   = $value->bonus - $value->discount;
                $flatFee = $value->flat_fee;
            }

            $commissions[] = [
                'tacl'            => $value->application_id,
                'mortgageCode'    => $value->mortgage_code,
                'agent'           => $agentName,
                'grossAmount'     => $value->gross_amount,
                'commissionBy'    => $value->commission_by,
                'commissionValue' => $value->commission_value,
                'flatFee'         => $flatFee,
                'bonus'           => $bonus,
                'discount'        => $value->discount,
                'status'          => $value->status,
                'province'        => $value->province
            ];

        }

        return $commissions;
    }

    public function approveByDepartment($userId, $department, $referenceDate, $company) {

        $this->logger->info('CommissionSummaryBO->approveByDepartment', [$userId, $department, $referenceDate->format('Y-m-d'), $company]);

        $res = $this->getCommissions($company, $referenceDate->format('Y-m-d'));
        $commisions = $res['commission'];

        foreach($commisions as $commision) {
            $query = 'select b.user_email
                      from cms_agent a
                      join users_table b on b.user_id = a.user_id
                      where a.id = ? 
                      and a.company_group = ?';
            $res = $this->db->select($query, [$commision['agent'], $company]);

            if (count($res) == 0) {
                continue;
            }

            if ($department == 'accounting') {
                $cmsCommission = CmsCommission::find($commision['id']);

                if ($cmsCommission) {
                    $cmsCommission->accounting_approved_by = $userId;
                    $cmsCommission->accounting_approved_at = new DateTime();
                    $cmsCommission->accounting_status = 'A';
                    $cmsCommission->updated_by = $userId;
                    $cmsCommission->save();

                    if ($cmsCommission->manager_status == 'A' && $cmsCommission->agent_status != 'A') {
                        $name = $commision['name'];
                        $to   = array($res[0]->user_email);

                        $this->sendNotificationAgent($to, $name, $cmsCommission->reference_date);
                    }
                }
            }

            if ($department == 'managers') {
                $cmsCommission = CmsCommission::find($commision['id']);

                if ($cmsCommission) {
                    $cmsCommission->manager_approved_by = $userId;
                    $cmsCommission->manager_approved_at = new DateTime();
                    $cmsCommission->manager_status = 'A';
                    $cmsCommission->updated_by = $userId;
                    $cmsCommission->save();

                    if ($cmsCommission->accounting_status == 'A' && $cmsCommission->agent_status != 'A') {
                        $name = $commision['name'];
                        $to   = array($res[0]->user_email);
                        $this->sendNotificationAgent($to, $name, $cmsCommission->reference_date);
                    }
                }
            }

            if ($department == 'executives') {
                $cmsCommission = CmsCommission::find($commision['id']);
                
                if ($cmsCommission) {
                    $referenceDate = $cmsCommission->reference_date;
                    $cmsCommission->executive_approved_by = $userId;
                    $cmsCommission->executive_approved_at = new DateTime();
                    $cmsCommission->executive_status = 'A';
                    $cmsCommission->updated_by = $userId;
                    $cmsCommission->save();
                }
            }
        }

        if ($department == 'executives') {
            $agentSetupBO = new AgentSetupBO($this->logger, $this->db);
            $agentSetupBO->sendSpreadsheet($referenceDate, $company);
        }
    }

    public function convertNumber($id) {
        $alphabet = range('A', 'Z');
        return $alphabet[$id - 1] ?? '';
    }

    public function sendNotificationAgent($to, $name, $referenceDate) {

        if(env('APP_ENV') == 'production') {

            $this->logger->info('CommissionSummaryBO->sendNotificationAgent', [$to, $name, $referenceDate]);

            $date      = new DateTime($referenceDate);
            $reference = $date->format('F').' '.$date->format('Y');

            $subject = 'Action Required: Commission Approval Reminder';

            $body = "Dear $name,<br><br>Your commission for the month of ".$reference." has been manager-approved and is ready for your review..<br><br>
            Please visit https://strive.amurfinancial.group to review and approve your commission. You have until 5th (11:59 PM PST) of this month to do so.<br><br>
            Failure to approve by the deadline will result in auto-approval based on the manager's decision.<br><br>
            Your prompt attention is appreciated.";

            Utils::sendEmail($to, $subject, $body);
        }
    }

    public function deleteCommissionsTable(DateTime $referenceDate, $company) {
        $query = "update cms_commission a
                     set a.deleted_at = now()
                   where a.cms_agent_id in (
                        select id from cms_agent
                         where company_group = '$company')
                     and reference_date = '{$referenceDate->format('Y-m-d')}'";
        $this->db->statement($query);

        $query = "update cms_commission_deal
                     set status = null
                   where cms_commission_detail_id in (
                            select id from cms_commission_detail
                             where cms_commission_id in (
                                      select id from cms_commission
                                       where cms_agent_id in (
                                                select id from cms_agent where company_group = '$company'
                                             )
                                         and reference_date = '{$referenceDate->format('Y-m-d')}'
                                   )
                         )";
        $this->db->statement($query);
    }

    public function compareChangedCommissions(DateTime $referenceDate, $company) {
        $query = "select a.cms_agent_id,
                         a.id,
                         GROUP_CONCAT(c.cms_type_id order by c.cms_type_id) cms_type_id,
                         GROUP_CONCAT(c.total_count order by c.cms_type_id) total_count,
                         GROUP_CONCAT(c.total_amount order by c.cms_type_id) total_amount,
                         GROUP_CONCAT(c.total_count_percentage order by c.cms_type_id) total_count_percentage,
                         GROUP_CONCAT(c.total_amount_percentage order by c.cms_type_id) total_amount_percentage
                    from cms_commission a
                    join cms_agent b on a.cms_agent_id = b.id and b.company_group = ?
                    join cms_commission_detail c on a.id = c.cms_commission_id
                   where reference_date = ?
                     and a.deleted_at is not null
                group by a.id, a.cms_agent_id
                order by a.cms_agent_id, c.cms_type_id";
        $res = $this->db->select($query, [$company, $referenceDate->format('Y-m-d')]);

        $oldData = array();
        foreach ($res as $key => $value) {
            $oldData[$value->cms_agent_id] = [
                'id' => $value->id,
                'cmsTypeId' => $value->cms_type_id,
                'totalCount' => $value->total_count,
                'totalAmount' => $value->total_amount,
                'totalCountPercentage' => $value->total_count_percentage,
                'totalAmountPercentage' => $value->total_amount_percentage
            ];
        }

        $query = "select a.cms_agent_id,
                         a.id,
                         GROUP_CONCAT(c.cms_type_id order by c.cms_type_id) cms_type_id,
                         GROUP_CONCAT(c.total_count order by c.cms_type_id) total_count,
                         GROUP_CONCAT(c.total_amount order by c.cms_type_id) total_amount,
                         GROUP_CONCAT(c.total_count_percentage order by c.cms_type_id) total_count_percentage,
                         GROUP_CONCAT(c.total_amount_percentage order by c.cms_type_id) total_amount_percentage
                    from cms_commission a
                    join cms_agent b on a.cms_agent_id = b.id and b.company_group = ?
                    join cms_commission_detail c on a.id = c.cms_commission_id
                   where reference_date = ?
                     and a.deleted_at is null
                group by a.id, a.cms_agent_id
                order by a.cms_agent_id, c.cms_type_id";
        $res = $this->db->select($query, [$company, $referenceDate->format('Y-m-d')]);

        $newData = array();
        foreach ($res as $key => $value) {
            $newData[$value->cms_agent_id] = [
                'id' => $value->id,
                'cmsTypeId' => $value->cms_type_id,
                'totalCount' => $value->total_count,
                'totalAmount' => $value->total_amount,
                'totalCountPercentage' => $value->total_count_percentage,
                'totalAmountPercentage' => $value->total_amount_percentage
            ];
        }

        $this->logger->debug('CommissionSummaryBO->compareChangedCommissions - oldData', [json_encode($oldData)]);
        $this->logger->debug('CommissionSummaryBO->compareChangedCommissions - newData', [json_encode($newData)]);

        foreach ($oldData as $key => $value) {
            if (isset($newData[$key])) {
                if (
                    $value['cmsTypeId'] == $newData[$key]['cmsTypeId'] &&
                    $value['totalCount'] == $newData[$key]['totalCount'] &&
                    $value['totalAmount'] == $newData[$key]['totalAmount'] &&
                    $value['totalCountPercentage'] == $newData[$key]['totalCountPercentage'] &&
                    $value['totalAmountPercentage'] == $newData[$key]['totalAmountPercentage']
                ) {
                    //if no changes, keep the old calculation and delete the new one (by agent)
                    $this->deleteByCommissionId($newData[$key]['id']);
                    unset($newData[$key]);
                    unset($oldData[$key]);
                } else {
                    //with changes, keep the new calculation
                    $this->flagChangedDeals($value['id'], $newData[$key]['id']);
                    $this->deleteByCommissionId($value['id']);
                    unset($oldData[$key]);
                }
            } else {
                //scenario: all deal were moved to another agent
                //the old calculation should be deleted
                $this->deleteByCommissionId($value['id']);
                unset($oldData[$key]);
            }
        }

        if (count($oldData) > 0) {
            $this->logger->error('CommissionSummaryBO->compareChangedCommissions - oldData', [json_encode($oldData)]);
        }

        $query = "update cms_commission a
                     set a.deleted_at = null
                   where a.cms_agent_id in (
                            select id from cms_agent
                             where company_group = '$company')
                     and reference_date = '{$referenceDate->format('Y-m-d')}'";
        $this->db->statement($query);
    }

    public function deleteByCommissionId($id) {
        $query = "delete from cms_commission_deal a
                   where a.cms_commission_detail_id in (select b.id from cms_commission_detail b where b.cms_commission_id = $id)";
        $this->db->statement($query);

        $query = "delete from cms_commission_detail b where b.cms_commission_id = $id";
        $this->db->statement($query);

        $query = "delete from cms_commission where id = $id";
        $this->db->statement($query);
    }

    public function flagChangedDeals($oldCommissionId, $newCommissionId) {

        $query = "select id, mortgage_id, commission_value
                    from cms_commission_deal where cms_commission_detail_id in (
                            select id from cms_commission_detail
                             where cms_commission_id = ?)
                    order by mortgage_id";
        $res = $this->db->select($query, [$oldCommissionId]);

        $oldDeals = array();
        foreach ($res as $key => $value) {
            $oldDeals[$value->mortgage_id] = ['id' => $value->id, 'commissionValue' => $value->commission_value];
        }

        $this->logger->debug('CommissionSummaryBO->flagChangedDeals - oldDeals', [$oldCommissionId, json_encode($oldDeals)]);

        $query = "select id, mortgage_id, commission_value
                    from cms_commission_deal where cms_commission_detail_id in (
                            select id from cms_commission_detail
                             where cms_commission_id = ?)
                    order by mortgage_id";
        $res = $this->db->select($query, [$newCommissionId]);

        $newDeals = array();
        foreach ($res as $key => $value) {
            $newDeals[$value->mortgage_id] = ['id' => $value->id, 'commissionValue' => $value->commission_value];
        }

        $this->logger->debug('CommissionSummaryBO->flagChangedDeals - newDeals', [$newCommissionId, json_encode($newDeals)]);

        foreach ($oldDeals as $oldKey => $oldDeal) {
            if (isset($newDeals[$oldKey])) {

                if ($newDeals[$oldKey]['commissionValue'] != $oldDeal['commissionValue']) {
                    $query = "update cms_commission_deal
                                 set status = 'C'
                            where id = {$newDeals[$oldKey]['id']}";
                    $this->db->statement($query);
                }

                unset($newDeals[$oldKey]);
            }
        }

        if (count($newDeals) > 0) {
            foreach ($newDeals as $key => $value) {
                $query = "update cms_commission_deal
                             set status = 'C'
                           where id = {$value['id']}";
                $this->db->statement($query);
            }
        }
    }

    public function getCommissionByProvince($company, $referenceDate) {

        $query = "SELECT
                COALESCE(
                    NULLIF(e.user_legal_name, ''), 
                    CASE 
                        WHEN e.user_lname IS NOT NULL AND e.user_lname <> '' 
                            THEN CONCAT(e.user_lname, ', ', e.user_fname)
                        ELSE e.user_fname
                    END
                ) AS `Name`,
                SUM(CASE WHEN g.province = 'AB' THEN a.commission_value ELSE 0 END) AS `AB`,
                SUM(CASE WHEN g.province = 'BC' THEN a.commission_value ELSE 0 END) AS `BC`,
                SUM(CASE WHEN g.province = 'ON' THEN a.commission_value ELSE 0 END) AS `ON`,
                SUM(CASE WHEN g.province = 'QC' THEN a.commission_value ELSE 0 END) AS `QC`,
                SUM(CASE WHEN b.cms_type_id = 12 THEN a.commission_value ELSE 0 END) AS `GC`,
                SUM(a.commission_value) AS `Total`
            FROM cms_commission_deal a
            JOIN cms_commission_detail b ON a.cms_commission_detail_id = b.id
            JOIN cms_commission c ON b.cms_commission_id = c.id
            JOIN cms_agent d ON c.cms_agent_id = d.id
            JOIN users_table e ON d.user_id = e.user_id
            LEFT JOIN mortgage_table f ON a.mortgage_id = f.mortgage_id
            LEFT JOIN alpine_companies_table g ON f.company_id = g.id
            where d.company_group = ? and reference_date = ?
            GROUP BY e.user_id
            ORDER BY e.user_fname";
        $res = $this->db->select($query, [$company, $referenceDate]);

        return $res;
    }

    public function commissionDownloadToCSV($company, $referenceDate) {

        $commisionByProvince = $this->getCommissionByProvince($company, $referenceDate);

        $filePath =  Storage::disk('local')->path('tmp');
        $fileName = md5(uniqid()) . '.csv';

        $file = fopen($filePath . '/' . $fileName, 'w');

        fputcsv($file, ['Name', 'AB', 'BC', 'ON', 'QC', 'GC', 'Total']);

        foreach ($commisionByProvince as $commision) {
            fputcsv($file, [$commision->Name, $commision->AB, $commision->BC, $commision->ON, $commision->QC, $commision->GC, $commision->Total]);
        }

        fclose($file);

        return ['fileName' => $fileName, 'file' => base64_encode(Storage::get('tmp/' . $fileName))];
    }

    public function cmsAgentApproved($agent) {

        $query = "select a.status as agent_status, b.status as agent_setup_status
                 from cms_agent a
                 inner join cms_agent_setup b on b.cms_agent_id = a.id
                 where a.id = ?
                 and (a.status <> 'A' or b.status <> 'A')";
        $res = $this->db->select($query, [$agent]);

        if (count($res) > 0) {
            return false;
        }else {
            return true;
        }
        
    }

    public function getPendingAgents() {

        $pendingAgents = false;

        $query = "select * from cms_setup_approval
                    where (accounting_status = 'P'
                    OR (
                        (accounting_status = 'P' OR executive_status = 'P')
                        and table_name = 'cms_commission_setup'
                        and type = 'CS'
                    ))
                        and deleted_at is null
                    LIMIT 1";
        $res = $this->db->select($query);

        if ($res) {
            if (count($res) > 0) {
                $pendingAgents = true;
            }
        }

        return $pendingAgents;
    }

    public function export($colunms, $commission, $total, $types) {

        $filePath  =  Storage::disk('local')->path('tmp');
        $timestamp = now()->format('mdY_His');
        $fileName  = 'Export_'.$timestamp.'.csv';
        $file      = fopen($filePath . '/' . $fileName, 'w');

        //Types
        $columnValues = [];
        $exportText   = '';
        $columnValues[] = 'Agent';
        foreach ($types as $typesItem) {   
            $columnValues[] = $typesItem['name'];
            if ($typesItem['id'] < 7) {
                $columnValues[] = '';
                $columnValues[] = '';
            }else {
                $columnValues[] = '';
            }
        }

        $exportText = implode(", ", $columnValues);
        fwrite($file,$exportText. PHP_EOL);

        //Columns

        $exportText = '';
        foreach ($colunms as $items) {
            $concatenatedText = '';
            foreach ($items as $key => $item) {
                if ($key !== 'cmsTypeId') {
                    if ($concatenatedText == '') {
                        $concatenatedText = $key;
                    }else {
                        $concatenatedText .= ', '. $key;
                    }
                }
            }
            if ($concatenatedText !== '') {
                $concatenatedTexts[] = $concatenatedText;
            }
        }

        // $exportText = '';
        // foreach ($colunms as $item) {
        //     $concatenatedText    = implode(', ', array_keys($item));
        //     $concatenatedTexts[] = $concatenatedText;
        // }

        $exportText = implode(", ", $concatenatedTexts);
        $exportText = ','.$exportText;
        fwrite($file,$exportText. PHP_EOL);


        //Commission
        $exportText = '';
        foreach ($commission as $commissionItem) {
            $columnValues = [];
            $columnValues[] = $commissionItem['name'];
            
            foreach ($commissionItem['columns'] as $column) {
                if (isset($column['count'])) {
                    $columnValues[] = $column['count'];
                }
                if (isset($column['amount'])) {
                    $columnValues[] = $column['amount'];
                }
                if (isset($column['gross'])) {
                    $columnValues[] = $column['gross'];
                }
            }

            $columnValues[] = $commissionItem['total'];            
            $exportText = implode(", ", $columnValues);
            fwrite($file,$exportText . PHP_EOL);
        }

        //Total
        $exportText   = '';
        $columnValues = [];
        foreach ($total as $totalItem) { 
            if (isset($totalItem['count'])) {
                $columnValues[] = $totalItem['count'];
            }
            if (isset($totalItem['amount'])) {
                $columnValues[] = $totalItem['amount'];
            }
            if (isset($totalItem['gross'])) {
                $columnValues[] = $totalItem['gross'];
            }
            if (isset($totalItem['total'])) {
                $columnValues[] = $totalItem['total'];
            }
        }

        $exportText = implode(", ", $columnValues);
        $exportText = 'Total,'.$exportText;
        fwrite($file,$exportText . PHP_EOL);

        fclose($file);

        return ['fileName' => $fileName, 'file' => base64_encode(Storage::get('tmp/' . $fileName))];
    }

    public function getReconciliations($referencePeriod) {
        $totals = [
            'fundedGrossAmount' => 0,
            'fundedTotalCount' => 0,
            'commissionGrossAmount' => 0,
            'commissionTotalCount' => 0
        ];

        $data = array();

        //Agent = Signing Agent
        $sql = "select concat(d.user_fname, ' ', d.user_lname) agent, sum(m.gross_amt) gross_amount, count(*) total_count
                  from mortgage_table m
                  join mortgage_payments_table p on m.mortgage_id = p.mortgage_id
                  join application_table a ON m.application_id = a.application_id
             left join users_table d on m.agent = d.user_id
                 where m.is_deleted = 'no'
                   and p.payment_id = 1
                   and m.company_id in (1,3,401,2022,701)
                   and p.period_date between ? and ?
                   and m.agent = m.signing_agent
                   and m.ab_loan <> 'c_mtg'
              group by concat(d.user_fname, ' ', d.user_lname)
              order by concat(d.user_fname, ' ', d.user_lname)";
        $res = $this->db->select($sql,[$referencePeriod->format('Y-m-01'), $referencePeriod->format('Y-m-t')]);

        foreach($res as $key => $value) {
            $data[$value->agent] = [
                'name' => $value->agent,
                'fundedGrossAmount' => $value->gross_amount,
                'fundedTotalCount' => $value->total_count,
                'commissionGrossAmount' => 0,
                'commissionTotalCount' => 0
            ];

            $totals['fundedGrossAmount'] += $value->gross_amount;
            $totals['fundedTotalCount'] += $value->total_count;
        }

        //Agent only
        $sql = "select concat(d.user_fname, ' ', d.user_lname) agent, sum(m.gross_amt) / 2 gross_amount, count(*) / 2 total_count
                  from mortgage_table m
                  join mortgage_payments_table p on m.mortgage_id = p.mortgage_id
                  join application_table a ON m.application_id = a.application_id
             left join users_table d on m.agent = d.user_id
                 where m.is_deleted = 'no'
                   and p.payment_id = 1
                   and m.company_id in (1,3,401,2022,701)
                   and p.period_date between ? and ?
                   and m.agent <> m.signing_agent
                   and m.ab_loan <> 'c_mtg'
              group by concat(d.user_fname, ' ', d.user_lname)
              order by concat(d.user_fname, ' ', d.user_lname)";
        $res = $this->db->select($sql,[$referencePeriod->format('Y-m-01'), $referencePeriod->format('Y-m-t')]);

        foreach($res as $key => $value) {
            if(isset($data[$value->agent])) {
                $data[$value->agent]['fundedGrossAmount'] += $value->gross_amount;
                $data[$value->agent]['fundedTotalCount'] += $value->total_count;
                $data[$value->agent]['commissionGrossAmount'] += 0;
                $data[$value->agent]['commissionTotalCount'] += 0;
            } else {
                $data[$value->agent] = [
                    'name' => $value->agent,
                    'fundedGrossAmount' => $value->gross_amount,
                    'fundedTotalCount' => $value->total_count,
                    'commissionGrossAmount' => 0,
                    'commissionTotalCount' => 0
                ];
            }

            $totals['fundedGrossAmount'] += $value->gross_amount;
            $totals['fundedTotalCount'] += $value->total_count;
        }

        //Signing Agent only
        $sql = "select concat(d.user_fname, ' ', d.user_lname) agent, sum(m.gross_amt) / 2 gross_amount, count(*) / 2 total_count
                  from mortgage_table m
                  join mortgage_payments_table p on m.mortgage_id = p.mortgage_id
                  join application_table a ON m.application_id = a.application_id
             left join users_table d on m.signing_agent = d.user_id
                 where m.is_deleted = 'no'
                   and p.payment_id = 1
                   and m.company_id in (1,3,401,2022,701)
                   and p.period_date between ? and ?
                   and m.agent <> m.signing_agent
                   and m.ab_loan <> 'c_mtg'
              group by concat(d.user_fname, ' ', d.user_lname)
              order by concat(d.user_fname, ' ', d.user_lname)";
        $res = $this->db->select($sql,[$referencePeriod->format('Y-m-01'), $referencePeriod->format('Y-m-t')]);

        foreach($res as $key => $value) {
            if(isset($data[$value->agent])) {
                $data[$value->agent]['fundedGrossAmount'] += $value->gross_amount;
                $data[$value->agent]['fundedTotalCount'] += $value->total_count;
                $data[$value->agent]['commissionGrossAmount'] += 0;
                $data[$value->agent]['commissionTotalCount'] += 0;
            } else {
                $data[$value->agent] = [
                    'name' => $value->agent,
                    'fundedGrossAmount' => $value->gross_amount,
                    'fundedTotalCount' => $value->total_count,
                    'commissionGrossAmount' => 0,
                    'commissionTotalCount' => 0
                ];
            }

            $totals['fundedGrossAmount'] += $value->gross_amount;
            $totals['fundedTotalCount'] += $value->total_count;
        }

        $commission = array();

        $res = $this->getCommissions('ACL', $referencePeriod);
        if(isset($res['commission'])) {
            $commission = array_merge($commission, $res['commission']);  
        }

        $res = $this->getCommissions('SNR', $referencePeriod);
        if(isset($res['commission'])) {
            $commission = array_merge($commission, $res['commission']);
        }

        $res = $this->getCommissions('SQC', $referencePeriod);
        if(isset($res['commission'])) {
            $commission = array_merge($commission, $res['commission']);
        }

        $res = $this->getCommissions('SON', $referencePeriod);
        if(isset($res['commission'])) {
            $commission = array_merge($commission, $res['commission']);
        }

        foreach($commission as $value) {
            if(isset($data[$value['name']])) {
                $commissionGrossAmount = 0;
                $commissionTotalCount = 0;
                foreach($value['columns'] as $v) {
                    if(!in_array($v['cmsTypeId'],[7,8,9,10])) {
                        $commissionGrossAmount += $v['gross'] ?? 0;
                        $commissionTotalCount += $v['count'] ?? 0;
                    }
                }

                $data[$value['name']]['commissionGrossAmount'] = $commissionGrossAmount;
                $data[$value['name']]['commissionTotalCount'] = $commissionTotalCount;

                $totals['commissionGrossAmount'] += $commissionGrossAmount;
                $totals['commissionTotalCount'] += $commissionTotalCount;
            }
        }

        return [
            'reconciliation' => array_values($data),
            'totals' => $totals
        ];
    }

    public function sendEmail($company, $startDate) {

        $this->logger->info('CommissionSummaryBO->sendEmail', [$company, $startDate]);

        if(env('APP_ENV') == 'production') {

            $query = "SELECT 
                        COUNT(*) AS total_records,
                        COUNT(CASE WHEN a.accounting_status = 'A' THEN 1 END) AS approved_records
                    FROM cms_commission a
                    JOIN cms_agent b ON b.id = a.cms_agent_id
                    WHERE a.reference_date = ?
                    AND b.company_group = ?";
            $res = $this->db->select($query, [$startDate, $company]);

            if ($res[0]->total_records == $res[0]->approved_records) {

                $this->logger->info('CommissionSummaryBO->sendEmail Sending email... Total Records - Approved Records', [$res[0]->total_records, $res[0]->approved_records]);

                if ($company == 'ACL') {
                    $toAddresses = array('shahab@alpinecredits.ca');
                } elseif($company == 'SNR') {
                    $toAddresses = array('colin@alpinecredits.ca');
                } elseif($company == 'SQC') {
                    $toAddresses = array('micah@sequencecapital.ca');
                } elseif($company == 'SON') {
                    $toAddresses = array('sebastien@sequencecapital.ca');
                }
    
                $dateAux = Carbon::parse($startDate);
                $cDate = $dateAux->format('M/Y');
                $subject = 'CMS - Commission ' . $cDate;
                $body = "Hi, <br><br>
                Please be informed that the commission calculation for the reference " . $cDate . " has been successfully completed.<br> 
                We kindly request that you review it carefully and approve it to proceed with the process.<br><br>
                <b><i>This is an automatic message. No response is required.</i></b><br><br>
                Best regards,<br>
                IT Team";
    
                Utils::sendEmail($toAddresses, $subject, $body,'local');
            } else {
                $this->logger->info('CommissionSummaryBO->sendEmail email cannot be sent: Total Records - Approved Records', [$res[0]->total_records, $res[0]->approved_records]);
            }
        }
    }

    public function checkAllApproved($startDate, $company) {
        
        $allApproved = false;

        $query = "select count(*) as total,
                         SUM(CASE WHEN a.accounting_status = 'A' THEN 1 ELSE 0 END) AS accounting_status,
                         SUM(CASE WHEN a.manager_status = 'A' THEN 1 ELSE 0 END) AS manager_status,
                         SUM(CASE WHEN a.agent_status = 'A' THEN 1 ELSE 0 END) AS agent_status,
                         SUM(CASE WHEN a.executive_status  = 'A' THEN 1 ELSE 0 END) AS executive_status
                    from cms_commission a
                    join cms_agent b on b.id = a.cms_agent_id
                   where a.reference_date = ?
                     and b.company_group = ?";
        $data = $this->db->select($query, [$startDate, $company]);

        if(count($data) > 0) {
            if(
                $data[0]->total > 0 &&
                $data[0]->total == $data[0]->accounting_status &&
                $data[0]->total == $data[0]->manager_status    &&
                $data[0]->total == $data[0]->agent_status      &&
                $data[0]->total == $data[0]->executive_status) {
                    $allApproved = true;
            }
        }

        return $allApproved;
    }

    public function guaranteeCommission($startDate, $company) {

        $this->logger->info('CommissionSummaryBO->guaranteeCommission', [$startDate, $company]);

        $cmsAgent = CmsAgent::query()
        ->where('company_group', $company)
        ->where('status', 'A')
        ->get();

        foreach ($cmsAgent as $agent) {           
            
            $cmsAgentSetup = CmsAgentSetup::query()
            ->where('cms_agent_id', $agent->id)
            ->where('status', 'A')
            ->where('cms_type_id', 12)
            ->first();

            if ($cmsAgentSetup) {

                if ($cmsAgentSetup->commission_by == 'a') {

                    if ($cmsAgentSetup->setup_by == 'd') {
                        $query = "select a.amount,a.percentage,a.cms_agent_id
                                    from cms_commission_setup a
                                where a.cms_type_id = 12
                                    and a.cms_agent_id Is Null
                                    and a.effective_at <= ?
                                    and a.status = 'A'
                                order by a.effective_at desc
                                limit 1";
                        $data = $this->db->select($query, [$startDate]);
                    } else {
                        $query = "select a.amount,a.percentage,a.cms_agent_id  
                                    from cms_commission_setup a
                                where a.cms_type_id = 12
                                    and a.cms_agent_id  = ?
                                    and a.effective_at <= ?
                                    and a.effective_until >= ?
                                    and a.status = 'A'
                                order by a.effective_at desc
                                limit 1";
                        $data = $this->db->select($query, [$agent->id, $startDate, $startDate]);
                    }

                    if (count($data) > 0) {

                        $amount = $data[0]->amount;

                        $cmsCommission = CmsCommission::query()
                        ->where('cms_agent_id', $agent->id)
                        ->where('reference_date', $startDate)
                        ->first();

                        if (!$cmsCommission) {

                            $userId = Auth::user()->user_id;

                            $cmsCommissionData = new CmsCommission;
                            $cmsCommissionData->cms_agent_id   = $agent->id;
                            $cmsCommissionData->reference_date = $startDate;
                            $cmsCommissionData->created_by     = $userId;
                            $cmsCommissionData->updated_by     = $userId;
                            $cmsCommissionData->save();

                            $this->saveCmsCommissionDetail($cmsCommissionData->id, 12, $amount, 'a');
                        } else {

                            $cmsCommissionId = $cmsCommission->id;

                            $commissionDetail = CmsCommissionDetail::query()
                            ->where('cms_commission_id', $cmsCommissionId)
                            ->where('cms_type_id', 12)
                            ->first();

                            if ($commissionDetail) {
                                $this->updateCmsCommissionDetail($cmsCommissionId, 12, $amount, 'a');
                            } else {
                                $this->saveCmsCommissionDetail($cmsCommissionId, 12, $amount, 'a');
                            }
                        }

                        $this->saveCmsCommissionDeal($agent->id,$startDate,12,0,0,0,0,0,'a',0,0,$amount);

                    }
                }
            }
        }
    }

}