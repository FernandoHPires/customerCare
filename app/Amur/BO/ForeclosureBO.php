<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\PayoutApproval; 
use App\Models\PayoutPayment;
use App\Amur\Factory\Factory;
use DateTime;

class ForeclosureBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }
    
    public function getForeclosures(DateTime $startDate, DateTime $endDate) {
        $query = "select a.id, b.application_id, a.mortgage_id, b.mortgage_code,
                         fn_GetPositionsByMortgageID(a.mortgage_id) position,
                         fn_GetSpouse1NameByApplicationID(b.application_id) client_name,
                         c.abbreviation, fn_GetProvincesByMortgageID(a.mortgage_id) province,
                         a.created_at, fn_GetOSBByMortgageID(a.mortgage_id) alpine_osb,
                         fn_GetOtherOSBByMortgageID(a.mortgage_id, 1) first_mortgage_osb,
                         fn_GetOtherOSBByMortgageID(a.mortgage_id, 2) second_mortgage_osb,
                         fn_GetAppraisalByMortgageID(a.mortgage_id) appraisal_value
                    from payout_approval a
                    join mortgage_table b on a.mortgage_id = b.mortgage_id
                    join alpine_companies_table c on b.company_id = c.id
                   where a.category_id = 538
                     and a.deleted_at is null
                     and (a.processed_at is null or a.processed_at between ? and ?)
                order by a.created_at";
        $res = $this->db->select($query, [$startDate, $endDate]);

        $foreclosures = array();
        foreach($res as $key => $value) {
            $foreclosures[] = [
                'id' => $value->id,
                'applicationId' => $value->application_id,
                'mortgageCode' => $value->mortgage_code,
                'position' => $value->position,
                'clientName' => $value->client_name,
                'lenderName' => $value->abbreviation,
                'lawyerName' => '',
                'province' => $value->province,
                'createdAt' => new DateTime($value->created_at),
                'createdAtS' => (new DateTime($value->created_at))->format('YmdHis'),
                'closedAtS' => '',
                'alpineOSB' => $value->alpine_osb,
                'firstMortgageOSB' => $value->first_mortgage_osb,
                'secondMortgageOSB' => $value->second_mortgage_osb,
                'ltv' => '',
                'appraisalValue' => $value->appraisal_value
            ];
        }

        return $foreclosures;
    }

    public function getProcessForeclosure(DateTime $startDate, DateTime $endDate) {

        $query = "select mt.application_id, mt.mortgage_id, mpt.payout_id, mt.mortgage_code,
                         fn_GetSpouse1NameByApplicationID(mt.application_id) name, mpt.payout_date,
                         mpt.last_balance, mpt.payout_amount, mpt.per_diem, mt.current_balance,
                         mpt.payout_reason, pa.id, pa.canceled_at, mpt.interest_accrual_date, mt.company_id
                    from mortgage_payouts_table mpt
              inner join mortgage_table mt on mpt.mortgage_id = mt.mortgage_id
              inner join payout_approval pa on mt.mortgage_id = pa.mortgage_id and mpt.payout_id = pa.payout_id
                   where mpt.payout_date between ? and ?
                     and pa.canceled_at is null
                     and pa.category_id = 538
                     and pa.deleted_at is null
                order by pa.canceled_at desc, mpt.payout_date";
        $resProcess = $this->db->select($query,[$startDate,$endDate]);

        $foreclosures = array();
        if($resProcess) {
            
            foreach($resProcess as $key => $value) {

                $status = '';

                if ($value->canceled_at <> null) {
                    $status = 'Deleted';
                } else {
                    $query = "SELECT * FROM mortgage_payments_table pmt, mortgage_payouts_table pout 
                                WHERE pmt.mortgage_id = pout.mortgage_id 
                                AND pmt.payout_id = pout.payout_id 
                                AND pmt.mortgage_id = ?";
                    $res = $this->db->select($query,[$value->mortgage_id]);  
                   
                    if (count($res) > 0) {
                        $status = 'Processed';
                    }
                }


                $foreclosures[] = [
                    "id"              => $value->id,
                    "application_id"  => $value->application_id,
                    "mortgage_id"     => $value->mortgage_id,
                    "payout_id"       => $value->payout_id,
                    "company_id"      => $value->company_id,
                    "mortgage_code"   => $value->mortgage_code,
                    "name"            => $value->name,
                    "payout_date"     => new DateTime($value->payout_date),
                    "payout_date_s"   => (new DateTime($value->payout_date))->format('YmdHis'),
                    "interest_acrrual_date" => new DateTime($value->interest_accrual_date),
                    "last_balance"    => round($value->last_balance,2),
                    "payout_amount"   => round($value->payout_amount,2),
                    "per_diem"        => round($value->per_diem,2),
                    "current_balance" => round($value->current_balance,2),
                    "payout_reason"   => $value->payout_reason,
                    "status"          => $status
                ];
            }

        }
        return $foreclosures;
    }

    public function getCancelForeclosure($startDate, $endDate) {
        
        $startDeleted = new DateTime();
        $endDeleted   = new DateTime();
        $startDeleted->setTime(0,0,1);
        $endDeleted->setTime(23,59,59);
        
        $query = "select mt.application_id, mt.mortgage_id, mpt.payout_id, mt.mortgage_code,
                         fn_GetSpouse1NameByApplicationID(mt.application_id) name, 
                         mpt.payout_date, mpt.last_balance, mpt.payout_amount, mpt.per_diem,
                         mt.current_balance, mpt.payout_reason, pa.id, pa.canceled_at, pa.status
                    from mortgage_payouts_table mpt
              inner join mortgage_table mt on mpt.mortgage_id = mt.mortgage_id
              inner join payout_approval pa on mt.mortgage_id = pa.mortgage_id and mpt.payout_id = pa.payout_id
                  where pa.category_id = 538
                    and pa.deleted_at is null
                    and ((mpt.payout_date between ? and ? and pa.canceled_at is null)
                         or (pa.canceled_at between ? and ?))
               order by pa.canceled_at desc, mpt.payout_date";
        $resProcess = $this->db->select($query,[$startDate,$endDate,$startDeleted,$endDeleted]);

        $foreclosure = array();
        if($resProcess) {
            
            foreach($resProcess as $key => $value) {

                $status = '';

                if ($value->canceled_at <> null) {
                    if ($value->status == 'R') {
                        $status = 'Rejected';
                    }else {
                        $status = 'Canceled';
                    }  
                }

                $query = "SELECT * FROM mortgage_payments_table pmt, mortgage_payouts_table pout 
                          WHERE pmt.mortgage_id = pout.mortgage_id 
                          AND pmt.payout_id = pout.payout_id 
                          AND pmt.mortgage_id = ?";
                $res = $this->db->select($query,[$value->mortgage_id]);

                if (count($res) == 0) {
                    $foreclosure[] = [
                        "application_id"  => $value->application_id,
                        "mortgage_id"     => $value->mortgage_id,
                        "payout_id"       => $value->payout_id,
                        "mortgage_code"   => $value->mortgage_code,
                        "name"            => $value->name,
                        "payout_date"     => new DateTime($value->payout_date),
                        "last_balance"    => round($value->last_balance,2),
                        "payout_amount"   => round($value->payout_amount,2),
                        "per_diem"        => round($value->per_diem,2),
                        "current_balance" => round($value->current_balance,2),
                        "payout_reason"   => $value->payout_reason,
                        "status"          => $status,
                        "id"              => $value->id,  
                    ];
                }
            }
        }

        return $foreclosure;
    }

    public function cancelForeclosure($id, $comment) {

        $payoutApproval = PayoutApproval::find($id);
        if(!$payoutApproval) {
            $this->logger->error('ForeclosureBO->cancelForeclosure - Could not find payout',[$id]);
            return false;
        }

        $userId = Auth::user()->user_id;

        $this->logger->debug('ForeclosureBO->cancelForeclosure',[$id,$userId,$comment]);

        $query = "select * from mortgage_payments_table pmt
                    join mortgage_payouts_table pout on pmt.mortgage_id = pout.mortgage_id and pmt.payout_id = pout.payout_id
                   where pmt.mortgage_id = ?";
        $res = $this->db->select($query,[$payoutApproval->mortgage_id]);

        if(count($res) == 0) {

            $data = PayoutPayment::query()
            ->where("mortgage_id", $payoutApproval->mortgage_id)
            ->where("payout_id", $payoutApproval->payout_id)
            ->get();
            
            if(count($data) > 0) {

                $fields = [
                    "status"        => 'C',
                    "canceled_by"   => $userId,
                    "canceled_at"   => new DateTime(),
                    "cancel_reason" => $comment
                ];

                PayoutApproval::query()
                ->where("id", $id)
                ->update($fields);
                //$this->logger->info('ForeclosureBO->cancelForeclosure - Update Table payout_approval - id, mortgage_id, payout_id, userId, comment',[$id, $mortgageId,$payoutId,$userId,$comment]);

                foreach($data as $key => $value) {
                    $this->logger->info('ForeclosureBO->cancelForeclosure',[$id,$value->payment_id,$value->flag,$value->payment_amount]);

                    $fields     = ['flag' => $value->flag, 'pmt_amt' => $value->payment_amount];
                    $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                    $this->db->update('mortgage_payments_table', $fields, $conditions);
                }

                return true;
            }
        }

        return false;

    }

    public function foreclosureReject($id, $comment) {

        $payoutApproval = PayoutApproval::find($id);
        if(!$payoutApproval) {
            $this->logger->error('ForeclosureBO->foreclosureReject - Could not find payout',[$id]);
            return false;
        }

        $userId = Auth::user()->user_id;

        $this->logger->info('ForeclosureBO->foreclosureReject',[$id,$userId,$comment]);
       
        $query = "select * from mortgage_payments_table pmt
                    join mortgage_payouts_table pout on pmt.mortgage_id = pout.mortgage_id and pmt.payout_id = pout.payout_id
                   where pmt.mortgage_id = ?";
        $res = $this->db->select($query,[$payoutApproval->mortgage_id]);

        if(count($res) == 0) {

            $payoutApproval->status = 'R';
            $payoutApproval->canceled_by = $userId;
            $payoutApproval->canceled_at = new DateTime();
            $payoutApproval->cancel_reason = $comment;

            $utilities = Factory::create('Utilities\\Notification', $this->logger, $this->db);
            $utilities->foreclosureRejected($id,$userId,$comment);

            return true;
        }

        return false;
    }
}