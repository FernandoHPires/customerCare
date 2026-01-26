<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitTypesTable extends Model
{
    protected $table = 'unit_types_table';

    protected $fillable = [
        'id',
        'name',
        'definition'
    ];
}