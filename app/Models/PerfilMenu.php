<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PerfilMenu extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'perfil_menu';

    protected $fillable = [
        'perfil_id',
        'menu_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
