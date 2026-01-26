<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseStyleTable extends Model
{
    protected $table = 'house_style_table';

    protected $fillable = [
        'id',
        'name'
    ];
}