<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function orderItems()
    {
      return $this->hasMany(ShopifyOrderItem::class);
    }

    public function order()
    {
      return $this->morphOne(Order::class, 'order_reference');
    }

    public function orderDetails()
    {
      return $this->hasMany(ShopifyOrderDetail::class);
    }
    /*
      @Description  : Function to fetch the date & time of latest order we have
      @Author   : Sanjay Chabhadiya
      @Input    : store_id
      @Output   :
      @Date     : 23-03-2021
    */

    public static function getLatestOrderDatetime($storeId)
    {
        if (!empty($storeId)) {
        	return self::where('store_id', $storeId)
        		->max('order_last_updated_date');
        }

        return [];
    }

    /*
      @Description	: Function to check if order already exists
      @Author			: Sanjay Chabhadiya
      @Input			:
      @Output			:
      @Date			: 23-03-2021
     */

    public static function orderExists($storeId, $orderId)
    {
        if (!empty($storeId) && !empty($orderId)) {
        	return  self::where('store_id', $storeId)
            ->where('shopify_unique_id', $orderId)
            ->first();
        }

        return [];
    }

    /*
      @Description: Function to fetch the shopify orders
      @Author     : Sanjay Chabhadiya
      @Input      :
      @Output     : shopify order details
      @Date       : 07-04-2021
     */
    public static function getOrders($storeId = null, $shopifyUniqueId = null, $limit = null)
    {
        
        $order = self::whereIn('processed', ['1', '2'])
        ->where('updated', '0');
        if (!empty($storeId)) {
            $order->where('store_id', $storeId);
        }

        if (!empty($shopifyUniqueId)) {
            $order->where('shopify_unique_id', $shopifyUniqueId);
        }

        if (!empty($limit)) {
          $order->limit($limit);
        }

        return $order->with(['order'])->orderBy('id', 'ASC')->get();
       
    }
}
