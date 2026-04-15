<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersTable extends Model {

    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'users_table';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_name',
        'user_password',
        'user_fname',
        'user_lname',
        'user_email',
        'user_phone',
        'user_dept',
        'default_company_id',
        'perfil_id',
        'admin',
        'session_token',
        'login_attempts',
        'locked_until',
        'last_login_attempt',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'user_password',
    ];

    protected $casts = [
        'locked_until'        => 'datetime',
        'last_login_attempt'  => 'datetime',
    ];
}
