<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use DB;

class POReceivedDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return DataTables::of($query)
            ->setTotalRecords($query->count('purchase_order_items.id'))
            ->addIndexColumn()
            ->editColumn('main_image', function ($value) {
                return (!empty($value->main_image)) ? '<a href="' . $value->main_image . '" target="_blank"><img src="' . $value->main_image . '" width="75" height="75"></a>' : '-';
            })
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('sku', function ($value) {
                return view('copy-btn', [
                    'value' => $value->sku,
                    'title' => 'SKU',
                ]) . view('copy-btn', [
                    'value' => $value->asin,
                    'title' => 'ASIN',
                    'link' => config('constants.redirect_amazon_product_url.1') . $value->asin,
                ]);
            })
            ->editColumn('supplier_sku', function ($value) {
                return (!empty($value->supplier_sku)) ? view('copy-btn', [
                    'value' => $value->supplier_sku,
                    'title' => 'Supplier SKU',
                ]) : '-';
            })
            ->editColumn('unit_price', function ($value) {
                $encryptPOId = Crypt::encrypt($value->id);
                return '<input type="number" step="0.01" min="0" class="form-control unit_price" pattern="^\d{0,12}(\.\d{0,12})?$" po-item-id="' . $value->id . '" id="unit_price' . '_' . $value->id . '" name="unit_price[]"  value="' . $value->unit_price . '" style="width:100px" onkeypress="return (event.charCode !=8  && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57) || (event.charCode === 46 || event.which === 46 || event.keyCode === 190))" onkeyup="onUpdateUnitPrice(event)">';
            })
            ->editColumn('order_qty', function ($value) {
                return $value->order_qty;
            })
            ->addColumn('total_price', function ($value) {
                return $value->total_price;
            })
            ->addColumn('received_qty', function ($value) {
                return '<input type="number" step="1" min="0" class="form-control received_qty" po-item-id="' . $value->id . '" id="received_qty' . '_' . $value->id . '" name="received_qty[]" value="' . $value->received_qty . '"  style="width:100px" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" onkeyup="onReceivedQty(event)">';
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
            ->editColumn('total_discrepancy', function ($value) {
                if (($value->total_discrepancy == 0)) {
                    $title = 'Add';
                    $modalFunction = 'showDiscrepancyModal';
                    $addNewDiscrepancy = null;
                } else {
                    $title = $value->total_discrepancy;
                    $modalFunction = 'editDiscrepancyModal';
                    $addNewDiscrepancy = ' | <a href="javascript:void(0)" onclick="showDiscrepancyModal(' . $value->id . ',' . $value->po_id . ')">Add';
                }
                return '<a href="javascript:void(0)" class="total-desc-class" onclick="' . $modalFunction . '(' . $value->id . ',' . $value->po_id . ')">' . $title .$addNewDiscrepancy. '</a>';
            })
            ->rawColumns(['main_image', 'title', 'sku', 'supplier_sku', 'unit_price', 'total_discrepancy', 'order_qty', 'total_price', 'received_qty', 'received_price', 'difference_qty', 'difference_price']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PurchaseOrderItem $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->leftJoin('supplier_products', 'supplier_products.id', '=', 'purchase_order_items.supplier_product_id')
            ->leftJoin('amazon_products', 'amazon_products.id', '=', 'purchase_order_items.product_id')
            ->leftJoin('po_discrepancies', 'po_discrepancies.po_item_id', '=', 'purchase_order_items.id')
            ->select('purchase_orders.id as po_id', 'purchase_order_items.id', 'purchase_order_items.order_qty', 'supplier_products.supplier_sku as supplier_sku',
                'purchase_order_items.unit_price', 'purchase_order_items.total_price', 'purchase_order_items.received_qty', 'purchase_order_items.received_price',
                'purchase_order_items.difference_qty', 'purchase_order_items.difference_price',
                'amazon_products.asin', 'amazon_products.sku', 'amazon_products.title', 'amazon_products.main_image',
                DB::raw('SUM(po_discrepancies.discrepancy_count) as total_discrepancy')
            );

        $model->where('purchase_orders.id', $request->poId);
        $model->groupBy(DB::Raw("purchase_order_items.id"));

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('amazon_products.title', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('supplier_products.supplier_sku', 'LIKE', '%' . $request->search . '%');
            });
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
                }',
                'beforeSend' => 'function() {
                    // show_loader();
                }',
                'complete' => 'function() {
                   // hide_loader();
                }',
                'error' => 'function (xhr, err) { console.log(err);}',
            ])
            ->columns($this->getColumns())
            ->parameters($this->getBuilderParameters());
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            ['data' => 'main_image', 'title' => 'Image'],
            [
                'data' => 'title',
                'title' => 'Title',
                'name' => 'title',
                'orderable' => true,
                "className" => 'min-w-180px',
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
            ['data' => 'sku', 'title' => 'Sku / ASIN', 'orderable' => true, "className" => 'text-nowrap'],
            ['data' => 'supplier_sku', 'title' => 'Supplier Sku', 'orderable' => true],
            ['data' => 'unit_price', 'title' => 'Unit Price('.config('constants.currency_symbol').')', 'className' => 'unit_price'],
            ['data' => 'order_qty', 'title' => 'Order Qty', 'orderable' => false, 'className' => 'text-center order_qty'],
            ['data' => 'total_price', 'title' => 'Total Price('.config('constants.currency_symbol').')', 'className' => 'total_price'],
            ['data' => 'received_qty', 'title' => 'Received Qty', 'className' => 'received_qty'],
            ['data' => 'total_discrepancy', 'title' => 'Total Discrepancy', 'className' => 'total_discrepancy_class'],
            ['data' => 'received_price', 'title' => 'Received Price('.config('constants.currency_symbol').')', 'className' => 'received_price'],
            ['data' => 'difference_qty', 'title' => 'Qty Difference', 'className' => 'difference_qty'],
            ['data' => 'difference_price', 'title' => 'Price Difference('.config('constants.currency_symbol').')', 'className' => 'difference_price'],
        ];
    }

    protected function getBuilderParameters(): array
    {
        $data = CommonHelper::getBuilderParameters();

        $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

        $data["columnDefs"] = [
            [
                "targets" => $targets,
            ],
        ];

        $data['bAutoWidth'] = false;
        return $data;
    }
}
