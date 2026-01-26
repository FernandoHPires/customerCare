<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiabilityTable extends Model {

    public $timestamps = false;

    protected $table = 'liability_table';
    protected $primaryKey = 'liability_id';

}
