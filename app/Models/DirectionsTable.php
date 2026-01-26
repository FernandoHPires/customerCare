<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectionsTable extends Model
{
    public $timestamps = false;

    protected $table = 'directions_table';
    protected $primaryKey = 'abbreviation';
    public $incrementing = false;
    protected $fillable = ['abbreviation', 'name'];
}