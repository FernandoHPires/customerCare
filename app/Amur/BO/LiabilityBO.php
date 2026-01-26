<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\LiabilityTable;

class LiabilityBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getDataByApplicationId($applicationId) {
        $data = LiabilityTable::query()
        ->where('application_id', $applicationId)
        ->orderBy('liability_id', 'asc')
        ->get();

        $liabilities = array();
        foreach($data as $key => $value) {
            $liabilities[] = [
                'id' => $value->liability_id,
                'lender' => $value->lender,
                'balanceOwed' => $value->balance,
                'monthlyPayment' => $value->payment,
                'toBePaidOut' => $value->payout,
                'comment' => $value->comment,
                'isRemoved' => false
            ];
        }
        
        return $liabilities;
    }

    public function updateByLiabilityId($data, $applicationId = null) {

        $this->logger->info("LiabilityBO->updateByLiabilityId", [$applicationId]);

        try {

            for($i = 0; $i < count($data); $i++) {
                if(isset($data[$i]['isRemoved']) && $data[$i]['isRemoved']) {
                    LiabilityTable::query()
                    ->where('liability_id', $data[$i]['id'])
                    ->delete();                    
                } else {                    
                    $liabilityId = isset($data[$i]['id']) ? $data[$i]['id'] : null;

                    $liabilityObj = LiabilityTable::find($liabilityId);

                    if(!$liabilityObj) {
                        $liabilityObj = new LiabilityTable();
                        $liabilityObj->application_id = $applicationId;
                    }                    

                    $liabilityObj->lender = ($data[$i]['lender'] ?? '');

                    $balanceTmp = 0;
                    if (isset($data[$i]['balanceOwed'])) {
                        $balanceTmp = str_replace(',','',$data[$i]['balanceOwed']);
                    }
                    
                    $liabilityObj->balance = floatval($balanceTmp);
                    $liabilityObj->payment = (floatval($data[$i]['monthlyPayment']) ?? '');
                    $liabilityObj->payout = ($data[$i]['toBePaidOut'] ?? 'No');
                    $liabilityObj->comment = ($data[$i]['comment'] ?? '');
                    $liabilityObj->save();
                    
                }        
            }

            return true;
            
        } catch (\Exception $e) {
            $this->logger->error( 'LiabilityBO->updateLiabilityById - Update Error', [json_encode($e->getMessage())] );
            return false;
        }
    }
}
