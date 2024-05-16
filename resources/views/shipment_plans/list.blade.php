@extends('layouts.app')
@section('title', 'FBA Shipment Plan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="javascript:void(0)">Shipment Plan List</a></li>
@endsection
@section('content')

    <x-lists>
        <div class="container-fluid py-5">

            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="column_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-solid fa-table-columns fs-4"></i>
                    </x-actions.button>
                    <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-regular fa-bars-filter fs-4"></i>
                    </x-actions.button>

                </div>
            </div>
            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                <x-filters.filter_msg title="Status" parent_id="plan-status-span" child_id="plan-status-data"/>
                <x-filters.filter_msg title="Min SKU" parent_id="total-min-sku-span" child_id="total-min-sku-data"/>
                <x-filters.filter_msg title="Max SKU" parent_id="total-max-sku-span" child_id="total-max-sku-data"/>
                <x-filters.filter_msg title="Min Sellable unit" parent_id="total-min-sellable-unit-span" child_id="total-min-sellable-unit-data"/>
                <x-filters.filter_msg title="Max Sellable unit" parent_id="total-max-sellable-unit-span" child_id="total-max-sellable-unit-data"/>
                <x-filters.filter_msg title="Plan start at" parent_id="plan_start_date_span" child_id="plan_start_date_val" />
                <x-filters.filter_msg title="Plan end at" parent_id="plan_end_date_span" child_id="plan_end_date_val" />
            </x-applied-filters>

        </div>

        @php
            $tableId = 'fba-plan-table'
        @endphp
        {{ $dataTable->table(['id' => 'fba-plan-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </x-lists>

    <!-- Filter Box -->
    <x-filters>
        <x-filters.multi-select label="Select Plan Status" title="plan_status" :options="$statusAttr" />
        <label>Total SKUs</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min SKUs" name="total_min_sku" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max SKUs" name="total_max_sku" type="number" />
            <span class="error" id="total_max_sku_err" style="color: #F1416C;"></span>
        </div>

        <label>Total Sellable Units</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min Sellable Units" name="total_min_sellable_unit" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max Sellable Units" name="total_max_sellable_unit" type="number" />
            <span class="error" id="total_max_sellable_unit_err" style="color: #F1416C;"></span>
        </div>

    <x-filters.date_range title="Plan Created Date Range" start_date_name="plan_start_date" start_date_id="plan_start_date" end_date_name="plan_end_date" end_date_id="plan_end_date"/>
    </x-filters>

    <!-- Columns list component -->
    <x-table_columns :fields="$listingCols" />

    <div class="modal fade" id="planApiErrorResponseModal" aria-labelledby="planApiErrorResponseModalLabel" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Creation of draft shipment Alert</h5>
                    <button type="button" class="btn-close stock-modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
    
                <div class="modal-body" style="height: 550px; overflow-y: auto; overflow-x: hidden;">
                    <input type="hidden" name="shipment_plan_id" id="shipment_plan_id">
                    <div class="mb-5">
                        <span id="code">Code: </span><span id="error_code"></span>
                    </div>
                    <div class="mb-5">
                        <span id="message">Message: </span><span id="error_message"></span>
                    </div>
                    <!-- <div class="mb-5">
                        <span id="reason">Reason: </span><span id="error_reson"></span>
                    </div> -->
                    <div id="sku_table_div" style="display: none;">
                        <table class="table-responsive w-100" id="sku_table">
                            <thead>
                                <th scope="col">SKU</th>
                                <th scope="col">Sellable Asin Qty</th>
                                <th scope="col">ASIN</th>
                                <th scope="col">Reason</th>
                                {{-- <th scope="col">Action</th> --}}
                            </thead>
                            <tbody id="sku_table_body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page-script')

    {{ $dataTable->scripts() }}
    <script>
        const redirectUrl = "{{ route('fba-shipments.index') }}"
    </script>
    <script src="{{ asset('js/fba_plans/form.js') }}" type="text/javascript"></script>

    <script>
        const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('fba-plan-columns-visibility') }}";
        const filterList = [
            ['plan_status', 'plan-status-span', 'plan-status-data','multi-select'],
            ['total_min_sku', 'total-min-sku-span', 'total-min-sku-data', 'number'],
            ['total_max_sku', 'total-max-sku-span', 'total-max-sku-data', 'number', {'validation': {
                'compare_field': 'total_min_sku',
                'message': 'The max sku field must be greater than or equal to min sku.'
            }}],
            ['total_min_sellable_unit', 'total-min-sellable-unit-span', 'total-min-sellable-unit-data', 'number'],
            ['total_max_sellable_unit', 'total-max-sellable-unit-span', 'total-max-sellable-unit-data', 'number', {'validation': {
                'compare_field': 'total_min_sellable_unit',
                'message': 'The  max sellable unit must be greater than or equal to min sellable unit.'
            }}],
            ['plan_start_date', 'plan_start_date_span', 'plan_start_date_val','date'],
            ['plan_end_date', 'plan_end_date_span', 'plan_end_date_val','date'],
        ];
    </script>

    <script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop
