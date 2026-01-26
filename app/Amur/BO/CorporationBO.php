<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\CorporationTable;

class CorporationBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDataByApplicationId($id) {

        $data = CorporationTable::query()
        ->where('application_id', $id)
        ->orderBy('corporation_id', 'asc')
        ->get();
        
        $corporations = array();
        foreach($data as $key => $value) {
            $corporations[] = [
                'id' => $value->corporation_id,
                'companyName' => $value->name,
                'incNumber' => $value->inc_number,
                'directors' => $value->directors,
                'phone' => $value->phone,
                'mobile' => $value->fax,
                'email' => $value->email,
                'isRemoved' => false
            ];
        }

        return $corporations;
    }

    public function store($corporations, $applicationId) {

        $this->logger->info('CorporationBO->store',[$applicationId]);

        $this->db->beginTransaction();

        try {
            foreach($corporations as $key => $corp) {

                if(isset($corp['isRemoved']) && $corp['isRemoved']) {
                    CorporationTable::query()
                    ->where('corporation_id', $corp['id'])
                    ->delete();
                } else {

                    $corpObj = CorporationTable::find($corp['id']);

                    if(!$corpObj) {
                        $corpObj = new CorporationTable();
                        $corpObj->application_id = $applicationId;
                    }

                    $corpObj->name   = ($corp['companyName'] ?? '');
                    $corpObj->inc_number = ($corp['incNumber'] ?? '');
                    $corpObj->directors = ($corp['directors'] ?? '');
                    $corpObj->phone = ($corp['phone'] ?? '');
                    $corpObj->fax = ($corp['mobile'] ?? '');
                    $corpObj->email = ($corp['email'] ?? '');
                    $corpObj->save();
                }           
            }

        } catch(\Throwable $e) {
            $this->logger->error('CorporationBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }
}
