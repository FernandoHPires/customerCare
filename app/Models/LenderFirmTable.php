<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LenderFirmTable extends Model {

    public $timestamps = false;

    protected $table = 'lender_firm_table';
    protected $primaryKey = 'lender_code';

}
