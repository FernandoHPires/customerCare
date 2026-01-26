<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\BO\ReadyToBuyBO;
use App\Amur\BO\ChecklistAnswerBO;
use App\Amur\BO\ApplicationBO;
use App\Models\SavedQuoteTable;
use App\Amur\BO\SalesforceBO;

class ChecklistBO {

    private $logger;
    private $db;
    private $pendingData;
    
    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($applicationId, $checkListType, $objectId) {

        $this->logger->info('ChecklistBO->index',[$applicationId, $checkListType, $objectId]);

        $pendingData = array();
        $questions = array();

        if ($checkListType == 'RTB') {
            if($this->isDataPending($applicationId, $objectId)) {

                $pendingData = $this->getPendingData();
            
            } else {
            
                $questions = $this->getQuestions('RTB', $objectId);
    
            }
        } else {
            $pendingData = array();
            $questions = $this->getQuestions('APC', $objectId);
        }


        return [
            'pendingData' => $pendingData,
            'questions' => $questions
        ];
    }

    public function isDataPending($applicationId, $objectId) {

        $this->pendingData = array();

        //1st mortgage term end
        $query = "select street_number, street_name, street_type, city, province, b.balance
                    from property_table a
                    join property_mortgages_table b on a.property_id = b.property_id
                   where a.application_id = $applicationId
                     and a.part_of_security = 'Yes'
                     and b.balance <> 0
                     and b.line_of_credit = 'No'
                     and b.term = '0000-00-00'";
        $res = $this->db->query($query);        

        foreach($res as $key => $value) {
            //$balance = number_format($value->balance,2);
            $this->pendingData[] =
                "Missing 'Term End Date' for non-Alpine mortgage: {$value->street_number} {$value->street_name} {$value->street_type}, {$value->city} {$value->province}";
        }

        //Get Beacon Score
        $query = "select s.spouse_id, s.beacon_score, ap.credit_bureau_recvd, a.signed_date, a.funding_date, a.status, s.type
                    from application_table a
                    join applicant_table ap on a.application_id = ap.application_id
               left join spouse_table s on (ap.spouse1_id = s.spouse_id or ap.spouse2_id = s.spouse_id)
                                       and s.type not in ('Not a co-applicant','Do not contact')
                                       and s.f_name <> ''
                                       and s.l_name <> ''
                   where a.application_id = $applicationId
                order by if(s.type = 'Applicant',0,1)";
        $dataRow = $this->db->query($query);

        foreach($dataRow as $value) {
            if(!is_null($value->spouse_id)) {
                if(empty($value->beacon_score)) {
                    $this->pendingData[] = 'Beacon score is required';
                }

                $days = round(time() - strtotime($value->credit_bureau_recvd)) / (3600 * 24);
                if($days > 365) {
                    $this->pendingData[] = 'Credit Bureau report is more than a year old, please request a new report';
                }
            }
    
            if(empty($value->funding_date) || $value->funding_date == '0000-00-00') {
                $this->pendingData[] = 'Funding date is required';
            }
    
            if ($value->status != 8 && $value->status != 10 && $value->status != 17) {
                $this->pendingData[] = 'Required Status: Init Docs or Signing';
            }
        }

        $appObj = new ApplicationBO($this->logger, $this->db);
        $ltv = $appObj->calLtv($applicationId, $objectId);
        if($ltv >= 100) {
            $this->pendingData[] = "Total LTV " . number_format($ltv,2) . "% (previous mortgage + alpine) is greater than 100%";
        }

        $query = "SELECT * FROM application_table WHERE application_id = ?";
        $res = $this->db->select($query,[$applicationId]);

        $appAmount = 0;
        $companyId = 0;
        if(count($res) > 0) {
            $appAmount = $res[0]->amt_required;
            $companyId = $res[0]->company;
        }

        $query = "SELECT SUM(net) as net FROM saved_quote_table WHERE application_id = ? AND disburse = 'Yes'";
        $res = $this->db->select($query,[$applicationId]);

        if($appAmount != $res[0]->net && $companyId != 701) {
            $this->pendingData[] = 'Application amount required not equal to enabled quote(s) net';
        }

        $query = "select b.f_name, b.l_name, b.is_pep, b.pep_description  
                    from applicant_table a
                    join spouse_table b on b.spouse_id = a.spouse1_id or b.spouse_id = a.spouse2_id 
                   where a.application_id = ?";
        $applicants = $this->db->select($query, [$applicationId]);

        foreach ($applicants as $applicant) {
            if (!empty($applicant->f_name) && !empty($applicant->l_name)) {
                if(empty($applicant->is_pep)) {
                    $this->pendingData[] = 'PEP or HIO is required for ' . $applicant->f_name . ' ' . $applicant->l_name;

                } elseif($applicant->is_pep == 'Yes' && empty($applicant->pep_description)) {
                    $this->pendingData[] = 'PEP Description is required for ' . $applicant->f_name . ' ' . $applicant->l_name;
                }
            }
        }

        return count($this->pendingData) > 0;
    }

