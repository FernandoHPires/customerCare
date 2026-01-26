<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\OtherIncomeTable;
use App\Models\PresentEmployerTable;
use App\Models\PreviousEmployerTable;

class IncomeBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getPresentEmployerById($applicationId) {
        $sql = "select d.present_employer_id present_employer1_id, concat(b.f_name, ' ', b.l_name) spouse1_name, d.name name1, d.position position1, d.years years1,
                       d.status status1, d.phone phone1, d.income_hourly income_hourly1, d.income_monthly income_monthly1, d.self_employed self_employed1, d.poi_received poi_received1, b.spouse_id spouse1_id,

                       e.present_employer_id present_employer2_id, concat(c.f_name, ' ', c.l_name) spouse2_name, e.name name2, e.position position2, e.years years2,
                       e.status status2, e.phone phone2, e.income_hourly income_hourly2, e.income_monthly income_monthly2, e.self_employed self_employed2, e.poi_received poi_received2, c.spouse_id spouse2_id
                  from applicant_table a
             left join spouse_table b on a.spouse1_id = b.spouse_id
             left join spouse_table c on a.spouse2_id = c.spouse_id
             left join present_employer_table d on b.spouse_id = d.spouse_id
             left join present_employer_table e on c.spouse_id = e.spouse_id
                 where a.application_id = ?
              order by a.applicant_id";
        $res = $this->db->select($sql,[$applicationId]);

        $data = array();
        foreach($res as $key => $value) {
            if(!empty(trim($value->spouse1_name))) {
                $data[] = [
                    'id' => $value->present_employer1_id,
                    'spouseId' => $value->spouse1_id,
                    'spouseName' => $value->spouse1_name,
                    'employerName' => $value->name1,
                    'position' => $value->position1,
                    'years' => $value->years1,
                    'phone' => $value->phone1,
                    'incomeHourly' => $value->income_hourly1,
                    'status' => $value->status1,
                    'incomeMonthly' => $value->income_monthly1,
                    'selfEmployed' => empty($value->self_employed1) || $value->self_employed1 == 'no' ? false : true,
                    'poiReceived' => empty($value->poi_received1) || $value->poi_received1 == 'no' ? false : true,
                ];
            }

            if(!empty(trim($value->spouse2_name))) {
                $data[] = [
                    'id' => $value->present_employer2_id,
                    'spouseId' => $value->spouse2_id,
                    'spouseName' => $value->spouse2_name,
                    'employerName' => $value->name2,
                    'position' => $value->position2,
                    'years' => $value->years2,
                    'phone' => $value->phone2,
                    'incomeHourly' => $value->income_hourly2,
                    'status' => $value->status2,
                    'incomeMonthly' => $value->income_monthly2,
                    'selfEmployed' => empty($value->self_employed2) || $value->self_employed2 == 'no' ? false : true,
                    'poiReceived' => empty($value->poi_received2) || $value->poi_received2 == 'no' ? false : true,
                ];
            }
        }

