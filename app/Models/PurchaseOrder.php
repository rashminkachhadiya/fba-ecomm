<?php

namespace App\Models;

use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public function setPOOrderDateAttribute($value)
    {
        $this->attributes['po_order_date'] = Carbon::createFromFormat('m-d-Y', $value)->format('Y-m-d');
    }

    public function setExpectedDeliveryDateAttribute($value)
    {
        if ($value == '') {
            $this->attributes['expected_delivery_date'] = "0000-00-00";
        } else {
            $this->attributes['expected_delivery_date'] = Carbon::createFromFormat('m-d-Y', $value)->format('Y-m-d');
        }
    }

    public function getExpectedDeliveryDateAttribute($value)
    {
        if (!empty($value) && $value != '0000-00-00') {
            return Carbon::parse($value)->format('d-m-Y');
        } else {
            return '';
        }

    }

    public function getPOOrderDateAttribute($value)
    {
        if (!empty($value)) {
            return Carbon::parse($value)->format('d-m-Y');
        }
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public static function getPurchaseOrderIteams(string $id)
    {
        $items = self::leftJoin('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id')
            ->leftJoin('supplier_products', 'purchase_order_items.supplier_product_id', '=', 'supplier_products.id')
            ->select('purchase_order_items.po_id', 'purchase_order_items.product_id', 'purchase_order_items.supplier_id', 'purchase_order_items.order_qty',
                'purchase_order_items.total_price', 'purchase_order_items.received_qty', 'purchase_order_items.received_price',
                'purchase_order_items.difference_qty', 'purchase_order_items.difference_price',
                'amazon_products.title', 'amazon_products.sku', 'amazon_products.asin', 'amazon_products.main_image',
                'amazon_products.price', 'supplier_products.supplier_sku', 'purchase_order_items.unit_price')
            ->where('purchase_orders.id', $id)
            ->where('purchase_order_items.deleted_at', null)
            ->get()
            ->toArray();

        return $items;
    }

    public function amazonProduct()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'id', 'po_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id', 'id');
    }
    public static function getPurchaseOrderIteamsForExport(string $id)
    {
        $items = self::leftJoin('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id')
            ->leftJoin('supplier_products', 'purchase_order_items.supplier_product_id', '=', 'supplier_products.id')
            ->select('amazon_products.title',
                'amazon_products.sku',
                'amazon_products.asin',
                'supplier_products.supplier_sku',
                'purchase_order_items.unit_price',
                'purchase_order_items.order_qty',
                'purchase_order_items.total_price')
            ->where('purchase_orders.id', $id)
            ->where('purchase_order_items.deleted_at', null)
            ->get();

        return $items;
    }

    public function scopeStatus($query, array $value)
    {
        return $query->whereIn('status', $value);
    }
}
