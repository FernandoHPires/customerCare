<?php

namespace App\AUni\Utilities;

use App\AUni\Bean\DB;
use App\AUni\Bean\Logger;
use Illuminate\Support\Facades\Log;

class Utils {

    public static function sendEmail($toAddresses, $subject, $body, $provider = 'local', $attachments = [], $cc = [], $bcc = []) {
        Email::send($toAddresses, $subject, $body, $cc, $bcc, $attachments ?? []);
    }

    public static function processdata($fields = []) {

        try {
            foreach( $fields as $key => $value ) {
                if( is_object($value) || is_array($value) ) {
                    $cur = $fields[$key];
                    foreach ( $value as $k => $v ) {
                        if( is_numeric($v) ) {
                            $cur[$k] = $v;
                        } else {
                            $cur[$k] = isset($v) ? $v : "";
                        }
                    }
                    $fields[$key] = $cur;
                } else {
                    if( is_numeric($value) ) {
                        $fields[$key] = $value;
                    } else {
                        $fields[$key] = isset($value) ? $value : "";
                    }
                }

            }

            return $fields;
        } catch( \Exception $e ) {
            Log::error('Utils->processdata', [json_encode($e)]);
        }
    }

    public static function toFloat($number) {
        $number = str_replace('$','',$number);
        $number = str_replace(',','',$number);
        $number = str_replace('"','',$number);

        return (float) $number;
    }

