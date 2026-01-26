<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class GroupScreen extends ModelFingerprint {
    
    use SoftDeletes;

    protected $table = 'group_screen';

}