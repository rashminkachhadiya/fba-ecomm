<?php

namespace App\DataTables\FBA;

use App\Helpers\CommonHelper;
use App\Models\FbaShipment;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class FbaWorkingShipmentDataTable extends DataTable
{
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
            ->editColumn('label_prep_type', function ($value) {
                $label =  "-";

                if (is_numeric($value->label_prep_type)) {
                    switch ($value->label_prep_type) {
                        case 1:
                            $label = "SELLER_LABEL";
                            break;
                        case 2:
                            $label = "AMAZON_LABEL";
                            break;
                        default:
                            $label = "NO_LABEL";
                    }
                }

                return $label;
            })
            ->editColumn('box_contents_source', function ($value) {
                $label =  "-";

                if (is_numeric($value->box_contents_source)) {
                    switch ($value->box_contents_source) {
                        case 0:
                            $label = "NONE";
                            break;
                        case 1:
                            $label = "FEED";
                            break;
                        case 2:
                            $label = "2D_BARCODE";
                            break;
                        case 3:
                            $label = "INTERACTIVE";
                            break;
                        default:
                            $label = "-";
                    }
                }

                return $label;
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
            ->addColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y H:i');
            })
            ->editColumn('shipment_status', function ($value) {
                return 'Working';
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
            ->editColumn('action', function ($value) {
                return view('fba.fba_shipment.working_shipment_list_action', compact('value'));
            })
            ->rawColumns(['shipment_name', 'prep_status', 'action']);
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
            ->where('shipment_status', 0)
            ->select(
                'fba_shipments.id',
                'shipment_id',
                'destination_fulfillment_center_id',
                'label_prep_type',
                'fba_shipments.created_at',
                'fba_shipment_plan_id',
                'shipment_created_from',
                'shipment_plans.plan_name',
                'fba_shipments.shipment_name',
                'fba_shipments.is_pallet_label_printed',
                'fba_shipments.no_pallet_label',
                'fba_shipments.is_approved',
                'fba_shipments.box_contents_source',
                'fba_shipments.prep_status',
                'fba_shipments.shipment_status'
            )
            ->with(['fbaShipmentItems' => function ($query) {
                $query->select('id', 'fba_shipment_id', DB::raw("SUM(quantity_shipped) as total_units"));
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

            ->withCount('fbaShipmentItems')
            ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipments.fba_shipment_plan_id');

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

        $model->when((request('total_min_sku') || request('total_max_sku')), function($query){

            if((request('total_min_sku') || request('total_min_sku') == "0") && (request('total_max_sku') || request('total_max_sku') == "0")){
                $query->havingRaw('fba_shipment_items_count >= ? AND fba_shipment_items_count <= ?', [request('total_min_sku'), request('total_max_sku')]);
            }
            if(request('total_min_sku'))
            {
                $query->havingRaw('fba_shipment_items_count >= ?', [request('total_min_sku')]);
            }

            if(request('total_max_sku'))
            {
                $query->havingRaw('fba_shipment_items_count <= ?', [request('total_max_sku')]);
            }
        });

        $model->when((request('total_min_sellable_unit') || request('total_max_sellable_unit')), function($query){

            if((request('total_min_sellable_unit') || request('total_min_sellable_unit') == "0") && (request('total_max_sellable_unit') || request('total_max_sellable_unit') == "0")){
                $query->havingRaw('units >= ? AND units <= ?', [request('total_min_sellable_unit'), request('total_max_sellable_unit')]);
            }
            if(request('total_min_sellable_unit'))
            {
                $query->havingRaw('units >= ?', [request('total_min_sellable_unit')]);
            }

            if(request('total_max_sellable_unit'))
            {
                $query->havingRaw('units <= ?', [request('total_max_sellable_unit')]);
            }
        });

        $sortColumn = $request->input('order.0.column');
        $sort = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy("fba_shipments.created_at", 'DESC');
        }elseif($sortColumn == 9){
            $model->orderBy("fba_shipments.created_at", $sort);
        }

        if (!empty($request->prep_status)) {
            $searchStatus = explode(",",request('prep_status'));
            $model->whereIn("fba_shipments.prep_status", $searchStatus);
        }

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {

        return $this->builder()
            ->ajax([
                'data' => 'function(d) {
                    d.search = $.trim($("#search").val());
                    d.prep_status = $.trim($("#prep_status").val());
                    d.total_min_sku = $.trim($("#total_min_sku").val());
                    d.total_max_sku = $.trim($("#total_max_sku").val());
                    d.total_min_sellable_unit = $.trim($("#total_min_sellable_unit").val());
                    d.total_max_sellable_unit = $.trim($("#total_max_sellable_unit").val());
                }',
                'beforeSend' => 'function() {
                    show_loader();
                }',
                'complete' => 'function() {
                    hide_loader();
                }',
                'error' => 'function (xhr, err) { }'
            ])
            ->columns($this->getColumns())
            ->parameters($this->getBuilderParameters());
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['data' => 'shipment_name', 'name' => 'shipment_name', 'title' => 'Shipment Name', 'orderable' => true, "className" => 'text-center', 'width'=>'15px'],
            ['data' => 'shipment_id', 'name' => 'shipment_id', 'title' => 'Shipment ID', 'orderable' => true,  "className" => ' text-center'],
            ['data' => 'destination_fulfillment_center_id', 'name' => 'destination_fulfillment_center_id', 'title' => 'Destination ID', 'orderable' => true,  "className" => 'text-center '],
            ['data' => 'label_prep_type', 'name' => 'label_prep_type', 'title' => 'Label Type', 'orderable' => true,  "className" => ' text-center'],
            ['data' => 'box_contents_source', 'name' => 'box_contents_source', 'title' => 'Box Content Source', 'orderable' => true,  "className" => ' text-center'],

            ['data' => 'fba_shipment_items_count', 'title' => 'Skus', 'orderable' => false,  "className" => 'text-center', 'orderable' => true],
            ['data' => 'units', 'title' => 'Total Sellable unit', 'orderable' => false,  "className" => 'text-center', 'orderable' => true],
            ['data' => 'shipment_status', 'name' => 'shipment_status', 'title' => 'Shipment Status', 'orderable' => true,  "className" => 'text-center'],
            ['data' => 'prep_status', 'name' => 'prep_status', 'title' => 'Prep Status', 'orderable' => true,  "className" => 'text-center'],

            ['data' => 'created_at', 'title' => 'Created At', 'orderable' => true, 'className' => 'text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],

        ];
    }

    protected function getBuilderParameters() : array
    {
        $data = CommonHelper::getBuilderParameters();
        $data["columnDefs"] = [
            [
                "targets" => [0,1,2,3,4,5,6,7,8,9,10],
                "className" => 'text-nowrap'
            ]
        ];
        return $data;
    }
}