    public function getQuestions($process, $objectId) {

        $query = "SELECT * FROM checklist_question a
                   WHERE process = ?
                     AND deleted_at is null
                ORDER BY a.parent_question_id, a.sequence";
        $res = $this->db->select($query,[$process]);

        $data = array();

        foreach($res as $value) {

            $question = str_replace('<tag_input>', '_________', $value->question);
            
            if ($value->type == 'select') {
                $answer = 'none'; 
            } elseif ($value->type == 'checkbox' && (is_null($value->validation_rule) || $value->validation_rule == '')) {
                $answer = false;
            } elseif ($value->type == 'checkbox' && $value->validation_rule != '') {
                $answer = $this->checkValidationRule($objectId, $value->validation_rule);
            } else {
                $answer = '';
            }

            $data[] = array(
                'id' => $value->checklist_question_id,
                'parentQuestionId' => $value->parent_question_id,
                'question' => $question,
                'type' => $value->type,
                'option' => json_decode($value->option),
                'disable' => $value->disable ?? null,
                'validationRule' => $value->validation_rule ?? null,
                'answer' => $answer,
                'correctAnswer' => '',
            );
        }

        $questions = array();

        foreach($data as $value) {

            if($value['parentQuestionId'] == null) {

                $parentQuestion = $this->getParentQuestion($value['id'], $data);

                if ($process == 'RTB') {
                    $object = 'saved_quote_table';
                } else {
                    $object = 'property_table';
                }

                $questions[] = array(
                    'id' => $value['id'],
                    'question' => $value['question'],
                    'type' => $value['type'],
                    'option' => $value['option'],
                    'disable' => $value['disable'],
                    'parentQuestion' => $parentQuestion,
                    'validationRule' => $value['validationRule'],
                    'answer' => $value['answer'],
                    'correctAnswer' => $value['correctAnswer'],
                    'object' => $object
                );
            }
        }

        return $questions;
    }

    private function getParentQuestion($id, $data) {

        $questions = array();
        foreach($data as $key => $value) {
            if($value['parentQuestionId'] != null && $id == $value['parentQuestionId']) {
                
                $parentQuestion = $this->getParentQuestion($value['id'], $data);

                $questions[] = array(
                    'id' => $value['id'],
                    'question' => $value['question'],
                    'type' => $value['type'],
                    'option' => $value['option'],
                    'disable' => $value['disable'],
                    'parentQuestion' => $parentQuestion,
                    'validationRule' => $value['validationRule'],
                    'answer' => $value['answer'],
                    'correctAnswer' => $value['correctAnswer'],
                );
            }
        }

        return $questions;
    }

    public function validate($id, $process) {
        $questions = $this->getQuestions($process, 0);

        if($process == 'RTB') {
            $object = 'saved_quote_table';

        } elseif($process == 'APC') {
            $object = 'property_table';
        }

        $answers = $this->getAnswers($object, $id);
        $tmp     = json_encode($answers);
        $answers = json_decode($tmp);

        $validation = array();
        $this->validateAnswers($questions, $answers, $validation, $id);

        return $validation;
    }

