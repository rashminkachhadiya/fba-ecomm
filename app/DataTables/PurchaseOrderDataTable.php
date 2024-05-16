<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\CommonService;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use App\Models\Supplier;

class PurchaseOrderDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.purchase_order'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            ->addIndexColumn()
            ->editColumn('supplier_id', function ($value) {
                return $value->supplier_name;
            })
            ->editColumn('po_number', function ($value) {
                return $value->status != 'Closed' ? '<a href="' . route('purchase_orders.edit', $value->id) . '" class="link-class">' . $value->po_number . '</a>' : $value->po_number;
            })
            ->editColumn('po_order_date', function ($value) {
                return $value->po_order_date;
            })
            ->editColumn('expected_delivery_date', function ($value) {
                return $value->expected_delivery_date;
            })
            ->editColumn('order_qty', function ($value) {
                return is_null($value->order_qty) ? '0' : $value->order_qty;
            })
            ->editColumn('total_price', function ($value) {
                return config('constants.currency_symbol').$value->total_price;
            })
            ->editColumn('received_qty', function ($value) {
                return is_null($value->received_qty) ? '0' : $value->received_qty;
            })
            ->addColumn('status', function ($value) {
                return $value->status;
                // $options = [$value->status, ...$this->getNextStatusOptions($value->status)];
                // return view('purchase_orders.status-dropdown', compact('options','value'));
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('updated_at', function ($value) {
                return Carbon::parse($value->updated_at)->format('d-m-Y');
            })
            ->editColumn('action', function ($value) {
                $nextActions = $this->getNextStatusOptions($value->status);
                return view('purchase_orders.action_button', compact('value','nextActions'));
            })
            ->rawColumns(['supplier_id', 'po_number', 'po_order_date', 'expected_delivery_date','received_qty','total_price', 'status', 'created_at', 'updated_at', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PurchaseOrder $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->leftJoin('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('purchase_orders.*','suppliers.name as supplier_name',
                DB::raw('SUM(purchase_order_items.order_qty) as order_qty'), 
                DB::raw('SUM(purchase_order_items.received_qty) as received_qty'),
                DB::raw('SUM(purchase_order_items.total_price) as total_price'),
            );

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('po_number', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('purchase_orders.status', 'LIKE', '%' . $request->search . '%');
                $query->orWhereHas('supplier', function ($subQuery) use ($request) {
                    $subQuery->where('suppliers.name', 'LIKE', '%' . $request->search . '%');
                });
                $query->orWhereHas('amazonProduct', function ($subQuery) use ($request) {
                    $subQuery->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id');
                    $subQuery->where('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
                    $subQuery->orWhere('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
                });
            });
        }

        if ($request->po_status_filter != "") {
            $statusIds = explode(',', $request->po_status_filter);
            $model->whereIn('purchase_orders.status', $statusIds);
        }

        if (!empty($request->supplier_filter)) {
            $supplierIds = explode(',', $request->supplier_filter);
            $model->whereIn('purchase_orders.supplier_id', $supplierIds);
        }

        if (!empty($request->po_number_filter)) {
            $model->where('purchase_orders.po_number', 'LIKE', '%' . $request->po_number_filter . '%');
        }

        if (!empty($request->sku_filter)) {
            $sku = $request->sku_filter;
            $model->where(function ($query) use ($sku) {
                $query->orWhereHas('amazonProduct', function ($subQuery) use ($sku) {
                    $subQuery->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id');
                    $subQuery->where('amazon_products.sku', 'LIKE', '%' . $sku . '%');
                });
            });
        }
        if (!empty($request->asin_filter)) {
            $asin = $request->asin_filter;
            $model->where(function ($query) use ($asin) {
                $query->orWhereHas('amazonProduct', function ($subQuery) use ($asin) {
                    $subQuery->leftJoin('amazon_products', 'purchase_order_items.product_id', '=', 'amazon_products.id');
                    $subQuery->where('amazon_products.asin', 'LIKE', '%' . $asin . '%');
                });
            });
        }

        $created_start_date = Carbon::parse($request->created_start_date)->startOfDay();
        $created_end_date = Carbon::parse($request->created_end_date)->endOfDay();
        $updated_start_date = Carbon::parse($request->updated_start_date)->startOfDay();
        $updated_end_date = Carbon::parse($request->updated_end_date)->endOfDay();

        if (!empty($request->created_start_date) && !empty($request->created_end_date)) {
            $model->whereBetween('purchase_orders.created_at', [$created_start_date, $created_end_date]);
        } elseif (!empty($request->created_start_date)) {
            $model->where('purchase_orders.created_at', '>=', $created_start_date);
        } elseif (!empty($request->created_end_date)) {
            $model->where('purchase_orders.created_at', '<=', $created_end_date);
        }

        if (!empty($request->updated_start_date) && !empty($request->updated_end_date)) {
            $model->whereBetween('purchase_orders.updated_at', [$updated_start_date, $updated_end_date]);
        } elseif (!empty($request->updated_start_date)) {
            $model->where('purchase_orders.updated_at', '>=', $updated_start_date);
        } elseif (!empty($request->updated_end_date)) {
            $model->where('purchase_orders.updated_at', '<=', $updated_end_date);
        }

        $sortColumn = $request->input('order.0.column');
        $sortDirection = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy('purchase_orders.id', 'desc');
        } elseif (!is_null($sortColumn) && $sortColumn == 4) {
            $model->orderBy('purchase_orders.status', $sortDirection);
        }

        $model->groupBy('purchase_orders.id');

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
                    d.po_status_filter = $.trim($("#po_status_filter").val());
                    d.supplier_filter = $.trim($("#supplier_filter").val());
                    d.po_number_filter = $.trim($("#po_number_filter").val());
                    d.sku_filter = $.trim($("#sku_filter").val());
                    d.asin_filter = $.trim($("#asin_filter").val());
                    d.created_start_date = $.trim($("#created_start_date").val());
                    d.created_end_date = $.trim($("#created_end_date").val());
                    d.updated_start_date = $.trim($("#updated_start_date").val());
                    d.updated_end_date = $.trim($("#updated_end_date").val());
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
                array_push($columnList, [
                    'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true,
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {

            return [
                ['data' => 'po_number', 'name' => 'purchase_orders.po_number', 'title' => 'PO Name', 'orderable' => true],
                ['data' => 'supplier_id', 'name' => 'purchase_orders.supplier_id', 'title' => 'Supplier', 'className' => ' '],
                ['data' => 'po_order_date', 'name' => 'purchase_orders.po_order_date', 'title' => 'PO Order Date', 'orderable' => true],
                ['data' => 'expected_delivery_date', 'name' => 'purchase_orders.expected_delivery_date', 'title' => 'Expected Delivery Date', 'orderable' => true],
                ['data' => 'order_qty', 'name' => 'purchase_order_items.order_qty', 'title' => 'Order Qty', 'className' => ' '],
                ['data' => 'received_qty', 'name' => 'purchase_order_items.received_qty', 'title' => 'Total Received Qty', 'className' => ' ','orderable' => true],
                ['data' => 'total_price', 'name' => 'purchase_order_items.total_price', 'title' => 'Total Value', 'className' => ' ', 'orderable' => true],
                ['data' => 'created_at', 'name' => 'purchase_orders.created_at', 'title' => 'Created Date', 'orderable' => true],
                ['data' => 'updated_at', 'name' => 'purchase_orders.updated_at', 'title' => 'Updated Date', 'orderable' => true],
                ['data' => 'status', 'title' => 'Status', 'name' => 'purchase_orders.status', 'orderable' => true, 'className' => 'text-nowrap'],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
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
            $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8];
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
            'po_number' => 'PO Name',
            'supplier_id' => 'Supplier',
            'po_order_date' => 'PO Order Date',
            'expected_delivery_date' => 'Expected Delivery Date',
            'order_qty' => 'Order Qty',
            'received_qty' => 'Total Received Qty',
            'total_price' => 'Total Value',
            'status' => 'Status',
            'created_at' => 'Created Date',
            'updated_at' => 'Updated Date',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        $parentTable = (new PurchaseOrder())->getTable();
        $childTable = (new PurchaseOrderItem())->getTable();
        return [
            'supplier_id' => "$parentTable.supplier_id",
            'po_number' => "$parentTable.po_number",
            'po_order_date' => "$parentTable.po_order_date",
            'expected_delivery_date' => "$parentTable.expected_delivery_date",
            'order_qty' => "$childTable.order_qty",
            'received_qty' => "$childTable.received_qty",
            'total_price' => "$childTable.total_price",
            'status' => "$parentTable.status",
            'created_at' => "$parentTable.created_at",
            'updated_at' => "$parentTable.updated_at",
        ];
    }

    /**
     * To determine the next status of the present status
     * @param string $currentStatus
     */
    public function getNextStatusOptions(string $currentStatus) : array
    {
        $status = [];

        switch ($currentStatus) {
            case 'Draft':
                return [...$status, 'Sent'];
                break;

            case 'Sent':
                return [...$status, 'Draft', 'Shipped'];
                break;

            case 'Shipped':
                return [...$status, 'Sent', 'Arrived'];
                break;

            case 'Arrived':
                return [...$status, 'Shipped', 'Receiving'];
                break;

            case 'Receiving':
                return [...$status, 'Shipped', 'Arrived', 'Partial Received', 'Received'];
                break;

            case 'Partial Received':
                return [...$status, 'Partial Received'];
                break;

            case 'Received':
                return [...$status, 'Receiving', 'Closed'];
                break;

            case 'Closed':
                return [...$status, 'Partial Received'];
                break;
            
            default:
                return $status;
                break;
        }
    }
}
