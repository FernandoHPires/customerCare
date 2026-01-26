<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteCategoriesTable extends Model {

    public $timestamps = false;

    protected $table = 'note_categories_table';
    protected $primaryKey = 'category_id';

}
