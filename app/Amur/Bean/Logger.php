<?php

namespace App\Amur\Bean;

use Illuminate\Support\Facades\Log;
use App\Amur\Utilities\Utils;
use Illuminate\Support\Facades\Session;

class Logger implements ILogger {

    public function __construct() {
        
    }

    public function info($message, $fields = []) {
        Log::info(substr($this->getSessionKey(),0,8) . ' ' . $message, $fields);
    }

    public function error($message, $fields = []) {
        Log::error($message, $fields);

        if(env('APP_ENV') == 'production') {
            Utils::sendEmail(
                ['diego@amurgroup.ca','fernando@amurgroup.ca'],
                env('APP_ENV') . ' - Strive Error',
                $this->getSessionKey() . ' ' . $message . "\n\n" . json_encode($fields)
            );
        }
    }

    public function debug($message, $fields = []) {
        Log::debug(substr($this->getSessionKey(),0,8) . ' ' . $message, $fields);
    }

    public function warning($message, $fields = []) {
        Log::warning(substr($this->getSessionKey(),0,8) . ' ' . $message, $fields);
    }

    private function getSessionKey() {
        /*if(session('session_key') === null || session('session_key') == '') {
            $sessionKey = substr(sha1(uniqid()),0,8);
            session(['session_key' => $sessionKey]);
        } else {
            $sessionKey = session('session_key');
        }*/

        return Session::getId();
    }
}
