<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaritalStatusTable extends Model
{
    protected $table = 'marital_status_table';

    protected $fillable = [
        'id',
        'name'
    ];
}