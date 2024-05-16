<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\ShipmentProduct;
use App\Models\ShipmentPlan;
use App\Services\CommonService;
use DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class FBAPlansDatatable extends DataTable
{
    private $getListingFields = [];
    private $parentTable;
    private $childTable;

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.fba_plan'));
        $this->parentTable = (new ShipmentPlan())->getTable();
        $this->childTable = (new ShipmentProduct())->getTable();
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('plan_name', function ($value) {
                return $value->plan_name;
            })
            ->editColumn('box_content', function ($value) {
                return $value->box_content;
            })
            ->editColumn('prep_preference', function ($value) {
                return $value->prep_preference;
            })
            ->editColumn('total_sku', function ($value) {
                return $value->total_sku;
            })
            ->editColumn('total_sellable_unit', function ($value) {
                return empty($value->total_sellable_unit) ? 0 : $value->total_sellable_unit;
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('plan_status', function ($value) {
                return view('shipment_plans.plan_status', compact('value'));
                // return $value->plan_status;
            })
            ->editColumn('action', function ($value) {
                return view('shipment_plans.action_button', compact('value'));
            })
            ->rawColumns(['plan_name', 'box_content', 'prep_preference','created_at','plan_status','total_sku','total_sellable_unit','action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ShipmentPlan $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->select("$this->parentTable.*", DB::raw("COUNT($this->childTable.sku ) as total_sku"),
        DB::raw("SUM($this->childTable.sellable_unit ) as total_sellable_unit"), "$this->childTable.sellable_unit as sellable_unit");

        $model->leftJoin("$this->childTable", "$this->childTable.shipment_plan_id", "=", "$this->parentTable.id")
              ->leftJoin("amazon_products", "amazon_products.id", "=", "$this->childTable.amazon_product_id");

        $sortColumn = $request->input('order.0.column');
        if (is_null($sortColumn)) {
            $model->orderBy("$this->parentTable.created_at", 'DESC');
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('plan_name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
            });
        }

        if (!empty($request->plan_status)) {
            $searchStatus = explode(",",request('plan_status'));
            $model->whereIn("$this->parentTable.plan_status", $searchStatus);
        }

        $model->when((request('total_min_sku') || request('total_max_sku')), function($query){

            if((request('total_min_sku') || request('total_min_sku') == "0") && (request('total_max_sku') || request('total_max_sku') == "0")){
                $query->havingRaw('total_sku >= ? AND total_sku <= ?', [request('total_min_sku'), request('total_max_sku')]);
            }
            if(request('total_min_sku'))
            {
                $query->havingRaw('total_sku >= ?', [request('total_min_sku')]);
            }

            if(request('total_max_sku'))
            {
                $query->havingRaw('total_sku <= ?', [request('total_max_sku')]);
            }
        });

        $model->when((request('total_min_sellable_unit') || request('total_max_sellable_unit')), function($query){

            if((request('total_min_sellable_unit') || request('total_min_sellable_unit') == "0") && (request('total_max_sellable_unit') || request('total_max_sellable_unit') == "0")){
                $query->havingRaw('total_sellable_unit >= ? AND total_sellable_unit <= ?', [request('total_min_sellable_unit'), request('total_max_sellable_unit')]);
            }
            if(request('total_min_sellable_unit'))
            {
                $query->havingRaw('total_sellable_unit >= ?', [request('total_min_sellable_unit')]);
            }

            if(request('total_max_sellable_unit'))
            {
                $query->havingRaw('total_sellable_unit <= ?', [request('total_max_sellable_unit')]);
            }
        });

        $plan_start_date = Carbon::parse($request->plan_start_date)->startOfDay();
        $plan_end_date = Carbon::parse($request->plan_end_date)->endOfDay();
        
        if (!empty($request->plan_start_date) && !empty($request->plan_end_date)) {
            $model->whereBetween("$this->parentTable.created_at", [$plan_start_date, $plan_end_date]);
        } elseif (!empty($request->plan_start_date)) {
            $model->where("$this->parentTable.created_at", '>=', $plan_start_date);
        } elseif (!empty($request->plan_end_date)) {
            $model->where("$this->parentTable.created_at", '<=', $plan_end_date);
        }

        $model->groupBy("$this->parentTable.id");
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
                    d.plan_status = $.trim($("#plan_status").val());
                    d.total_min_sku = $.trim($("#total_min_sku").val());
                    d.total_max_sku = $.trim($("#total_max_sku").val());
                    d.total_min_sellable_unit = $.trim($("#total_min_sellable_unit").val());
                    d.total_max_sellable_unit = $.trim($("#total_max_sellable_unit").val());
                    d.plan_start_date = $.trim($("#plan_start_date").val());
                    d.plan_end_date = $.trim($("#plan_end_date").val());
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
                    'data' => $value, 'name' => $getColsWithTableName[$value], 'title' => $getListingColumns[$value], 'orderable' => true, 
                    "className" => ($value == 'total_sku') || ($value == 'total_sellable_unit' ) || ($value == 'plan_status') ? 'text-center' : 'text-left','text-center'

                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {

            return [
                ['data' => 'plan_name', 'name' => $getColsWithTableName['plan_name'], 'title' => $getListingColumns['plan_name'], 'orderable' => true],
                ['data' => 'box_content', 'name' => $getColsWithTableName['box_content'], 'title' => $getListingColumns['box_content'], 'orderable' => true],
                ['data' => 'prep_preference', 'name' => $getColsWithTableName['prep_preference'], 'title' => $getListingColumns['prep_preference'], 'orderable' => true],
                ['data' => 'created_at', 'name' => $getColsWithTableName['created_at'], 'title' => $getListingColumns['created_at'], 'orderable' => true],
                ['data' => 'total_sku', 'name' => $getColsWithTableName['total_sku'], 'title' => $getListingColumns['total_sku'], 'orderable' => true, "className" => 'text-center'],
                ['data' => 'total_sellable_unit', 'name' => $getColsWithTableName['total_sellable_unit'], 'title' => $getListingColumns['total_sellable_unit'], 'orderable' => true, "className" => 'text-center'],
                ['data' => 'plan_status', 'name' => $getColsWithTableName['plan_status'], 'title' => $getListingColumns['plan_status'], 'orderable' => true, "className" => 'text-center'],
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
            $targets = [0, 1, 2, 3, 4, 5,6];
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
           "plan_name" => "Plan Name",
           "box_content" => "Box Content Info",
           "prep_preference" => "Prep Preference",
           "created_at" => "Created Date",
           "total_sku" => "Total SKUs",
           "total_sellable_unit" => "Total Sellable Units",
           "plan_status" => "Plan Status"
        ];
    }

     // Lising of default column list
     public function ColsWithTableName(): array
     {
         return [
            "plan_name" => "$this->parentTable.plan_name",
            "box_content" => "$this->parentTable.box_content",
            "prep_preference" => "$this->parentTable.prep_preference",
            "created_at" => "$this->parentTable.created_at",
            "total_sku" => DB::raw("COUNT($this->childTable.sku)"),
            "total_sellable_unit" => DB::raw("SUM($this->childTable.sellable_unit)"),
            "plan_status" => "$this->parentTable.plan_status"
         ];
     }

}