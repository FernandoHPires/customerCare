<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FilterOption extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;
    
    protected $table = 'filter_option';

}
