<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brokers extends Model {

    public $timestamps = false;

    protected $table = 'broker';
    protected $primaryKey = 'broker_id';

}
