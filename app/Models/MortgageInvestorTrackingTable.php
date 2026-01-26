<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortgageInvestorTrackingTable extends Model {

    public $timestamps = false;

    protected $table = 'mortgage_investor_tracking_table';
    protected $primaryKey = 'mortgage_id';

    protected function setKeysForSaveQuery($query) {
        $query
            ->where('mortgage_id', '=', $this->getAttribute('mortgage_id'))
            ->where('investor_tracking_id', '=', $this->getAttribute('investor_tracking_id'));

        return $query;
    }
}
