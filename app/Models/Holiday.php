<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;

    protected $table = 'holiday';

}
