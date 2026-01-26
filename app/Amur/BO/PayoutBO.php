<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\PayoutApproval; 
use App\Models\GroupMembersTable;
use App\Models\MortgagePayoutsTable;
use App\Models\PayoutPayment;
use App\Models\MortgageTable;
use App\Models\MortgagePaymentsTable;
use App\Models\SavedQuoteTable;
use App\Models\SaleInvestorTable;
use App\Amur\Factory\Factory;
use App\Amur\Utilities\Loan;
use DateTime;
use DateTimeZone;

class PayoutBO {

    private $logger;
    private $db;
    private $cutoffTime = 14;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function createPayout($userId, $mortgageId, $payoutId) {

        $this->logger->info('PayoutBO->createPayout',[$userId,$mortgageId,$payoutId]);

        $mortgageTable = MortgageTable::find($mortgageId);
        if(!$mortgageTable) {
            $this->logger->error('PayoutBO->createPayout - Could not find mortgage',[$mortgageId]);
            return false;
        }

        $papCompanies = PapBO::getPapCompanies();

        if($userId > 0 && $payoutId > 0 && in_array($mortgageTable->company_id, $papCompanies)) {

            $pa = PayoutApproval::query()
            ->where('mortgage_id', $mortgageId)
            ->where('payout_id', $payoutId)
            ->first();

            $mp = MortgagePayoutsTable::query()
            ->where('mortgage_id', $mortgageId)
            ->where('payout_id', $payoutId)
            ->first();

            //Should not create payout approval for child cards            
            if(!$pa && $mp && $mortgageTable->ab_loan != 'c_inv') {
                if($mp->estimated == 'yes' && $mp->foreclosure == 'no') {
                    //Payout
                    $payout = new PayoutApproval;
                    $payout->mortgage_id = $mortgageId;
                    $payout->payout_id = $payoutId;
                    $payout->category_id = 515;
                    $payout->admin_status = 'P';
                    $payout->broker_status = 'P';
                    $payout->created_by = $userId;
                    $payout->updated_by = $userId;
                    $payout->save();

                } elseif($mp->foreclosure == 'yes') {
                    //Foreclosure
                    /*$payout = new PayoutApproval;
                    $payout->mortgage_id = $mortgageId;
                    $payout->payout_id = $payoutId;
                    $payout->category_id = 538;
                    $payout->admin_approved_at = new DateTime();
                    $payout->admin_approved_by = $userId;
                    $payout->admin_status = 'A';
                    $payout->broker_approved_at = new DateTime();
                    $payout->broker_approved_by = $userId;
                    $payout->broker_status = 'A';
                    $payout->created_by = $userId;
                    $payout->updated_by = $userId;
                    $payout->save();

                    $this->cancelPayments($payout->id);*/
                }
                
                return true;

            } elseif($pa) {
                //payout cancelled - don't do anything else
                if(!is_null($pa->canceled_at)){
                    $this->logger->info('PayoutBO->createPayout - cancelled at',[$mortgageId, $pa->canceled_at, $pa->cancel_reason]);

                    /*$pa->status = null;
                    $pa->canceled_at = null;
                    $pa->canceled_by = null;
                    $pa->cancel_reason = null;
                    $pa->admin_approved_at = null;
                    $pa->admin_approved_by = null;
                    $pa->broker_approved_at = null;
                    $pa->broker_approved_by = null;
                    $pa->lawyer_status = null;
                    $pa->lawyer_at = null;
                    $pa->lawyer_by = null;
                    $pa->status = null;
                    $pa->admin_status = 'P';
                    $pa->broker_status = 'P';
                    $pa->save();*/
                }
            }
        }
        
        return false;
    }

