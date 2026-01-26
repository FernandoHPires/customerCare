<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotesTable extends Model {

    public $timestamps = false;

    protected $table = 'notes_table';
    protected $primaryKey = 'note_id';

}
