<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatTable extends Model
{
    protected $table = 'heat_table';

    protected $fillable = [
        'id',
        'name'
    ];
}