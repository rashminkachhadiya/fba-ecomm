<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonOrderReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function getLatestRecord()
    {
        return self::max('last_updated_date');
    }

    public static function getExistsOrderData($productId, $amazonOrderId)
    {
        return self::select('id', 'product_id', 'amazon_order_id')->where('product_id', $productId)->where('amazon_order_id', $amazonOrderId)->first();
    }

    public function scopeLastNDates($query, $dates)
    {
        return $query->whereBetween('order_date', $dates);
    }
}
