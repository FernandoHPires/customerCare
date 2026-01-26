<?php

namespace App\Amur\Routine;

use Illuminate\Support\Facades\Storage;
use App\Amur\Bean\ILogger;
use App\Amur\Bean\IDB;
use App\Amur\BO\NotesBO;
use Illuminate\Support\Facades\DB;
use App\Amur\BO\PapBO;
use App\Amur\Utilities\Loan;
use App\Amur\Utilities\Utils;
use DateTime;

class MortgageTask {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function dailyPaymentRun() {
        $query = "select m.application_id, p.mortgage_id, m.mortgage_code, m.company_id, m.ab_loan, p.payment_id, p.processing_date, p.is_processed, p.pmt_amt, a.id,
                         (select count(*) from mortgage_payments_table aa where aa.mortgage_id = p.mortgage_id and comment = 'Payout') payout,
                         m.escalation, m.escalation_date, p.flag, fn_GetSpouse1LastNameByApplicationID(m.application_id) client_last_name
                    from mortgage_payments_table p
                    join mortgage_table m on p.mortgage_id = m.mortgage_id
               left join payout_approval a on a.mortgage_id = p.mortgage_id and a.deleted_at is null and a.canceled_at is null and (a.admin_status = 'A' or a.broker_status = 'A')
                   where is_processed = 'no'
                     and is_deleted = 'no'
                     and processing_date <= date(now())
                     and m.current_balance > 0
                     and p.flag <> 'N/A'
                order by p.mortgage_id, p.processing_date";
        $res = $this->db->select($query);

        $this->db->beginTransaction();
            
        $csvData = array();

        foreach($res as $key => $value) {
            try {
                if($value->payout > 0) {
                    $fields = ['current_balance' => 0];
                    $conditions = ['mortgage_id' => $value->mortgage_id];
                    $this->db->update('mortgage_table', $fields, $conditions);
                    
                    $this->logger->debug('MortgageTask->dailyPaymentRun - Paid out mortgage',[$value->mortgage_id]);

                } else {

                    if(!Loan::isLockedDate($value->company_id, new DateTime($value->processing_date))) {
                        $papCompanies = PapBO::getPapCompanies();

                        if ($value->ab_loan == 'c_inv') {//children card with mother approved for payout

                            $parentId = DB::table('mortgage_table')
                            ->where('mortgage_id', $value->mortgage_id)
                            ->value('parent');

                            if($parentId) {

                                $payoutId = DB::table('payout_approval')
                                    ->where('mortgage_id', $parentId)
                                    ->where('deleted_at', null)
                                    ->where('canceled_at', null)
                                    ->where(function ($q) {
                                        $q->where('admin_status', 'A')
                                        ->orWhere('broker_status', 'A');
                                    })
                                    ->value('id');
                        
                                if ($payoutId) { 
                                    $this->logger->debug(
                                        'MortgageTask->dailyPaymentRun - Payment did not run (mother payout approved)', 
                                        [$value->mortgage_id, $value->payment_id, $payoutId]
                                    );
                                    continue;
                                }
                        
                            } else {
                                $this->logger->error(
                                    'MortgageTask->dailyPaymentRun - Parent ID not found for children mortgage_id', 
                                    [$value->mortgage_id]
                                );
                            }
                        }
                                                
                        if(in_array($value->company_id, $papCompanies) && !is_null($value->id)) {
                            $this->logger->debug('MortgageTask->dailyPaymentRun - Payment did not run (payout approved)',[$value->mortgage_id, $value->payment_id, $value->id]);
                        } else {
                            $fields = ['is_processed' => 'yes'];
                            $conditions = ['mortgage_id' => $value->mortgage_id, 'payment_id' => $value->payment_id];
                            $this->db->update('mortgage_payments_table', $fields, $conditions);
                            
                            $this->logger->debug('MortgageTask->dailyPaymentRun - Running payment',[$value->mortgage_id, $value->payment_id]);

                            $csvData[] = [
                                'applicationId'	 => $value->application_id,
                                'mortgageCode'	 => $value->mortgage_code,
                                'mortgageId'     => $value->mortgage_id,
                                'processingDate' => (new DateTime($value->processing_date))->format('m/d/Y'),	
                                'amount'	     => $value->pmt_amt,
                                'paymentType'    => $value->flag,
                                'clientLastName' => $value->client_last_name
                                
                            ];

                            $query = "select max(term_end) last_term_end from mortgage_interest_rates_table where mortgage_id = ?";
                            $resTerm = $this->db->select($query,[$value->mortgage_id]);

                            if(count($resTerm) > 0) {

                                $interval = (new DateTime($resTerm[0]->last_term_end))->diff(new DateTime($value->processing_date));

                                if($interval->days <= 15) {
                                    $notesBO = new NotesBO($this->logger, $this->db);
                                    $note = (new DateTime())->format('M j, Y,') . ' ' . $value->mortgage_code . ' - The last posted cheque we have on file will run in 30 days. Please review and request new posted cheques for our investor as this file is not presently up for renewal or a variable rate change.';

                                    $followerId = 4744;

                                    $notesBO->new(
                                        $value->application_id,
                                        $value->mortgage_id,
                                        3,
                                        $note,
                                        $followerId,
                                        'no',
                                        $followerId
                                    );

                                    $this->logger->debug('MortgageTask->dailyPaymentRun - Follow up note',[$value->mortgage_id]);
                                }
                            }
                        }
                    } else {
                        $this->logger->warning('MortgageTask->dailyPaymentRun - Could not run payment, period locked',[$value->mortgage_id, $value->payment_id]);
                    }
                }
            } catch(\Throwable $e) {
                //$this->db->rollback();
                $this->logger->error('MortgageTask->dailyPaymentRun', [$value->mortgage_id,$value->payment_id,$e->getMessage(),json_encode($e->getTraceAsString())]);
            }
        }

        $this->db->commit();
            
        if(count($csvData) > 0) {

            $day = (new DateTime())->format('d');
        
            if($day == 1 || $day == 15) {
                $this->logger->debug('MortgageTask->dailyPaymentRun - csvData', [$csvData]);
                $this->exportCSV($csvData);
            }
            
        }
    }

    public function exportCSV($csvData) {

        $csvName  = 'run_payments_'.(new DateTime())->format('m-d-Y').'.csv';

        $this->logger->info('MortgageTask->exportCSV', [$csvName]);

        Storage::delete('tmp/' . $csvName); 

        Storage::disk('local')->path('tmp/' . $csvName);

        Storage::append('tmp/' . $csvName, 'Application Id,Last Name Mortgage Code,Mortgage #,Processing Date,Amount,Payment Type');
        
        foreach ($csvData as $key => $value) {
            Storage::append('tmp/' . $csvName, $value['applicationId'].','.
                                     $value['clientLastName'].' '.$value['mortgageCode'].','.
                                     $value['mortgageCode'].','.
                                     $value['processingDate'].','.
                                     $value['amount'].','.
                                     $value['paymentType']);
        }

        $toAddresses = array('danny@amurgroup.ca');
        $subject     = 'PAP - Run payments';
        $body        = "Hi, <br><br>
        The run payments daily reports is attached";
        
        $contentBytesBase64 = base64_encode(Storage::get('tmp/' . $csvName));

        $attachments = array();

        $attachments[] = [
            'name' => $csvName,
            'attachmentType' => "xlsx",
            'contentBytesBase64' => $contentBytesBase64,
        ]; 

        Utils::sendEmail($toAddresses, $subject, $body,'', $attachments);
    }   
}