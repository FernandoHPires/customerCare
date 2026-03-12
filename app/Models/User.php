<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class User extends Model implements AuthenticatableContract {

    use Authenticatable;

    protected $table = 'users_table';
    protected $primaryKey = 'user_id';

    public function getAuthPassword() {
        return Hash::make($this->user_password);
    }

}