        return $data;
    }  

    public function getPresentEmployer($applicationId) {

        $query = 'select b.spouse1_id, b.spouse2_id
                    from application_table a
                    join applicant_table b on a.application_id = b.application_id
                   where a.application_id = ?';
        $spouses = $this->db->select($query,[$applicationId]);

        $spousesIn = array();
        foreach ($spouses as $key => $value) {
            if($value->spouse1_id > 0) {
                $spousesIn[] = $value->spouse1_id;
            }

            if($value->spouse2_id > 0) {
                $spousesIn[] = $value->spouse2_id;
            }
        }

        $data = array();

        if ($spousesIn) {
            $query = 'select concat(a.f_name, " ", a.l_name) spouse_name, a.spouse_id,
                             b.present_employer_id, b.name, b.position, b.years, b.phone, b.income_hourly,
                             b.status, b.income_monthly, b.self_employed, b.poi_received
                        from spouse_table a
                   left join present_employer_table b on b.spouse_id = a.spouse_id 
                       where a.spouse_id  in (' . implode(',',$spousesIn) . ') '.
                   'order by a.spouse_id';
            $res = $this->db->select($query);

            $spouseIdTmp = 0;
            foreach ($res as $key => $value) {

                if(!empty(trim($value->spouse_name))) {

                    $enabledDelete = true;
                    if ($value->spouse_id != $spouseIdTmp) {
                        $spouseIdTmp = $value->spouse_id;
                        $enabledDelete = false;
                    }

                    $data[] = [
                        'id' => $value->present_employer_id,
                        'spouseId' => $value->spouse_id,
                        'spouseName' => $value->spouse_name,
                        'employerName' => $value->name,
                        'position' => $value->position,
                        'years' => $value->years,
                        'phone' => $value->phone,
                        'incomeHourly' => $value->income_hourly,
                        'status' => $value->status,
                        'incomeMonthly' => $value->income_monthly,
                        'selfEmployed' => empty($value->self_employed) || $value->self_employed == 'no' ? false : true,
                        'poiReceived' => empty($value->poi_received) || $value->poi_received == 'no' ? false : true,
                        'enabledDelete' => $enabledDelete,
                        'isRemoved' => false
                    ];
                }
            }
        }

        return $data;
    }

    public function getPreviousEmployerById($applicationId) {
        $sql = "select d.previous_employer_id previous_employer1_id, concat(b.f_name, ' ', b.l_name) spouse1_name,
                       d.name name1, d.position position1, d.years years1, b.spouse_id spouse1_id,

                       e.previous_employer_id previous_employer2_id, concat(c.f_name, ' ', c.l_name) spouse2_name,
                       e.name name2, e.position position2, e.years years2, c.spouse_id spouse2_id
                  from applicant_table a
             left join spouse_table b on a.spouse1_id = b.spouse_id
             left join spouse_table c on a.spouse2_id = c.spouse_id
             left join previous_employer_table d on b.spouse_id = d.spouse_id
             left join previous_employer_table e on c.spouse_id = e.spouse_id
                    where a.application_id = ?
                    order by a.applicant_id";
        $res = $this->db->select($sql,[$applicationId]);

        $data = array();
        foreach($res as $key => $value) {
            if(!empty(trim($value->spouse1_name))) {
                $data[] = [
                    'id' => $value->previous_employer1_id,
                    'spouseId' => $value->spouse1_id,
                    'spouseName' => $value->spouse1_name,
                    'employerName' => $value->name1,
                    'position' => $value->position1,
                    'years' => $value->years1,
                ];
            }

            if(!empty(trim($value->spouse2_name))) {
                $data[] = [
                    'id' => $value->previous_employer2_id,
                    'spouseId' => $value->spouse2_id,
                    'spouseName' => $value->spouse2_name,
                    'employerName' => $value->name2,
                    'position' => $value->position2,
                    'years' => $value->years2,
                ];
            }
        }

        return $data;
    } 

    public function getPreviousEmployer($applicationId) {

        $query = "select b.spouse1_id, b.spouse2_id
                    from application_table a
                    join applicant_table b on a.application_id = b.application_id
                   where a.application_id = ?";
        $spouses = $this->db->select($query,[$applicationId]);

        $spousesIn = array();
        foreach($spouses as $key => $value) {
            if($value->spouse1_id > 0) {
                $spousesIn[] = $value->spouse1_id;
            }
            if($value->spouse2_id > 0) {
                $spousesIn[] = $value->spouse2_id;
            }
        }

        $data = array();

        if(count($spousesIn) > 0) {
            $query = 'select concat(a.f_name, " ", a.l_name) spouse_name, a.spouse_id, b.previous_employer_id,
                             b.name, b.position, b.years
                        from spouse_table a
                   left join previous_employer_table b on b.spouse_id = a.spouse_id 
                       where a.spouse_id  in (' . implode(',',$spousesIn) . ')'.
                  ' order by a.spouse_id';
            $res = $this->db->select($query);

            $spouseIdTmp = 0;
            foreach($res as $value) {
                if(!empty(trim($value->spouse_name))) {
                    $enabledDelete = true;
                    if($value->spouse_id != $spouseIdTmp) {
                        $spouseIdTmp = $value->spouse_id;
                        $enabledDelete = false;
                    }

                    $data[] = [
                        'id' => $value->previous_employer_id,
                        'spouseId' => $value->spouse_id,
                        'spouseName' => $value->spouse_name,
                        'employerName' => $value->name,
                        'position' => $value->position,
                        'years' => $value->years,
                        'enabledDelete' => $enabledDelete,
                        'isRemoved' => false
                    ];
                }
            }
        }

        return $data;
    }


    public function updatePresentById($id = null, $spouseId = null, $data = []) {
        try {
            if( $spouseId == null ) return false; // required field

            if( $id == null || $id == "" ) {
                // insert employment
                $present = new PresentEmployerTable;
                $present->spouse_id = $spouseId;
                $present->name = $data['employerName'];
                $present->position = $data['jobTitle'];
                $present->status = $data['status'];
                $present->years = $data['employmentYears'];
                $present->phone = $data['phone'];
                $present->extension = $data['ext']; 
                $present->income_hourly = $data['hourlyRate'];
                $present->income_monthly = floatval($data['grossIncome']);
                $present->save();

            } else {
                // update employment
                $presentInfo = [
                    "name" => $data['employerName'],
                    "position" => $data['jobTitle'],
                    "status" => $data['status'],
                    "years" => $data['employmentYears'],
                    "phone" => $data['phone'],
                    "extension" => $data['ext'],
                    "income_hourly" => $data['hourlyRate'],
                    "income_monthly" => floatval($data['grossIncome'])
                ];
    
                PresentEmployerTable::query()
                ->where('present_employer_id', $id)
                ->update($presentInfo);
            }

            return true;
        }catch(\Exception $e){
            $this->logger->error( 'EmploymentBO->updatePresentById - Update Error', [json_encode($e)] );
            return false;
        }
    }

    public function updatePreviousById($id = null, $spouseId = null, $data = []) {
        try {
            if( $spouseId == null ) return false; // required field

            if( $id == null || $id == "" ) {
                // insert
                $previous = new PreviousEmployerTable;
                $previous->spouse_id = $spouseId;
                $previous->name = $data['employerName'];
                $previous->position = $data['position'];
                $previous->years = $data['yearsOfStay'];
                $previous->save();

            } else {
                // update
                $previousInfo = [
                    "name" => $data['employerName'],
                    "position" => $data['position'],
                    "years" => $data['yearsOfStay']
                ];

                PreviousEmployerTable::query()
                ->where('previous_employer_id', $id)
                ->update($previousInfo);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error( 'EmploymentBO->updatePreviousById - Update Error', [json_encode($e)] );
            return false;
        }
    }

    public function getOtherIncomeById($id) {

        $data = OtherIncomeTable::query()
        ->where('application_id', $id)
        ->orderBy('other_income_id', 'asc')
        ->get();

        $otherIncomes = array();
        foreach($data as $key => $value) {
            $otherIncomes[] = [
                'id' => $value->other_income_id,
                'source' => $value->source,
                'monthly' => $value->monthly
            ];
        }
        
        return $otherIncomes;
    }

    public function updateByOtherIncomeId($id = null, $applicationId = null, $other = []) {
        try {
            if( $applicationId == null ) return false;

            if( $id == null || $id == "" ) {
                
                $otherIncome = new OtherIncomeTable();
                $otherIncome->application_id = $applicationId;
                $otherIncome->source = $other['source'];
                $otherIncome->monthly = $other['income'];
                $otherIncome->save();

            } else {
                $otherData = [
                    "source" => $other['source'],
                    "monthly" => $other['income']
                ];
    
                OtherIncomeTable::query()
                ->where('other_income_id', $id)
                ->update($otherData);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error( 'OtherIncomeBO->updateByOtherIncomeId - Update Error', [json_encode($e)] );
            return false;
        }
    }

    public function storeOtherIncome($otherIncomes, $applicationId) {

        $this->db->beginTransaction();
        try {
            foreach($otherIncomes as $key => $otherIncome) {
                if(isset($otherIncome['isRemoved']) && $otherIncome['isRemoved']) {
                    OtherIncomeTable::query()
                    ->where('other_income_id', $otherIncome['id'])
                    ->delete();
                } else {
                    $otherIncomeObj = OtherIncomeTable::find($otherIncome['id']);

                    if((!isset($otherIncome['source']) || empty($otherIncome['source'])) && !$otherIncomeObj) {
                        continue;
                    }

                    if(!$otherIncomeObj) {
                        $otherIncomeObj = new OtherIncomeTable();
                        $otherIncomeObj->application_id = $applicationId;
                    }

                    $monthlyTmp = 0;
                    if(isset($otherIncome['monthly'])) {
                        $monthlyTmp = str_replace(',','',$otherIncome['monthly']);
                    }

                    $otherIncomeObj->source = empty($otherIncome['source']) ? '' : $otherIncome['source'];
                    $otherIncomeObj->monthly = empty($monthlyTmp) ? 0 : $monthlyTmp;
                    $otherIncomeObj->save();
                }           
            }

        } catch(\Throwable $e) {
            $this->logger->error('IncomeBO->storeOtherIncome', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function storePreviousEmployerTable($previousEmployments) {

        $this->db->beginTransaction();
        try {
            foreach($previousEmployments as $key => $previousEmployment) {
                if(isset($previousEmployment['isRemoved']) && $previousEmployment['isRemoved']) {
                    PreviousEmployerTable::query()
                    ->where('previous_employer_id', $previousEmployment['id'])
                    ->delete();

                } else {
                    if(!isset($previousEmployment['spouseId']) || $previousEmployment['spouseId'] == 0) {
                        continue;
                    }

                    $previousEmploymentObj = PreviousEmployerTable::find($previousEmployment['id']);

                    if(!$previousEmploymentObj) {
                        $previousEmploymentObj = new PreviousEmployerTable();
                    }
                    
                    $previousEmploymentObj->spouse_id = $previousEmployment['spouseId'];
                    $previousEmploymentObj->name = $previousEmployment['employerName'] ?? '';
                    $previousEmploymentObj->position = $previousEmployment['position'] ?? '';
                    $previousEmploymentObj->years = $previousEmployment['years'] ?? '';
                    $previousEmploymentObj->save();
                }
            }
        } catch(\Throwable $e) {
            $this->logger->error('IncomeBO->storepreviousEmployerTable', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;

    }

    public function storePresentEmployerTable($presentEmployments) {

        $this->db->beginTransaction();
        try {
            foreach($presentEmployments as $key => $presentEmployment) {
                if(isset($presentEmployment['isRemoved']) && $presentEmployment['isRemoved']) {
                    PresentEmployerTable::query()
                    ->where('present_employer_id', $presentEmployment['id'])
                    ->delete();

                } else {
                    if(!isset($presentEmployment['spouseId']) || $presentEmployment['spouseId'] == 0) {
                        continue;
                    }

                    $presentEmploymentObj = PresentEmployerTable::find($presentEmployment['id']);

                    if(!$presentEmploymentObj) {
                        $presentEmploymentObj = new PresentEmployerTable();
                    }

                    $incomeMonthlyTmp = 0;
                    if (isset($presentEmployment['incomeMonthly'])) {
                        $incomeMonthlyTmp = str_replace(',','',$presentEmployment['incomeMonthly']);
                    }

                    $presentEmploymentObj->spouse_id = $presentEmployment['spouseId'];
                    $presentEmploymentObj->name = $presentEmployment['employerName'] ?? '';
                    $presentEmploymentObj->position = $presentEmployment['position'] ?? '';
                    $presentEmploymentObj->years = $presentEmployment['years'] ?? '';
                    $presentEmploymentObj->phone = $presentEmployment['phone'] ?? '';
                    $presentEmploymentObj->income_hourly = $presentEmployment['incomeHourly'] ?? 0;
                    $presentEmploymentObj->status = $presentEmployment['status'] ?? '';
                    $presentEmploymentObj->income_monthly = $incomeMonthlyTmp;
                    $presentEmploymentObj->self_employed = $presentEmployment['selfEmployed'] ? 'yes' : '';
                    $presentEmploymentObj->poi_received = $presentEmployment['poiReceived'] ? 'yes' : '';
                    $presentEmploymentObj->save();
                }
            }
        } catch(\Throwable $e) {
            $this->logger->error('IncomeBO->storePresentEmployerTable', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

}
