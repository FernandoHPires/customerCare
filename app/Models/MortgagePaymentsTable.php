<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortgagePaymentsTable extends Model {

    public $timestamps = false;

    protected $table = 'mortgage_payments_table';
    protected $primaryKey = 'payment_id';

    protected function setKeysForSaveQuery($query) {
        $query
            ->where('payment_id', '=', $this->getAttribute('payment_id'))
            ->where('mortgage_id', '=', $this->getAttribute('mortgage_id'));

        return $query;
    }
}
