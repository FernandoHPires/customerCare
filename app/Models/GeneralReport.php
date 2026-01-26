<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralReport extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'general_report';

}
