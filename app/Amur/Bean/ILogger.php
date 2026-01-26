<?php

namespace App\Amur\Bean;

interface ILogger {

    public function info($message, $fields = []);

    public function error($message, $fields = []);

    public function debug($message, $fields = []);

    public function warning($message, $fields = []);

}
