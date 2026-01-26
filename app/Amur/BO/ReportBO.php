<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use Illuminate\Support\Facades\DB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\GeneralReportUser;
use App\Models\MicPipeline;
use DateInterval;
use DateTime;

class ReportBO {

    private $logger;
    private $db;
    private $originationPipeline = [];
    private $originationPipelineDetail = [];

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($userId) {
        $query = "select c.*, d.id favorite from group_members_table a
                    join general_report_group b on a.group_id = b.group_id
                    join general_report c on b.general_report_id = c.id
               left join general_report_user d on c.id = d.general_report_id and d.user_id = ? and d.deleted_at is null
                    where a.user_id = ?
                    and a.deleted = 0
                    and b.deleted_at is null
                    and c.deleted_at is null
                    group by c.id
                    order by c.department, c.section, c.sequence";
        $res = $this->db->select($query, [$userId, $userId]);

        $reports = array();
        foreach ($res as $key => $value) {
            $reports[$value->department][$value->section][] = [
                'id' => $value->id,
                'name' => $value->name,
                'route' => $value->route,
                'section' => $value->section,
                'favorite' => is_null($value->favorite) ? false : true
            ];
        }

        return $reports;
    }

    public function saveReportFavourites($id, $reportId) {
        $generalReportUser = GeneralReportUser::query()
            ->where('user_id', $id)
            ->where('general_report_id', $reportId)
            ->first();

        if ($generalReportUser) {
            $generalReportUser->delete();
        } else {
            $generalReportUser = new GeneralReportUser();
            $generalReportUser->user_id = $id;
            $generalReportUser->general_report_id = $reportId;
            $generalReportUser->save();
        }
    }

    public function getMicPipeline() {

        MicPipeline::query()
            ->where('reference_date', (new DateTime())->format('Y-m-d'))
            ->forceDelete();

        $this->getInitialDocs();
        $this->getSigning(null, new DateTime());
        $this->getFunding(null, new DateTime());
        $this->getFunded('Mic Pipeline');

        //get the total gross amount
        $totalAmount = $this->getGrossTotal();

        $query = "select a.*, b.last_name, c.name
                    from mic_pipeline a
                    join investor_table b ON b.investor_id = a.investor_id
                    join alpine_companies_table c ON a.origination_company_id = c.id
                   where a.reference_date = ?
                order by c.name";
        $res = $this->db->select($query, [(new DateTime())->format('Y-m-d')]);

        $alpineReports = array();
        $sequenceReports = array();
        $directToMICReports = array();
        $micSummary = array();
        $micAlpine = array();
        $micSequence = array();
        $micDirectToMIC = array();
        $micAlpineWithFunded = array();
        $micSequenceWithFunded = array();
        $micDirectToMICWithFunded = array();
        $micSummaryTotalStatus = array();
        $micSummaryTotalStatusAlpine = array();
        $micSummaryTotalStatusSequence = array();
        $micSummaryTotalStatusDirectToMIC = array();
        foreach ($res as $key => $value) {
            if ($value->name === 'Alpine Credits Limited') {
                $alpineReports[$value->stage][$value->last_name][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'last_name' => $value->last_name,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            } elseif ($value->name === 'Sequence Capital') {
                $sequenceReports[$value->stage][$value->last_name][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'last_name' => $value->last_name,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            } else {
                $directToMICReports[$value->stage][$value->last_name][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'last_name' => $value->last_name,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            }

            $micSummary[$value->last_name][$value->stage][] = [
                'name' => $value->name,
                'gross_amount' => $value->gross_amount,
                'weighted_average_lvt' => $value->weighted_average_lvt,
                'weighted_average_yield' => $value->weighted_average_yield,
                'count' => $value->count
            ];
            $micSummaryTotalStatus[$value->stage][] = [
                'name' => $value->name,
                'gross_amount' => $value->gross_amount,
                'weighted_average_lvt' => $value->weighted_average_lvt,
                'weighted_average_yield' => $value->weighted_average_yield,
                'count' => $value->count
            ];
            if ($value->name === 'Alpine Credits Limited') {
                $micSummaryTotalStatusAlpine[$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            }
            if ($value->name === 'Sequence Capital') {
                $micSummaryTotalStatusSequence[$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            }
            if ($value->name !== 'Alpine Credits Limited' && $value->name !== 'Sequence Capital') {
                $micSummaryTotalStatusDirectToMIC[$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            }

            if ($value->name === 'Alpine Credits Limited') {
                if ($value->stage !== 'Funded') {
                    $micAlpine[$value->last_name][$value->stage][] = [
                        'name' => $value->name,
                        'gross_amount' => $value->gross_amount,
                        'weighted_average_lvt' => $value->weighted_average_lvt,
                        'weighted_average_yield' => $value->weighted_average_yield,
                        'count' => $value->count
                    ];
                }
            } elseif ($value->name === 'Sequence Capital') {
                if ($value->stage !== 'Funded') {
                    $micSequence[$value->last_name][$value->stage][] = [
                        'name' => $value->name,
                        'gross_amount' => $value->gross_amount,
                        'weighted_average_lvt' => $value->weighted_average_lvt,
                        'weighted_average_yield' => $value->weighted_average_yield,
                        'count' => $value->count
                    ];
                }
            } else {
                if ($value->stage !== 'Funded') {
                    $micDirectToMIC[$value->last_name][$value->stage][] = [
                        'name' => $value->name,
                        'gross_amount' => $value->gross_amount,
                        'weighted_average_lvt' => $value->weighted_average_lvt,
                        'weighted_average_yield' => $value->weighted_average_yield,
                        'count' => $value->count
                    ];
                }
            }

            if ($value->name === 'Alpine Credits Limited') {
                $micAlpineWithFunded[$value->last_name][$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            } elseif ($value->name === 'Sequence Capital') {
                $micSequenceWithFunded[$value->last_name][$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            } else {
                $micDirectToMICWithFunded[$value->last_name][$value->stage][] = [
                    'name' => $value->name,
                    'gross_amount' => $value->gross_amount,
                    'weighted_average_lvt' => $value->weighted_average_lvt,
                    'weighted_average_yield' => $value->weighted_average_yield,
                    'count' => $value->count
                ];
            }
        }

        return [
            'sequenceReports' => $sequenceReports,
            'alpineReports' => $alpineReports,
            'directToMICReports' => $directToMICReports,
            'micSummary' => $micSummary,
            'micAlpine' => $micAlpine,
            'micDirectToMIC' => $micDirectToMIC,
            'micSequence' => $micSequence,
            'micAlpineWithFunded' => $micAlpineWithFunded,
            'micSequenceWithFunded' => $micSequenceWithFunded,
            'micDirectToMICWithFunded' => $micDirectToMICWithFunded,
            'totalAmount' => $totalAmount,
            'micSummaryTotalStatus' => $micSummaryTotalStatus,
            'micSummaryTotalStatusAlpine' => $micSummaryTotalStatusAlpine,
            'micSummaryTotalStatusSequence' => $micSummaryTotalStatusSequence,
            'micSummaryTotalStatusDirectToMIC' => $micSummaryTotalStatusDirectToMIC
        ];
    }

    public function getGrossTotal() {

        $query = "select a.id,a.origination_company_id, a.investor_id, a.stage, a.gross_amount,
                         a.weighted_average_lvt, a.weighted_average_yield, a.count, a.reference_date
                    from mic_pipeline a
                    join investor_table b on a.investor_id = b.investor_id
                   where a.reference_date = ?
                     and a.origination_company_id = 0";
        $res = $this->db->select($query, [(new DateTime())->format('Y-m-d')]);

        $total = array();
        foreach ($res as $key => $value) {
            $total[$value->stage][$value->investor_id][] = [
                'gross_amount' => $value->gross_amount,
                'weighted_average_lvt' => $value->weighted_average_lvt,
                'weighted_average_yield' => $value->weighted_average_yield,
                'count' => $value->count
            ];
        }

        return $total;
    }

    public function saveMicReports() {
        $this->getInitialDocs();
        $this->getSigning(null, new DateTime());
        $this->getFunding(null, new DateTime());
        $this->getFunded('Mic Pipeline');
    }

    public function getSigningQuery($condition = '') {
        $query = "select sq.application_id, a.agent, a.funding_date, ut.user_fname, ut.user_lname, if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))) origination_company_id,
                         sit.investor_id, sq.ltv, sit.yield, sq.gross gross_amount, a.status,
                         sit.ap_inv_co, sit.ap_amount, sit.ap_yield, sit.bp_inv_co, sit.bp_amount, sit.bp_yield, sit.cp_inv_co, sit.cp_amount, sit.cp_yield, a.funding_date,
                         sq.mortgage_id, sq.saved_quote_id, a.status as status_id
                    from saved_quote_table sq
               left join sale_investor_table sit on sq.saved_quote_id = sit.saved_quote_id and sit.fm_committed in ('yes', 'looking')
                    join application_table a on sq.application_id = a.application_id and a.company in (1,3,401,701,2022)
                    left join users_table ut on ut.user_id = a.agent 
                    where sq.disburse = 'Yes'
                        and (a.status = '10' or a.status = '14')
                        $condition
                    order by if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))), sit.investor_id";
        return $query;
    }

    public function getSigning($report = null, DateTime $endDate) {

        $this->logger->info('ReportBO->getSigning', [$report]);

        $condition = '';
        if ($report == 'expectedFunding') {
            if (empty($endDate)) {
                $condition = 'and a.funding_date <= CURDATE()';
            } else {
                $condition = "and a.funding_date <= '{$endDate->format('Y-m-d')}'";
            }
        }

        $query = $this->getSigningQuery($condition);
        $result = $this->db->select($query);

        if (in_array($report, ['returnResult', 'expectedFunding'])) {
            return $result;
        }

        $data = array();
        $data2 = array();
        foreach ($result as $key => $value) {
            $investors = array();
            if ($value->investor_id == 1971) {
                //AB Loan
                if (!is_null($value->ap_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->ap_inv_co), 'amount' => $value->ap_amount, 'yield' => $value->ap_yield];
                }
                if (!is_null($value->bp_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->bp_inv_co), 'amount' => $value->bp_amount, 'yield' => $value->bp_yield];
                }
                if (!is_null($value->cp_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->cp_inv_co), 'amount' => $value->cp_amount, 'yield' => $value->cp_yield];
                }
            } else {
                $investors[] = ['id'  => $value->investor_id, 'amount' => $value->gross_amount, 'yield' => $value->yield];
            }

            foreach ($investors as $investor) {
                //Origination + Lender
                if (isset($data[$value->origination_company_id][$investor['id']])) {
                    $data[$value->origination_company_id][$investor['id']]['count']++;
                    $data[$value->origination_company_id][$investor['id']]['grossAmount'] += $investor['amount'];
                    $data[$value->origination_company_id][$investor['id']]['totalGrossLTV'] += $investor['amount'] * $value->ltv;
                    $data[$value->origination_company_id][$investor['id']]['totalGrossYield'] += $investor['amount'] * $investor['yield'];
                } else {
                    $data[$value->origination_company_id][$investor['id']] = [
                        'count' => 1,
                        'grossAmount' => $investor['amount'],
                        'totalGrossLTV' => $investor['amount'] * $value->ltv,
                        'totalGrossYield' => $investor['amount'] * $investor['yield'],
                    ];
                }

                //Lender
                if (isset($data2[$investor['id']])) {
                    $data2[$investor['id']]['count']++;
                    $data2[$investor['id']]['grossAmount'] += $investor['amount'];
                    $data2[$investor['id']]['totalGrossLTV'] += $investor['amount'] * $value->ltv;
                    $data2[$investor['id']]['totalGrossYield'] += $investor['amount'] * $investor['yield'];
                } else {
                    $data2[$investor['id']] = [
                        'count' => 1,
                        'grossAmount' => $investor['amount'],
                        'totalGrossLTV' => $investor['amount'] * $value->ltv,
                        'totalGrossYield' => $investor['amount'] * $investor['yield'],
                    ];
                }
            }
        }

        foreach ($data as $originationCompanyId => $originationCompanies) {
            foreach ($originationCompanies as $investorId => $value) {
                $today = (new DateTime())->format('Y-m-d');

                $existingRecord = MicPipeline::query()
                    ->where('origination_company_id', $originationCompanyId)
                    ->where('investor_id', $investorId)
                    ->where('stage', 'Signing')
                    ->where('reference_date', $today)
                    ->first();

                if ($existingRecord) {
                    // Update ltv and yield , gross amount fields
                    $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                    $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                    $existingRecord->gross_amount = $value['grossAmount'];
                    $existingRecord->save();
                } else {
                    $micPipeline = new MicPipeline();
                    $micPipeline->origination_company_id = $originationCompanyId;
                    $micPipeline->investor_id = $investorId;
                    $micPipeline->stage = 'Signing';
                    $micPipeline->gross_amount = $value['grossAmount'];
                    $micPipeline->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                    $micPipeline->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                    $micPipeline->count = $value['count'];
                    $micPipeline->reference_date = $today;
                    $micPipeline->save();
                }
            }
        }

        foreach ($data2 as $investorId => $value) {
            $today = (new DateTime())->format('Y-m-d');

            $existingRecord = MicPipeline::query()
                ->where('origination_company_id', 0)
                ->where('investor_id', $investorId)
                ->where('stage', 'Signing')
                ->where('reference_date', $today)
                ->first();

            if ($existingRecord) {
                // Update ltv and yield , gross amount fields
                $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                $existingRecord->gross_amount = $value['grossAmount'] ? $value['grossAmount'] : 0;
                $existingRecord->save();
            } else {
                $micPipeline = new MicPipeline();
                $micPipeline->origination_company_id = 0;
                $micPipeline->investor_id = $investorId;
                $micPipeline->stage = 'Signing';
                $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                $micPipeline->reference_date = $today;
                $micPipeline->save();
            }
        }
    }


    public function getFundingQuery() {
        $query = "select sq.application_id, at.agent, at.funding_date, ut.user_fname, ut.user_lname, if(at.company = 1,1,if(at.company = 3, 1, if(at.company = 401, 1, if(at.company = 2022, 1, at.company)))) origination_company_id,
                         ifnull(si.investor_id,0) investor_id, sq.ltv, si.yield, sq.gross gross_amount, at.funding_date,
                         sq.mortgage_id, sq.saved_quote_id, at.status as status_id
                    from saved_quote_table sq
                    join application_table at on sq.application_id = at.application_id and company in (1,3,401,2022)
               left join status_table st on at.status = st.id
               left join sale_investor_table si on sq.saved_quote_id = si.saved_quote_id and (si.fm_committed = 'Yes' or si.fm_committed = 'Looking')
               left join users_table ut on ut.user_id = at.agent
                   where at.status = '13'
                     and sq.disburse = 'Yes'
                     and not exists (select 'x' from mortgage_table aa where aa.transfer_id = sq.mortgage_id)
                     and not exists (select 'x' from mortgage_table bb where bb.mortgage_id = sq.mortgage_id and bb.ab_loan = 'm_mtg')
        ";
        return $query;
    }

    public function getFunding($report = null, DateTime $endDate) {
        $query = $this->getFundingQuery();
        $this->logger->info('ReportBO->getFunding', [$report]);

        $condition = '';
        if ($report == 'expectedFunding') {
            if (empty($endDate)) {
                $condition = 'and at.funding_date <= CURDATE()';
            } else {
                $condition = "and at.funding_date <= '{$endDate->format('Y-m-d')}'";
            }
        }

        $query = "select sq.application_id, at.agent, at.funding_date, ut.user_fname, ut.user_lname, if(at.company = 1,1,if(at.company = 3, 1, if(at.company = 401, 1, if(at.company = 2022, 1, at.company)))) origination_company_id,
                         ifnull(si.investor_id,0) investor_id, sq.ltv, si.yield, sq.gross gross_amount, at.funding_date
                    from saved_quote_table sq
                    join application_table at on sq.application_id = at.application_id and company in (1,3,401,2022,701)
               left join status_table st on at.status = st.id
               left join sale_investor_table si on sq.saved_quote_id = si.saved_quote_id and (si.fm_committed = 'Yes' or si.fm_committed = 'Looking')
               left join users_table ut on ut.user_id = at.agent
                   where at.status = '13'
                     and sq.disburse = 'Yes'
                     and not exists (select 'x' from mortgage_table aa where aa.transfer_id = sq.mortgage_id)
                     and not exists (select 'x' from mortgage_table bb where bb.mortgage_id = sq.mortgage_id and bb.ab_loan = 'm_mtg')
                         $condition
                        
                   union all
                  select a.application_id, at.agent, at.funding_date, ut.user_fname, ut.user_lname, if(a.company_id = 1,1,if(a.company_id = 3, 1, if(a.company_id = 401, 1, if(a.company_id = 2022, 1, a.company_id)))) company_id,
                         b.investor_id, a.ltv, b.yield, a.gross_amt, at.funding_date
                    from mortgage_table a
                    join application_table at on at.application_id = a.application_id
                    join mortgage_investor_tracking_table b on a.mortgage_id = b.mortgage_id
                    left join users_table ut on ut.user_id = at.agent
                   where a.ab_loan = 'c_mtg'
                     and a.is_deleted = 'no'
                     and b.committed = 'Yes'
                     and not exists (select 'x' from mortgage_table aa where aa.is_deleted = 'no' and aa.transfer_id = a.mortgage_id)
                         $condition

                   union all
                  select a.application_id, at.agent, at.funding_date, ut.user_fname, ut.user_lname, if(a.company_id = 1,1,if(a.company_id = 3, 1, if(a.company_id = 401, 1, if(a.company_id = 2022, 1, a.company_id)))) origination_company_id,
                         b.investor_id, a.ltv, b.yield, a.gross_amt, at.funding_date
                    from mortgage_table a
                    join mortgage_investor_tracking_table b on a.mortgage_id = b.mortgage_id
                    join application_table at on a.application_id = at.application_id
                    join status_table d on at.status = d.id
                    left join users_table ut on ut.user_id = at.agent
                   where a.company_id in (1,3,401,2022,701)
                     and b.investor_id in (31,100,248)
                     and b.committed = 'Yes'
                     and a.is_deleted = 'no'
                     and a.ab_loan = 'No'
                     and not exists (select * from mortgage_table aa where aa.transfer_id = a.mortgage_id and aa.is_deleted = 'no')
                     and exists (select * from saved_quote_table bb where bb.mortgage_id = a.mortgage_id)
                     and at.status = 9
                         $condition
        ";
        $result = $this->db->select($query);

        if (in_array($report, ['returnResult', 'expectedFunding'])) {
            return $result;
        }

        $data = array();
        $data2 = array();
        foreach ($result as $key => $value) {
            //Origination + Lender
            if (isset($data[$value->origination_company_id][$value->investor_id])) {
                $data[$value->origination_company_id][$value->investor_id]['count']++;
                $data[$value->origination_company_id][$value->investor_id]['grossAmount'] += $value->gross_amount;
                $data[$value->origination_company_id][$value->investor_id]['totalGrossLTV'] += $value->gross_amount * $value->ltv;
                $data[$value->origination_company_id][$value->investor_id]['totalGrossYield'] += $value->gross_amount * $value->yield;
            } else {
                $data[$value->origination_company_id][$value->investor_id] = [
                    'count' => 1,
                    'grossAmount' => $value->gross_amount,
                    'totalGrossLTV' => $value->gross_amount * $value->ltv,
                    'totalGrossYield' => $value->gross_amount * $value->yield,
                ];
            }
            // Lender
            if (isset($data2[$value->investor_id])) {
                $data2[$value->investor_id]['count']++;
                $data2[$value->investor_id]['grossAmount'] += $value->gross_amount;
                $data2[$value->investor_id]['totalGrossLTV'] += $value->gross_amount * $value->ltv;
                $data2[$value->investor_id]['totalGrossYield'] += $value->gross_amount * $value->yield;
            } else {
                $data2[$value->investor_id] = [
                    'count' => 1,
                    'grossAmount' => $value->gross_amount,
                    'totalGrossLTV' => $value->gross_amount * $value->ltv,
                    'totalGrossYield' => $value->gross_amount * $value->yield,
                ];
            }
        }

        foreach ($data as $originationCompanyId => $originationCompanies) {
            foreach ($originationCompanies as $investorId => $value) {
                $now = new DateTime();
                $today = $now->format('Y-m-d');

                $existingRecord = MicPipeline::query()
                    ->where('origination_company_id', $originationCompanyId)
                    ->where('investor_id', $investorId)
                    ->where('stage', 'Funding')
                    ->where('reference_date', $today)
                    ->first();

                if ($existingRecord) {
                    // Update ltv and yield , gross amount fields
                    $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                    $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                    $existingRecord->gross_amount = $value['grossAmount'] ? $value['grossAmount'] : 0;
                    $existingRecord->save();
                } else {

                    $micPipeline = new MicPipeline();
                    $micPipeline->origination_company_id = $originationCompanyId;
                    $micPipeline->investor_id = $investorId;
                    $micPipeline->stage = 'Funding';
                    $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                    $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                    $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                    $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                    $micPipeline->reference_date = $today;
                    $micPipeline->save();
                }
            }
        }

        foreach ($data2 as $investorId => $value) {
            $now = new DateTime();
            $today = $now->format('Y-m-d');

            $existingRecord = MicPipeline::query()
                ->where('origination_company_id', 0)
                ->where('investor_id', $investorId)
                ->where('stage', 'Funding')
                ->where('reference_date', $today)
                ->first();

            if ($existingRecord) {
                // Update ltv and yield , gross amount fields
                $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                $existingRecord->gross_amount = $value['grossAmount'] ? $value['grossAmount'] : 0;
                $existingRecord->save();
            } else {

                $micPipeline = new MicPipeline();
                $micPipeline->origination_company_id = 0;
                $micPipeline->investor_id = $investorId;
                $micPipeline->stage = 'Funding';
                $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                $micPipeline->reference_date = $today;
                $micPipeline->save();
            }
        }
    }

    public function getInitialDocsQuery() {
        $query = "SELECT if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))) origination_company_id,
                         si.investor_id, sq.ltv, si.yield, sq.gross gross_amount,
                         si.ap_inv_co, si.ap_amount, si.ap_yield, si.bp_inv_co, si.bp_amount, si.bp_yield, si.cp_inv_co, si.cp_amount, si.cp_yield, a.funding_date,
                         sq.mortgage_id, sq.saved_quote_id, a.status as status_id
                    from saved_quote_table sq
               left join application_table a on sq.application_id = a.application_id AND company IN (1, 3, 401, 701, 2022)
               left join sale_investor_table si on sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
                   where sq.disburse = 'Yes'
                     and a.status in (8,17)
                order by if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))), si.investor_id";

