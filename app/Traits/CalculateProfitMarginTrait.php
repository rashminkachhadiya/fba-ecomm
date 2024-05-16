<?php

namespace App\Traits;

use App\Models\SupplierProduct;
use Illuminate\Support\Collection;

trait CalculateProfitMarginTrait
{
    private $updateProfitMarginData = [];
    /**
     * Get supplier wise products
     * @param int $supplierId
     */
    public function getSupplierProducts(int $supplierId) : Collection
    {
        return SupplierProduct::whereSupplierId($supplierId)
                        ->with(['amazonProduct' => function($query){
                            return $query->select('id','price','buybox_price','referral_fees','buybox_referral_fees','fba_fees');
                        }])
                        ->select('id','product_id','supplier_id','unit_price')
                        ->get();
    }
}