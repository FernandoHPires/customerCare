<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyTypesTable extends Model
{
    protected $table = 'property_types_table';

    protected $fillable = [
        'id',
        'name'
    ];
}