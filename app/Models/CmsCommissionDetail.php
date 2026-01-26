<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsCommissionDetail extends Model {

    use HasFactory, SoftDeletes;

    protected $table = 'cms_commission_detail';

}