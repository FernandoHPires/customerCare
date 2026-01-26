<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExteriorFinishTable extends Model
{
    protected $table = 'exterior_finish_table';

    protected $fillable = [
        'id',
        'name'
    ];
}