    public static function toOrdinal($number) {
        $number = substr($number,0,1); //if 1st, 2nd, 3th, ...
        
        $array = ['', 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth'];

        return $array[$number] ?? '';
    }

    public static function toOrdinalAbbr($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if((($number % 100) >= 11) && (($number%100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }

    public static function getFundNames() {
        $fundNames = array();
        $fundNames['Ryan Mortgage Income Fund Inc.'] = 'Amur Capital Income Fund Inc.';
        $fundNames['Ryan Mortgage'] = 'Amur Capital Income Fund';
        $fundNames['RMIF'] = 'ACIF';
        $fundNames['RMC'] = 'ACIF';
        $fundNames['Manchester Investments Inc.'] = 'Amur Capital Conservative Income Fund Inc.';
        $fundNames['Manchester Investments Inc'] = 'Amur Capital Conservative Income Fund Inc.';
        $fundNames['Manchester Investments'] = 'Amur Capital Conservative Income Fund';
        $fundNames['MII'] = 'ACCIF';
        $fundNames['Blue Stripe Financial Ltd.'] = 'Amur Capital High Yield Fund Inc.';
        $fundNames['Blue Stripe Financial'] = 'Amur Capital High Yield Fund';
        $fundNames['BSF'] = 'ACHYF';

        return $fundNames;
    }

    public static function oneLineAddress($unitNumber, $streetNumber, $streetName, $streetType, $streetDirection, $city, $province, $postalCode) {

        $addr = "";

        if (strcmp($unitNumber, "") != 0) {
            $addr .= $unitNumber . "-";
        }

        $addr .= $streetNumber . " " . $streetName . " " . $streetType;

        if (strcmp($streetDirection, "N/A") != 0) {
            $addr .= " " . $streetDirection;
        }

        $addr .= ", " . $city . " " . $province . " " . $postalCode;

        return htmlspecialchars($addr);
    }

    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    public static function formatAddress($unitNumber, $streetNumber, $streetName, $streetType, $direction, $city, $province, $postalCode, $poBoxNumber, $station, $ruralRoute, $site, $compartment) {
                       
                       
        $address = "";

        //unit number
        if(strcmp($unitNumber, "") != 0)
            $address .= $unitNumber."-";

        //street address
        $address .= $streetNumber." ".$streetName." ".$streetType;

        //optional street direction
        if(strcmp($direction, "N/A") != 0)
            $address .= " ".$direction;

        //end of first line
        $address .= " ";

        //optional po box
        if(strcmp($poBoxNumber, "") != 0) {
            $address .= "PO Box ".$poBoxNumber." ";

            if(strcmp($station, "") != 0) {
                $address .= "STN ".$station;
            }

            $address .= " ";
        }

        //optional site compartment
        if(strcmp($site, "") != 0) {
            $address .= "SITE ".$site." COMPARTMENT ".$compartment;
            $address .= " ";
        }

        //optional rural route
        if(strcmp($ruralRoute, "") != 0) {
            $address .= "RR ".$ruralRoute." ";

            if(strcmp($station, "") != 0) {
                $address .= "STN ".$station;
            }

            $address .= " ";
        }

        //city province postal code
        $address .= $city." ".$province."  ".$postalCode;

        // $url = "https://www.google.ca/maps/place/".$streetNumber."+".$streetName."+".$streetType."+".$city."+".$province."+".$postalCode."+Canada";
        // $address = '<i class="fa fa-map-marker" style="xfont-size:60px;color:red;"></i><A target="map" HREF="'.$url.'" ><u>'.$address.'</u></A>';

        return $address;
    }

    public static function to18Id($inputId) {
        $suffix = '';
        for($i = 0; $i < 3; $i++) {
            $flags = 0;

            for($j = 0; $j < 5; $j++) {
                $start = $i * 5 + $j;
                $end = ($i * 5 + $j + 1) - $start;
                $c = substr($inputId, $start, $end);

                if(ctype_upper($c)  && $c >= 'A' && $c <= 'Z') {
                    $flags = $flags + (1 << $j);
                }
            }

            if($flags <= 25) {
                $suffix .= substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ',$flags,1);
            } else {
                $suffix .= substr('012345', $flags - 26, 1);
            }
        }

        return $inputId . $suffix;
    }

    public static function convertCompanyToInvestor($companyId) {
        $mic = array(1 => 0, 5 => 31, 16 => 248, 182 => 100, 1970 => 1971, 200 => 1993, 201 => 1991, 202 => 1992);
        
        return $mic[$companyId] ?? false;
    }

    public static function micCompanies() {
        return array(31, 100, 248, 1919, 1971, 2042);
    }

    public static function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 10) {
            return '('.substr($phone, 0, 3).') '.substr($phone, 3, 3).'-'.substr($phone, 6);
        } else {
            return $phone;
        }

    }

    public static function toColumn($c) {
        $c = intval($c);
        $c++;

        if($c <= 0) {
            return '';
        }

        $letter = '';

        while($c != 0) {
            $p = ($c - 1) % 26;
            $c = intval(($c - $p) / 26);
            $letter = chr(65 + $p) . $letter;
        }

        return $letter;
    }

    public static function convertDecisionTimelineId($id) {

        $db = new DB();
        $sql = "select a.id, a.name, a.category_id
                  from category_value a
                 where a.category_id = 57
                   and a.live = 'Y'
                   and a.id = ?";
        $res = $db->select($sql,[$id]);

        if(count($res) > 0) {
            return $res[0]->name;
        } else {
            return null;
        }
    }

    public static function convertDecisionTimelineName($name) {

        $db = new DB();

        $sql = "select a.id, a.name, a.category_id
                  from category_value a
                 where a.category_id = 57
                   and a.live = 'Y'
                   and a.name = ?";
        $res = $db->select($sql,[$name]);

        if(count($res) > 0) {
            return $res[0]->id;
        } else {
            return null;
        }
    }

    public static function convertCategoryValue($id, $categoryId) {

        $db = new DB();
        $sql = "select a.id, a.name, a.category_id
                  from category_value a
                 where a.category_id = ?
                   and a.live = 'Y'
                   and a.id = ?";
        $res = $db->select($sql,[$categoryId,$id]);

        if(count($res) > 0) {
            return $res[0]->name;
        } else {
            return null;
        }
    }
}
