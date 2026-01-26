<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SalesJourney extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;
    
    protected $table = 'sales_journey';

}


