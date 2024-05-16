<?php

namespace App\Http\Controllers;

use App\DataTables\FBAProductsDataTable;
use App\Models\AmazonProduct;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\CommonService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\ShipmentProduct;
use App\Models\SupplierProduct;
use App\Models\Warehouse;
use App\Models\Setting;

class FBAProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FBAProductsDataTable $dataTable)
    {
        $suppliers = Supplier::where('status', '1')->whereNULL('deleted_at')->pluck('name', 'id')->toArray();
        $listingCols = $dataTable->listingColumns();
        $stores = Store::active()->pluck('store_name', 'id');

        return $dataTable->render('fba_products.list', compact('listingCols', 'suppliers','stores'));
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.fba_products'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $selectedProductsReq = $request->selectedProducts;
        $selectedProducts = Crypt::encrypt($selectedProductsReq);
        $storeFilter = $request->storeFilter;

        if (!empty($selectedProducts)) {
            $serializedData = json_encode($selectedProducts);
            return response()->json(['status' => true,
                'route' => route('fba_products.selected_products', ['selectedProducts' => $serializedData, 'storeFilter' => $storeFilter]),
            ]);
        }
    }

    public function selectedProducts($processedData, $filter)
    {
        $dataArray = Crypt::decrypt($processedData);
        // $dataArray = json_decode($processedDataRes, true);
        $fbaProductsData = AmazonProduct::whereIn('amazon_products.id', $dataArray)
            ->leftJoin('purchase_order_items', 'amazon_products.id', '=', 'purchase_order_items.product_id')
            ->select('amazon_products.id', 'amazon_products.title', 'amazon_products.asin', 'amazon_products.sku', 'amazon_products.qty', 'amazon_products.afn_reserved_quantity', 'amazon_products.product_note',
                'amazon_products.afn_unsellable_quantity', 'amazon_products.afn_inbound_working_quantity', 'amazon_products.afn_inbound_shipped_quantity',
                'amazon_products.afn_inbound_receiving_quantity', 'amazon_products.main_image', 'pack_of', 'amazon_products.case_pack', 'amazon_products.is_hazmat', 'amazon_products.is_oversize',
                'amazon_products.wh_qty','amazon_products.reserved_qty','amazon_products.sellable_units')
            ->where('amazon_products.store_id', $filter)
            ->orderBy('amazon_products.id', 'desc')
            ->groupBy('amazon_products.id')
            ->get();

        $stores = Store::where('id', $filter)->pluck('store_name', 'id');
        $wareHouses = Warehouse::latest()->get();
        $prepPreferences = config('constants.prep_preference');
        $marketplaces = [
            "CA" => 'CA',
        ];
        $packingDetails = config('constants.packing_details');
        $boxContents = config('constants.box_content');

        $totalHazmat = 0;
        $totalHazmatQty = 0;
        $totalOversize = 0;
        $totalOversizeQty = 0;
        if (!empty($fbaProductsData)) {
            foreach ($fbaProductsData as $key => $value) {
                if ($value->is_hazmat == 1) {
                    $totalHazmat++;
                    $totalHazmatQty += $value->qty;
                }
                if ($value->is_oversize == 1) {
                    $totalOversize++;
                    $totalOversizeQty += $value->qty;
                }
            }

            $commonService = new CommonService();
            $setting = Setting::first();
            $fbaProductsData = $fbaProductsData->map(function($item) use($commonService, $setting){
                $item->total_fba_qty = $commonService->totalFBAQty([$item->qty, $item->afn_inbound_working_quantity, $item->afn_inbound_shipped_quantity, $item->afn_inbound_receiving_quantity, $item->afn_reserved_quantity]);
                $item->suggested_shipment_qty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $item->salesVelocity->ros_30, $item->total_fba_qty]);
                return $item;
            });

            return view('fba_products.create', compact('fbaProductsData', 'stores', 'wareHouses', 'prepPreferences', 'marketplaces', 'packingDetails', 'boxContents', 'totalHazmat', 'totalOversize', 'totalHazmatQty', 'totalOversizeQty','setting'));
        } else {
            return redirect()->back()->with('error', 'No products found');
        }
    }

    public function selectAllChecked(Request $request)
    {
        $this->parentTable = (new AmazonProduct())->getTable();
        $this->childTable = (new ShipmentProduct())->getTable();
        $this->supplierProductTable = (new SupplierProduct())->getTable();

        $products = AmazonProduct::leftJoin("$this->childTable", "$this->childTable.amazon_product_id", "=", "$this->parentTable.id")
            ->leftJoin("$this->supplierProductTable", "$this->supplierProductTable.product_id", "=", "$this->parentTable.id")
            ->leftJoin('suppliers', "$this->supplierProductTable.supplier_id", '=', 'suppliers.id')
            ->select("$this->parentTable.id");

        if (!empty($request->search)) {
            $products->where(function ($query) use ($request) {
                $query->where("$this->parentTable.title", 'like', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.asin", 'like', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.sku", 'like', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.fnsku", 'like', '%' . $request->search . '%');
                $query->orWhere("suppliers.name", 'LIKE', '%' . $request->search . '%');
            });
        }

        if (!empty($request->sku_filter)) {
            $sku = $request->sku_filter;
            $products->where("$this->parentTable.sku", 'LIKE', '%' . $sku . '%');
        }
        if (!empty($request->asin_filter)) {
            $asin = $request->asin_filter;
            $products->where("$this->parentTable.asin", 'LIKE', '%' . $asin . '%');
        }
        if (!empty($request->fnsku_filter)) {
            $fnsku = $request->fnsku_filter;
            $products->where("$this->parentTable.fnsku", 'LIKE', '%' . $fnsku . '%');
        }

        if (!empty($request->supplier_filter)) {
            $supplierIds = explode(',', $request->supplier_filter);
            $products->whereIn("$this->supplierProductTable.supplier_id", $supplierIds);
        }

        if(empty($request->bulk_option)){
            $products->where("$this->parentTable.store_id", '1');
        }else{
            $products->where("$this->parentTable.store_id", $request->bulk_option);
        }

        $products->where("$this->parentTable.sellable_units", '>', 0);

        $products->where("$this->parentTable.if_fulfilled_by_amazon", 1);

        $products->groupBy(DB::Raw("$this->parentTable.id"));

        return response()->json(['status' => true, 'products' => $products->get()->pluck('id')->toArray()]);

    }
}
