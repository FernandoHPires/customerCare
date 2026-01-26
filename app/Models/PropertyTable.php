<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyTable extends Model {

    public $timestamps = false;

    protected $table = 'property_table';
    protected $primaryKey = 'property_id';

}
