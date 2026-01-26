<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterSourceTable extends Model
{
    protected $table = 'water_source_table';

    protected $fillable = [
        'id',
        'name'
    ];
}