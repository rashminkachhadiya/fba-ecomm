<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AmazonProduct;
use Illuminate\Support\Facades\DB;

class ShipmentProduct extends Model
{
    use HasFactory;

    public $guarded = [];

    public function product()
    {
        return $this->belongsTo(AmazonProduct::class,'amazon_product_id','id');
    }

    public static function manageSkuQtyInMoveAndDeleteShipment($skuAndQtyArr, $planId, $shipmentId, $attachedToPlanId)
    {
        // Check already attached sku with the selected plan
        // Get all Plan's shipment sku arr
        $allPlanSkuArr = FbaShipmentItem::whereIn('seller_sku', array_keys($skuAndQtyArr))
            ->where('shipment_id', '!=', $shipmentId)
            ->pluck('seller_sku')
            ->toArray();

        $duplicateSkuInBothPlanArr = array_intersect($allPlanSkuArr, array_keys($skuAndQtyArr));
        // First delete all specific skus which are not in common and can be deleted driectly
        // shipment_plan_quantities: Delete rows by shipment_plan_id and sku
        // ShipmentPlanQuantity::where('shipment_plan_id', $planId)
        //     ->whereIn('sku', array_keys($skuAndQtyArr))
        //     ->whereNotIn('sku', $duplicateSkuInBothPlanArr)
        //     ->update([
        //         'shipment_plan_id' => $attachedToPlanId,
        //         'shipment_product_id' => null
        //     ]);

        $skuInAttachToPlanArr = ShipmentProduct::where('shipment_plan_id', $attachedToPlanId)->pluck('sku')->toArray();

        $doNotIncludeSkuArr = array_merge($skuInAttachToPlanArr, $duplicateSkuInBothPlanArr);

        // shipment_products: Delete rows by shipment_plan_id and sku
        ShipmentProduct::where('shipment_plan_id', $planId)
            ->whereIn('sku', array_keys($skuAndQtyArr))
            ->whereNotIn('sku', $doNotIncludeSkuArr)
            ->update(['shipment_plan_id' => $attachedToPlanId]);

        // Custom logic to handle duplicate sku quantity
        if (!empty($duplicateSkuInBothPlanArr))
        {
            self::handleDuplicateSkuQtyInMoveShipment($duplicateSkuInBothPlanArr, $shipmentId, $planId, $attachedToPlanId);
        }

        // shipment_plan_quantities: Take po_flow_id array by shipment_plan_id and sku
        // Get all specific shipment skus (And their Po Flow ID)
        // $shipmentPoFlowIdArr = ShipmentPlanQuantity::whereIn('shipment_plan_id', [$planId, $attachedToPlanId])
        //     ->whereIn('sku', array_keys($skuAndQtyArr))
        //     ->pluck('po_flow_id')
        //     ->toArray();

        // Recalculate shipped quantity
        // ShipmentPlan::recalculateShippedQuantity($shipmentPoFlowIdArr);

        // Reassign shipment product id
        // ShipmentProduct::assignShipmentProductId($shipmentPoFlowIdArr, $attachedToPlanId);
    }

    public static function handleDuplicateSkuQtyInMoveShipment($duplicateSkuInBothPlanArr, $shipmentId, $planId, $attachedToPlanId)
    {
        if (!empty($duplicateSkuInBothPlanArr))
        {
            $dupskuAndQtyArr = FbaShipmentItem::where('shipment_id', $shipmentId)
                                    ->whereIn('seller_sku', $duplicateSkuInBothPlanArr)
                                    ->pluck('fba_shipment_items.quantity', 'fba_shipment_items.seller_sku')
                                    ->toArray();

                //                     dd(DB::table('fba_shipment_items')->where('fba_shipment_items.shipment_id', $shipmentId)
                // ->whereIn('fba_shipment_items.seller_sku', $duplicateSkuInBothPlanArr)
                // ->select('fba_shipment_items.quantity', 'fba_shipment_items.seller_sku')
                // ->get()
                // ->toArray());
                
                // dd(FbaShipmentItem::whereIn('fba_shipment_items.seller_sku', $duplicateSkuInBothPlanArr)->get(['shipment_id']));
                                    
                // dd(FbaShipmentItem::where('fba_shipment_items.shipment_id', $shipmentId)
                // ->whereIn('fba_shipment_items.seller_sku', $duplicateSkuInBothPlanArr)
                // ->select('fba_shipment_items.quantity', 'fba_shipment_items.seller_sku')
                // ->get()
                // ->toArray());

            // $dataQtyArr = []
            $data = [];

            if (!empty($dupskuAndQtyArr))
            {
                foreach ($dupskuAndQtyArr as $sku => $qty)
                {
                    // $skuFirstRow = ShipmentPlanQuantity::where('sku', $sku)
                    //     ->where('shipment_plan_id', $planId)
                    //     ->orderBy('sellable_unit', 'DESC')
                    //     ->first();

                    // $dataQtyArr[] = [
                    //     'shipment_plan_id' => $attachedToPlanId,
                    //     'sku' => $sku,
                    //     'po_flow_id' => !empty($skuFirstRow) ? $skuFirstRow->po_flow_id : null,
                    //     'sellable_unit' => $qty,
                    //     'po_id' => !empty($skuFirstRow) ?  $skuFirstRow->po_id : null,
                    //     'is_added_from_amazon' => 0
                    // ];

                    // Insert row in shipment product if sku and amazon id not exist
                    $isSkuExist = ShipmentProduct::where('shipment_plan_id', $attachedToPlanId)
                        ->where('sku', $sku)
                        ->first();

                    if (empty($isSkuExist))
                    {
                        $amazonRowInfo = AmazonProduct::select('title', 'asin', 'sku', 'id')
                            ->where('sku', $sku)
                            ->orderBy('id', 'DESC')
                            ->first()
                            ->toArray();

                        if (!empty($amazonRowInfo))
                        {
                            $data[] = [
                                'shipment_plan_id' => $attachedToPlanId,
                                'amazon_product_id' => $amazonRowInfo['id'],
                                // 'po_flow_id' =>  !empty($skuFirstRow) ? $skuFirstRow->po_flow_id : null,
                                'sellable_unit' => $qty,
                                'created_by' => auth()->user()->id,
                                'title' => !empty($amazonRowInfo) ? $amazonRowInfo['title'] : null,
                                'asin' => !empty($amazonRowInfo) ? $amazonRowInfo['asin'] : null,
                                'sku' => !empty($amazonRowInfo) ? $amazonRowInfo['sku'] : null
                            ];
                        }
                    }

                    // $skuRows = ShipmentPlanQuantity::where('sku', $sku)
                    //     ->where('shipment_plan_id', $planId)
                    //     ->orderBy('sellable_unit', 'DESC')
                    //     ->get();


                    // foreach ($skuRows as $rowObj) {

                    //     // qty = 15 , sellable_qty = 8, remainig = 15-8 = 7
                    //     // qty = 8, sellable_qty = 15, 15-8 = remaining = 0

                    //     if ($rowObj->sellable_unit >= $qty) {
                    //         $rowObj->update(['sellable_unit' => bcsub($rowObj->sellable_unit, $qty)]);

                    //         break;
                    //     } else {
                    //         $rowObj->update(['sellable_unit' => 0]);
                    //         $qty =  bcsub($qty, $rowObj->sellable_unit);
                    //     }
                    // }
                }
            }

            // ShipmentPlanQuantity::insert($dataQtyArr);

            ShipmentProduct::insert($data);

            // For updating  sellable asin qty in Shipment Product table bases on the entried on shipment quantity
            // ShipmentProduct::updateSellableAsinQty($planId);
            // ShipmentProduct::updateSellableAsinQty($attachedToPlanId);
        }

        // Delete rows where these is no qty added in front of po flow row
        // ShipmentPlanQuantity::where('sellable_unit', 0)
        //     ->where('shipment_plan_id', $planId)
        //     ->delete();

        ShipmentProduct::where('sellable_unit', 0)
            ->where('shipment_plan_id', $planId)
            ->delete();
    }

    public static function updateSellableAsinQty($planId)
    {
        // $updateArr = ShipmentPlanQuantity::select(
        //     'shipment_plan_id',
        //     'shipment_plan_quantities.sku',
        //     'amazon_products.id as amazon_product_id',
        //     DB::raw("SUM(sellable_unit) as sellable_unit")
        // )
        //     ->where('shipment_plan_id', $planId)
        //     ->join('amazon_products', 'amazon_products.sku', 'shipment_plan_quantities.sku')
        //     ->groupBy(['shipment_plan_quantities.sku', 'shipment_plan_quantities.shipment_plan_id'])
        //     ->get()
        //     ->toArray();

        // DB::table('shipment_products')->upsert(
        //     $updateArr,
        //     ["shipment_plan_id", "amazon_product_id"],
        //     [
        //         'sellable_unit',
        //     ]
        // );

        // $updatePoFlowQtyArr = ShipmentPlanQuantity::select(
        //     'shipment_plan_id',
        //     'shipment_plan_quantities.sku',
        //     'amazon_products.id as amazon_product_id',
        //     'po_flow_id',
        //     DB::raw('MAX(sellable_unit) as sellable_unit')
        // )
        //     ->join('amazon_products', 'amazon_products.sku', 'shipment_plan_quantities.sku')
        //     ->whereNotNull('po_flow_id')
        //     ->where('shipment_plan_id', $planId)
        //     ->groupBy('shipment_plan_quantities.shipment_product_id')
        //     ->get()
        //     ->toArray();

        // DB::table('shipment_products')->upsert(
        //     $updatePoFlowQtyArr,
        //     ["shipment_plan_id", "amazon_product_id"],
        //     [
        //         'po_flow_id',
        //     ]
        // );
    }

    public function shipmentPlan()
    {
        return $this->belongsTo(ShipmentPlan::class, 'shipment_plan_id');
    }
}
