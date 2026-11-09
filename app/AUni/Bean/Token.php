<?php

namespace App\AUni\Bean;

use Carbon\Carbon;
use DateTime;

class Token {

    private $logger;
    private $db;

    private $tokenId;
    private $platform;
    private $authorizationCode;
    private $state;
    private $tokenType;
    private $accessToken;
    private $accessTokenExpires;
    private $refreshToken;
    private $refreshTokenExpires;
    private $createdAt;

    public function __construct(ILogger $logger, IDB $db, $platform = null) {

        $this->logger = $logger;
        $this->db = $db;

        if($platform == null) {
            $this->tokenId = null;
        } else {
            $this->getLastTokenByPlatform($platform);
        }
    }

    public function getLastTokenByPlatform($platform) {

        $this->logger->debug('Token->getLastTokenByPlatform', [$platform]);

        $sql = "select * from token
                 where platform = ?
              order by access_token_expires desc
                 limit 1";
        $res = $this->db->select($sql, [$platform]);

        if(count($res) > 0) {
            $this->tokenId = $res[0]->id;
            $this->platform = $res[0]->platform;
            $this->authorizationCode = $res[0]->authorization_code;
            $this->state = $res[0]->state;
            $this->tokenType = $res[0]->token_type;
            $this->accessToken = $res[0]->access_token;
            $this->accessTokenExpires = new DateTime($res[0]->access_token_expires);
            $this->refreshToken = $res[0]->refresh_token;
            $this->refreshTokenExpires = new DateTime($res[0]->refresh_token_expires);
            $this->createdAt = new DateTime($res[0]->created_at);
        }
    }

    public function insert() {

        $fields = [
            'platform' => $this->platform,
            'authorization_code' => $this->authorizationCode,
            'state' => $this->state,
            'token_type' => $this->tokenType,
            'access_token' => $this->accessToken,
            'access_token_expires' => $this->accessTokenExpires->format('Y-m-d H:i:s'),
            'refresh_token' => $this->refreshToken,
            'refresh_token_expires' => $this->refreshTokenExpires->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()
        ];

        $this->tokenId = $this->db->insertGetId('token', $fields, 'id');
    }

    public function getTokenId() {

        return $this->tokenId;
    }

    public function getPlatform() {

        return $this->platform;
    }

    public function getAuthorizationCode() {

        return $this->authorizationCode;
    }

    public function getState() {

        return $this->state;
    }

    public function getTokenType() {

        return $this->tokenType;
    }

    public function getAccessToken() {

        return $this->accessToken;
    }

    public function getAccessTokenExpires() {

        return $this->accessTokenExpires;
    }

    public function getRefreshToken() {

        return $this->refreshToken;
    }

    public function getRefreshTokenExpires() {

        return $this->refreshTokenExpires;
    }

    public function getCreatedAt() {

        return $this->createdAt;
    }

    public function setPlatform($platform) {

        $this->platform = $platform;
    }

    public function setAuthorizationCode($authorizationCode) {

        $this->authorizationCode = $authorizationCode;
    }

    public function setState($state) {

        $this->state = $state;
    }

    public function setTokenType($tokenType) {

        $this->tokenType = $tokenType;
    }

    public function setAccessToken($accessToken) {

        $this->accessToken = $accessToken;
    }

    public function setAccessTokenExpires(DateTime $accessTokenExpires) {

        $this->accessTokenExpires = $accessTokenExpires;
    }

    public function setRefreshToken($refreshToken) {

        $this->refreshToken = $refreshToken;
    }

    public function setRefreshTokenExpires(DateTime $refreshTokenExpires) {

        $this->refreshTokenExpires = $refreshTokenExpires;
    }

    public function setCreatedAt(DateTime $createdAt) {

        $this->createdAt = $createdAt;
    }
}
