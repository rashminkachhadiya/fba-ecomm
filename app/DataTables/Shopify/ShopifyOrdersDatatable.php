<?php

namespace App\DataTables\Shopify;

use App\Helpers\CommonHelper;
use App\Models\ShopifyOrder;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use App\Models\ShopifyOrderItem;
use DB;
use App\Models\ShopifyOrderDetail;

class ShopifyOrdersDatatable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.shopify_orders'));
        $this->parentTable = (new ShopifyOrder())->getTable();
        $this->childTable = (new ShopifyOrderItem())->getTable();
        $this->shopifyOrderDetailTable = (new ShopifyOrderDetail())->getTable();
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            ->setTotalRecords($query->count("$this->parentTable.id"))
            ->addIndexColumn()
            ->editColumn('shopify_unique_id', function ($value) {
                return $value->shopify_unique_id;
            })
            ->editColumn('order_items_sum_quantity', function ($value) {
                return $value->order_items_sum_quantity;
            })
            ->editColumn('order_date', function ($value) {
                return Carbon::parse($value->order_date)->format('d-m-Y H:i:s');
            })
            ->editColumn('customer_name', function ($value) {
                if (!empty($value->shopify_customer)) {
                    $unserialized = unserialize($value->shopify_customer);
                    $customer_name = $unserialized['first_name'].' '.$unserialized['last_name'];
                }else{
                    $customer_name = '';
                }

                return $customer_name;
            })
            ->editColumn('shipping_address', function ($value) {
                if(!empty($value->shipping_address_address1)){
                return $value->shipping_address_address1. ' '. $value->shipping_address_address2. ', '. $value->shipping_address_city
                .', </br>'. $value->shipping_address_zip. ', '. $value->shipping_address_country;
                }else{
                    return '-';
                }
            })
            ->editColumn('processing_method', function ($value) {
                return $value->processing_method;
            })
            ->editColumn('shipping_method_code', function ($value) {
                return $value->shipping_method_code;
            })
            ->editColumn('fulfillment_status', function ($value) {
                return $value->fulfillment_status;
            })
            ->editColumn('total_price', function ($value) {
                return config('constants.currency_symbol').$value->total_price;
            })
            ->editColumn('subtotal_price', function ($value) {
                return  config('constants.currency_symbol').$value->subtotal_price;
            })
            ->editColumn('total_discounts', function ($value) {
                return config('constants.currency_symbol').$value->total_discounts;
            })
            ->editColumn('last_modified', function ($value) {
                return Carbon::parse($value->last_modified)->format('d-m-Y H:i:s');
            })
            ->editColumn('order_note', function ($value) {
                return '<textarea class="form-control" rows="1" onkeyup="saveOrderNote(this, '.$value->id.')" id="order_note_'.$value->id.'" url="'.route('shopify-order-note-save').'">'.$value->order_note.'</textarea>';
            })
            ->editColumn('action', function ($value) {
                return view('shopify.orders.action', compact('value'))->render();
            })
            ->rawColumns(['shopify_unique_id','order_date','shipping_address','processing_method','shipping_method_code','fulfillment_status','total_price','subtotal_price','total_discounts','last_modified','order_items_sum_quantity', 'order_note', 'customer_name', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ShopifyOrder $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->select("$this->parentTable.id", "$this->parentTable.shopify_unique_id", "$this->parentTable.order_date", 
        "$this->parentTable.shipping_address_address1","$this->parentTable.shipping_address_address2", 
        "$this->parentTable.shipping_address_city", "$this->parentTable.shipping_address_zip", "$this->parentTable.shipping_address_country",
        "$this->parentTable.processing_method", "$this->parentTable.shipping_method_code", 
        "$this->parentTable.fulfillment_status", "$this->parentTable.total_price", "$this->parentTable.subtotal_price", "$this->parentTable.total_discounts",
        "$this->parentTable.last_modified", "$this->parentTable.order_note", "$this->shopifyOrderDetailTable.shopify_customer");

        $model->leftJoin($this->shopifyOrderDetailTable, "$this->shopifyOrderDetailTable.shopify_order_id", "$this->parentTable.id");

        $model->withSum('orderItems', 'quantity');

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('shopify_unique_id', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipping_address_address1', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipping_address_address2', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipping_address_city', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipping_address_zip', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipping_address_country', 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->shopifyOrderDetailTable.shopify_customer", 'LIKE', '%' . $request->search . '%');
            });
        }

        if (!empty($request->order_status)) {
            $searchStatus = explode(",",request('order_status'));
            $model->whereIn("fulfillment_status", $searchStatus);
        }

        $order_start_date = Carbon::parse($request->order_start_date)->startOfDay();
        $order_end_date = Carbon::parse($request->order_end_date)->endOfDay();
        
        if (!empty($request->order_start_date) && !empty($request->order_end_date)) {
            $model->whereBetween("$this->parentTable.order_date", [$order_start_date, $order_end_date]);
        } elseif (!empty($request->order_start_date)) {
            $model->where("$this->parentTable.order_date", '>=', $order_start_date);
        } elseif (!empty($request->order_end_date)) {
            $model->where("$this->parentTable.order_date", '<=', $order_end_date);
        }

        $model->when((request('total_min_qty') || request('total_max_qty')), function($query){

            if((request('total_min_qty') || request('total_min_qty') == "0") && (request('total_max_qty') || request('total_max_qty') == "0")){
                $query->havingRaw('order_items_sum_quantity >= ? AND order_items_sum_quantity <= ?', [request('total_min_qty'), request('total_max_qty')]);
            }
            if(request('total_min_qty'))
            {
                $query->havingRaw('order_items_sum_quantity >= ?', [request('total_min_qty')]);
            }

            if(request('total_max_qty'))
            {
                $query->havingRaw('order_items_sum_quantity <= ?', [request('total_max_qty')]);
            }
        });

        $model->when((request('total_min_order_price') || request('total_max_order_price')), function($query){

            if((request('total_min_order_price') || request('total_min_order_price') == "0") && (request('total_max_order_price') || request('total_max_order_price') == "0")){
                $query->havingRaw('subtotal_price >= ? AND subtotal_price <= ?', [request('total_min_order_price'), request('total_max_order_price')]);
            }
            if(request('total_min_order_price'))
            {
                $query->havingRaw('subtotal_price >= ?', [request('total_min_order_price')]);
            }

            if(request('total_max_order_price'))
            {
                $query->havingRaw('subtotal_price <= ?', [request('total_max_order_price')]);
            }
        });

        $model->when((request('total_min_total_price') || request('total_max_total_price')), function($query){

            if((request('total_min_total_price') || request('total_min_total_price') == "0") && (request('total_max_total_price') || request('total_max_total_price') == "0")){
                $query->havingRaw('total_price >= ? AND total_price <= ?', [request('total_min_total_price'), request('total_max_total_price')]);
            }
            if(request('total_min_total_price'))
            {
                $query->havingRaw('total_price >= ?', [request('total_min_total_price')]);
            }

            if(request('total_max_total_price'))
            {
                $query->havingRaw('total_price <= ?', [request('total_max_total_price')]);
            }
        });

        $model->when((request('total_min_discount') || request('total_max_discount')), function($query){

            if((request('total_min_discount') || request('total_min_discount') == "0") && (request('total_max_discount') || request('total_max_discount') == "0")){
                $query->havingRaw('total_discounts >= ? AND total_discounts <= ?', [request('total_min_discount'), request('total_max_discount')]);
            }
            if(request('total_min_discount'))
            {
                $query->havingRaw('total_discounts >= ?', [request('total_min_discount')]);
            }

            if(request('total_max_discount'))
            {
                $query->havingRaw('total_discounts <= ?', [request('total_max_discount')]);
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
                    d.order_status = $.trim($("#order_status").val());
                    d.order_start_date = $.trim($("#order_start_date").val());
                    d.order_end_date = $.trim($("#order_end_date").val());
                    d.total_min_qty = $.trim($("#total_min_qty").val());
                    d.total_max_qty = $.trim($("#total_max_qty").val());
                    d.total_min_order_price = $.trim($("#total_min_order_price").val());
                    d.total_max_order_price = $.trim($("#total_max_order_price").val());
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
                    'data' => $value, 'name' =>  $getColsWithTableName[$value], 
                    'title' => $getListingColumns[$value], 
                    'orderable' =>  true, 
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {

            return [
                ['data' => 'shopify_unique_id', 'name' => $getColsWithTableName['shopify_unique_id'], 'title' => $getListingColumns['shopify_unique_id'],'orderable' =>  true],
                ['data' => 'order_items_sum_quantity', 'name' => $getColsWithTableName['order_items_sum_quantity'], 'title' => $getListingColumns['order_items_sum_quantity'],'orderable' =>  true],
                ['data' => 'order_date', 'name' => $getColsWithTableName['order_date'], 'title' => $getListingColumns['order_date'],'orderable' =>  true],
                ['data' => 'customer_name', 'name' => $getColsWithTableName['customer_name'], 'title' => $getListingColumns['customer_name'],'orderable' =>  true],
                ['data' => 'shipping_address', 'name' => $getColsWithTableName['shipping_address'], 'title' => $getListingColumns['shipping_address'],'orderable' =>  true],
                ['data' => 'processing_method', 'name' => $getColsWithTableName['processing_method'], 'title' => $getListingColumns['processing_method'],'orderable' =>  true],
                ['data' => 'shipping_method_code', 'name' => $getColsWithTableName['shipping_method_code'], 'title' => $getListingColumns['shipping_method_code'],'orderable' =>  true],
                ['data' => 'fulfillment_status', 'name' => $getColsWithTableName['fulfillment_status'], 'title' => $getListingColumns['fulfillment_status'],'orderable' =>  true],
                ['data' => 'last_modified', 'name' => $getColsWithTableName['last_modified'], 'title' => $getListingColumns['last_modified'],'orderable' =>  true],
                ['data' => 'subtotal_price', 'name' => $getColsWithTableName['subtotal_price'], 'title' => $getListingColumns['subtotal_price'],'orderable' =>  true],
                ['data' => 'total_price', 'name' => $getColsWithTableName['total_price'], 'title' => $getListingColumns['total_price'],'orderable' =>  true],
                ['data' => 'total_discounts', 'name' => $getColsWithTableName['total_discounts'], 'title' => $getListingColumns['total_discounts'],'orderable' =>  true],
                ['data' => 'order_note', 'name' => $getColsWithTableName['order_note'], 'title' => $getListingColumns['order_note'],'orderable' =>  false],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' =>  false, 'searchable' => false, 'exportable' => false, 'printable' => false, 'className' => 'text-center']
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
            $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
                "className" => 'text-nowrap',
            ],
        ];

        return $data;
    }

    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'shopify_unique_id' => 'Order Id',
            'order_items_sum_quantity' => 'Total Qty',
            'order_date' => 'Order Date',
            'customer_name' => 'Customer Name',
            'shipping_address' => 'Shipping Address',
            'processing_method' => 'Payment Method',
            'shipping_method_code' => 'Shipping Method',
            'fulfillment_status' => 'Fulfillment Status',
            'last_modified' => 'Modified Date',
            'subtotal_price' => 'Order Price',
            'total_price' => 'Total Price',
            'total_discounts' => 'Total Discounts',
            'order_note' => 'Order Note',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        return [
            'shopify_unique_id' => "$this->parentTable.shopify_unique_id",
            'order_items_sum_quantity' => DB::raw("SUM($this->childTable.quantity)"),
            'order_date' => "$this->parentTable.order_date",
            'customer_name' => "$this->shopifyOrderDetailTable.shopify_customer",
            'shipping_address' => "$this->parentTable.shipping_address_address1",
            'processing_method' => "$this->parentTable.processing_method",
            'shipping_method_code' => "$this->parentTable.shipping_method_code",
            'fulfillment_status' => "$this->parentTable.fulfillment_status",
            'last_modified' => "$this->parentTable.last_modified",
            'subtotal_price' => "$this->parentTable.subtotal_price",
            'total_price' => "$this->parentTable.total_price",
            'total_discounts' => "$this->parentTable.total_discounts",
            'order_note' => "$this->parentTable.order_note",
        ];
    }
}
