<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'menu';

}