<?php

namespace App\Http\Controllers;

use App\DataTables\FBAPlansDatatable;
use App\Http\Requests\StoreShipmentPlanRequest;
use App\Http\Requests\UpdateShipmentPlanRequest;
use App\Models\AmazonProduct;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\ShipmentPlan;
use App\Models\ShipmentPlanError;
use App\Models\ShipmentProduct;
use App\Models\Store;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Services\ShipmentPlanService;
use Batch;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class ShipmentPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FBAPlansDatatable $dataTable)
    {
        $statusAttr = config('constants.plan_status');
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('shipment_plans.list', compact(['listingCols', 'statusAttr']));
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create($po_id)
    // {
    //     $stores = Store::active()->pluck('store_name', 'id');
    //     $storeId = (!empty($stores)) ? $stores->keys()->first() : 0;
    //     $wareHouses = Warehouse::latest()->get();
    //     $prepPreferences = config('constants.prep_preference');
    //     $marketplaces = [
    //         "CA" => 'CA',
    //     ];
    //     $packingDetails = config('constants.packing_details');
    //     $boxContents = config('constants.box_content');
        
    //     $poProducts = PurchaseOrderItem::where('po_id', $po_id)
    //         ->whereHas('product', function ($query) use ($storeId) {
    //             $query->where('store_id', $storeId)->select('id');
    //         })
    //         ->with(['product' => function ($query) use ($storeId) {
    //             return $query->with(['store' => function ($storeQuery) {
    //                 return $storeQuery->select('id', 'store_name');
    //             }, 'salesVelocity' => function ($salesVelocityQuery) {
    //                 return $salesVelocityQuery->select('id', 'amazon_product_id', 'ros_30');
    //             }])
    //                 ->where('store_id', $storeId)
    //                 ->select(
    //                     'id',
    //                     'title',
    //                     'asin',
    //                     'sku',
    //                     'qty',
    //                     'afn_reserved_quantity',
    //                     'store_id',
    //                     'afn_unsellable_quantity',
    //                     'afn_inbound_working_quantity',
    //                     'afn_inbound_shipped_quantity',
    //                     'afn_inbound_receiving_quantity',
    //                     'main_image',
    //                     'pack_of',
    //                     'case_pack',
    //                     'wh_qty',
    //                     'reserved_qty',
    //                     'is_hazmat',
    //                     'is_oversize',
    //                     'product_note',
    //                     'sellable_units'
    //                 );
    //         }])
    //         ->select('product_id', 'po_id', 'received_qty')
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     $commonService = new CommonService();
    //     $setting = Setting::first();
    //     $poProducts = $poProducts->map(function ($item) use ($commonService, $setting) {
    //         $item->total_fba_qty = $commonService->totalFBAQty([$item->product->qty, $item->product->afn_inbound_working_quantity, $item->product->afn_inbound_shipped_quantity, $item->product->afn_inbound_receiving_quantity, $item->product->afn_reserved_quantity]);
    //         $item->suggested_shipment_qty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $item->product->salesVelocity->ros_30, $item->total_fba_qty]);
    //         return $item;
    //     });

    //     return view('shipment_plans.create', compact(['wareHouses', 'stores', 'prepPreferences', 'marketplaces', 'boxContents', 'packingDetails', 'po_id', 'poProducts', 'setting']));
    // }

    public function create($po_id)
    {
        // Session::forget("po_$po_id");
        // Session::save();
        // dd(session("po_$po_id"));
        $stores = Store::active()->pluck('store_name', 'id');
        $storeId = (!empty($stores)) ? $stores->keys()->first() : 0;
        $wareHouses = Warehouse::latest()->get();
        $prepPreferences = config('constants.prep_preference');
        $marketplaces = [
            "CA" => 'CA',
        ];
        $packingDetails = config('constants.packing_details');
        $boxContents = config('constants.box_content');
        $isStored = false;
        $amazonProducts = [];

        $poProductsQuery = PurchaseOrderItem::where('po_id', $po_id)
            ->whereHas('product', function ($query) use ($storeId) {
                $query->where('store_id', $storeId)->where('if_fulfilled_by_amazon', 1)->select('id');
            })
            ->with(['product' => function ($query) use ($storeId) {
                return $query->with(['store' => function ($storeQuery) {
                    return $storeQuery->select('id', 'store_name');
                }, 'salesVelocity' => function ($salesVelocityQuery) {
                    return $salesVelocityQuery->select('id', 'amazon_product_id', 'ros_30');
                }])
                    ->where('store_id', $storeId)
                    ->select(
                        'id',
                        'title',
                        'asin',
                        'sku',
                        'qty',
                        'afn_reserved_quantity',
                        'store_id',
                        'afn_unsellable_quantity',
                        'afn_inbound_working_quantity',
                        'afn_inbound_shipped_quantity',
                        'afn_inbound_receiving_quantity',
                        'main_image',
                        'pack_of',
                        'case_pack',
                        'wh_qty',
                        'reserved_qty',
                        'is_hazmat',
                        'is_oversize',
                        'product_note',
                        'sellable_units'
                    );
            }]);

            if(app('session')->has("po_$po_id"))
            {
                $poProductsQuery->whereIn('product_id', [...(session("po_$po_id"))]);
                $isStored = true;
            }

        $poProducts = $poProductsQuery->select('product_id', 'po_id', 'received_qty')
            ->orderBy('id', 'desc')
            ->get();

        $commonService = new CommonService();
        $setting = Setting::first();
        $poProducts = $poProducts->map(function ($item) use ($commonService, $setting) {
            $item->total_fba_qty = $commonService->totalFBAQty([$item->product->qty, $item->product->afn_inbound_working_quantity, $item->product->afn_inbound_shipped_quantity, $item->product->afn_inbound_receiving_quantity, $item->product->afn_reserved_quantity]);
            $item->suggested_shipment_qty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $item->product->salesVelocity->ros_30, $item->total_fba_qty]);
            return $item;
        });

        $productIds = $poProducts->map(function ($item){
            return $item->product_id;
        })->toArray();
        
        if(!$isStored)
        {
            session(["po_$po_id" => [...$productIds]]);
            // Log::info("after listing session = ",session("po_$po_id"));
        }

        if(app('session')->has("po_$po_id"))
        {
            // dd(session("po_$po_id"));
            $remainingProductIds = array_diff(session("po_$po_id"), $productIds);

            if(!empty($remainingProductIds))
            {
                $amazonProducts = AmazonProduct::whereIn('id', $remainingProductIds)
                                    ->with(['store' => function ($storeQuery) {
                                            return $storeQuery->select('id', 'store_name');
                                        }, 'salesVelocity' => function ($salesVelocityQuery) {
                                            return $salesVelocityQuery->select('id', 'amazon_product_id', 'ros_30');
                                    }])
                                    ->orderBy('id','desc')
                                    ->get([
                                        'id', 'title', 'asin', 'sku', 'qty', 'afn_reserved_quantity', 'store_id', 'afn_unsellable_quantity',
                                        'afn_inbound_working_quantity', 'afn_inbound_shipped_quantity', 'afn_inbound_receiving_quantity', 'main_image',
                                        'pack_of', 'case_pack', 'wh_qty', 'reserved_qty', 'is_hazmat', 'is_oversize', 'product_note', 'sellable_units'
                                    ]);

                if($amazonProducts->count() > 0)
                {
                    $amazonProducts = $amazonProducts->map(function ($item) use ($commonService, $setting) {
                        $item->total_fba_qty = $commonService->totalFBAQty([$item->qty, $item->afn_inbound_working_quantity, $item->afn_inbound_shipped_quantity, $item->afn_inbound_receiving_quantity, $item->afn_reserved_quantity]);
                        $item->suggested_shipment_qty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $item->salesVelocity->ros_30, $item->total_fba_qty]);
                        return $item;
                    });
                }
            }
        }

        return view('shipment_plans.create', compact(['wareHouses', 'stores', 'prepPreferences', 'marketplaces', 'boxContents', 'packingDetails', 'po_id', 'poProducts', 'setting', 'amazonProducts']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShipmentPlanRequest $request, CommonService $commonService)
    {
        DB::beginTransaction();
        try {
            $shipmentPlan = $request->safe()->except(['sellable_unit']);
            $storeShipmentPlan = ShipmentPlan::create($shipmentPlan);
            if ($storeShipmentPlan)
            {
                if(!empty($request->po_id) && app('session')->has("po_$request->po_id"))
                {
                    // app('session')->forget("po_$request->po_id");
                    Session::forget("po_$request->po_id");
                    Session::save();
                }
                $shipmentProducts = [];
                $productNotes = [];
                $qtyLogData = [];
                if ($request->sellable_unit)
                {
                    foreach ($request->sellable_unit as $key => $value)
                    {
                        array_push($shipmentProducts, [
                            'amazon_product_id' => $key,
                            'title' => (isset($request->title[$key])) ? $request->title[$key] : '',
                            'asin' => (isset($request->asin[$key])) ? $request->asin[$key] : '',
                            'sku' => (isset($request->sku[$key])) ? $request->sku[$key] : '',
                            'sellable_unit' => $value,
                        ]);

                        $qty = $value * $request->pack_of[$key];
                        $updatedQty = $request->wh_qty[$key] - $qty;
                        if (isset($request->product_note[$key])) {
                            array_push($productNotes, [
                                'id' => $key,
                                'wh_qty' => $updatedQty,
                                'reserved_qty' => $qty,
                                'sellable_units' => $request->current_sellable_units[$key] - $value,
                                'product_note' => $request->product_note[$key],
                            ]);
                        }else{
                            array_push($productNotes, [
                                'id' => $key,
                                'wh_qty' => $updatedQty,
                                'reserved_qty' => $qty,
                                'sellable_units' => $request->current_sellable_units[$key] - $value,
                            ]);
                        }

                        array_push($qtyLogData, [
                            'amazon_product_id' => $key,
                            'previous_qty' => $request->wh_qty[$key],
                            'updated_qty' => $updatedQty,
                            'comment' => 'Create shipment plan ('.$storeShipmentPlan->plan_name.')',
                            'updated_by' => auth()->user()->id,
                            'created_at' => now()
                        ]);
                    }
                    $storeShipmentPlan->shipmentProducts()->createMany($shipmentProducts);

                    if (!empty($productNotes))
                    {
                        Batch::update(new AmazonProduct, $productNotes, 'id');
                    }

                    $commonService->storeQtyLog($qtyLogData, '', true);
                    DB::commit();
                }
                return $this->sendResponse('Draft shipment plan created successfully', 200);
            }

            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.fba_plan'));
        if (isset($response['status']) && $response['status'] == true) {

            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ShipmentPlan $shipmentPlan)
    {
        $stores = Store::active()->pluck('store_name', 'id');
        $wareHouses = Warehouse::latest()->get();
        $prepPreferences = config('constants.prep_preference');
        $marketplaces = [
            "CA" => 'CA',
        ];
        $packingDetails = config('constants.packing_details');
        $boxContents = config('constants.box_content');

        $shipmentPlanDetails = $this->getProductDetails($shipmentPlan);

        return view('shipment_plans.view', compact(['wareHouses', 'stores', 'prepPreferences', 'marketplaces', 'boxContents', 'packingDetails', 'shipmentPlanDetails', 'shipmentPlan']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShipmentPlan $shipmentPlan)
    {
        $stores = Store::active()->pluck('store_name', 'id');
        $wareHouses = Warehouse::latest()->get();
        $prepPreferences = config('constants.prep_preference');
        $marketplaces = [
            "CA" => 'CA',
        ];
        $packingDetails = config('constants.packing_details');
        $boxContents = config('constants.box_content');

        $shipmentPlanDetails = $this->getProductDetails($shipmentPlan);

        $totalCount = $shipmentPlanDetails->count();

        $commonService = new CommonService();
        $setting = Setting::first();

        $shipmentPlanDetails = $shipmentPlanDetails->map(function ($item) use ($commonService, $setting) {
            $item->total_fba_qty = $commonService->totalFBAQty([$item->product->qty, $item->product->afn_inbound_working_quantity, $item->product->afn_inbound_shipped_quantity, $item->product->afn_inbound_receiving_quantity, $item->product->afn_reserved_quantity]);
            $item->suggested_shipment_qty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $item->product->salesVelocity->ros_30, $item->total_fba_qty]);
            return $item;
        });

        return view('shipment_plans.edit', compact(['wareHouses', 'stores', 'prepPreferences', 'marketplaces', 'boxContents', 'packingDetails', 'shipmentPlanDetails', 'shipmentPlan', 'totalCount', 'setting']));
    }

    public function getProductDetails($shipmentPlan)
    {

        if (empty($shipmentPlan->po_id)) {
            $shipmentPlanDetails = ShipmentProduct::where('shipment_plan_id', $shipmentPlan->id)
                ->with('product')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $shipmentPlanDetails = ShipmentProduct::where('sp.shipment_plan_id', $shipmentPlan->id)
                ->with('product')
                ->leftJoin('shipment_products as sp', 'sp.amazon_product_id', '=', 'shipment_products.amazon_product_id')
                ->leftJoin('purchase_order_items as poi', 'poi.product_id', '=', 'shipment_products.amazon_product_id')
                ->select('sp.*', 'poi.received_qty', 'poi.po_id', 'poi.unit_price')
                // ->where('poi.po_id', $shipmentPlan->po_id)
                ->groupBy('sp.amazon_product_id')
                ->orderBy('sp.created_at', 'desc')
                ->get();
        }
        return $shipmentPlanDetails;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShipmentPlanRequest $request, CommonService $commonService)
    {
        $planId = $request->id;
        DB::beginTransaction();
        try {
            ShipmentPlan::where('id', $planId)->update([
                'plan_name' => $request->plan_name,
                'updated_at' => Carbon::now(),
            ]);

            $shipmentProducts = ShipmentProduct::where('shipment_plan_id', $planId)->pluck('sellable_unit','amazon_product_id')->toArray();

            if(count($shipmentProducts) == 0)
            {
                return $this->sendError('Shipment products are not available', 500);
            }

            $productNotes = [];
            $updatePlanProduct = [];
            $qtyLogData = [];

            if ($request->sellable_unit)
            {
                foreach ($request->sellable_unit as $key => $value)
                {
                    array_push($updatePlanProduct, [
                        'id' => $request->shipment_product_id[$key],
                        'sellable_unit' => $value,
                        'updated_at' => Carbon::now(),
                    ]);

                    // if (isset($request->product_note[$key]))
                    // {
                    //     array_push($productNotes, [
                    //         'id' => $key,
                    //         'product_note' => $request->product_note[$key],
                    //     ]);
                    // }

                    // Difference of old sellable unit and new sellable unit
                    $oldSellableUnit = isset($shipmentProducts[$key]) ? $shipmentProducts[$key] : 0;
                    $sellableUnitDiff = $value - $oldSellableUnit;
                    $qty = $sellableUnitDiff * $request->pack_of[$key];
                    $updatedQty = $request->wh_qty[$key] - $qty;
                    $reservedQty = $request->current_reserved_qty[$key] + $qty;
                    if (isset($request->product_note[$key]))
                    {
                        array_push($productNotes, [
                            'id' => $key,
                            'wh_qty' => $updatedQty,
                            'reserved_qty' => $reservedQty,
                            // 'sellable_units' => $request->current_sellable_units[$key] - $value,
                            'sellable_units' => floor($updatedQty / $request->pack_of[$key]),
                            'product_note' => $request->product_note[$key],
                        ]);
                    }else{
                        array_push($productNotes, [
                            'id' => $key,
                            'wh_qty' => $updatedQty,
                            'reserved_qty' => $reservedQty,
                            'sellable_units' => floor($updatedQty / $request->pack_of[$key]),
                        ]);
                    }

                    array_push($qtyLogData, [
                        'amazon_product_id' => $key,
                        'previous_qty' => $request->wh_qty[$key],
                        'updated_qty' => $updatedQty,
                        'comment' => 'Edit shipment plan ('.$request->plan_name.')',
                        'updated_by' => auth()->user()->id,
                        'created_at' => now()
                    ]);
                }

                // dd($productNotes);

                if (!empty($updatePlanProduct))
                {
                    Batch::update(new ShipmentProduct, $updatePlanProduct, 'id');
                }
                if (!empty($productNotes))
                {
                    Batch::update(new AmazonProduct, $productNotes, 'id');
                }

                $commonService->storeQtyLog($qtyLogData, '', true);
                DB::commit();
            }
            return $this->sendResponse('Draft shipment Plan Updated successfully', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShipmentPlan $shipmentPlan)
    {
        try {
            $shipmentPlan->delete();

            if ($shipmentPlan) {
                return $this->sendResponse('Draft shipment plan deleted successfully', 200);
            }

            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function deletePlanProduct(string $id, $po_id = '')
    {
        // dd("hello");
        $amazonProduct = null;
        $currentSellableUnit = 0;
        $comment = '';
        DB::beginTransaction();
        try {
            if(empty($po_id))
            {
                // Here, $id is shipment products table primary key
                $shipmentProduct = ShipmentProduct::where('id', $id)->first();
                $currentSellableUnit = $shipmentProduct->sellable_unit;
                if(empty($shipmentProduct))
                {
                    return $this->sendError('Shipment products not found', 500);
                }

                $amazonProduct = AmazonProduct::where('id', $shipmentProduct->amazon_product_id)->first();
                $comment = 'Delete product from edit shipment plan ('.$shipmentProduct->shipmentPlan->plan_name.')';

                $shipmentPlanId = $shipmentProduct->shipment_plan_id;

                if ($shipmentProduct->delete())
                {
                    ShipmentPlan::where('id', $shipmentPlanId)->update([
                        'status' => 0,
                        'remark' => ''
                    ]);
                }
            }else{
                // Here, $id is amazon products table primary key
                $amazonProduct = AmazonProduct::where('id', $id)->first();
                $comment = 'Delete product from create shipment plan';

                if(app('session')->has("po_$po_id"))
                {
                    $productIds = session("po_$po_id");
                    if (($key = array_search($id, $productIds)) !== false)
                    {
                        unset($productIds[$key]);
                    }

                    session(["po_$po_id" => [...$productIds]]);
                }
            }

            if(empty($amazonProduct))
            {
                return $this->sendError('Products not found', 500);
            }

            $updatedQty = $currentSellableUnit * $amazonProduct->pack_of;
            $productDetail = [
                'id' => $amazonProduct->id,
                'previous_qty' => $amazonProduct->wh_qty,
                'updated_qty' => $amazonProduct->wh_qty + $updatedQty,
                'pack_of' => $amazonProduct->pack_of,
            ];

            (new CommonService())->storeQtyLog($productDetail, $comment);
            DB::commit();

            return $this->sendResponse('Product deleted successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function submit(ShipmentPlan $shipmentPlan)
    {
        try {
            if ($shipmentPlan && $shipmentPlan->id) {
                $shipmentPlanService = new ShipmentPlanService();
                $shipmentPlanService->shipmentId = $shipmentPlan->id; // set shipment id to service
                // For request amazon sp api to create a shipment plan
                $submitPlanResult = $shipmentPlanService->createShipmentPlan($shipmentPlan->store_id);

                $response = $shipmentPlanService->shipmentPlanFinalize($submitPlanResult);

                if ($response && $response['type'] == 'success') {
                    return $this->sendResponse('Shipment plan submitted successfully', 200, $response);
                }
                return $this->sendError($response['message'], 500);
            }

            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function getShipmentPlanError(ShipmentPlan $shipmentPlan)
    {
        $fbaShipmentErrors = ShipmentPlanError::where('fba_shipment_id', $shipmentPlan->id)->get();

        $code = '';
        $reason = '';
        $products = [];

        if (count($fbaShipmentErrors) > 0) {
            $code = $fbaShipmentErrors[0]['error_code'];

            foreach ($fbaShipmentErrors as $fbaShipmentError) {
                $errorMessage = explode('Reason:', $fbaShipmentError->error_description);
                $message = isset($errorMessage[0]) ? $errorMessage[0] : '';
                $reason = isset($errorMessage[1]) ? $errorMessage[1] : '';

                if (!empty($fbaShipmentError->reason)) {
                    $shipmentProduct = ShipmentProduct::where('shipment_plan_id', $shipmentPlan->id)->where("sku", $fbaShipmentError->sku)->first();

                    if (!empty($shipmentProduct)) {
                        $products[] = [
                            'id' => $shipmentProduct->id,
                            'asin' => $shipmentProduct->asin,
                            'sku' => $shipmentProduct->sku,
                            'title' => $shipmentProduct->title,
                            'amazon_product_id' => $shipmentProduct->amazon_product_id,
                            'sellable_asin_qty' => $shipmentProduct->sellable_asin_qty,
                            'reason' => $fbaShipmentError->reason,
                        ];
                    }
                } else {
                    $message = isset($fbaShipmentErrors[0]->error_description) && !empty($fbaShipmentErrors[0]->error_description) ? $fbaShipmentErrors[0]->error_description : '';
                }
            }
        }

        return response()->json([
            'type' => 'success',
            'status' => 200,
            'message' => $message,
            'code' => $code,
            'reason' => $reason,
            'products' => $products,
        ]);
    }

    public function addFbaProducts(Request $request)
    {
        $planId = $request->plan_id;
        $storeId = $request->storeId;
        $search = '';

        $exsitingProducts = ShipmentProduct::where('shipment_plan_id', $planId)->pluck('amazon_product_id')->toArray();
        if ($planId) {
            try {
                $this->parentTable = (new AmazonProduct())->getTable();
                $this->childTable = (new ShipmentProduct())->getTable();

                $products = AmazonProduct::leftJoin("$this->childTable", "$this->childTable.amazon_product_id", "=", "$this->parentTable.id")
                    ->select(
                        "$this->parentTable.id",
                        "$this->parentTable.qty",
                        "$this->parentTable.afn_reserved_quantity",
                        "$this->parentTable.afn_unsellable_quantity",
                        "$this->parentTable.afn_inbound_working_quantity",
                        "$this->parentTable.afn_inbound_shipped_quantity",
                        "$this->parentTable.afn_inbound_receiving_quantity",
                        "$this->parentTable.asin",
                        "$this->parentTable.pack_of",
                        "$this->parentTable.inbound_shipping_cost",
                        "$this->parentTable.sku",
                        "$this->parentTable.title",
                        "$this->parentTable.main_image",
                        "$this->parentTable.if_fulfilled_by_amazon",
                        "$this->parentTable.sellable_units",
                        "$this->parentTable.wh_qty",
                        "$this->parentTable.fnsku"
                    );

                if ($request->has('search') && $request->search["value"] != '') {
                    $search = $request->search["value"];
                } elseif (isset($request->product_search_data) && !empty($request->product_search_data)) {
                    $search = $request->product_search_data;
                }

                if (!empty($search)) {
                    $products->where(function ($query) use ($search) {
                        $query->orWhere("$this->parentTable.title", 'like', '%' . $search . '%');
                        $query->orWhere("$this->parentTable.asin", 'like', '%' . $search . '%');
                        $query->orWhere("$this->parentTable.sku", 'like', '%' . $search . '%');
                    });
                }

                $products->whereNotIn("$this->parentTable.id", $exsitingProducts);
                $products->where("$this->parentTable.sellable_units", '>', 0);
                $products->where("$this->parentTable.store_id", $storeId);
                $products->where("$this->parentTable.if_fulfilled_by_amazon", 1);
                $products->where("$this->parentTable.is_active", '!=', 0);

                $products->groupBy("$this->parentTable.id");

                if ($request->reqType == 'forGetProductListing') {
                    return Datatables::of($products)
                        ->addIndexColumn()
                        ->setRowId(function ($products) {
                            return $products->id;
                        })
                        ->addColumn('checkbox', function ($products) {
                            return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-4px d-flex-inline select_row_btn" value="' . $products->id . '" role="button">';
                        })
                        ->editColumn('sku', function ($products) {
                            return $products->sku;
                        })
                        ->editColumn('asin', function ($products) {
                            return '<a href="https://www.amazon.ca/dp/' . $products->asin . '" target="_blank" class="link-class">' . $products->asin . '</a>';
                        })
                        ->editColumn('title', function ($products) {
                            return $products->title;
                        })
                        ->editColumn('qty', function ($products) {
                            return view('inventory-detail', compact('products'));
                        })
                        ->editColumn('pack_of', function ($products) {
                            return $products->pack_of;
                        })
                        ->editColumn('sellable_units', function ($products) {
                            return empty($products->sellable_units) ? 0 : $products->sellable_units;
                        })
                        ->editColumn('wh_qty', function ($products) {
                            return empty($products->wh_qty) ? 0 : $products->wh_qty;
                        })
                        ->editColumn('main_image', function ($products) {
                            return (!empty($products->main_image)) ? '<a href="' . $products->main_image . '" target="_blank"><img src="' . $products->main_image . '" width="75" height="75"></a>' : '-';
                        })
                        ->rawColumns(['checkbox', 'asin', 'sku', 'fnsku', 'title', 'main_image', 'qty', 'pack_of', 'sellable_units', 'wh_qty'])
                        ->make(true);
                } elseif ($request->reqType == 'forCkeckboxSelection') {
                    return response()->json(['status' => true, 'products' => $products->get()->pluck('id')->toArray()]);
                }
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        }
    }

    public function insertSelectedFbaProducts(Request $request, $po_id = '')
    {
        try {
            $selectedProducts = $request->selectedProducts;
            if(empty($po_id))
            {
                $planId = $request->planId;
                $insertArray = [];
                if (!empty($selectedProducts))
                {
                    foreach ($selectedProducts as $selectedProduct) {
                        $insertArray[] = [
                            'shipment_plan_id' => $planId,
                            'amazon_product_id' => $selectedProduct,
                            'title' => AmazonProduct::find($selectedProduct)->title,
                            'sku' => AmazonProduct::find($selectedProduct)->sku,
                            'asin' => AmazonProduct::find($selectedProduct)->asin,
                            'created_at' => Carbon::now(),
                            'created_by' => Auth::user()->id,
                        ];
                    }
                }
                if (!empty($insertArray))
                {
                    ShipmentProduct::insert($insertArray);
                    return response()->json(['status' => true, 'message' => 'Product(s) added successfully.']);
                } else {
                    return response()->json(['status' => false, 'message' => 'No product(s) selected.']);
                }
            }else{
                if(app('session')->has("po_$po_id"))
                {
                    $selectedProducts = array_merge($selectedProducts, array_diff(session("po_$po_id"), $selectedProducts));
                    // $selectedProducts = [...$selectedProducts, ...(session("po_$po_id"))];
                }

                session(["po_$po_id" => [...$selectedProducts]]);
                // Log::info("after insert session = ",session("po_$po_id"));
                return response()->json(['status' => true, 'message' => 'Product(s) added successfully.']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function storewiseProducts(Request $request)
    {
        $poProducts = PurchaseOrderItem::where('po_id', $request->poId)
            ->whereHas('product', function ($query) use ($request) {
                $query->where('store_id', $request->storeId)->select('id');
            })
            ->with(['product' => function ($query) use ($request) {
                return $query->with(['store' => function ($storeQuery) {
                    return $storeQuery->select('id', 'store_name');
                }])
                    ->where('store_id', $request->storeId)
                    ->select(
                        'id',
                        'title',
                        'asin',
                        'sku',
                        'qty',
                        'afn_reserved_quantity',
                        'store_id',
                        'afn_unsellable_quantity',
                        'afn_inbound_working_quantity',
                        'afn_inbound_shipped_quantity',
                        'afn_inbound_receiving_quantity',
                        'main_image',
                        'pack_of',
                        'case_pack',
                        'wh_qty',
                        'reserved_qty',
                        'is_hazmat',
                        'is_oversize',
                        'product_note',
                        'sellable_units'
                    );
            }])
            ->select('product_id', 'po_id', 'received_qty')
            ->orderBy('id', 'desc')
            ->get();

        return view('shipment_plans.storewise_products', compact('poProducts'));
    }

    public function getEmptySellableUnits(Request $request)
    {
        $shipment_plan_id = $request->shipment_plan_id;
        if ($shipment_plan_id) {
            $emptySellableUnits = ShipmentProduct::where('shipment_plan_id', $shipment_plan_id)->where('sellable_unit', 0)->get()->count();
            return response()->json(['status' => true, 'emptySellableUnits' => $emptySellableUnits]);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid Request']);
        }
    }

    /**
     * Update the Sellable Units on Edit mode
     */
    public function updateAutoShipmentDetail(Request $request)
    {
        $updateType = $request->updateType;
        $planId = $request->planId;
        $productId = $request->productId;

        if ($updateType == 'sellableUnit') {
            $sellableUnit = $request->sellableUnit;
            try {
                ShipmentProduct::where('shipment_plan_id', $planId)->where('amazon_product_id', $productId)->update(['sellable_unit' => $sellableUnit]);
                $productData = AmazonProduct::select('wh_qty', 'reserved_qty', 'sellable_units')->where('id', $productId)->first();
                return response()->json(['status' => true, 'productData' => $productData]);
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => $e->getMessage()]);
            }
        } elseif ($updateType == 'productNote') {
            $productNote = $request->productNote;
            try {
                AmazonProduct::where('id', $productId)->update(['product_note' => $productNote]);
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // public function updateAutoShipmentDetail(Request $request, CommonService $commonService)
    // {
    //     $updateType = $request->updateType;
    //     $planId = $request->planId;
    //     $productId = $request->productId;

    //     if ($updateType == 'sellableUnit')
    //     {
    //         $sellableUnit = $request->sellableUnit;
    //         DB::beginTransaction();
    //         try {
    //             $shipmentProduct = ShipmentProduct::where('shipment_plan_id', $planId)->where('amazon_product_id', $productId)->first('id','sellable_unit');
    //             $oldSellableUnit = $shipmentProduct->sellable_unit;
    //             $shipmentProduct->update(['sellable_unit' => $sellableUnit]);

    //             $productData = AmazonProduct::select('wh_qty', 'reserved_qty', 'sellable_units', 'pack_of')->where('id', $productId)->first();
    //             $updatedSellableUnit = $sellableUnit - $oldSellableUnit;
    //             $productDetail = [
    //                 'id' => $productId,
    //                 'previous_qty' => $productData->wh_qty,
    //                 'updated_qty' => $productData->wh_qty - ($updatedSellableUnit * $productData->pack_of),
    //                 'pack_of' => $productData->pack_of
    //             ];

    //             $commonService->storeQtyLog($productDetail, 'Edit sellable unit in edit shipment');
                
    //             return response()->json(['status' => true, 'productData' => $productData]);
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             return response()->json(['status' => false, 'message' => $e->getMessage()]);
    //         }
    //     } elseif ($updateType == 'productNote') {
    //         $productNote = $request->productNote;
    //         try {
    //             AmazonProduct::where('id', $productId)->update(['product_note' => $productNote]);
    //         } catch (\Exception $e) {
    //             return response()->json(['status' => false, 'message' => $e->getMessage()]);
    //         }
    //     }
    // }

    public function addProducts(Request $request)
    {
        $poId = $request->po_id;
        $search = '';

        if ($poId)
        {
            $exsitingProducts = PurchaseOrderItem::where('po_id', $poId)->pluck('product_id')->toArray();

            if(app('session')->has("po_$poId"))
            {
                // $exsitingProducts = [...$exsitingProducts, ...(session("po_$poId"))];
                $exsitingProducts = [...(session("po_$poId"))];
            }
            try {
                $products = AmazonProduct::select(
                                    "amazon_products.id",
                                    "amazon_products.qty",
                                    "amazon_products.afn_reserved_quantity",
                                    "amazon_products.afn_unsellable_quantity",
                                    "amazon_products.afn_inbound_working_quantity",
                                    "amazon_products.afn_inbound_shipped_quantity",
                                    "amazon_products.afn_inbound_receiving_quantity",
                                    "amazon_products.asin",
                                    "amazon_products.pack_of",
                                    "amazon_products.inbound_shipping_cost",
                                    "amazon_products.sku",
                                    "amazon_products.title",
                                    "amazon_products.main_image",
                                    "amazon_products.if_fulfilled_by_amazon",
                                    "amazon_products.sellable_units",
                                    "amazon_products.wh_qty",
                                    "amazon_products.fnsku"
                                );

                if ($request->has('search') && $request->search["value"] != '')
                {
                    $search = $request->search["value"];
                } elseif (isset($request->product_search_data) && !empty($request->product_search_data)) {
                    $search = $request->product_search_data;
                }

                if (!empty($search))
                {
                    $products->where(function ($query) use ($search) {
                        $query->orWhere("amazon_products.title", 'like', '%' . $search . '%');
                        $query->orWhere("amazon_products.asin", 'like', '%' . $search . '%');
                        $query->orWhere("amazon_products.sku", 'like', '%' . $search . '%');
                    });
                }

                $products->whereNotIn("amazon_products.id", $exsitingProducts);
                $products->where("amazon_products.sellable_units", '>', 0);
                // $products->where("amazon_products.store_id", $storeId);
                $products->where("amazon_products.if_fulfilled_by_amazon", 1);
                $products->where("amazon_products.is_active", '!=', 0);

                $products->groupBy("amazon_products.id");

                if ($request->reqType == 'forGetProductListing')
                {
                    return DataTables::of($products)
                        ->addIndexColumn()
                        ->setRowId(function ($products) {
                            return $products->id;
                        })
                        ->addColumn('checkbox', function ($products) {
                            return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-4px d-flex-inline select_row_btn" value="' . $products->id . '" role="button">';
                        })
                        ->editColumn('sku', function ($products) {
                            return $products->sku;
                        })
                        ->editColumn('asin', function ($products) {
                            return '<a href="https://www.amazon.ca/dp/' . $products->asin . '" target="_blank" class="link-class">' . $products->asin . '</a>';
                        })
                        ->editColumn('title', function ($products) {
                            return $products->title;
                        })
                        ->editColumn('qty', function ($products) {
                            return view('inventory-detail', compact('products'));
                        })
                        ->editColumn('pack_of', function ($products) {
                            return $products->pack_of;
                        })
                        ->editColumn('sellable_units', function ($products) {
                            return empty($products->sellable_units) ? 0 : $products->sellable_units;
                        })
                        ->editColumn('wh_qty', function ($products) {
                            return empty($products->wh_qty) ? 0 : $products->wh_qty;
                        })
                        ->editColumn('main_image', function ($products) {
                            return (!empty($products->main_image)) ? '<a href="' . $products->main_image . '" target="_blank"><img src="' . $products->main_image . '" width="75" height="75"></a>' : '-';
                        })
                        ->rawColumns(['checkbox', 'asin', 'sku', 'fnsku', 'title', 'main_image', 'qty', 'pack_of', 'sellable_units', 'wh_qty'])
                        ->make(true);
                } elseif ($request->reqType == 'forCkeckboxSelection') {
                    return response()->json(['status' => true, 'products' => $products->get()->pluck('id')->toArray()]);
                }
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        }
    }
}
