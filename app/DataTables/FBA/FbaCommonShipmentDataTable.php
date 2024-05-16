<?php

namespace App\DataTables\FBA;

use App\Helpers\CommonHelper;
use App\Models\FbaShipment;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class FbaCommonShipmentDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query, Request $request)
    {
        return Datatables::of($query)
            ->addIndexColumn()
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
            ->editColumn('plan_name', function ($value) {
                return view('fba.fba_shipment._plan_name', compact('value'));
            })
            ->editColumn('shipment_status', function ($value) {
                return CommonHelper::returnStatusNameById($value->shipment_status);
            })
            ->editColumn('action', function ($value) {
                return view('fba.fba_shipment.common_shipment_list_action', compact('value'));
            })
            ->rawColumns(['plan_name', 'created_at', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\FbaShipmentAmazon $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FbaShipment $model, Request $request)
    {
        $status = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        if (!empty($request->status)) {
            switch ($request->status) {

                case "shipped":

                    // 2 = Shipped
                    $status = [2];
                    break;

                case "in_transit":

                    // 8 = IN_TRANSIT
                    $status = [8];
                    break;

                case "receiving":

                    // 9 = DELIVERED
                    // 10 = CHECKED_IN
                    // 3 = RECEIVING
                    $status = [9, 10, 3];
                    break;

                case "closed":

                    // 6 = CLOSED
                    $status = [6];
                    break;

                case "cancelled":

                    // 4 = CANCELLED
                    // 5 = DELETED,
                    $status = [4, 5];
                    break;

                case "error":

                    // 7 = ERROR,
                    $status = [7];
                    break;
            }
        }
        $model = $model->newQuery()
            ->whereIn('shipment_status', $status)
            ->select(
                'fba_shipments.id',
                'shipment_id',
                'destination_fulfillment_center_id',
                'label_prep_type',
                'fba_shipments.created_at',
                'fba_shipment_plan_id',
                'shipment_created_from',
                'shipment_plans.plan_name',
                'shipment_name',
                'fba_shipments.is_pallet_label_printed',
                'fba_shipments.no_pallet_label',
                'fba_shipments.is_approved',
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

        $sortColumn = $request->input('order.0.column');
        $sort = $request->input('order.0.dir');
        if (is_null($sortColumn)) {
            $model->orderBy("fba_shipments.created_at", 'DESC');
        }elseif($sortColumn == 7){
            $model->orderBy("fba_shipments.created_at", $sort);
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
            ['data' => 'plan_name', 'name' => 'plan_name', 'title' => 'Shipment Name', 'orderable' => true, "className" => 'text-center', 'width'=>'15px'],
            ['data' => 'shipment_id', 'name' => 'shipment_id', 'title' => 'Shipment ID', 'orderable' => true,  "className" => ' text-center'],
            ['data' => 'destination_fulfillment_center_id', 'name' => 'destination_fulfillment_center_id', 'title' => 'Destination ID', 'orderable' => true,  "className" => 'text-center '],
            ['data' => 'label_prep_type', 'name' => 'label_prep_type', 'title' => 'Label Type', 'orderable' => true,  "className" => ' text-center'],
            ['data' => 'fba_shipment_items_count', 'title' => 'Skus', 'orderable' => true,  "className" => 'text-center'],
            ['data' => 'units', 'title' => 'Units', 'orderable' => true,  "className" => 'text-center'],
            ['data' => 'shipment_status', 'name' => 'shipment_status', 'title' => 'Shipment Status', 'orderable' => true,  "className" => 'text-center'],

            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At', 'orderable' => true, 'className' => 'text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],

        ];
    }

    protected function getBuilderParameters(): array
    {
        $data = CommonHelper::getBuilderParameters();
        $data["columnDefs"] = [
            [
                "targets" => [0, 1, 2, 3],
                "className" => 'text-nowrap'
            ]
        ];
        return $data;
    }
}
