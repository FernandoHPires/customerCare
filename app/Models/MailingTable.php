<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingTable extends Model {

    public $timestamps = false;

    protected $table = 'mailing_table';
    protected $primaryKey = 'mailing_id';

}
