<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\IDB;
use Illuminate\Support\Facades\DB;
use App\Amur\Bean\ILogger;
use App\Amur\Bean\Transaction;
use App\Amur\Bean\TransactionDetail;
use App\Amur\Utilities\Loan;
use App\Amur\Utilities\Notification;
use App\Models\PapBank;
use App\Models\CategoryValue;
use App\Models\MortgagePaymentsHistory;
use App\Models\MortgagePaymentsTable;
use App\Models\MortgageTable;
use App\Models\PapTransaction;
use App\Models\PapTransactionDetail;
use App\Models\MortgageRenewalsTable;
use App\Models\PapInstitution;
use DateInterval;
use DateTime;
use App\Amur\Utilities\Utils;

class PapBO {

    private $logger;
    private $db;
    private $cutoffTime = 14;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getPapBankInfo($mortgageId) {
        $query = "select a.id, a.transit, b.name, b.code, a.account, a.validated_at
                    from pap_bank a
                    join pap_institution b on a.institution_id = b.id
                   where a.mortgage_id = ?
                     and a.deleted_at is null
                order by a.id desc";
        $res = $this->db->select($query,[$mortgageId]);

        $status = 'Active';
        $data = [];
        foreach($res as $key => $value) {
            $data[] = [
                'id' => $value->id,
                'transit' => $value->transit,
                'institutionCode' => $value->code,
                'institutionName' => $value->name,
                'accountRedacted' => '***' . substr($value->account,2,4),
                'status' => is_null($value->validated_at) ? '' : $status
            ];

            if($status == 'Active') {
                $status = 'Inactive';
            }
        }

        return $data;
    }

    public function getUpdates(DateTime $startDate, DateTime $endDate, $companyId) {
        $updates = $this->getUpdateTransactions($startDate, $endDate, $companyId);
        
        return $updates;
    }

