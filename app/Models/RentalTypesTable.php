<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalTypesTable extends Model
{
    protected $table = 'rental_types_table';

    protected $fillable = [
        'id',
        'name'
    ];
}