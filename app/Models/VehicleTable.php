<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleTable extends Model {

    public $timestamps = false;

    protected $table = 'vehicle_table';
    protected $primaryKey = 'vehicle_id';

}
