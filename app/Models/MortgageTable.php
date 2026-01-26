<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortgageTable extends Model {

    public $timestamps = false;

    protected $table = 'mortgage_table';
    protected $primaryKey = 'mortgage_id';

}
