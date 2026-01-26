<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class DmsTemplate extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;

    protected $table = 'dms_template';

}
