<?php

namespace App\Models;

use App\Helpers\CommonHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonCronLog extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'store_id',
    //     'amazon_feed_id',
    //     'cron_name',
    //     'cron_type',
    //     'cron_param',
    //     'start_time',
    //     'end_time'
    // ];

    protected $guarded = [];

    public $timestamps = false;

    public static function cronStartEndUpdate( $cron )
    {
        // If action is start
        if( $cron['action'] === 'start' )
        {
            $cron['start_time'] = Carbon::now();
            unset( $cron['action'] );
            return self::create( $cron );
        }
    }

    /* Update cron end_time */
    public function updateEndTime()
    {
        $this->update(['end_time' => Carbon::now()]);
    }

}