    public function getPayouts($companyId, DateTime $startDate, DateTime $endDate) {

        $userId       = Auth::user()->user_id;
        $startDeleted = new DateTime();
        $endDeleted   = new DateTime();
        $startDeleted->setTime(0,0,1);
        $endDeleted->setTime(23,59,59);

        $gm = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('deleted', 0)
        ->get();    
        
        $access = '';
        foreach($gm as $key => $value) {
            if($value->group_id == 16) {
                $access = 'admin';
                break;
            } elseif($value->group_id == 17) {
                $access = 'broker';
                break;
            }
        }

        $query = "select pa.id, mt.application_id, mt.mortgage_code, fn_GetSpouse1NameByApplicationID(mt.application_id) name,
                         pa.created_at, pa.admin_status, pa.broker_status, pa.lawyer_status, pa.canceled_at,
                         pa.processed_at, pa.mortgage_id, pa.status, concat(us.user_fname, ' ', us.user_lname) created_by,
                         mt.company_id, mp.payout_date, pa.cancel_reason
                    from payout_approval pa
              inner join mortgage_payouts_table mp on pa.mortgage_id = mp.mortgage_id and pa.payout_id = mp.payout_id
              inner join mortgage_table mt on pa.mortgage_id = mt.mortgage_id
               left join users_table us on pa.created_by = us.user_id
                   where pa.category_id = 515
                     and pa.deleted_at is null
                     and (
                            (pa.created_at between ? and ? and pa.canceled_at is null) or
                            (pa.canceled_at between ? and ?) or
                            pa.status = 'R' or 
                            pa.processed_at is null
                         )
                order by pa.canceled_at desc, pa.created_at asc";
        $res = $this->db->select($query,[$startDate,$endDate,$startDeleted,$endDeleted]);
        
        $payouts = array();
        if(count($res) > 0) {
            foreach($res as $key => $value) {
                $disable = '';

                $status = '';
                if($value->status == 'C') {
                    $status = 'Canceled';
                } elseif($value->status == 'R') {
                    $status = 'Rejected';
                } elseif(!is_null($value->processed_at)) {
                    $status = 'Completed';
                }

                if($value->admin_status == 'A' && $value->broker_status == 'A') {
                    $disable = 'yes';
                } elseif($value->admin_status == 'A' && $access == 'admin') {
                    $disable = 'yes';
                } elseif($value->broker_status == 'A' && $access == 'broker') {
                    $disable = 'yes';
                } else {
                    $disable = 'no';
                }

                if($companyId == 0 || $companyId == $value->company_id) {
                    $payouts[] = [
                        "id"             => $value->id,
                        "application_id" => $value->application_id,
                        "mortgage_id"    => $value->mortgage_id,
                        "company_id"     => $value->company_id,
                        "mortgage_code"  => $value->mortgage_code,
                        "name"           => $value->name,
                        "payout_date"    => new DateTime($value->payout_date),
                        "payout_date_s"  => (new DateTime($value->payout_date))->format('YmdHis'),
                        "created_at"     => new DateTime($value->created_at),
                        "created_at_s"   => (new DateTime($value->created_at))->format('YmdHis'),
                        "created_by"     => $value->created_by,
                        "admin_status"   => $value->admin_status,
                        "broker_status"  => $value->broker_status,
                        "lawyer_status"  => is_null($value->lawyer_status) ? '' : $value->lawyer_status,
                        "finance_status" => $status,
                        "status"         => $status,
                        "cancel_reason"  => $value->cancel_reason,
                        "disable"        => $disable
                    ];
                }
            }
        }

        return $payouts;
    }

    public function acceptPayout($id) {

        $userId = Auth::user()->user_id;

        $gm = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('deleted', 0)
        ->get();    

        foreach($gm as $key => $value) {

            if($value->group_id == 16) {

                $this->logger->info('PayoutBO->acceptPayout - Admin Accept id,user_id',[$id,$userId]);

                $fields = [
                    "admin_approved_by" => $userId,
                    "admin_approved_at" => new DateTime(),
                    "admin_status"      => 'A',
                    "broker_approved_by" => $userId,
                    "broker_approved_at" => new DateTime(),
                    "broker_status"      => 'A',
                    "updated_by"        => $userId
                ];
                
                PayoutApproval::query()
                ->where("id", $id)
                ->update($fields);

                $this->cancelPayments($id);

                //find children cards, also cancel them
                $payoutApproval = PayoutApproval::find($id);
                //$this->logger->info('PayoutBO->acceptPayout - Payout Approval',[$payoutApproval]);

                if($payoutApproval) {
                    $mortgageId = $payoutApproval->mortgage_id;
                    $payoutId   = $payoutApproval->payout_id;

                    $cInvCard = MortgageTable::query()
                    ->where("is_deleted", 'no')
                    ->where("ab_loan", 'c_inv')
                    ->where("parent", $mortgageId)
                    ->get();

                    foreach($cInvCard as $key => $value) {
                        $this->cancelChildrenPayments($value->mortgage_id,$payoutId);
                    }
                }

                return true;
            }
            
            if($value->group_id == 17) {

                /*$this->logger->info('PayoutBO->acceptPayout - Broker Accept  id, user_id',[$id,$userId]);

                $fields = [
                    "broker_approved_by" => $userId,
                    "broker_approved_at" => new DateTime(),
                    "broker_status"      => 'A',
                    "updated_by"         => $userId
                ];

                PayoutApproval::query()
                ->where("id", $id)
                ->update($fields);
                
                $this->cancelPayments($id);
                
                return true;*/
            }            
        }        

        return false;
    }

