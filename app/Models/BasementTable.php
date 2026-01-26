<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasementTable extends Model
{
    protected $table = 'basement_table';

    protected $fillable = [
        'id',
        'name'
    ];
}