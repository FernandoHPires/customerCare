<?php

namespace App\Amur\BO;

use App\Models\ChecklistAnswer;

class ChecklistAnswerBO {

    private $db;
    private $logger;

    private $checklistAnswerId;
    private $checklistQuestionId;
    private $object;
    private $objectId;
    private $answer;
    private $error = 'no';
    private $updatedAt;
    private $userId;
    
    public function __construct($db, $logger, $id = null) {
        $this->db = $db;
        $this->logger = $logger;

        if($id != null) {
            $this->getById($id);
        }
    }

    public function getById($id) {
        $query = "SELECT *
                    FROM checklist_answer
                   WHERE checklist_answer_id = $id";
        $res = $this->db->query($query);

        $this->checklistAnswerId = null;
        foreach($res as $key => $value) {
            $this->checklistAnswerId = $value['checklist_answer_id'];
            $this->checklistQuestionId = $value['checklist_question_id'];
            $this->object = $value['object'];
            $this->objectId = $value['object_id'];
            $this->answer = $value['answer'];
            $this->updatedAt = $value['updated_at'];
            $this->userId = $value['user_id'];
        }
    }

    public function getByObjectAndQuestionId($object, $objectId, $questionId) {

        $checklistAnswer = ChecklistAnswer::query()
        ->where('object', $object)
        ->where('object_id', $objectId)
        ->where('checklist_question_id', $questionId)
        ->get();

        $this->checklistAnswerId = null;

        foreach($checklistAnswer as $key => $value) {
            $this->checklistAnswerId = $value->checklist_answer_id;
            $this->checklistQuestionId = $value->checklist_question_id;
            $this->object = $value->object;
            $this->objectId = $value->object_id;
            $this->answer = $value->answer;
            $this->updatedAt = $value->updated_at;
            $this->userId = $value->user_id;
        }
    }

    public function save() {

        if($this->checklistAnswerId != null) {

            $query = "UPDATE checklist_answer 
                         SET answer = '$this->answer',
                             error = '$this->error',
                             updated_at = '" . date("Y-m-d H:i:s",time()) . "'
                       WHERE checklist_answer_id = $this->checklistAnswerId";
            $this->db->query($query);
        } else {

            $checklistAnswer = new ChecklistAnswer();
            $checklistAnswer->object = $this->object;
            $checklistAnswer->object_id = $this->objectId;
            $checklistAnswer->checklist_question_id = $this->checklistQuestionId;
            $checklistAnswer->answer = $this->answer;
            $checklistAnswer->error = $this->error;
            $checklistAnswer->user_id = $this->userId;
            $checklistAnswer->save();
        }
    }

    public function delete() {

    }

    public function getChecklistAnswerId() {
        return $this->checklistAnswerId;
    }

    public function getChecklistQuestionId() {
        return $this->checklistQuestionId;
    }

    public function getObject() {
        return $this->object;
    }

    public function getObjectId() {
        return $this->objectId;
    }

    public function getAnswer() {
        return $this->answer;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function setChecklistAnswerId($checklistAnswerId) {
        $this->checklistAnswerId = $checklistAnswerId;
    }

    public function setChecklistQuestionId($checklistQuestionId) {
        $this->checklistQuestionId = $checklistQuestionId;
    }

    public function setObject($object) {
        $this->object = $object;
    }

    public function setObjectId($objectId) {
        $this->objectId = $objectId;
    }

    public function setAnswer($answer) {
        if($answer === true) {
            $this->answer = 'true';

        } else if($answer === false) {
            $this->answer = 'false';

        } else {
            $this->answer = $answer;
        }
        
    }

    public function setError($error) {
        if(strtolower($error) == 'yes') {
            $this->error = 'yes';
        } else {
            $this->error = 'no';
        }
	}

    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }
}
