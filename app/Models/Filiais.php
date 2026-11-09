<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Filiais extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'filiais';

    protected $fillable = [
        'codigo',
        'nome',
        'contas_receber',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
