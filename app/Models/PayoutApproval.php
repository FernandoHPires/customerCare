<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutApproval extends ModelFingerprint {

    use SoftDeletes;

    public $fingerprint = true;

    protected $table = 'payout_approval';

}
