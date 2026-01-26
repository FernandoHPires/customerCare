<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTable extends Model {

    public $timestamps = false;

    protected $table = 'asset_table';
    protected $primaryKey = 'asset_id';

}
