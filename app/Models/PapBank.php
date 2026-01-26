<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PapBank extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;

    protected $table = 'pap_bank';

}
