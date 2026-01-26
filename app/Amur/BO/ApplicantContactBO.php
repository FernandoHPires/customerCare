<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\ApplicantContactsTable;

class ApplicantContactBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getDataApplicantById($id) {

        $appObj = ApplicantContactsTable::query()
        ->where('applicant_id', $id)
        ->get();
        
        return $appObj;

    }
}
