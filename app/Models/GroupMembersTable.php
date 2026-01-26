<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembersTable extends Model {

    public $timestamps = false;

    protected $table = 'group_members_table';
    protected $primaryKey = 'id';

}
