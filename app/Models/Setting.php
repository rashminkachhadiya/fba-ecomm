<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted() : void
    {
        static::created(function() {
            if(Cache::has('general_settings'))
            {
                Cache::forget('general_settings');
            }
        });

        static::updated(function() {
            if(Cache::has('general_settings'))
            {
                Cache::forget('general_settings');
            }
        });
    }
}
