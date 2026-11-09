<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Clientes extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'nome_fantasia',
        'cnpj',
        'email',
        'telefone',
        'status',
        'filial',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
