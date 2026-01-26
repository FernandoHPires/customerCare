<?php

namespace App\Models;

class SavedQuoteTable extends ModelFingerprint {

    public $fingerprint = true;

    protected $table = 'saved_quote_table';
    protected $primaryKey = 'saved_quote_id';

}
