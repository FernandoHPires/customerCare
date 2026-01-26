<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use DateTime;

class PapFileDailyReportBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function papDailyReport() {
        // get the companies
        // loop companies
        // catch all data that will have errors then send email to Danny, Daniel and Penny

        $getCompanies = new PapBO($this->logger, $this->db);
        $companies = $getCompanies->getPapCompanies();

        $issues = array();
        $temp = "";
        
        $papFileBO = new PapFileBO($this->logger, $this->db);
        $papFileBO->changeIntervalDays(2);

        if( $companies ) {
            // loop through companies
            foreach( $companies as $companyId ) {
                // get payment per companyId
                $payments = $papFileBO->getPayments($companyId);
                
                // validate payments and return payment details with issues
                $validated = $this->validateDailyReportPayments($payments);
                if( count($validated) > 0 ) {
                    $issues[] = $validated;
                }
            }

            // if issues are found notify Danny, Daniel and Penny
            if( count($issues) > 0 ) {
                $to = array('danny@amurgroup.ca', 'daniel@amurgroup.ca', 'penny@amurfinancialgroup.ca');
                $subject = 'PAP Bank - Issue Report';
                $body = 'Hi,<br><br>';
                $body .= 'We found some issues with these files, please check:<br><br>';
                $body .= '<ul>';
                foreach($issues as $companyIssues) {
                    foreach($companyIssues as $data){
                        $body .= '<li>'.$data.'</li>';
                    }
                }
                $body .= '</ul>';
                $temp = $body;
                
                if(env('APP_ENV') == 'production') {
                    Utils::sendEmail($to, $subject, $body);
                    $this->logger->info('PapFileDailyReportBO->papDailyReport email sent',[]);
                }
            }
        } else {
            $this->logger->error('PapFileDailyReportBO->papDailyReport error companies not found', [$companies]);
        }

        return array(
            'companies' => $companies,
            'issues' => $issues,
            "body" => $temp
        );
    }

    public function validateDailyReportPayments($payments) {
        $hold = array();

        if(count($payments) == 0) {
            return $hold;
        }

        foreach($payments as $key => $payment) {
            if(is_null($payment['clientName'])) {
                $hold[] = $payment['mortgageCode'] . ': Client name is empty';
            }

            if(is_null($payment['bank']) || is_null($payment['transit']) || is_null($payment['account'])) {
                $hold[] = $payment['mortgageCode'] . ': Incomplete bank information';
            }

            if($payment['amount'] <= 0) {
                $hold[] = $payment['mortgageCode'] . ': Payment amount is zero';
            }

            $dateCheck = DateTime::createFromFormat('Y-m-d', $payment['dueDate']->format('Y-m-d'));
            if($dateCheck === false) {
                $hold[] = $payment['mortgageCode'] . ": Payment date invalid - Contact IT!";
            }
        }

        return $hold;
    }
    
    public function bankInfoReminder() {
        //get the brokers
 
        $brokers = $this->getBrokerForPapBankUpdate();
        // $this->logger->info('Broker are' .$brokers);

         foreach( $brokers as $broker){

             // notify brokers daily for the rejected bank information
                 $to = $broker->user_email;
                 $subject = 'PAP Bank - Bank Information Validate Reminder';
                 $body = 'Hi ' .$broker->user_fname.',' .'<br>';
                 $body .= '<br>This is a gentle reminder to check the bank information. It was rejected by Finance team.<br><br/>';
                 $body .= '<b>Mortgage Code</b>: ' . $broker ->mortgage_code .'<br>';
                 $body .= '<b>Bank Name</b>: ' . $broker ->name  .'<br>';
                 $body .= '<b>Transit:</b> ' . $broker ->transit  .'<br>';
                 $body .= '<b>Comment:</b> ' . $broker ->comment  .'<br>';
                 $temp = $body;
                 
                if(env('APP_ENV') == 'production') {
                    Utils::sendEmail([$to], $subject, $body);
                     $this->logger->info('PapFileDailyReportBO->bankInfoReminder email sent',[$to,$subject,$temp]);
                }
        }
    }
    public function getBrokerForPapBankUpdate(){
        $query = "select d.name, b.user_fname, b.user_lname, b.user_email, a.institution_id, a.transit, a.account, a.`comment`, c.mortgage_code
        from pap_bank a
        join users_table b on a.created_by = b.user_id
        join mortgage_table c on c.mortgage_id = a.mortgage_id
        join pap_institution d on d.id = a.institution_id
        where a.status = 'R'
        and a.deleted_at is null and c.is_deleted = 'no'";

        $res = $this->db->select($query);
        return $res;
    }
}
