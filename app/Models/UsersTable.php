<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersTable extends Model {

    public $timestamps = false;

    protected $table = 'users_table';
    protected $primaryKey = 'user_id';

}
