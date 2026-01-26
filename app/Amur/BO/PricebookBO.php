<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationTable;
use App\Models\MortgageTable;
use App\Models\Pricebook;
use DateTime;

class PricebookBO {

    private $logger;
    private $db;
    private $errors;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($company, $position, $province, $cityClassification, $display) {

        $query = "select id, start_ltv, end_ltv, interest_rate, effective_at
                    from pricebook
                   where company = ?
                     and position = ?
                     and province = ?
                     and city_classification = ?
                     and deleted_at is null
                order by start_ltv, effective_at";
        $res = $this->db->select($query, [$company, $position, $province, $cityClassification]);

        $priceBook = [];
        $currentDate = new DateTime();

        foreach($res as $value) {

            if($display == 'C') {
                $effectiveDate = new DateTime($value->effective_at);                

                if($effectiveDate > $currentDate) {
                    continue;
                }

                $key = $value->start_ltv . '-' . $value->end_ltv;

                if(isset($priceBook[$key])) {
                    if ($priceBook[$key]['effectiveAt'] > $effectiveDate) {
                        continue;
                    }
                }
                
                $priceBook[$key] = [
                    'id' => $value->id,
                    'startLtv' => $value->start_ltv, 
                    'endLtv' => $value->end_ltv,
                    'interestRate' => $value->interest_rate,
                    'effectiveAt' => $effectiveDate,
                    'isEditing' => 'no' 
                ];
            } else {
                $priceBook[] = [
                    'id' => $value->id,
                    'startLtv' => $value->start_ltv, 
                    'endLtv' => $value->end_ltv,
                    'interestRate' => $value->interest_rate,
                    'effectiveAt' => new DateTime($value->effective_at),
                    'isEditing' => 'no' 
                ];
            }           
        }

        return $priceBook;
    }

    public function newPricebook($id, $interestRate, $effectiveAt) {

        $this->db->beginTransaction();
        try {

            $pricebook = Pricebook::query()
            ->where('id', $id)
            ->first();
    
            if ($pricebook) {
                $newPricebook = $pricebook->replicate();
                $newPricebook->interest_rate = $interestRate;
                $newPricebook->effective_at  = $effectiveAt;
                $newPricebook->save();
            }

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->logger->error('PapBO->rejectTransaction', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }
    }

    public function getByApplication($applicationId, $ltv) {
        /*$adminUser = false;
        $query = "select admin from users_table where user_id = $userId";
        $res = $this->db->query($query);

        foreach($res as $r) {
            $adminUser = $r['admin'] == 'yes' ? true : false;
        }*/

        $application = ApplicationTable::find($applicationId);
        if(!$application) {
            return false;
        }

        $companyId = $application->company;
        $company = $companyId == 701 ? 'SQC' : 'ACL';

        $mortgages = MortgageTable::query()
        ->where('application_id', $applicationId)
        ->where('is_deleted', 'no')
        ->get();

        $mortgageGroup = 'NB';
        if(count($mortgages) > 0) {
            $mortgageGroup = 'PB';
        }

        $query = "select a.rural_urban, a.province,
                         (select count(*) from property_mortgages_table b where b.property_id = a.property_id and balance > 0 and payout = 'No') senior_mortgages,
                         (select count(*) from mortgage_table c
                            join mortgage_properties_table d 
                                    on c.mortgage_id = d.mortgage_id
                                   and d.property_id = a.property_id
                           where c.is_deleted = 'no'
                             and c.current_balance > 0
                             and c.tobe_paidout = 'no') alpine_mortgage
                    from property_table a
                   where a.application_id = ?
                     and a.part_of_security = 'Yes'
                order by a.idx";
        $res = $this->db->select($query,[$applicationId]);

        if(count($res) == 0) {
            $this->errors[] = 'Part of security is not selected for the property';
            return false;
        }

        $province = '';
        $position = '';
        $cityClassification = '';
        foreach($res as $value) {
            $province = $value->province;
            $position = ($value->senior_mortgages == 0 && $value->alpine_mortgage == 0) ? '1st' : '2nd';

            if($value->rural_urban == 'Rural') {
                $cityClassification = 'R';
            } elseif($value->rural_urban == 'Urban') {
                $cityClassification = 'U';
            } else {
                $cityClassification = $value->rural_urban;
            }

            break;
        }

        if(!in_array($cityClassification, array('R', 'U'))) {
            $this->errors[] = 'Needs to select Rural or Urban for the property';
            return false;
        }

        $interestRate = $this->getInterestRate(
            $company,
            $ltv,
            $position,
            $province,
            $cityClassification
        );

        if(is_null($interestRate)) {
            $this->errors[] = 'Interest rate not found';
            return false;
        }

        return [
            'interestRate' => $interestRate,
            'ltv' => (float) $ltv,
            'position' => $position,
            'province' => $province,
            'cityClassification' => $cityClassification == 'U' ? 'Urban' : 'Rural',
            'companyId' => $companyId,
            'company' => $company,
            'mortgageGroup' => $mortgageGroup
        ];
    }

    public function getInterestRate(
        $company,
        $ltv,
        $position,
        $province,
        $cityClassification
    ) {
        $query = "select a.interest_rate
                    from pricebook a
                   where a.company = ?
                     and a.start_ltv <= ?
                     and (a.end_ltv + 1) > ?
                     and a.position = ?
                     and a.province = ?
                     and a.city_classification = ?
                     and effective_at <= date(now())
                     and a.deleted_at is null
                order by effective_at desc";
        $res = $this->db->select($query,[$company, $ltv, $ltv, $position, $province, $cityClassification]);

        if(count($res) > 0) {
            return (float) $res[0]->interest_rate;
        }

        return null;
    }

    public function getErrors() {
        return $this->errors;
    }

}
