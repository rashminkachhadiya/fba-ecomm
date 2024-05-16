<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonReportLog extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'report_type', 'request_id', 'is_processed', 'requested_date', 'processed_date', 'cut_off_time'];

    public static function getAmazonReportLog($storeId, $reportType = null)
    {
        return self::where('store_id', $storeId)
            ->where('is_processed', '0')
            ->where('report_type', !empty($reportType) ? $reportType : 'GET_MERCHANT_LISTINGS_DATA')
            ->first();
    }
}
