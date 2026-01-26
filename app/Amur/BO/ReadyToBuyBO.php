<?php

namespace App\Amur\BO;
use App\Amur\Utilities\Utils;

use App\Amur\BO\ChecklistBO;
use App\Amur\BO\SalesforceBO;

class ReadyToBuyBO {

    private $db;
    private $logger;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }


    public function getCheckList($savedQuoteId) {
                

        $checklistBO = new ChecklistBO($this->db, $this->logger);
        $questions = $checklistBO->getQuestions('RTB');

        $answers = $checklistBO->getAnswers('saved_quote_table', $savedQuoteId);
        $htmlChecklist = $this->renderQuestions('', $questions, $answers, 0, 0, true);

        $htmlChecklist .= '<br>';
        $htmlChecklist .= '<div style="width: 100%; text-align: left; margin-bottom: 10px;">';
        $htmlChecklist .= '<label for="rejectReason">Comments</label>';
        $htmlChecklist .= '<textarea id="rejectReason" name="rejectReason" rows="3" style="width: 100%;"></textarea>';
        $htmlChecklist .= '</div>';

        $htmlChecklist .= '<div style="width: 100%; text-align: left;">';
        $htmlChecklist .= '<input type="submit" name="approve" value="Approve" onClick="approve(' . $savedQuoteId . ')" style="margin-right: 10px;">';
        $htmlChecklist .= '<input type="submit" name="  " value="Reject" onClick="reject(' . $savedQuoteId . ')">';
        $htmlChecklist .= '</div>';

        return $htmlChecklist;

    }

    public function renderQuestions($htmlQuestions, $questions, $answers, $parentQuestionId, $level, $parentOption) {
        
        //header
        if($htmlQuestions == '') {
            $htmlQuestions .= '<div>';
            $htmlQuestions .= '    <div class="row">';
            $htmlQuestions .= '        <label>Checklist Item</label>';
            $htmlQuestions .= '        <div class="column" style="width: 17%; text-align: center; font-family: \'Arial\', serif; font-size: 12px;"><span>Broker Answer</span></div>';
            $htmlQuestions .= '    </div>';
            $htmlQuestions .= '</div>';
        }
    
        if($parentOption) {
            $display = 'unset';
        } else {
            $display = 'none';
        }
    
        $htmlQuestions .= '<div id="divParentQuestion_' . $parentQuestionId . '" style="display: ' . $display . '">';
    
        foreach($questions as $questionKey => $question) {
    
            $options = json_decode($question['option']);
    
            $ra = $this->getReceiveAnswer($answers, $question['id']);
            $receivedAnswer = $ra['receivedAnswer'];
            $receivedAnswerId = $ra['answerId'];
    
            $parentOption = false;
            if($question['type'] == 'select') {
    
                $htmlQuestions .= '<div id="divQuestion_' . $question['id'] . '" class="row">';
                $htmlQuestions .= '    <div class="column" style="padding-left: ' . ($level * 10) . 'px;">';
                $htmlQuestions .= '        <label>' . ($questionKey + 1) . '. ' . $question['question'] . '</label>';
                $htmlQuestions .= '    </div>';
    
                $htmlQuestions .= '    <div class="column" style="width: 15%; text-align: center;">';
                $htmlQuestions .= '        <select disabled="true">';
                $htmlQuestions .= '            <option value="none">' . $receivedAnswer . '</option>';
                $htmlQuestions .= '        </select>';
                $htmlQuestions .= '    </div>';   
                    
                $htmlQuestions .= '</div>';
    
            } elseif($question['type'] == 'checkbox') {
                $checked = '';
                if($receivedAnswer == "true") {
                    $checked = 'checked';
                }
    
                $htmlQuestions .= '<div id="divQuestion_' . $question['id'] . '" class="row">';
                $htmlQuestions .= '    <div class="column" style="padding-left: ' . ($level * 10) . 'px;">';
                $htmlQuestions .= '        <input disabled="true" type="checkbox" ' . $checked . '>';
                $htmlQuestions .= '        <label style="display: inline-block;">' . $question['question'] . '</label>';
                $htmlQuestions .= '    </div>';
    
                $htmlQuestions .= '    <div class="column" style="width: 15%; text-align: center;"></div>';   
    
                $htmlQuestions .= '</div>';
    
            } elseif($question['type'] == 'input') {
                $tmpHtmlQuestions = '';
                $tmpHtmlQuestions .= '<div id="divQuestion_' . $question['id'] . '" class="row">';
                $tmpHtmlQuestions .= '    <div class="column">';
                $tmpHtmlQuestions .= '        <label>' . $question['question'] . '</label>';
                $tmpHtmlQuestions .= '    </div>';
                $tmpHtmlQuestions .= '</div>';
    
                if(isset($options->replace)) {
                    foreach($options->replace as $replaceKey => $replace) {
                        $tmpHtmlQuestions = str_replace($replace->find, $receivedAnswer, $tmpHtmlQuestions);
                    }
                }
    
                $htmlQuestions .= $tmpHtmlQuestions;
            }
    
            if(count($question['parentQuestion']) > 0) {
                if(isset($options->answer) && $options->answer == $receivedAnswer) {
                    $tmpParentOption = false;
                } else {
                    $tmpParentOption = true;
                }
    
                $level++;
                $htmlQuestions = $this->renderQuestions($htmlQuestions, $question['parentQuestion'], $answers, $question['id'], $level, $tmpParentOption);
                $level--;
            }
        }
    
        $htmlQuestions .= '</div>';
    
        return $htmlQuestions;
    }    

    public function getReceiveAnswer($answers, $questionId) {
        $answerId = '';
        $receivedAnswer = '';
        $error = 'no';
        foreach($answers as $answerKey => $answer) {
            if($answer['questionId'] == $questionId) {
                $answerId = $answer['answerId'];
                $error = $answer['error'];

                if($answer['answer'] === true || $answer['answer'] == "true") {
                    $receivedAnswer = "true";
                } elseif($answer['answer'] === false || $answer['answer'] == "false") {
                    $receivedAnswer = "false";
                } else {
                    $receivedAnswer = $answer['answer'];
                }

                break;
            }
        }

        return array('answerId' => $answerId, 'receivedAnswer' => $receivedAnswer, 'error' => $error);
    }

    public function approve($savedQuoteId, $userId, $comments) {

        $query = "UPDATE saved_quote_table 
        SET ready_buy = 'Yes',
            validated_by = $userId,
            validated_at = NOW(),
            validated_status = 'A',
            reject_reason = '$comments'
        WHERE saved_quote_id = $savedQuoteId";
        $this->db->query($query);

        if ($savedQuoteId > 0) {

            $this->logger->info('ReadyToBuyBO->approve - Sync quote to salesforce', [$savedQuoteId]);

            $salesforceBO = new SalesforceBO($this->logger, $this->db);
            $salesforceBO->syncQuote($savedQuoteId);
        }
        
    }
    
    public function reject($savedQuoteId, $comments, $userId) {

        $query = "UPDATE saved_quote_table 
        SET ready_buy = 'No',
            validated_by = $userId,
            validated_at = NOW(),
            validated_status = 'R',
            reject_reason = '$comments'
        WHERE saved_quote_id = $savedQuoteId";
        $this->db->query($query);

        $this->sendEmail($savedQuoteId, $comments, $userId);

    }

    public function sendEmail($savedQuoteId, $comments, $userId) {

        $query = 'select a.saved_quote_id,b.user_fname ,b.user_lname, b.user_email  
                  from saved_quote_table a
                  join users_table b on b.user_id  = a.updated_by 
                  where a.saved_quote_id = ' . $savedQuoteId;
        $res = $this->db->query($query);       

        $brokerName  = '';
        $brokerEmail = '';

        foreach ($res as $key => $value) {
            $brokerName = $value['user_fname'].' '.$value['user_lname'];
            $brokerEmail = $value['user_email'];
        }

        $query = 'select a.application_id,d.f_name,d.l_name from saved_quote_table a
                  join application_table b on b.application_id = a.application_id
                  join applicant_table c on c.application_id = a.application_id 
                  join spouse_table d on d.spouse_id = c.spouse1_id 
                  where a.saved_quote_id = ' . $savedQuoteId;
        $res = $this->db->query($query);

        $applicationId = '';
        $name = '';

        foreach ($res as $key => $value) {
            $applicationId = $value['application_id'];
            $name = $value['l_name'].', '. $value['f_name'];
        }

        if ($brokerName != '' && $brokerEmail != '') {
            $subject = "Quote Rejected - #".$applicationId. ' '.$name;

            $body = "Hi $brokerName,<br><br>";
            $body .= "Your quote has been rejected.<br>";
            $body .= "Reason: $comments <br>";
            $body .= "<p>This is an automated message. Please do not reply.</p>";
            $body .= "<p>Regards,<br>IT Team</p>";

            $this->logger->info("user:{$userId}, To: {$brokerEmail},'','',{$subject},{$body}", "local_email");

            $toAddresses = array($brokerEmail);
            Utils::sendEmail($toAddresses, $subject, $body,'', null);
        }else {
            $this->logger->info("Email was not sent after it was rejected because the broker was not found, or the name and email fields are empty. Quote ".$savedQuoteId, "local_email");


            
        }
    }   

}
?>