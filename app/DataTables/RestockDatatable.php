<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\AmazonProduct;
use App\Models\SalesVelocity;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class RestockDatatable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.restocks'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTableQuery = Datatables::of($query);
        $dataTableArr = $dataTableQuery->toArray();
        $getProductDetail = null;

        if(!empty($dataTableArr['data']))
        {
            $supplierIds = array_column($dataTableArr['data'], 'supplier_id');

            $getProductDetail = SupplierProduct::whereIn('supplier_products.supplier_id', $supplierIds)
                                            ->leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
                                            ->select('supplier_products.supplier_id','supplier_products.threshold_qty','amazon_products.afn_inbound_working_quantity','amazon_products.afn_inbound_shipped_quantity','amazon_products.afn_inbound_receiving_quantity','amazon_products.qty','amazon_products.afn_reserved_quantity')
                                            ->get();
        }
        
        return $dataTableQuery
            ->addIndexColumn()
            ->editColumn('supplier_id', function ($value) {
                return "<a href='" . route('restock-supplier-products', ['supplier_id' => $value->supplier_id]) . "' class='link-class'>" . $value->supplier_name . "</a>";

            })
            ->editColumn('suggested_quantity', function ($value) {
                return is_null($value->total_suggested_quantity) ? 0 : $value->total_suggested_quantity;
            })
            ->editColumn('total_product', function ($value) {
                return is_null($value->total_product) ? 0 : $value->total_product;
            })
            ->addColumn('flag', function ($value) use($getProductDetail){
                $flag =  '';

                if($getProductDetail)
                {
                    $supplierWiseProduct = $getProductDetail->filter(function ($item) use($value) {
                    return $item->supplier_id == $value->supplier_id;
                    })->toArray();

                    if(!empty($supplierWiseProduct))
                    {
                        foreach($supplierWiseProduct as $products)
                        {
                            $totalFbaQty = $products['qty'] + ($products['afn_inbound_working_quantity'] + $products['afn_inbound_shipped_quantity'] + $products['afn_inbound_receiving_quantity']) + $products['afn_reserved_quantity'];

                            if($products['threshold_qty'] > $totalFbaQty)
                            {
                                $flag = '<i class="fa-solid fa-triangle-exclamation"></i>';
                            }
                        }
                    }
                }

                return $flag;
            })
            ->rawColumns(['supplier_id', 'total_product','flag']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SupplierProduct $model, Request $request)
    {
        $model = $model->newQuery();

        $model->leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->select('supplier_products.*', DB::raw('COUNT(amazon_products.id) as total_product'),
                DB::raw('SUM(supplier_products.suggested_quantity) as total_suggested_quantity'), 'suppliers.name as supplier_name')
            ->groupBy('supplier_products.supplier_id');

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->whereHas('supplier', function ($subQuery) use ($request) {
                    $subQuery->where('suppliers.name', 'LIKE', '%' . $request->search . '%');
                });
            });
        }

        if (!empty($request->supplier_filter)) {
            $supplierIds = explode(',', $request->supplier_filter);
            $model->whereIn('supplier_products.supplier_id', $supplierIds);
        }

        $sortColumn = $request->input('order.0.column');
        $sortDirection = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy('supplier_products.id', 'asc');
        } else {
            switch ($sortColumn) {
                case 0:
                    $model->orderBy('supplier_products.supplier_id', $sortDirection);
                    break;
                case 1:
                    $model->orderBy('suggested_quantity', $sortDirection);
                    break;
                case 2:
                    $model->orderBy('total_product', $sortDirection);
                default:
                    $model->orderBy('supplier_products.id', 'asc');
                    break;
            }
        }

        $model->where('suppliers.status', '1');

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
                    d.supplier_filter = $.trim($("#supplier_filter").val());

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
                if($value == 'flag')
                {
                    array_push($columnList, [
                        'data' => $value, 'title' => $getListingColumns[$value],
                    ]);
                }else{
                    array_push($columnList, [
                        'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true,
                    ]);
                }
            }
            return [
                ...$columnList,
            ];
        } else {

            return [
                ['data' => 'flag', 'title' => 'Flag'],
                ['data' => 'supplier_id', 'name' => 'supplier_products.supplier_id', 'title' => 'Supplier', 'orderable' => true],
                ['data' => 'suggested_quantity', 'name' => 'supplier_products.suggested_quantity', 'title' => 'Suggested Restock Qty', 'orderable' => true],
                ['data' => 'total_product', 'name' => 'amazon_products.qty', 'title' => 'Total Products', 'orderable' => true],
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
            $targets = [0, 1, 2, 3];
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
            'flag' => 'Flag',
            'supplier_id' => 'Supplier',
            'suggested_quantity' => 'Suggested Restock Qty',
            'total_product' => 'Total Products',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        $parentTable = (new SupplierProduct())->getTable();
        $childTable = (new AmazonProduct())->getTable();
        $salesVelocities = (new SalesVelocity())->getTable();
        return [
            'supplier_id' => "$parentTable.supplier_id",
            'total_product' => "$childTable.qty",
            'suggested_quantity' => "$salesVelocities.suggested_quantity",
        ];
    }
}
