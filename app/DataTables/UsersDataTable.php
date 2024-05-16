<?php

namespace App\DataTables;

use App\Helpers\CommonHelper;
use App\Models\User;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
{
    private $getListingFields = [];

    public function __construct()
    {
        $this->getListingFields = (new CommonService())->getLisingColumns(auth()->user()->id, config('constants.module_name.users'));
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
                return $value->name;
            })
            ->addColumn('status', function ($value) {
                return view('users.status', compact('value'));
            })
            ->editColumn('email', function ($value) {
                return $value->email;
            })
            ->editColumn('created_at', function ($value) {
                return Carbon::parse($value->created_at)->format('d-m-Y');
            })
            ->editColumn('action', function ($value) {
                return view('users.action_button', compact('value'));
            })
            ->rawColumns(['name', 'email', 'status', 'action', 'created_at']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model, Request $request): QueryBuilder
    {
        $model = $model->newQuery();

        if (!empty($this->getListingFields)) {
            $model->select('id', ...($this->getListingFields));
        } else {
            $model->select('id', 'name', 'email', 'status');
        }

        if (!empty($request->search)) {
            $model->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%');
                $query->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->status == "1") {
            $model->where('status', $request->status);
        } elseif ($request->status == "0") {
            $model->where('status', "0");
        }

        $model->whereNotIn('id', [1, auth()->user()->id]);

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
    public function getColumns($cols): array
    {
        if (!empty($cols)) {
            $columnList = [];
            $getListingColumns = $this->listingColumns();
            foreach ($cols as $value) {
                array_push($columnList, [
                    'data' => $value, 'title' => $getListingColumns[$value], 'orderable' => true
                ]);
            }
            return [
                ...$columnList,
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
            ];
        } else {

            return [
                ['data' => 'name', 'title' => 'Name', 'orderable' => true],
                ['data' => 'email', 'title' => 'Email', 'orderable' => true],
                ['data' => 'status', 'title' => 'Status', 'orderable' => true, 'className' => 'text-nowrap text-center'],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'className' => 'text-center'],
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
            'status' => 'Status',
            'created_at' => 'Created At'
        ];
    }

    /**
     * Get the filename for export.
     */
    // protected function filename(): string
    // {
    //     return 'Users_' . date('YmdHis');
    // }
}