    public function getUpdateTransactions($startDate, $endDate, $companyId) {

        $categories = $this->getPapCategoriesById();

        $companies = implode(',',self::getPapCompanies());
        if($companyId > 0) {
            $condition = " and ((a.processed_at is null and b.company_id in ($companies)) or a.status is not null or (date(a.processed_at) between ? and ? and b.company_id = $companyId))";
        } else {
            $condition = " and b.company_id in ($companies) and (a.processed_at is null or (date(a.processed_at) between ? and ?) or a.status is not null)";
        }

        $query = "select a.id, a.mortgage_id, b.application_id, b.mortgage_code,
                         a.deferral_advance, a.processed_at, a.status, a.category_id,
                         fn_GetSpouse1NameByApplicationID(b.application_id) client_name,
                         company_id, concat(c.user_fname, ' ', c.user_lname) created_by, a.created_at
                    from pap_transaction a
                    join mortgage_table b on a.mortgage_id = b.mortgage_id
               left join users_table c on a.created_by = c.user_id
                   where b.is_deleted = 'no'
                     and a.deleted_at is null
                     and a.category_id in (511,512,534)
                         $condition
                order by mortgage_code";
        $res = $this->db->select($query,[$startDate->format('Y-m-d'),$endDate->format('Y-m-d')]);

        $transactions = array();
        $status = null;
        foreach($res as $value) {
            if($value->status == 'R') {
                $status = 'Rejected';
            } elseif($value->status == 'E') {
                $status = 'Error';
            } else {
                $status = is_null($value->processed_at) ? 'Pending' : 'Done';
            }

            $transaction = new Transaction();
            $transaction->id = $value->id;
            $transaction->companyId = $value->company_id;
            $transaction->applicationId = $value->application_id;
            $transaction->mortgageId = $value->mortgage_id;
            $transaction->mortgageCode = $value->mortgage_code;
            $transaction->clientName = $value->client_name;
            $transaction->requestType = $categories[$value->category_id];
            $transaction->requestTypeId = $value->category_id;
            $transaction->createdBy = $value->created_by;
            $transaction->createdAt = new DateTime($value->created_at);
            $transaction->status = $status;
            $transaction->isDeferral = $value->deferral_advance == 'deferral' ? true : false;
            $transaction->isAdvance = !$transaction->isDeferral;

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    public function getTransactions(DateTime $startDate, DateTime $endDate, $companyId, $category) {

        $companies = implode(',',self::getPapCompanies());

        if($companyId > 0) {
            $condition1 = " and ((a.validated_at is null and b.company_id in ($companies)) or a.status = 'R' or (date(a.validated_at) between ? and ? and b.company_id = $companyId))";
            $condition2 = " and ((a.processed_at is null and b.company_id in ($companies)) or a.status = 'R' or (date(a.processed_at) between ? and ? and b.company_id = $companyId))";
        } else {
            $condition1 = " and b.company_id in ($companies) and (a.validated_at is null or (date(a.validated_at) between ? and ?) or a.status = 'R')";
            $condition2 = " and b.company_id in ($companies) and (a.processed_at is null or (date(a.processed_at) between ? and ?) or a.status = 'R')";
        }

        if($category == 'new') {
            $query = "select a.mortgage_id, b.application_id, b.mortgage_code, a.validated_at, a.status,
                            fn_GetSpouse1NameByApplicationID(b.application_id) client_name, company_id, 'B' request_type, d.folder
                        from pap_bank a
                        join mortgage_table b on a.mortgage_id = b.mortgage_id AND payout_at IS NULL
                        join application_table c on b.application_id = c.application_id
                   left join alpine_companies_table d on c.company = d.id
                    where b.is_deleted = 'no'
                        and a.deleted_at is null
                        and a.category_id = 536
                            $condition1
                    union
                    select a.mortgage_id, b.application_id, b.mortgage_code, a.processed_at, a.status,
                            fn_GetSpouse1NameByApplicationID(b.application_id) client_name, company_id, 'T' request_type, d.folder
                        from pap_transaction a
                        join mortgage_table b on a.mortgage_id = b.mortgage_id AND payout_at IS NULL
                        join application_table c on b.application_id = c.application_id
                   left join alpine_companies_table d on c.company = d.id
                    where b.is_deleted = 'no'
                        and a.deleted_at is null
                        and a.category_id in (509,532)
                            $condition2
                    order by mortgage_code";
            $res = $this->db->select($query,[
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        } else {
            $query = "select a.mortgage_id, b.application_id, b.mortgage_code, a.validated_at, a.status,
                            fn_GetSpouse1NameByApplicationID(b.application_id) client_name, company_id, 'B' request_type, d.folder
                        from pap_bank a
                        join mortgage_table b on a.mortgage_id = b.mortgage_id
                        join application_table c on b.application_id = c.application_id
                   left join alpine_companies_table d on c.company = d.id
                    where b.is_deleted = 'no'
                        and a.deleted_at is null
                        and a.category_id = 510
                            $condition1";
            $res = $this->db->select($query,[
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        }

        $transactions = array();
        foreach($res as $key => $value) {
            if($value->status == 'R') {
                $status = 'Rejected';
            } else if($value->status == 'E') {
                $status = 'Error';
            } else {
                $status = is_null($value->validated_at) ? 'Pending' : 'Done';
            }

            if(isset($transactions[$value->mortgage_id])) {
                $statuses = [
                    'bank' => $value->request_type == 'B' ? $status : $transactions[$value->mortgage_id]->statuses['bank'],
                    'newPap' => $value->request_type == 'T' ? $status : $transactions[$value->mortgage_id]->statuses['newPap']
                ];

                if($transactions[$value->mortgage_id]->status != 'Rejected' && $transactions[$value->mortgage_id]->status != 'Pending') {
                    $transactions[$value->mortgage_id]->status = $status;
                }
                $transactions[$value->mortgage_id]->statuses = $statuses;
            } else {
                $statuses = [
                    'bank' => $value->request_type == 'B' ? $status : null,
                    'newPap' => $value->request_type == 'T' ? $status : null
                ];

                $transaction = new Transaction();
                $transaction->companyId = $value->company_id;
                $transaction->applicationId = $value->application_id;
                $transaction->folder = $value->folder;
                $transaction->mortgageId = $value->mortgage_id;
                $transaction->mortgageCode = $value->mortgage_code;
                $transaction->clientName = $value->client_name;
                $transaction->status = $status;
                $transaction->statuses = $statuses;
                $transactions[$value->mortgage_id] = $transaction;
            }
        }

        $transactions = array_values($transactions);

        return $transactions;
    }

    public function getTransactionDetails($mortgageId, $category) {
        if($category == 'new' || $category == 'update') {
            return $this->getNewPapDetails($mortgageId, $category, 2);
        } else {
            return $this->getOtherTransactionDetails($mortgageId, $category);
        }
    }

    public function getOtherTransactionDetails($mortgageId, $categoryId) {
        $query = "select a.id, b.id detail_id, b.payment_id original_payment_id, a.mortgage_id,
                         c.period_date original_payment_date, c.pmt_amt original_payment_amount,
                         a.processed_at, a.processed_at, b.payment_date, b.payment_amount,
                         c.pap_file_payment_id
                    from pap_transaction a
                    join pap_transaction_detail b on a.id = b.pap_transaction_id
               left join mortgage_payments_table c on a.mortgage_id = c.mortgage_id and b.payment_id = c.payment_id
                   where a.mortgage_id = ?
                     and a.category_id = ?
                     and a.deleted_at is null
                     and a.processed_at is null
                     and a.status is null
                order by b.id, b.payment_date";
        $res = $this->db->select($query, [$mortgageId, $categoryId]);

        $transactionDetails = array();
        if($categoryId == 512) {
            foreach($res as $key => $value) {
                if(is_null($value->pap_file_payment_id)) {
                    if(isset($transactionDetails[$value->original_payment_id])) {
                        $transactionDetails[$value->original_payment_id]->new2ndPaymentDate = new DateTime($value->payment_date);
                        $transactionDetails[$value->original_payment_id]->new2ndPaymentAmount = (float) $value->payment_amount;
                    } else {
                        $transactionDetail = new TransactionDetail();
                        $transactionDetail->originalPaymentDate = new DateTime($value->original_payment_date);
                        $transactionDetail->originalPaymentAmount = (float) $value->original_payment_amount;
                        $transactionDetail->new1stPaymentDate = new DateTime($value->payment_date);
                        $transactionDetail->new1stPaymentAmount = (float) $value->payment_amount;

                        $transactionDetails[$value->original_payment_id] = $transactionDetail;
                    }
                }
            }

        } elseif($categoryId == 534 || $categoryId == 511) {

            foreach($res as $key => $value) {
                if(is_null($value->pap_file_payment_id)) {
                    $transactionDetail = new TransactionDetail();
                    $transactionDetail->id = $value->detail_id;
                    $transactionDetail->originalPaymentDate = new DateTime($value->original_payment_date);
                    $transactionDetail->originalPaymentAmount = (float) $value->original_payment_amount;
                    $transactionDetail->new1stPaymentDate = new DateTime($value->payment_date);
                    $transactionDetail->new1stPaymentAmount = (float) $value->payment_amount;

                    $transactionDetails[] = $transactionDetail;
                }
            }
        }

        $transactionDetails = array_values($transactionDetails);

        return $transactionDetails;
    }

    public function getNewPapDetails($mortgageId, $category, $businessDay) {

        $bankTransaction = array();
        $newPapTransaction = array();

        if($category == 'new') {
            $categoryId = 536;
        } else {
            $categoryId = 510;
        }

        $query = "select a.id, a.status, a.validated_at, b.code institution_code, b.name institution_name, a.transit,
                         a.account, a.payee_name, a.created_at, concat(c.user_fname, ' ', c.user_lname) created_by
                    from pap_bank a
                    join pap_institution b on a.institution_id = b.id
               left join users_table c on a.created_by = c.user_id
                   where a.mortgage_id = ?
                     and a.category_id = ?
                     and a.deleted_at is null
                     and (a.validated_at is null or date(a.validated_at) = date(now()) or a.status = 'R')
                order by validated_at";
        $res = $this->db->select($query, [$mortgageId, $categoryId]);

        if(count($res) > 0) {
            if($res[0]->status == 'R') {
                $status = 'Rejected';
            } else if($res[0]->status == 'E') {
                $status = 'Error';
            } else {
                $status = is_null($res[0]->validated_at) ? 'Pending' : 'Done';
            }

            $bankTransaction = [
                'id' => $res[0]->id,
                'status' => $status,
                'institutionCode' => $res[0]->institution_code,
                'institutionName' => $res[0]->institution_name,
                'transit' => $res[0]->transit,
                'account' => $res[0]->account,
                'payeeName' => $res[0]->payee_name,
                'createdAt' => new DateTime($res[0]->created_at),
                'createdBy' => $res[0]->created_by
            ];
        }

        $query = "select a.id, a.status, a.processed_at, a.created_at, concat(b.user_fname, ' ', b.user_lname) created_by
                    from pap_transaction a
                    join users_table b on a.created_by = b.user_id
                   where a.category_id in (509,532)
                     and a.mortgage_id = ?
                     and a.deleted_at is null
                     and (a.processed_at is null or date(a.processed_at) = date(now()) or a.status = 'R')
                order by a.processed_at ";
        $res = $this->db->select($query, [$mortgageId]);

        if(count($res) > 0) {
            if($res[0]->status == 'R') {
                $status = 'Rejected';
            } else if($res[0]->status == 'E') {
                $status = 'Error';
            } else {
                $status = is_null($res[0]->processed_at) ? 'Pending' : 'Done';
            }

            $newPapTransaction = [
                'id' => $res[0]->id,
                'status' => $status,
                'createdAt' => new DateTime($res[0]->created_at),
                'createdBy' => $res[0]->created_by
            ];

            $papTransactionDetails = PapTransactionDetail::query()
            ->where('pap_transaction_id', $res[0]->id)
            ->orderBy('payment_date')
            ->get();

            $papFileBO = new PapFileBO($this->logger, $this->db);

            $details = array();

            foreach($papTransactionDetails as $key => $value) {

                if ($status !== 'Pending') {

                    $message = '';
                    $isPaid = false;
                    $paymentBeforeNextBusinessDay = false;
                    $validated = false;

                } else {

                    $isPaid = $this->checkDuplicatePayment($mortgageId, $value->payment_date, $value->payment_amount);

                    $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), $businessDay);
                    $nextBusinessDay->setTime(0, 0);

                    $paymentDate = new DateTime($value->payment_date);

                    $paymentBeforeNextBusinessDay = false;
                    
                    if ($paymentDate < $nextBusinessDay) {
                        $paymentBeforeNextBusinessDay = true;
                    }
                    
                    $message = '';

                    if ($isPaid) {
                        $message = 'Duplicate Payment';
                    }

                    if ($paymentBeforeNextBusinessDay) {
                        if ($message != '') {
                            $message .= ' - Past due payment';
                        }else {
                            $message = 'Past due payment';
                        }
                        if ($value->payment_status == 'Payment Collected' || $value->payment_status == 'Payment not Collected') {
                            $message .= ': ' . $value->payment_status;
                            if (!is_null($value->new_payment_date)) {
                                $message .= ' - New Payment Date: ' . (new DateTime($value->new_payment_date))->format('m/d/Y');
                            }
                        }
                    }

                    if ($value->payment_status == 'Payment Collected' || $value->payment_status == 'Payment not Collected') {
                        $validated = true;
                    }else {
                        $validated = false;
                    }
                }                

                $details[] = [
                    'id' => $value->id,
                    'paymentDate' => new DateTime($value->payment_date),
                    'paymentAmount' => (float) $value->payment_amount,
                    'prePayment' => $value->prepayment == 'yes' ? true : false,
                    'message' => $message,
                    'isPaid' => $isPaid,
                    'paymentBeforeNextBusinessDay' => $paymentBeforeNextBusinessDay,
                    'validated' => $validated
                ];
            }

            $paymentsDetail = $this->checkPayments($mortgageId, $status);           
            
            $newPapTransaction['details'] = $details;
            $newPapTransaction['paymentsDetail'] = $paymentsDetail;
        }

        return [
            'bankTransaction' => $bankTransaction,
            'newPapTransaction' => $newPapTransaction
        ];
    }

    public function rejectBankInfo($id, $rejectReason) {
        $this->db->beginTransaction();

        try {
            $papBank = PapBank::find($id);

            if(!$papBank) {
                $this->logger->error('PapBO->rejectBankInfo - Could not find transaction', [$id]);
                return false;
            }

            if(!is_null($papBank->validated_at)) {
                $this->logger->error('PapBO->rejectBankInfo - Transaction already processed', [$id]);
                return false;
            }

            $mortgage = MortgageTable::find($papBank->mortgage_id);
            if(!$mortgage) {
                $this->logger->error('PapBO->rejectBankInfo - Could not find mortgage', [$id]);
                return false;
            }

            $papBank->status = 'R';
            $papBank->comment = $rejectReason;
            $papBank->save();

            Notification::bankInfoRejected($papBank->created_by, $mortgage->mortgage_code, $rejectReason);

            $this->db->commit();
            return true;

        } catch(\Throwable $e) {
            $this->logger->error('PapBO->rejectBankInfo', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }

        $this->db->rollback();
        return false;
    }

    public function rejectTransaction($id, $rejectReason) {
        $this->db->beginTransaction();

        try {
            $papTransaction = PapTransaction::find($id);

            if(!$papTransaction) {
                $this->logger->error('PapBO->rejectTransaction - Could not find transaction', [$id]);
                return false;
            }

            if(!is_null($papTransaction->processed_at)) {
                $this->logger->error('PapBO->rejectTransaction - Transaction already processed', [$id]);
                return false;
            }

            $mortgage = MortgageTable::find($papTransaction->mortgage_id);
            if(!$mortgage) {
                $this->logger->error('PapBO->rejectTransaction - Could not find mortgage', [$id]);
                return false;
            }

            $papTransaction->status = 'R';
            $papTransaction->comment = $rejectReason;
            $papTransaction->save();

            $category = $this->getPapCategoryById($papTransaction->category_id);

            Notification::transactionRejected($papTransaction->created_by, $category, $mortgage->mortgage_code, $rejectReason);

            $this->db->commit();
            return true;

        } catch(\Throwable $e) {
            $this->logger->error('PapBO->rejectTransaction', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }

        $this->db->rollback();
        return false;
    }

    public function processTransaction($id, $origin) {

        $this->logger->debug('PapBO->processTransaction',[$id, $origin]);

        $this->db->beginTransaction();

        try {
            $papTransaction = PapTransaction::find($id);

            if(!$papTransaction) {
                $this->logger->error('PapBO->processTransaction - Could not find transaction', [$id]);
                return false;
            }

            if(!is_null($papTransaction->processed_at) && $papTransaction->category_id != 513) {
                $this->logger->error('PapBO->processTransaction - Transaction already processed', [$id]);
                return false;
            }

            $mortgage = MortgageTable::find($papTransaction->mortgage_id);
            if(!$mortgage) {
                $this->logger->error('PapBO->processTransaction - Could not find mortgage', [$id]);
                return false;
            }

            $papTransactionDetails = PapTransactionDetail::query()
            ->where('pap_transaction_id', $papTransaction->id)
            ->orderBy('id')
            ->get();

            if(!$papTransactionDetails) {
                $this->logger->error('PapBO->processTransaction - Missing PAP detail', [$id]);
                return false;
            }

            $processed = false;
            switch($papTransaction->category_id) {
                //New PAP
                case 509:
                    $processed = $this->newPap($papTransaction, $papTransactionDetails, $mortgage, $origin);
                    break;

                case 532:
                    $processed = $this->newPap($papTransaction, $papTransactionDetails, $mortgage, $origin);
                    break;

                //PAP Update
                case 511:
                    $processed = $this->dayChange($papTransaction, $papTransactionDetails, $mortgage);
                    break;
                    
                case 512:
                    $processed = $this->splitPayment($papTransaction, $papTransactionDetails, $mortgage);
                    break;

                case 513:
                    $processed = $this->nsfRetake($papTransaction, $papTransactionDetails, $mortgage);
                    break;

                case 534:
                    $processed = $this->paymentAmount($papTransaction, $papTransactionDetails, $mortgage);
                    break;

                //Renewal
                case 520:
                    $processed = $this->renewal($papTransaction, $papTransactionDetails, $mortgage);
                    break;
            }

            if(is_null($processed)) {
                //no action
                return true;

            } else if($processed === true) {
                $userId = Auth::user()->user_id ?? $papTransaction->created_by;

                $papTransaction->processed_at = new DateTime();
                $papTransaction->processed_by = $userId;
                $papTransaction->save();

                $this->db->commit();
                return true;

            } else {
                $papTransaction->status = 'E';
                $papTransaction->comment = 'Transaction could not be processed!';
                $papTransaction->save();

                $this->db->commit();
                return true;
            }
        } catch(\Throwable $e) {
            $this->logger->error('PapBO->processTransaction', [$e->getMessage(),json_encode($e->getTraceAsString())]);
        }

        $this->db->rollback();
        return false;
    }

    public function getNsfFee($mortgageId) {
        $mortgageTable = MortgageTable::find($mortgageId);

        if($mortgageTable) {
            return $mortgageTable->nsf_fee;
        } else {
            return 0;
        }
    }

    public function getPaymentById($mortgageId, $paymentId) {
        $mortgagePaymentsTable = MortgagePaymentsTable::query()
        ->where('mortgage_id', $mortgageId)
        ->where('payment_id', $paymentId)
        ->first();

        if($mortgagePaymentsTable) return $mortgagePaymentsTable;

        return false;
    }

    public function newPap($papTransaction, $papTransactionDetails, $mortgage, $origin) {
        $lender = Loan::getFutureLender($mortgage->mortgage_id);

        if(
            in_array($lender['companyId'], self::getPapCompanies()) ||
            in_array($mortgage->company_id, self::getPapCompanies())
        ) {
            if($origin == 'web') {
                return $this->newPapMIC($papTransaction, $papTransactionDetails, $mortgage);
            } else {
                return null;
            }
        } else {
            return $this->newPapPrivateInvestor($papTransaction, $papTransactionDetails, $mortgage);
        }
    }

    public function newPapPrivateInvestor($papTransaction, $papTransactionDetails, $mortgage) {
        $this->logger->info('PapBO->newPapPrivateInvestor',[$papTransaction->id]);

        $totalPrePaymentCount = 0;
        $totalPrePaymentAmount = 0;
        foreach($papTransactionDetails as $key => $papTransactionDetail) {

            if($papTransactionDetail->prepayment == 'yes') {
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    0,
                    false,
                    '',
                    'Zero payment'
                );

                $totalPrePaymentCount++;
                $totalPrePaymentAmount += $papTransactionDetail->payment_amount;
                
            } else {
                //payment
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    $papTransactionDetail->payment_amount,
                    false,
                    '',
                    'Post'
                );
            }

            $papTransactionDetail->new_payment_id = $newPaymentId;
            $papTransactionDetail->holdback = 'no';
            $papTransactionDetail->save();
        }

        if($totalPrePaymentAmount > 0) {
            $mortgagePaymentsTable = $this->getPaymentById($papTransaction->mortgage_id, 1);

            if($mortgagePaymentsTable) {
                $paymentDate = new DateTime($mortgagePaymentsTable->processing_date);
                $paymentDate->sub(new DateInterval('P1D'));

                if(Loan::isLockedDate($mortgage->company_id, $paymentDate)) {
                    $paymentDate = $this->getFundingDate($papTransaction->mortgage_id);

                    if($paymentDate === false) {
                        $this->logger->error('PapBO->newPapPrivateInvestor - Funding date not found',[$papTransaction->mortgage_id]);
                        return false;
                    }
                }

                $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $paymentDate,
                    $totalPrePaymentAmount,
                    true,
                    $totalPrePaymentCount . ' Months Pre-Payment',
                    'N/A'
                );
            } else {
                $this->logger->error('PapBo->newPapPrivateInvestor - Could not get first payment',[$papTransaction->mortgage_id]);
                return false;
            }
        }

        return true;
    }

    public function newPapMIC($papTransaction, $papTransactionDetails, $mortgage) {

        $this->logger->info('PapBO->newPapMIC',[$papTransaction->id]);

        $papFileBO = new PapFileBO($this->logger, $this->db);
        $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 2);
        $nextBusinessDay->setTime(0, 0);
        
        $totalPrePaymentCount = 0;
        $totalPrePaymentAmount = 0;
        $totalAPrePaymentAmount = 0;
        $totalBPrePaymentAmount = 0;
        $totalCPrePaymentAmount = 0;

        //get mortgages where parent is the current mortgage
        $childrenMortgages = MortgageTable::query()
            ->where('parent', $papTransaction->mortgage_id)
            ->orderBy('mortgage_id', 'asc')
            ->get();  
            
        $savedQuoteId = null; 
        $saleInvestor = null; 
        if ($childrenMortgages->isNotEmpty()) {
            $this->logger->info('PapBO->newPapMIC - Children Mortgages', [$childrenMortgages]);

            $savedQuoteId = DB::table('saved_quote_table')
                ->where('mortgage_id', $mortgage->transfer_id)
                ->value('saved_quote_id');  
            $saleInvestor = DB::table('sale_investor_table')
                ->where('investor_id', 1971)
                ->where('fm_committed', 'Yes')
                ->where('saved_quote_id', $savedQuoteId)
                ->first();
        }

        foreach($papTransactionDetails as $papTransactionDetail) {

            $paymentDate = new DateTime($papTransactionDetail->payment_date);

            $holdback = 'no';
            if($papTransactionDetail->prepayment == 'yes') {
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    0,
                    false,
                    '',
                    'Zero payment'
                );

                if ($childrenMortgages->isNotEmpty()) {
                    if ($saleInvestor !== null) {
                        $count = 0;
                        foreach ($childrenMortgages as $childMortgage) {
                            if ($count == 0){
                                $totalAPrePaymentAmount += $saleInvestor->ap_payment;
                            } elseif($count == 1) {
                                $totalBPrePaymentAmount += $saleInvestor->bp_payment;
                            } elseif($count == 2) {
                                $totalCPrePaymentAmount += $saleInvestor->cp_payment;
                            }
                            $this->createMortgagePayment(
                                $childMortgage->mortgage_id,
                                $papTransactionDetail->payment_date,
                                0,
                                false,
                                '',
                                'Zero payment'
                            );
                            $count++;
                        }
                    }
                }

                $totalPrePaymentCount++;
                $totalPrePaymentAmount += $papTransactionDetail->payment_amount;

            } elseif($paymentDate < $nextBusinessDay) {
                
                $holdback = 'yes';

                $fundingDate = $this->getFundingDate($papTransaction->mortgage_id);

                if($fundingDate === false) {
                    $this->logger->error('PapBO->newPapMic - Funding date not found',$papTransaction->mortgage_id);
                    return false;
                }

                if($papTransactionDetail->payment_status == 'Payment Collected') {
                    $comment = 'Collected at the funding time';
                    $paymentAmount = $papTransactionDetail->payment_amount;
                    $flag = 'N/A';
                    $newPayment = false;
                } else {
                    $comment = 'Not Collected: Past Due Payment';
                    $paymentAmount = 0;
                    $flag = 'N/A';
                    $newPayment = true;
                }

                $newPaymentId = $this->createMortgagePayment($papTransaction->mortgage_id, $fundingDate, $paymentAmount, true, $comment, $flag);

                if($childrenMortgages->isNotEmpty()) {

                    if($saleInvestor !== null) {
                        $count = 0;
                        foreach ($childrenMortgages as $childMortgage) {

                            if ($comment == 'Not Collected: Past Due Payment') {
                                $piecePayment = 0;
                            } else {
                                if ($count == 0){
                                    $piecePayment = $saleInvestor->ap_payment;
                                } elseif($count == 1) {
                                    $piecePayment = $saleInvestor->bp_payment;
                                } elseif($count == 2) {
                                    $piecePayment = $saleInvestor->cp_payment;
                                }
                            }
                            
                            $this->createMortgagePayment($childMortgage->mortgage_id, $fundingDate, $piecePayment, true, $comment, $flag);
                            $count++;
                        }
                    }
                }

                // New Payment
                if($newPayment) {
                    $comment = (new DateTime($papTransactionDetail->payment_date))->format('F/Y');
                    $paymentAmount = $papTransactionDetail->payment_amount;
                    $flag = 'Pre2';
                    $fundingDate = $papTransactionDetail->new_payment_date;

                    $newPaymentId = $this->createMortgagePayment($papTransaction->mortgage_id, $fundingDate, $paymentAmount, false, $comment, $flag);

                    if($childrenMortgages->isNotEmpty()) {

                        if($saleInvestor !== null) {

                            $count = 0;
                            foreach ($childrenMortgages as $childMortgage) {
                                if($count == 0) {
                                    $piecePayment = $saleInvestor->ap_payment;
                                } elseif($count == 1) {
                                    $piecePayment = $saleInvestor->bp_payment;
                                } elseif($count == 2) {
                                    $piecePayment = $saleInvestor->cp_payment;
                                }
                                
                                $this->createMortgagePayment($childMortgage->mortgage_id, $fundingDate, $piecePayment, false, $comment, $flag);
                                $count++;
                            }
                        }
                    }
                }

            } else {
                //payment
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    $papTransactionDetail->payment_amount,
                    false,
                    '',
                    'Pre2'
                );

                if ($childrenMortgages->isNotEmpty()) {
                    if ($saleInvestor !== null) {   
                        $count = 0;
                        foreach ($childrenMortgages as $childMortgage) {
                            if ($count == 0){
                                $piecePayment = $saleInvestor->ap_payment;
                            }elseif($count == 1){
                                $piecePayment = $saleInvestor->bp_payment;
                            }elseif($count == 2){
                                $piecePayment = $saleInvestor->cp_payment;
                            }
                            $this->createMortgagePayment(
                                $childMortgage->mortgage_id,
                                $papTransactionDetail->payment_date,
                                $piecePayment,
                                false,
                                '',
                                'Pre2'
                            );
                            $count++;
                        }
                    }
                }
            }

            $papTransactionDetail->new_payment_id = $newPaymentId;
            $papTransactionDetail->holdback = $holdback;
            $papTransactionDetail->save();
        }

        if($totalPrePaymentAmount > 0) {
            $mortgagePaymentsTable = $this->getPaymentById($papTransaction->mortgage_id, 1);

            if($mortgagePaymentsTable) {
                $paymentDate = new DateTime($mortgagePaymentsTable->processing_date);
                $paymentDate->sub(new DateInterval('P1D'));

                if(Loan::isLockedDate($mortgage->company_id, $paymentDate)) {
                    $paymentDate = $this->getFundingDate($papTransaction->mortgage_id);

                    if($paymentDate === false) {
                        $this->logger->error('PapBO->newPapMic - Funding date not found',$papTransaction->mortgage_id);
                        return false;
                    }
                }

                $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $paymentDate,
                    $totalPrePaymentAmount,
                    true,
                    $totalPrePaymentCount . ' Months Pre-Payment',
                    'N/A'
                );

                if($childrenMortgages->isNotEmpty() && $saleInvestor !== null) {   
                    $count = 0;
                    foreach($childrenMortgages as $childMortgage) {
                        if($count == 0) {
                            $piecePayment = $totalAPrePaymentAmount;
                        } elseif($count == 1) {
                            $piecePayment = $totalBPrePaymentAmount;
                        } elseif($count == 2) {
                            $piecePayment = $totalCPrePaymentAmount;
                        }

                        $this->createMortgagePayment(
                            $childMortgage->mortgage_id,
                            $paymentDate,
                            $piecePayment,
                            true,
                            $totalPrePaymentCount . ' Months Pre-Payment',
                            'N/A'
                        );
                        $count++;
                    }
                }
            } else {
                $this->logger->error('PapBo->newPapMic - Could not get first payment',[$papTransaction->mortgage_id]);
                return false;
            }
        }

