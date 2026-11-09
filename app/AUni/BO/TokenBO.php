<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use App\AUni\Bean\Token;
use App\AUni\Utilities\HttpRequest;
use DateTime;
use DateInterval;
use DateTimeZone;
use Exception;

class TokenBO {

    private $logger;
    private $db;
    private $platform;

    public function __construct(ILogger $logger, IDB $db) {

        $this->logger = $logger;
        $this->db = $db;
    }

    public function getAccessToken() {

        $this->logger->debug('TokenBO->getAccessToken', [$this->platform]);

        $token = new Token($this->logger, $this->db, $this->platform);
        $limit = (new DateTime())->add(new DateInterval('PT5M'));

        if(!empty($token->getAccessToken()) && $token->getAccessTokenExpires() > $limit) {
            return $token->getAccessToken();
        }

        return $this->refreshToken($token);
    }

    public function refreshToken(?Token $current = null) {

        $this->logger->debug('TokenBO->refreshToken', [$this->platform]);

        if($this->platform != 'MEGA') {
            $this->logger->error('TokenBO->refreshToken - Invalid platform', [$this->platform]);
            return null;
        }

        $response = null;

        if($current != null
            && !empty($current->getRefreshToken())
            && $current->getRefreshTokenExpires() > new DateTime()) {
            $response = $this->megaRefreshToken($current->getRefreshToken());
        }

        if($response == null) {
            $response = $this->megaSignIn();
        }

        if($response == null) {
            return null;
        }

        $token = new Token($this->logger, $this->db, null);
        $token->setPlatform($this->platform);
        $token->setAuthorizationCode('');
        $token->setState('');
        $token->setTokenType('Bearer');
        $token->setAccessToken($response->accessToken);
        $token->setAccessTokenExpires($this->parseExpiration($response->expirationToken, 'PT2H'));
        $token->setRefreshToken($response->refreshToken);
        $token->setRefreshTokenExpires($this->parseExpiration($response->expirationRefreshToken, 'PT4H'));
        $token->insert();

        return $response->accessToken;
    }

    public function setPlatform($platform) {

        $this->platform = $platform;
    }

    private function megaSignIn() {

        $this->logger->info('TokenBO->megaSignIn', [$this->platform]);

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setMethod('post');
        $httpRequest->setUrl(config('mega.base_url') . '/api/Auth/SignIn');
        $httpRequest->setHeaderType('custom');
        $httpRequest->setHeaders([
            'Content-Type: application/json',
            'Accept: application/json',
            'tenantId: ' . config('mega.tenant_id'),
            'grantType: Api'
        ]);
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode([
            'userName' => config('mega.username'),
            'password' => config('mega.password')
        ]));
        $httpRequest->exec();

        return $this->parseTokenResponse($httpRequest, 'TokenBO->megaSignIn');
    }

    private function megaRefreshToken($refreshToken) {

        $this->logger->info('TokenBO->megaRefreshToken', [$this->platform]);

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setMethod('post');
        $httpRequest->setUrl(config('mega.base_url') . '/api/Auth/RefreshToken?refreshToken=' . rawurlencode($refreshToken));
        $httpRequest->setHeaderType('custom');
        $httpRequest->setHeaders([
            'Accept: application/json',
            'tenantId: ' . config('mega.tenant_id')
        ]);
        $httpRequest->exec();

        return $this->parseTokenResponse($httpRequest, 'TokenBO->megaRefreshToken');
    }

    private function parseTokenResponse(HttpRequest $httpRequest, $source) {

        $responseCode = $httpRequest->getResponseCode();
        $response = json_decode($httpRequest->getResponse());

        if($responseCode != 200 || !isset($response->accessToken) || empty($response->accessToken)) {
            $this->logger->error($source . ' - Token request failed', ['response_code' => $responseCode]);
            return null;
        }

        return $response;
    }

    private function parseExpiration($value, $fallback) {

        try {
            $expiration = new DateTime($value);
            $expiration->setTimezone(new DateTimeZone(config('app.timezone')));
            return $expiration;
        } catch(Exception $e) {
            $this->logger->warning('TokenBO->parseExpiration - Invalid expiration date', [$value]);
            return (new DateTime())->add(new DateInterval($fallback));
        }
    }
}
