<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreetsTable extends Model
{
    public $timestamps = false;

    protected $table = 'streets_table';
    protected $primaryKey = 'abbreviation';
    public $incrementing = false;
    protected $fillable = ['abbreviation', 'name'];
}