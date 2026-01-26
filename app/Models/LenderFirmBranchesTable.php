<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LenderFirmBranchesTable extends Model {

    public $timestamps = false;

    protected $table = 'lender_firm_branches_table';
    protected $primaryKey = 'lender_branch_code';

}
