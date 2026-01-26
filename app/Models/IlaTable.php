<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IlaTable extends Model {

    public $timestamps = false;

    protected $table = 'ila_table';
    protected $primaryKey = 'ila_code';

    protected $fillable = [
        'firm_name',
        'name',
        'position',
        'telephone',
        'fax',
        'email',
        'use_ila',
        'comments',
        'rating'
    ];
}