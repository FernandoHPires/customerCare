<?php

namespace App\Amur\Bean;

class Transaction {
    public $id;
    public $applicationId;
    public $folder;
    public $mortgageId;
    public $mortgageCode;
    public $requestType;
    public $requestTypeId;
    public $status;
    public $statuses;
    public $clientName;
    public $createdBy;
    public $createdAt;
    public $isAutomatedCaft;
    public $isDeferral;
    public $isAdvance;

    public $transit;
    public $institution;
    public $account;

    public $companyId;
    
    public $transactionDetails = array();
}