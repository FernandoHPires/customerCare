<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\AlpineCompaniesTable;

class AlpineCompaniesBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getAllData() {

        $data = AlpineCompaniesTable::query()
        ->orderBy('abbreviation', 'ASC')
        ->get();

        $companies = array();
        if($data) {
            foreach($data as $key => $value) {
                $companies[] = [
                    "id" => $value->id,
                    "name" => $value->name
                ];
            }
        }

        return $companies;

    }
}
