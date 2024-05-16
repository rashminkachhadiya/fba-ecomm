<?php

namespace App\DataTables\Supplier;

use App\Helpers\CommonHelper;
use App\Models\AmazonProduct;
use App\Models\SupplierProduct;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class ProductsDatatable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.supplier_products'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return DataTables::of($query)
            ->setTotalRecords($query->count('supplier_products.id'))
            ->addIndexColumn()
            // ->setRowId(function ($value) {
            //     return $value->id;
            // })
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
            ->editColumn('title', function ($value) {
                return $value->title;
            })
            ->editColumn('main_image', function ($value) {
                return '<img src="' . $value->main_image . '" width="50" height="50">';
            })
            ->editColumn('supplier_sku', function ($value) {
                return '<input type="text" class="form-control valid_supplier_sku" onkeyup="onUpdateSupplierSKU(event);" id="supplier_sku' . '_' . $value->id . '" supplier-product-id="' . $value->id . '" name="supplier_sku[]" value="' . $value->supplier_sku . '" style="width:150px">';
            })
            ->editColumn('unit_price', function ($value) {
                return '<input type="number" step="0.01" min="0" onkeyup="onUpdateUnitPrice(event);" class="form-control valid_unit_price" id="unit_price' . '_' . $value->id . '" name="unit_price[]" supplier-product-id="' . $value->id . '" value="' . $value->unit_price . '" style="width:85px">';
            })
            ->editColumn('additional_cost', function ($value) {
                return '<input type="number" step="0.01" min="0" class="form-control valid_additional_cost" onkeyup="onUpdateAdditionalCost(event);" id="additional_cost' . '_' . $value->id . '" supplier-product-id="' . $value->id . '" name="additional_cost[]" value="' . $value->additional_cost . '" style="width:85px">';
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('action', function ($value) {
                return view('suppliers.products.action_button', compact('value'));
            })
            ->rawColumns(['sku_asin', 'sku', 'asin', 'title', 'main_image', 'supplier_sku', 'unit_price', 'additional_cost', 'action', 'created_at']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SupplierProduct $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->leftJoin('amazon_products', 'amazon_products.id', '=', 'supplier_products.product_id')
            ->select(
                'amazon_products.sku',
                'amazon_products.asin',
                'amazon_products.title',
                'amazon_products.main_image',
                'supplier_products.id',
                'supplier_products.supplier_sku',
                'supplier_products.unit_price',
                'supplier_products.additional_cost'
            );

        $model->where('supplier_products.supplier_id', $request->supplierId);

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('supplier_sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('unit_price', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('title', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('asin', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('additional_cost', 'LIKE', '%' . $request->search . '%');
            });
        }

        $model->orderBy('supplier_products.created_at', 'DESC');

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
                        }
                    ]);
                } else {
                    array_push($columnList, [
                        'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true,
                    ]);
                }
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {
            return [
                ['data' => 'main_image', 'name' => 'amazon_products.main_image', 'orderable' => false, 'title' => 'Image', "visible" => true, "className" => ''],
                [
                    'data' => 'title',
                    'name' => 'amazon_products.title',
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
                ['data' => 'sku_asin','name' => 'amazon_products.sku', 'title' => 'SKU / ASIN', "visible" => true, "className" => 'text-nowrap'],
                ['data' => 'supplier_sku', 'name' => 'supplier_sku', 'title' => 'Supplier SKU', 'orderable' => true, "visible" => true, "className" => ''],
                ['data' => 'unit_price', 'name' => 'unit_price', 'title' => 'Unit Price('.config('constants.currency_symbol').')', 'orderable' => true, "visible" => true, "className" => ''],
                ['data' => 'additional_cost', 'name' => 'additional_cost', 'title' => 'Additional Cost('.config('constants.currency_symbol').')', 'orderable' => true, "visible" => true, "className" => ''],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, "visible" => true, 'className' => 'text-center'],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();

        if (!empty($cols)) {
            $count = count($cols);
            $colCount = array_map(fn ($i) => $i, range(0, $count));
            $targets = [...$colCount];
        } else {
            $targets = [0, 1, 2, 3, 4, 5, 6];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
                // "className" => 'text-nowrap',
            ],
        ];

        return $data;
    }

    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'main_image' => 'Image',
            'title' => 'Title',
            'sku_asin' => 'SKU / ASIN',
            'supplier_sku' => 'Supplier SKU',
            'unit_price' => 'Unit Price('.config('constants.currency_symbol').')',
            'additional_cost' => 'Additional Cost('.config('constants.currency_symbol').')',
            'created_at' => 'Created At',
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        $parentTable = (new SupplierProduct())->getTable();
        $childTable = (new AmazonProduct())->getTable();
        return [
            'main_image' => "$childTable.main_image",
            'title' => "$childTable.title",
            'sku_asin' => "$childTable.sku",
            'supplier_sku' => "$parentTable.supplier_sku",
            'unit_price' => "$parentTable.unit_price",
            'additional_cost' => "$parentTable.additional_cost",
            'created_at' => "$parentTable.created_at",
        ];
    }
}
