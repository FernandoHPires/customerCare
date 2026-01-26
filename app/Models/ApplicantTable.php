<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantTable extends Model {

    public $timestamps = false;

    protected $table = 'applicant_table';
    protected $primaryKey = 'applicant_id';

}
