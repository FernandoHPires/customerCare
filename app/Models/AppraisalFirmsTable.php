<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalFirmsTable extends Model {

    public $timestamps = false;

    protected $table = 'appraisal_firms_table';
    protected $primaryKey = 'appraisal_firm_code';

}
