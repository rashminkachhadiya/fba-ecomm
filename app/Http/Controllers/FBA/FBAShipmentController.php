<?php

namespace App\Http\Controllers\FBA;

use App\DataTables\FBA\FbaCommonShipmentDataTable;
use App\DataTables\FBA\FBAShipmentDataTable;
use App\DataTables\FBA\FbaWorkingShipmentDataTable;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FBATransportInfoRequest;
use App\Models\FbaPrepBoxDetail;
use App\Models\FbaShipment;
use App\Models\FbaShipmentItem;
use App\Models\FbaShipmentTransportDetail;
use App\Models\FbaShipmentTransportPalletDetail;
use App\Models\Setting;
use App\Models\ShipmentPlan;
use App\Models\ShipmentProduct;
use App\Models\Warehouse;
use App\Services\CreateShipmentService;
use App\Services\TransportService;
use Batch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FBAShipmentController extends Controller
{
    public function index(FBAShipmentDataTable $fbaShipmentDatatable)
    {
        $draftTabStatus = config('constants.draft_tab_status');
        $planList = ShipmentPlan::getDraftPlanList();
        return $fbaShipmentDatatable->render('fba.fba_shipment.list', compact('draftTabStatus', 'planList'));
    }

    public function show($shipmentId, Request $request)
    {
        $fbaShipment = FbaShipment::where('shipment_id', $shipmentId)->first();

        $plan = $this->getPlanDetailWithShipmentCount($fbaShipment);
        $shipmentTableData = $this->getShipmentTableData($fbaShipment);
        $uniqueSkuCount = $this->getUnqiueSkuCountByWorkingShipmentName($fbaShipment);
        $mainData = $this->viewShipmentData($fbaShipment, $request);
        return view('fba.fba_shipment.view', compact(['fbaShipment', 'plan', 'shipmentTableData', 'uniqueSkuCount', 'mainData']));
    }

    public function getPlanDetailWithShipmentCount($fbaShipment)
    {
        $plan = [];

        if ($fbaShipment->shipment_created_from == 1) {
            if (!empty($fbaShipment->fba_shipment_plan_id)) {
                $plan = ShipmentPlan::select('plan_name')
                    ->where('id', $fbaShipment->fba_shipment_plan_id)
                    ->withCount('fbaShipment')
                    ->first();

                if ($plan) {
                    $plan = $plan->toArray();
                }
            }
        } else if ($fbaShipment->shipment_created_from == 2) {
            // Created from 3rd party software
            $plan = [
                'plan_name' => $fbaShipment->shipment_name,
                'fba_shipment_count' => FbaShipment::where('shipment_name', $fbaShipment->shipment_name)
                    ->count('id'),
            ];
        }

        return $plan;
    }

    public function getShipmentTableData($shipInfo)
    {
        if ($shipInfo->shipment_created_from == 1) {
            $column = 'fba_shipment_plan_id';
            $columnData = $shipInfo->fba_shipment_plan_id;
        } else if ($shipInfo->shipment_created_from == 2) {

            $column = 'shipment_name';
            $columnData = $shipInfo->shipment_name;
        }

        $data = FbaShipment::select('id', 'shipment_id', 'deleted_at', 'shipment_status')->where($column, $columnData)
            ->with(['fbaShipmentItems' => function ($query) {
                $query->select('id', 'fba_shipment_id', DB::raw("SUM(quantity_shipped) as total_units"));
                $query->groupBy('fba_shipment_id');
            }])
            ->withCount('fbaShipmentItems')
            ->get()
            ->toArray();

        return $data;
    }

    public function viewShipmentData($shipInfo, object $request)
    {
        $response = [];

        if ($shipInfo->shipment_created_from == 1) {
            $shipments = FbaShipment::where('fba_shipment_plan_id', $shipInfo->fba_shipment_plan_id)
                ->get()
                ->toArray();
        } else if ($shipInfo->shipment_created_from == 2) {
            // Fetched from seller central of amazon
            $shipments = FbaShipment::where('shipment_name', $shipInfo->shipment_name)
                ->get()
                ->toArray();
        }

        if (count($shipments) > 0) {
            foreach ($shipments as $shipmentRow) {
                $dataQuery = FbaShipmentItem::select(
                    'fba_shipment_items.id',
                    'seller_sku',
                    'quantity_received',
                    'quantity_shipped as sellable_asin_qty',
                    'is_quantity_updated',
                    'amazon_products.asin',
                    'amazon_products.sku',
                    'amazon_products.pack_of',
                    'amazon_products.fnsku',
                    'amazon_products.title',
                    'amazon_products.is_hazmat',
                    'amazon_products.is_oversize',
                    'amazon_products.main_image',
                    'original_quantity_shipped'
                )
                // ->with('fbaPrepDetail')
                    ->where('shipment_id', $shipmentRow['shipment_id'])
                    ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku');

                // Search
                if (!empty($request->search)) {
                    $dataQuery->where(function ($q) use ($request) {
                        $q->where('seller_sku', 'LIKE', '%' . $request->search . '%');

                        //Search by Product Title in amazon_products
                        $q->orWhere('amazon_products.title', 'LIKE', '%' . $request->search . '%');

                        //Search by ASIN in amazon_products
                        $q->orWhere('amazon_products.asin', 'LIKE', '%' . $request->search . '%');

                        //Search by SKU in amazon_product
                        $q->orWhere('amazon_products.sku', 'LIKE', '%' . $request->search . '%');

                        //Search by FNSKU in amazn_products
                        $q->orWhere('amazon_products.fnsku', 'LIKE', '%' . $request->search . '%');
                    });
                }

                $data = $dataQuery
                // ->with('itemPrepDetail')
                    ->get()->toArray();

                $addData = [
                    'shipmentInfo' => $shipmentRow,
                    'shipmentProductInfo' => $data,
                ];

                $response[] = $addData;
            }
        }

        return $response;
    }

    public function getUnqiueSkuCountByWorkingShipmentName($shipment)
    {
        $skuCount = 0;

        if ($shipment->shipment_created_from == 2) {
            // From other system
            $shipmentIds = FbaShipment::where('shipment_name', $shipment->shipment_name)
                ->pluck('shipment_id')
                ->toArray();

            $skuCount = FbaShipmentItem::distinct('seller_sku')->whereIn('shipment_id', $shipmentIds)->count();
        } else {
            // From our system
            $skuCount = FbaShipmentItem::distinct('seller_sku')->where('fba_shipment_plan_id', $shipment->fba_shipment_plan_id)->count();
        }

        return $skuCount;
    }

    public function destroy(FbaShipment $fbaShipment)
    {
        try {
            $fbaShipment->delete();
            if ($fbaShipment) {
                $fbaShipment->fbaShipmentItems()->delete();
                return $this->sendResponse('Shipment deleted successfully', 200);
            }

            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    // Shipment > FBA Shipment > Working
    public function fbaWorkingShipmentsList(FbaWorkingShipmentDataTable $dataTable)
    {
        $latestFbaShipment = FbaShipment::select('id', 'shipment_status', 'updated_at')->where('shipment_status', '!=', null)->orderby('id', 'DESC')->first();

        $statusAttr = config('constants.prep_status');

        return $dataTable->render('fba.fba_shipment.fba_working_shipments_list', compact('statusAttr', 'latestFbaShipment'));
    }

    // Shipment > FBA Shipment > Shipped | In Transit | Received | Closed | Cancelled
    public function fbaCommonShipmentsList(Request $request, FbaCommonShipmentDataTable $dataTable)
    {
        $status = $request->status;

        return $dataTable->render('fba.fba_shipment.fba_common_shipments_list', compact('status'));
    }

    // For confirm shipment
    public function confirmShipment($shipmentId)
    {
        try {
            $fbaShipment = FbaShipment::where('shipment_id', $shipmentId)->first();

            $result = (new CreateShipmentService())->invokeCreateShipmentApi($fbaShipment->store_id, $fbaShipment->id);

            $response = [];
            if (isset($result['status']) && $result['status'] == 'success') {
                $fbaShipment->update(['is_approved' => 1]);
                $response = [
                    'type' => 'success',
                    'status' => 200,
                    'message' => isset($result['message']) ? $result['message'] : '',
                ];
            } else {
                $response = [
                    'type' => 'error',
                    'status' => 400,
                    'message' => isset($result['message']) && !empty($result['message']) ? $result['message'] : 'Something went wrong!',
                ];
            }

            return $response;
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'status' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function transportInfo($shipmetId)
    {
        $isPalletArray = [];
        $isPackageArray = [];

        if ($shipmetId) {
            try {
                $carrierNameArray = config('constants.fba_shipment_transport_carrier');

                $transportInfo = FbaShipmentTransportDetail::where('fba_shipment_id', $shipmetId)->first();
                $transportData = FbaShipmentTransportPalletDetail::where('fba_shipment_id', $shipmetId)->get();
                if (!empty($transportData)) {
                    foreach ($transportData as $key => $value) {
                        if ($value['is_pallet'] == '1') {
                            $isPalletArray[] = $value;
                        } else {
                            $isPackageArray[] = $value;
                        }
                    }
                }
                $shipmentData = Fbashipment::where('id', $shipmetId)->select('shipment_id','has_transport_detail','shipment_name')->first();
                $totalBox = FbaPrepBoxDetail::where('fba_shipment_id', $shipmentData['shipment_id'])->count();
                return view('fba.fba_shipment.transport_info', compact('transportInfo', 'shipmetId', 'carrierNameArray', 'totalBox', 'isPalletArray', 'isPackageArray','shipmentData'));
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }
    }

    public function createTransportPallet(Request $request)
    {
        $numberOfTotal = $request->total_num;
        $shipmentId = $request->shipment_log_id;
        $addType = $request->add_type;

        if (isset($addType) && $addType == 'add_pallet') {
            $isPallet = '1';
            $existingData = FbaShipmentTransportPalletDetail::where('fba_shipment_id', $shipmentId)->where('is_pallet', '1')->get();
        } elseif (isset($addType) && $addType == 'add_package') {
            $isPallet = '0';
            $existingData = FbaShipmentTransportPalletDetail::where('fba_shipment_id', $shipmentId)->where('is_pallet', '0')->get();
        }

        $insertDataArray = [];

        if ($numberOfTotal && !empty($numberOfTotal) && $numberOfTotal > 0) {
            for ($i = 1; $i <= $numberOfTotal; $i++) {
                $insertDataArray[] = [
                    'fba_shipment_id' => $shipmentId,
                    'is_pallet' => $isPallet,
                    'created_at' => Carbon::now(),
                ];
            }

            if (!empty($insertDataArray)) {
                FbaShipmentTransportPalletDetail::insert($insertDataArray);
                $responseArray = FbaShipmentTransportPalletDetail::where('fba_shipment_id', $shipmentId)->where('is_pallet', $isPallet)->whereNotIn('id', $existingData->pluck('id')->toArray())->get();
                $response = [
                    'type' => 'success',
                    'status' => 200,
                    'responseArray' => $responseArray,
                    'is_pallet' => $isPallet,
                ];
            }

        } else {
            return response()->json(['error' => 'Something went wrong']);
        }

        return $response;
    }

    public function deleteTransportPallet(Request $request)
    {
        $deleteFor = $request->deleteFor;
        if ($deleteFor && $deleteFor == 'pallet') {
            $msg = 'Pallet Deleted Successfully';
        } elseif ($deleteFor && $deleteFor == 'package') {
            $msg = 'Package Deleted Successfully';
        }
        $deleteId = $request->unique_id;
        if ($deleteId) {
            try {
                $data = FbaShipmentTransportPalletDetail::where('id', $deleteId)->delete();
                return $this->sendResponse($msg, 200);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        }
    }

    public function saveTransportInfo(FBATransportInfoRequest $request)
    {
        $response = $this->storeTransportDetail($request);
        if (isset($response) && $response['type'] == 'success') {
            return $this->sendResponse($response['message'], 200);
        } elseif (isset($response) && $response['type'] == 'error') {
            return $this->sendError($response['message'], 500);
        } else {
            return response()->json(['error' => 'Something went wrong']);
        }

    }

    public function moveAndDeleteShipment(Request $request)
    {
        $loggedUser = auth()->user()->id;
        DB::beginTransaction();

        $fromPlanName = null;

        $shipmentId = $request->shipment_id;

        // Move products to existing plan
        if ($request->move_type == 1) {
            $attachedToPlanId = $request->planId;
            $toPlanObj = ShipmentPlan::select('plan_name')->where('id', $attachedToPlanId)->first();
            $toPlanName = $toPlanObj->plan_name;
        } else {
            $toPlanName = $request->input_plan_name;
            $warehouseId = null;

            $warehouse = Warehouse::first();
            if ($warehouse) {
                $warehouseId = $warehouse->id;
            }

            $planObj = ShipmentPlan::create([
                'plan_name' => $toPlanName,
                'warehouse_id' => $warehouseId,
                'created_by' => $loggedUser,
            ]);

            $attachedToPlanId = $planObj->id;
        }

        // fba_shipment_items: Take seller_sku and array by shipment_id
        $skuAndQtyArr = FbaShipmentItem::where('shipment_id', $shipmentId)->pluck('quantity', 'seller_sku')->toArray();

        $totalSkus = count($skuAndQtyArr);

        // fba_shipments: Take fba_shipment_plan_id by shipment_id
        $planId = FbaShipment::where('shipment_id', $shipmentId)->pluck('fba_shipment_plan_id')->first();

        // fba_shipments : Updated delete flag by shipment id
        FbaShipment::where('shipment_id', $shipmentId)->update([
            'deleted_by' => $loggedUser,
            'deleted_at' => CommonHelper::getInsertedDateTime(),
        ]);

        if (!empty($planId)) {
            // Get from plan name
            $fromPlanObj = ShipmentPlan::select('plan_name')->where('id', $planId)->first();

            $fromPlanName = $fromPlanObj->plan_name;
            ShipmentProduct::manageSkuQtyInMoveAndDeleteShipment($skuAndQtyArr, $planId, $shipmentId, $attachedToPlanId);

            // Roll back if more than 200 SKUs are attached with the plan
            $afterAddingSkuCount = ShipmentProduct::where('shipment_plan_id', $attachedToPlanId)->count();

            // 200 SKU limit validation
            if ($afterAddingSkuCount > 200) {
                DB::rollBack();

                $message = "200 SKU limit is reached. Products can not be moved to selected plan. Process aborted.";

                return [
                    'status' => false,
                    'message' => $message,
                ];
            }
        }

        // fba_shipment_items: Updated deleted flag by shipment id
        FbaShipmentItem::where('shipment_id', $shipmentId)->update([
            'deleted_by' => $loggedUser,
            'deleted_at' => CommonHelper::getInsertedDateTime(),
        ]);

        DB::commit();

        $message = $totalSkus > 1 ? "$totalSkus products from plan $fromPlanName moved successfully to $toPlanName" :
        "$totalSkus product from plan $fromPlanName moved successfully to $toPlanName";

        return [
            'status' => true,
            'message' => $message,
        ];
    }

    public function putShipmentTransportDetail(FBATransportInfoRequest $request)
    {
        $response = $this->storeTransportDetail($request);
        if (isset($response) && $response['type'] == 'success') {
            $shipping_method = $request->shipping_method_radio;
            $shipping_carrier = $request->shipping_carrier_type;

            $fbaShipment = Fbashipment::where('id', $request->shipment_log_id)->select('shipment_id', 'store_id')->first();
            $fbaShipmentItem = FbaShipmentItem::select(
                'fba_shipment_items.id',
                'seller_sku',
                'amazon_products.sku',
                'amazon_products.is_hazmat')
                ->where('shipment_id', $fbaShipment['shipment_id'])
                ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
                ->get();

                if($shipping_carrier == '1'){
                    $isHazmatItem = [];
                    if(!empty($fbaShipmentItem) && $fbaShipmentItem->count() > 0){
                        foreach($fbaShipmentItem as $item){
                            if($item->is_hazmat == 1){
                                $isHazmatItem[] = $item->id;
                            }
                        }
                    }
                    if(!empty($isHazmatItem) && count($isHazmatItem) > 0){
                        return $this->sendError('Cannot use partnered carrier for hazmat shipment ['.$fbaShipment['shipment_id'].']', 500);
                    }
                }
            if ($shipping_method == 'SP') {
                if ($shipping_carrier == '1') {

                    $body = [
                        'IsPartnered' => true,
                        'ShipmentType' => 'SP',
                    ];

                    $packageInfoArray = [];

                    $total_package = $request->number_of_package;

                    if (isset($total_package) && !empty($total_package)) {
                        foreach ($total_package as $key => $package_value) {
                            for ($i = 0; $i < $package_value; $i++) {
                                $packageInfoArray[] = [
                                    'Dimensions' => [
                                        "Length" => $request->package_length[$key],
                                        "Width" => $request->package_width[$key],
                                        "Height" => (double) $request->package_height[$key],
                                        "Unit" => "inches",
                                    ],
                                    'Weight' => [
                                        "Value" => (double) $request->package_weight[$key],
                                        "Unit" => "pounds",
                                    ],
                                ];
                            }
                        }
                    }

                    $partneredSmallParcelData = [
                        'PartneredSmallParcelData' => [
                            'PackageList' => $packageInfoArray,
                        ],
                    ];
                    $body['TransportDetails'] = $partneredSmallParcelData;

                } elseif ($shipping_carrier == '0') {
                    $body = [
                        'IsPartnered' => false,
                        'ShipmentType' => 'SP',
                    ];

                    $nonPartneredSmallParcelData = [
                        'NonPartneredSmallParcelData' => [
                            'CarrierName' => $request->other_shipping_carrier,
                            'PackageList' => [
                                'TrackingId' => $request->tracking_id,
                            ],
                        ],
                    ];

                    $body['TransportDetails'] = $nonPartneredSmallParcelData;
                }

            } elseif ($shipping_method == 'LTL') {

                if ($shipping_carrier == '0') {

                    $body = [
                        'IsPartnered' => false,
                        'ShipmentType' => 'LTL',
                    ];

                    $nonPartneredLtlData = [
                        'NonPartneredLtlData' => [
                            'CarrierName' => $request->other_shipping_carrier,
                            'ProNumber' => $request->pro_number,
                        ],
                    ];

                    $body['TransportDetails'] = $nonPartneredLtlData;
                } elseif ($shipping_carrier == '1') {
                    $body = [
                        'IsPartnered' => true,
                        'ShipmentType' => 'LTL',
                    ];

                    $palletInfoArray = [];

                    $companyDetail = Setting::select('company_email', 'company_phone')->first();

                    $total_pallet = $request->number_of_pallet;

                    if (isset($total_pallet) && !empty($total_pallet)) {
                        foreach ($total_pallet as $key => $pallet_value) {
                            for ($i = 0; $i < $pallet_value; $i++) {
                                $palletInfoArray[] = [
                                    'Dimensions' => [
                                        "Length" => (double) 48,
                                        "Width" => (double) 40,
                                        "Height" => (double) $request->pallet_height[$key],
                                        "Unit" => "inches",
                                    ],
                                    'Weight' => [
                                        "Value" => (double) $request->pallet_weight[$key],
                                        "Unit" => "pounds",
                                    ],
                                    'IsStacked' => isset($request->is_stackable[$key]) ? true : false,
                                ];
                            }
                        }
                    }

                    $partneredLtlData = [
                        'PartneredLtlData' => [
                            'Contact' => [
                                'Name' => 'STANBI',
                                'Phone' => $companyDetail['company_phone'],
                                'Email' => $companyDetail['company_email'],
                            ],
                            'BoxCount' => $request->number_boxes,
                            'PalletList' => $palletInfoArray,
                            'FreightReadyDate' => isset($request->freight_ready_date) ? date('Y-m-d', strtotime($request->freight_ready_date)) : '',
                        ],
                    ];
                    $body['TransportDetails'] = $partneredLtlData;
                }
            }
            try {
                $result = (new TransportService())->invokePutTransportDetailApi($fbaShipment['store_id'], $fbaShipment['shipment_id'], $body);

                if (isset($result['status']) && $result['status'] == 'success') {
                    return $this->sendResponse(isset($result['message']) ? $result['message'] : 'Put Transport Detail Successfully', 200);
                } else {
                    return $this->sendError(isset($result['message']) ? $result['message'] : 'Something went wrong!', 500);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }

        } elseif (isset($response) && $response['type'] == 'error') {
            return $this->sendError($response['message'], 500);
        } else {
            return response()->json(['error' => 'Something went wrong']);
        }
    }

    public function storeTransportDetail($request)
    {
        $updateInsertDataArray = [];
        $response = [];

        $shipping_method = $request->shipping_method_radio;
        $shipping_carrier = $request->shipping_carrier_type;

        if ($shipping_method == 'LTL' && $shipping_carrier == '1') {
            if (!isset($request->pallet_height) || empty($request->pallet_height)) {
                $response = [
                    'type' => 'error',
                    'message' => 'Please add at least one pallet detail',
                ];
            }
        } elseif ($shipping_method == 'SP' && $shipping_carrier == '1') {
            if (!isset($request->package_height) || empty($request->package_height)) {
                $response = [
                    'type' => 'error',
                    'message' => 'Please add at least one Package detail',
                ];
            }
        }
        if (isset($response['type']) && $response['type'] == 'error') {
            return $response;
        } else {
            try {
                $insertUpdateArray = [
                    'shipping_method' => $shipping_method,
                    'shipping_carrier' => $shipping_carrier,
                    'fba_shipment_id' => $request->shipment_log_id,
                    'freight_ready_date' => $request->freight_ready_date,
                    'seller_declared_value' => $request->seller_declared_value,
                    'number_boxes' => $request->number_boxes,
                    'pro_number' => $request->pro_number,
                    'other_shipping_carrier' => $request->other_shipping_carrier,
                    'tracking_id' => $request->tracking_id,
                    'system_status' => '0',
                ];

                FbaShipmentTransportDetail::updateOrCreate(['fba_shipment_id' => $insertUpdateArray['fba_shipment_id']], $insertUpdateArray);

                $pallet_height = $request->pallet_height;
                $package_height = $request->package_height;
                if (isset($pallet_height) && !empty($pallet_height)) {
                    foreach ($pallet_height as $key => $value) {
                        $updateInsertDataArray[] = [
                            'pallet_height' => $value,
                            'pallet_weight' => $request->pallet_weight[$key],
                            'number_of_pallet' => $request->number_of_pallet[$key],
                            'pallet_total_weight' => $request->pallet_total_weight[$key],
                            'id' => $key,
                            'is_stackable' => isset($request->is_stackable[$key]) ? '1' : '0',
                        ];
                    }

                } elseif (isset($package_height) && !empty($package_height)) {
                    foreach ($package_height as $key => $value) {
                        $updateInsertDataArray[] = [
                            'package_length' => $request->package_length[$key],
                            'package_width' => $request->package_width[$key],
                            'package_height' => $value,
                            'package_weight' => $request->package_weight[$key],
                            'number_of_package' => $request->number_of_package[$key],
                            'package_total_weight' => $request->package_total_weight[$key],
                            'id' => $key,
                        ];
                    }
                }

                if (!empty($updateInsertDataArray)) {
                    Batch::update(new FbaShipmentTransportPalletDetail, $updateInsertDataArray, 'id');
                }

                $response = [
                    'type' => 'success',
                    'message' => 'Transport information saved successfully',
                ];
            } catch (\Exception $e) {
                $response = [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
            return $response;
        }
    }

    public function estimateTransportDetail(Request $request){

        $fbaShipment = Fbashipment::getShipmentData($request->shipment_id);
        
        try {
            $result = (new TransportService())->invokeEstimateTransportApi($fbaShipment['store_id'], $fbaShipment['shipment_id']);

            if (isset($result['status']) && $result['status'] == 'success') {
                return $this->sendResponse(isset($result['message']) ? $result['message'] : 'Estimate Transport Detail Successfully', 200);
            } else {
                return $this->sendError(isset($result['message']) ? $result['message'] : 'Something went wrong!', 500);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }

    }

    public function confirmTransportDetail(Request $request){
        $fbaShipment = Fbashipment::getShipmentData($request->shipment_id);

        try {
            $result = (new TransportService())->invokeConfirmTransportApi($fbaShipment['store_id'], $fbaShipment['shipment_id']);

            if (isset($result['status']) && $result['status'] == 'success') {
                return $this->sendResponse(isset($result['message']) ? $result['message'] : 'Transport Details Confirmed Successfully', 200);
            } else {
                return $this->sendError(isset($result['message']) ? $result['message'] : 'Something went wrong!', 500);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }

    }

    public function voidTransportDetail(Request $request){
        $fbaShipment = Fbashipment::getShipmentData($request->shipment_id);

        try {
            $result = (new TransportService())->invokeVoidTransportApi($fbaShipment['store_id'], $fbaShipment['shipment_id']);

            if (isset($result['status']) && $result['status'] == 'success') {
                return $this->sendResponse(isset($result['message']) ? $result['message'] : 'Transport Details Cancelled Successfully', 200);
            } else {
                return $this->sendError(isset($result['message']) ? $result['message'] : 'Something went wrong!', 500);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }

    }

}
