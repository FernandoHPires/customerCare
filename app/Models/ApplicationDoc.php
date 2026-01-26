<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDoc extends Model {
    
    public $timestamps = false;

    protected $table = 'application_doc';
    protected $primaryKey = 'id';

    protected $fillable = [
        'application_id',
        'mortgage_id',
        'doc_type',
        'broker_notes',
        'support_status',
        'accounting_status',
        'create_date',
        'update_date',
    ];
}