<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInviteToken extends Model {

    public $timestamps = false;

    protected $table = 'user_invite_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
        'created_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(UsersTable::class, 'user_id', 'user_id');
    }
}