    public function cancelPayments($id) {

        $payoutApproval = PayoutApproval::query()
        ->where("id", $id)
        ->where("admin_status", 'A')
        ->where("broker_status", 'A')
        ->where("status", null)
        ->first();

        if($payoutApproval) {
            $this->logger->info('PayoutBO->cancelPayments',[$id]);

            $mortgageId	= $payoutApproval->mortgage_id;
            $payoutId   = $payoutApproval->payout_id;

            if ($mortgageId > 0 && $payoutId > 0) {
                $query = "select a.mortgage_id, a.payout_id, b.payment_id, b.processing_date, b.flag, a.payout_amount, b.pmt_amt
                            from mortgage_payouts_table a
                      inner join mortgage_payments_table b on b.mortgage_id = a.mortgage_id 
                           where a.mortgage_id = ? 
                             and a.payout_id = ?
                             and b.is_processed = 'no'
                             and b.pap_file_payment_id is null";
                
                $res = $this->db->select($query,[$mortgageId,$payoutId]);

                foreach($res as $key => $value) {
                    $payment = new PayoutPayment();
                    $payment->mortgage_id     = $value->mortgage_id;
                    $payment->payout_id       = $value->payout_id;
                    $payment->payment_id      = $value->payment_id;
                    $payment->processing_date = $value->processing_date;
                    $payment->flag            = $value->flag;
                    $payment->payment_amount  = $value->pmt_amt;
                    $payment->save();

                    $userId = Auth::user()->user_id ?? null;    
                    $fields = ['flag' => 'Zero payment', 'pmt_amt' => 0, 'updated_by' => $userId];
                    $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                    $this->db->update('mortgage_payments_table', $fields, $conditions);
                    $this->logger->info('PayoutBO->cancelPayments update the table back to zero',[$id]);

                }
            }
        }
    }

    public function cancelChildrenPayments($mortgageId,$payoutId=0) {

        if ($mortgageId > 0 && $payoutId > 0) {
            $query = "select a.mortgage_id, a.payout_id, b.payment_id, b.processing_date, b.flag, a.payout_amount, b.pmt_amt
                        from mortgage_payouts_table a
                    inner join mortgage_payments_table b on b.mortgage_id = a.mortgage_id 
                        where a.mortgage_id = ? 
                            and a.payout_id = ?
                            and b.is_processed = 'no'
                            and b.pap_file_payment_id is null";

            //$this->logger->info('PayoutBO->cancelChildrenPayments',[$query,$mortgageId,$payoutId]);
            $res = $this->db->select($query,[$mortgageId,$payoutId]);

            foreach($res as $key => $value) {
                $payment = new PayoutPayment();
                $payment->mortgage_id     = $value->mortgage_id;
                $payment->payout_id       = $value->payout_id;
                $payment->payment_id      = $value->payment_id;
                $payment->processing_date = $value->processing_date;
                $payment->flag            = $value->flag;
                $payment->payment_amount  = $value->pmt_amt;
                //Ask Diego, if children cards need payout_payments table record?
                //$payment->save();

                $userId = Auth::user()->user_id ?? null; 
                $fields = ['flag' => 'Zero payment', 'pmt_amt' => 0, 'updated_by' => $userId];
                $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                $this->db->update('mortgage_payments_table', $fields, $conditions);

            }           
            //$this->logger->info('PayoutBO->cancelChildrenPayments update the table back to zero',[$mortgageId);
        }

    }

