<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class StoreDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.stores'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return DataTables::of($query)
            ->setTotalRecords($query->count('stores.id'))
            ->addIndexColumn()
            ->editColumn('store_name', function ($value) {
                return $value->store_name;
            })
            ->addColumn('store_type', function ($value) {
                return $value->store_type;
            })
            ->addColumn('store_country', function ($value) {
                return $value->store_country;
            })
            ->addColumn('status', function ($value) {
                return view('stores.status', compact('value'));
            })
            ->editColumn('action', function ($value) {
                return view('stores.action_button', compact('value'));
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->rawColumns(['store_name', 'store_type', 'store_country', 'status', 'action', 'created_at']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Store $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->leftJoin('store_configs', 'store_configs.id', '=', 'stores.store_config_id')
            ->select(
                'stores.id',
                'stores.store_name',
                'stores.status',
                'stores.created_at',
                'store_configs.store_type',
                'store_configs.store_country'
            );

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('stores.store_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('store_configs.store_type', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('store_configs.store_country', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->status == "1") {
            $model->where('stores.status', $request->status);
        } elseif ($request->status == "0") {
            $model->where('stores.status', "0");
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
                    d.status = $.trim($("#status").val());
                }',
                'beforeSend' => 'function() {
                    show_loader();
                }',
                'complete' => 'function() {
                    hide_loader();
                }',
                'error' => 'function (xhr, err) { }',
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
                    'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {
            return [
                ['data' => 'store_name', 'name' => 'store_name', 'title' => 'Store Name', 'orderable' => true, "visible" => true, "className" => ''],
                ['data' => 'store_type', 'name' => 'store_configs.store_type', 'title' => 'Store Type', 'orderable' => true, "visible" => true, 'className' => ''],
                ['data' => 'store_country', 'name' => 'store_configs.store_country', 'title' => 'Store Country', 'orderable' => true, "visible" => true, 'className' => ''],
                ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true, "visible" => true, 'className' => 'text-nowrap'],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, "visible" => true, 'className' => 'text-center'],
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
            $targets = [0, 1, 2, 3, 4];
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
            'store_name' => 'Store Name',
            'store_type' => 'Store Type',
            'store_country' => 'Store Country',
            'status' => 'Status',
            'created_at' => 'Created At'
        ];
    }

    // Lising of default column list
    public function ColsWithTableName(): array
    {
        $parentTable = (new Store())->getTable();
        $childTable = (new StoreConfig())->getTable();
        return [
            'store_name' => "$parentTable.store_name",
            'store_type' => "$childTable.store_type",
            'store_country' => "$childTable.store_country",
            'status' => "$parentTable.status",
            'created_at' => "$parentTable.created_at"
        ];
    }
}
