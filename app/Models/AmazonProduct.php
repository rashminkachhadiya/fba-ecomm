<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmazonProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('amazon_products.is_active', [0, 1]);
    }

    public static function existingList($data = [])
    {
        extract($data);
        // Get all amazon products from db
        $products = self::select('id', 'store_id', 'sku', 'title', 'asin', 'price', 'qty', 'main_image', 'is_active', 'item_height', 'item_length', 'item_width', 'item_weight')
                    ->where('store_id', $storeId);

        if (!empty($asin)) {
            $products->whereIn('asin', $asin);
        }

        $products = $products->get();
        // Make array of fetched SKU
        $productList = [];
        if ($products->count() > 0) {
            foreach ($products as $product) {
                $productList[$product->store_id][$product->$fieldName] = $product;
            }
        }

        return $productList;
    }

    public static function fbaProductExistingSku($storeId)
    {
        return self::select('id', 'sku', 'asin', 'price', 'qty', 'is_active','fnsku','qty', 'afn_reserved_quantity', 'afn_inbound_working_quantity', 'afn_inbound_shipped_quantity', 'afn_inbound_receiving_quantity')
                    ->where('store_id', $storeId)
                    ->where('if_fulfilled_by_amazon', 1)
                    ->get()
                    ->keyBy('sku');
    }

    public function supplier_products()
    {
        return $this->hasMany(SupplierProduct::class, 'product_id')
            ->has('supplier')->with('supplier', function($query){
                return $query->select('id', 'name','lead_time');
            });
    }

    public static function fbaProductReferralFeeSku($storeId)
    {
        return self::select('id', 'sku', 'product_id', 'asin', 'price', 'qty', 'is_active','fnsku','qty','estimated_fee_total','estimated_referral_fee_per_unit','expected_fulfillment_fee_per_unit','if_fulfilled_by_amazon')
        ->where('store_id', $storeId)
        ->where('if_fulfilled_by_amazon', '1')
        ->get()
        ->keyBy('sku');
    }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class, 'product_id');
    }

    public function salesVelocity()
    {
        return $this->hasOne(SalesVelocity::class, 'amazon_product_id','id');
    }
}
