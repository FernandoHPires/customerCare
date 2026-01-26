<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsCommission extends ModelFingerprint {

    use HasFactory, SoftDeletes;

    public $fingerprint = true;

    protected $table = 'cms_commission';

}