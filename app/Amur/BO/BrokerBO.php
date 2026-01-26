<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\Brokers;

class BrokerBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getAllData() {
        $data = Brokers::query()
        ->join('broker_office', 'broker.broker_office_id', '=', 'broker_office.broker_office_id')
        ->join('national_broker', 'broker_office.national_broker_id', '=', 'national_broker.national_broker_id')
        ->select('broker.broker_id', 'broker.name','broker_office.broker_office_id', 'broker_office.name AS broker_office_name', 'national_broker.national_broker_id', 'national_broker.name AS national_broker_name', 'cps')
        ->where('broker.deleted_at', NULL)
        ->orderBy('broker.name')
        ->get();

        $brokers = array(
            [ "id" => "", "fullName" => "" , "brokerOffice_name" => "", "nationalBroker_name" => "", "cps" => "" ]
        );

        foreach($data ?? [] as $value) {
            $brokers[] = [
                "id" => $value->broker_id,
                "fullName" => $value->name,
                "brokerOfficeId" => $value->broker_office_id,
                "brokerOfficeName" => $value->broker_office_name,
                "nationalBrokerId" => $value->national_broker_id,
                "nationalBrokerName" => $value->national_broker_name,
                "cps" => $value->cps
            ];
        }

        return $brokers;
    }
}
