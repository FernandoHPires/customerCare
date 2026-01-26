<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\VehicleTable;

class VehicleBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getDataByApplicationId($id) {

        $data = VehicleTable::query()
        ->where('application_id', $id)
        ->orderBy('vehicle_id', 'asc')
        ->get();

        $vehicles = array();
        foreach($data as $key => $value) {
            $vehicles[] = [
                'id' => $value->vehicle_id,
                'model' => $value->make_model_year,
                'ownLease' => $value->own_lease,
                'financed' => $value->financed,
                'expiry' => empty(trim($value->Expiry)) ? 'n/a' : $value->Expiry,
                'isRemoved' => false
            ];
        }
        
        return $vehicles;
    }

    public function updateByVehicleId($vehicles = [], $applicationId = null) {

        $this->logger->info('VehicleBO->updateByVehicleId',[$applicationId]);

        try {

            for( $i = 0; $i < count($vehicles); $i++ ) {

                if(isset($vehicles[$i]['isRemoved']) && $vehicles[$i]['isRemoved']) {
                    VehicleTable::query()
                    ->where('vehicle_id', $vehicles[$i]['id'])
                    ->delete();
                } else {
                    $vehicleId = isset($vehicles[$i]['id']) ? $vehicles[$i]['id'] : null;

                    $vehicleObj = VehicleTable::find($vehicleId);

                    if(!$vehicleObj) {
                        $vehicleObj = new VehicleTable();
                        $vehicleObj->application_id = $applicationId;
                    }

                    $vehicleObj->make_model_year = isset($vehicles[$i]['model']) ? $vehicles[$i]['model'] : "";
                    $vehicleObj->own_lease = isset($vehicles[$i]['ownLease']) ? $vehicles[$i]['ownLease'] : "";
                    $vehicleObj->financed = isset($vehicles[$i]['financed']) ? $vehicles[$i]['financed'] : "";
                    $vehicleObj->Expiry = isset($vehicles[$i]['expiry']) ? $vehicles[$i]['expiry'] : 'n/a';
                    $vehicleObj->serial_vin_number = "";
                    $vehicleObj->save();
                }
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error( 'VehicleBO->updateByVehicleId - Update Error', [json_encode($e)] );
            return false;
        }
    }

}
