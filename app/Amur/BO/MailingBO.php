<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Bean\SalesforceIntegration;
use App\Models\MailingTable;
use App\Amur\BO\PropertyBO;

class MailingBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDataByApplicationId($applicationId) {

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $recipientsOptions = $propertyBO->getTitleHolders($applicationId);

        $data = MailingTable::query()
        ->where('application_id', $applicationId)
        ->orderBy('mailing_id')
        ->get();

        $mailings = array();
        foreach($data as $key => $value) {
            $mailings[] = [
                'id' => $value->mailing_id,
                'type' => $value->type,
                'recipients' => $value->recipients,
                'unitNumber' => $value->unit_number,
                'unitType' => $value->unit_type,
                'streetNumber' => $value->street_number,
                'streetName' => $value->street_name,
                'streetType' => $value->street_type,
                'streetDirection' => $value->street_direction,
                'city' => $value->city,
                'province' => $value->province,
                'postalCode' => $value->postal_code,
                'poNumber' => $value->box_number,
                'station' => $value->station,
                'rrNumber' => $value->rr_number,
                'site' => $value->site,
                'compartment' => $value->compartment,
                'other' => $value->other,
                'howLong' => $value->how_long,
                'recipientsOptions' => $recipientsOptions,
                'isRemoved' => false
            ];
        }
        
