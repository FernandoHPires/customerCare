<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class RenewalApproval extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;
    
    protected $table = 'renewal_approval';

}


