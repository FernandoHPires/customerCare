<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CmsCommissionSetup extends ModelFingerprint {

    // public $fingerprint = true;

    protected $table = 'cms_commission_setup';

    protected $fillable = [
        'cms_type_id',
        'cms_agent_id',
        'effective_at',
        'amount',
        'minimum_amount',
        'percentage',
        'bonus',
        'enable_bonus',
        'enable_minimum_amount',
        'created_at',
        'updated_at',
    ];
}