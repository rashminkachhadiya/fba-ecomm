<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\AmazonOrderReport;
use App\Models\AmazonProduct;
use App\Models\Store;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class OrderDataTable extends DataTable
{
    private $getListingFields = [];
    private $parentTable;
    private $childTable;
    private $storeTable;

    public function __construct()
    {
        // $result = Cache::rememberForever('listing_fields', function () {
        //     return (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.order_report'));
        // });
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.order_report'));
        // $this->getListingFields = $result;
        $this->parentTable = (new AmazonOrderReport())->getTable();
        $this->childTable = (new AmazonProduct())->getTable();
        $this->storeTable = (new Store())->getTable();
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return DataTables::of($query)
            ->setTotalRecords($query->count("$this->parentTable.id"))
            ->addIndexColumn()
            ->editColumn('amazon_order_id', function ($value) {
                return $value->amazon_order_id;
            })
            ->editColumn('quantity', function ($value) {
                return $value->quantity;
            })
            ->editColumn('item_price', function ($value) {
                return config('constants.currency_symbol').$value->item_price;
            })
            ->editColumn('order_date', function ($value) {
                return Carbon::parse($value->order_date)->format('d-m-Y H:i:s');
            })
            ->editColumn('order_status', function ($value) {
                // $badgeArr = [
                //     'title' => $value->order_status,
                //     'bgColor' => config('constants.order_status_color.'.$value->order_status),
                // ];
                // return view('badge',compact('badgeArr'));
                return $value->order_status;
            })
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('fulfillment_channel', function ($value) {
                return $value->fulfillment_channel;
            })
            ->editColumn('store_name', function ($value) {
                return $value->store_name;
            })
            ->rawColumns(['amazon_order_id', 'item_price', 'quantity', 'order_date','order_status','title','fulfillment_channel','store_name']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(AmazonOrderReport $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->leftJoin($this->childTable, "$this->parentTable.product_id", '=', "$this->childTable.id")
            ->leftJoin($this->storeTable, "$this->parentTable.store_id", '=', "$this->storeTable.id");

        if(!empty($this->getListingFields))
        {
            $getColsWithTableName = $this->ColsWithTableName();

            $selectedFields = [];
            foreach ($this->getListingFields as $value) {
                $selectedFields[] = $getColsWithTableName[$value];
            }
            $model->select("$this->parentTable.id",...($selectedFields));
        }else{
            $model->select("$this->parentTable.id", "$this->parentTable.amazon_order_id", "$this->parentTable.quantity", "$this->parentTable.order_date","$this->parentTable.order_status","$this->childTable.title","$this->parentTable.fulfillment_channel","$this->storeTable.store_name","$this->parentTable.item_price",);
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where("$this->parentTable.amazon_order_id", 'LIKE', '%' . $request->search . '%');
                $query->orWhere("$this->childTable.title", 'LIKE', '%' . $request->search . '%');
            });
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

        return $model->when((request('order_status') || request('order_status') == "0"), function($query){
            $orderStatus = [];
            $searchStatus = explode(",",request('order_status'));
            foreach($searchStatus as $statusVal)
            {
                array_push($orderStatus, config('constants.order_status.'.$statusVal));
            }
            return $query->whereIn("$this->parentTable.order_status", $orderStatus);
        })->when(request('fulfillment_channel'), function($query){
            return $query->where("$this->parentTable.fulfillment_channel", request('fulfillment_channel'));
        })->when(request('store'), function($query){
            return $query->where("$this->storeTable.id", request('store'));
        });
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
                    d.fulfillment_channel = $.trim($("#fulfillment_channel").val());
                    d.store = $.trim($("#store").val());
                    d.order_start_date = $.trim($("#order_start_date").val());
                    d.order_end_date = $.trim($("#order_end_date").val());
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

        if(!empty($cols))
        {
            $columnList = [];
            
            foreach ($cols as $value) {

                if($value == 'title')
                {
                    array_push($columnList,[
                        'data' => 'title',
                        'name' => $getColsWithTableName['title'],
                        'title' => $getListingColumns['title'], 
                        'orderable' => true,
                        'width' => '200px',
                        'render' => function() {
                            return <<<JS
                                function(data, type, row){
                                if (data && type === 'display') {
                                    // Truncate the title to a desired length
    
                                    var truncatedTitle = data.length > 50 ? data.substr(0, 50) + '...' : data;
                                    
                                    // Add a tooltip with the full title
                                    var tooltip = data.length > 50 ? 'title=' + '"' + data + '"' : '';
                                    
                                    return '<span ' + tooltip + '>' + truncatedTitle + '</span>';
                                }
                                return data;
                            }
                            JS
                            ;
                        },
                    ]);
                }else{
                    array_push($columnList, [
                        'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true
                    ]);
                }
            }
            return [
                ...$columnList,
            ];
        }else{

            return [
                ['data' => 'store_name', 'name' => $getColsWithTableName['store_name'], 'title' => $getListingColumns['store_name'], 'orderable' => true],
                ['data' => 'amazon_order_id', 'name' => $getColsWithTableName['amazon_order_id'], 'title' => $getListingColumns['amazon_order_id']],
                ['data' => 'fulfillment_channel', 'name' => $getColsWithTableName['fulfillment_channel'], 'title' => $getListingColumns['fulfillment_channel'], 'orderable' => true],
                [
                    'data' => 'title',
                    'name' => $getColsWithTableName['title'],
                    'title' => $getListingColumns['title'], 
                    'orderable' => true,
                    'width' => '200px',
                    'render' => function() {
                        return <<<JS
                            function(data, type, row){
                            if (data && type === 'display') {
                                // Truncate the title to a desired length

                                var truncatedTitle = data.length > 100 ? data.substr(0, 100) + '...' : data;
                                
                                // Add a tooltip with the full title
                                var tooltip = data.length > 100 ? 'title=' + '"' + data + '"' : '';
                                
                                return '<span ' + tooltip + '>' + truncatedTitle + '</span>';
                            }
                            return data;
                        }
                        JS
                        ;
                    },
                ],
                ['data' => 'quantity', 'name' => $getColsWithTableName['quantity'], 'title' => $getListingColumns['quantity'], 'orderable' => true,'searchable' => true],
                ['data' => 'item_price', 'name' => $getColsWithTableName['item_price'], 'title' => $getListingColumns['item_price'], 'orderable' => true,'searchable' => true],
                ['data' => 'order_status', 'name' => $getColsWithTableName['order_status'], 'title' => $getListingColumns['order_status'], 'orderable' => true],
                ['data' => 'order_date', 'name' => $getColsWithTableName['order_date'], 'title' => $getListingColumns['order_date'], 'orderable' => true],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();
        
        if(!empty($cols))
        {
            $count = count($cols);
            $colCount = array_map(fn($i) => $i, range(0, $count - 1));
            $targets = [...$colCount];
            
        }else{
            $targets = [0, 1, 2, 3, 4, 5, 6, 7];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
            ]
        ];

        return $data;
    }


    public function listingColumns() : array
    {
        return [
            'store_name' => 'Store Name',
            'amazon_order_id' => 'Order ID',
            'fulfillment_channel' => 'Fulfillment Channel',
            'title' => 'Product Name',
            'quantity' => 'Qty',
            'item_price' => 'Order Price',
            'order_status' => 'Order Status',
            'order_date' => 'Order Date',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName() : array
    {
        return [
            'amazon_order_id' => "$this->parentTable.amazon_order_id",
            'quantity' => "$this->parentTable.quantity",
            'item_price' => "$this->parentTable.item_price",
            'store_name' => "$this->storeTable.store_name",
            'order_status' => "$this->parentTable.order_status",
            'order_date' => "$this->parentTable.order_date",
            'title' => "$this->childTable.title",
            'fulfillment_channel' => "$this->parentTable.fulfillment_channel",
        ];
    }

}
