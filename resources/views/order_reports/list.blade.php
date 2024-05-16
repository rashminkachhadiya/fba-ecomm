@extends('layouts.app')
@section('title', 'Order Management')
@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Amazon Order List') }}</li>
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
                    <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter" style="color: #009ef7; display: none;" />
                </div>

            </div>
            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                <x-filters.filter_msg title="Order Status" parent_id="order-status-span" child_id="order-status-data"/>
                <x-filters.filter_msg title="Fulfillment Channel" parent_id="fulfillment-channel-span" child_id="fulfillment-channel-data"/>
                <x-filters.filter_msg title="Store" parent_id="store-span" child_id="store-data"/>
                <x-filters.filter_msg title="Order start at" parent_id="order_start_date_span" child_id="order_start_date_val" />
                <x-filters.filter_msg title="Order end at" parent_id="order_end_date_span" child_id="order_end_date_val" />
            </x-applied-filters>

        </div>

        @php
            $tableId = 'order-table'
        @endphp

        {{ $dataTable->table(['id' => $tableId, 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </x-lists>
    <!-- Filter Box -->
    <x-filters>
        <x-filters.multi-select label="Select Order Status" title="order_status" :options="$orderStatus" />
        <x-filters.list label="Select Fulfillment Channel" title="fulfillment_channel" :options="$fulfillmentChannel" />
        <x-filters.list label="Select Store" title="store" :options="$stores" />
        <x-filters.date_range title="Order Date Range" start_date_name="order_start_date" start_date_id="order_start_date" end_date_name="order_end_date" end_date_id="order_end_date"/>
    </x-filters>

    <!-- Columns list component -->
    <x-table_columns :fields="$listingCols" />
@endsection
@section('page-script')

    {{ $dataTable->scripts() }}

    <script>
        const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('orders-columns-visibility') }}";
        
        $("#order_start_date").daterangepicker({
            singleDatePicker: true,
            autoUpdateInput: false,
            opens: 'left',
            minYear: 2021,
            maxYear: 2032,
            showDropdowns: true,
            "autoApply": true,
            locale: {
                format: "DD-MM-YYYY",
            },
        }).off('focus');

        $("#order_start_date").on("apply.daterangepicker", function (ev, picker) {
            $(this).val(picker.startDate.format("DD-MM-YYYY"));
        });

        $("#order_end_date").daterangepicker({
            singleDatePicker: true,
            autoUpdateInput: false,
            opens: 'left',
            minYear: 2021,
            maxYear: 2032,
            showDropdowns: true,
            "autoApply": true,
            locale: {
                format: "DD-MM-YYYY",
            },
        }).off('focus');

        $("#order_end_date").on("apply.daterangepicker", function (ev, picker) {
            $(this).val(picker.startDate.format("DD-MM-YYYY"));
        });

        const filterList = [
            ['order_status', 'order-status-span', 'order-status-data','multi-select'],
            ['fulfillment_channel', 'fulfillment-channel-span', 'fulfillment-channel-data'],
            ['store', 'store-span', 'store-data'],
            ['order_start_date', 'order_start_date_span', 'order_start_date_val','date'],
            ['order_end_date', 'order_end_date_span', 'order_end_date_val','date']
        ];
    </script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop
