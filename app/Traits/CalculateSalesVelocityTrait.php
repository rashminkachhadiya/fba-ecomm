<?php

namespace App\Traits;

use App\Models\AmazonOrderReport;
use App\Models\AmazonProduct;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait CalculateSalesVelocityTrait
{
    private int $productOffset = 0;
    private int $productLimit = 100;

    /**
     * Get duration wise dates
     * @param int $duration
     */
    public function getlastNDates(int $duration) : array
    {
        $dates = [];
        for ($i=$duration; $i > 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            if($i == $duration)
            {
                $date = "$date 00:00:00";
                $dates[] = $date;
            }
            
            if($i == 1){
                $date = "$date 23:59:59";
                $dates[] = $date;
            }

            continue;
        }
        return $dates;
    }

    /**
     * For calculate suggested qty
     * @param float $ros
     * @param int $unitAtAmazon
     */
    public function calculateSuggestedQty(float $ros, int $unitAtAmazon, int $poQty, int $supplierLeadTime = null) : int
    {
        $setting = Setting::first();
        
        if(empty($supplierLeadTime))
        {
            $leadTime = ($setting->supplier_lead_time) ?? 0;
        }else{
            $leadTime = $supplierLeadTime;
        }

        $suggestedQty = (($setting->day_stock_holdings + $leadTime) * $ros) - $unitAtAmazon - $poQty;
        return ($suggestedQty > 0) ? $suggestedQty : 0;
    }

    /**
     * Get Amazon Products
     */
    public function getAmazonProducts() : Collection
    {
        return AmazonProduct::with(['supplierProducts' => function($query){
                                return $query->with(['supplier' => function($supplier){
                                    return $supplier->select('id','lead_time');
                                }])->select('id','product_id','supplier_id');
                            }])
                            ->orderBy('id','asc')
                            ->take($this->productLimit)
                            ->skip($this->productOffset)
                            ->select('id','qty','afn_reserved_quantity','afn_inbound_working_quantity','afn_inbound_shipped_quantity','afn_inbound_receiving_quantity')
                            ->get();
    }

    /**
     * Get total orders from amazon order report with product id and date
     * @param array $productIds
     * @param array $dates
     */
    public function getTotalOrders(array $productIds, array $dates) : array
    {
        $getTotalOrders = AmazonOrderReport::selectRaw('sum(quantity) as total_orders,product_id')
                                    ->whereIn('product_id', $productIds)
                                    ->lastNDates($dates)
                                    ->groupBy('product_id')
                                    ->get()
                                    ->keyBy('product_id')
                                    ->toArray();

        return $getTotalOrders;
    }

    /**
     * sales velocity table column list
     */
    public function columnList() : array
    {
        return [
            'amazon_product_id',
            'ros_2',
            'ros_7',
            'ros_30',
            'total_units_sold_2',
            'total_units_sold_7',
            'total_units_sold_30',
            'suggested_quantity'
        ];
    }

    /**
     * For calculate threshold qty
     * @param float $ros
     */
    public function calculateThresholdQty(float $ros, int $supplierLeadTime = null) : int
    {
        if(empty($supplierLeadTime))
        {
            $setting = Setting::first();
            $leadTime = ($setting->supplier_lead_time) ?? 0;
        }else{
            $leadTime = $supplierLeadTime;
        }

        $thresholdQty = $ros * $leadTime;
        return ($thresholdQty > 0) ? round($thresholdQty) : 0;
    }

    /**
     * Get total orders quantity from purchase order items based on product id and supplier id
     */
    public function getTotalOrderQty(array $productIds) : array
    {
        $allPoStatus = config('constants.po_status');
        $poStatus = collect($allPoStatus)->forget(['Draft','Cancelled','Closed'])->keys()->toArray();
        
        return PurchaseOrderItem::whereHas('purchaseOrder', function($query) use($poStatus){
            return $query->status($poStatus);
        })
        ->whereIn('product_id', $productIds)
        // ->whereIn('supplier_id', $supplierIds)
        // ->sum('order_qty')
        ->select('po_id','product_id',DB::raw('sum(order_qty) as total_order_qty'))
        ->groupBy('product_id')
        ->get()
        ->keyBy('product_id')
        ->toArray();
    }
}