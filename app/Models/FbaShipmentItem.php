<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AmazonProduct;
use Illuminate\Support\Facades\DB;

class FbaShipmentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function amazonData()
    {
        return $this->hasOne(AmazonProduct::class, "sku", "seller_sku");
    }

    public static function getTotalQtyShippedUnits($shipmentId)
    {
        return FbaShipmentItem::select(
            DB::raw("SUM(quantity_shipped) as total_units")
        )
        ->where('shipment_id', $shipmentId)->first();  
    }

    public static function getPrepDetailsInfo($type, $rowId, $params, $pageNumber=null)
    {
        if($type == "multiple")
        {
            $where_data = array(
                'shipment_id' => $rowId
              );
        }else if($type == "single"){
            $where_data = array(
              'fba_shipment_items.id' => $rowId
            );
        }else{
            $where_data = null;
        }

        $query = FbaShipmentItem::select(
            'fba_shipment_items.id',
            'fba_shipment_items.seller_sku',
            'fba_shipment_items.skus_prepped',
            'fba_shipment_items.is_validated',
            'fba_prep_notes.prep_note',
            'fba_prep_notes.asin_weight',
            'fba_prep_notes.warehouse_note as prep_warehouse_notes',
            'fba_prep_details.done_qty',
            'fba_prep_details.discrepancy_note',
            'fba_prep_details.status',
            'quantity_shipped as qty',
            'original_quantity_shipped as orig_qty',
            'suppliers.name as supplier_name',
            'purchase_orders.po_number',
            'purchase_orders.id as poId',
            // 'po_flows.pallet_id',
            // 'product_analyzers.case_pack',
            // 'product_analyzers.a_pack',
            // 'product_analyzers.warehouse_notes',
            // 'product_analyzers.supplier_id',
            // 'po_flows.product_id as proId',
            'fba_shipment_items.is_quantity_updated',
            'fba_shipment_items.original_quantity_shipped',
            // 'product_analyzers.upc as product_analyzer_upc',
            // 'product_analyzers.item_code',
        )
        ->where('quantity_shipped', '!=', 0)
        ->where($where_data)
        ->orderByRaw("FIELD(skus_prepped , '1', '0', '2','3') ASC")
        ->with('amazonData', function ($joinQ) {
            $joinQ
            // ->with('casePackSupplier', function ($supQ) {
            //     $supQ->select('id', 'name');
            // })
            ->select(
                'sku',
                'title',
                'asin',
                'main_image',
                'fnsku',
                'item_weight',
                'upc',
                'pack_of as amazon_product_a_pack',
                'case_pack as amazon_product_case_pack',
                // 'case_pack_supplier_id'
            );
        })
        ->addSelect([
            'done_units' => FbaPrepDetail::select(DB::raw("SUM(done_qty) as done_units"))
                ->whereColumn('fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id'),
        ])
        ->leftJoin('shipment_products', function ($joinQ) {
            $joinQ->on('shipment_products.shipment_plan_id', 'fba_shipment_items.fba_shipment_plan_id');
            $joinQ->on('shipment_products.sku', 'fba_shipment_items.seller_sku');
        })
        ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        // ->leftJoin('po_flows', 'po_flows.id', 'shipment_products.po_flow_id')
        ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipment_items.fba_shipment_plan_id')
        ->leftJoin('purchase_orders', 'purchase_orders.id', 'shipment_plans.po_id')
        ->leftJoin('suppliers', 'suppliers.id', 'purchase_orders.supplier_id')
        // ->leftJoin('product_analyzers', 'product_analyzers.id', 'po_flows.product_id')
        ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
        ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
        ->leftJoin('purchase_order_items', function ($joinQ) {
            $joinQ->on('purchase_order_items.po_id', 'purchase_orders.id');
            $joinQ->on('purchase_order_items.product_id', 'amazon_products.id');
        });
        
        if($type == "multiple")
        {
            if (isset($params['product_info_search']) && !empty($params['product_info_search']))
            {
                $searchString = trim(base64_decode($params['product_info_search']));
                $query->where(function ($q) use ($searchString) {
                    $q->where('amazon_products.upc', 'LIKE', '%' . $searchString . '%');
                    $q->orWhere('amazon_products.asin', 'LIKE', '%' . $searchString . '%');
                    $q->orWhere('amazon_products.title', 'LIKE', '%' . $searchString . '%');
                    $q->orWhere('amazon_products.sku',  'LIKE', '%' . $searchString . '%');
                    $q->orWhere('amazon_products.fnsku',  'LIKE', '%' . $searchString . '%');

                    $q->orWhere('suppliers.name',  'LIKE', '%' . $searchString . '%');
                    // $q->orWhere('po_flows.pallet_id',  'LIKE', '%' . $searchString . '%');
                    // $q->orWhere('purchase_orders.po_number',  'LIKE', '%' . $searchString . '%');
                    // $q->orWhere('product_analyzers.item_code',  'LIKE', '%' . $searchString . '%');
                });
            }
            $dataRos = $query->paginate(20);
            $supplierInfos = self::getSupplierInfoData($type, $rowId);
            $data = self::getSupplierInfomation($dataRos, $supplierInfos);
        }
        
        if($type == "single")
        {
            $data = $query->first()->toArray();
        }

        return $data;
    }

    public static function getSupplierInfoData($type, $rowId)
    {
        if($type == "multiple")
        {
            $where_data = array(
                'shipment_id' => $rowId
              );
        }else if($type == "single"){
            $where_data = array(
              'fba_shipment_items.id' => $rowId
            );
        }else{
            $where_data = null;
        }

        $query = FbaShipmentItem::select(
            'fba_shipment_items.id',
            'fba_shipment_items.seller_sku',
            'fba_shipment_items.is_validated',
            'suppliers.name as supplier_name',
            'purchase_orders.po_number',
            'purchase_orders.id as poId',
            // 'po_flows.pallet_id',
        )
        ->where($where_data)
        // ->leftJoin('shipment_plan_quantities', function ($joinQ) {
        //     $joinQ->on('shipment_plan_quantities.shipment_plan_id', 'fba_shipment_items.fba_shipment_plan_id');
        //     $joinQ->on('shipment_plan_quantities.sku', 'fba_shipment_items.seller_sku');
        // })
        ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        // ->leftJoin('po_flows', 'po_flows.id', 'shipment_plan_quantities.po_flow_id')
        ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipment_items.fba_shipment_plan_id')
        ->leftJoin('purchase_orders', 'purchase_orders.id', 'shipment_plans.po_id')
        ->leftJoin('suppliers', 'suppliers.id', 'purchase_orders.supplier_id')
        ->leftJoin('purchase_order_items', function ($joinQ) {
            $joinQ->on('purchase_order_items.po_id', 'purchase_orders.id');
            // $joinQ->on('purchase_order_items.product_id', 'po_flows.product_id');
        });

        return $query->paginate(20);
    }

    public static function getSupplierInfomation($dataRos, $supplierInfos)
    {
        $resultArr = [];
        if(isset($dataRos) && !empty($dataRos))
        {
            foreach($dataRos as $key1 => $data)
            {
                $dataRos[$key1]['supplierInfos'] = NULL;

                if(isset($supplierInfos) && count($supplierInfos) > 0)
                {
                    foreach($supplierInfos as $key2 => $supplierData){
                        if($supplierData['id']==$data->id){
                            $resultArr[$data->id][] = [
                                'supplier_name' => $supplierData['supplier_name'],
                                'po_number' => $supplierData['po_number'],
                                'pallet_id' => $supplierData['pallet_id'],
                            ];
                        }
                    }
                }
            }
        }

        if(isset($dataRos) && !empty($dataRos))
        {
            foreach($dataRos as $key1 => $data)
            { 
                if(isset($resultArr) && count($resultArr) > 0)
                {
                    foreach($resultArr as $dataId => $result)
                    {
                        if($dataId==$data->id && isset($result[0]['supplier_name']) && !empty($result[0]['supplier_name'])){
                            $data['supplierInfos'] = $result;
                        }
                    }
                }
            }
        }

        return $dataRos;
    }

    public static function fbaPrepAllBoxDetail($shipmentId = null)
    {
        $boxes = FbaPrepBoxDetail::where(['fba_shipment_id' => $shipmentId])
                            ->orderBy('id','DESC')->get()->toArray();

        return $boxes;
    }

    public static function fbaPrepBoxDetail($shipmentId = null, $fba_shipment_item_id = null)
    {
        $boxes = FbaPrepBoxDetail::where(['fba_shipment_id' => $shipmentId, 'fba_shipment_item_id' => $fba_shipment_item_id])
                            ->orderBy('id','DESC')
                            ->get()->toArray();

        return $boxes;
    }

    public static function getAllPrepDetailsInfo($itemId){
        $query = FbaShipmentItem::select(
            'fba_shipment_items.id',
            'fba_shipment_items.seller_sku',
            'fba_shipment_items.is_validated',
            'fba_prep_notes.prep_note',
            'fba_prep_notes.asin_weight',
            'fba_prep_notes.warehouse_note as prep_warehouse_notes',
            'fba_prep_details.done_qty',
            'fba_prep_details.discrepancy_note',
            'fba_prep_details.status',
            'quantity_shipped as qty',
            'original_quantity_shipped as orig_qty',
            'suppliers.name as supplier_name',
            'purchase_orders.po_number',
            // 'po_flows.pallet_id',
            // 'product_analyzers.case_pack',
            // 'product_analyzers.warehouse_notes',
            // 'product_analyzers.item_code',
            'fba_shipment_item_prep_details.prep_instruction'
        )
        ->where('fba_shipment_items.id', $itemId)
        ->with('amazonData', function ($joinQ) {
            $joinQ->select(
                'sku',
                'title',
                'asin',
                'main_image',
                'fnsku',
                'item_weight',
                'upc',
                'pack_of'
            );
        })
        ->addSelect([
            'done_units' => FbaPrepDetail::select(DB::raw("SUM(done_qty) as done_units"))
                ->whereColumn('fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id'),
        ])   
        ->leftJoin('shipment_products', function ($joinQ) {
            $joinQ->on('shipment_products.shipment_plan_id', 'fba_shipment_items.fba_shipment_plan_id');
            $joinQ->on('shipment_products.sku', 'fba_shipment_items.seller_sku');
        })
        ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        // ->leftJoin('po_flows', 'po_flows.id', 'shipment_products.po_flow_id')
        ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipment_items.fba_shipment_plan_id')
        ->leftJoin('purchase_orders', 'purchase_orders.id', 'shipment_plans.po_id')
        ->leftJoin('suppliers', 'suppliers.id', 'purchase_orders.supplier_id')
        // ->leftJoin('product_analyzers', 'product_analyzers.id', 'po_flows.product_id')
        ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
        ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
        ->leftJoin('fba_shipment_item_prep_details', 'fba_shipment_item_prep_details.fba_shipment_item_id', 'fba_shipment_items.id', 'AND', 'fba_shipment_item_prep_details.fba_shipment_id', 'fba_shipments.id')
        ->leftJoin('purchase_order_items', function ($joinQ) {
            $joinQ->on('purchase_order_items.po_id', 'purchase_orders.id');
            // $joinQ->on('purchase_order_items.product_id', 'po_flows.product_id');
        });

       return $query->get()->toArray();
    }

    public function fbaPrepDetail()
    {
        return $this->hasOne(FbaPrepDetail::class, 'fba_shipment_item_id', 'id');
    }

    public static function getCompleteShipmentPrepData($shipmentId)
    {
        $query = FbaShipmentItem::select(
            'fba_shipment_items.id',
            'fba_shipment_items.fba_shipment_id',
            'fba_shipment_items.seller_sku',
            'fba_shipment_items.skus_prepped',
            'fba_shipment_items.is_validated',
            'fba_prep_details.done_qty',
            'fba_prep_details.discrepancy_note',
            'fba_prep_details.status',
            'fba_shipment_items.quantity_shipped',
            'fba_shipment_items.is_quantity_updated',
            'fba_shipment_items.original_quantity_shipped',
            'fba_prep_details.discrepancy_qty',
            'amazon_products.title',
            'amazon_products.asin',
            'amazon_products.fnsku',
            'amazon_products.sku',
            DB::raw("(fba_shipment_items.quantity_shipped - IFNULL(fba_prep_details.done_qty, 0)) as all_discrepancy_qty")
        )
        ->where('fba_shipment_items.fba_shipment_id', $shipmentId)
        ->addSelect([
            'done_units' => FbaPrepDetail::select(DB::raw("SUM(done_qty) as done_units"))
                ->whereColumn('fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id'),
        ])
        ->leftJoin('shipment_products', function ($joinQ) {
            $joinQ->on('shipment_products.shipment_plan_id', 'fba_shipment_items.fba_shipment_plan_id');
            $joinQ->on('shipment_products.sku', 'fba_shipment_items.seller_sku');
        })
        ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
        ->whereRaw('IFNULL(fba_prep_details.done_qty, 0) != fba_shipment_items.quantity_shipped');

        $data = $query->get();

        return $data;
    }

    public static function getNoDiscrepancyPrepData($shipmentId)
    {
        $query = FbaShipmentItem::select(
                        'fba_shipment_items.id',
                        DB::raw("(fba_shipment_items.quantity_shipped - IFNULL(fba_prep_details.done_qty, 0)) as all_discrepancy_qty")
                    )
                    ->where('fba_shipment_items.fba_shipment_id', $shipmentId)
                    ->addSelect([
                        'done_units' => FbaPrepDetail::select(DB::raw("SUM(done_qty) as done_units"))
                            ->whereColumn('fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id'),
                    ])
                    ->leftJoin('shipment_products', function ($joinQ) {
                        $joinQ->on('shipment_products.shipment_plan_id', 'fba_shipment_items.fba_shipment_plan_id');
                        $joinQ->on('shipment_products.sku', 'fba_shipment_items.seller_sku');
                    })
                    ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
                    ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
                    ->whereRaw('IFNULL(fba_prep_details.done_qty, 0) = fba_shipment_items.quantity_shipped');

        $data = $query->count();

        return $data;
    }
}
