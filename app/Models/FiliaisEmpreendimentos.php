<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FiliaisEmpreendimentos extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'filiais_empreendimentos';

    protected $fillable = [
        'filial_id',
        'codigo',
        'descricao',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
