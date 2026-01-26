<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\BranchesTable;

class BranchBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getAllData() {

        $data = BranchesTable::query()
        ->get();

        $branches = array(
            [ "id" => "", "name" => "" ]
        );
        if($data) {
            foreach($data as $key => $value) {
                $branches[] = [
                    "id" => $value->id,
                    "name" => $value->name
                ];
            }
        }

        return $branches;
    }
}
