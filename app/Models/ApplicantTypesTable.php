<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantTypesTable extends Model
{
    protected $table = 'applicant_types_table';

    protected $fillable = [
        'id',
        'name',
        'time_stamp'
    ];
}