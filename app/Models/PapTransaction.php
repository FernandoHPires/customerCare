<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PapTransaction extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;

    protected $table = 'pap_transaction';

}
