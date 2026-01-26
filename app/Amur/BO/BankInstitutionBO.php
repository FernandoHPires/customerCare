<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\PapInstitution;

class BankInstitutionBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function showByCode($code) {
        $papInstitution = PapInstitution::where('code', $code)->first();

        if($papInstitution) {
            return [
                'id' => $papInstitution->id,
                'code' => $papInstitution->code,
                'name' => $papInstitution->name
            ];
        } else {
            return false;
        }
    }
}