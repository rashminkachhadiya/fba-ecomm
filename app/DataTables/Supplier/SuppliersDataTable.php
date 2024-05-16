<?php

namespace App\DataTables\Supplier;

use App\Helpers\CommonHelper;
use App\Models\Supplier;
use App\Services\CommonService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class SuppliersDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.suppliers'));
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return Datatables::of($query)
            ->setTotalRecords($query->count('id'))
            ->addIndexColumn()
            ->editColumn('name', function ($value) {
                return ucfirst($value->name);
            })
            ->editColumn('account_number', function ($value) {
                return $value->account_number;
            })
            ->editColumn('email', function ($value) {
                return $value->email;
            })
            ->editColumn('url', function ($value) {
                $url = trim($value->url);
                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                    $url = "http://" . $url;
                }else{
                    $url = $url;
                }
                return '<a href="' . $url . '" target="_blank" class="link-class">' . $value->url . '</a>';
            })
            ->editColumn('phone_number', function ($value) {
                return $value->phone_number;
            })
            ->editColumn('address', function ($value) {
                return $value->address;
            })
            ->editColumn('lead_time', function ($value) {
                return $value->lead_time;
            })
            ->addColumn('status', function ($value) {
                return view('suppliers.status', compact('value'));
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('supplier_products_count', function ($value) {
                $productCount = !empty($value->supplier_products_count) ? $value->supplier_products_count : '0';
                return '<a href="'.route('supplier_products.index').'?supplierId='.$value->id.'" class="link-class text-center">'.$productCount.'</a>';
            })
            ->editColumn('action', function ($value) {
                return view('suppliers.action_button', compact('value'));
            })
            ->rawColumns(['name', 'account_number', 'phone_number', 'email', 'url', 'address', 'lead_time', 'status', 'action', 'created_at','supplier_products_count']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Supplier $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        
        if (!empty($this->getListingFields)) {
            $model->select('id', ...($this->getListingFields));
        } else {
            $model->select('id', 'name', 'account_number', 'phone_number', 'email', 'url', 'address', 'lead_time', 'status');
        }

        $model->withCount('supplierProducts');

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('email', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('url', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('account_number', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('phone_number', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('address', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('lead_time', 'LIKE', '%' . $request->search . '%');

            });
        }

        if ($request->status == "1") {
            $model->where('status', $request->status);
        } elseif ($request->status == "0") {
            $model->where('status', "0");
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
            foreach ($cols as $value) {
                if($value == 'status'){
                    array_push($columnList, 
                    ['data' => 'supplier_products_count', 'name' => 'supplier_products_count', 'title' => 'Associated Products', 'orderable' => true, 'className' => 'text-center'],
                    ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At', 'orderable' => true],
                    ['data' => $value, 'title' => $getListingColumns[$value], 'orderable' => true,
                    ]);
                }else{
                    array_push($columnList, [
                        'data' => $value, 'title' => $getListingColumns[$value], 'orderable' => true,
                    ]);
                }
            }
            return [
                ...$columnList,
                // ['data' => 'supplier_products_count', 'name' => 'supplier_products_count', 'title' => 'Associted Products', 'orderable' => true],
                // ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At', 'orderable' => true],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {
            return [
                ['data' => 'name', 'name' => 'name', 'title' => 'Name', 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'title' => 'Email', 'orderable' => true],
                ['data' => 'url', 'name' => 'url', 'title' => 'Website', 'orderable' => true],
                ['data' => 'account_number', 'name' => 'account_number', 'title' => 'Account Number', 'orderable' => true],
                ['data' => 'phone_number', 'name' => 'phone_number', 'title' => 'Phone Number', 'orderable' => true],
                ['data' => 'address', 'name' => 'address', 'title' => 'Address', 'orderable' => true],
                ['data' => 'lead_time', 'name' => 'lead_time', 'title' => 'Lead Time', 'orderable' => true],
                ['data' => 'supplier_products_count', 'name' => 'supplier_products_count', 'title' => 'Associated Products', 'orderable' => true, 'className' => 'text-center'],
                ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At', 'orderable' => true],
                ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => false, "visible"=>true, 'className' => 'text-nowrap'],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();

        if (!empty($cols)) {
            $count = count($cols);
            $colCount = array_map(fn($i) => $i, range(0, $count - 1));
            $targets = [...$colCount];

        } else {
            $targets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        }

        $data["columnDefs"] = [
            [
                "targets" => $targets,
                "className" => 'text-nowrap',
            ],
        ];

        // $data['order'] = [
        //     [8,'desc']
        // ];

        return $data;
    }
    
    // Lising of default column list
    public function listingColumns(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'url' => 'Website',
            'account_number' => 'Account Number',
            'phone_number' => 'Phone Number',
            'address' => 'Address',
            'lead_time' => 'Lead Time',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

}