    public function validateAnswers($questions, $answers, &$validation, $id) {

        foreach($questions as $questionKey => $question) {

            $option = json_decode($question['option']);
            
            $receivedAnswer = null;
            foreach($answers as $answerKey => $answer) {
                if($answer->questionId == $question['id']) {
                    if($answer->answer === true || $answer->answer == "true") {
                        $receivedAnswer = "true";
                    } elseif($answer->answer === false || $answer->answer == "false") {
                        $receivedAnswer = "false";
                    } else {
                        $receivedAnswer = $answer->answer;
                    }
                    break;
                }
            }

            if($receivedAnswer != null) {
                if($question['type'] == 'select') {
                    if($receivedAnswer == 'none') {
                        $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Wrong answer!');
                    } elseif($receivedAnswer != $option->answer && $option->answer != '') {
                        //parent
                        if(count($question['parentQuestion']) > 0) {
                            $this->validateAnswers($question['parentQuestion'], $answers, $validation, $id);
                        } else {
                            $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Wrong answer!');
                        }
                    }

                } elseif($question['type'] == 'checkbox') {
                    if($receivedAnswer != "true") {
                        $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Checkbox should be flagged');
                    }

                } else {
                    if(trim($receivedAnswer) == '') {
                        $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Field cannot be empty');
                    }
                }
            } else {
                if(!is_null($question['validationRule'])) {
                    $query = $question['validationRule'];
                    $query = str_replace('[TAG_SAVED_QUOTE_ID]', $id, $query);
                    $res = $this->db->query($query)->fetchAll();
                    if(count($res) == 0) {
                        $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Missing answer');
                    }
                } else {
                    $validation[] = array('questionId' => $question['id'], 'status' => 'error', 'message' => 'Missing answer');
                }
            }
        }
    }

    public function saveAnswers($fields, $objectId, $userId) {

        $this->logger->info('ChecklistBO->saveAnswers',[$fields, $objectId, $userId]);
        
        $process = '';
        foreach ($fields as $row) {
            if(isset($row['object'])) {
                if($row['object'] == 'saved_quote_table') {
                    $process = 'RTB';
                } elseif($row['object'] == 'property_table') {
                    $process = 'APC';
                }
                break;
            }
        }

        //$questions = $this->getQuestions($process, $objectId);
        $validation = array();
        //$this->validateAnswers($questions, $fields, $validation, $objectId);

        if(count($validation) <= 0) {

            foreach($fields as $answer) {

                $checklistAnswer = new ChecklistAnswerBO($this->db, $this->logger, null);
                $checklistAnswer->getByObjectAndQuestionId($answer['object'], $objectId, $answer['id']);
                $checklistAnswer->setChecklistQuestionId($answer['id']);
                $checklistAnswer->setObject($answer['object']);
                $checklistAnswer->setObjectId($objectId);
                $checklistAnswer->setAnswer($answer['answer']);
                $checklistAnswer->setUserId($userId);
                $checklistAnswer->save();

                if (isset($answer['parentQuestion'])) {

                    foreach ($answer['parentQuestion'] as $parentAnswer) {

                        if (!isset($parentAnswer['answer']) || $parentAnswer['answer'] == null) {
                            $parentAnswerAux = '';
                        }else {
                            $parentAnswerAux = $parentAnswer['answer'];
                        }

                        $checklistAnswer = new ChecklistAnswerBO($this->db, $this->logger, null);
                        $checklistAnswer->getByObjectAndQuestionId($answer['object'], $objectId, $parentAnswer['id']);

                        $checklistAnswer->setChecklistQuestionId($parentAnswer['id']);
                        $checklistAnswer->setObject($answer['object']);
                        $checklistAnswer->setObjectId($objectId);
                        $checklistAnswer->setAnswer($parentAnswerAux);
                        $checklistAnswer->setUserId($userId);
                        $checklistAnswer->save();
                    }
                }
            }

            if($process === 'RTB') {
                $this->readyToBuy($objectId, $userId);
                $this->checkUpdate($objectId);
            }

            return true;
        } else {
            return $validation;
        }
    }

