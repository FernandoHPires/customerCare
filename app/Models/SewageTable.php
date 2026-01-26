<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewageTable extends Model
{
    protected $table = 'sewage_table';

    protected $fillable = [
        'id',
        'name'
    ];
}