        return true;
    }

    public function dayChange($papTransaction, $papTransactionDetails, $mortgage) {

        //ABL, create payment for the children mortgages
        $cInvCard = MortgageTable::query()
            ->where("is_deleted", 'no')
            ->where("ab_loan", 'c_inv')
            ->where("parent", $papTransaction->mortgage_id)
            ->get();
        $cInvCardCount = count($cInvCard);
        $userId = Auth::user()->user_id ?? null;
            
        foreach($papTransactionDetails as $key => $papTransactionDetail) {
            $mortgagePaymentsTable = MortgagePaymentsTable::query()
            ->where('mortgage_id', $papTransaction->mortgage_id)
            ->where('payment_id', $papTransactionDetail->payment_id)
            ->first();

            if(is_null($mortgagePaymentsTable->pap_file_payment_id)) {
                $this->savePaymentHistory($mortgagePaymentsTable, '526');

                $mortgagePaymentsTable->processing_date = $papTransactionDetail->payment_date;
                $mortgagePaymentsTable->period_date = $papTransactionDetail->payment_date;
                $mortgagePaymentsTable->updated_by = $userId;
                $mortgagePaymentsTable->save();

                if ($cInvCardCount > 0) {
                    foreach ($cInvCard as $cInv) {
                        $cMortgagePaymentsTable = MortgagePaymentsTable::query()
                            ->where('mortgage_id', $cInv->mortgage_id)
                            ->where('payment_id', $papTransactionDetail->payment_id)
                            ->first();
                
                        if ($cMortgagePaymentsTable) {
                            $cMortgagePaymentsTable->processing_date = $papTransactionDetail->payment_date;
                            $cMortgagePaymentsTable->period_date = $papTransactionDetail->payment_date;
                            $cMortgagePaymentsTable->updated_by = $userId;
                            $cMortgagePaymentsTable->save();
                        } else {
                            $this->logger->error("No record found for mortgage_id: {$cInv->mortgage_id} and payment_id: {$papTransactionDetail->payment_id}");
                        }
                    }
                }                

                $papTransactionDetail->new_payment_id = $papTransactionDetail->payment_id;
                $papTransactionDetail->save();
            } else {
                $this->logger->warning(
                    'PapBO->dayChange - Could not process it - Payment already sent to the bank',
                    [$papTransaction->mortgage_id, $papTransactionDetail->payment_id]);
            }
        }

        return true;
    }

    public function paymentAmount($papTransaction, $papTransactionDetails, $mortgage) {

        foreach($papTransactionDetails as $key => $papTransactionDetail) {
            $mortgagePaymentsTable = MortgagePaymentsTable::query()
            ->where('mortgage_id', $papTransaction->mortgage_id)
            ->where('payment_id', $papTransactionDetail->payment_id)
            ->first();

            if(is_null($mortgagePaymentsTable->pap_file_payment_id)) {
                $this->savePaymentHistory($mortgagePaymentsTable, '535');

                $mortgagePaymentsTable->pmt_amt = $papTransactionDetail->payment_amount;
                $mortgagePaymentsTable->updated_by = Auth::user()->user_id ?? null;
                $mortgagePaymentsTable->save();

                $papTransactionDetail->new_payment_id = $papTransactionDetail->payment_id;
                $papTransactionDetail->save();
            } else {
                $this->logger->warning(
                    'PapBO->paymentAmount - Could not process it - Payment already sent to the bank',
                    [$papTransaction->mortgage_id, $papTransactionDetail->payment_id]);
            }
        }

        return true;
    }

    public function renewal($papTransaction, $papTransactionDetails, $mortgage) {

        $mortgageRenewalsTable = MortgageRenewalsTable::query()
        ->where('mortgage_id', $papTransaction->mortgage_id)
        ->where('renewal_id', $papTransaction->renewal_id)
        ->first();

        if(!$mortgageRenewalsTable) {
            return false;
        }

        $papFileBO = new PapFileBO($this->logger, $this->db);
        if((new DateTime())->format('H') >= $this->cutoffTime) {
            $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 2);
        } else {
            $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 1);
        }
        $nextBusinessDay->setTime(0, 0);
        
        //ABL, create payment of renewal for the children mortgages
        $cInvCard = MortgageTable::query()
            ->where("is_deleted", 'no')
            ->where("ab_loan", 'c_inv')
            ->where("parent", $papTransaction->mortgage_id)
            ->get();
    
        $cInvCardCount = count($cInvCard);
        
        foreach($papTransactionDetails as $key => $papTransactionDetail) {

            if(new DateTime($papTransactionDetail->payment_date) < $nextBusinessDay) {
                //note
                $notesBO = new NotesBO($this->logger, $this->db);
                $note = (new DateTime())->format('M j, Y, g:ia') . ' - Renewal: payment due in ' . (new DateTime($papTransactionDetail->payment_date))->format('m/d/Y') . ' could not be processed automatically, it needs to be processed manually.';

                $followerId = $notesBO->getFollowerUpByMortgageId($mortgage->mortgage_id);
                $followerId = is_null($followerId) ? $papTransaction->created_by : $followerId;

                $notesBO->new(
                    $mortgage->application_id,
                    $mortgage->mortgage_id,
                    59,
                    $note,
                    $followerId,
                    'no',
                    $papTransaction->created_by
                );

                if ($cInvCardCount > 0) {
                    foreach ($cInvCard as $cInv) {
                        $notesBO->new(
                            $mortgage->application_id,
                            $cInv->mortgage_id,
                            59,
                            $note,
                            $followerId,
                            'no',
                            $papTransaction->created_by
                        );
                    }
                }
            } else {
                //payment
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    $papTransactionDetail->payment_amount,
                    false,
                    '',
                    'Pre2'
                );

                $papTransactionDetail->new_payment_id = $newPaymentId;
                $papTransactionDetail->save();

                if ($cInvCardCount > 0) {
                    foreach ($cInvCard as $cInv) {

                        $cMortgageRenewalsTable = MortgageRenewalsTable::query()
                            ->where('mortgage_id', $cInv->mortgage_id)
                            ->where('renewal_id', $papTransaction->renewal_id)
                            ->first();
                        //$this->logger->info('PapBO->renewal - cMortgageRenewalsTable', [$cMortgageRenewalsTable]);
                        $cInvCardPayment = $cMortgageRenewalsTable->new_monthly_pmt;
                        //$this->logger->info('PapBO->renewal - cInvCardPayment', [$cInvCardPayment]);

                        $newPaymentId = $this->createMortgagePayment(
                            $cInv->mortgage_id,
                            $papTransactionDetail->payment_date,
                            $cInvCardPayment,
                            false,
                            '',
                            'Pre2'
                        );
                    }
                }

            }
        }

        return true;
    }

    public function nsfRetake($papTransaction, $papTransactionDetails, $mortgage) {

        $userId = Auth::user()->user_id ?? null;
        if ($userId === null) {
            $userId = request()->query('userId') ?? null;
        }
        
        foreach($papTransactionDetails as $key => $papTransactionDetail) {
            if($papTransactionDetail->new_payment_id == 0) {
                $newPaymentId = $this->createMortgagePayment(
                    $papTransaction->mortgage_id,
                    $papTransactionDetail->payment_date,
                    $papTransactionDetail->payment_amount,
                    false,
                    $papTransaction->comment,
                    'Pre2'
                );

                $papTransactionDetail->new_payment_id = $newPaymentId;
                $papTransactionDetail->save();
    
                //ab loan
                if ($mortgage->ab_loan == 'm_inv') {

                    $cInvCardCount = 0;
                    $childPaymentAmount = array();
                    $retakeAmount = $papTransactionDetail->payment_amount;
                    $paymentPlusNsf = $mortgage->monthly_pmt + $mortgage->nsf_fee;

                    $cInvCards = MortgageTable::query()
                        ->where("is_deleted", 'no')
                        ->where("ab_loan", 'c_inv')
                        ->where("parent", $mortgage->mortgage_id)
                        ->get();

                    $$cInvCardCount = count($cInvCards);

                    $i = 0;
                    foreach($cInvCards as $key => $cInvCard) {
                        
                        //$osbPercent = $cInvCard->current_balance / $mortgage->current_balance;
                        $pmtPercent = $cInvCard->monthly_pmt / $mortgage->monthly_pmt;
                        $i++;

                        if ($retakeAmount == $paymentPlusNsf) {
                            $childPaymentAmount[$i] = $cInvCard->monthly_pmt + $pmtPercent * $mortgage->nsf_fee;
                            /*
                            $mortgageBalanceHistory = DB::table('mortgage_balance_history')
                                ->where('mortgage_id', $cInvCard->mortgage_id)
                                ->where('payment_id', $newPaymentId-1)
                                ->first();
                            $childPaymentAmount[$i] = -1*$mortgageBalanceHistory->principal_amount;
                            */
                        } elseif ($retakeAmount < $paymentPlusNsf) {
                            $childPaymentAmount[$i] = $pmtPercent * $retakeAmount;
                            /*
                            if ($retakeAmount == $mortgage->monthly_pmt){
                                $childPaymentAmount[$i] = $cInvCard->monthly_pmt;
                            }elseif($retakeAmount > $mortgage->monthly_pmt){                                
                                $childPaymentAmount[$i] = $cInvCard->monthly_pmt + $osbPercent * ($retakeAmount - $mortgage->monthly_pmt);
                            }elseif($retakeAmount < $mortgage->monthly_pmt){
                                                    
                                if ($i == 1){
                                    $remainingAmount = $retakeAmount;
                                }else{
                                    $remainingAmount -= $childPaymentAmount[$i-1];
                                }
                                $remainingAmount = $remainingAmount < 0 ? 0 : $remainingAmount;                            
                                $childPaymentAmount[$i] = min($cInvCard->monthly_pmt, $remainingAmount);

                            }
                            */
                        } elseif ($retakeAmount > $paymentPlusNsf) {
                            //following is same as payment based on payment percentage
                            $childPaymentAmount[$i] = $cInvCard->monthly_pmt + $pmtPercent * ($retakeAmount - $mortgage->monthly_pmt);
                        }

                        $childPaymentAmount[$i] = round($childPaymentAmount[$i], 2);
                        
                        $newChildPaymentId = $this->createMortgagePayment(
                            $cInvCard->mortgage_id,
                            $papTransactionDetail->payment_date,
                            $childPaymentAmount[$i],
                            false,
                            $papTransaction->comment,
                            'Pre2',
                            $newPaymentId
                        );
                    }
                }

            } else {
                $mortgagePaymentsTable = MortgagePaymentsTable::query()
                ->where('mortgage_id', $papTransaction->mortgage_id)
                ->where('payment_id', $papTransactionDetail->new_payment_id)
                ->first();

                if($mortgagePaymentsTable) {
                    if(is_null($mortgagePaymentsTable->pap_file_payment_id)) {
                        $mortgagePaymentsTable->processing_date = $papTransactionDetail->payment_date;
                        $mortgagePaymentsTable->pmt_amt = $papTransactionDetail->payment_amount;
                        $mortgagePaymentsTable->is_processed = 'no';
                        $mortgagePaymentsTable->comment = is_null($papTransaction->comment) ? '' : $papTransaction->comment;
                        $mortgagePaymentsTable->updated_by = Auth::user()->user_id ?? null;
                        $mortgagePaymentsTable->save();

                        //ab loan
                        if ($mortgage->ab_loan == 'm_inv') {

                            $cInvCardCount = 0;
                            $childPaymentAmount = array();
                            $retakeAmount = $papTransactionDetail->payment_amount;
                            $paymentPlusNsf = $mortgage->monthly_pmt + $mortgage->nsf_fee;

                            $cInvCards = MortgageTable::query()
                                ->where("is_deleted", 'no')
                                ->where("ab_loan", 'c_inv')
                                ->where("parent", $mortgage->mortgage_id)
                                ->get();

                            $$cInvCardCount = count($cInvCards);

                            $i = 0;
                            foreach($cInvCards as $key => $cInvCard) {
                                
                                $pmtPercent = $cInvCard->monthly_pmt / $mortgage->monthly_pmt;
                                $i++;

                                if ($retakeAmount == $paymentPlusNsf) {
                                    $childPaymentAmount[$i] = $cInvCard->monthly_pmt + $pmtPercent * $mortgage->nsf_fee;
                                } elseif ($retakeAmount < $paymentPlusNsf) {
                                    $childPaymentAmount[$i] = $pmtPercent * $retakeAmount;
                                } elseif ($retakeAmount > $paymentPlusNsf) {
                                    //following is same as payment based on payment percentage
                                    $childPaymentAmount[$i] = $cInvCard->monthly_pmt + $pmtPercent * ($retakeAmount - $mortgage->monthly_pmt);
                                }

                                $childPaymentAmount[$i] = round($childPaymentAmount[$i], 2);
                        
                                DB::table('mortgage_payments_table')
                                ->where('mortgage_id', $cInvCard->mortgage_id)
                                ->where('payment_id', $papTransactionDetail->new_payment_id)
                                ->update([
                                    'original_date'    => $papTransactionDetail->payment_date,
                                    'processing_date'  => $papTransactionDetail->payment_date,
                                    'period_date'  => $papTransactionDetail->payment_date,
                                    'pmt_amt'         => $childPaymentAmount[$i],
                                    'comment'         => $papTransaction->comment,
                                    'updated_by'    => $userId,
                                ]);

                            }
                        }

                    } else {
                        $this->logger->warning(
                            'PapBO->nsfRetake - Could not process it - Payment already sent to the bank',
                            [$papTransaction->mortgage_id, $papTransactionDetail->payment_id]);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function splitPayment($papTransaction, $papTransactionDetails, $mortgage) {

        /**
         * each payment to be split has 2 records in pap_transaction_detail
         * this function needs to delete the old payment just once and create 2 new payments
         */
        $originalPaymentId = '';
        $paymentSentToBank = array();
        foreach($papTransactionDetails as $key => $papTransactionDetail) {
            if($originalPaymentId != $papTransactionDetail->payment_id) {
                $mortgagePaymentsTable = MortgagePaymentsTable::query()
                ->where('mortgage_id', $papTransaction->mortgage_id)
                ->where('payment_id', $papTransactionDetail->payment_id)
                ->first();

                if(is_null($mortgagePaymentsTable->pap_file_payment_id)) {
                    $this->savePaymentHistory($mortgagePaymentsTable);

                    $mortgagePaymentsTable->delete();

                } else {
                    $paymentSentToBank[$papTransactionDetail->payment_id] = 'x';

                    $this->logger->warning(
                        'PapBO->splitPayment - Could not process it - Payment already sent to the bank',
                        [$papTransaction->mortgage_id, $papTransactionDetail->payment_id]);
                }
            }
            $originalPaymentId = $papTransactionDetail->payment_id;

            if(!isset($paymentSentToBank[$papTransactionDetail->payment_id])) {
                $newPaymentId = $this->createNewPayment(
                    $mortgagePaymentsTable,
                    $papTransactionDetail->payment_date,
                    $papTransactionDetail->payment_amount,
                    null,
                    null,
                    null,
                    null
                );

                $papTransactionDetail->new_payment_id = $newPaymentId;
                $papTransactionDetail->save();
            }
        }

        return true;

    }

    public function createMortgagePayment($mortgageId, $paymentDate, $paymentAmount, $isProcessed, $comment, $flag, $paymentId = null) {

        if(is_null($paymentId)) {
            $paymentId = $this->getNextPaymentId($mortgageId);
        }

        $comment = is_null($comment) ? '' : $comment;

        $userId = Auth::user()->user_id ?? null;
        if(is_null($userId)) {
            $userId = request()->query('userId') ?? null;
        }

        $mortgagePaymentsTable = new MortgagePaymentsTable();
        $mortgagePaymentsTable->payment_id = $paymentId;
        $mortgagePaymentsTable->mortgage_id = $mortgageId;
        $mortgagePaymentsTable->original_date = $paymentDate;
        $mortgagePaymentsTable->processing_date = $paymentDate;
        $mortgagePaymentsTable->pmt_amt = $paymentAmount;
        $mortgagePaymentsTable->is_nsf = 'no';
        $mortgagePaymentsTable->nsf_id = 0;
        $mortgagePaymentsTable->is_post_dated_cheque = 'yes';
        $mortgagePaymentsTable->is_processed = $isProcessed ? 'yes' : 'no';
        $mortgagePaymentsTable->is_add_on_fee = 'no';
        $mortgagePaymentsTable->comment = $comment;
        $mortgagePaymentsTable->initial_pmt = 'no';
        $mortgagePaymentsTable->period_date = $paymentDate;
        $mortgagePaymentsTable->is_payout = 'no';
        $mortgagePaymentsTable->payout_id = 0;
        $mortgagePaymentsTable->is_sale = 'no';
        $mortgagePaymentsTable->investor_tracking_id = 0;
        $mortgagePaymentsTable->is_renewal = 'no';
        $mortgagePaymentsTable->renewal_id = 0;
        $mortgagePaymentsTable->renewal_amt = 0;
        $mortgagePaymentsTable->service_charge = 0;
        $mortgagePaymentsTable->pd_date = '';
        $mortgagePaymentsTable->before_sale = 'no';
        $mortgagePaymentsTable->transfer_mortgage = 'no';
        $mortgagePaymentsTable->transfer_mortgage_id = 0;
        $mortgagePaymentsTable->transfer_mortgage_investor_tracking_id = 0;
        $mortgagePaymentsTable->nsf = 0;
        $mortgagePaymentsTable->flag = $flag;
        $mortgagePaymentsTable->deposit_date = '0000-00-00';
        $mortgagePaymentsTable->parent = 0;
        $mortgagePaymentsTable->is_adjust = 'no';
        $mortgagePaymentsTable->ab_by_volume = 'na';
        $mortgagePaymentsTable->is_bank_payment = 'no';
        $mortgagePaymentsTable->pap_file_payment_id = null;
        $mortgagePaymentsTable->created_by = $userId;
        $mortgagePaymentsTable->updated_by = $userId;
        $mortgagePaymentsTable->save();

        return $paymentId;
    }

    public function createNewPayment(
        $mortgagePaymentsTable,
        $paymentDate,
        $paymentAmount,
        $isNsf,
        $nsfId,
        $nsfFee,
        $comment
    ) {
        $paymentId = $this->getNextPaymentId($mortgagePaymentsTable->mortgage_id);
        $userId = Auth::user()->user_id ?? null;

        $mortgagePaymentsTableNew = new MortgagePaymentsTable();
        $mortgagePaymentsTableNew->mortgage_id = $mortgagePaymentsTable->mortgage_id;
        $mortgagePaymentsTableNew->payment_id = $paymentId;

        if(is_null($paymentDate)) {
            $mortgagePaymentsTableNew->original_date = $mortgagePaymentsTable->original_date;
            $mortgagePaymentsTableNew->processing_date = $mortgagePaymentsTable->processing_date;
            $mortgagePaymentsTableNew->period_date = $mortgagePaymentsTable->period_date;
        } else {
            $mortgagePaymentsTableNew->original_date = $paymentDate;
            $mortgagePaymentsTableNew->processing_date = $paymentDate;
            $mortgagePaymentsTableNew->period_date = $paymentDate;
        }

        if(is_null($paymentAmount)) {
            $mortgagePaymentsTableNew->pmt_amt = $mortgagePaymentsTable->pmt_amt;
        } else {
            $mortgagePaymentsTableNew->pmt_amt = $paymentAmount;
        }

        if($isNsf === true && !is_null($nsfId) && !is_null($nsfFee)) {
            $mortgagePaymentsTableNew->is_nsf = 'yes';
            $mortgagePaymentsTableNew->nsf_id = $nsfId;
            $mortgagePaymentsTableNew->is_post_dated_cheque = 'no';
            $mortgagePaymentsTableNew->nsf = $nsfFee;
            $mortgagePaymentsTableNew->flag = 'N/A';

        } elseif($isNsf === false) {
            $mortgagePaymentsTableNew->is_nsf = 'no';
            $mortgagePaymentsTableNew->nsf_id = 0;
            $mortgagePaymentsTableNew->is_post_dated_cheque = 'no';
            $mortgagePaymentsTableNew->nsf = 0;
            $mortgagePaymentsTableNew->flag = 'N/A';

        } else {
            $mortgagePaymentsTableNew->is_nsf = $mortgagePaymentsTable->is_nsf;
            $mortgagePaymentsTableNew->nsf_id = $mortgagePaymentsTable->nsf_id;
            $mortgagePaymentsTableNew->is_post_dated_cheque = $mortgagePaymentsTable->is_post_dated_cheque;
            $mortgagePaymentsTableNew->nsf = $mortgagePaymentsTable->nsf;
            $mortgagePaymentsTableNew->flag = $mortgagePaymentsTable->flag;
        }

        if(is_null($comment)) {
            $mortgagePaymentsTableNew->comment = $mortgagePaymentsTable->comment;
        } else {
            $mortgagePaymentsTableNew->comment = $comment;
        }

        $mortgagePaymentsTableNew->is_processed = $mortgagePaymentsTable->is_processed;
        $mortgagePaymentsTableNew->is_add_on_fee = $mortgagePaymentsTable->is_add_on_fee;
        $mortgagePaymentsTableNew->initial_pmt = $mortgagePaymentsTable->initial_pmt;
        $mortgagePaymentsTableNew->is_payout = $mortgagePaymentsTable->is_payout;
        $mortgagePaymentsTableNew->payout_id = $mortgagePaymentsTable->payout_id;
        $mortgagePaymentsTableNew->is_sale = $mortgagePaymentsTable->is_sale;
        $mortgagePaymentsTableNew->investor_tracking_id = $mortgagePaymentsTable->investor_tracking_id;
        $mortgagePaymentsTableNew->is_renewal = $mortgagePaymentsTable->is_renewal;
        $mortgagePaymentsTableNew->renewal_id = $mortgagePaymentsTable->renewal_id;
        $mortgagePaymentsTableNew->renewal_amt = $mortgagePaymentsTable->renewal_amt;
        $mortgagePaymentsTableNew->service_charge = $mortgagePaymentsTable->service_charge;
        $mortgagePaymentsTableNew->pd_date = $mortgagePaymentsTable->pd_date;
        $mortgagePaymentsTableNew->before_sale = $mortgagePaymentsTable->before_sale;
        $mortgagePaymentsTableNew->transfer_mortgage = $mortgagePaymentsTable->transfer_mortgage;
        $mortgagePaymentsTableNew->transfer_mortgage_id = $mortgagePaymentsTable->transfer_mortgage_id;
        $mortgagePaymentsTableNew->transfer_mortgage_investor_tracking_id = $mortgagePaymentsTable->transfer_mortgage_investor_tracking_id;
        if($mortgagePaymentsTable->deposit_date != '0000-00-00') {
            $mortgagePaymentsTableNew->deposit_date = $mortgagePaymentsTable->deposit_date;
        }
        $mortgagePaymentsTableNew->parent = $mortgagePaymentsTable->parent;
        $mortgagePaymentsTableNew->is_adjust = $mortgagePaymentsTable->is_adjust;
        $mortgagePaymentsTableNew->ab_by_volume = $mortgagePaymentsTable->ab_by_volume;
        $mortgagePaymentsTableNew->created_by = $userId;
        $mortgagePaymentsTableNew->updated_by = $userId;
        $mortgagePaymentsTableNew->save();

        return $paymentId;
    }

    public function savePaymentHistory($mortgagePaymentsTable) {
        $mortgagePaymentsHistory = new MortgagePaymentsHistory();
        $mortgagePaymentsHistory->payment_id = $mortgagePaymentsTable->payment_id;
        $mortgagePaymentsHistory->mortgage_id = $mortgagePaymentsTable->mortgage_id;
        $mortgagePaymentsHistory->original_date = $mortgagePaymentsTable->original_date;
        $mortgagePaymentsHistory->processing_date = $mortgagePaymentsTable->processing_date;
        $mortgagePaymentsHistory->pmt_amt = $mortgagePaymentsTable->pmt_amt;
        $mortgagePaymentsHistory->is_nsf = $mortgagePaymentsTable->is_nsf;
        $mortgagePaymentsHistory->nsf_id = $mortgagePaymentsTable->nsf_id;
        $mortgagePaymentsHistory->is_post_dated_cheque = $mortgagePaymentsTable->is_post_dated_cheque;
        $mortgagePaymentsHistory->is_processed = $mortgagePaymentsTable->is_processed;
        $mortgagePaymentsHistory->is_add_on_fee = $mortgagePaymentsTable->is_add_on_fee;
        $mortgagePaymentsHistory->comment = $mortgagePaymentsTable->comment;
        $mortgagePaymentsHistory->initial_pmt = $mortgagePaymentsTable->initial_pmt;
        $mortgagePaymentsHistory->period_date = $mortgagePaymentsTable->period_date;
        $mortgagePaymentsHistory->is_payout = $mortgagePaymentsTable->is_payout;
        $mortgagePaymentsHistory->payout_id = $mortgagePaymentsTable->payout_id;
        $mortgagePaymentsHistory->is_sale = $mortgagePaymentsTable->is_sale;
        $mortgagePaymentsHistory->investor_tracking_id = $mortgagePaymentsTable->investor_tracking_id;
        $mortgagePaymentsHistory->is_renewal = $mortgagePaymentsTable->is_renewal;
        $mortgagePaymentsHistory->renewal_id = $mortgagePaymentsTable->renewal_id;
        $mortgagePaymentsHistory->renewal_amt = $mortgagePaymentsTable->renewal_amt;
        $mortgagePaymentsHistory->service_charge = $mortgagePaymentsTable->service_charge;
        $mortgagePaymentsHistory->pd_date = $mortgagePaymentsTable->pd_date;
        $mortgagePaymentsHistory->before_sale = $mortgagePaymentsTable->before_sale;
        $mortgagePaymentsHistory->transfer_mortgage = $mortgagePaymentsTable->transfer_mortgage;
        $mortgagePaymentsHistory->transfer_mortgage_id = $mortgagePaymentsTable->transfer_mortgage_id;
        $mortgagePaymentsHistory->transfer_mortgage_investor_tracking_id = $mortgagePaymentsTable->transfer_mortgage_investor_tracking_id;
        $mortgagePaymentsHistory->nsf = $mortgagePaymentsTable->nsf;
        $mortgagePaymentsHistory->flag = $mortgagePaymentsTable->flag;
        $mortgagePaymentsHistory->deposit_date = $mortgagePaymentsTable->deposit_date == '0000-00-00' ? null : $mortgagePaymentsTable->deposit_date;
        $mortgagePaymentsHistory->parent = $mortgagePaymentsTable->parent;
        $mortgagePaymentsHistory->is_adjust = $mortgagePaymentsTable->is_adjust;
        $mortgagePaymentsHistory->ab_by_volume = $mortgagePaymentsTable->ab_by_volume;
        $mortgagePaymentsHistory->save();
    }

    public function getNextPaymentId($mortgageId) {
        $paymentId = MortgagePaymentsTable::query()
        ->where('mortgage_id', $mortgageId)
        ->where('is_adjust', '!=', 'yes')
        ->max('payment_id');

        if($paymentId) {
            return $paymentId + 1;
        } else {
            return 1;
        }
    }

    public function validadeBankInfo($id, $userId) {
        $papBank = PapBank::find($id);

        if($papBank) {
            if(is_null($papBank->validated_at)) {
                $papBank->validated_at = new DateTime();
                $papBank->validated_by = $userId;
                $papBank->save();

                return true;
            }
        }

        return false;
    }

    public function updateBankInfo($id, $transit, $institutionCode, $account) {
        $papBank = PapBank::find($id);

        if($papBank) {
            if(is_null($papBank->validated_at)) {
                $bankInstitutionBO = new BankInstitutionBO($this->logger, $this->db);
                $institution = $bankInstitutionBO->showByCode($institutionCode);

                if($institution === false) {
                    return false;
                }

                $papBank->transit = $transit;
                $papBank->institution_id = $institution['id'];
                $papBank->account = $account;
                $papBank->save();

                return true;
            }
        }

        return false;
    }

    public function transactionNotification($id) {
        $papTransaction = PapTransaction::find($id);

        switch($papTransaction->category_id) {
            //PAP Update
            case 511:
                Notification::papUpdateFM();
                break;
                
            case 512:
                Notification::papUpdateFM();
                break;

            case 534:
                Notification::papUpdateFM();
                break;
        }
    }

    public function getPapAlerts() {
        $companies = implode(',',self::getPapCompanies());

        $papFileBO = new PapFileBO($this->logger, $this->db);
        $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 1);
        $diff = ((new DateTime())->diff($nextBusinessDay)->days) + 1;

        $sql = "select * from (
                   select a.mortgage_id, a.mortgage_code,
                          (select count(*) from mortgage_payments_table bb
                          where bb.mortgage_id = a.mortgage_id
                          and bb.initial_pmt = 'no' and bb.transfer_mortgage = 'no' and bb.is_add_on_fee = 'no') count,
                          (select min(processing_date) from mortgage_payments_table bb where bb.mortgage_id = a.mortgage_id) processing_date
                     from mortgage_table a
                    where a.mortgage_id >= 76223
                      and a.is_deleted = 'no'
                      and a.company_id in ($companies)
                      and a.current_balance > 0) aa
                 where aa.count < 3
                   and processing_date < date_sub(now(), interval $diff day)
              order by processing_date";
        $this->logger->info('PapBO->getPapAlerts - SQL', [$sql]);
        $res = $this->db->select($sql);

        $alerts = array();
        foreach ($res as $key => $value) {
            $alerts[] = [
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'processingDate' => new DateTime($value->processing_date),
                'message' => 'File already funded but no payments added yet'
            ];
        }

        return $alerts;
    }

    public function getPapCategoryById($id) {
        $categoryValue = CategoryValue::find($id);
        
        if($categoryValue) {
            return $categoryValue->name;
        }

        return null;
    }

    public function getPapCategoriesById() {
        $cat = $this->getPapCategories();

        $categories = array();
        foreach($cat as $key => $value) {
            $categories[$value['id']] = [
                'id' => $value['id'],
                'name' => $value['name']
            ];
        }

        return $categories;
    }

    public function getPapCategories() {
        $categoryValues = CategoryValue::query()
        ->where('category_id', 47)
        ->orderBy('name')
        ->get();

        $categories = array();
        foreach($categoryValues as $key => $value) {
            $categories[] = [
                'id' => $value->id,
                'name' => $value->name
            ];
        }

        return $categories;
    }

    public function getFundingDate($mortgageId) {
        /*$mortgagePaymentsTable = MortgagePaymentsTable::query()
        ->where('mortgage_id', $mortgageId)
        ->where('transfer_mortgage', 'yes')
        ->orderBy('payment_id')
        ->first();

        if($mortgagePaymentsTable) {
            $fundingDate = new DateTime($mortgagePaymentsTable->processing_date);
            $fundingDate->setTime(0, 0);
            return $fundingDate;
        }*/

        $query = "select b.processing_date from mortgage_table a
                    join mortgage_payments_table b on a.transfer_id = b.mortgage_id
                   where a.mortgage_id = ?
                     and b.is_sale = 'yes'
                order by b.processing_date";
        $res = $this->db->select($query, [$mortgageId]);

        if(count($res) > 0) {
            $fundingDate = new DateTime($res[0]->processing_date);
            $fundingDate->setTime(0, 0);
            return $fundingDate;
        }

        return false;
    }

    public static function getPapCompanies() {
        return array(5,16,183,182,1970);
    }

    public function institutionName($institutionCod) {

        if (is_numeric($institutionCod)) {
            $institutionCod = strval($institutionCod);
            $institutionCod = str_pad($institutionCod, 3, '0', STR_PAD_LEFT);
        }

        $papInstitution = PapInstitution::query()
        ->where('code', $institutionCod)
        ->first();

        if ($papInstitution) {

            $data = [
                'id'   => $papInstitution->id,
                'name' => $papInstitution->name
            ];

            return $data;

        }else {
            return false;
        }
    }

    public function checkDuplicatePayment($mortgageId, $paymentdate, $paymentAmount) {

        $mortgagePaymentsTable = MortgagePaymentsTable::query()
            ->where('mortgage_id', $mortgageId)
            ->where('processing_date', $paymentdate)
            ->where('pmt_amt', $paymentAmount)
            ->first();

        if($mortgagePaymentsTable) {
            return true;
        }

        return false;

    }

    public function checkTransactions() {

        $this->logger->info('PapBO->checkTransactions');

        $startDate = new DateTime();
        $endDate = new DateTime();
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);

        $companyId = 0;
        $category = 'new';

        $transactions = $this->getTransactions($startDate, $endDate, $companyId, $category);

        $payments = array();

        foreach ($transactions as $transaction) {            

            $data = $this->getNewPapDetails($transaction->mortgageId, $category, 2);

            if (isset($data['newPapTransaction']['details'])) {
                foreach ($data['newPapTransaction']['details'] as $row) {
                    if ($row['paymentBeforeNextBusinessDay'] == true) {                    
                        $payments[] = [
                            'mortgageCode' => $transaction->mortgageCode,
                            'paymentDate' => $row['paymentDate'] ?? '0000-00-00',
                            'paymentAmount' => $row['paymentAmount'] ?? 0
                        ];
                    }
                }
            }
        }

        if(count($payments) > 0) {
            $toAddresses = array('micaccounting@amurgroup.ca');
            $subject     = 'PAP - Payment Overdue Validation';
            $body        = "Hi, <br><br>The payments below are past due.<br><br>";

            foreach ($payments as $payment) {
                $paymentAmount = number_format($payment['paymentAmount'], 2);
                $body .= "Mortgage: {$payment['mortgageCode']}  Payment Date: {$payment['paymentDate']->format('m/d/Y')} Payment Amount: {$paymentAmount}<br>";
            }

            Utils::sendEmail($toAddresses, $subject, $body,'', null);

            $this->logger->info('PapBO->checkTransactions email sent');

        } else {
            $this->logger->info('PapBO->checkTransactions email not sent');
        }
    }

    public function checkPayments($mortgageId, $status) {

        if($status !== 'Pending') {
            return [
                'paymentsFound' => false,
                'paymentsMessage' => ''
            ];
        }

        $x = MortgagePaymentsTable::query()
        ->where('mortgage_id', $mortgageId)
        ->where('is_nsf', 'no')
        ->where('is_add_on_fee', 'no')
        ->where('initial_pmt', 'no')
        ->where('is_payout', 'no')
        ->where('is_renewal', 'no')
        ->where('transfer_mortgage', 'no')
        ->count();

        $paymentsDetail = array();
        if($x > 0) {
            $paymentsDetail['paymentsFound'] = true;
            if ($x == 1) {
                $paymentsDetail['paymentsMessage'] = $x . ' payment found on the card';
            } else {
                $paymentsDetail['paymentsMessage'] = $x . ' payments found on the card';
            }            
        } else {
            $paymentsDetail['paymentsFound'] = false;
            $paymentsDetail['paymentsMessage'] = '';
        }

        return $paymentsDetail;
    }

    public function updatePayments($id, $transactionId, $paymentStatus, $newPaymentDate) {

        $papTransactionDetails = PapTransactionDetail::query()
        ->where('id', $id)
        ->where('pap_transaction_id', $transactionId)
        ->first();

        if ($papTransactionDetails) {
            $papTransactionDetails->payment_status = $paymentStatus;
            $papTransactionDetails->new_payment_date = $newPaymentDate;
            $papTransactionDetails->save();
        }

        return true;
    }

}
