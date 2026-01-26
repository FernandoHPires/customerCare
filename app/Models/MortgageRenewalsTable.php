<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortgageRenewalsTable extends Model {

    public $timestamps = false;

    protected $table = 'mortgage_renewals_table';
    protected $primaryKey = 'renewal_id';

    protected $fillable = [
        'mortgage_id',
        'renewal_id',
        'renewal_date',
        'next_pmt_date',
        'next_term_due_date',
        'new_interest_rate',
        'renewal_fee',
        'osb_renewal',
        'renewal_paid_over',
        'signed_by_borrower',
        'signed_by_investor',
        'pd_start_date',
        'pd_first_pmt',
        'pd_end_date',
        'pd_reg_pmt',
        'new_monthly_pmt',
        'new_osb',
        'new_aer',
        'new_apr',
        'new_sae_int_rate',
        'completed_by',
        'approved_by',
        'start_value',
        'start_date',
        'int_rate',
        'num_pmts',
        'first_pmt',
        'first_pmt_date',
        'omit_first_pmt',
        'term_date',
        'amort',
        'comments',
        'property_valuation',
        'property_valuation_fee',
        'var_renew',
    ];

    protected function setKeysForSaveQuery($query) {
        $query
            ->where('renewal_id', '=', $this->getAttribute('renewal_id'))
            ->where('mortgage_id', '=', $this->getAttribute('mortgage_id'));

        return $query;
    }
}