        return $mailings;
    }

    /*public function updateByMailingById($id = null, $applicationId = null, $address = []) {
        if( ($id == null || $id == "") && $applicationId != null  ) {
            // insert
            $mailing = new MailingTable;
            $mailing->application_id = (int) $applicationId;
            $mailing->type = "Mailing";
            $mailing->unit_number = $address['unitNo'];
            $mailing->unit_type = $address['unitType'];
            $mailing->recipients = $address['mailingName'];
            $mailing->street_number = $address['streetNumber'];
            $mailing->street_name = $address['streetName'];
            $mailing->street_type = $address['streetType'];
            $mailing->street_direction = $address['direction'];
            $mailing->city = $address['city'];
            $mailing->province = $address['province'];
            $mailing->postal_code = $address['postalCode'];
            $mailing->station = $address['station'];
            $mailing->compartment = $address['comp'];
            $mailing->rr_number = $address['rr'];
            $mailing->box_number = $address['pobox'];
            $mailing->how_long = $address['howLong'];
            $mailing->other = $address['otherFormat'];
            $mailing->site = $address['site'];
            $mailing->save();
            
        } else {
            // update
            $mailingAddress = [
                "unit_number" => $address['unitNo'],
                "unit_type" => $address['unitType'],
                "recipients" => $address['mailingName'],
                "street_number" => $address['streetNumber'],
                "street_name" => $address['streetName'],
                "street_type" => $address['streetType'],
                "city" => $address['city'],
                "province" => $address['province'],
                "postal_code" => $address['postalCode'],
                "street_direction" => $address['direction'],
                "station" => $address['station'],
                "compartment" => $address['comp'],
                "rr_number" => $address['rr'],
                "box_number" => $address['pobox'],
                "how_long" => $address['howLong'],
                //"type" => $address['type'],
                "other" => $address['otherFormat']
            ];

            MailingTable::query()
            ->where('mailing_id', $id)
            ->update($mailingAddress);
        }

        return true;
    }*/

    public function storeMailingsSF($opportunityId, $mailings) {
        $applicationId = null;
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {
            $applicationId = $sfi->getObjectId();
        }

        if(is_null($applicationId) || empty($applicationId)) return false;

        foreach($mailings as $key => $mailing) {
            $mailingTable = MailingTable::find($mailing['id']);

            if(!$mailingTable) {
                $mailingTable = new MailingTable();
                $mailingTable->application_id = $applicationId;
                $mailingTable->box_number = '';
                $mailingTable->station = '';
                $mailingTable->rr_number = '';
                $mailingTable->site = '';
                $mailingTable->compartment = '';
                $mailingTable->how_long = '';
            }

            $mailingTable->type = empty($mailing['type']) ? 'Mailing' : $mailing['type'];
            $mailingTable->recipients = empty($mailing['recipients']) ? '' : $mailing['recipients'];
            $mailingTable->unit_number = empty($mailing['unitNumber']) ? '' : $mailing['unitNumber'];
            $mailingTable->unit_type = empty($mailing['unitType']) ? '' : $mailing['unitType'];
            $mailingTable->street_number = empty($mailing['streetNumber']) ? '' : $mailing['streetNumber'];
            $mailingTable->street_name = empty($mailing['streetName']) ? '' : $mailing['streetName'];
            $mailingTable->street_type = empty($mailing['streetType']) ? '' : $mailing['streetType'];
            $mailingTable->street_direction = empty($mailing['streetDirection']) ? '' : $mailing['streetDirection'];
            $mailingTable->city = empty($mailing['city']) ? '' : $mailing['city'];
            $mailingTable->province = empty($mailing['province']) ? '' : $mailing['province'];
            $mailingTable->postal_code = empty($mailing['postalCode']) ? '' : $mailing['postalCode'];
            $mailingTable->other = empty($mailing['other']) ? '' : $mailing['other'];
            $mailingTable->save();
        }
    }

    public function destroyMailingsSF($id) {
        $mailingTable = MailingTable::find($id);

        if(!$mailingTable) return false;

        $mailingTable->delete();

        return true;
    }

    public function getMailingsSF($opportunityId) {

        $applicationId = null;
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {
            $applicationId = $sfi->getObjectId();
        }

        if(is_null($applicationId) || empty($applicationId)) return false;

        $mailings = $this->getDataByApplicationId($applicationId);

        return $mailings;
    }

    public function getTitleHoldersSF($opportunityId) {
        $applicationId = null;
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {
            $applicationId = $sfi->getObjectId();
        }

        if(is_null($applicationId) || empty($applicationId)) return false;

        $propertyBO = new PropertyBO($this->logger, $this->db);
        $titleHolders = $propertyBO->getTitleHolders($applicationId);

        return $titleHolders;
    }

    public function store($mailings, $applicationId) {

        $this->logger->info('MailingBO->store',[$applicationId]);

        $this->db->beginTransaction();
        try {
            foreach($mailings as $key => $mailing) {

                if(isset($mailing['isRemoved']) && $mailing['isRemoved']) {

                    MailingTable::query()
                    ->where('mailing_id', $mailing['id'])
                    ->delete();

                } else {
                    $mailingObj = MailingTable::find($mailing['id']);

                    if(!$mailingObj) {
                        $mailingObj = new MailingTable();
                        $mailingObj->application_id = $applicationId;
                    }

                    $mailingObj->type   = $mailing['type'];
                    $mailingObj->recipients = $mailing['recipients'] ?? '';
                    $mailingObj->unit_number = $mailing['unitNumber'] ?? '';
                    $mailingObj->unit_type = $mailing['unitType'] ?? '';
                    $mailingObj->street_number = $mailing['streetNumber'] ?? '';
                    $mailingObj->street_name = $mailing['streetName'] ?? '';
                    $mailingObj->street_type = $mailing['streetType'] ?? '';
                    $mailingObj->street_direction = $mailing['streetDirection'] ?? '';
                    $mailingObj->city = $mailing['city'] ?? '';
                    $mailingObj->province = $mailing['province'] ?? '';
                    $mailingObj->postal_code = $mailing['postalCode'] ?? '';
                    $mailingObj->box_number = $mailing['poNumber'] ?? '';
                    $mailingObj->station = $mailing['station'] ?? '';
                    $mailingObj->rr_number = $mailing['rrNumber'] ?? '';
                    $mailingObj->site = $mailing['site'] ?? '';
                    $mailingObj->compartment = $mailing['compartment'] ?? '';
                    $mailingObj->other = $mailing['other'] ?? '';
                    $mailingObj->how_long = $mailing['howLong'] ?? '';
                    $mailingObj->save();
                }           
            }

        } catch(\Throwable $e) {
            $this->logger->error('MailingBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }
}