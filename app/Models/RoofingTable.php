<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoofingTable extends Model
{
    protected $table = 'roofing_table';

    protected $fillable = [
        'id',
        'name'
    ];
}