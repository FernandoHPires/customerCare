<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\PapSla;

class PapSlaBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function process() {
        $papSlas = PapSla::get();

        foreach($papSlas as $key => $papSla) {
            switch($papSla->category_id) {
                case 510:
                    $this->papBank($papSla);
                    break;
                
                default:
                    # code...
                    break;
            }
        }
    }

    public function papBank($papSla) {
        $query = "select a.id, a.updated_at, b.mortgage_code
                    from pap_bank a
                    join mortgage_table b on a.mortgage_id = b.mortgage_id
                   where a.status = 'R'
                     and date_add(updated_at, interval ? hour) < now()
                order by a.updated_at";
        $res = $this->db->select($query,[$papSla->sla]);

        $hasOutstandingSla = false;
        $files = '';
        foreach($res as $key => $value) {
            $hasOutstandingSla = true;
            $files .=  $value->mortgage_code . '<br>';
        }

        if($hasOutstandingSla) {
            $toAddresses = explode(',',$papSla->escalates_to);
            $subject = 'Bank Information Rejected';
            $body = "Hi,<br><br>Some bank information updates were rejected and not fixed yet by your team, can you please fix it as soon as possible.<br><br>Files:<br>$files";

            if(env('APP_ENV') == 'production') {
                Utils::sendEmail($toAddresses, $subject, $body);
            }
        }
    }
}