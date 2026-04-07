<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Viabilidades extends ModelFingerprint {
    
    use SoftDeletes;

    protected $table = 'viabilidades';

}