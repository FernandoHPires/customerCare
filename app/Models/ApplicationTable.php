<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationTable extends Model {

    public $timestamps = false;

    protected $table = 'application_table';
    protected $primaryKey = 'application_id';

}
