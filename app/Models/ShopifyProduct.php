<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /*
    @Description    : Function to get list of all the existing products
    @Author         : Kinjal Prajapati
    @Input          : storeId
    @Output         : array of SKU
    @Date           : 09-03-2021
     */
    public static function getExistingProducts($data = [])
    {
        extract($data);
        // Get all amazon products from db
        $products = self::select('id', 'sku', 'title', 'description', 'main_image', 'price', 'quantity', 'unique_id')
        ->where('store_id', $storeId);

        if (!empty($uniqueIds)) {
            $products->whereIn('unique_id', $uniqueIds);
        }

        return $products->get()->keyBy('unique_id')->toArray();
    }

    /*
    @Description    : Function to update the existing product in shopify table
    @Author         : Kinjal Prajapati
    @Input          : data
    @Output         :
    @Date           : 17-03-2021
     */

    public static function batchUpdate($data = [])
    {
        if (is_array($data) && !empty($data)) {
            self::upsert($data, ['id'], array_keys($data[0]));
        }
    }

    public static function getExistingProductVariations($productId) {

        if (!empty($productId)) {
            return self::select('unique_id')
                ->where('parent_id', $productId)
                ->whereNotNull('parent_id')
                ->pluck('unique_id', 'unique_id')->toArray();
        }
    }

    /*
      @Description: Function to get the product details
      @Author:      Kinjal Prajapati Used Niranka Busa's code from TNT 
      @Input:
      @Output:
      @Date:        17-03-2021
     */
    public static function getProductLocations($storeId)
    {
        return self::select('id', 'inventory_item_id')
        ->whereNotNull('inventory_item_id')
        ->whereNull('location_id')
        ->where('store_id', $storeId)
        ->get()->toArray();
    }

    /*
    @Description    : Function to get shopify products
    @Author         : Sanjay Cha bhadiya
    @Input          :
    @Output         :
    @Date           : 19-03-2021
    */
    public static function getProducts($limit = null)
    {
        return self::whereNull('product_id')->whereNotNull('title')
        ->orderBy('id', 'ASC')->limit($limit)
        ->with(['productThroughSku' => function($query) {
            $query->select('id', 'sku')->with(['productShippingDetail']);
        }, 'productProperty'])
        ->get();
    }

    public function getVariantList($params,$parentProductId)
    {
        return ShopifyProduct::with('store.store_config')->select('id','parent_id','store_id','sku','unique_id','title','main_image','price','quantity','handle')->where('parent_id',$parentProductId)->get();
    }
}
