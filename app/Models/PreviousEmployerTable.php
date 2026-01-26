<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreviousEmployerTable extends Model {

    public $timestamps = false;

    protected $table = 'previous_employer_table';
    protected $primaryKey = 'previous_employer_id';

}
