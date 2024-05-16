<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AmazonProduct;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public function product()
    {
        return $this->belongsTo(AmazonProduct::class,'product_id','id');
    }

    public function supplierProduct()
    {
        return $this->belongsTo(SupplierProduct::class,'supplier_product_id','id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class,'po_id','id');
    }
}
