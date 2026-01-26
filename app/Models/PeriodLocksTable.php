<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodLocksTable extends Model {

    public $timestamps = false;

    protected $table = 'period_locks_table';
    protected $primaryKey = 'company_id';

    protected function setKeysForSaveQuery($query) {
        $query
            ->where('company_id', '=', $this->getAttribute('company_id'))
            ->where('start_date', '=', $this->getAttribute('start_date'))
            ->where('end_date', '=', $this->getAttribute('end_date'));

        return $query;
    }
}
