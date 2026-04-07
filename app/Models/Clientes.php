<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Clientes extends ModelFingerprint {
    
    use SoftDeletes;

    protected $table = 'clientes';

}