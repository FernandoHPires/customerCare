<?php

namespace App\Amur\Bean;

use App\Amur\Utilities\Utils;
use DateTime;

class SalesforceIntegration {

    private $db;
    private $logger;

    private $tableName = 'salesforce_integration';
    private $salesforceIntegrationId;
    private $salesforceObject;
    private $salesforceId;
    private $object;
    private $objectId;
    private $customFields = array();
    private $createdAt;
    private $updatedAt;

    public function __construct(IDB $db, ILogger $logger) {
        $this->db = $db;
        $this->logger = $logger;

        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime('2020-01-01 00:00:00.0000');
    }

    public function getById($salesforceIntegrationId) {
        //$sql .= "where id = ?";
    }

    public function getByObjectId($object, $objectId) {
        $query = "select * from salesforce_integration where object = ? and object_id = ?";
        $res = $this->db->select($query, [$object, $objectId]);

        return $this->fillFields($res);
    }

    public function getBySalesforceId($object, $salesforceId) {
        if(strlen($salesforceId) == 15) {
            $salesforceId = Utils::to18Id($salesforceId);
        }

        $query = "select * from salesforce_integration where salesforce_object = ? and salesforce_id = ?";
        $res = $this->db->select($query, [$object, $salesforceId]);

        return $this->fillFields($res);
    }

    public function fillFields($res) {
        if(count($res) > 0) {
            $this->salesforceIntegrationId = $res[0]->salesforce_integration_id;
            $this->salesforceObject = $res[0]->salesforce_object;
            $this->salesforceId = $res[0]->salesforce_id;
            $this->object = $res[0]->object;
            $this->objectId = $res[0]->object_id;
            $this->customFields = json_decode($res[0]->custom_fields);
            $this->createdAt = new DateTime($res[0]->created_at);
            $this->updatedAt = new DateTime($res[0]->updated_at);

            return true;
        } else {
            return false;
        }
    }

    public function insert() {
        $fields = array(
            'salesforce_object' => $this->salesforceObject,
            'salesforce_id' => $this->salesforceId,
            'object' => $this->object,
            'object_id' => $this->objectId,
            'custom_fields' => json_encode($this->customFields),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s.u')
        );

        $this->salesforceIntegrationId = $this->db->insertGetId('salesforce_integration', $fields, 'salesforce_integration_id');
    }

    public function update() {
        //update
        $fields['object_id'] = $this->objectId;
        $fields['custom_fields'] = json_encode($this->customFields);
        $fields['updated_at'] = $this->updatedAt->format('Y-m-d H:i:s.u');
        
        $conditions['salesforce_integration_id'] = $this->salesforceIntegrationId;

        $this->db->update('salesforce_integration', $fields, $conditions);
    }

    public function save() {
        $fields['salesforce_object'] = $this->salesforceObject;
        $fields['salesforce_id'] = $this->salesforceId;
        $fields['object'] = $this->object;
        $fields['object_id'] = $this->objectId;
        $fields['custom_fields'] = json_encode($this->customFields);
        $fields['updated_at'] = $this->updatedAt->format('Y-m-d H:i:s.u');

        if(is_null($this->salesforceIntegrationId)) {
            $fields['created_at'] = (new DateTime())->format('Y-m-d H:i:s');

            $this->salesforceIntegrationId = $this->db->insertGetId($this->tableName, $fields, 'salesforce_integration_id');
        } else {
            $conditions['salesforce_integration_id'] = $this->salesforceIntegrationId;
            $this->db->update($this->tableName, $fields, $conditions);
        }
    }

    public function delete() {

    }

    public function getSalesforceIntegrationId() {
		return $this->salesforceIntegrationId;
	}

	public function setSalesforceIntegrationId($salesforceIntegrationId) {
		$this->salesforceIntegrationId = $salesforceIntegrationId;
	}

	public function getSalesforceObject() {
		return $this->salesforceObject;
	}

	public function setSalesforceObject($salesforceObject) {
		$this->salesforceObject = $salesforceObject;
	}

	public function getSalesforceId() {
		return $this->salesforceId;
	}

	public function setSalesforceId($salesforceId) {

        if(strlen($salesforceId) == 15) {
            $this->salesforceId = Utils::to18Id($salesforceId);

        } elseif(strlen($salesforceId) == 18) {
            $this->salesforceId = $salesforceId;

        } else {
            $this->salesforceId = null;
        }
    }

	public function getObject() {
		return $this->object;
	}

	public function setObject($object) {
		$this->object = $object;
    }

	public function getObjectId() {
		return $this->objectId;
	}

	public function setObjectId($objectId) {
		$this->objectId = $objectId;
    }
    
    public function getCustomFields() {
		return $this->customFields;
	}

	public function setCustomFields($customFields) {
		$this->customFields = $customFields;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt(DateTime $createdAt) {
        $this->createdAt = $createdAt;
    }

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt(DateTime $updatedAt) {
		$this->updatedAt = $updatedAt;
    }
}
