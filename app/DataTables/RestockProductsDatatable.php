<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Http\Requests\RestockProductFilterRequest;
use App\Models\AmazonProduct;
use App\Models\SalesVelocity;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class RestockProductsDatatable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.restock_products'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            ->setTotalRecords($query->count('supplier_products.id'))
            ->addIndexColumn()
            ->setRowId(function ($value) {
                return $value->supplier_product_id;
            })
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('sku_asin', function ($value) {
                return view('copy-btn', [
                    'value' => $value->sku,
                    'title' => 'SKU',
                ]) . view('copy-btn', [
                    'value' => $value->asin,
                    'title' => 'ASIN',
                    'link' => "https://www.amazon.ca/dp/$value->asin",
                ]);
            })
            ->editColumn('main_image', function ($value) {
                return '<img src="' . $value->main_image . '" width="50" height="50">';
            })
            ->editColumn('total_order', function ($value) {
                return '<p><span class="fw-700">2D:</span>' . (empty($value->total_units_sold_2) ? 0 : $value->total_units_sold_2) . ' | ' . (!empty($value->ros_2) ? '  <span class="fw-700">ROS</span>(' . $value->ros_2 . ')' : '<span class="fw-700">ROS</span>(0)') . '</p>' .
                    '<p><span class="fw-700">7D:</span>' . (empty($value->total_units_sold_7) ? 0 : $value->total_units_sold_7) . ' | ' . (!empty($value->ros_7) ? '  <span class="fw-700">ROS</span>(' . $value->ros_7 . ')' : '<span class="fw-700">ROS</span>(0)') . '</p>' .
                    '<p><span class="fw-700">30D:</span>' . (empty($value->total_units_sold_30) ? 0 : $value->total_units_sold_30) . ' | ' . (!empty($value->ros_30) ? '  <span class="fw-700">ROS</span>(' . $value->ros_30 . ')' : '<span class="fw-700">ROS</span>(0)') . '</p>';
            })
            ->editColumn('qty', function ($value) {
                return view('inventory-detail', compact('value'));
            })
            ->editColumn('suggested_quantity', function ($value) {
                $flag = '';
                $totalFbaQty = $value->qty + ($value->afn_inbound_working_quantity + $value->afn_inbound_shipped_quantity + $value->afn_inbound_receiving_quantity) + $value->afn_reserved_quantity;

                if ($value->threshold_qty > $totalFbaQty) {
                $flag = '<i class="ms-3 fa-solid fa-flag" style="color: #ff0000;" title="((30D ROS * Supplier Lead Time) > FBA Qty)"></i>';
                }
                return view('restocks.flag_details', compact(['flag', 'value']));
            })
            ->addColumn('case_pack', function ($value) {
                return $value->case_pack;
            })
            ->editColumn('order_qty', function ($value) {
                return ($value->unit_price > 0) ? '<input type="number" step="1" min="0" class="form-control valid_order_qty" onkeyup="onUpdateOrderQty(event);" supplier-product-id="' . $value->supplier_product_id . '" id="order_qty' . '_' . $value->supplier_product_id . '" name="order_qty[]" value="' . $value->order_qty . '" style="width:100px" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"><span class="help-block error-help-block" id="case_pack_error_' . $value->supplier_product_id . '"></span>' : $value->order_qty;
            })
            ->editColumn('supplier_sku', function ($value) {

                return view('restocks.supplier-details',compact('value'));
            })
            ->editColumn('estimated_margin', function ($value) {
                return view('restocks.profit_detail', compact('value'));;
            })
            ->editColumn('is_hazmat', function ($value) {
                $hazmatArr = [
                    'title' => $value->is_hazmat == 1 ? 'Yes' : 'No',
                    'status' => $value->is_hazmat,
                ];
                $oversizeArr = [
                    'title' => $value->is_oversize == 1 ? 'Yes' : 'No',
                    'status' => $value->is_oversize,
                ];
                return view('restocks/hazmat-oversize', compact(['hazmatArr','oversizeArr']));
            })
            ->rawColumns(['title', 'sku_asin', 'case_pack', 'sku', 'asin', 'main_image', 'total_order', 'qty', 'suggested_quantity', 'estimated_margin', 'order_qty', 'supplier_sku', 'is_hazmat', 'is_oversize']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SupplierProduct $model, Request $request): QueryBuilder
    {
        $supplierId = $this->supplier_id;

        $model = $model->newQuery();

        $model->leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
            ->leftJoin('sales_velocities', 'sales_velocities.amazon_product_id', '=', 'amazon_products.id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->select(
                'amazon_products.sku',
                'amazon_products.asin',
                'amazon_products.title',
                'amazon_products.main_image',
                'amazon_products.qty',
                'amazon_products.wh_qty',
                'amazon_products.is_hazmat',
                'amazon_products.is_oversize',
                'amazon_products.buybox_seller_id',
                'amazon_products.is_buybox_fba',
                'amazon_products.afn_reserved_quantity',
                'amazon_products.afn_unsellable_quantity',
                'amazon_products.afn_inbound_working_quantity',
                'amazon_products.afn_inbound_shipped_quantity',
                'amazon_products.afn_inbound_receiving_quantity',
                'supplier_products.id as supplier_product_id',
                'supplier_products.product_id',
                'supplier_products.supplier_sku',
                'supplier_products.order_qty',
                'supplier_products.suggested_quantity',
                'supplier_products.unit_price',
                'supplier_products.buybox_price',
                'supplier_products.selling_price',
                'supplier_products.referral_fees',
                'supplier_products.buybox_referral_fees',
                'supplier_products.fba_fees',
                'supplier_products.buybox_price_profit',
                'supplier_products.buybox_price_margin',
                'supplier_products.selling_price_profit',
                'supplier_products.selling_price_margin',
                'sales_velocities.total_units_sold_2',
                'sales_velocities.total_units_sold_7',
                'sales_velocities.total_units_sold_30',
                'sales_velocities.ros_2',
                'sales_velocities.ros_7',
                'sales_velocities.ros_30',
                'supplier_products.threshold_qty',
                'amazon_products.case_pack',
                'suppliers.name',
                'suppliers.lead_time',
            );

        $model->where('supplier_products.supplier_id', $supplierId);

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->orWhere('title', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('asin', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('suppliers.name', 'LIKE', '%' . $request->search . '%');
            });
        }

        $model->when((request('is_hazmat') || request('is_hazmat') == '0'), function ($query) {
            $query->where('amazon_products.is_hazmat', request('is_hazmat'));
        })->when((request('is_oversize') || request('is_oversize') == '0'), function ($query) {
            $query->where('amazon_products.is_oversize', request('is_oversize'));
        })->when((request('min_fba_qty') || request('max_fba_qty')), function ($query) {
            if ((request('min_fba_qty') || request('min_fba_qty') == "0") && (request('max_fba_qty') || request('max_fba_qty') == "0")) {
                $query->whereBetween("amazon_products.qty", [request('min_fba_qty'), request('max_fba_qty')]);
            } else {
                if (request('min_fba_qty')) {
                    $query->where("amazon_products.qty", '>=', request('min_fba_qty'));
                }

                if (request('max_fba_qty')) {
                    $query->where("amazon_products.qty", '<=', request('max_fba_qty'));
                }
            }
        })->when((request('min_inbound_qty') || request('max_inbound_qty')), function($query){
            if((request('min_inbound_qty') || request('min_inbound_qty') == "0") && (request('max_inbound_qty') || request('max_inbound_qty') == "0"))
            {
                // $query->whereBetween('IFNULL("amazon_products.afn_inbound_working_quantity",0) + IFNULL("amazon_products.afn_inbound_shipped_quantity",0)' + 'IFNULL("amazon_products.afn_inbound_receiving_quantity",0)', [request('min_inbound_qty'), request('max_inbound_qty')]);
                // $query->whereBetween($nullCondition."(amazon_products.afn_inbound_working_quantity,0)+IFNULL(amazon_products.afn_inbound_shipped_quantity,0)+IFNULL(amazon_products.afn_inbound_receiving_quantity,0)", [request('min_inbound_qty'), request('max_inbound_qty')]);
                // $query->whereBetween('"amazon_products.afn_inbound_working_quantity"+"amazon_products.afn_inbound_shipped_quantity"+"amazon_products.afn_inbound_receiving_quantity"'
                //                 , [request('min_inbound_qty'), request('max_inbound_qty')]);
                $query->whereRaw('IFNULL(amazon_products.afn_inbound_working_quantity, 0) + IFNULL(amazon_products.afn_inbound_shipped_quantity, 0) + IFNULL(amazon_products.afn_inbound_receiving_quantity, 0) BETWEEN "' . request('min_inbound_qty') . '" AND "' . request('max_inbound_qty') . '"');
            } else {
                if (request('min_inbound_qty')) {
                    $query->whereRaw('IFNULL(amazon_products.afn_inbound_working_quantity, 0) + IFNULL(amazon_products.afn_inbound_shipped_quantity, 0) + IFNULL(amazon_products.afn_inbound_receiving_quantity, 0) >= "' . request('min_inbound_qty') . '"');
                }

                if (request('max_inbound_qty')) {
                    // $query->where("(amazon_products.afn_inbound_working_quantity + amazon_products.afn_inbound_shipped_quantity + amazon_products.afn_inbound_receiving_quantity)", '<=', request('max_inbound_qty'));
                    $query->whereRaw('IFNULL(amazon_products.afn_inbound_working_quantity, 0) + IFNULL(amazon_products.afn_inbound_shipped_quantity, 0) + IFNULL(amazon_products.afn_inbound_receiving_quantity, 0) <= "' . request('max_inbound_qty') . '"');
                }
            }
        })->when((request('min_suggested_qty') || request('max_suggested_qty')), function ($query) {
            if ((request('min_suggested_qty') || request('min_suggested_qty') == "0") && (request('max_suggested_qty') || request('max_suggested_qty') == "0")) {
                $query->whereBetween("supplier_products.suggested_quantity", [request('min_suggested_qty'), request('max_suggested_qty')]);
            } else {
                if (request('min_suggested_qty')) {
                    $query->where("supplier_products.suggested_quantity", '>=', request('min_suggested_qty'));
                }

                if (request('max_suggested_qty')) {
                    $query->where("supplier_products.suggested_quantity", '<=', request('max_suggested_qty'));
                }
            }
        })->when((request('min_price') || request('max_price')), function ($query) {
            if ((request('min_price') || request('min_price') == "0") && (request('max_price') || request('max_price') == "0")) {
                $query->whereBetween("supplier_products.unit_price", [request('min_price'), request('max_price')]);
            } else {
                if (request('min_price')) {
                    $query->where("supplier_products.unit_price", '>=', request('min_price'));
                }

                if (request('max_price')) {
                    $query->where("supplier_products.unit_price", '<=', request('max_price'));
                }
            }
        })->when((request('min_buybox_price') || request('max_buybox_price')), function ($query) {
            if ((request('min_buybox_price') || request('min_buybox_price') == "0") && (request('max_buybox_price') || request('max_buybox_price') == "0")) {
                $query->whereBetween("supplier_products.buybox_price_profit", [request('min_buybox_price'), request('max_buybox_price')]);
            } else {
                if (request('min_buybox_price')) {
                    $query->where("supplier_products.buybox_price_profit", '>=', request('min_buybox_price'));
                }

                if (request('max_buybox_price')) {
                    $query->where("supplier_products.buybox_price_profit", '<=', request('max_buybox_price'));
                }
            }
        })->when((request('min_selling_price') || request('max_selling_price')), function ($query) {
            if ((request('min_selling_price') || request('min_selling_price') == "0") && (request('max_selling_price') || request('max_selling_price') == "0")) {
                $query->whereBetween("supplier_products.selling_price_profit", [request('min_selling_price'), request('max_selling_price')]);
            } else {
                if (request('min_selling_price')) {
                    $query->where("supplier_products.selling_price_profit", '>=', request('min_selling_price'));
                }

                if (request('max_selling_price')) {
                    $query->where("supplier_products.selling_price_profit", '<=', request('max_selling_price'));
                }
            }
        });

        $sortColumn = $request->input('order.0.column');
        $sortDirection = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy('supplier_products.suggested_quantity', 'DESC');
        }

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
                    d.min_fba_qty = $.trim($("#min_fba_qty").val());
                    d.max_fba_qty = $.trim($("#max_fba_qty").val());
                    d.min_inbound_qty = $.trim($("#min_inbound_qty").val());
                    d.max_inbound_qty = $.trim($("#max_inbound_qty").val());
                    d.min_suggested_qty = $.trim($("#min_suggested_qty").val());
                    d.max_suggested_qty = $.trim($("#max_suggested_qty").val());
                    d.min_price = $.trim($("#min_price").val());
                    d.max_price = $.trim($("#max_price").val());
                    d.min_buybox_price = $.trim($("#min_buybox_price").val());
                    d.max_buybox_price = $.trim($("#max_buybox_price").val());
                    d.min_selling_price = $.trim($("#min_selling_price").val());
                    d.max_selling_price = $.trim($("#max_selling_price").val());
                    d.is_hazmat = $.trim($("#is_hazmat").val());
                    d.is_oversize = $.trim($("#is_oversize").val());
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
        if (!empty($cols)) {
            $columnList = [];
            $getListingColumns = $this->listingColumns();
            $getColsWithTableName = $this->ColsWithTableName();

            foreach ($cols as $value) {
                if ($value === 'title') {
                    array_push($columnList, [
                        'data' => $value,
                        'title' => $getListingColumns[$value],
                        'orderable' => true,
                        'class' => ' min-w-180px',
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
                    ]);
                } else {
                    array_push($columnList, [
                        'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value],
                    ]);
                }
            }
            return [
                ...$columnList,
                ['data' => 'qty', 'title' => 'FBA Qty', 'className' => 'text-nowrap'],
            ];
        } else {

            return [
                ['data' => 'main_image', 'name' => 'amazon_products.main_image', 'title' => 'Image', 'orderable' => false, "visible" => true, "className" => ''],
                [
                    'data' => 'title',
                    'name' => 'amazon_products.title',
                    'title' => 'Title',
                    'orderable' => true,
                    'class' => 'min-w-180px',
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
                ['data' => 'sku_asin', 'name' => 'amazon_products.sku', 'title' => 'SKU / ASIN', "visible" => true, "className" => 'text-nowrap'],
                // ['data' => 'sku', 'name' => 'amazon_products.sku', 'title' => 'SKU', "visible" => true, "className" => 'text-nowrap'],
                // ['data' => 'asin', 'name' => 'amazon_products.asin', 'title' => 'ASIN', "visible" => true, "className" => 'text-nowrap'],
                ['data' => 'total_order', 'name' => 'total_order', 'title' => 'Total Orders 2D|7D|30D', "visible" => true, "className" => 'text-nowrap', 'orderable' => false],
                ['data' => 'qty', 'name' => 'amazon_products.qty', 'title' => 'FBA Qty', "visible" => true, "className" => 'min-w-150px'],

                ['data' => 'suggested_quantity', 'name' => 'supplier_products.suggested_quantity', 'title' => 'Suggested Ship Qty / WH Qty', "visible" => true, "className" => 'suggested_quantity_class', 'data-toggle' => "tooltip"],
                ['data' => 'case_pack', 'name' => 'case_pack', 'title' => 'Case Pack', "visible" => true, "className" => 'case_pack'],
                ['data' => 'order_qty', 'name' => 'order_qty', 'title' => 'Order Qty', "visible" => true, "className" => ''],
                ['data' => 'supplier_sku', 'name' => 'supplier_products.supplier_sku', 'title' => 'Unit Price / Supplier SKU /Supplier Name', "visible" => true, "orderable" => false, "className" => ''],
                ['data' => 'estimated_margin', 'name' => 'estimated_margin', 'title' => 'Profit / Margin', "orderable" => false, "className" => 'text-nowrap', "width" => '200px'],
                ['data' => 'is_hazmat', 'name' => 'amazon_products.is_hazmat', 'title' => 'Hazmat / Oversize', "visible" => true, "orderable" => false, "className" => ''],
           
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
            $colCount = array_map(fn ($i) => $i, range(0, $rowCount));
            $targets = [...$colCount];
        } else {
            $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        }
        $data["columnDefs"] = [
            [
                "targets" => $targets,
                // "className" => 'text-nowrap',
            ],
        ];
        $data['bAutoWidth'] = false;
        return $data;
    }

    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'main_image' => 'Image',
            'title' => 'Title',
            'sku_asin' => 'SKU / ASIN',
            // 'asin' => 'ASIN',
            'total_order' => 'Total Order',
            'qty' => 'FBA Qty',
            'suggested_quantity' => 'Suggested Ship Qty / WH Qty',
            'case_pack' => 'Case Pack',
            'order_qty' => 'Order Qty',
            'supplier_sku' => 'Unit Price / Supplier SKU /Supplier Name',
            'estimated_margin' => 'Profit / Margin',
            'is_hazmat' => 'Hazmat / Oversize',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        $parentTable = (new SupplierProduct())->getTable();
        $childTable = (new AmazonProduct())->getTable();
        $salesVelocities = (new SalesVelocity())->getTable();
        return [
            "main_image" => "$childTable.main_image",
            "title" => "$childTable.title",
            "sku_asin" => "$childTable.sku",
            // "asin" => "$childTable.asin",
            "total_order" => "total_order",
            "suggested_quantity" => "$salesVelocities.suggested_quantity",
            "qty" => "$childTable.qty",
            "case_pack" => "$childTable.case_pack",
            "unit_price" => "supplier_products.unit_price",
            "order_qty" => "$parentTable.order_qty",
            "supplier_sku" => "$parentTable.supplier_sku",
            "estimated_margin" => "estimated_margin",
            "is_hazmat" => "$childTable.is_hazmat",
            "is_oversize" => "$childTable.is_oversize",
        ];
    }
}
