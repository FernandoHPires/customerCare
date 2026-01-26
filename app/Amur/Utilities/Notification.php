<?php

namespace App\Amur\Utilities;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Models\MortgageTable;
use App\Models\PayoutApproval;
use App\Models\UsersTable;

class Notification {

    public static function appraisalUpdate($applicationId) {
        $logger = new Logger();
        $db = new DB();

        $sql = "select c.user_id, c.user_email
                  from saved_quote_table a
                  join sale_investor_table b on a.saved_quote_id = b.saved_quote_id
                  join users_table c on b.fm_approved_id = c.user_id
                 where a.application_id = $applicationId
                   and a.disburse = 'Yes'
                   and a.ready_buy = 'Yes'
                   and b.fm_committed = 'Looking'";
        $res = $db->query($sql);

        if(count($res) == 0) {
            $logger->info('Notification->appraisalUpdate - No FM committed found', [$applicationId]);
            return;
        }

        if(!in_array($res[0]->user_id,[425,4895,4974])) {
            return;
        }

        $toAddresses = array($res[0]->user_email);
        //$toAddresses = array('ricky@amurgroup.ca');
        $subject = "Appraisal for TACL# $applicationId was updated";
        $body = "Appraisal for TACL# $applicationId was updated";

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->appraisalUpdate',[$applicationId,json_encode($toAddresses)]);
    }

    public static function papUpdateFM() {
        $logger = new Logger();

        $toAddresses = array('jason@amurgroup.ca', 'ricky@amurgroup.ca', 'emilie@amurgroup.ca');
        $subject = 'New PAP Update Request';
        $body = "Hi,<br><br>There is a new PAP update request peding for your approval/rejection. Please, access Strive to see it.<br><br>https://strive.amurfinancial.group";

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->papUpdateFM',[json_encode($toAddresses)]);
    }

    public static function bankInfoRejected($userId, $mortgageCode, $rejectReason) {
        $logger = new Logger();

        $user = UsersTable::find($userId);

        $toAddresses = array($user->user_email);
        $subject = 'Bank Account Information Rejected';
        $body = "Hi,<br><br>The bank account information you entered for the file $mortgageCode was rejected, please, access TACL and fix it as soon as possible.<br><br>Reject Reason:<br>$rejectReason";

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->bankInfoRejected',[json_encode($toAddresses), $mortgageCode]);
    }

    public static function payoutRejected($id, $userId, $rejectReason) {
        $logger = new Logger();

        $payout = PayoutApproval::find($id);
        $mortgageId	= $payout->mortgage_id;
        $createdBy = $payout->created_by;

        $mortgage = MortgageTable::find($mortgageId);

        $subject = 'Payout Rejected - ' . $mortgage->mortgage_code;

        $user = UsersTable::find($createdBy);
        $userFname = $user->user_fname;
        $userEmail = $user->user_email;

        $userReject = UsersTable::find($userId);
        $userRejectFname = $userReject->user_fname;
        $userRejectLname = $userReject->user_lname;

        $toAddresses = array($userEmail);
        $body   = 'Hi ' . $userFname . ",<br><br>";
        $body  .= 'The payout request you created for file ' . $mortgage->mortgage_code . ' was rejected by ' . $userRejectFname . ' ' . $userRejectLname . '<br>';
        $body  .= 'Rejected reason: ' . $rejectReason;
        
        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->payoutRejected',[json_encode($toAddresses), $id, $mortgageId]);
    }

    public static function foreclosureRejected($id, $userId, $rejectReason) {
        $logger = new Logger();

        $payout = PayoutApproval::find($id);
        $mortgageId = $payout->mortgage_id;
        $createdBy = $payout->created_by;

        $mortgage = MortgageTable::find($mortgageId);

        $subject = 'Foreclosure Rejected - ' . $mortgage->mortgage_code;

        $user = UsersTable::find($createdBy);
        $userFname = $user->user_fname;
        $userEmail = $user->user_email;       

        $userReject = UsersTable::find($userId);
        $userRejectFname = $userReject->user_fname;
        $userRejectLname = $userReject->user_lname;

        $toAddresses = array($userEmail);
        $body   = 'Hi ' . $userFname . ",<br> <br>";
        $body  .= 'The foreclosure request you created for file ' . $mortgage->mortgage_code . ' was rejected by ' . $userRejectFname . ' ' . $userRejectLname . '<br>';
        $body  .= 'Rejected reason: ' . $rejectReason;

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->foreclosureRejected',[json_encode($toAddresses), $id, $mortgageId]);
    }

    public static function transactionRejected($userId, $transactionName, $mortgageCode, $rejectReason) {
        $logger = new Logger();

        $user = UsersTable::find($userId);

        $toAddresses = array($user->user_email);
        $subject = 'PAP Transaction Rejected';
        $body = "Hi,<br><br>The PAP Transaction ($transactionName) you requested for the file $mortgageCode was rejected, please, access TACL and fix it as soon as possible.<br><br>Reject Reason:<br>$rejectReason";

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail($toAddresses, $subject, $body);
        }

        $logger->info('Notification->transactionRejected',[json_encode($toAddresses), $mortgageCode]);
    }
}

