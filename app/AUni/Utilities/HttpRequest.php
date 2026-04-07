<?php

namespace App\AUni\Utilities;

use App\AUni\Bean\ILogger;

class HttpRequest {

    private $logger;
    private $token;
    private $url;
    private $method;
    private $bearer = false;
    private $basic = false;
    private $contentType;
    private $accept;
    private $cookie;
    private $userAgent;
    private $xCSRFToken;
    private $fields;
    private $fieldType;
    private $requestType = 'sync';
    private $referer;
    private $headers;
    private $headerType = 'default';
    private $response;
    private $responseCode;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function exec() {
        if($this->method == 'get') {
            $this->get();

        } elseif($this->method == 'post') {
            $this->post();

        } elseif($this->method == 'put') {
            $this->put();

        } elseif($this->method == 'patch') {
            $this->patch();

        } elseif($this->method == 'delete') {
            $this->delete();
        }
    }

    public function get() {
        $ch = curl_init();

        $url = $this->url . (($this->fields != null) ? '?' . http_build_query($this->fields) : '');

        $headers = $this->getHeader();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if($this->requestType == 'async') {
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->headers = $this->parseHeader($header);

        $body = substr($response, $headerSize);
        $this->response = $body;

        $info = curl_getinfo($ch);

        $this->responseCode = $info['http_code'];

        curl_close($ch);
    }

    public function post() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());

        if($this->fields != null) {
            if($this->fieldType == 'raw') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->fields);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->fields));
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if($this->requestType == 'async') {
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->headers = $this->parseHeader($header);

        $body = substr($response, $headerSize);
        $this->response = $body;

        $info = curl_getinfo($ch);

        $this->responseCode = $info['http_code'];

        curl_close($ch);
    }

    public function put() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());

        if($this->fieldType == 'raw') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->fields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->fields));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        if($this->requestType == 'async') {
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->headers = $this->parseHeader($header);

        $body = substr($response, $headerSize);
        $this->response = $body;

        $info = curl_getinfo($ch);

        $this->responseCode = $info['http_code'];

        curl_close($ch);
    }

    public function patch() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());

        if($this->fieldType == 'raw') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->fields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->fields));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

        if($this->requestType == 'async') {
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->headers = $this->parseHeader($header);

        $body = substr($response, $headerSize);
        $this->response = $body;

        $info = curl_getinfo($ch);

        $this->responseCode = $info['http_code'];

        curl_close($ch);
    }

    public function delete() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        if($this->requestType == 'async') {
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->headers = $this->parseHeader($header);

        $body = substr($response, $headerSize);
        $this->response = $body;

        $info = curl_getinfo($ch);

        $this->responseCode = $info['http_code'];

        curl_close($ch);
    }

    public function parseHeader($header) {
        $headers = array();

        $arrRequests = explode("\r\n\r\n", $header);

        for($i = 0; $i < count($arrRequests) -1; $i++) {
            foreach (explode("\r\n", $arrRequests[$i]) as $i => $line) {
                if($i === 0) {
                    $headers['http_code'] = $line;
                } else {
                    list($key, $value) = explode(': ', $line);
                    $headers[$key] = $value;
                }
            }
        }

        return $headers;
    }

    public function getHeader() {
        if(is_null($this->headers) || $this->headerType == 'default') {
            $headers = array();

            if($this->contentType != '' && $this->contentType != null) {
                $headers[] = 'Content-Type: ' . $this->contentType;
            }

            if($this->accept != '' && $this->accept != null) {
                $headers[] = 'Accept: ' . $this->accept;
            }

            if($this->bearer) {
                $headers[] = 'Authorization: Bearer ' . $this->token;
            }

            if($this->basic) {
                $headers[] = 'Authorization: Basic ' . $this->token;
            }

            if($this->userAgent != '' && $this->userAgent != null) {
                $headers[] = 'User-Agent: ' . $this->userAgent;
            }

            if($this->referer != '' && $this->referer != null) {
                $headers[] = 'Referer: ' . $this->referer;
            }

            if($this->cookie != '' && $this->cookie != null) {
                $headers[] = 'Cookie: ' . $this->cookie;
            }

            if($this->xCSRFToken != '' && $this->xCSRFToken != null) {
                $headers[] = 'X-CSRF-Token: ' . $this->xCSRFToken;
            }

            return $headers;
            
        } else {
            return $this->headers;
        }
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function setMethod($method) {
        $this->method = strtolower($method);
    }

    public function setBearer($bearer) {
        $this->bearer = $bearer;
    }

    public function setBasic($basic) {
        $this->basic = $basic;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function setContentType($contentType) {
        if($contentType == 'json') {
            $this->contentType = 'application/json';

        } elseif($contentType == 'xml') {
            $this->contentType = 'application/xml';

        } elseif($contentType == 'text') {
            $this->contentType = 'application/text';

        } elseif($contentType == 'www-form') {
            $this->contentType = 'application/x-www-form-urlencoded';
        }
    }

    public function setAccept($accept) {
        if($accept == 'json') {
            $this->accept = 'application/json';

        } elseif($accept == 'xml') {
            $this->accept = 'application/xml';

        } elseif($accept == 'text') {
            $this->accept = 'application/text';
        }
    }

    public function setCookie($cookie) {
        $this->cookie = $cookie;
    }

    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

    public function setXCSRFToken($xCSRFToken) {
        $this->xCSRFToken = $xCSRFToken;
    }

    public function setFields($fields) {
        $this->fields = $fields;
    }

    public function setFieldType($fieldType) {
        $this->fieldType = $fieldType;
    }

    public function setRequestType($requestType) {
		$this->requestType = $requestType;
	}

    public function setReferer($referer) {
        $this->referer = $referer;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    public function setHeaderType($headerType) {
        $this->headerType = $headerType;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }
}