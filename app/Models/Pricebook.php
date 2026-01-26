<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Pricebook extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'pricebook';

}
