<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyOrderDetail extends Model
{
    use HasFactory;

    protected $guarded = [];
    /*
      @Description	: Function to check if order detail already exists
      @Author			: Sanjay Chabhadiya
      @Input			:
      @Output			:
      @Date			: 23-03-2021
     */

    public static function orderDetailExists($shopifyId)
    {
        if (!empty($shopifyId)) {
        	return  self::where('shopify_order_id', $shopifyId)
            ->first();
        }

        return [];
    }
}
