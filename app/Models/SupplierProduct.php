<?php

namespace App\Models;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AmazonProduct;

class SupplierProduct extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public static function getAmazoneProductDetails()
    {
        return self::join('amazon_products', function ($query) {
            $query->on('supplier_products.product_id', 'amazon_products.id');
        })
            ->select('amazon_products.sku', 'amazon_products.asin', 'amazon_products.title', 'amazon_products.main_image', 'supplier_products.id', 'supplier_products.supplier_sku', 'supplier_products.unit_price')
            ->get();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function amazonProduct()
    {
        return $this->belongsTo(AmazonProduct::class, 'product_id', 'id');
    }

    public function scopeDefaultSupplier($query)
    {
        return $query->where('default_supplier', 1);
    }
}
