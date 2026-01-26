<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPipelineTable extends Model
{
    protected $table = 'daily_pipeline';
    protected $primaryKey = 'id';
    
    // Enable timestamps for created_at and updated_at fields
    public $timestamps = true;

    protected $fillable = [
        'origination_company_id',
        'stage',
        'reference_date',
        'gross_amount',
        'count',
        'created_at',
        'updated_at',
    ];
}