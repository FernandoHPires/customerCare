<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SalesforceCap extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;
    
    protected $table = 'salesforce_cap';

}
