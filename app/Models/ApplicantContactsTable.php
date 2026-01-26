<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantContactsTable extends Model {

    public $timestamps = false;

    protected $table = 'applicant_contacts_table';
    protected $primaryKey = 'contact_id';

}
