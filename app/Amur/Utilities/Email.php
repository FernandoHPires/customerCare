<?php

namespace App\Amur\Utilities;

use App\Amur\Bean\ILogger;

class Email {

    private $logger;

    private $fromAddress;
    private $fromName;
    private $toAddress;
    private $replyToAddress;
    private $replyToName;
    private $subject;
    private $body;
    private $bodyType;
    private $provider = 'local';
    private $unsubscribeGroup;
    private $error;
    private $messageId;
    private $object;
    private $objectId;
    private $companyName;
    private $emailCategory;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function send() {

        if($this->provider == 'local') {
            return $this->local();
        }
    }

    private function local() {
        $fields = array(
            'provider' => $this->provider,
            'fromAddress' => 'it@amurgroup.ca',
            'fromName' => 'Amur IT',
            'toAddress' => $this->toAddress,
            'subject' => $this->subject,
            'bodyType' => $this->bodyType,
            'body' => $this->body
        );

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl(env('AMUR_API_ENDPOINT') . '/email/send-email');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->setRequestType('async');
        $httpRequest->exec();
    }

    public function getFromAddress() {
		return $this->fromAddress;
	}

	public function setFromAddress($fromAddress) {
		$this->fromAddress = $fromAddress;
	}

	public function getFromName() {
		return $this->fromName;
	}

	public function setFromName($fromName) {
		$this->fromName = $fromName;
	}

	public function getToAddress() {
		return $this->toAddress;
	}

	public function setToAddress($toAddress) {
		$this->toAddress = $toAddress;
	}

	public function getReplyToAddress() {
		return $this->replyToAddress;
	}

	public function setReplyToAddress($replyToAddress) {
		$this->replyToAddress = $replyToAddress;
	}

	public function getReplyToName() {
		return $this->replyToName;
	}

	public function setReplyToName($replyToName) {
		$this->replyToName = $replyToName;
	}

	public function getSubject() {
		return $this->subject;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function getBody() {
		return $this->body;
	}

	public function setBody($body) {
		$this->body = $body;
	}

	public function getBodyType() {
		return $this->bodyType;
	}

	public function setBodyType($bodyType) {
		$this->bodyType = $bodyType;
    }
    
    public function getProvider() {
		return $this->provider;
	}

	public function setProvider($provider) {
		$this->provider = $provider;
    }

    public function getUnsubscribeGroup() {
		return $this->unsubscribeGroup;
	}

	public function setUnsubscribeGroup($unsubscribeGroup) {
        if(is_null($unsubscribeGroup) || trim($unsubscribeGroup) == '') {
            $this->unsubscribeGroup = null;
        } else {
            $this->unsubscribeGroup = (int) $unsubscribeGroup;
        }
	}
    
    public function getError() {
		return $this->error;
	}

	public function setError($error) {
		$this->error = $error;
	}

	public function getMessageId() {
		return $this->messageId;
	}

	public function setMessageId($messageId) {
		$this->messageId = $messageId;
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
		$this->objectId = substr($objectId,0,50);
	}

    public function getCompanyName() {
        return $this->companyName;
    }

    public function setCompanyName($companyName) {
        $this->companyName = $companyName;
    }

    public function getEmailCategory() {
        return $this->emailCategory;
    }

    public function setEmailCategory($emailCategory) {
        $this->emailCategory = $emailCategory;
    }
}
