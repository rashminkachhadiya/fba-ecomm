<?php

namespace App\DataTables\Shopify;

use App\Helpers\CommonHelper;
use App\Models\ShopifyOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class ViewOrderDatatable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            ->addIndexColumn()
            ->editColumn('sku', function ($value) {
                return $value->sku;
            })
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('item_total_price', function ($value) {
                return config('constants.currency_symbol').$value->item_total_price;
            })
            ->editColumn('item_total_discount', function ($value) {
                return config('constants.currency_symbol').$value->item_total_discount;
            })
            ->editColumn('variant_id', function ($value) {
                return $value->variant_id;
            })
            ->editColumn('quantity', function ($value) {
                return $value->quantity;
            })
            ->editColumn('main_image', function ($value) {
                return (!empty($value->main_image)) ? '<a href="' . $value->main_image . '" target="_blank"><img src="' . $value->main_image . '" width="75" height="75"></a>' : '-';
            })
            ->rawColumns(['sku', 'title', 'item_total_price', 'item_total_discount', 'variant_id', 'quantity', 'main_image']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ShopifyOrderItem $model, Request $request): QueryBuilder
    {
        $orderId = $this->id;

        $model = $model->newQuery();

        $model->where('shopify_order_items.shopify_order_id', $orderId);

        $model->leftJoin('shopify_products', 'shopify_products.sku', '=' , 'shopify_order_items.sku');

        $model->select('shopify_order_items.*', 'shopify_products.main_image as main_image');

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('title', 'LIKE', '%' . $request->search . '%');
            });
        }

        $model->when((request('total_min_qty') || request('total_max_qty')), function($query){

            if((request('total_min_qty') || request('total_min_qty') == "0") && (request('total_max_qty') || request('total_max_qty') == "0")){
                $query->havingRaw('quantity >= ? AND quantity <= ?', [request('total_min_qty'), request('total_max_qty')]);
            }
            if(request('total_min_qty'))
            {
                $query->havingRaw('quantity >= ?', [request('total_min_qty')]);
            }

            if(request('total_max_qty'))
            {
                $query->havingRaw('quantity <= ?', [request('total_max_qty')]);
            }
        });

        $model->when((request('total_min_total_price') || request('total_max_total_price')), function($query){

            if((request('total_min_total_price') || request('total_min_total_price') == "0") && (request('total_max_total_price') || request('total_max_total_price') == "0")){
                $query->havingRaw('item_total_price >= ? AND item_total_price <= ?', [request('total_min_total_price'), request('total_max_total_price')]);
            }
            if(request('total_min_total_price'))
            {
                $query->havingRaw('item_total_price >= ?', [request('total_min_total_price')]);
            }

            if(request('total_max_total_price'))
            {
                $query->havingRaw('item_total_price <= ?', [request('total_max_total_price')]);
            }
        });

        $model->when((request('total_min_discount') || request('total_max_discount')), function($query){

            if((request('total_min_discount') || request('total_min_discount') == "0") && (request('total_max_discount') || request('total_max_discount') == "0")){
                $query->havingRaw('item_total_discount >= ? AND item_total_discount <= ?', [request('total_min_discount'), request('total_max_discount')]);
            }
            if(request('total_min_discount'))
            {
                $query->havingRaw('item_total_discount >= ?', [request('total_min_discount')]);
            }

            if(request('total_max_discount'))
            {
                $query->havingRaw('item_total_discount <= ?', [request('total_max_discount')]);
            }
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
                    d.total_min_qty = $.trim($("#total_min_qty").val());
                    d.total_max_qty = $.trim($("#total_max_qty").val());
                    d.total_min_total_price = $.trim($("#total_min_total_price").val());
                    d.total_max_total_price = $.trim($("#total_max_total_price").val());
                    d.total_min_discount = $.trim($("#total_min_discount").val());
                    d.total_max_discount = $.trim($("#total_max_discount").val());                
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
            ['data' => 'main_image', 'name' => 'main_image', 'title' => 'Image'],
            ['data' => 'title', 'name' => 'title', 'title' => 'Title', 'orderable' => true, "className" => ' '],
            ['data' => 'item_total_price', 'name' => 'item_total_price', 'title' => 'Item Total Price', 'orderable' => true, "className" => ' '],
            ['data' => 'sku', 'name' => 'sku', 'title' => 'SKU', 'orderable' => true, "className" => ' '],
            ['data' => 'quantity', 'name' => 'quantity', 'title' => 'Quantity', 'orderable' => true, "className" => ' '],
            ['data' => 'item_total_discount', 'name' => 'item_total_discount', 'title' => 'Item Total Discount', 'orderable' => true, "className" => ' '],
            ['data' => 'variant_id', 'name' => 'variant_id', 'title' => 'Variant ID', 'orderable' => true, "className" => ' '],
        ];
    }

    protected function getBuilderParameters(): array
    {
        $data = CommonHelper::getBuilderParameters();

        $targets = [0,1,2,3,4,5,6];

        $data["columnDefs"] = [
            [
                "targets" => $targets,
            ],
        ];

        return $data;
    }
}