<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpouseTable extends Model {

    public $timestamps = false;

    protected $table = 'spouse_table';
    protected $primaryKey = 'spouse_id';

}
