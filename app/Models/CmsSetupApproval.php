<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CmsSetupApproval extends ModelFingerprint
{


    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'cms_setup_approval';

    protected $fillable = [
        'table_name',
        'table_id',
        'description',
        'accounting_status',
        'accounting_at',
        'accounting_by',
        'executive_status',
        'executive_at',
        'executive_by',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
    ];
}
