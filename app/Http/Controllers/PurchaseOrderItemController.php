<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierProductsRequest;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierProduct;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class PurchaseOrderItemController extends Controller
{
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            PurchaseOrderItem::where('id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            return $this->sendResponse('PO Items deleted successfully', 200);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function getPOItemList(Request $request, SupplierProduct $model)
    {
        $search = '';
        try {
            $po_id = $request->po_id;
            $existingItems = PurchaseOrderItem::where('po_id', $po_id)->pluck('product_id')->toArray();

            $model = $model->newQuery();

            $model->leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
                ->select('amazon_products.id as product_id','amazon_products.qty as fba_qty',  'supplier_products.id as supplier_product_id', 'amazon_products.sku', 'amazon_products.title', 'amazon_products.asin','amazon_products.main_image')
                ->where('supplier_id', $request->supplier_id);

            if ($request->has('search') && $request->search["value"] != '') {
                $search = $request->search["value"];
            }elseif(isset($request->product_search_data) && !empty($request->product_search_data)){
                $search = $request->product_search_data;
            }

            if(!empty($search)){
                $model->where(function ($query) use ($search) {
                    $query->orWhere('amazon_products.title', 'like', '%' . $search . '%');
                    $query->orWhere('amazon_products.sku', 'like', '%' . $search . '%');
                    $query->orWhere('amazon_products.asin', 'like', '%' . $search . '%');
                });
            }
            
            $products = $model->whereNotIn('product_id', $existingItems)->get();

            if($request->reqType == 'forGetProductListing'){
                return Datatables::of($products)
                    ->setRowId(function ($products) {
                        return $products->supplier_product_id;
                    })
                    ->addColumn('checkbox', function ($products) {
                        return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-4px d-flex-inline add_select_row_btn unique_checkbox_'.$products->supplier_product_id.'" value="' . $products->supplier_product_id . '" role="button">';
                    })
                    ->editColumn('main_image', function ($products) {
                        return '<img src="' . $products->main_image . '" width="50" height="50">';
                    })
                    ->editColumn('sku', function ($products) {
                        return $products->sku;
                    })
                    ->editColumn('asin', function ($products) {
                        return '<a href="https://www.amazon.com/dp/' . $products->asin . '" target="_blank" class="product-url link-class">' . $products->asin . '</a>';
                    })
                    ->editColumn('fba_qty', function ($products) {
                        return empty($products->fba_qty) ? '0' : $products->fba_qty;
                    })
                    ->addIndexColumn()
                    ->rawColumns(['checkbox', 'sku', 'asin','fba_qty','main_image'])
                    ->make(true);
                }elseif($request->reqType == 'forCkeckboxSelection'){
                    return response()->json(['status' => true, 'products' => $products->pluck('supplier_product_id')->toArray()]);
                }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $productData = $request->selectedItems;
            $addProductsrray = [];
            $productIdArray = [];

            if (!empty($productData)) {
                foreach ($productData as $key => $value) {
                    $productIdArray[] = SupplierProduct::where('id', $value)->select('product_id', 'unit_price')->get()->toArray();
                }
            }
            if (!empty($productData) && is_array($productData)) {
                foreach ($productData as $key => $value) {
                    $addProductsrray[] = [
                        'po_id' => $request->poId,
                        'supplier_id' => $request->supplierId,
                        'product_id' => $productIdArray[$key][0]['product_id'],
                        'unit_price' => $productIdArray[$key][0]['unit_price'],
                        'supplier_product_id' => $value,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                    ];
                }
            }

            $products = PurchaseOrderItem::insert($addProductsrray);

            if ($products) {
                return $this->sendResponse('Product Added successfully', 200);
            }
            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Update PO Product Order Qty.
     */
    public function updatePOItemOrderQty(SupplierProductsRequest $request)
    {
        $updateType = $request->updateType;
        if (!empty($request->po_item_id)) {
            $orderQty = $request->order_qty;
            $totalPrice = $request->total_price;
            $unitPrice = $request->unit_price;
            if ($updateType == 'order_qty') {
                try {
                    PurchaseOrderItem::where('id', $request->po_item_id)->update([
                        'order_qty' => $orderQty,
                        'total_price' => $totalPrice,
                        'difference_qty' => $orderQty,
                        'difference_price' => $totalPrice,
                        'updated_at' => Carbon::now(),
                    ]);
                } catch (Exception $ex) {
                    return $this->sendError($ex->getMessage(), 500);
                }
            } elseif ($updateType == 'unit_price') {
                try {
                    PurchaseOrderItem::where('id', $request->po_item_id)->update([
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'difference_price' => $totalPrice,
                        'updated_at' => Carbon::now(),
                    ]);
                } catch (Exception $ex) {
                    return $this->sendError($ex->getMessage(), 500);
                }
            }
        }
    }

    /**
     * Bulk delete option 
     */
    public function poItemBulkDelete(Request $request)
    {
        $deleteIds = explode(',', $request->selected_rows);

        // Purchase order item delete        
        PurchaseOrderItem::whereIn('id', $deleteIds)->delete();

        return response()->json([
            'type'   => 'success',
            'status' => 200,
            'message' => 'Purchase Order Items deleted successfully',
        ]);
    }
}
