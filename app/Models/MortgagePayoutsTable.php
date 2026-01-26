<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortgagePayoutsTable extends Model {

    public $timestamps = false;

    protected $table = 'mortgage_payouts_table';
    protected $primaryKey = 'payout_id';

    protected function setKeysForSaveQuery($query) {
        $query
            ->where('payout_id', '=', $this->getAttribute('payout_id'))
            ->where('mortgage_id', '=', $this->getAttribute('mortgage_id'));

        return $query;
    }

}
