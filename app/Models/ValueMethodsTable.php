<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValueMethodsTable extends Model
{
    protected $table = 'value_methods_table';

    protected $fillable = [
        'id',
        'name'
    ];
}