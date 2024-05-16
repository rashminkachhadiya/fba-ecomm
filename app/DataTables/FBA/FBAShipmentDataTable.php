<?php

namespace App\DataTables\FBA;

use App\Helpers\CommonHelper;
use App\Models\FbaShipment;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class FBAShipmentDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.fba_shipment'));
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return Datatables::of($query)
            ->setTotalRecords($query->count('fba_shipments.id'))
            ->addIndexColumn()
            ->addColumn('DT_RowIndex', function() {
                // The value of DT_RowIndex will automatically be set based on page-wise numbering
                return '';
            })
            ->editColumn('plan_name', function ($value) {
                return ($value->plan_name != '') ? '<a class="link link-class" href="'.route('fba-shipments.show',['shipmentId' => $value->shipment_id]).'">'.$value->plan_name.'</a>' : '-';
            })
            ->editColumn('skus', function ($value) {
                return $value->fba_shipment_items_count;
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y H:i');
            })
            ->editColumn('units', function ($value) {
                if (!empty($value->fbaShipmentItems)) {
                    return !empty($value->fbaShipmentItems->first()) ? $value->fbaShipmentItems->first()->units : '-';
                }
                return '-';
            })
            ->editColumn('shipment_status', function ($value) {
                $isShipmentIdExpired = (new CommonService())->checkShipmentIdExpired($value->created_at);

                $deletedAt = $value->deleted_at;

                if (!empty($deletedAt)) {
                    $deletedAt = Carbon::parse($deletedAt)->format('d-m-Y H:i');
                }

                return view('fba.fba_shipment.shipment_status', compact('value', 'deletedAt', 'isShipmentIdExpired'));
                // return config('constants.draft_tab_status.'.$request->shipment_status);
            })
            ->addColumn('action', function($value){
                return view('fba.fba_shipment.action_button',compact('value'));
            })
            ->rawColumns(['action','plan_name']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(FbaShipment $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        $model->select('fba_shipments.id','shipment_plans.plan_name','fba_shipments.shipment_id',
                        'fba_shipments.destination_fulfillment_center_id','shipment_plans.prep_preference',
                        'fba_shipments.shipment_status','fba_shipments.created_at','fba_shipments.deleted_at')
                ->shipmentStatus(NULL)
                ->withCount(['fbaShipmentItems'])
                ->with(['fbaShipmentItems' => function($query){
                    $query->select('id','fba_shipment_id', DB::raw('SUM(quantity) as units'))
                    ->groupBy('fba_shipment_id');
                }])
                ->whereNotNull('fba_shipments.fba_shipment_plan_id')
                ->addSelect(DB::raw('(SELECT SUM(quantity) FROM fba_shipment_items WHERE fba_shipment_id = fba_shipments.id) AS units'));

        $model->leftJoin('shipment_plans','shipment_plans.id','=','fba_shipments.fba_shipment_plan_id');

        if ($request->shipment_status != null)
        {
            if ($request->shipment_status == 0) // Pending Approval
            {
                $model->where('is_shipment_id_expired', 0);
            } else if ($request->shipment_status == 1) { // Shipment id deleted
                $model->onlyTrashed();
            } else if ($request->shipment_status == 2) { // Shipment id deleted
                $model->where('is_shipment_id_expired', 1);
            } else if($request->shipment_status == 3) { // All
                $model->withTrashed();
            }
        } else {
            $model->where('is_shipment_id_expired', 0);
        }
        
        $sortColumn = $request->input('order.0.column');
        // $sort = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy("fba_shipments.created_at", 'DESC');
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('shipment_plans.plan_name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('fba_shipments.shipment_id', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('fba_shipments.destination_fulfillment_center_id', 'LIKE', '%' . $request->search . '%');

                //Search by ASIN in amazon_products
                $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
                    });
                });

                //Search by SKU in amazon_products
                $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
                    });
                });

                //Search by FNSKU in amazon_products
                $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.fnsku', 'LIKE', '%' . $request->search . '%');
                    });
                });
            });
        }

        $shipment_start_date = Carbon::parse($request->shipment_start_date)->startOfDay();
        $shipment_end_date = Carbon::parse($request->shipment_end_date)->endOfDay();
        
        if (!empty($request->shipment_start_date) && !empty($request->shipment_end_date)) {
            $model->whereBetween("fba_shipments.created_at", [$shipment_start_date, $shipment_end_date]);
        } elseif (!empty($request->shipment_start_date)) {
            $model->where("fba_shipments.created_at", '>=', $shipment_start_date);
        } elseif (!empty($request->shipment_end_date)) {
            $model->where("fba_shipments.created_at", '<=', $shipment_end_date);
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
                    d.shipment_status = $.trim($("#shipment_status").val());
                    d.shipment_start_date = $.trim($("#shipment_start_date").val());
                    d.shipment_end_date = $.trim($("#shipment_end_date").val());
                }',
                'beforeSend' => 'function() {
                    // show_loader();
                }',
                'complete' => 'function() {
                   // hide_loader();
                }',
                'error' => 'function (xhr, err) { console.log(err);}',
            ])
            ->columns($this->getColumns($this->getListingFields))
            ->parameters($this->getBuilderParameters($this->getListingFields));
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns($cols = []): array
    {
        $getListingColumns = $this->listingColumns();
        if(!empty($cols))
        {
            $columnList = [];
            foreach ($cols as $value) {
                array_push($columnList, [
                    'data' => $value, 'title' => $getListingColumns[$value], 'orderable' => true
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        }else{
            return [
                ['data' => 'DT_RowIndex', 'title' => $getListingColumns['DT_RowIndex'], 'orderable' => false],
                ['data' => 'plan_name', 'title' => $getListingColumns['plan_name'], 'orderable' => true],
                ['data' => 'shipment_id', 'title' => $getListingColumns['shipment_id'], 'orderable' => true],
                ['data' => 'destination_fulfillment_center_id', 'title' => $getListingColumns['destination_fulfillment_center_id'], 'orderable' => true],
                ['data' => 'prep_preference', 'title' => $getListingColumns['prep_preference'], 'orderable' => true],
                ['data' => 'fba_shipment_items_count', 'title' => $getListingColumns['skus'], 'orderable' => true],
                ['data' => 'units', 'name' => 'units' ,'title' => $getListingColumns['units'], 'orderable' => true],
                ['data' => 'shipment_status', 'title' => $getListingColumns['shipment_status'], 'orderable' => true],
                ['data' => 'created_at', 'title' => $getListingColumns['created_at'], 'orderable' => true],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();
        
        if(!empty($cols))
        {
            $count = count($cols);
            $colCount = array_map(fn($i) => $i, range(0, $count));
            $targets = [...$colCount];
            
        }else{
            $targets = [0,1,2,3,4,5,6,7,8,9];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
            ],
        ];

        return $data;
    }

    // Lising of default column list
    public function listingColumns() : array
    {
        return [
            'DT_RowIndex' => 'ID',
            'plan_name' => 'Plan name',
            'shipment_id' => 'Shipment ID',
            'destination_fulfillment_center_id' => 'Destination ID',
            'prep_preference' => 'Label Type',
            'skus' => 'Skus',
            'units' => 'Units',
            'shipment_status' => 'Shipping Status',
            'created_at' => 'Created at'
        ];
    }
}
