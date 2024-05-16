<?php

namespace App\DataTables\Prep;

use App\Helpers\CommonHelper;
use App\Models\FbaShipment;
use App\Models\FbaShipmentItem;
use App\Models\FbaPrepDetail;
use App\Models\FbaShipmentNotes;
use DataTables;
use Illuminate\Http\Request;
use DB;
use Yajra\DataTables\Services\DataTable;
use App\Services\CommonService;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;

class FbaPrepDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.fba_prep_list'));
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query, Request $request)
    {
        // To make different border color by PO
        return Datatables::of($query)
            ->addIndexColumn()
            ->editColumn('shipment_name', function ($value) {
                return view('fba.fba_shipment._plan_name', compact('value'));
            })
            ->editColumn('shipment_id', function ($value) {
                return $value->shipment_id;
            })
            ->editColumn('destination_fulfillment_center_id', function ($value) {
                return $value->destination_fulfillment_center_id;
            })
            ->editColumn('shipment_status', function ($value) {
                if ($value->shipment_status == 0) {
                    return 'WORKING';
                }
                return CommonHelper::returnStatusNameById($value->shipment_status);
            })
            ->editColumn('sku', function ($value) {
                return $value->fba_shipment_items_count;
            })
            ->editColumn('units', function ($value) {
                if (!empty($value->fbaShipmentItems)) {
                    return !empty($value->fbaShipmentItems->first()) ? $value->fbaShipmentItems->first()->total_units : '-';
                }
                return '-';
            })
            ->editColumn('prep_status', function ($value) {
                $label =  "-";

                if (is_numeric($value->prep_status)) {
                    switch ($value->prep_status) {
                        case 1:
                            $label = "<span class='fs-6 badge badge-warning text-dark mb-2'> Prep In Progress </span>";
                            break;
                        case 2:
                            $label = "<span class='fs-6 badge bg-success text-white mb-2'> Prep Completed </span>";
                            break;
                        default:
                            $label = "<span class='fs-6 badge badge-danger text-white mb-2'> Prep Pending </span>";
                    }
                }

                return $label;
            })
           
            ->editColumn('progress', function ($value) {

                $totalShippedUnits = FbaShipmentItem::getTotalQtyShippedUnits($value->shipment_id);
                $totalShippedUnits = $totalShippedUnits->total_units;

                $totalDoneUnits = $this->getTotalDoneUnits($value->shipment_id);

                $htm = '';
                $count1 = 0;
                if (!empty($totalShippedUnits)) {
                    $count1 = $totalDoneUnits / $totalShippedUnits;
                }
                $count2 = $count1 * 100;
                $percentage = number_format($count2, 0);

                $htm = '<div class="position-relative">
                    <p class="position-absolute text-center w-100 fw-700 my-1"> '.number_format($totalDoneUnits, 0).'/'.$totalShippedUnits.' </p>
                    <div class="progress border border-secondary h-25px" style="height: 2rem;border:2px solid #e1e1e1;">
                    <div class="progress-bar bg-success" role="progressbar" aria-valuenow="'.$totalDoneUnits.'" aria-valuemin="0" aria-valuemax="'.$totalShippedUnits.'" style="width:'.$percentage.'%"></div>
                    </div>
                    </div>';

                return $htm;
            })
            ->editColumn('action', function ($value) {

                return view('fba_prep.prep_list_action', compact('value'));
            })
            ->rawColumns(['shipment_name', 'shipment_id', 'shipment_status', 'prep_status', 'progress', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\FbaShipmentAmazon $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FbaShipment $model, Request $request)
    {
        $model = $model->newQuery()
            ->whereIn('shipment_status', [0]) 
            ->select(
                'fba_shipments.id',
                'shipment_id',
                'destination_fulfillment_center_id',
                'label_prep_type',
                'fba_shipments.prep_status',
                'fba_shipment_plan_id',
                'shipment_created_from',
                'shipment_plans.plan_name',
                'shipment_name',
                'shipment_status',
                'shipping_schedule_id',
                'added_by_shipping_schedule',
                'is_shipment_appointed'
            )
           
            ->with(['fbaShipmentItems' => function ($query) {
                $query->select('id', 'fba_shipment_id','seller_sku', DB::raw("SUM(quantity_shipped) as total_units"));
                $query->with('amazonData', function ($joinQ) {
                    $joinQ->select(
                        'sku',
                        'asin',
                        'fnsku',
                    );
                });
                $query->groupBy('fba_shipment_id');
            }])
            
            ->addSelect(DB::raw('(SELECT SUM(quantity_shipped) FROM fba_shipment_items WHERE fba_shipment_id = fba_shipments.id) AS units'))

            ->withCount([
                'fbaShipmentItems' => function ($countQ) {
                    $countQ->where('quantity_shipped', '!=', 0);
                }
            ])
            ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipments.fba_shipment_plan_id')
            // ->having('fba_shipment_items_count', '>', 0)
            ->whereHas('fbaShipmentItems', function($q) {
               $q->select(DB::raw('SUM(quantity_shipped) as balance'))
                   ->havingRaw('balance > 0');
            })
            ->orderByRaw('FIELD(prep_status, 1,0,2)');
            
        // Search
        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('shipment_id', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('destination_fulfillment_center_id', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipment_plans.plan_name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('shipment_name', 'LIKE', '%' . $request->search . '%');

                //Search by ASIN in amazon_products
                $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.asin', 'LIKE', '%' . $request->search . '%');
                    });
                });

                //Search by FNSKU in amazon_products
                $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.fnsku', 'LIKE', '%' . $request->search . '%');
                    });
                });

                 //Search by Title in amazon_products
                 $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.title', 'LIKE', '%' . $request->search . '%');
                    });
                });

                 //Search by SKU in amazon_products
                 $query->orWhereHas("fbaShipmentItems", function ($q) use ($request) {
                    $q->whereHas("amazonData", function ($query) use ($request) {
                        $query->where('amazon_products.sku', 'LIKE', '%' . $request->search . '%');
                    });
                });
                
            });
        }

        if ($request->prep_status_filter != null) {
            $prepStatusArr = explode(',', $request->prep_status_filter);
            $model->whereIn('fba_shipments.prep_status', $prepStatusArr);
        }

        if ($request->shipment_status_filter != null) {
            $shipStatusArr = explode(',', $request->shipment_status_filter);
            $model->whereIn('fba_shipments.shipment_status', $shipStatusArr);
        }
        
        $sortColumn = $request->input('order.0.column');
        if (is_null($sortColumn)) {
            $model->orderBy("shipment_id", 'DESC');
        }
        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->ajax([
                'data' => 'function(d) {
                    d.search = $.trim($("#search").val());
                    d.prep_status_filter = $.trim($("#prep_status_filter").val());
                    d.shipment_status_filter = $.trim($("#shipment_status_filter").val());
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
     * Get columns.
     *
     * @return array
     */
    public function getColumns($cols = []): array
    {
        $getListingColumns = $this->listingColumns();
        if(!empty($cols))
        {
            $columnList = [];
            foreach ($cols as $value) {
                array_push($columnList, [
                    'data' => $value, 'title' => $getListingColumns[$value], 'orderable' => ($value == 'progress') ? false : true, 'className' => 'text-center', 'width' => ($value == 'shipment_name') ? '15px' : 'auto',
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        }else{
            return [
                ['data' => 'shipment_name', 'title' => $getListingColumns['shipment_name'], 'orderable' => true, "className" => 'text-center', 'width'=>'15px'],
                ['data' => 'shipment_id',  'title' => $getListingColumns['shipment_id'], 'orderable' => true,  "className" => 'text-center'],
                ['data' => 'destination_fulfillment_center_id', 'title' => $getListingColumns['destination_fulfillment_center_id'], 'orderable' => true,  "className" => 'text-center'],
                ['data' => 'shipment_status', 'title' => $getListingColumns['shipment_status'], 'orderable' => false,'orderable' => true,  "className" => 'text-center'],
                ['data' => 'fba_shipment_items_count',  'title' => $getListingColumns['fba_shipment_items_count'], 'orderable' => true,  "className" => 'text-center'],
                ['data' => 'units', 'title' => $getListingColumns['units'], 'orderable' => true, "className" => 'text-center'],
                ['data' => 'prep_status',  'title' => $getListingColumns['prep_status'], 'orderable' => true,  "className" => 'text-center'],
                ['data' => 'progress',  'title' => $getListingColumns['progress'], 'orderable' => false,  "className" => 'text-center'],
                ['data' => 'action',  'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
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
            $targets = [0,1,2,3,4,5,6,7,8];
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
            'shipment_name' => 'Shipment Name',
            'shipment_id' => 'Shipment ID',
            'destination_fulfillment_center_id' => 'Destination ID',
            'shipment_status' => 'Shipment Status',
            'fba_shipment_items_count' => 'Skus',
            'units' => 'Total Qty',
            'prep_status' => 'Prep Status',
            'progress' => 'Progress'
        ];
    }

    protected function getTotalDoneUnits($shipmentId){
        $totalDoneUnit = 0;
        if(isset($shipmentId) && !empty($shipmentId)){
            $doneUnits = FbaPrepDetail::select( DB::raw("SUM(done_qty) as done_units"))->where('fba_shipment_id', $shipmentId)->first();
            if(!empty($doneUnits)){
                $doneUnits = $doneUnits->toArray();
                $totalDoneUnit = $doneUnits['done_units'];
            }else{
                $totalDoneUnit = 0;
            }
        }
        return $totalDoneUnit;
    }
}
