<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedQuotePositionsTable extends Model {

    public $timestamps = false;

    protected $table = 'saved_quote_positions_table';
    protected $primaryKey = 'saved_quote_position_id';

}
