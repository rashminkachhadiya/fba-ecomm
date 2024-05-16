<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\AmazonProduct;
use App\Models\Setting;
use App\Models\Store;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class ProductDataTable extends DataTable
{
    private $getListingFields = [];
    private $parentTable;
    private $childTable;

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.products'));
        $this->parentTable = (new AmazonProduct())->getTable();
        $this->childTable = (new Store())->getTable();
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $commonService = new CommonService();
        $setting = Setting::first();
        return DataTables::of($query)
            ->setTotalRecords($query->count("$this->parentTable.id"))
            ->addIndexColumn()
            ->addColumn('checkbox', function ($value) {
                return '<input type="checkbox" title="Select this row" name="product_select[]" class="form-check form-check-input w-4px d-flex-inline select_row_btn select_checkbox" value="' . $value->id . '" role="button">';
            })
            ->editColumn('sku', function ($value) {
                return view('copy-btn', [
                    'value' => $value->sku,
                    'title' => 'SKU',
                ]) . view('copy-btn', [
                    'value' => $value->asin,
                    'title' => 'ASIN',
                    'link' => "https://www.amazon.ca/dp/$value->asin"
                ]);
            })
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('store_name', function ($value) {
                return $value->store_name;
            })
            ->editColumn('price', function ($value) {
                return config('constants.currency_symbol') . $value->price;
            })
            ->editColumn('main_image', function ($value) {
                return (!empty($value->main_image)) ? '<a href="' . $value->main_image . '" target="_blank"><img src="' . $value->main_image . '" width="75" height="75"></a>' : '-';
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('is_active', function ($value) {
                $title = "";
                if ($value->is_active == 1) {
                    $title = 'Active';
                } elseif ($value->is_active == 0) {
                    $title = 'Inactive';
                } else {
                    $title = 'Incomplete';
                }
                $badgeArr = [
                    'title' => $title,
                    'status' => $value->is_active
                ];
                if ($value->is_active == 2) {
                    unset($badgeArr['status']);
                    $badgeArr['incomplete'] = $value->is_active;
                }
                return view('badge', compact('badgeArr'));
            })
            ->editColumn('wh_qty', function ($value) {
                return (!empty($value->wh_qty)) ? $value->wh_qty : 0;
            })
            ->editColumn('if_fulfilled_by_amazon', function ($value) {
                return ($value->if_fulfilled_by_amazon == 1) ? 'FBA' : 'FBM';
            })
            ->addColumn('fba', function ($value) {
                return view('inventory-detail', compact('value'));
            })
            ->editColumn('is_hazmat', function ($value) {
                $badgeArr = [
                    'title' => $value->is_hazmat == 1 ? 'Yes' : 'No',
                    'status' => $value->is_hazmat
                ];
                return view('badge', compact('badgeArr'));
            })
            ->editColumn('is_oversize', function ($value) {
                $badgeArr = [
                    'title' => $value->is_oversize == 1 ? 'Yes' : 'No',
                    'status' => $value->is_oversize
                ];
                return view('badge', compact('badgeArr'));
            })
            ->addColumn('supplier', function (AmazonProduct $amazonProducts) {
                $supplier = '-';
                if ($amazonProducts->supplier_products->count() > 0) {
                    $supplier = '<a href="javascript:void(0);" onclick="showSuppliers(' . $amazonProducts->id . ')">' . $amazonProducts->supplier_products->first()->supplier->name . '</a>';
                }
                return $supplier;
            })
            ->addColumn('supplier_sku', function (AmazonProduct $amazonProducts) {
                $supplierSku = '-';
                $supplierProductId = 0;
                $disabled = 'disabled';
                if($amazonProducts->supplier_products->count() > 0)
                {
                    $supplierProductId = $amazonProducts->supplier_products->first()->id;
                    $supplierSku = $amazonProducts->supplier_products->first()->supplier_sku;
                    $disabled = '';
                }
                return "<input type='text' class='form-control default_supplier_sku' data-supplier-product='" . $supplierProductId . "' value='" . $supplierSku . "' $disabled />";;
            })
            ->addColumn('supplier_price', function (AmazonProduct $amazonProducts) {
                $price = 0;
                $supplierProductId = 0;
                $disabled = 'disabled';
                if ($amazonProducts->supplier_products->count() > 0) {
                    $price = $amazonProducts->supplier_products->first()->unit_price;
                    $supplierProductId = $amazonProducts->supplier_products->first()->id;
                    $disabled = '';
                }
                return "<input type='number' class='form-control default_supplier_price' data-supplier-product='" . $supplierProductId . "' value='" . $price . "' $disabled />";
            })
            ->editColumn('product_note', function ($value) {
                return '<textarea class="form-control product_note" cols="20" data-product="' . $value->id . '">' . $value->product_note . '</textarea>';
            })
            ->addColumn('case_pack_info', function ($value) {
                return view('products.case_pack_info', compact('value'));
            })
            ->addColumn('buy_box_price', function ($value) {
                return view('products.buy_box_price', compact('value'));
            })
            ->addColumn('action', function ($value) {
                return view('products.action_button', compact('value'));
            })
            ->editColumn('suggested_shipment_qty', function ($value) use ($commonService, $setting) {
                $totalFBAQty = $commonService->totalFBAQty([$value->qty, $value->afn_inbound_working_quantity, $value->afn_inbound_shipped_quantity, $value->afn_inbound_receiving_quantity, $value->afn_reserved_quantity]);
                $suggestedShipmentQty =  $commonService->calculteSuggestedShipmentQty([[$setting->day_stock_holdings, $setting->supplier_lead_time], $value->ros_30, $totalFBAQty]);
                $ros_30 = $value->ros_30;
                return view('fba_products.suggested_shipment_qty', compact(['suggestedShipmentQty', 'ros_30', 'setting', 'totalFBAQty']));
            })
            ->rawColumns(['checkbox', 'sku', 'title', 'store_name', 'action', 'created_at', 'main_image', 'is_active', 'price', 'if_fulfilled_by_amazon', 'fba', 'is_oversize', 'is_hazmat', 'supplier', 'case_pack_info', 'buybox_price', 'wh_qty', 'supplier_price', 'supplier_sku', 'product_note']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(AmazonProduct $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $selectCols = [
            "$this->parentTable.id", "$this->parentTable.qty", "$this->parentTable.afn_reserved_quantity", "$this->parentTable.afn_unsellable_quantity",
            "$this->parentTable.afn_inbound_working_quantity", "$this->parentTable.afn_inbound_shipped_quantity", "$this->parentTable.afn_inbound_receiving_quantity",
            "$this->parentTable.asin", "$this->parentTable.case_pack", "$this->parentTable.pack_of", "$this->parentTable.inbound_shipping_cost", "$this->parentTable.buybox_price",
            "$this->parentTable.is_buybox_fba", "$this->parentTable.buybox_seller_id", "$this->parentTable.wh_qty", "$this->parentTable.product_note",
            'sales_velocities.ros_30'
        ];

        $model->leftJoin("$this->childTable", "$this->parentTable.store_id", "=", "$this->childTable.id")
            ->leftJoin("sales_velocities", "sales_velocities.amazon_product_id", "=", "$this->parentTable.id");;

        // if(!empty($this->getListingFields))
        // {
        //     if(in_array('suggested_shipment_qty', $this->getListingFields))
        //     {
        //         unset($this->getListingFields[array_search('suggested_shipment_qty', $this->getListingFields)]);
        //     }
        //     $model->select(...$selectCols,...($this->getListingFields));
        // }else{
        $model->select(
            "$this->parentTable.sku",
            "$this->parentTable.title",
            "$this->parentTable.main_image",
            "$this->parentTable.price",
            "$this->parentTable.is_active",
            "$this->parentTable.created_at",
            "$this->parentTable.if_fulfilled_by_amazon",
            "$this->parentTable.is_hazmat",
            "$this->parentTable.is_oversize",
            "stores.store_name",
            ...$selectCols
        );
        // }

        $model->with(['supplier_products' => function ($query) {
            return $query->defaultSupplier()->select('unit_price', 'product_id', 'supplier_id', 'id','supplier_sku');
        }]);

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where("$this->parentTable.title", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.sku", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->parentTable.asin", 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->status == "1") {
            $model->where('is_active', $request->status);
        } elseif ($request->status == "0") {
            $model->where('is_active', "0");
        }

        if ($request->hazmat == "1") {
            $model->where('is_hazmat', $request->hazmat);
        } elseif ($request->hazmat == "0") {
            $model->where('is_hazmat', $request->hazmat);
        }

        if ($request->oversize == "1") {
            $model->where('is_oversize', $request->oversize);
        } elseif ($request->oversize == "0") {
            $model->where('is_oversize', $request->oversize);
        }

        $model->when(request('store'), function ($query) {
            return $query->where("$this->childTable.id", request('store'));
        });

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
                    d.status = $.trim($("#status").val());
                    d.hazmat = $.trim($("#hazmat").val());
                    d.oversize = $.trim($("#oversize").val());
                    d.store = $.trim($("#store").val());
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
        $getColumns = $this->allColumns();
        $columnDetail = array_values($getColumns);

        if (!empty($cols)) {
            $columns = [];
            $defaultColumns = $this->defaultColumns();
            foreach ($getColumns as $key => $value) {
                if (in_array($key, $cols) || in_array($key, $defaultColumns)) {
                    $columns[] = $value;
                }
            }
            return [...$columns];
        } else {
            return [...$columnDetail];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();

        if (!empty($cols)) {
            $count = count($cols) + count($this->defaultColumns());
            $colCount = array_map(fn ($i) => $i, range(0, $count - 1));
            $targets = [...$colCount];
        } else {
            $colCount = array_map(fn ($i) => $i, range(0, (count($this->allColumns()) - 1)));
            $targets = [...$colCount];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
                // "className" => 'text-nowrap',
            ]
        ];

        return $data;
    }

    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'main_image' => 'Image',
            'title' => 'Title',
            'store_name' => 'Store',
            'sku' => 'SKU / ASIN',
            'price' => 'Listing Price',
            'suggested_shipment_qty' => 'Suggested Shipment Qty',
            'if_fulfilled_by_amazon' => 'Fulfillment Type',
            'is_active' => 'Status',
            'is_hazmat' => 'Hazmat',
            'is_oversize' => 'Oversize',
            'supplier' => 'Supplier',
            'supplier_sku' => 'Supplier SKU',
            'supplier_price' => 'Unit Price',
            'product_note' => 'Notes',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        return [
            'main_image' => "$this->parentTable.main_image",
            'title' => "$this->parentTable.title",
            'store_name' => "$this->childTable.store_name",
            'sku' => "$this->parentTable.sku",
            'asin' => "$this->parentTable.asin",
            'price' => "$this->parentTable.price",
            'if_fulfilled_by_amazon' => "$this->parentTable.if_fulfilled_by_amazon",
            'is_active' => "$this->parentTable.is_active",
            'is_hazmat' => "$this->parentTable.is_hazmat",
            'is_oversize' => "$this->parentTable.is_oversize",
        ];
    }

    // All fields list
    public function allColumns(): array
    {
        $getListingColumns = $this->listingColumns();
        $getColsWithTableName = $this->ColsWithTableName();

        return [
            'checkbox' => ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" title="Select All" class="form-check form-check-input w-4px d-flex-inline select_all_btn" role="button">', 'orderable' => false],
            'main_image' => ['data' => 'main_image', 'name' => $getColsWithTableName['main_image'], 'title' => $getListingColumns['main_image']],
            'title' => [
                'data' => 'title',
                'title' => $getListingColumns['title'],
                'name' => $getColsWithTableName['title'],
                'orderable' => true,
                'width' => '100px',
                'render' => function () {
                    return <<<JS
                            function(data, type, row){
                            if (type === 'display') {
                                // Truncate the title to a desired length

                                var truncatedTitle = data.length > 50 ? data.substr(0, 50) + '...' : data;
                                
                                // Add a tooltip with the full title
                                var tooltip = data.length > 50 ? 'title=' + '"' + data + '"' : '';
                                
                                return '<span ' + tooltip + '>' + truncatedTitle + '</span>';
                            }
                            return data;
                        }
                        JS;
                },
            ],
            'store_name' => ['data' => 'store_name', 'name' => $getColsWithTableName['main_image'], 'title' => $getListingColumns['store_name'], 'orderable' => true, 'searchable' => true],
            'sku' => ['data' => 'sku', 'name' => $getColsWithTableName['sku'], 'title' => $getListingColumns['sku'], 'orderable' => true, "className" => 'text-nowrap'],
            'price' => ['data' => 'price', 'name' => $getColsWithTableName['price'], 'title' => $getListingColumns['price'], 'orderable' => true],
            'wh_qty' => ['data' => 'wh_qty', 'title' => 'WH Qty', 'className' => 'text-nowrap'],
            'fba' => ['data' => 'fba', 'title' => 'FBA', 'className' => 'text-nowrap'],
            'suggested_shipment_qty' => ['data' => 'suggested_shipment_qty', 'title' => 'Suggested Shipment Qty', 'className' => 'text-center suggested_shipment_qty'],
            'if_fulfilled_by_amazon' => ['data' => 'if_fulfilled_by_amazon', 'title' => $getListingColumns['if_fulfilled_by_amazon']],
            'is_active' => ['data' => 'is_active', 'name' => $getColsWithTableName['is_active'], 'title' => $getListingColumns['is_active'], 'orderable' => true, 'className' => 'text-nowrap'],
            'is_hazmat' => ['data' => 'is_hazmat', 'name' => $getColsWithTableName['is_hazmat'], 'title' => $getListingColumns['is_hazmat'], 'orderable' => true, 'className' => 'text-nowrap'],
            'is_oversize' => ['data' => 'is_oversize', 'name' => $getColsWithTableName['is_oversize'], 'title' => $getListingColumns['is_oversize'], 'orderable' => true, 'className' => 'text-nowrap'],
            'supplier' => ['data' => 'supplier', 'title' => 'Supplier'],
            'supplier_sku' => ['data' => 'supplier_sku', 'title' => 'Supplier SKU'],
            'supplier_price' => ['data' => 'supplier_price', 'title' => 'Unit Price'],
            'product_note' => ['data' => 'product_note', 'title' => 'Notes', "className" => 'min-w-180px'],
            'case_pack_info' => ['data' => 'case_pack_info', 'title' => 'Case Pack Detail', 'className' => 'text-nowrap'],
            'buy_box_price' => ['data' => 'buy_box_price', 'title' => 'Buy Box Price', 'className' => 'text-nowrap'],
            'action' => ['data' => 'action', 'title' => 'Action', 'className' => 'text-center', 'orderable' => false]
        ];
    }

    public function defaultColumns(): array
    {
        return [
            'checkbox','wh_qty', 'fba', 'case_pack_info', 'buy_box_price', 'action'
        ];
    }
}
