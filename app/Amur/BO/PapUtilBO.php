<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Factory\Factory;
use App\Amur\Utilities\Utils;
use DateTime;

class PapUtilBO {
    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }
    
    public function checkPayments($execution) {
        $this->logger->info('PapUtilBO->checkPayments - Execution started',[$execution]);
        
        $papFile = Factory::create('BO\\PapFileBO', $this->logger, $this->db );        
       
        if ($papFile->checkValidBusinessDay(new DateTime())) {
            $companies      = [5,16,182,1970];
            $arrayValues_5  = [];
            $arrayValues_16 = [];
            $arrayValues_182 = [];
            $arrayValues_1970 = [];

            foreach($companies as $key => $companyId) {
                $payments = $papFile->getPayments($companyId);

                if($companyId == 5) {
                    $arrayValues_5 = $payments;
                } elseif($companyId == 16) {
                    $arrayValues_16 = $payments;
                } elseif($companyId == 182) {
                    $arrayValues_182 = $payments;
                } elseif($companyId == 1970) {
                    $arrayValues_1970 = $payments;
                }
            }

            if(
                count($arrayValues_5) > 0 ||
                count($arrayValues_16) > 0 ||
                count($arrayValues_182) > 0 ||
                count($arrayValues_1970) > 0
            ) {
                if ($execution == 'First') {
                    $toAddresses = [''];

                } else {
                    $toAddresses = [''];
                }
                
                $subject = 'PAP bank file alert';

                $body    = "Hello, <br> <br>
                            Today's PAP bank file wasn't sent yet, please, send it as soon as possible. <br><br>";

                if (count($arrayValues_5) > 0) {

                    $companyId   = 5;
                    $response    = $papFile->getConfigData($companyId);
                    $companyName = $response['longName'];


                    $body   = $body . 'Company: '. $companyName . '<br>';
                    $amount = count($arrayValues_5);
                    $body  .= $amount  . ' Payments' . '<br><br>';
                }

                if (count($arrayValues_16) > 0) {
                    $companyId   = 16;
                    $response    = $papFile->getConfigData($companyId);
                    $companyName = $response['longName'];


                    $body   = $body . 'Company: '. $companyName . '<br>';  
                    $amount = count($arrayValues_16);
                    $body  .= $amount  . ' Payments' . '<br><br>';
                }

                if (count($arrayValues_182) > 0) {
                    $companyId   = 182;
                    $response    = $papFile->getConfigData($companyId);
                    $companyName = $response['longName'];


                    $body   = $body . 'Company: '. $companyName . '<br>';  
                    $amount = count($arrayValues_182);
                    $body  .= $amount  . ' Payments' . '<br><br>';
                }

                if (count($arrayValues_1970) > 0) {
                    $companyId   = 1970;
                    $response    = $papFile->getConfigData($companyId);
                    $companyName = $response['longName'];


                    $body   = $body . 'Company: '. $companyName . '<br>';  
                    $amount = count($arrayValues_1970);
                    $body  .= $amount  . ' Payments' . '<br><br>';
                }

                $body = $body .  '<br>This is an automated message, please do not reply.';

                Utils::sendEmail($toAddresses, $subject, $body);
                $this->logger->info('PapUtilBO->checkPayments - email sent',[$execution]);

            }
        }
    }
}
