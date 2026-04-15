<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model {

    public $timestamps = false;

    protected $table = 'password_history';

    protected $fillable = [
        'user_id',
        'password_hash',
        'created_at',
        'created_by',
    ];
}
