<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRentalsTable extends Model {

    public $timestamps = false;

    protected $table = 'property_rentals_table';
    protected $primaryKey = 'rental_id';

}
