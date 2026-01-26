<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMethodsTable extends Model
{
    protected $table = 'order_methods_table';

    protected $fillable = [
        'id',
        'name'
    ];
}