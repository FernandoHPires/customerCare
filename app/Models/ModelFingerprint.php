<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ModelFingerprint extends Model {
    
    protected static function boot() {
        parent::boot();

        static::creating(function($model) {
            if(static::usesFingerprint() && ($user = Auth::user())) {
                $model->created_by = $user->user_id;
                $model->updated_by = $user->user_id;
            }
        });

        static::updating(function($model) {
            if(static::usesFingerprint() && ($user = Auth::user())) $model->updated_by = $user->user_id;
        });

        static::deleting(function($model) {
            if(static::usesFingerprint() && ($user = Auth::user())) {
                $model->updated_by = $user->user_id;
                $model->deleted_by = $user->user_id;
                $model->save();
            }
        });
    }

    public static function usesFingerprint() {

        $class = get_called_class();

        if(isset(get_class_vars($class)['fingerprint']) && get_class_vars($class)['fingerprint']) {
            return true;
        }

        return false;
    }

}