    public function getAnswers($object, $objectId) {
        $query = "SELECT a.*, b.option
                    FROM checklist_answer a
               LEFT JOIN checklist_question b ON a.checklist_question_id = b.checklist_question_id
                   WHERE object = '$object'
                     AND object_id = '$objectId'";
        $res = $this->db->query($query);

        $data = array();
        foreach($res as $key => $value) {
            
            $data[] = array(
                'questionId' => $value['checklist_question_id'],
                'answerId' => $value['checklist_answer_id'],
                'answer' => $value['answer'],
                'options' => $value['option'],
                'error' => $value['error']
            );
        }

        if (count($data) <= 0) {
            $data = $this->getValidationRule($object, $objectId);
        }

        return $data;
    }

    public function getValidationRule($object, $objectId) {
        $data = array();

        $query = "SELECT * FROM checklist_question
                   WHERE process = 'RTB'
                     AND validation_rule <> ''
                     and deleted_at is null
                  ORDER BY parent_question_id, sequence";
        $res = $this->db->query($query);
        
        foreach($res as $key => $value) {

            $answer = $this->answerValidationRule($value['validation_rule'],$object, $objectId);

            if($answer != '') {
                $data[] = array(
                    'questionId' => $value['checklist_question_id'],
                    'answerId'   => 0,
                    'answer'     => $answer,
                    'options'    => $value['option'],
                    'error'      => ''
                );
            }

        }

        return $data;
    }

    public function answerValidationRule($validationRule, $object, $objectId) {
        $answer = '';
        
        if ($object == 'saved_quote_table') {
            $queryAppId = 'select application_id from saved_quote_table where saved_quote_id = ' . $objectId;
        } elseif($object == 'property_table') {
            $queryAppId = 'select application_id from property_table where property_id = ' . $objectId;
        }

        $resAppId = $this->db->query($queryAppId);

        if ($resAppId) {
            $answer = 'false';
            foreach($resAppId as $keyAppId => $valueAppId) {
                $applicationId = $valueAppId['application_id'];
            }
        }

        $queryAnswer = str_replace('[TAG_APPLICATION_ID]', $applicationId ,$validationRule);
        $queryAnswer = str_replace('[TAG_SAVED_QUOTE_ID]', $objectId , $queryAnswer);
        $resAnswer   = $this->db->query($queryAnswer);
        
        if ($resAnswer) {
            $answer = 'false';
            foreach($resAnswer as $keyAnswer => $valueAnswer) {
                $answer = 'true'; 
            }
        }
        
        return $answer;
    }

    public function getPendingData() {
        return $this->pendingData;
    }

    public function checkUpdate($savedQuoteId) {
        $query = "select b.company from saved_quote_table a
                    join application_table b on b.application_id = a.application_id
                   where saved_quote_id = $savedQuoteId";
        $res = $this->db->query($query);

        foreach($res as $value) {
            if($value->company == 701) {
                $readyToBuyObj = new ReadyToBuyBO($this->db, $this->logger);
                $readyToBuyObj->approve($savedQuoteId, 0, '');
            }
        }
    }
    
    public function readyToBuy($savedQuoteId, $userId) {
        $this->logger->info('ChecklistBO->readyToBuy',[$savedQuoteId, $userId]);

        $savedQuote = SavedQuoteTable::find($savedQuoteId);

        if($savedQuote) {
            $savedQuote->ready_buy = 'Yes';
            $savedQuote->save();

            $apiBO = new ApiBO($this->logger);
            $apiBO->readyBuy($savedQuote->application_id, $userId);

            if ($savedQuoteId > 0) {

                $this->logger->info('ChecklistBO->readyToBuy - Sync quote to salesforce', [$savedQuoteId]);

                $salesforceBO = new SalesforceBO($this->logger, $this->db);
                $salesforceBO->syncQuote($savedQuoteId);
            }
        }
    }

    public function checkValidationRule($objectId, $validationRule) {

        if ($objectId == 0 || $objectId == '' || $objectId == null || $objectId == 'undefined') {
            return false;
        }

        $query = str_replace('[TAG_SAVED_QUOTE_ID]', $objectId, $validationRule);
        $res = $this->db->query($query);

        if(count($res) > 0) {
            return true;
        } else {
            return false;
        }
        
    }
}