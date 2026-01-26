<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceFirmTable extends Model {

    public $timestamps = false;

    protected $table = 'insurance_firm_table';
    protected $primaryKey = 'insurance_code';

}
