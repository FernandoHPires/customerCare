<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyMortgagesTable extends Model {

    public $timestamps = false;

    protected $table = 'property_mortgages_table';
    protected $primaryKey = 'mortgage_id';

}
