@extends('layouts.app')
@section('title', 'Order Management')
@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Shopify Order Fulfilment') }}</li>
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
                <x-filters.filter_msg title="Order start at" parent_id="order_start_date_span" child_id="order_start_date_val" />
                <x-filters.filter_msg title="Order end at" parent_id="order_end_date_span" child_id="order_end_date_val" />
                <x-filters.filter_msg title="Min QTY" parent_id="total-min-qty-span" child_id="total-min-qty-data"/>
                <x-filters.filter_msg title="Max QTY" parent_id="total-max-qty-span" child_id="total-max-qty-data"/>
                <x-filters.filter_msg title="Min Order Price" parent_id="total-min-order-price-span" child_id="total-min-order-price-data"/>
                <x-filters.filter_msg title="Max Order Price" parent_id="total-max-order-price-span" child_id="total-max-order-price-data"/>
                <x-filters.filter_msg title="Min Total Price" parent_id="total-min-total-price-span" child_id="total-min-total-price-data"/>
                <x-filters.filter_msg title="Max Total Price" parent_id="total-max-total-price-span" child_id="total-max-total-price-data"/>
                <x-filters.filter_msg title="Min Discount" parent_id="total-min-discount-span" child_id="total-min-discount-data"/>
                <x-filters.filter_msg title="Max Discount" parent_id="total-max-discount-span" child_id="total-max-discount-data"/>
            </x-applied-filters>

        </div>

        @php
            $tableId = 'shopify-order-table'
        @endphp

        {{ $dataTable->table(['id' => $tableId, 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </x-lists>
  
    <!-- Columns list component -->
    <x-table_columns :fields="$listingCols" />

     <!-- Filter Box -->
     <x-filters>
        <x-filters.multi-select label="Select Order Status" title="order_status" :options="$orderStatus" />
        <x-filters.date_range title="Order Date Range" start_date_name="order_start_date" start_date_id="order_start_date" end_date_name="order_end_date" end_date_id="order_end_date"/>
    
        <label class="mt-3">Total Qty</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min QTY" name="total_min_qty" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max QTY" name="total_max_qty" type="number" />
            <span class="error" id="total_max_qty_err" style="color: #F1416C;"></span>
        </div>

        <label>Order Price</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min Order Price" name="total_min_order_price" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max Order Price" name="total_max_order_price" type="number" />
            <span class="error" id="total_max_order_price_err" style="color: #F1416C;"></span>
        </div>

        <label>Total Price</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min Price" name="total_min_total_price" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max Price" name="total_max_total_price" type="number" />
            <span class="error" id="total_max_total_price_err" style="color: #F1416C;"></span>
        </div>

        <label>Total Discount</label>
        <div class="d-flex align-items-baseline">
            <x-filters.input label="Min Discount" name="total_min_discount" type="number" />
            <div class="mx-3">TO</div>
            <x-filters.input label="Max Discount" name="total_max_discount" type="number" />
            <span class="error" id="total_max_discount_err" style="color: #F1416C;"></span>
        </div>
    </x-filters>

@endsection
@section('page-script')

    {{ $dataTable->scripts() }}

    <script>
        const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('shopify-orders-columns-visibility') }}";

        const filterList = [
            ['order_status', 'order-status-span', 'order-status-data','multi-select'],
            ['order_start_date', 'order_start_date_span', 'order_start_date_val','date'],
            ['order_end_date', 'order_end_date_span', 'order_end_date_val','date'],
            ['total_min_qty', 'total-min-qty-span', 'total-min-qty-data', 'number'],
            ['total_max_qty', 'total-max-qty-span', 'total-max-qty-data', 'number', {'validation': {
                'compare_field': 'total_min_qty',
                'message': 'The max qty field must be greater than or equal to min qty.'
            }}],
            ['total_min_order_price', 'total-min-order-price-span', 'total-min-order-price-data', 'number'],
            ['total_max_order_price', 'total-max-order-price-span', 'total-max-order-price-data', 'number', {'validation': {
                'compare_field': 'total_min_order_price',
                'message': 'The max order price must be greater than or equal to min order price.'
            }}],
            ['total_min_total_price', 'total-min-total-price-span', 'total-min-total-price-data', 'number'],
            ['total_max_total_price', 'total-max-total-price-span', 'total-max-total-price-data', 'number', {'validation': {
                'compare_field': 'total_min_total_price',
                'message': 'The max total price must be greater than or equal to min total price.'
            }}],
            ['total_min_discount', 'total-min-discount-span', 'total-min-discount-data', 'number'],
            ['total_max_discount', 'total-max-discount-span', 'total-max-discount-data', 'number', {'validation': {
                'compare_field': 'total_min_discount',
                'message': 'The max discount must be greater than or equal to min discount.'
            }}],
        ];

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

    function saveOrderNote($this, orderID){

        const orderNote = $($this).val();
        const url = $($this).attr('url');
        $.ajax({
            url : url,
            type: 'POST',
            data: { orderNote: orderNote, orderID: orderID },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
    }

    </script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop
