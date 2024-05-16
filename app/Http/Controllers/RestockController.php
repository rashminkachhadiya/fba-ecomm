<?php

namespace App\Http\Controllers;

use App\DataTables\RestockDatatable;
use App\DataTables\RestockProductsDatatable;
use App\Http\Requests\RestockProductFilterRequest;
use App\Http\Requests\RestockRequest;
use App\Http\Requests\SupplierProductsRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Batch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RestockDatatable $dataTable)
    {
        $suppliers = Supplier::where('status', '1')->whereNULL('deleted_at')->pluck('name', 'id')->toArray();
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('restocks.list', compact('listingCols', 'suppliers'));
    }

    public function supplierProductsList($supplier_id, RestockProductsDatatable $dataTable)
    {
        if (!empty($supplier_id)) {
            $sumOfOrderQty = SupplierProduct::where('supplier_id', $supplier_id)->sum('order_qty');
            $supplier = Supplier::find($supplier_id);
            $suppliers = Supplier::where('status', '1')->whereNULL('deleted_at')->pluck('name', 'id')->toArray();
            return $dataTable->with('supplier_id', $supplier_id)->render('restocks.supplier_products_list', compact('supplier', 'supplier_id', 'sumOfOrderQty','suppliers'));
        }

    }

    /**
     * Save purchase of the resource.
     */
    public function createPO(RestockRequest $request)
    {
        return self::save($request);
    }

    /**
     * Create purchase order.
     */
    public function save(Request $request)
    {
        if ($request->purchase_order_id == 0 && $request->sum_of_order_qty != 0) {

            try {
                $purchaseOrder = PurchaseOrder::create([
                    'supplier_id' => $request->supplier_id,
                    'po_number' => $request->po_number,
                    'po_order_date' => $request->po_order_date,
                    'expected_delivery_date' => $request->expected_delivery_date,
                    'order_note' => !empty($request->order_note) ? $request->order_note : '',
                    'created_by' => auth()->user()->id,
                    'created_at' => Carbon::now(),
                ]);

                $purchaseOrderItems = SupplierProduct::with('amazonProduct')->where('supplier_id', $request->supplier_id)->get();
                $purchaseOrderItemsArray = [];
                if ($purchaseOrder && !empty($purchaseOrderItems)) {
                    foreach ($purchaseOrderItems as $purchaseOrderItem) {

                        if ($purchaseOrderItem->order_qty > 0) {
                            $purchaseOrderItemsArray[] = [
                                'po_id' => $purchaseOrder->id,
                                'supplier_id' => $purchaseOrderItem->supplier_id,
                                'supplier_product_id' => $purchaseOrderItem->id,
                                'product_id' => $purchaseOrderItem->product_id,
                                'unit_price' => floatval($purchaseOrderItem->unit_price),
                                'order_qty' => $purchaseOrderItem->order_qty,
                                'total_price' => floatval($purchaseOrderItem->unit_price) * floatval($purchaseOrderItem->order_qty),
                                'received_qty' => 0,
                                'received_price' => 0,
                                'difference_qty' => $purchaseOrderItem->order_qty,
                                'difference_price' => floatval($purchaseOrderItem->unit_price) * floatval($purchaseOrderItem->order_qty),
                                'total_product_cost' => floatval($purchaseOrderItem->amazonProduct->price) * floatval($purchaseOrderItem->order_qty),
                                'created_by' => auth()->user()->id,
                                'created_at' => Carbon::now(),
                            ];
                        }
                    }
                    PurchaseOrderItem::insert($purchaseOrderItemsArray);
                }

                $supplierProducts = SupplierProduct::where('supplier_id', $request->supplier_id)->get();
                $updateSupplierProductQty = [];
                if (!empty($supplierProducts)) {
                    foreach ($supplierProducts as $product) {
                        $updateSupplierProductQty[] = [
                            'id' => $product->id,
                            'order_qty' => 0,
                        ];
                    }

                    if (!empty($updateSupplierProductQty)) {
                        try {
                            $supplierProductInstance = new SupplierProduct();
                            Batch::update($supplierProductInstance, $updateSupplierProductQty, 'id');

                        } catch (\Exception $ex) {
                            return $this->sendError($ex->getMessage(), 500);
                        }
                    }
                }

                $response = [
                    'type' => 'success',
                    'created_purchase_order_id' => $purchaseOrder->id,
                    'status' => 200,
                    'message' => 'Purchase Order Created successfully',
                ];

            } catch (\Exception $e) {
                $response = [
                    'type' => 'error',
                    'status' => 500,
                    'message' => $e,
                ];
            }
            return response()->json($response);
        }else{
            return $this->sendError('Please add at least one product qty in PO', 500);
        }
    }

    /**
     * Update Product Order Qty.
     */
    public function updateOrderQty(SupplierProductsRequest $request, string $id)
    {
        $data = $request->validated();
        if (!empty($request->supplier_products_id)) {
            $orderQty = $request->order_qty;

            try {
                SupplierProduct::where('id', $request->supplier_products_id)->update([
                    'order_qty' => is_null($orderQty) ? 0 : $orderQty,
                    'updated_at' => Carbon::now(),
                ]);

                $sumOfOrderQty = SupplierProduct::where('supplier_id', $request->supplier_id)->sum('order_qty');
                return response()->json([
                    'sumOfOrderQty' => $sumOfOrderQty,
                ]);
            } catch (\Exception $ex) {
                return $this->sendError($ex->getMessage(), 500);
            }
        }
    }

    public function columnVisibility(Request $request, RestockDataTable $dataTable)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.restocks'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }

    }
}