    public function getProcessPayout($companyId, $startDate, $endDate) {
       
        $query = "select mt.application_id, mt.mortgage_id, mpt.payout_id, mt.mortgage_code,
                         fn_GetSpouse1NameByApplicationID(mt.application_id) name, 
                         mpt.payout_date, mpt.last_balance, mpt.payout_amount, mpt.per_diem,
                         mt.current_balance, mpt.payout_reason, pa.id, pa.canceled_at, pa.processed_at,
                         pa.status, mt.company_id, mpt.interest_accrual_date
                    from mortgage_payouts_table mpt
              inner join mortgage_table mt on mpt.mortgage_id = mt.mortgage_id
              inner join payout_approval pa on mt.mortgage_id = pa.mortgage_id and mpt.payout_id = pa.payout_id
                   where pa.category_id = 515
                     and ((mpt.payout_date between ? and ? and pa.admin_status = 'A' and pa.broker_status = 'A' and pa.canceled_at is null)
                         or pa.status = 'R')
                     and pa.deleted_at is null
                order by mt.application_id, pa.canceled_at desc, mpt.payout_date asc";
        $resProcess = $this->db->select($query,[$startDate,$endDate]);

        $payouts = array();
        foreach($resProcess as $key => $value) {

            $status = '';
            if($value->status == 'C') {
                $status = 'Canceled';
            } elseif($value->status == 'R') {
                $status = 'Rejected';
            } else {
                $query = "SELECT * FROM mortgage_payments_table pmt, mortgage_payouts_table pout 
                            WHERE pmt.mortgage_id = pout.mortgage_id 
                                AND pmt.payout_id = pout.payout_id 
                                AND pmt.mortgage_id = ?";
                $res = $this->db->select($query,[$value->mortgage_id]);
                
                if(count($res) > 0) {
                    $status = 'Processed';
                } else {
                    $status = 'Active';
                }
            }

            if($companyId == 0 || $status == 'Canceled' || $status == 'Rejected' || $companyId == $value->company_id) {
                $payouts[] = [
                    "id"              => $value->id,
                    "application_id"  => $value->application_id,
                    "mortgage_id"     => $value->mortgage_id,
                    "company_id"      => $value->company_id,
                    "payout_id"       => $value->payout_id,
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

        return $payouts;
    }

    public function processPayout($id, $payoutAmount, DateTime $paymentReceivedDate) {
        
        $this->logger->info('PayoutBO->processPayout',[$id, $payoutAmount, $paymentReceivedDate->format('Y-m-d')]);

        $payoutApproval = PayoutApproval::find($id);
        if(!$payoutApproval) {
            $this->logger->error('PayoutBO->processPayout - Could not find payout approval',[$id]);
            return false;
        }

        if(!is_null($payoutApproval->processed_at) || !is_null($payoutApproval->canceled_at)) {
            $this->logger->error('PayoutBO->processPayout - Payout already processed or canceled',[$id]);
            return false;
        }

        $mortgageId = $payoutApproval->mortgage_id;
        $payoutId = $payoutApproval->payout_id;

        $cInvCard = MortgageTable::query()
        ->where("is_deleted", 'no')
        ->where("ab_loan", 'c_inv')
        ->where("parent", $mortgageId)
        ->get();

        $cInvCardCount = count($cInvCard);

        $mortgageTable = MortgageTable::find($mortgageId);

        if(!$mortgageTable) {
            $this->logger->error('PayoutBO->processPayout - Could not find mortgage',[$mortgageId]);
            return false;
        }

        $applicationId = $mortgageTable->application_id;
        $companyId     = $mortgageTable->company_id;
        $transferId    = $mortgageTable->transfer_id;
        $abLoan        = $mortgageTable->ab_loan;

        $mortgagePayoutsTable = MortgagePayoutsTable::query()
        ->where("mortgage_id", $mortgageId)
        ->where("payout_id", $payoutId)
        ->first();

        if(!$mortgagePayoutsTable) {
            $this->logger->error('PayoutBO->processPayout - Could not find payout calculation',[$mortgageId, $payoutId]);
            return false;
        }

        //$payoutDate = $mortgagePayoutsTable->payout_date;
        //$mip = $mortgagePayoutsTable->mip;
        $interest_accrued = $mortgagePayoutsTable->interest_accrued;

        if(!Loan::isLockedDate($companyId, $paymentReceivedDate)) {

            $this->db->beginTransaction();
            try {
                
                $this->logger->debug('PayoutBO->processPayout - Not locked period',[$companyId]);

                $diffPayout = $payoutAmount - $mortgagePayoutsTable->payout_amount;
                //$mip += $diffPayout;
                $interest_accrued += $diffPayout;

                $mortgagePayoutsTable->payout_amount = $payoutAmount;
                //$mortgagePayoutsTable->mip = $mip;
                $mortgagePayoutsTable->interest_accrued = $interest_accrued;
                $mortgagePayoutsTable->save();

                $lastPaymentId = MortgagePaymentsTable::where('mortgage_id', $mortgageId)->max('payment_id');

                $userId = Auth::user()->user_id ?? null;
                $queryPayout = new MortgagePaymentsTable;
                $queryPayout->payment_id           = $lastPaymentId + 1;
                $queryPayout->mortgage_id          = $mortgageId;
                $queryPayout->original_date        = $paymentReceivedDate;
                $queryPayout->processing_date      = $paymentReceivedDate;
                $queryPayout->period_date          = $paymentReceivedDate;
                $queryPayout->pmt_amt              = $payoutAmount;
                $queryPayout->is_nsf               = 'no';
                $queryPayout->nsf_id               = '0';
                $queryPayout->is_post_dated_cheque = 'no';
                $queryPayout->is_processed         = 'yes';
                $queryPayout->is_add_on_fee        = 'no';
                $queryPayout->comment              = 'Payout';
                $queryPayout->is_payout            = 'yes';
                $queryPayout->payout_id            = $payoutId;
                $queryPayout->created_by           = $userId;
                $queryPayout->save();

                $mortgageTable->payout_at = $paymentReceivedDate;
                $mortgageTable->save();

                if(!empty($transferId)) {
                    $fields = [
                        "payout_at" => $paymentReceivedDate,
                    ];
                    
                    MortgageTable::query()
                    ->where("mortgage_id", $transferId)
                    ->update($fields);

                    $this->logger->debug('PayoutBO->processPayout - Updated orig mortgage',[$transferId]);
                }

                $this->logger->debug('PayoutBO->processPayout - Process Payout Successful',[$mortgageId,$payoutId]);

                if($abLoan == 'm_inv' && $cInvCardCount >= 2) {

                    $this->logger->debug('PayoutBO->processPayout - Process Payout ab_loan',[$mortgageId,$payoutId]);

                    $originalMortgageId = MortgagePaymentsTable::query()
                    ->where("mortgage_id", $mortgageId)
                    ->where("transfer_mortgage", 'yes')
                    ->get();

                    foreach($originalMortgageId as $key => $value) {
                        $transferMrtgageId = $value->transfer_mortgage_id;
                    }

                    $this->logger->debug('PayoutBO->processPayout - Process Payout ab_loan',[$mortgageId,$payoutId,$transferMrtgageId]);

                    $querySavedQuoteId = SavedQuoteTable::query()
                    ->where("mortgage_id", $transferMrtgageId)
                    ->where("application_id", $applicationId)
                    ->get(); 

                    foreach($querySavedQuoteId as $key => $value) {
                        $savedQuoteId = $value->saved_quote_id;
                    }

                    $saleInvestorRow = SaleInvestorTable::query()
                    ->where("investor_id", 1971)
                    ->where("fm_committed", 'Yes')
                    ->where("saved_quote_id", $savedQuoteId)
                    ->get();                     

                    foreach($saleInvestorRow as $key => $value) {
                        $apInvCo   = $value->ap_inv_co;
                        $bpInvCo   = $value->ap_inv_co;
                        $cpInvCo   = $value->ap_inv_co;
                        $apPayment = $value->ap_payment;
                        $bpPayment = $value->bp_payment;
                        $cpPayment = $value->cp_payment;
                        $apPercent = $value->ap_percent;
                        $bpPercent = $value->bp_percent;
                        $cpPercent = $value->cp_percent;
                    }  

                    if (!empty($apInvCo)) $piecePayment[] = $apPayment; $piecePercent[] = $apPercent;
                    if (!empty($bpInvCo)) $piecePayment[] = $bpPayment; $piecePercent[] = $bpPercent;
                    if (!empty($cpInvCo)) $piecePayment[] = $cpPayment; $piecePercent[] = $cpPercent;

                    $i = 0;
                    $cPayoutAmountTotal = 0;

                    foreach ($cInvCard as $key => $row) {

                        $c_row_payout_info = MortgagePayoutsTable::query()
                        ->where("mortgage_id", $row->mortgage_id)
                        ->where("payout_id", $payoutId)
                        ->get(); 
                        
                        foreach($c_row_payout_info as $keyInfo => $value) {
                            $cPayoutDate   = $value->payout_date;
                            $cPayoutAmount = $value->payout_amount;
                            $cRowPayoutAmount = $value->payout_amount;
                            //$cMip           = $value->mip;
                            $cInterestAccrued = $value->interest_accrued;
                            $cPerDiem = $value->per_diem;
                        } 
                        
                        //To update the payout amount and interest accrued
                        if (!empty($diffPayout)){
                            $roundedCPerDiem = round($cPerDiem, 2);
                            $roundedPerDiem = round($mortgagePayoutsTable->per_diem, 2);
                            $roundCRowPayoutAmount = round($cRowPayoutAmount, 2);

                            //$cDiffPayout = $roundedCPerDiem * ($diffPayout/$roundedPerDiem);
                            if ($roundedPerDiem != 0) {
                                $cDiffPayout = $roundedCPerDiem * ($diffPayout / $roundedPerDiem);
                            } else {
                                $cDiffPayout = 0; 
                            }
                            $cPayoutAmount = $roundCRowPayoutAmount + $cDiffPayout;
                            $cInterestAccrued += $cDiffPayout;                        
                                                    
                            $fields[] = '';
                            $fields = [
                                "payout_amount" => $cPayoutAmount,
                                "interest_accrued" => $cInterestAccrued
                            ]; 
                            $fields_json = json_encode($fields);
                            MortgagePayoutsTable::query()
                            ->where("mortgage_id", $row->mortgage_id)
                            ->where("payout_id", $payoutId)
                            ->update($fields);
                            //to log above $fields in json
                            $this->logger->debug('PayoutBO->processPayout - Updated payout amount and interest accrued',[$row->mortgage_id,$payoutId,$fields_json]);

                        }
                        

                        $lastPaymentId = MortgagePaymentsTable::where('mortgage_id', $row->mortgage_id)->where('payment_id', '<', 10000)->max('payment_id');

                        $queryPayout = new MortgagePaymentsTable;
                        $queryPayout->payment_id           = $lastPaymentId + 1;
                        $queryPayout->mortgage_id          = $row->mortgage_id;
                        $queryPayout->original_date        = $paymentReceivedDate;
                        $queryPayout->processing_date      = $paymentReceivedDate;
                        $queryPayout->period_date          = $paymentReceivedDate;
                        $queryPayout->pmt_amt              = $cPayoutAmount;
                        $queryPayout->is_nsf               = 'no';
                        $queryPayout->nsf_id               = '0';
                        $queryPayout->is_post_dated_cheque = 'no';
                        $queryPayout->is_processed         = 'yes';
                        $queryPayout->is_add_on_fee        = 'no';
                        $queryPayout->comment              = 'Payout';
                        $queryPayout->is_payout            = 'yes';
                        $queryPayout->payout_id            = $payoutId;
                        $queryPayout->created_by           = $userId;
                        $queryPayout->save();                   
                        $i++;

                        $cPayoutAmountTotal += $cPayoutAmount;
                        $lastMortgageId = $row->mortgage_id;

                        $fields = [
                            'payout_at' => $paymentReceivedDate,
                        ];
                        
                        MortgageTable::query()
                        ->where('mortgage_id', $row->mortgage_id)
                        ->update($fields);

                    }

                    // Adjustments for last piece
                    $adjust = $cPayoutAmountTotal - $payoutAmount;

                    // Update last piece mortgage_payments_table payout amount to match the total payout amount
                    DB::table('mortgage_payments_table')
                        ->where('mortgage_id', $lastMortgageId)
                        ->where('payout_id', $payoutId)
                        ->update([
                            'pmt_amt' => DB::raw("pmt_amt - $adjust")
                        ]);

                    // Update last piece mortgage_payouts_table payout amount and misc fee to match the total payout amount
                    DB::table('mortgage_payouts_table')
                        ->where('mortgage_id', $lastMortgageId)
                        ->where('payout_id', $payoutId)
                        ->update([
                            'payout_amount' => DB::raw("payout_amount - $adjust"),
                            'misc_fee' => DB::raw("misc_fee - $adjust")
                        ]);

                    $this->logger->debug('PayoutBO->processPayout - Process Payout ab_loan Successful',[$mortgageId,$payoutId,$queryPayout->payment_id,$queryPayout->mortgage_id]);

                }

                $payoutApproval->processed_at = new DateTime();
                $payoutApproval->processed_by = Auth::user()->user_id ?? null;
                $payoutApproval->save();

                $this->db->commit();
                return true;

            } catch(\Throwable $e) {
                $this->logger->error('PayoutBO->processPayout', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            }
    
            $this->db->rollback();
            return false;

        } else {
            $this->logger->warning('PayoutBO->processPayout - Period locked',[$id]);
            return false;
        }     
        
        return false;
    }

    public function cancelPayout($id, $mortgageId, $payoutId, $comment) {

        $userId = Auth::user()->user_id;

        $this->logger->debug('PayoutBO->cancelPayout',[$id,$mortgageId,$payoutId,$userId,$comment]);

        $mortgage = MortgageTable::find($mortgageId);
        if(!$mortgage) {
            $this->logger->error('PayoutBO->cancelPayout - Could not find mortgage', [$mortgageId]);
            return false;
        }
        $abLoan = $mortgage->ab_loan;
        if($abLoan == 'm_inv'){
            $cInvCard = MortgageTable::query()
                ->where("is_deleted", 'no')
                ->where("ab_loan", 'c_inv')
                ->where("parent", $mortgageId)
                ->get();
            $cInvCardCount = count($cInvCard);
        }

        $this->db->beginTransaction();

        try {
            $query = "select * from mortgage_payments_table pmt, mortgage_payouts_table pout 
                       where pmt.mortgage_id = pout.mortgage_id
                         and pmt.payout_id = pout.payout_id
                         and pmt.mortgage_id = ?
                         and pmt.pap_file_payment_id is null";
            $res = $this->db->select($query,[$mortgageId]);

            if(count($res) == 0) {
                
                $data = PayoutPayment::query()
                ->where("mortgage_id", $mortgageId)
                ->where("payout_id", $payoutId)
                ->get();
                
                if($data) {

                    $papFileBO = new PapFileBO($this->logger, $this->db);
                    if((new DateTime())->format('H') >= $this->cutoffTime) {
                        $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 2);
                    } else {
                        $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 1);
                    }
                    $nextBusinessDay->setTime(0, 0);

                    foreach($data as $key => $value) {
                        $this->logger->info('PayoutBO->cancelPayout - Update mortgage_payments_table',[$id,$value->processing_date,$value->payment_id,$value->flag]);

                        if(new DateTime($value->processing_date) < $nextBusinessDay) {
                            //note
                            $notesBO = new NotesBO($this->logger, $this->db);
                            $note = (new DateTime())->format('M j, Y, g:ia') . ' - Payout Canceled: payment due in ' . (new DateTime($value->processing_date))->format('m/d/Y') . ' could not be processed automatically, it needs to be processed manually.';

                            $followerId = $notesBO->getFollowerUpByMortgageId($value->mortgage_id);

                            $notesBO->new(
                                $mortgage->application_id,
                                $value->mortgage_id,
                                59,
                                $note,
                                $followerId,
                                'no',
                                $followerId
                            );

                            $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                            $this->db->delete('mortgage_payments_table', $conditions);

                        } else {
                            $userId = Auth::user()->user_id ?? null; 
                            $fields     = ['flag' => $value->flag, 'pmt_amt' => $value->payment_amount, 'updated_by' => $userId];
                            $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                            $this->db->update('mortgage_payments_table', $fields, $conditions);

                            if($abLoan == 'm_inv' && $cInvCardCount >= 2) {
                                foreach($cInvCard as $cKey => $row) {
                                    //$this->logger->info('PayoutBO->cancelPayout - Update mortgage_payments_table for c_inv',[$id,$row->mortgage_id,$value->payment_id,$value->flag]);
                                    $fields = ['flag' => $value->flag, 'pmt_amt' => $row->monthly_pmt, 'updated_by' => $userId];
                                    $conditions = ['mortgage_id' => $row->mortgage_id, 'payment_id' => $value->payment_id];
                                    $this->db->update('mortgage_payments_table', $fields, $conditions);
                                }
                            }                   
                        }
                    }

                    $fields = [
                        "status"        => 'C',
                        "canceled_by"   => $userId,
                        "canceled_at"   => new DateTime(),
                        "cancel_reason" => $comment
                    ];                
                    PayoutApproval::query()
                    ->where("id", $id)
                    ->update($fields);

                    $this->db->commit();
                    return true;
                }
            } else {
                $this->logger->debug('PayoutBO->cancelPayout - Payout could not be canceled',[$id]);
            }

        } catch(\Throwable $e) {
            $this->logger->error('PayoutBO->cancelPayout', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }

        $this->db->rollback();
        return false;
    }

    public function getCancelPayout($startDate, $endDate) {
        
        $startDeleted = new DateTime();
        $endDeleted   = new DateTime();
        $startDeleted->setTime(0,0,0);
        $endDeleted->setTime(23,59,59);
        
        $query = "select mt.application_id, mt.mortgage_id, mpt.payout_id, mt.mortgage_code,
                         fn_GetSpouse1NameByApplicationID(mt.application_id) name,
                         mpt.payout_date, mpt.last_balance, mpt.payout_amount, mpt.per_diem,
                         mt.current_balance, mpt.payout_reason, pa.id, pa.canceled_at, pa.status,
                         pa.processed_at
                    from mortgage_payouts_table mpt
              inner join mortgage_table mt on mpt.mortgage_id = mt.mortgage_id
              inner join payout_approval pa on mt.mortgage_id = pa.mortgage_id and mpt.payout_id = pa.payout_id
                   where pa.category_id = 515
                     and ((mpt.payout_date between ? and ? and pa.canceled_at is null)
                         or (pa.canceled_at between ? and ?))
                     and pa.deleted_at is null
                order by pa.canceled_at desc, mpt.payout_date asc";
        $resProcess = $this->db->select($query,[$startDate,$endDate,$startDeleted,$endDeleted]);

        $payouts = array();
        foreach($resProcess as $key => $value) {

            $status = '';
            if($value->status == 'C') {
                $status = 'Canceled';
            } elseif($value->status == 'R') {
                $status = 'Rejected';
            } elseif(!is_null($value->processed_at)) {
                $status = 'Completed';
            }

            $query = "select * from mortgage_payments_table pmt, mortgage_payouts_table pout 
                       where pmt.mortgage_id = pout.mortgage_id 
                         and pmt.payout_id = pout.payout_id 
                         and pmt.mortgage_id = ?";
            $res = $this->db->select($query,[$value->mortgage_id]);

            if(count($res) == 0) {
                $payouts[] = [
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

        return $payouts;
    }

    public function calculatePayout($id, DateTime $paymentReceivedDate) {
        $paymentReceivedDate->setTimezone(new DateTimeZone('UTC'));
        $paymentReceivedDate->setTime(0,0,0);

        $payoutApproval = PayoutApproval::find($id);

        if($payoutApproval) {

            $query = "select mt.application_id, mpt.mortgage_id, mpt.payout_id,
                             mpt.payout_date, mpt.payout_amount, mpt.per_diem, mpt.interest_accrual_date
                        from mortgage_payouts_table mpt
                  inner join mortgage_table mt on mpt.mortgage_id = mt.mortgage_id
                       where mpt.mortgage_id = ?
                         and mpt.payout_id = ?";
            $resProcess = $this->db->select($query,[$payoutApproval->mortgage_id, $payoutApproval->payout_id]);

            $payoutValue = array();
            if(count($resProcess) > 0) {
                $perDiemDays = 0;
                $interestAccrualDate = new DateTime($resProcess[0]->interest_accrual_date);
                $interestAccrualDate->setTimezone(new DateTimeZone('UTC'));
                $interestAccrualDate->setTime(0,0,0);

                if($paymentReceivedDate > $interestAccrualDate) {
                    $interval = $paymentReceivedDate->diff($interestAccrualDate);
                    $perDiemDays = $interval->days;
                }

                if ($perDiemDays > 0) {
                    $totalPayoutAmount = $resProcess[0]->payout_amount + ($resProcess[0]->per_diem * $perDiemDays);
                } else {
                    $totalPayoutAmount = $resProcess[0]->payout_amount;
                }

                $payoutValue = [
                    "application_id"  => $resProcess[0]->application_id,
                    "mortgage_id"     => $resProcess[0]->mortgage_id,
                    "payout_id"       => $resProcess[0]->payout_id,
                    "per_diem_days"   => $perDiemDays,
                    "total_payout_amount" => round($totalPayoutAmount,2)
                ];
            }

            return $payoutValue;
        }

        return false;
    }

    public function rejectPayout($id, $rejectReason) {

        $userId = Auth::user()->user_id;

        $gm = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('deleted', 0)
        ->get();

        foreach($gm as $key => $value) {
            $fields = array();

            if($value->group_id == 16) {
                $this->logger->info('PayoutBO->rejectPayout - Admin Reject',[$id, $userId]);

                $fields = [
                    "admin_approved_by" => $userId,
                    "admin_approved_at" => new DateTime(),
                    "admin_status"      => 'R',
                    "admin_comment"     => $rejectReason,

                ];
            } elseif($value->group_id == 17) {
                $this->logger->info('PayoutBO->rejectPayout - Broker Reject',[$id, $userId]);

                $fields = [
                    "broker_approved_by" => $userId,
                    "broker_approved_at" => new DateTime(),
                    "broker_status"      => 'R',
                    "broker_comment"     => $rejectReason
                ];
            } elseif($value->group_id == 9) {
                $this->logger->info('PayoutBO->rejectPayout - Accounting Reject',[$id, $userId]);

                $fields = [
                    "admin_approved_by" => null,
                    "admin_approved_at" => null,
                    "admin_status"      => 'P',
                    "status"            => 'R',
                    "canceled_at"       => new DateTime(),
                    "canceled_by"       => $userId,
                    "cancel_reason"     => $rejectReason
                ];
            }

            if(count($fields) > 0) {
                PayoutApproval::query()
                ->where("id", $id)
                ->update($fields);
                
                $utilities = Factory::create('Utilities\\Notification', $this->logger, $this->db);
                $utilities->payoutRejected($id,$userId,$rejectReason);                
                
                return true;
            }
        } 
        
        return false;
    }

    public function sendLawyer($id) {
        $payoutApproval = PayoutApproval::find($id);

        if(
            $payoutApproval->admin_status == 'A' &&
            $payoutApproval->broker_status == 'A' &&
            is_null($payoutApproval->lawyer_status)
        ) {
            $payoutApproval->lawyer_status = 'S';
            $payoutApproval->lawyer_at = new DateTime();
            $payoutApproval->lawyer_by = Auth::user()->user_id;
            $payoutApproval->save();

            return true;
        }

        return false;
    }
}
