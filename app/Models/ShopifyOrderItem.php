<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyOrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function shopifyOrder()
    {
      return $this->belongsTo(ShopifyOrder::class);
    }

    public function orderItem()
    {
      return $this->morphOne(OrderItem::class, 'order_item');
    }

    public function product()
    {
      return $this->belongsTo(ShopifyProduct::class, 'product_id', 'unique_id');
    }

    /*
      @Description	: Function to check if order item already exists
      @Author			: Sanjay Chabhadiya
      @Input			:
      @Output			:
      @Date			: 23-03-2021
    */

    public static function orderItemExist($shopifyId = null, $shopifyItemId = null)
    {
        if (!empty($shopifyId) && !empty($shopifyItemId)) {
        	return self::select('id')
        	->where('shopify_order_id', $shopifyId)
            ->where('shopify_item_id', $shopifyItemId)
            ->first();
        }
        
        return [];
    }

    /*
      @Description: Function to fetch the shopify order items
      @Author     : Sanjay Chabhadiya
      @Input      :
      @Output     : shopify order items details
      @Date       : 07-04-2021

    */
    public static function orderItems($storeId = null, $shopifyUniqueId = null, $orderId = null)
    {
        $orderItems = self::select('shopify_order_items.*','marketplace_products.product_id as marketplace_products_product_id', 'total_shipping_tax', 'items_count', 'shipping_price','shopify_orders.financial_status','shopify_orders.order_cancelled_date','shopify_orders.cancel_reason','shopify_orders.order_date','marketplace_products.id as marketplace_product_id','product_masters.product_weight')
        ->where('shopify_order_items.updated', '0')
        ->whereIn('shopify_orders.processed', ['1', '2'])
        ->with(['orderItem'])
        ->join('shopify_orders', function($query){
            $query->on('shopify_order_items.shopify_order_id','shopify_orders.id');
        })
        ->leftJoin('marketplace_products', function($query){
            $query->on('marketplace_products.store_id','shopify_orders.store_id')
            ->on('marketplace_products.sku','shopify_order_items.sku')
            ->on('marketplace_products.marketplace_unique_id','shopify_order_items.variant_id')
            ->where('marketplace_products.is_merge', '1');
        })
        ->leftJoin('product_masters',function($productMasterQuery){
            $productMasterQuery->on('product_masters.id','marketplace_products.product_id');
        });

        if (!empty($storeId)) {
            $orderItems->where('shopify_orders.store_id', $storeId);
        }

        if (!empty($shopifyUniqueId)) {
            $orderItems->where('shopify_orders.shopify_unique_id', $shopifyUniqueId);
        }

        if (!empty($orderId)) {
          $orderItems->whereIn('shopify_order_items.shopify_order_id', $orderId);
        }
        
        return $orderItems->groupBy('shopify_order_items.id')->orderBy('shopify_order_items.id', 'ASC')->get();
    }
}
