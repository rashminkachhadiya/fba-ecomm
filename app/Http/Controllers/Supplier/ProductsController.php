<?php

namespace App\Http\Controllers\Supplier;

use App\DataTables\Supplier\ProductsDatatable;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierProductsRequest;
use App\Models\AmazonProduct;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Batch;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductsDatatable $dataTable, Request $request)
    {
        if ($request->has('supplierId')) {
            $supplier_id = request('supplierId');

        } else {
            $supplier_id = '';
        }
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('suppliers.products.list', compact(['supplier_id', 'listingCols']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getProductList(Request $request)
    {
        $search = '';

        try {
            $supplier_id = $request->id;
            $existingProducts = SupplierProduct::where('supplier_id', $supplier_id)->pluck('product_id')->toArray();

            $products = AmazonProduct::select('id', 'sku', 'title', 'asin','main_image');

            if ($request->has('search') && $request->search["value"] != '') {
                $search = $request->search["value"];
            }elseif(isset($request->product_search_data) && !empty($request->product_search_data)){
                $search = $request->product_search_data;
            }

            if(!empty($search)){
                $products->where(function ($query) use ($search) {
                    $query->orWhere("title", 'like', '%' . $search . '%');
                    $query->orWhere("asin", 'like', '%' . $search . '%');
                    $query->orWhere("sku", 'like', '%' . $search . '%');
                });
            }

            $products->whereNotIn("id", $existingProducts);

            if($request->reqType == 'forGetProductListing'){
                return Datatables::of($products)
                    ->addIndexColumn()
                    ->setRowId(function ($products) {
                        return $products->id;
                    })
                    ->addColumn('checkbox', function ($products) {
                        return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-4px d-flex-inline select_row_btn mt-3" value="' . $products->id . '" role="button">';
                    })
                    ->editColumn('main_image', function ($products) {
                        return (!empty($products->main_image)) ? '<a href="' . $products->main_image . '" target="_blank"><img src="' . $products->main_image . '" width="50" height="50"></a>' : '-';
                    })
                    ->editColumn('sku', function ($products) {
                        return $products->sku;
                    })
                    ->editColumn('asin', function ($products) {
                        return '<a href="https://www.amazon.com/dp/'.$products->asin.'" target="_blank" class="product-url link-class">'.$products->asin.'</a>';
                    })
                    ->rawColumns(['checkbox','sku','asin','main_image'])
                    ->make(true);
                }elseif($request->reqType == 'forCkeckboxSelection'){
                    return response()->json(['status' => true, 'products' => $products->get()->pluck('id')->toArray()]);
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

            if (!empty($productData)) {
                foreach ($productData as $key => $value) {
                    $addProductsrray[] = [
                        'product_id' => $value,
                        'supplier_id' => $request->supplierId,
                        'supplier_sku' => '',
                        'unit_price' => '0.00',
                        'additional_cost' => '0.00',
                        'created_by' => auth()->user()->id,
                        'created_at' => Carbon::now(),
                    ];
                }
            }

            $chunkSize = 500;
            $totalRecords = count($addProductsrray);

            for ($i = 0; $i < $totalRecords; $i += $chunkSize) {
                $chunk = array_slice($addProductsrray, $i, $chunkSize);

                $products = SupplierProduct::insert($chunk);
            }

            if ($products) {
                return $this->sendResponse('Product Added successfully', 200);
            }
            return $this->sendError('Something went wrong', 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierProductsRequest $request, string $id)
    {
        $updateFor = $request->updateFor;
        $product_id = $request->supplier_products_id;

        if (($updateFor == 'supplier_sku') && !empty($product_id)) {
            $supplierSKU = $request->supplier_sku;

            try {
                SupplierProduct::where('id', $product_id)->update([
                    'supplier_sku' => is_null($supplierSKU) ? null : $supplierSKU,
                    'updated_at' => Carbon::now(),
                ]);

            } catch (\Exception $ex) {
                return $this->sendError($ex->getMessage(), 500);
            }
        }elseif (($updateFor == 'unit_price') && !empty($product_id)) {
            $unitPrice = $request->unit_price;

            try {
                SupplierProduct::where('id', $product_id)->update([
                    'unit_price' => is_null($unitPrice) ? 0.0 : $unitPrice,
                    'updated_at' => Carbon::now(),
                ]);

            } catch (\Exception $ex) {
                return $this->sendError($ex->getMessage(), 500);
            }
        }elseif (($updateFor == 'additional_cost') && !empty($product_id)) {
            $additionalCost = $request->additional_cost;

            try {
                SupplierProduct::where('id', $product_id)->update([
                    'additional_cost' => is_null($additionalCost) ? 0.0 : $additionalCost,
                    'updated_at' => Carbon::now(),
                ]);

            } catch (\Exception $ex) {
                return $this->sendError($ex->getMessage(), 500);
            }
        }else{
            return $this->sendError('Something went wrong', 500);
        }
    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            SupplierProduct::where('id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            return $this->sendResponse('Product deleted successfully', 200);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function columnVisibility(Request $request, ProductsDatatable $dataTable)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.supplier_products'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }
}
