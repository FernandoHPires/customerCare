<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class MicPipeline extends ModelFingerprint {

    use SoftDeletes;

    protected $table = 'mic_pipeline';

}
