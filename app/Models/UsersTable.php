<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsersTable extends Authenticatable {

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
        'session_token',
        'reset_request',
        'is_uni_user',
        'active_company_id',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_attempts',
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
        'locked_until'           => 'datetime',
        'last_login_attempt'     => 'datetime',
        'two_factor_expires_at'  => 'datetime',
    ];

    // Necessário para Auth::login() saber qual coluna é a senha
    public function getAuthPassword(): string {
        return $this->user_password;
    }
}
