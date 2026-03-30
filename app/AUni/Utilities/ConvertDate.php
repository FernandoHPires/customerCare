<?php

namespace App\Amur\Utilities;

class ConvertDate {

    public static function convert($date = null, $format = 'Y-m-d') {
        // format is based on database
        if( !$date || $date == "0000-00-00" ) return false; 

        $finalDate = "";
        $hold = strtotime($date);
        $finalDate = date($format, $hold); 

        return ($finalDate == "") ? false : $finalDate;
    }

    public static function moment($startDate, $endDate) {
        $diff = $startDate->diff($endDate);

        if($diff->d < 1) {
            if($diff->h < 1) {
                return $diff->i . 'm';
            } else {
                return $diff->h . 'h ' . $diff->i . 'm';
            }
        } else {
            return $startDate->format('m/d/Y g:ia');
        }
    }

}