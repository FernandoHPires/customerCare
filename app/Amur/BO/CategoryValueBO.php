<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\CategoryValue;

class CategoryValueBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getDataByCategoryId($categoryId) {

        $data = CategoryValue::query()
        ->where('category_id', $categoryId)
        ->get();

        $categories = array(
            [ "id" => "", "name" => "" ]
        );
        if($data) {
            foreach($data as $key => $value) {
                $categories[] = [
                    "id" => $value->id,
                    "name" => $value->name
                ];
            }
        }

        return $categories;
    }
}
