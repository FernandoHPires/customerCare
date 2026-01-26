<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhoWillPayTable extends Model
{
    protected $table = 'who_will_pay_table';

    protected $fillable = [
        'id',
        'name'
    ];
}