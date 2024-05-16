<?php

namespace App\Http\Controllers\Supplier;

use App\DataTables\Supplier\SuppliersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Requests\UpdateSupplierProductRequest;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SuppliersDataTable $dataTable)
    {
        $statusArr = ["1" => "Active", "0" => "In-Active"];
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('suppliers.list', compact(['statusArr', 'listingCols']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        $data['created_by'] = auth()->user()->id;
        $data['created_at'] = Carbon::now();

        $supplier = Supplier::create($data);

        if ($supplier) {
            $response = [
                'supplier_id' => $supplier->id,
                'type' => 'success',
                'status' => 200,
                'message' => 'Supplier added successfully',
            ];
        } else {
            $response = [
                'type' => 'fail',
                'status' => 500,
                'message' => 'Something went wrong',
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $supplier = Supplier::findOrfail($id);
        $contacts = SupplierContact::where('supplier_id', $id)->get()->toArray();
        $products = SupplierProduct::leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
            ->select('amazon_products.sku', 'amazon_products.asin', 'amazon_products.title',
                'amazon_products.main_image', 'supplier_products.id', 'supplier_products.supplier_sku',
                'supplier_products.unit_price', 'supplier_products.additional_cost')
            ->where('supplier_products.supplier_id', $id)->get()->toArray();
        return view('suppliers.view', compact('supplier', 'contacts', 'products'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $supplier = Supplier::findOrfail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, string $id)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        $data['updated_at'] = Carbon::now();

        Supplier::where('id', $id)->update($data);

        return [
            'type' => 'success',
            'supplier_id' => $id,
            'status' => 200,
            'message' => 'Supplier updated successfully',
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Supplier::find($id)->delete();
        
        SupplierContact::where('supplier_id', $id)->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json([
            'type' => 'success',
            'status' => 200,
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Update the specified Spplier's status.
     */
    public function updateStatus(Request $request)
    {
        try {
            if ($request->id) {
                $updateArr = [
                    'status' => $request->status == '1' ? '0' : '1',
                ];

                Supplier::where('id', $request->id)->update($updateArr);

                return response()->json([
                    'type' => 'success',
                    'status' => 200,
                    'message' => 'Status Updated Successfully',
                ]);
            } else {
                return [
                    'type' => 'fail',
                    'status' => 500,
                    'message' => 'Supplier not found',
                ];
            }
        } catch (\Exception $ex) {
            return [
                'type' => 'fail',
                'status' => 400,
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function columnVisibility(Request $request, SuppliersDataTable $dataTable)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.suppliers'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }

    }

    public function updateSupplierProducts(UpdateSupplierProductRequest $request, $supplierProductId)
    {
        try{ 
            if(null !== $request->input('bulk_supplier_products'))
            {
                if($request->validated())
                {
                    foreach($request->input('supplier_sku') as $key => $value)
                    {
                        SupplierProduct::where('id', $key)->update([
                            'supplier_sku' => $value,
                            'unit_price' => $request->input('unit_price')[$key],
                            'default_supplier' => (isset($request->input('default_supplier')[$key])) ? 1 : 0
                        ]);
                    }
                }
            }else{
                $updateData = [];
                if(!empty($request->all()))
                {
                    foreach($request->all() as $key => $value)
                    {
                        $updateData[$key] = $value;
                    }
                }

                SupplierProduct::where('id', $supplierProductId)->update($updateData);
            }
            return $this->sendResponse("Default supplier details updated successfully.", 200);
        } catch(\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
