<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\SourcesTable;

class SourceBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getAllData() {

        $data = SourcesTable::query()
        ->get();

        $sources = array();
        if($data) {
            foreach($data as $key => $value) {
                $sources[] = [
                    'id' => $value->id,
                    'source' => $value->source,
                ];
            }
        }
        
        return $sources;

    }

    public function getAutoCompleteSourceData() {

        $data = SourcesTable::query()->get();

        $sources = array();
        if($data) {
            foreach($data as $key => $value) {
                $sources[] = [
                    'id' => $value->id,
                    'fullName' => $value->source,
                ];
            }
        }
        
        return $sources;

    }

}
