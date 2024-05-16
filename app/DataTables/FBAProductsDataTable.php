<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\AmazonProduct;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\ShipmentProduct;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class FBAProductsDataTable extends DataTable
{
    private $getListingFields = [];
    private $parentTable;
    private $childTable;
    private $poItemTable;
    private $supplierProductTable;

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.fba_products'));
        $this->parentTable = (new AmazonProduct())->getTable();
        $this->childTable = (new ShipmentProduct())->getTable();
        $this->poItemTable = (new PurchaseOrderItem())->getTable();
        $this->supplierProductTable = (new SupplierProduct())->getTable();
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $setting = Setting::select('day_stock_holdings','supplier_lead_time')->first();
        $commonService = new CommonService();
        return DataTables::of($query)
        // ->setTotalRecords($query->count("$this->parentTable.id"))
            ->addIndexColumn()
            ->setRowId(function ($value) {
                return $value->id;
            })
            ->editColumn('checkbox', function ($value) {
                return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-4px d-flex-inline select_row_btn" value="' . $value->id . '" role="button">';
            })
            ->editColumn('sku', function ($value) {
                return view('copy-btn', [
                    'value' => $value->sku,
                    'title' => 'SKU',
                ]) . view('copy-btn', [
                    'value' => $value->asin,
                    'title' => 'ASIN',
                    'link' => "https://www.amazon.ca/dp/$value->asin",
                ]). view('copy-btn', [
                    'value' => empty($value->fnsku) ? null : $value->fnsku,
                    'title' => 'FNSKU',
                    'param' => 'fnsku',
                ]);
            })
            ->editColumn('title', function ($value) {
                if($value->store_id == 1){
                    $class = 'badge-info';
                }else{
                    $class = 'badge-success';
                }
                return $value->title."&emsp;<span class='badge ".$class."'  title='Store'>".$value->store_name."</span>";
            })
            ->editColumn('fba', function ($value) {
                return view('inventory-detail', compact('value'));

            })
            ->editColumn('pack_of', function ($value) {
                return $value->pack_of;
            })
            ->editColumn('sellable_units', function ($value) {
                return empty($value->sellable_units) ? 0 : $value->sellable_units;
            })
            ->editColumn('wh_qty', function ($value) {
                return empty($value->wh_qty) ? 0 : $value->wh_qty;
            })
            ->editColumn('suggested_shipment_qty', function ($value) use($commonService, $setting){
                $totalFBAQty = $commonService->totalFBAQty([$value->qty, $value->afn_inbound_working_quantity, $value->afn_inbound_shipped_quantity, $value->afn_inbound_receiving_quantity, $value->afn_reserved_quantity]);
                $suggestedShipmentQty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $value->ros_30, $totalFBAQty]);
                $ros_30 = $value->ros_30;
                return view('fba_products.suggested_shipment_qty', compact(['suggestedShipmentQty','ros_30','setting','totalFBAQty']));
            })
            ->editColumn('main_image', function ($value) {
                return (!empty($value->main_image)) ? '<a href="' . $value->main_image . '" target="_blank"><img src="' . $value->main_image . '" width="50" height="50"></a>' : '-';
            })
            ->rawColumns(['checkbox', 'sku', 'title', 'main_image', 'fba', 'pack_of', 'sellable_units', 'wh_qty']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(AmazonProduct $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->select("$this->parentTable.id", "$this->parentTable.qty", "$this->parentTable.afn_reserved_quantity", "$this->parentTable.afn_unsellable_quantity",
            "$this->parentTable.afn_inbound_working_quantity", "$this->parentTable.afn_inbound_shipped_quantity", "$this->parentTable.afn_inbound_receiving_quantity",
            "$this->parentTable.asin", "$this->parentTable.pack_of", "$this->parentTable.inbound_shipping_cost", "$this->parentTable.sku", "$this->parentTable.title", "$this->parentTable.main_image",
            "$this->parentTable.if_fulfilled_by_amazon", "$this->parentTable.sellable_units", "$this->parentTable.is_active",
            "$this->parentTable.wh_qty", "sales_velocities.ros_30",
            // DB::raw("SUM($this->supplierProductTable.suggested_quantity) as fba_suggested_quantity"),
            "$this->parentTable.fnsku","stores.store_name","stores.id  as store_id");

        $model->leftJoin("$this->childTable", "$this->childTable.amazon_product_id", "=", "$this->parentTable.id");
        $model->leftJoin("$this->poItemTable", "$this->poItemTable.product_id", "=", "$this->parentTable.id");
        $model->leftJoin("$this->supplierProductTable", "$this->supplierProductTable.product_id", "=", "$this->parentTable.id");
        $model->leftJoin('suppliers', "$this->supplierProductTable.supplier_id", '=', 'suppliers.id');
        $model->leftJoin('sales_velocities', "$this->parentTable.id", '=', 'sales_velocities.amazon_product_id');
        $model->leftJoin('stores', "$this->parentTable.store_id", '=', 'stores.id');

        $sortColumn = $request->input('order.0.column');
        if (is_null($sortColumn)) {
            $model->orderBy("$this->parentTable.title", 'ASC');
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where("$this->parentTable.title", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.sku", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.asin", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.fnsku", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("suppliers.name", 'LIKE', '%' . $request->search . '%');
            });
        }

        if (!empty($request->sku_filter)) {
            $sku = $request->sku_filter;
            $model->where("$this->parentTable.sku", 'LIKE', '%' . $sku . '%');
        }
        if (!empty($request->asin_filter)) {
            $asin = $request->asin_filter;
            $model->where("$this->parentTable.asin", 'LIKE', '%' . $asin . '%');
        }
        if (!empty($request->fnsku_filter)) {
            $fnsku = $request->fnsku_filter;
            $model->where("$this->parentTable.fnsku", 'LIKE', '%' . $fnsku . '%');
        }


        if (!empty($request->supplier_filter)) {
            $supplierIds = explode(',', $request->supplier_filter);
            $model->whereIn("$this->supplierProductTable.supplier_id", $supplierIds);
        }

        if(empty($request->bulk_option)){
            $model->where("$this->parentTable.store_id", '1');
        }else{
            $model->where("$this->parentTable.store_id", $request->bulk_option);
        }

        $model->where("$this->parentTable.sellable_units", '>', 0);

        $model->where("$this->parentTable.if_fulfilled_by_amazon", 1);

        $model->where("$this->parentTable.is_active",'!=',0);

        $model->groupBy(DB::Raw("$this->parentTable.id"));

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->ajax([
                'data' => 'function(d) {
                    d.search = $.trim($("#search").val());
                    d.sku_filter = $.trim($("#sku_filter").val());
                    d.asin_filter = $.trim($("#asin_filter").val());
                    d.fnsku_filter = $.trim($("#fnsku_filter").val());
                    d.supplier_filter = $.trim($("#supplier_filter").val());
                    d.bulk_option = $.trim($("#bulk_option").val());

                }',
                'beforeSend' => 'function() {
                    show_loader();
                }',
                'complete' => 'function() {
                   hide_loader();
                }',
                'error' => 'function (xhr, err) { console.log(err);}',
            ])
            ->columns($this->getColumns($this->getListingFields))
            ->parameters($this->getBuilderParameters($this->getListingFields));
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns($cols): array
    {
        $getListingColumns = $this->listingColumns();
        $getColsWithTableName = $this->ColsWithTableName();

        if (!empty($cols)) {
            $columnList = [];

            foreach ($cols as $value) {

                array_push($columnList, [
                    'data' => $value, 'name' => ($value == 'suggested_shipment_qty') ? '' : $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => ($value == 'main_image' || $value == 'suggested_shipment_qty') ? false : true, 
                    "className" =>  ($value == 'wh_qty') || ($value == 'pack_of') || ($value == 'sellable_units') ? 'text-center' : (($value == 'sku') ? 'text-nowrap' : (($value == 'title') ? 'text-left' : (($value == 'fba') ? 'text-left' : 'text-center')))
                ]);
            }
            return [
                ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" title="Select All" class="form-check form-check-input w-4px d-flex-inline select_all_btn" role="button">', 'orderable' => false],
                ...$columnList,
            ];
        } else {

            return [
                ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" title="Select All" class="form-check form-check-input w-4px d-flex-inline select_all_btn" role="button">', 'orderable' => false],
                ['data' => 'main_image', 'name' => $getColsWithTableName['main_image'], 'title' => $getListingColumns['main_image']],
                ['data' => 'title', 'name' => $getColsWithTableName['title'], 'title' => $getListingColumns['title']],
                ['data' => 'sku', 'name' => $getColsWithTableName['sku'], 'title' => $getListingColumns['sku'], 'orderable' => true,"className" => 'text-nowrap'],
                ['data' => 'fba', 'name' => $getColsWithTableName['fba'], 'title' => 'FBA Qty', "visible" => true],
                ['data' => 'suggested_shipment_qty', 'title' => 'Suggested Shipment Qty', 'orderable' => false, 'className' => 'suggested_shipment_qty text-center'],
                ['data' => 'wh_qty', 'name' => $getColsWithTableName['wh_qty'], 'title' => $getListingColumns['wh_qty'], 'orderable' => true, "className" => 'text-center'],
                ['data' => 'pack_of', 'name' => $getColsWithTableName['pack_of'], 'title' => $getListingColumns['pack_of'], 'orderable' => true, "className" => 'text-center'],
                ['data' => 'sellable_units', 'name' => $getColsWithTableName['sellable_units'], 'title' => $getListingColumns['sellable_units'], 'orderable' => true, "className" => 'text-center'],
                // ['data' => 'fba_suggested_quantity', 'name' => $getColsWithTableName['fba_suggested_quantity'], 'title' => $getListingColumns['fba_suggested_quantity'], 'orderable' => true, "className" => 'text-center'],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();

        if (!empty($cols)) {
            $count = count($cols);
            if ($count == 1) {
                $rowCount = 0;
            } else {
                $rowCount = $count - 1;
            }
            $colCount = array_map(fn($i) => $i, range(0, $rowCount));
            $targets = [...$colCount];

        } else {
            $targets = [0, 1, 2, 3, 4, 5, 6, 7];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
                // "className" => 'text-nowrap',
            ],
        ];

        $data['pageLength'] = 100;

        return $data;
    }

    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'main_image' => 'Image',
            'title' => 'Title',
            'sku' => 'SKU / ASIN / FNSKU',
            'fba' => 'FBA Qty',
            'suggested_shipment_qty' => 'Suggested Shipment Qty',
            'wh_qty' => 'WH Qty',
            'pack_of' => 'Pack of',
            'sellable_units' => 'Sellable Units',
            // 'fba_suggested_quantity' => 'Suggested Units',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        return [
            'main_image' => "$this->parentTable.main_image",
            'title' => "$this->parentTable.title",
            'sku' => "$this->parentTable.sku",
            'asin' => "$this->parentTable.asin",
            'fba' => "$this->parentTable.qty",
            'wh_qty' => "$this->parentTable.wh_qty",
            'pack_of' => "$this->parentTable.pack_of",
            'sellable_units' => "$this->parentTable.sellable_units",
            // 'fba_suggested_quantity' => "$this->supplierProductTable.suggested_quantity",
        ];
    }
}
