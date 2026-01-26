<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceFirmBranchesTable extends Model {

    public $timestamps = false;

    protected $table = 'insurance_firm_branches_table';
    protected $primaryKey = 'insurance_branch_code';

}
