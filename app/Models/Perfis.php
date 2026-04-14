<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Perfis extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'perfis';

    protected $fillable = [
        'nome',
        'descricao',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
