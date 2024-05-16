<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class EditPurchaseOrderDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            // ->setTotalRecords($query->count('purchase_order_items.id'))
            ->addIndexColumn()
            ->setRowId(function ($value) {
                return $value->id;
            })
            ->editColumn('checkbox', function ($value) {
                return '<input type="checkbox" title="Select this row" class="form-check form-check-input w-25px d-flex-inline select_row_btn" value="' . $value->id . '" role="button">';
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
                    'link' => config('constants.redirect_amazon_product_url.1') . $value->asin,
                ]);
            })
            ->editColumn('main_image', function ($value) {
                return '<img src="' . $value->main_image . '" width="65" height="65">';
            })
            ->editColumn('order_qty', function ($value) {
                return '<input type="number" step="1" min="1" class="form-control order_qty" po-item-id="' . $value->id . '" id="order_qty' . '_' . $value->id . '" name="order_qty[]" value="' . $value->order_qty . '"  style="width:100px" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" onkeyup="onUpdateOrderQty(event)"><span class="help-block error-help-block" id="case_pack_error_' . $value->id . '"></span>';
            })
            ->editColumn('supplier_sku', function ($value) {
                return $value->supplier_sku;
            })
            ->editColumn('unit_price', function ($value) {
                return '<input type="number" step="0.01" min="0" pattern="^\d{0,12}(\.\d{0,12})?$" class="form-control unit_price" po-item-id="' . $value->id . '" id="unit_price' . '_' . $value->id . '" name="unit_price[]"  value="' . $value->unit_price . '" style="width:100px" onkeypress="return (event.charCode !=8  && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57) || (event.charCode === 46 || event.which === 46 || event.keyCode === 190))" onkeyup="onUpdateUnitPrice(event)">';
            })
            ->editColumn('total_price', function ($value) {
                return $value->total_price;
            })
            ->addColumn('received_qty', function ($value) {
                return empty($value->received_qty) ? '0' : $value->received_qty;
            })
            ->addColumn('received_price', function ($value) {
                return $value->received_price;
            })
            ->addColumn('difference_qty', function ($value) {
                return $value->difference_qty;
            })
            ->addColumn('difference_price', function ($value) {
                return $value->difference_price;
            })
            ->addColumn('case_pack', function ($value) {
                return $value->case_pack;
            })
            ->editColumn('action', function ($value) {
                return view('purchase_orders.action_button_po_item', compact('value'));
            })
            ->rawColumns(['main_image','checkbox', 'sku_asin', 'title', 'sku', 'supplier_sku', 'unit_price', 'order_qty', 'total_price', 'received_qty', 'received_price', 'difference_qty', 'difference_price', 'action','case_pack']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PurchaseOrder $model, Request $request): QueryBuilder
    {
        $poId = $this->po_id;

        $model = $model->newQuery();

        $model->leftJoin('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id')
            ->leftJoin('supplier_products', 'purchase_order_items.supplier_product_id', '=', 'supplier_products.id')
            ->select('purchase_order_items.id', 'purchase_order_items.po_id', 'purchase_order_items.product_id', 'purchase_order_items.supplier_id', 'purchase_order_items.order_qty',
                'purchase_order_items.total_price', 'purchase_order_items.received_qty', 'purchase_order_items.received_price',
                'amazon_products.title', 'amazon_products.sku', 'amazon_products.asin', 'amazon_products.main_image',
                'purchase_order_items.difference_qty', 'purchase_order_items.difference_price',
                'amazon_products.price', 'supplier_products.supplier_sku', 'purchase_order_items.unit_price', 'supplier_products.id as supplier_product_id','amazon_products.case_pack');

        $model->where('purchase_orders.id', $poId);

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->orWhere('amazon_products.title', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
            });
        }

        $model->whereNULL('purchase_order_items.deleted_at');
        $model->orderBy('purchase_order_items.created_at', 'DESC');

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
                }',
                'beforeSend' => 'function() {
                    show_loader();
                }',
                'complete' => 'function() {
                   hide_loader();
                }',
                'error' => 'function (xhr, err) { console.log(err);}',
            ])
            ->columns($this->getColumns())
            ->parameters($this->getBuilderParameters());
    }

    /**
     * Get the dataTable columns definition.
     */
    protected function getColumns()
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '', 'orderable' => false],
            ['data' => 'main_image', 'name' => 'main_image', 'title' => 'Image', 'orderable' => false, "visible" => true, "className" => ''],
            [
                'data' => 'title',
                'name' => 'title',
                'title' => 'Title',
                'orderable' => true,
                'width' => '200px',
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
            ['data' => 'sku_asin', 'name' => 'sku', 'title' => 'SKU / ASIN', "visible" => true, "className" => 'text-nowrap'],
            ['data' => 'supplier_sku', 'name' => 'supplier_sku', 'title' => 'Supplier SKU', "visible" => true, "className" => ''],
            ['data' => 'case_pack', 'name' => 'case_pack', 'title' => 'Case Pack', "visible" => true, "className" => 'case_pack'],
            ['data' => 'unit_price', 'name' => 'unit_price', 'title' => 'Unit Price('.config('constants.currency_symbol').')', 'orderable' => true, "visible" => true, "className" => 'unit_price'],
            ['data' => 'order_qty', 'name' => 'order_qty', 'title' => 'Order Qty', "visible" => true, "className" => 'order_qty'],
            ['data' => 'total_price', 'title' => 'Total Price('.config('constants.currency_symbol').')', 'className' => 'total_price'],
            ['data' => 'received_qty', 'title' => 'Received Qty'],
            ['data' => 'received_price', 'title' => 'Received Price('.config('constants.currency_symbol').')', 'className' => 'received_price'],
            ['data' => 'difference_qty', 'title' => 'Difference Qty', 'className' => 'difference_qty'],
            ['data' => 'difference_price', 'title' => 'Difference Price('.config('constants.currency_symbol').')', 'className' => 'difference_price'],
            ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
        ];
    }

    protected function getBuilderParameters(): array
    {
        $data = CommonHelper::getBuilderParameters();

        $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $data["columnDefs"] = [
            [
                "targets" => $targets,
            ],
        ];

        return $data;
    }
}
