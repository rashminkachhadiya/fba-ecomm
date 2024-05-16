<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests\UpdateDefaultSupplierRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\AmazonProduct;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductDataTable $dataTable)
    {
        $statusArr = ["1" => "Active", "0" => "In-Active"];
        $listingCols = $dataTable->listingColumns();
        $stores = Store::active()->pluck('store_name', 'id')->toArray();
        $suppliers = Supplier::active()->get(['name', 'id']);
        return $dataTable->render('products.list', compact(['statusArr', 'listingCols', 'stores', 'suppliers']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = AmazonProduct::select('id', 'case_pack', 'inbound_shipping_cost', 'pack_of', 'wh_qty')->find($id);
        return $this->sendResponse('Product Details', 200, $product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $updateData = [];
            if (!empty($request->all())) {
                foreach ($request->all() as $key => $value) {
                    $updateData[$key] = $value;
                }
            }

            AmazonProduct::where('id', $id)->update($updateData);

            return $this->sendResponse("Product note updated successfully.", 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.products'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }

    public function supplierList(Request $request)
    {
        $query = SupplierProduct::leftJoin('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->whereNull('suppliers.deleted_at')
            ->whereProductId(request('product_id'))
            ->select('supplier_products.id', 'supplier_products.supplier_sku', 'supplier_products.unit_price', 'suppliers.name', 'supplier_products.default_supplier', 'supplier_products.product_id');

        if ($request->has('search') && $request->search["value"] != '') {
            $search = $request->search["value"];
            $query->where(function ($sql) use ($search) {
                $sql->where("suppliers.name", 'LIKE', '%' . $search . '%');
            });
        }

        return DataTables::of($query)
            ->setTotalRecords($query->count("supplier_products.id"))
            ->addIndexColumn()
            ->editColumn('name', function ($value) {
                return ($value->name) ? $value->name : '-';
            })
            ->addColumn('default_supplier', function ($value) {
                $checked = ($value->default_supplier == 1) ? 'checked' : '';
                return "<div class='form-check form-switch form-switch-sm form-check-custom form-check-solid mb-6'><input class='form-check-input default_supplier' name='default_supplier[" . $value->id . "]' type='checkbox' $checked /></div>";
            })
            ->editColumn('supplier_sku', function ($value) {
                return "<input type='text' class='form-control supplier_sku' name='supplier_sku[" . $value->id . "]' data-supplier-product='" . $value->product_id . "' value='" . $value->supplier_sku . "' />";
            })
            ->editColumn('unit_price', function ($value) {
                return "<input type='number' class='form-control supplier_price' name='unit_price[" . $value->id . "]' data-supplier-product='" . $value->product_id . "' value='" . $value->unit_price . "' />";
            })
            ->rawColumns(['name', 'supplier_sku', 'unit_price', 'default_supplier'])
            ->make(true);
    }

    public function updateProduct(UpdateProductRequest $request, CommonService $commonService)
    {
        $productId = $request->input('product_id');

        $updateData = [
            'case_pack' => $request->input('case_pack'),
            'pack_of' => $request->input('pack_of'),
            'inbound_shipping_cost' => $request->input('inbound_shipping_cost'),
            'wh_qty' => $request->input('wh_qty'),
        ];

        $amazonProduct = AmazonProduct::where('id', $productId)->select('id', 'wh_qty')->first();
        $prevQty = $amazonProduct->wh_qty;
        $amazonProduct->update($updateData);

        $productDetail = [
            'id' => $productId,
            'previous_qty' => $prevQty,
            'updated_qty' => $request->input('wh_qty'),
            'pack_of' => $request->input('pack_of'),
        ];
        $commonService->storeQtyLog($productDetail, 'Product details updated');

        if ($request->ajax()) {
            // Return a response indicating success or failure
            return response()->json(['message' => 'Product information updated successfully']);
        }

        return redirect()->route('products.index');
    }

    public function updateProductDefaultSupplier(UpdateDefaultSupplierRequest $request)
    {
        $productId = $request->product_id;
        $supplierId = $request->supplier_id;
        if (isset($productId) && isset($supplierId)) {
            try {
                $productIdList = explode(",", $productId);
                foreach ($productIdList as $value) {
                    $updatedData = SupplierProduct::updateOrCreate(['product_id' => $value, 'default_supplier' => 1], ['supplier_id' => $supplierId]);
                }
                return response()->json(['message' => 'Product information updated successfully']);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        } else {
            return $this->sendError('Something went wrong', 500);
        }
    }
}