        return $query;
    }

    public function getInitialDocs($report = null) {

        $query = $this->getInitialDocsQuery();
        $result = $this->db->select($query);

        if ($report == 'returnResult') {
            return $result;
        }

        $data = array();
        $data2 = array();
        foreach ($result as $key => $value) {
            $investors = array();
            if ($value->investor_id == 1971) {
                //AB Loan
                if (!is_null($value->ap_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->ap_inv_co), 'amount' => $value->ap_amount, 'yield' => $value->ap_yield];
                }
                if (!is_null($value->bp_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->bp_inv_co), 'amount' => $value->bp_amount, 'yield' => $value->bp_yield];
                }
                if (!is_null($value->cp_inv_co)) {
                    $investors[] = ['id'  => Utils::convertCompanyToInvestor($value->cp_inv_co), 'amount' => $value->cp_amount, 'yield' => $value->cp_yield];
                }
            } else {
                $investors[] = ['id'  => $value->investor_id, 'amount' => $value->gross_amount, 'yield' => $value->yield];
            }

            foreach ($investors as $investor) {
                //Origination + Lender
                if (isset($data[$value->origination_company_id][$investor['id']])) {
                    $data[$value->origination_company_id][$investor['id']]['count']++;
                    $data[$value->origination_company_id][$investor['id']]['grossAmount'] += $investor['amount'];
                    $data[$value->origination_company_id][$investor['id']]['totalGrossLTV'] += $investor['amount'] * $value->ltv;
                    $data[$value->origination_company_id][$investor['id']]['totalGrossYield'] += $investor['amount'] * $investor['yield'];
                } else {
                    $data[$value->origination_company_id][$investor['id']] = [
                        'count' => 1,
                        'grossAmount' => $investor['amount'],
                        'totalGrossLTV' => $investor['amount'] * $value->ltv,
                        'totalGrossYield' => $investor['amount'] * $investor['yield'],
                    ];
                }

                //Lender
                if (isset($data2[$investor['id']])) {
                    $data2[$investor['id']]['count']++;
                    $data2[$investor['id']]['grossAmount'] += $investor['amount'];
                    $data2[$investor['id']]['totalGrossLTV'] += $investor['amount'] * $value->ltv;
                    $data2[$investor['id']]['totalGrossYield'] += $investor['amount'] * $investor['yield'];
                } else {
                    $data2[$investor['id']] = [
                        'count' => 1,
                        'grossAmount' => $investor['amount'],
                        'totalGrossLTV' => $investor['amount'] * $value->ltv,
                        'totalGrossYield' => $investor['amount'] * $investor['yield'],
                    ];
                }
            }
        }

        foreach ($data as $originationCompanyId => $originationCompanies) {
            foreach ($originationCompanies as $investorId => $value) {

                $now = new DateTime();
                $today = $now->format('Y-m-d');

                $existingRecord = MicPipeline::query()
                    ->where('origination_company_id', $originationCompanyId)
                    ->where('investor_id', $investorId)
                    ->where('stage', 'Initial Docs')
                    ->where('reference_date', $today)
                    ->first();

                if ($existingRecord) {
                    // Update ltv and yield , gross amount fields
                    $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                    $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                    $existingRecord->gross_amount = $value['grossAmount'] ? $value['grossAmount'] : 0;
                    $existingRecord->save();
                } else {
                    $micPipeline = new MicPipeline();
                    $micPipeline->origination_company_id = $originationCompanyId;
                    $micPipeline->investor_id = $investorId;
                    $micPipeline->stage = 'Initial Docs';
                    $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                    $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                    $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                    $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                    $micPipeline->reference_date = $today;
                    $micPipeline->save();
                }
            }
        }

        foreach ($data2 as $investorId => $value) {

            $now = new DateTime();
            $today = $now->format('Y-m-d');

            $existingRecord = MicPipeline::query()
                ->where('origination_company_id', 0)
                ->where('investor_id', $investorId)
                ->where('stage', 'Initial Docs')
                ->where('reference_date', $today)
                ->first();

            if ($existingRecord) {
                // Update ltv and yield , gross amount fields
                $existingRecord->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                $existingRecord->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                $existingRecord->gross_amount = $value['grossAmount'] ? $value['grossAmount'] : 0;
                $existingRecord->save();
            } else {
                $micPipeline = new MicPipeline();
                $micPipeline->origination_company_id = 0;
                $micPipeline->investor_id = $investorId;
                $micPipeline->stage = 'Initial Docs';
                $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                $micPipeline->reference_date = $today;
                $micPipeline->save();
            }
        }
    }

    public function getFunded($reportName, $report = null) {

        $query = "SELECT if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))) origination_company_id,
                    ifnull(i.investor_id,0) investor_id, m.ltv, mi.yield, m.gross_amt, a.funding_date  
                    FROM mortgage_table m
                    LEFT JOIN application_table a ON m.application_id = a.application_id AND a.company IN (1, 3, 401, 2022, 701, 801)
                    JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                    JOIN mortgage_investor_tracking_table mi ON m.mortgage_id = mi.mortgage_id AND mp.investor_tracking_id = mi.investor_tracking_id
                    JOIN mortgage_investor_tracking_investors_table mii ON mii.mortgage_id = mi.mortgage_id AND mii.investor_tracking_id = mi.investor_tracking_id
                    LEFT JOIN mortgage_payments_table mpa ON m.mortgage_id = mpa.transfer_mortgage_id
                    LEFT JOIN mortgage_table mta ON mpa.mortgage_id = mta.mortgage_id
                    LEFT JOIN investor_table i ON mii.investor_id = i.investor_id
                    WHERE m.is_deleted = 'no'
                    AND i.investor_id in (31,100,248)
                    AND mp.processing_date between DATE_FORMAT(NOW(), '%Y-%m-01') and LAST_DAY(NOW())
                    AND m.company_id <> 0
                    AND mta.is_deleted = 'no'";

        if ($reportName != 'Origination') {
            $query .= "order by if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))), i.investor_id";
        }

        if ($reportName == 'Origination') {
            $query .= " UNION ALL";

            $query .= " SELECT if(a.company = 1,1,if(a.company = 3, 1, if(a.company = 401, 1, if(a.company = 2022, 1, a.company)))) origination_company_id,
                    si.investor_id, 0 as ltv, 0 as yield, m.gross_amt, a.funding_date   
                    FROM status_info ssi
                    LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id
                                                AND ad.accounting_status = 'funded'
                                                AND DATE(ad.accounting_date) = DATE(ssi.status_date)
                    LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
                    LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                    JOIN mortgage_table m  ON ssi.mortgage_id = m.mortgage_id
                    LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id
                    LEFT JOIN alpine_companies_table ac ON a.company = ac.id
                    LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
                    LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id                    
                    WHERE ssi.status_id = 20
                    AND ssi.status_date between DATE_FORMAT(NOW(), '%Y-%m-01') and LAST_DAY(NOW())
                    and ssi.status_id = 20
                    AND si.investor_id NOT IN (31, 100, 248)
                    AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                GROUP BY ssi.quote_id;";
        }
        $result = $this->db->select($query);

        if ($report == 'returnResult') {
            return $result;
        }

        $data = array();
        $data2 = array();
        foreach ($result as $key => $value) {
            //Origination + Lender
            if (isset($data[$value->origination_company_id][$value->investor_id])) {
                $data[$value->origination_company_id][$value->investor_id]['count']++;
                $data[$value->origination_company_id][$value->investor_id]['grossAmount'] += $value->gross_amt;
                $data[$value->origination_company_id][$value->investor_id]['totalGrossLTV'] += $value->gross_amt * $value->ltv;
                $data[$value->origination_company_id][$value->investor_id]['totalGrossYield'] += $value->gross_amt * $value->yield;
            } else {
                $data[$value->origination_company_id][$value->investor_id] = [
                    'count' => 1,
                    'grossAmount' => $value->gross_amt,
                    'totalGrossLTV' => $value->gross_amt * $value->ltv,
                    'totalGrossYield' => $value->gross_amt * $value->yield,
                ];
            }

            // Lender
            if (isset($data2[$value->investor_id])) {
                $data2[$value->investor_id]['count']++;
                $data2[$value->investor_id]['grossAmount'] += $value->gross_amt;
                $data2[$value->investor_id]['totalGrossLTV'] += $value->gross_amt * $value->ltv;
                $data2[$value->investor_id]['totalGrossYield'] += $value->gross_amt * $value->yield;
            } else {
                $data2[$value->investor_id] = [
                    'count' => 1,
                    'grossAmount' => $value->gross_amt,
                    'totalGrossLTV' => $value->gross_amt * $value->ltv,
                    'totalGrossYield' => $value->gross_amt * $value->yield,
                ];
            }
        }

        foreach ($data as $originationCompanyId => $originationCompanies) {
            foreach ($originationCompanies as $investorId => $value) {
                $now = new DateTime();
                $today = $now->format('Y-m-d');

                $existingRecord = MicPipeline::query()
                    ->where('origination_company_id', $originationCompanyId)
                    ->where('investor_id', $investorId)
                    ->where('stage', 'Funded')
                    ->where('reference_date', $today)
                    ->first();

                if ($existingRecord) {
                    // Update ltv and yield , gross amount fields
                    $existingRecord->weighted_average_lvt = ($value['totalGrossLTV'] ?? 0) !== 0 ? $value['totalGrossLTV'] / ($value['grossAmount'] ?? 1) : 0;
                    $existingRecord->weighted_average_yield = ($value['totalGrossYield'] ?? 0) !== 0 ? $value['totalGrossYield'] / ($value['grossAmount'] ?? 1) : 0;
                    $existingRecord->gross_amount = $value['grossAmount'];
                    $existingRecord->count = $value['count'];
                    $existingRecord->save();
                } else {
                    $micPipeline = new MicPipeline();
                    $micPipeline->origination_company_id = $originationCompanyId;
                    $micPipeline->investor_id = $investorId;
                    $micPipeline->stage = 'Funded';
                    $micPipeline->gross_amount = $value['grossAmount'];
                    $micPipeline->weighted_average_lvt = $value['totalGrossLTV'] / $value['grossAmount'];
                    $micPipeline->weighted_average_yield = $value['totalGrossYield'] / $value['grossAmount'];
                    $micPipeline->count = $value['count'];
                    $micPipeline->reference_date = $today;
                    $micPipeline->save();
                }
            }
        }

        foreach ($data2 as $investorId => $value) {

            $now = new DateTime();
            $today = $now->format('Y-m-d');

            $existingRecord = MicPipeline::query()
                ->where('origination_company_id', 0)
                ->where('investor_id', $investorId)
                ->where('stage', 'Funded')
                ->where('reference_date', $today)
                ->first();

            if ($existingRecord) {
                // Update ltv and yield , gross amount fields
                $existingRecord->weighted_average_lvt = ($value['totalGrossLTV'] ?? 0) !== 0 ? $value['totalGrossLTV'] / ($value['grossAmount'] ?? 1) : 0;
                $existingRecord->weighted_average_yield = ($value['totalGrossYield'] ?? 0) !== 0 ? $value['totalGrossYield'] / ($value['grossAmount'] ?? 1) : 0;
                $existingRecord->gross_amount = $value['grossAmount'];
                $existingRecord->count = $value['count'];
                $existingRecord->save();
            } else {
                $micPipeline = new MicPipeline();
                $micPipeline->origination_company_id = 0;
                $micPipeline->investor_id = $investorId;
                $micPipeline->stage = 'Funded';
                $micPipeline->gross_amount = isset($value['grossAmount']) ? $value['grossAmount'] : 0;
                $micPipeline->weighted_average_lvt = isset($value['totalGrossLTV']) ? $value['totalGrossLTV'] / $value['grossAmount'] : 0;
                $micPipeline->weighted_average_yield = isset($value['totalGrossYield']) ? $value['totalGrossYield'] / $value['grossAmount'] : 0;
                $micPipeline->count = isset($value['count']) ? $value['count'] : 0;
                $micPipeline->reference_date = $today;
                $micPipeline->save();
            }
        }
    }

    public function getInitialDocsReport($startDate, $endDate) {
        $columns = [
            'Application',
            'Relationship & Compensation',
            'Disbursement',
            'Fixed Credit Disclosure',
            'Mortgage Instructions (ILG)',
            'Mortgage Support - Lawyer Documentation',
            'ILA Forms',
            '5 Day Letter',
            'Income letter'
        ];

        $sql = "select concat(b.user_fname, ' ', b.user_lname) agent_name, c.name report_name, count(distinct(a.application_id)) count
                  from application_document a
                  join users_table b on a.created_by = b.user_id
                  join document c on a.document_id = c.id
                  join dms_template d on c.dms_template_id = d.id
                 where a.deleted_at is null
                   and a.created_by is not null
                   and a.sharepoint_document_id is not null
                   and a.created_at between ? and ?
                   and b.user_id not in (99,5040,5088)
              group by b.user_fname, b.user_lname, c.name, d.name
              order by b.user_fname, b.user_lname";
        $res = $this->db->select($sql, [$startDate, $endDate]);

        $rows = array();
        foreach ($res as $key => $value) {
            if (!isset($rows[$value->agent_name])) {
                foreach ($columns as $column) {
                    $rows[$value->agent_name]['agentName'] = $value->agent_name;
                    $rows[$value->agent_name]['reports'][$column] = 0;
                }
            }

            $reportName = str_replace(' |', '', $value->report_name);

            if (isset($rows[$value->agent_name]['reports'][$reportName])) {
                $rows[$value->agent_name]['reports'][$reportName] = $value->count;
            }
        }

        $rows = array_values($rows);

        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    }

    public function commercialLoansTracker() {
        $sql = "select category_id, category_name from note_categories_table";
        $res = $this->db->select($sql);

        $noteCategories = [];
        foreach ($res as $key => $value) {
            $noteCategories[$value->category_id] = $value->category_name;
        }

        $sql = "select a.mortgage_id, a.mortgage_code, a.application_id, a.ab_loan, 
                       c.mortgage_id investor_id, c.mortgage_code investor_code,
                       fn_GetSpouse1NameByApplicationID(a.application_id) applicant_name,
                       fn_GetCitiesByMortgageID(c.mortgage_id) cities,
                       fn_GetProvincesByMortgageID(c.mortgage_id) provinces,
                       fn_GetPositionsByMortgageID(c.mortgage_id) positions,
                       fn_GetPropertyTypeByMortgageID(c.mortgage_id) property_types,
                       fn_GetAppraisalByMortgageID(c.mortgage_id) appraisals,
                       c.gross_amt, c.current_balance, c.ltv, c.brokerage_fee + c.discount_fee total_fee, c.interest_rate,
                       e.processing_date funded_date, c.due_date, c.payout_at, concat(d.user_fname, ' ', d.user_lname) broker_name,
                       f.yield, c.pari_passu,
                       (select sum(pm.balance)
                          from mortgage_properties_table mp
                          join property_mortgages_table pm on pm.property_id = mp.property_id
                         where mp.mortgage_id = c.mortgage_id
                           and pm.term <> '0000-00-00') prior_balance
                  from mortgage_table a
                  join application_table b on a.application_id = b.application_id
                  join mortgage_table c on a.mortgage_id = c.transfer_id
                  join users_table d on c.agent = d.user_id
             left join mortgage_payments_table e on a.mortgage_id = e.mortgage_id and e.is_sale = 'yes'
             left join mortgage_investor_tracking_table f on a.mortgage_id = f.mortgage_id and f.committed = 'Yes'
                 where a.is_deleted = 'no'
                   and c.is_deleted = 'no'
                   and a.ab_loan in ('No','m_mtg')
                   and a.mortgage_code <> 'DUMMY CARD'
                   and (
                           b.business_channel_id = 559
                       )
              order by a.mortgage_id";
        $res = $this->db->select($sql);

        $rows = array();
        foreach ($res as $key => $value) {
            $sql = "select category_id from notes_table
                     where application_id = ?
                       and followed_up = 'no'
                       and ((mortgage_id != 0 AND mortgage_id = ?) or mortgage_id = 0) 
                       and category_id in (7,32,36,37,39)
                  order by if(category_id = 7, 0, 1),
                           if(category_id = 39, 0, 1),
                           if(category_id = 37, 0, 1),
                           if(category_id = 36, 0, 1),
                           if(category_id = 32, 0, 1)";
            $res2 = $this->db->select($sql, [$value->application_id, $value->investor_id]);

            $collection = '';
            if (count($res2) > 0) {
                if (isset($noteCategories[$res2[0]->category_id])) {
                    $collection = $noteCategories[$res2[0]->category_id];
                }
            }

            $sql = "select sum(p.appraised_value) appraised_value
                      from mortgage_properties_table mp
                      join property_table p on mp.property_id = p.property_id
                     where mp.mortgage_id = ?";
            $res2 = $this->db->select($sql, [$value->mortgage_id]);

            $totalAppraisedValue = 0;
            if (count($res2) > 0) {
                $totalAppraisedValue = $res2[0]->appraised_value;
            }

            if ($value->ab_loan != 'No') {
                $sql = "select a.ltv from mortgage_table a where a.is_deleted = 'no' and a.parent = ?";
                $res2 = $this->db->select($sql, [$value->investor_id]);

                $tmp = array();
                foreach ($res2 as $key2 => $value2) {
                    $tmp[] = number_format($value2->ltv, 2);
                }

                $ltv = implode('/', $tmp);
            } else {
                $ltv = number_format($value->ltv, 2);
            }

            $maturityDate = $value->due_date;
            if ($value->due_date == '0000-00-00') {
                $sql = "select term_end from mortgage_interest_rates_table where mortgage_id = ? order by term_end desc";
                $res2 = $this->db->select($sql, [$value->mortgage_id]);

                if (count($res2) > 0) {
                    $maturityDate = $res2[0]->term_end;
                }
            }

            $rows[] = [
                'fundedDate' => $value->funded_date,
                'applicationId' => $value->application_id,
                'mortgageCode' => $value->mortgage_code,
                'investorCode' => $value->investor_code,
                'ablCode' => $value->ab_loan == 'm_mtg' ? $value->investor_code : '',
                'applicantName' => $value->applicant_name,
                'cities' => $value->cities,
                'provinces' => $value->provinces,
                'propertyTypes' => $value->property_types,
                'syndicated' => $value->ab_loan == 'No' ? 'No' : 'Yes',
                'construction' => '',
                'positions' => $value->positions,
                'priority' => '',
                'pariPassu' => ($value->pari_passu == 'yes') ? 'Yes' : '',
                'authorizedAmount' => $value->gross_amt,
                'fundedBalance' => $value->gross_amt,
                'currentBalance' => $value->ab_loan  == 'm_mtg' ? '' : (is_null($value->payout_at) ? $value->current_balance : 0),
                'appraisedValue' => $totalAppraisedValue,
                'totalPriorMortgage' => $value->ab_loan == 'm_mtg' ? '' : $value->prior_balance,
                'currentLTV' => $value->ab_loan == 'm_mtg' ? '' : number_format($ltv / 100, 4),
                'yield' => $value->ab_loan == 'm_mtg' ? '' : number_format($value->yield / 100, 4),
                'feesPerc' => number_format($value->total_fee / $value->gross_amt, 4),
                'fees' => $value->total_fee,
                'annualInterest' => (is_null($value->payout_at) && $value->ab_loan == 'No') ? (($value->yield / 100) * $value->current_balance) : '',
                'interestRate' => $value->ab_loan == 'No' ? number_format($value->interest_rate / 100, 4) : '',
                'maturityDate' => $maturityDate,
                'directReferral' => '',
                'paidOut' => is_null($value->payout_at) ? '' : $value->payout_at,
                'referringBrokerage' => '',
                'broker' => $value->broker_name,
                'collectionStatus' => $collection,
            ];



            if ($value->ab_loan == 'm_mtg') {
                $sql2 = "select a.mortgage_id, a.mortgage_code, a.application_id, a.ab_loan, a.interest_rate,
                                a.mortgage_id investor_id, a.mortgage_code investor_code,
                                fn_GetSpouse1NameByApplicationID(a.application_id) applicant_name,
                                fn_GetCitiesByMortgageID(a.mortgage_id) cities,
                                fn_GetProvincesByMortgageID(a.mortgage_id) provinces,
                                fn_GetPositionsByMortgageID(a.mortgage_id) positions,
                                fn_GetPropertyTypeByMortgageID(a.mortgage_id) property_types,
                                fn_GetAppraisalByMortgageID(a.mortgage_id) appraisals,
                                a.gross_amt, a.current_balance, a.ltv, a.brokerage_fee + a.discount_fee total_fee, a.interest_rate,
                                e.processing_date funded_date, a.due_date, a.payout_at, concat(d.user_fname, ' ', d.user_lname) broker_name,
                                f.yield, a.pari_passu,
                            (select sum(pm.balance)
                                from mortgage_properties_table mp
                                join property_mortgages_table pm on pm.property_id = mp.property_id
                                where mp.mortgage_id = a.mortgage_id
                                and pm.term <> '0000-00-00') prior_balance
                        from mortgage_table a
                        join application_table b on a.application_id = b.application_id
                        join users_table d on a.agent = d.user_id
                    left join mortgage_payments_table e on a.mortgage_id = e.mortgage_id and e.is_sale = 'yes'
                    left join mortgage_investor_tracking_table f on a.mortgage_id = f.mortgage_id and f.committed = 'Yes'
                        where a.is_deleted = 'no'
                        and a.ab_loan = 'c_inv'
                        and a.parent = ?
                    order by a.mortgage_id";
                $res2 = $this->db->select($sql2, [$value->investor_id]);

                $yields = [];
                $i = 0;
                foreach ($res2 as $key2 => $value2) {

                    $renewalCount = DB::table('mortgage_renewals_table')
                        ->whereNotNull('processed_at')
                        ->where('mortgage_id', $value2->mortgage_id)
                        ->count();

                    if ($renewalCount > 0) {
                        $yield[$i] = $value2->interest_rate;
                    }

                    $i++;
                    $cOsb[$i] = $value2->current_balance;
                    if ($i == 1) {
                        $totalPriorMortgage = $value->prior_balance;
                        $priority = 'A';
                        $piece_cn = 1;
                    } elseif ($i == 2) {
                        $totalPriorMortgage = $value->prior_balance + $cOsb[$i - 1];
                        $priority = 'B';
                        $priority = ($value->pari_passu == 'yes') ? 'A' : 'B';
                        $piece_cn = 2;
                    } elseif ($i == 3) {
                        $totalPriorMortgage = $value->prior_balance + $cOsb[$i - 1] + $cOsb[$i - 2];
                        $priority = ($value->pari_passu == 'yes') ? 'A' : 'C';
                        $piece_cn = 3;
                    }

                    $yieldColumn = match ($piece_cn) {
                        1 => 'ap_yield',
                        2 => 'bp_yield',
                        3 => 'cp_yield',
                        default => null,
                    };

                    if ($yieldColumn) {
                        $applicationId = $value->application_id;
                        $transferId = $value->mortgage_id;

                        $yield[$i] = DB::table('saved_quote_table as sqt')
                            ->leftJoin('sale_investor_table as sit', function ($join) {
                                $join->on('sit.saved_quote_id', '=', 'sqt.saved_quote_id')
                                    ->whereIn('sit.fm_committed', ['yes', 'looking']);
                            })
                            ->where('sqt.application_id', $applicationId)
                            ->where(function ($query) use ($transferId) {
                                $query->where('sqt.mortgage_id', $transferId)
                                    ->orWhere(function ($query) {
                                        $query->where('sqt.mortgage_id', 0)
                                            ->where('sqt.disburse', 'yes');
                                    });
                            })
                            ->value($yieldColumn);
                    }

                    //$this->logger->info('ReportBO->yield',[$transferId, $applicationId, $yieldColumn, $yield[$i]]);
                    $rows[] = [
                        'fundedDate' => $value->funded_date,
                        'applicationId' => $value2->application_id,
                        'mortgageCode' => '',
                        'investorCode' => $value2->investor_code,
                        'ablCode' => $value->ab_loan == 'm_mtg' ? $value->investor_code : '',
                        'applicantName' => $value2->applicant_name,
                        'cities' => $value2->cities,
                        'provinces' => $value2->provinces,
                        'propertyTypes' => $value2->property_types,
                        'syndicated' => 'Yes',
                        'construction' => '',
                        'positions' => $value2->positions,
                        'priority' => $priority,
                        'pariPassu' => ($value2->pari_passu == 'yes') ? 'Yes' : '',
                        'authorizedAmount' => '',
                        'fundedBalance' => '',
                        'currentBalance' => is_null($value2->payout_at) ? $value2->current_balance : 0,
                        'appraisedValue' => $totalAppraisedValue,
                        'totalPriorMortgage' => $totalPriorMortgage,
                        'currentLTV' => number_format($value2->ltv / 100, 4),
                        'yield' => number_format($yield[$i] / 100, 4),
                        'feesPerc' => '',
                        'fees' => '',
                        'annualInterest' => (is_null($value2->payout_at) && !empty($value2->current_balance)) ? (($yield[$i] / 100) * $value2->current_balance) : '',
                        'interestRate' => number_format($value2->interest_rate / 100, 4),
                        'maturityDate' => $value2->due_date,
                        'directReferral' => '',
                        'paidOut' => is_null($value2->payout_at) ? '' : $value2->payout_at,
                        'referringBrokerage' => '',
                        'broker' => $value2->broker_name,
                        'collectionStatus' => $collection,
                    ];
                }
            }
        }

        return $rows;
    }

    public function getOriginationPipeline($endDate) {

        $result = $this->getInitialDocs('returnResult');
        $this->processData($result, 'Initial Docs', '');

        $result = $this->getSigning('returnResult', $endDate);
        $this->processData($result, 'Signing', '');

        $result = $this->getFunding('returnResult', $endDate);
        $this->processData($result, 'Funding', '');

        $result = $this->getFunded('Origination', 'returnResult');
        $this->processData($result, 'Funded', '');

        $alpineData = array();
        $sequenceData = array();
        $directMicData = array();
        $totalCompanyData = array();

        foreach ($this->originationPipeline as $key => $value) {

            if ($value['companyId'] == 1) {

                $alpineData[] = $value;

                // Alpine Sub Total
                $exists = false;
                foreach ($alpineData as &$subTotal) {
                    if (
                        (
                            $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs'
                        ) &&
                        $subTotal['statusOrder'] == 'Sub Total' &&
                        $subTotal['investorId'] == $value['investorId']
                    ) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }
                if (!$exists && $value['statusOrder'] != 'Funded') {
                    $alpineData[] = [
                        'statusOrder' => 'Sub Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }

                // Alpine Total
                $exists = false;
                foreach ($alpineData as &$subTotal) {
                    if (
                        (
                            $value['statusOrder'] == 'Funded' || $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs'
                        ) &&
                        $subTotal['statusOrder'] == 'Total' &&
                        $subTotal['investorId'] == $value['investorId']
                    ) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $alpineData[] = [
                        'statusOrder' => 'Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }
            }

            if ($value['companyId'] == 701) {

                $sequenceData[] = $value;

                // Sequence Sub Total
                $exists = false;
                foreach ($sequenceData as &$subTotal) {
                    if (($value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs') && $subTotal['statusOrder'] == 'Sub Total' && $subTotal['investorId'] == $value['investorId']) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }

                if (!$exists && $value['statusOrder'] != 'Funded') {
                    $sequenceData[] = [
                        'statusOrder' => 'Sub Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }

                // Sequence Total
                $exists = false;
                foreach ($sequenceData as &$subTotal) {
                    if (($value['statusOrder'] == 'Funded' || $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs') && $subTotal['statusOrder'] == 'Total' && $subTotal['investorId'] == $value['investorId']) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $sequenceData[] = [
                        'statusOrder' => 'Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }
            }

            if ($value['companyId'] != 1 && $value['companyId'] != 701) {

                $directMicData[] = $value;

                // Direct MIC Sub Total
                $exists = false;
                foreach ($directMicData as &$subTotal) {
                    if (
                        (
                            $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs'
                        ) &&
                        $subTotal['statusOrder'] == 'Sub Total' &&
                        $subTotal['investorId'] == $value['investorId']
                    ) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }
                if (!$exists && $value['statusOrder'] != 'Funded') {
                    $directMicData[] = [
                        'statusOrder' => 'Sub Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }

                // Direct MIC Total
                $exists = false;
                foreach ($directMicData as &$subTotal) {
                    if (
                        (
                            $value['statusOrder'] == 'Funded' || $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs'
                        ) &&
                        $subTotal['statusOrder'] == 'Total' &&
                        $subTotal['investorId'] == $value['investorId']
                    ) {
                        $subTotal['count'] += $value['count'];
                        $subTotal['grossAmount'] += $value['grossAmount'];
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $directMicData[] = [
                        'statusOrder' => 'Total',
                        'companyId'   => 0,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                    ];
                }
            }

            $exists = false;
            foreach ($totalCompanyData as &$doc) {
                if ($doc['statusOrder'] == $value['statusOrder'] && $doc['investorId'] == $value['investorId']) {
                    $doc['count'] += $value['count'];
                    $doc['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalCompanyData[] = [
                    'statusOrder' => $value['statusOrder'],
                    'companyId'   => 0,
                    'investorId'  => $value['investorId'],
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }

            // Total Company Sub Total
            $exists = false;
            foreach ($totalCompanyData as &$subTotal) {
                if (($value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs') && $subTotal['statusOrder'] == 'Sub Total' && $subTotal['investorId'] == $value['investorId']) {
                    $subTotal['count'] += $value['count'];
                    $subTotal['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists && $value['statusOrder'] != 'Funded') {
                $totalCompanyData[] = [
                    'statusOrder' => 'Sub Total',
                    'companyId'   => 0,
                    'investorId'  => $value['investorId'],
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }

            // Total Company Total
            $exists = false;
            foreach ($totalCompanyData as &$subTotal) {
                if (($value['statusOrder'] == 'Funded' || $value['statusOrder'] == 'Funding' || $value['statusOrder'] == 'Signing' || $value['statusOrder'] == 'Initial Docs') && $subTotal['statusOrder'] == 'Total' && $subTotal['investorId'] == $value['investorId']) {
                    $subTotal['count'] += $value['count'];
                    $subTotal['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalCompanyData[] = [
                    'statusOrder' => 'Total',
                    'companyId'   => 0,
                    'investorId'  => $value['investorId'],
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }
        }

        $totalTmp = array();
        foreach ($alpineData as $key => $value) {
            $exists = false;
            foreach ($totalTmp as &$totalRow) {
                if ($value['statusOrder'] == $totalRow['statusOrder'] && $totalRow['investorId'] == -2) {
                    $totalRow['count'] += $value['count'];
                    $totalRow['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalTmp[] = [
                    'statusOrder' => $value['statusOrder'],
                    'companyId'   => 0,
                    'investorId'  => -2,
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }
        }
        $alpineData = array_merge($alpineData, $totalTmp);

        $totalTmp = array();
        foreach ($sequenceData as $key => $value) {
            $exists = false;
            foreach ($totalTmp as &$totalRow) {
                if ($value['statusOrder'] == $totalRow['statusOrder'] && $totalRow['investorId'] == -2) {
                    $totalRow['count'] += $value['count'];
                    $totalRow['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalTmp[] = [
                    'statusOrder' => $value['statusOrder'],
                    'companyId'   => 0,
                    'investorId'  => -2,
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }
        }
        $sequenceData = array_merge($sequenceData, $totalTmp);
        $totalTmp = array();
        foreach ($directMicData as $key => $value) {
            $exists = false;
            foreach ($totalTmp as &$totalRow) {
                if ($value['statusOrder'] == $totalRow['statusOrder'] && $totalRow['investorId'] == -2) {
                    $totalRow['count'] += $value['count'];
                    $totalRow['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalTmp[] = [
                    'statusOrder' => $value['statusOrder'],
                    'companyId'   => 0,
                    'investorId'  => -2,
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }
        }
        $directMicData = array_merge($directMicData, $totalTmp);

        $totalTmp = array();
        foreach ($totalCompanyData as $key => $value) {
            $exists = false;
            foreach ($totalTmp as &$totalRow) {
                if ($value['statusOrder'] == $totalRow['statusOrder'] && $totalRow['investorId'] == -2) {
                    $totalRow['count'] += $value['count'];
                    $totalRow['grossAmount'] += $value['grossAmount'];
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $totalTmp[] = [
                    'statusOrder' => $value['statusOrder'],
                    'companyId'   => 0,
                    'investorId'  => -2,
                    'count'       => $value['count'],
                    'grossAmount' => $value['grossAmount'],
                ];
            }
        }
        $totalCompanyData = array_merge($totalCompanyData, $totalTmp);

        $this->originationPipeline = [];
        $this->originationPipelineDetail = [];

        $result = $this->getSigning('expectedFunding', $endDate);
        $this->processData($result, 'Signing', 'Detail');

        $result = $this->getFunding('expectedFunding', $endDate);
        $this->processData($result, 'Funding', 'Detail');

        //Funding today
        /*$result = array_merge(
            $this->getSigning('expectedFunding'),
            $this->getFunding('expectedFunding')
        );*/

        $originationCompanies = [1, 701];
        $investors = [31, 248, 100, 0, -1];

        $alpineTodaysFunding = [];
        $sequenceTodaysFunding = [];
        $totalTodayFunding = [];

        foreach ($originationCompanies as $originationCompany) {
            foreach ($investors as $investor) {

                $count = 0;
                $total = 0;

                foreach ($this->originationPipeline as $key => $value) {
                    if ($value['companyId'] == $originationCompany && $value['investorId'] == $investor) {
                        $count += $value['count'];
                        $total += $value['grossAmount'];
                    }
                }

                if ($originationCompany == 1) {
                    $alpineTodaysFunding[] = [
                        'companyId' => $originationCompany,
                        'investorId' => $investor,
                        'count' => $count,
                        'total' => $total,
                    ];
                }

                if ($originationCompany == 701) {
                    $sequenceTodaysFunding[] = [
                        'companyId' => $originationCompany,
                        'investorId' => $investor,
                        'count' => $count,
                        'total' => $total
                    ];
                }
            }
        }

        $totalCountA = 0;
        $totalAmountA = 0;
        $totalCountS = 0;
        $totalAmountS = 0;
        foreach ($alpineTodaysFunding as $key => $value) {
            $totalCountA += $value['count'];
            $totalAmountA += $value['total'];
            $totalCountS += $sequenceTodaysFunding[$key]['count'];
            $totalAmountS += $sequenceTodaysFunding[$key]['total'];

            $totalTodayFunding[] = [
                'companyId' => $value['companyId'],
                'investorId' => $value['investorId'],
                'count' => $value['count'] + $sequenceTodaysFunding[$key]['count'],
                'total' => $value['total'] + $sequenceTodaysFunding[$key]['total']
            ];
        }
        $alpineTodaysFunding[] = [
            'count' => $totalCountA,
            'total' => $totalAmountA
        ];
        $sequenceTodaysFunding[] = [
            'count' => $totalCountS,
            'total' => $totalAmountS
        ];
        $totalTodayFunding[] = [
            'count' => $totalCountA + $totalCountS,
            'total' => $totalAmountA + $totalAmountS
        ];

        $data = [
            'alpineData' => $alpineData,
            'sequenceData' => $sequenceData,
            'directMicData' => $directMicData,
            'totalCompanyData' => $totalCompanyData,
            'pipelineDetail' => $this->originationPipelineDetail,
            'fundingToday' => [
                'alpine' => $alpineTodaysFunding,
                'sequence' => $sequenceTodaysFunding,
                'total' => $totalTodayFunding,
            ]
        ];

        return $data;
    }

    public function processData($result, $statusOrder, $detailType) {

        foreach ($result as $key => $value) {

            $exists = false;

            if ($value->investor_id == 1971 && ($statusOrder == 'Signing' || $statusOrder == 'Initial Docs')) {

                if (!is_null($value->ap_inv_co)) {

                    $exists = false;
                    $investorId = Utils::convertCompanyToInvestor($value->ap_inv_co);
                    $grossAmount = $value->ap_amount;

                    foreach ($this->originationPipeline as &$doc) {
                        if ($doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                            $doc['count'] += 1;
                            $doc['grossAmount'] += $grossAmount;
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $this->originationPipeline[] = [
                            'statusOrder' => $statusOrder,
                            'companyId'   => $value->origination_company_id,
                            'investorId'  => $investorId,
                            'count'       => 1,
                            'grossAmount' => $grossAmount,
                        ];
                    }

                    if ($detailType == 'Detail') {
                        $this->originationPipelineDetail[] = [
                            'aplicationId' => $value->application_id,
                            'statusOrder'  => $statusOrder,
                            'companyId'    => $value->origination_company_id,
                            'investorId'   => $investorId,
                            'count'        => 1,
                            'grossAmount'  => $grossAmount,
                            'agentName'    => $value->user_fname . ' ' . $value->user_lname,
                            'fundingDate'  => $value->funding_date,
                        ];
                    }
                }

                if (!is_null($value->bp_inv_co)) {

                    $exists = false;
                    $investorId  = Utils::convertCompanyToInvestor($value->bp_inv_co);
                    $grossAmount = $value->bp_amount;

                    foreach ($this->originationPipeline as &$doc) {
                        if ($doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                            $doc['count'] += 1;
                            $doc['grossAmount'] += $grossAmount;
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $this->originationPipeline[] = [
                            'statusOrder' => $statusOrder,
                            'companyId'   => $value->origination_company_id,
                            'investorId'  => $investorId,
                            'count'       => 1,
                            'grossAmount' => $grossAmount,
                        ];
                    }

                    if ($detailType == 'Detail') {
                        $this->originationPipelineDetail[] = [
                            'aplicationId' => $value->application_id,
                            'statusOrder'  => $statusOrder,
                            'companyId'    => $value->origination_company_id,
                            'investorId'   => $investorId,
                            'count'        => 1,
                            'grossAmount'  => $grossAmount,
                            'agentName'    => $value->user_fname . ' ' . $value->user_lname,
                            'fundingDate'  => $value->funding_date,
                        ];
                    }
                }

                if (!is_null($value->cp_inv_co)) {

                    $exists = false;
                    $investorId  = Utils::convertCompanyToInvestor($value->cp_inv_co);
                    $grossAmount = $value->cp_amount;

                    foreach ($this->originationPipeline as &$doc) {
                        if ($doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                            $doc['count'] += 1;
                            $doc['grossAmount'] += $grossAmount;
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $this->originationPipeline[] = [
                            'statusOrder' => $statusOrder,
                            'companyId'   => $value->origination_company_id,
                            'investorId'  => $investorId,
                            'count'       => 1,
                            'grossAmount' => $grossAmount,
                        ];
                    }

                    if ($detailType == 'Detail') {
                        $this->originationPipelineDetail[] = [
                            'aplicationId' => $value->application_id,
                            'statusOrder'  => $statusOrder,
                            'companyId'    => $value->origination_company_id,
                            'investorId'   => $investorId,
                            'count'        => 1,
                            'grossAmount'  => $grossAmount,
                            'agentName'    => $value->user_fname . ' ' . $value->user_lname,
                            'fundingDate'  => $value->funding_date,
                        ];
                    }
                }
            } else {

                if (is_null($value->investor_id)) {
                    $investorId = -1;
                } else {

                    $investorId = $value->investor_id;

                    if ($statusOrder == 'Funded' && $investorId != 31 && $investorId != 100 && $investorId != 248) {
                        $investorId = 0;
                    }
                }

                $grossAmount = $value->gross_amount ?? $value->gross_amt;

                foreach ($this->originationPipeline as &$doc) {
                    if ($doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                        $doc['count'] += 1;
                        $doc['grossAmount'] += $grossAmount;
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $this->originationPipeline[] = [
                        'statusOrder' => $statusOrder,
                        'companyId'   => $value->origination_company_id,
                        'investorId'  => $investorId,
                        'count'       => 1,
                        'grossAmount' => $grossAmount
                    ];
                }

                if ($detailType == 'Detail') {
                    $this->originationPipelineDetail[] = [
                        'aplicationId' => $value->application_id,
                        'statusOrder'  => $statusOrder,
                        'companyId'    => $value->origination_company_id,
                        'investorId'   => $investorId,
                        'count'        => 1,
                        'grossAmount'  => $grossAmount,
                        'agentName'    => $value->user_fname . ' ' . $value->user_lname,
                        'fundingDate'  => $value->funding_date,
                    ];
                }
            }
        }
    }

    public function fillDailyPipelineTable() {
        $data = $this->getOriginationPipeline(new DateTime());
        $now = new DateTime();

        $totals = [];

        foreach (['alpineData', 'sequenceData'] as $groupKey) {
            foreach ($data[$groupKey] as $row) {
                if (!in_array($row['companyId'], [1, 701])) {
                    continue;
                }

                if (!in_array($row['statusOrder'], ['Funding', 'Signing'])) {
                    continue;
                }

                $companyId = $row['companyId'];
                $stage = $row['statusOrder'] . ' Sheet';

                $key = $companyId . '|' . $stage;

                if (!isset($totals[$key])) {
                    $totals[$key] = [
                        'origination_company_id' => $companyId,
                        'stage' => $stage,
                        'reference_date' => $now->format('Y-m-d'),
                        'gross_amount' => 0,
                        'count' => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                $totals[$key]['gross_amount'] += $row['grossAmount'];
                $totals[$key]['count'] += $row['count'];
            }
        }

        foreach ($totals as $record) {
            $this->db->insert('daily_pipeline', $record);
        }
    }

    public function getPipelineForecast($month = null, $year = null, $province = null) {
        // Query 1: Get files in Initial Docs (Status 8 or 17)
        $initialDocsQuery = "
            SELECT
                IF(a.company = 1, 1, IF(a.company = 3, 1, IF(a.company = 401, 1, IF(a.company = 2022, 1, a.company)))) AS origination_company_id,
                sq.gross AS gross_amount,
                a.status,
                a.funding_date
            FROM
                saved_quote_table sq
                LEFT JOIN application_table a ON sq.application_id = a.application_id
                    AND company IN (1, 3, 401, 2022)
                LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id
                    AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
            WHERE
                sq.disburse = 'Yes'
                AND a.status IN (8, 17)
            ORDER BY
                a.funding_date DESC
        ";

        // Query 2: Get files in Signing (Status 10 or 14)
        $signingQuery = "
            SELECT
                IF(a.company = 1, 1, IF(a.company = 3, 1, IF(a.company = 401, 1, IF(a.company = 2022, 1, a.company)))) AS origination_company_id,
                sq.gross AS gross_amount,
                a.status,
                a.funding_date
            FROM
                saved_quote_table sq
                LEFT JOIN sale_investor_table sit ON sq.saved_quote_id = sit.saved_quote_id
                    AND sit.fm_committed IN ('yes', 'looking')
                JOIN application_table a ON sq.application_id = a.application_id
                    AND a.company IN (1, 3, 401, 2022)
                LEFT JOIN users_table ut ON ut.user_id = a.agent
            WHERE
                sq.disburse = 'Yes'
                AND (a.status = '10' OR a.status = '14')
            ORDER BY
                a.funding_date DESC
        ";

        // Query 3: Get files in Funding (Status 13 and other complex conditions)
        $fundingQuery = "
            SELECT
                CASE WHEN at.company IN (1,3,401,2022) THEN 1 ELSE at.company END AS origination_company_id,
                sq.gross AS gross_amount,
                at.funding_date
            FROM saved_quote_table sq
            JOIN application_table at
                ON sq.application_id = at.application_id
                AND at.company IN (1,3,401,2022)
            LEFT JOIN sale_investor_table si
                ON sq.saved_quote_id = si.saved_quote_id
                AND si.fm_committed IN ('Yes','Looking')
            WHERE
                at.status = 13
                AND sq.disburse = 'Yes'
                AND NOT EXISTS (
                    SELECT 1
                    FROM mortgage_table aa
                    WHERE aa.transfer_id = sq.mortgage_id
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM mortgage_table bb
                    WHERE bb.mortgage_id = sq.mortgage_id
                        AND bb.ab_loan = 'm_mtg'
                )

            UNION ALL

            SELECT
                CASE WHEN a.company_id IN (1,3,401,2022) THEN 1 ELSE a.company_id END AS origination_company_id,
                a.gross_amt AS gross_amount,
                at.funding_date
            FROM mortgage_table a
            JOIN application_table at
                ON at.application_id = a.application_id
            JOIN mortgage_investor_tracking_table b
                ON a.mortgage_id = b.mortgage_id
            WHERE
                a.ab_loan = 'c_mtg'
                AND a.is_deleted = 'no'
                AND b.committed = 'Yes'
                AND NOT EXISTS (
                    SELECT 1
                    FROM mortgage_table aa
                    WHERE aa.is_deleted = 'no'
                        AND aa.transfer_id = a.mortgage_id
                )

            UNION ALL

            SELECT
                CASE WHEN a.company_id IN (1,3,401,2022) THEN 1 ELSE a.company_id END AS origination_company_id,
                a.gross_amt AS gross_amount,
                at.funding_date
            FROM mortgage_table a
            JOIN mortgage_investor_tracking_table b
                ON a.mortgage_id = b.mortgage_id
            JOIN application_table at
                ON a.application_id = at.application_id
            WHERE
                a.company_id IN (1,3,401,2022)
                AND b.investor_id IN (31,100,248)
                AND b.committed = 'Yes'
                AND a.is_deleted = 'no'
                AND a.ab_loan = 'No'
                AND NOT EXISTS (
                    SELECT 1
                    FROM mortgage_table aa
                    WHERE aa.transfer_id = a.mortgage_id
                        AND aa.is_deleted = 'no'
                )
                AND EXISTS (
                    SELECT 1
                    FROM saved_quote_table bb
                    WHERE bb.mortgage_id = a.mortgage_id
                )
                AND at.status = 9
        ";

        // Execute queries
        $initialDocsResults = $this->db->select($initialDocsQuery);
        $signingResults = $this->db->select($signingQuery);
        $fundingResults = $this->db->select($fundingQuery);

        // Aggregate results for Initial Docs
        $initialDocsTotal = 0;
        $initialDocsCount = 0;
        foreach ($initialDocsResults as $row) {
            $initialDocsTotal += $row->gross_amount ?? 0;
            $initialDocsCount++;
        }

        // Aggregate results for Signing
        $signingTotal = 0;
        $signingCount = 0;
        foreach ($signingResults as $row) {
            $signingTotal += $row->gross_amount ?? 0;
            $signingCount++;
        }

        // Aggregate results for Funding
        $fundingTotal = 0;
        $fundingCount = 0;
        foreach ($fundingResults as $row) {
            $fundingTotal += $row->gross_amount ?? 0;
            $fundingCount++;
        }

        // Calculate grand totals
        $grandTotal = $initialDocsTotal + $signingTotal + $fundingTotal;
        $grandCount = $initialDocsCount + $signingCount + $fundingCount;

        // Return data in camelCase format to match frontend expectations
        return [
            'initialDocs' => [
                'count' => $initialDocsCount,
                'grossAmount' => $initialDocsTotal
            ],
            'signing' => [
                'count' => $signingCount,
                'grossAmount' => $signingTotal
            ],
            'funding' => [
                'count' => $fundingCount,
                'grossAmount' => $fundingTotal
            ],
            'total' => [
                'count' => $grandCount,
                'grossAmount' => $grandTotal
            ]
        ];
    }

    public function brokerDashboard($month = null, $year = null, $province = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First second of the next month
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime("$startDate +1 month"));

        // Map province to company IDs
        $companyFilter = '';
        $companyFilterApps = '';
        if ($province) {
            switch ($province) {
                case 'AB':
                    $companyFilter = 'AND a.company_id IN (3)'; // Alpine Mortgage Corp.
                    $companyFilterApps = 'AND b.company IN (3)'; // Alpine Mortgage Corp.
                    break;
                case 'BC':
                    $companyFilter = 'AND a.company_id IN (1)'; // Alpine Credits Limited
                    $companyFilterApps = 'AND b.company IN (1)'; // Alpine Credits Limited
                    break;
                case 'ON':
                    $companyFilter = 'AND a.company_id IN (401)'; // Alpine Credits Limited Ontario
                    $companyFilterApps = 'AND b.company IN (401)'; // Alpine Credits Limited Ontario
                    break;
            }
        }

        $sql = "
            WITH broker_applications AS (
                SELECT 
                    a.broker_id,
                    COUNT(distinct(a.application_id)) as sales_journey_count
                FROM sales_journey a
                JOIN application_table b on a.application_id = b.application_id
                WHERE a.created_at >= ? AND a.created_at < ?
                AND b.qualification_group_id in (443,444)
                AND b.company IN (1, 3, 401)
                $companyFilterApps
                GROUP BY a.broker_id
            ),
            
            broker_funded AS (
                SELECT aa.user_id,
                       aa.broker user_fname,
                       aa.doctracker_group,
                       COUNT(*) funded_file,
                       SUM(aa.gross_amt) total_gross_amt,
                       round(SUM(aa.brokerage_fee + aa.discount_fee), 0) AS gross_fee,
                       round(SUM(aa.brokerage_fee + aa.discount_fee) / SUM(aa.gross_amt), 2) AS gross_fee_percentage
                  FROM (
                        SELECT m.application_id, m.mortgage_id, m.mortgage_code, m2.mortgage_code as inv_mortgage_code,
                               m.gross_amt, m.brokerage_fee, m.discount_fee, u.user_id, concat(u.user_fname, ' ', u.user_lname ) broker, u.doctracker_group
                          FROM mortgage_table m
                     LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                          JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                          JOIN application_doc ad ON ad.application_id = m.application_id
                          JOIN users_table u ON m.agent = u.user_id
                         WHERE m.is_deleted = 'no'
                           AND mp.processing_date BETWEEN '{$startDate}' AND '{$nextMonthStart}'
                           AND m.company_id <> 0
                           AND ad.id > 0
                           AND ad.accounting_status = 'funded'
                           AND DATE(accounting_date) = mp.processing_date
                           AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                           AND m.company_id IN (1, 3, 401, 2022)
                           " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                           AND m2.company_id IN (5, 16, 182)
                      GROUP BY m.mortgage_id

                         UNION

                        SELECT ad.application_id, m.mortgage_id, m.mortgage_code, m2.mortgage_code as inv_mortgage_code, sq.gross AS gross_amt,
                               sq.broker brokerage_fee, sq.discount discount_fee, u.user_id, concat(u.user_fname, ' ', u.user_lname ) broker, u.doctracker_group
                          FROM status_info ssi
                     LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
                     LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
                     LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                          JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
                     LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                     LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
                     LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                          JOIN users_table u ON m.agent = u.user_id
                         WHERE m.is_deleted = 'no'
                           AND ssi.status_id = 20
                           AND ssi.status_date between '{$startDate}' AND '{$nextMonthStart}'
                           AND si.investor_id NOT IN (31, 100, 248)
                           AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                           AND m.company_id IN (1, 3, 401, 2022)
                           " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                      GROUP BY ssi.quote_id

                         UNION

                        SELECT a.application_id, d.mortgage_id, d.mortgage_code, a.mortgage_code inv_mortgage_code, a.gross_amt,
                               a.brokerage_fee, a.discount_fee, e.user_id, concat(e.user_fname, ' ', e.user_lname ) broker, e.doctracker_group
                          FROM mortgage_table a
                          JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                          JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                          JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                          JOIN users_table e on a.agent = e.user_id
                         WHERE a.ab_loan = 'c_inv'
                           AND a.is_deleted = 'no'
                           AND d.is_deleted = 'no'
                           AND a.created_at between '{$startDate}' AND '{$nextMonthStart}'
                           AND d.company_id IN (1, 3, 401, 2022)
                           " . str_replace('a.company_id', 'd.company_id', $companyFilter) . "
                ) aa
                GROUP BY aa.user_id, aa.broker, aa.doctracker_group
            )

            SELECT 
                bf.user_id,
                bf.user_fname,
                bf.doctracker_group,
                COALESCE(ba.sales_journey_count, 0) AS sales_journey,
                bf.funded_file,
                CASE 
                    WHEN COALESCE(ba.sales_journey_count, 0) > 0 
                    THEN ROUND(bf.funded_file / ba.sales_journey_count, 2)
                    ELSE 0 
                END AS conversion,
                bf.total_gross_amt,
                bf.gross_fee,
                bf.gross_fee_percentage
            FROM broker_funded bf
            LEFT JOIN broker_applications ba ON bf.user_id = ba.broker_id
            ORDER BY bf.doctracker_group, bf.user_fname
        ";

        $brokers = $this->db->select($sql, [
            $startDate,
            $nextMonthStart, //broker_applications
            //$startDate,
            //$nextMonthStart  // broker_funded
        ]);

        // Add broker group classification
        //$pbBrokers = ['Alvir', 'Anupreet', 'Jasmine', 'Simon'];
        //$ltBrokers = ['Adrian', 'Kyra', 'Noemi', 'Lauren'];
        foreach($brokers as $broker) {
            if($broker->doctracker_group == 'Group1 PB') {
                $broker->broker_group = 'PB';

            } elseif($broker->doctracker_group == 'Group1 NB') {
                $broker->broker_group = 'NB';

            } else {
                $broker->broker_group = 'LT';
            }
        }

        return [
            'brokers' => $brokers,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }

    public function brokerDetails($userId, $month = null, $year = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First moment of the next month (exclusive end boundary)
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));

        // Get broker summary data
        $brokerDataSql = "
            WITH broker_applications AS (
                SELECT 
                    a.broker_id,
                    COUNT(distinct(a.application_id)) as sales_journey_count
                FROM sales_journey a
                JOIN application_table b on a.application_id = b.application_id
                WHERE a.created_at >= ? AND a.created_at < ?
                AND b.qualification_group_id in (443,444)
                GROUP BY a.broker_id
            ),
            broker_funded AS (
                SELECT aa.user_id,
                       aa.broker user_fname,
                       aa.doctracker_group,
                       COUNT(*) funded_file,
                       SUM(aa.gross_amt) total_gross_amt,
                       round(SUM(aa.brokerage_fee + aa.discount_fee), 0) AS gross_fee,
                       round(SUM(aa.brokerage_fee + aa.discount_fee) / SUM(aa.gross_amt), 2) AS gross_fee_percentage
                  FROM (
                        SELECT m.application_id, m.mortgage_id, m.mortgage_code, m2.mortgage_code as inv_mortgage_code,
                               m.gross_amt, m.brokerage_fee, m.discount_fee, u.user_id, concat(u.user_fname, ' ', u.user_lname ) broker, u.doctracker_group
                          FROM mortgage_table m
                     LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                          JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                          JOIN application_doc ad ON ad.application_id = m.application_id
                          JOIN users_table u ON m.agent = u.user_id
                         WHERE m.is_deleted = 'no'
                           AND mp.processing_date >= ? AND mp.processing_date < ?
                           AND m.company_id <> 0
                           AND ad.id > 0
                           AND ad.accounting_status = 'funded'
                           AND DATE(ad.accounting_date) = DATE(mp.processing_date)
                           AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                           AND m.company_id IN (1, 3, 401, 2022)
                           AND m2.company_id IN (5, 16, 182)
                           AND u.user_id = ?
                      GROUP BY m.mortgage_id

                         UNION

                        SELECT ad.application_id, m.mortgage_id, m.mortgage_code, m2.mortgage_code as inv_mortgage_code, sq.gross AS gross_amt,
                               sq.broker brokerage_fee, sq.discount discount_fee, u.user_id, concat(u.user_fname, ' ', u.user_lname ) broker, u.doctracker_group
                          FROM status_info ssi
                     LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
                     LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
                     LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                          JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
                     LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                     LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
                     LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                          JOIN users_table u ON m.agent = u.user_id
                         WHERE m.is_deleted = 'no'
                           AND ssi.status_id = 20
                           AND ssi.status_date >= ? AND ssi.status_date < ?
                           AND si.investor_id NOT IN (31, 100, 248)
                           AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                           AND u.user_id = ?
                      GROUP BY ssi.quote_id

                         UNION

                        SELECT a.application_id, d.mortgage_id, d.mortgage_code, a.mortgage_code inv_mortgage_code, a.gross_amt,
                               a.brokerage_fee, a.discount_fee, e.user_id, concat(e.user_fname, ' ', e.user_lname ) broker, e.doctracker_group
                          FROM mortgage_table a
                          JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                          JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                          JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                          JOIN users_table e on a.agent = e.user_id
                         WHERE a.ab_loan = 'c_inv'
                           AND a.is_deleted = 'no'
                           AND d.is_deleted = 'no'
                           AND a.created_at >= ? AND a.created_at < ?
                           AND d.company_id IN (1, 3, 401, 2022)
                           AND e.user_id = ?
                ) aa
                GROUP BY aa.user_id, aa.broker, aa.doctracker_group
            )
            SELECT 
                bf.user_fname,
                bf.doctracker_group,
                COALESCE(ba.sales_journey_count, 0) AS sales_journey,
                bf.funded_file,
                CASE 
                    WHEN COALESCE(ba.sales_journey_count, 0) > 0 
                    THEN ROUND(bf.funded_file / ba.sales_journey_count, 2)
                    ELSE 0 
                END AS conversion,
                bf.total_gross_amt,
                bf.gross_fee,
                bf.gross_fee_percentage
            FROM broker_funded bf
            LEFT JOIN broker_applications ba ON bf.user_id = ba.broker_id
        ";
        $brokerData = $this->db->select($brokerDataSql, [
            $startDate, $nextMonthStart, // broker_applications
            $startDate, $nextMonthStart, $userId, // broker_funded - first UNION
            $startDate, $nextMonthStart, $userId, // broker_funded - second UNION
            $startDate, $nextMonthStart, $userId  // broker_funded - third UNION
        ]);

        // Add broker group classification
        if (!empty($brokerData)) {
            $broker = $brokerData[0];
            if($broker->doctracker_group == 'Group1 PB') {
                $broker->broker_group = 'PB';
            } elseif($broker->doctracker_group == 'Group1 NB') {
                $broker->broker_group = 'NB';
            } else {
                $broker->broker_group = 'LT';
            }
        }

        // Get sales journey details
        $salesJourneySql = "
            SELECT 
                a.application_id,
                a.id as sales_journey_id,
                MIN(a.created_at) as created_at
            FROM sales_journey a
            JOIN application_table b on a.application_id = b.application_id
            JOIN users_table d on a.broker_id = d.user_id
            WHERE a.created_at >= ? AND a.created_at < ?
            AND b.qualification_group_id in (443,444)
            AND d.user_id = ?
            GROUP BY a.application_id, a.id
            ORDER BY created_at DESC
        ";
        $salesJourneyData = $this->db->select($salesJourneySql, [$startDate, $nextMonthStart, $userId]);

        // Get funded files details
        $fundedFilesSql = "
            SELECT 
                aa.application_id,
                aa.mortgage_id,
                aa.funding_date,
                aa.gross_amt,
                aa.gross_fee,
                aa.fee_percentage,
                aa.company_name
            FROM (
                SELECT 
                    m.application_id,
                    m.mortgage_id,
                    mp.processing_date as funding_date,
                    m.gross_amt,
                    round(m.brokerage_fee + m.discount_fee, 0) AS gross_fee,
                    round((m.brokerage_fee + m.discount_fee) / m.gross_amt, 2) AS fee_percentage,
                    d.name as company_name
                FROM mortgage_table m
                LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                JOIN application_doc ad ON ad.application_id = m.application_id
                JOIN users_table u ON m.agent = u.user_id
                LEFT JOIN alpine_companies_table d on m.company_id = d.id
                WHERE m.is_deleted = 'no'
                    AND mp.processing_date >= ? AND mp.processing_date < ?
                    AND m.company_id <> 0
                    AND ad.id > 0
                    AND ad.accounting_status = 'funded'
                    AND DATE(ad.accounting_date) = DATE(mp.processing_date)
                    AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                    AND m.company_id IN (1, 3, 401, 2022)
                    AND m2.company_id IN (5, 16, 182)
                    AND u.user_id = ?

                UNION

                SELECT 
                    ad.application_id,
                    m.mortgage_id,
                    ssi.status_date as funding_date,
                    sq.gross AS gross_amt,
                    round(sq.broker + sq.discount, 0) AS gross_fee,
                    round((sq.broker + sq.discount) / sq.gross, 2) AS fee_percentage,
                    d.name as company_name
                FROM status_info ssi
                LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
                LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
                LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
                LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
                LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                JOIN users_table u ON m.agent = u.user_id
                LEFT JOIN alpine_companies_table d on m.company_id = d.id
                WHERE m.is_deleted = 'no'
                    AND ssi.status_id = 20
                    AND ssi.status_date >= ? AND ssi.status_date < ?
                    AND si.investor_id NOT IN (31, 100, 248)
                    AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                    AND u.user_id = ?

                UNION

                SELECT 
                    a.application_id,
                    d.mortgage_id,
                    a.created_at as funding_date,
                    a.gross_amt,
                    round(a.brokerage_fee + a.discount_fee, 0) AS gross_fee,
                    round((a.brokerage_fee + a.discount_fee) / a.gross_amt, 2) AS fee_percentage,
                    d2.name as company_name
                FROM mortgage_table a
                JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                JOIN users_table e on a.agent = e.user_id
                LEFT JOIN alpine_companies_table d2 on d.company_id = d2.id
                WHERE a.ab_loan = 'c_inv'
                    AND a.is_deleted = 'no'
                    AND d.is_deleted = 'no'
                    AND a.created_at >= ? AND a.created_at < ?
                    AND d.company_id IN (1, 3, 401, 2022)
                    AND e.user_id = ?
            ) aa
            ORDER BY aa.funding_date DESC
        ";
        $fundedFilesData = $this->db->select($fundedFilesSql, [
            $startDate, $nextMonthStart, $userId, // first UNION
            $startDate, $nextMonthStart, $userId, // second UNION
            $startDate, $nextMonthStart, $userId  // third UNION
        ]);

        return [
            'brokerData' => $brokerData[0] ?? (object)[],
            'salesJourneyData' => $salesJourneyData,
            'fundedFilesData' => $fundedFilesData,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }

    public function pbBreakdown($month = null, $year = null, $province = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First moment of the next month (exclusive end boundary)
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));

        // Map province to company IDs
        $companyFilter = '';
        if ($province) {
            switch ($province) {
                case 'AB':
                    $companyFilter = 'AND a.company_id IN (3)'; // Alpine Mortgage Corp.
                    break;
                case 'BC':
                    $companyFilter = 'AND a.company_id IN (1)'; // Alpine Credits Limited
                    break;
                case 'ON':
                    $companyFilter = 'AND a.company_id IN (401)'; // Alpine Credits Limited Ontario
                    break;
            }
        }

        // Get PB brokers data
        $pbBrokers = ['Alvir', 'Anupreet', 'Jasmine', 'Simon'];
        $pbBrokersStr = "'" . implode("','", $pbBrokers) . "'";

        $sql = "
            SELECT 
                CASE 
                    WHEN aa.gross_amt > 1000000 THEN 'Greater than $1 Million'
                    WHEN aa.gross_amt >= 500000 THEN '$500K - $1 Million'
                    WHEN aa.gross_amt >= 200000 THEN '$200K - $500K'
                    WHEN aa.gross_amt >= 100000 THEN '$100K - $200K'
                    ELSE 'Under $100K'
                END as loan_range,
                CASE 
                    WHEN aa.gross_amt >= 200000 THEN 'Over 200K'
                    ELSE 'Under 200K'
                END as loan_category,
                aa.gross_amt,
                aa.gross_fee
            FROM (
                SELECT m.mortgage_id,
                       m.gross_amt,
                       round(m.brokerage_fee + m.discount_fee, 0) AS gross_fee
                  FROM mortgage_table m
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                  JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                  JOIN application_doc ad ON ad.application_id = m.application_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND mp.processing_date BETWEEN '{$startDate}' AND '{$nextMonthStart}'
                   AND m.company_id <> 0
                   AND ad.id > 0
                   AND ad.accounting_status = 'funded'
                   AND DATE(ad.accounting_date) = mp.processing_date
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND m2.company_id IN (5, 16, 182)
                   AND u.user_fname IN ($pbBrokersStr)
              GROUP BY m.mortgage_id

                 UNION

                SELECT m.mortgage_id,
                       sq.gross AS gross_amt,
                       round(sq.broker + sq.discount, 0) AS gross_fee
                  FROM status_info ssi
             LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
             LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
             LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                  JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
             LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
             LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND ssi.status_id = 20
                   AND ssi.status_date between '{$startDate}' AND '{$nextMonthStart}'
                   AND si.investor_id NOT IN (31, 100, 248)
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND u.user_fname IN ($pbBrokersStr)
              GROUP BY ssi.quote_id

                 UNION

                SELECT d.mortgage_id,
                       a.gross_amt,
                       round(a.brokerage_fee + a.discount_fee, 0) AS gross_fee
                  FROM mortgage_table a
                  JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                  JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                  JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                  JOIN users_table e on a.agent = e.user_id
                 WHERE a.ab_loan = 'c_inv'
                   AND a.is_deleted = 'no'
                   AND d.is_deleted = 'no'
                   AND a.created_at between '{$startDate}' AND '{$nextMonthStart}'
                   AND d.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'd.company_id', $companyFilter) . "
                   AND e.user_fname IN ($pbBrokersStr)
            ) aa
        ";

        $rawData = $this->db->select($sql);

        // Process the data to create breakdown
        $breakdown = [
            'Greater than $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$500K - $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$200K - $500K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$100K - $200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under $100K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Over 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0]
        ];

        foreach ($rawData as $row) {
            $breakdown[$row->loan_range]['files']++;
            $breakdown[$row->loan_range]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_range]['fees'] += $row->gross_fee;

            $breakdown[$row->loan_category]['files']++;
            $breakdown[$row->loan_category]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_category]['fees'] += $row->gross_fee;
        }

        // Calculate totals
        $totalFiles = array_sum(array_column($breakdown, 'files')) / 2; // Divide by 2 since we have both detailed and summary
        $totalVolume = array_sum(array_column($breakdown, 'volume')) / 2;
        $totalFees = array_sum(array_column($breakdown, 'fees')) / 2;

        // Format the response data
        $detailedBreakdown = [];
        $summaryBreakdown = [];

        // Detailed breakdown
        $ranges = ['Greater than $1 Million', '$500K - $1 Million', '$200K - $500K', '$100K - $200K', 'Under $100K'];
        foreach ($ranges as $range) {
            $files = $breakdown[$range]['files'];
            $volume = $breakdown[$range]['volume'];
            $fees = $breakdown[$range]['fees'];

            $detailedBreakdown[] = [
                'category' => $range,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Summary breakdown
        $categories = ['Over 200K', 'Under 200K'];
        foreach ($categories as $category) {
            $files = $breakdown[$category]['files'];
            $volume = $breakdown[$category]['volume'];
            $fees = $breakdown[$category]['fees'];

            $summaryBreakdown[] = [
                'category' => $category,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Add totals
        $detailedBreakdown[] = [
            'category' => 'All Files',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        $summaryBreakdown[] = [
            'category' => 'Total',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        return [
            'detailedBreakdown' => $detailedBreakdown,
            'summaryBreakdown' => $summaryBreakdown,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }

    public function nbBreakdown($month = null, $year = null, $province = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First moment of the next month (exclusive end boundary)
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));

        // Map province to company IDs
        $companyFilter = '';
        if ($province) {
            switch ($province) {
                case 'AB':
                    $companyFilter = 'AND a.company_id IN (3)'; // Alpine Mortgage Corp.
                    break;
                case 'BC':
                    $companyFilter = 'AND a.company_id IN (1)'; // Alpine Credits Limited
                    break;
                case 'ON':
                    $companyFilter = 'AND a.company_id IN (401)'; // Alpine Credits Limited Ontario
                    break;
            }
        }

        // Get NB brokers data (all brokers except PB and LT)
        $pbBrokers = ['Alvir', 'Anupreet', 'Jasmine', 'Simon'];
        $ltBrokers = ['Adrian', 'Kyra', 'Noemi', 'Lauren'];
        $excludedBrokers = array_merge($pbBrokers, $ltBrokers);
        $excludedBrokersStr = "'" . implode("','", $excludedBrokers) . "'";

        $sql = "
            SELECT 
                CASE 
                    WHEN aa.gross_amt > 1000000 THEN 'Greater than $1 Million'
                    WHEN aa.gross_amt >= 500000 THEN '$500K - $1 Million'
                    WHEN aa.gross_amt >= 200000 THEN '$200K - $500K'
                    WHEN aa.gross_amt >= 100000 THEN '$100K - $200K'
                    ELSE 'Under $100K'
                END as loan_range,
                CASE 
                    WHEN aa.gross_amt >= 200000 THEN 'Over 200K'
                    ELSE 'Under 200K'
                END as loan_category,
                aa.gross_amt,
                aa.gross_fee
            FROM (
                SELECT m.mortgage_id,
                       m.gross_amt,
                       round(m.brokerage_fee + m.discount_fee, 0) AS gross_fee
                  FROM mortgage_table m
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                  JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                  JOIN application_doc ad ON ad.application_id = m.application_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND mp.processing_date BETWEEN '{$startDate}' AND '{$nextMonthStart}'
                   AND m.company_id <> 0
                   AND ad.id > 0
                   AND ad.accounting_status = 'funded'
                   AND DATE(ad.accounting_date) = mp.processing_date
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND m2.company_id IN (5, 16, 182)
                   AND u.user_fname NOT IN ($excludedBrokersStr)
              GROUP BY m.mortgage_id

                 UNION

                SELECT m.mortgage_id,
                       sq.gross AS gross_amt,
                       round(sq.broker + sq.discount, 0) AS gross_fee
                  FROM status_info ssi
             LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
             LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
             LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                  JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
             LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
             LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND ssi.status_id = 20
                   AND ssi.status_date between '{$startDate}' AND '{$nextMonthStart}'
                   AND si.investor_id NOT IN (31, 100, 248)
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND u.user_fname NOT IN ($excludedBrokersStr)
              GROUP BY ssi.quote_id

                 UNION

                SELECT d.mortgage_id,
                       a.gross_amt,
                       round(a.brokerage_fee + a.discount_fee, 0) AS gross_fee
                  FROM mortgage_table a
                  JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                  JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                  JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                  JOIN users_table e on a.agent = e.user_id
                 WHERE a.ab_loan = 'c_inv'
                   AND a.is_deleted = 'no'
                   AND d.is_deleted = 'no'
                   AND a.created_at between '{$startDate}' AND '{$nextMonthStart}'
                   AND d.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'd.company_id', $companyFilter) . "
                   AND e.user_fname NOT IN ($excludedBrokersStr)
            ) aa
        ";

        $rawData = $this->db->select($sql);

        // Process the data to create breakdown
        $breakdown = [
            'Greater than $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$500K - $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$200K - $500K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$100K - $200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under $100K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Over 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0]
        ];

        foreach ($rawData as $row) {
            $breakdown[$row->loan_range]['files']++;
            $breakdown[$row->loan_range]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_range]['fees'] += $row->gross_fee;

            $breakdown[$row->loan_category]['files']++;
            $breakdown[$row->loan_category]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_category]['fees'] += $row->gross_fee;
        }

        // Calculate totals
        $totalFiles = array_sum(array_column($breakdown, 'files')) / 2; // Divide by 2 since we have both detailed and summary
        $totalVolume = array_sum(array_column($breakdown, 'volume')) / 2;
        $totalFees = array_sum(array_column($breakdown, 'fees')) / 2;

        // Format the response data
        $detailedBreakdown = [];
        $summaryBreakdown = [];

        // Detailed breakdown
        $ranges = ['Greater than $1 Million', '$500K - $1 Million', '$200K - $500K', '$100K - $200K', 'Under $100K'];
        foreach ($ranges as $range) {
            $files = $breakdown[$range]['files'];
            $volume = $breakdown[$range]['volume'];
            $fees = $breakdown[$range]['fees'];

            $detailedBreakdown[] = [
                'category' => $range,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Summary breakdown
        $categories = ['Over 200K', 'Under 200K'];
        foreach ($categories as $category) {
            $files = $breakdown[$category]['files'];
            $volume = $breakdown[$category]['volume'];
            $fees = $breakdown[$category]['fees'];

            $summaryBreakdown[] = [
                'category' => $category,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Add totals
        $detailedBreakdown[] = [
            'category' => 'All Files',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        $summaryBreakdown[] = [
            'category' => 'Total',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        return [
            'detailedBreakdown' => $detailedBreakdown,
            'summaryBreakdown' => $summaryBreakdown,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }

    public function ltBreakdown($month = null, $year = null, $province = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First moment of the next month (exclusive end boundary)
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));

        // Map province to company IDs
        $companyFilter = '';
        if ($province) {
            switch ($province) {
                case 'AB':
                    $companyFilter = 'AND a.company_id IN (3)'; // Alpine Mortgage Corp.
                    break;
                case 'BC':
                    $companyFilter = 'AND a.company_id IN (1)'; // Alpine Credits Limited
                    break;
                case 'ON':
                    $companyFilter = 'AND a.company_id IN (401)'; // Alpine Credits Limited Ontario
                    break;
            }
        }

        // Get LT brokers data
        $ltBrokers = ['Adrian', 'Kyra', 'Noemi', 'Lauren'];
        $ltBrokersStr = "'" . implode("','", $ltBrokers) . "'";

        $sql = "
            SELECT 
                CASE 
                    WHEN aa.gross_amt > 1000000 THEN 'Greater than $1 Million'
                    WHEN aa.gross_amt >= 500000 THEN '$500K - $1 Million'
                    WHEN aa.gross_amt >= 200000 THEN '$200K - $500K'
                    WHEN aa.gross_amt >= 100000 THEN '$100K - $200K'
                    ELSE 'Under $100K'
                END as loan_range,
                CASE 
                    WHEN aa.gross_amt >= 200000 THEN 'Over 200K'
                    ELSE 'Under 200K'
                END as loan_category,
                aa.gross_amt,
                aa.gross_fee
            FROM (
                SELECT m.mortgage_id,
                       m.gross_amt,
                       round(m.brokerage_fee + m.discount_fee, 0) AS gross_fee
                  FROM mortgage_table m
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                  JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                  JOIN application_doc ad ON ad.application_id = m.application_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND mp.processing_date BETWEEN '{$startDate}' AND '{$nextMonthStart}'
                   AND m.company_id <> 0
                   AND ad.id > 0
                   AND ad.accounting_status = 'funded'
                   AND DATE(ad.accounting_date) = mp.processing_date
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND m2.company_id IN (5, 16, 182)
                   AND u.user_fname IN ($ltBrokersStr)
              GROUP BY m.mortgage_id

                 UNION

                SELECT m.mortgage_id,
                       sq.gross AS gross_amt,
                       round(sq.broker + sq.discount, 0) AS gross_fee
                  FROM status_info ssi
             LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
             LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
             LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                  JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
             LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
             LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND ssi.status_id = 20
                   AND ssi.status_date between '{$startDate}' AND '{$nextMonthStart}'
                   AND si.investor_id NOT IN (31, 100, 248)
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND u.user_fname IN ($ltBrokersStr)
              GROUP BY ssi.quote_id

                 UNION

                SELECT d.mortgage_id,
                       a.gross_amt,
                       round(a.brokerage_fee + a.discount_fee, 0) AS gross_fee
                  FROM mortgage_table a
                  JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                  JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                  JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                  JOIN users_table e on a.agent = e.user_id
                 WHERE a.ab_loan = 'c_inv'
                   AND a.is_deleted = 'no'
                   AND d.is_deleted = 'no'
                   AND a.created_at between '{$startDate}' AND '{$nextMonthStart}'
                   AND d.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'd.company_id', $companyFilter) . "
                   AND e.user_fname IN ($ltBrokersStr)
            ) aa
        ";

        $rawData = $this->db->select($sql);

        // Process the data to create breakdown
        $breakdown = [
            'Greater than $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$500K - $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$200K - $500K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$100K - $200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under $100K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Over 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0]
        ];

        foreach ($rawData as $row) {
            $breakdown[$row->loan_range]['files']++;
            $breakdown[$row->loan_range]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_range]['fees'] += $row->gross_fee;

            $breakdown[$row->loan_category]['files']++;
            $breakdown[$row->loan_category]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_category]['fees'] += $row->gross_fee;
        }

        // Calculate totals
        $totalFiles = array_sum(array_column($breakdown, 'files')) / 2; // Divide by 2 since we have both detailed and summary
        $totalVolume = array_sum(array_column($breakdown, 'volume')) / 2;
        $totalFees = array_sum(array_column($breakdown, 'fees')) / 2;

        // Format the response data
        $detailedBreakdown = [];
        $summaryBreakdown = [];

        // Detailed breakdown
        $ranges = ['Greater than $1 Million', '$500K - $1 Million', '$200K - $500K', '$100K - $200K', 'Under $100K'];
        foreach ($ranges as $range) {
            $files = $breakdown[$range]['files'];
            $volume = $breakdown[$range]['volume'];
            $fees = $breakdown[$range]['fees'];

            $detailedBreakdown[] = [
                'category' => $range,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Summary breakdown
        $categories = ['Over 200K', 'Under 200K'];
        foreach ($categories as $category) {
            $files = $breakdown[$category]['files'];
            $volume = $breakdown[$category]['volume'];
            $fees = $breakdown[$category]['fees'];

            $summaryBreakdown[] = [
                'category' => $category,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Add totals
        $detailedBreakdown[] = [
            'category' => 'All Files',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        $summaryBreakdown[] = [
            'category' => 'Total',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        return [
            'detailedBreakdown' => $detailedBreakdown,
            'summaryBreakdown' => $summaryBreakdown,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }

    public function allBreakdown($month = null, $year = null, $province = null) {
        // Default to current month/year if not provided
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');

        // Start of the month
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

        // First moment of the next month (exclusive end boundary)
        $nextMonthStart = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));

        // Map province to company IDs
        $companyFilter = '';
        if ($province) {
            switch ($province) {
                case 'AB':
                    $companyFilter = 'AND a.company_id IN (3)'; // Alpine Mortgage Corp.
                    break;
                case 'BC':
                    $companyFilter = 'AND a.company_id IN (1)'; // Alpine Credits Limited
                    break;
                case 'ON':
                    $companyFilter = 'AND a.company_id IN (401)'; // Alpine Credits Limited Ontario
                    break;
            }
        }

        $sql = "
            SELECT 
                CASE 
                    WHEN aa.gross_amt > 1000000 THEN 'Greater than $1 Million'
                    WHEN aa.gross_amt >= 500000 THEN '$500K - $1 Million'
                    WHEN aa.gross_amt >= 200000 THEN '$200K - $500K'
                    WHEN aa.gross_amt >= 100000 THEN '$100K - $200K'
                    ELSE 'Under $100K'
                END as loan_range,
                CASE 
                    WHEN aa.gross_amt >= 200000 THEN 'Over 200K'
                    ELSE 'Under 200K'
                END as loan_category,
                aa.gross_amt,
                aa.gross_fee
            FROM (
                SELECT m.mortgage_id,
                       m.gross_amt,
                       round(m.brokerage_fee + m.discount_fee, 0) AS gross_fee
                  FROM mortgage_table m
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
                  JOIN mortgage_payments_table mp ON m.mortgage_id = mp.mortgage_id AND mp.is_sale = 'yes'
                  JOIN application_doc ad ON ad.application_id = m.application_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND mp.processing_date BETWEEN '{$startDate}' AND '{$nextMonthStart}'
                   AND m.company_id <> 0
                   AND ad.id > 0
                   AND ad.accounting_status = 'funded'
                   AND DATE(ad.accounting_date) = mp.processing_date
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
                   AND m2.company_id IN (5, 16, 182)
              GROUP BY m.mortgage_id

                 UNION

                SELECT m.mortgage_id,
                       sq.gross AS gross_amt,
                       round(sq.broker + sq.discount, 0) AS gross_fee
                  FROM status_info ssi
             LEFT JOIN application_doc ad ON ad.application_id = ssi.application_id AND ad.accounting_status = 'funded' AND DATE(ad.accounting_date) = DATE(ssi.status_date)
             LEFT JOIN saved_quote_table sq ON ssi.quote_id = sq.saved_quote_id
             LEFT JOIN application_table a  ON ssi.application_id = a.application_id
                  JOIN mortgage_table m ON ssi.mortgage_id = m.mortgage_id
             LEFT JOIN mortgage_table m2 ON m2.transfer_id = m.mortgage_id AND m2.is_deleted = 'no'
             LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
             LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
                  JOIN users_table u ON m.agent = u.user_id
                 WHERE m.is_deleted = 'no'
                   AND ssi.status_id = 20
                   AND ssi.status_date between '{$startDate}' AND '{$nextMonthStart}'
                   AND si.investor_id NOT IN (31, 100, 248)
                   AND m.ab_loan NOT IN ('c_mtg','c_inv','m_mtg','m_inv')
                   AND m.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'm.company_id', $companyFilter) . "
              GROUP BY ssi.quote_id

                 UNION

                SELECT d.mortgage_id,
                       a.gross_amt,
                       round(a.brokerage_fee + a.discount_fee, 0) AS gross_fee
                  FROM mortgage_table a
                  JOIN mortgage_payments_table b on a.mortgage_id = b.mortgage_id and b.payment_id = 1
                  JOIN mortgage_payments_table c on a.mortgage_id = c.mortgage_id and c.transfer_mortgage = 'yes'
                  JOIN mortgage_table d on c.transfer_mortgage_id = d.mortgage_id
                  JOIN users_table e on a.agent = e.user_id
                 WHERE a.ab_loan = 'c_inv'
                   AND a.is_deleted = 'no'
                   AND d.is_deleted = 'no'
                   AND a.created_at between '{$startDate}' AND '{$nextMonthStart}'
                   AND d.company_id IN (1, 3, 401, 2022)
                   " . str_replace('a.company_id', 'd.company_id', $companyFilter) . "
            ) aa
        ";

        $rawData = $this->db->select($sql);

        // Process the data to create breakdown
        $breakdown = [
            'Greater than $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$500K - $1 Million' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$200K - $500K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            '$100K - $200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under $100K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Over 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0],
            'Under 200K' => ['files' => 0, 'volume' => 0, 'fees' => 0]
        ];

        foreach ($rawData as $row) {
            $breakdown[$row->loan_range]['files']++;
            $breakdown[$row->loan_range]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_range]['fees'] += $row->gross_fee;

            $breakdown[$row->loan_category]['files']++;
            $breakdown[$row->loan_category]['volume'] += $row->gross_amt;
            $breakdown[$row->loan_category]['fees'] += $row->gross_fee;
        }

        // Calculate totals
        $totalFiles = array_sum(array_column($breakdown, 'files')) / 2; // Divide by 2 since we have both detailed and summary
        $totalVolume = array_sum(array_column($breakdown, 'volume')) / 2;
        $totalFees = array_sum(array_column($breakdown, 'fees')) / 2;

        // Format the response data
        $detailedBreakdown = [];
        $summaryBreakdown = [];

        // Detailed breakdown
        $ranges = ['Greater than $1 Million', '$500K - $1 Million', '$200K - $500K', '$100K - $200K', 'Under $100K'];
        foreach ($ranges as $range) {
            $files = $breakdown[$range]['files'];
            $volume = $breakdown[$range]['volume'];
            $fees = $breakdown[$range]['fees'];

            $detailedBreakdown[] = [
                'category' => $range,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Summary breakdown
        $categories = ['Over 200K', 'Under 200K'];
        foreach ($categories as $category) {
            $files = $breakdown[$category]['files'];
            $volume = $breakdown[$category]['volume'];
            $fees = $breakdown[$category]['fees'];

            $summaryBreakdown[] = [
                'category' => $category,
                'files' => $files,
                'files_ratio' => $totalFiles > 0 ? round(($files / $totalFiles) * 100, 0) : 0,
                'volume' => $volume,
                'volume_ratio' => $totalVolume > 0 ? round(($volume / $totalVolume) * 100, 0) : 0,
                'ave_size' => $files > 0 ? round($volume / $files, 0) : 0,
                'total_fees' => $fees,
                'fees_ratio' => $totalFees > 0 ? round(($fees / $totalFees) * 100, 0) : 0,
                'fee_percentage' => $volume > 0 ? round(($fees / $volume) * 100, 2) : 0
            ];
        }

        // Add totals
        $detailedBreakdown[] = [
            'category' => 'All Files',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        $summaryBreakdown[] = [
            'category' => 'Total',
            'files' => $totalFiles,
            'files_ratio' => null,
            'volume' => $totalVolume,
            'volume_ratio' => null,
            'ave_size' => $totalFiles > 0 ? round($totalVolume / $totalFiles, 0) : 0,
            'total_fees' => $totalFees,
            'fees_ratio' => null,
            'fee_percentage' => $totalVolume > 0 ? round(($totalFees / $totalVolume) * 100, 2) : 0
        ];

        return [
            'detailedBreakdown' => $detailedBreakdown,
            'summaryBreakdown' => $summaryBreakdown,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'startDate' => $startDate,
            'endDate' => $nextMonthStart
        ];
    }
}
