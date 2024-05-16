<?php

namespace App\DataTables\Supplier;

use App\Helpers\CommonHelper;
use App\Models\SupplierContact;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class ContactsDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.supplier_contacts'));
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
                $is_default = SupplierContact::where('id', $value->id)->where('is_default', '1')->first();
                if(!empty($is_default)){
                    return ucfirst($value->name).' <span class="badge badge-success">Default</span>';
                }else{
                    return ucfirst($value->name);
                }
            })
            ->editColumn('email', function ($value) {
                return $value->email;
            })
            ->editColumn('phone_number', function ($value) {
                return $value->phone_number;
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('Y-m-d');
            })
            ->editColumn('action', function ($value) {
                return view('suppliers.contacts.action_button', compact('value'));
            })
            ->rawColumns(['name', 'phone_number', 'email', 'action', 'created_at']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SupplierContact $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery()->where('supplier_id', $request->supplierId);

        if (!empty($this->getListingFields)) {
            $model->select('id', ...($this->getListingFields));
        } else {
            $model->select('id', 'supplier_id', 'name', 'phone_number', 'email', 'created_at', 'is_default');
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('email', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('phone_number', 'LIKE', '%' . $request->search . '%');

            });
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
                array_push($columnList, [
                    'data' => $value, 'title' => $getListingColumns[$value], 'orderable' => true,
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {
            return [
                ['data' => 'name', 'name' => 'name', 'title' => 'Name', 'orderable' => true],
                ['data' => 'email', 'name' => 'email', 'title' => 'Email', 'orderable' => true],
                ['data' => 'phone_number', 'name' => 'phone_number', 'title' => 'Phone Number', 'orderable' => true],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        }
    }

    protected function getBuilderParameters($cols = []): array
    {
        $data = CommonHelper::getBuilderParameters();

        if (!empty($cols)) {
            $count = count($cols);
            $colCount = array_map(fn($i) => $i, range(0, $count));
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
            'name' => 'Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'created_at' => 'Created At',
        ];
    }

}
