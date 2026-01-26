<?php

namespace App\Amur\Utilities;

use App\Models\MortgageInvestorTrackingTable;
use App\Models\PeriodLocksTable;
use DateTime;

class Loan {

    public static function isLockedDate($companyId, DateTime $date) {
        $date->setTime(0, 0);

        $periodLocksTable = PeriodLocksTable::query()
        ->where('company_id', $companyId)
        ->where('locked', 'yes')
        ->where('start_date', '<=', $date)
        ->where('end_date', '>=', $date)
        ->first();

        if($periodLocksTable) {
            return true;
        }
        
        return false;
    }

    public static function getFutureLender($mortgageId) {
        $mitt = MortgageInvestorTrackingTable::query()
        ->where('mortgage_id', $mortgageId)
        ->where('committed', 'Yes')
        ->orderByRaw("if(committed = 'Yes', 0, 1)")
        ->first();

        if($mitt) {
            
            switch ($mitt->investor_id) {
                case 31:
                    $companyId = 5;
                    break;
                case 100:
                    $companyId = 182;
                    break;
                case 248:
                    $companyId = 16;
                    break;
                case 1971:
                    $companyId = 1970;
                    break;
                case 1919:
                    $companyId = 183;
                    break;                    
                default:
                    $companyId = 0;
                    break;
            }

            return [
                'investorId' => $mitt->investor_id,
                'companyId' => $companyId
            ];
            
        }

        return [
            'investorId' => 0,
            'companyId' => 0
        ];
    }


}