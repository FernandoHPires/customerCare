<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporationTable extends Model {

    public $timestamps = false;

    protected $table = 'corporation_table';
    protected $primaryKey = 'corporation_id';

}
