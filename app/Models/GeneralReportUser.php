<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralReportUser extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'general_report_user';

}
