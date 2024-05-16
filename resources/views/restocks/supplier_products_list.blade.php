@extends('layouts.app')
@section('title', 'Restock Management')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('restocks.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Restock Suppliers') }}</a></li>
{{-- <li class="breadcrumb-item text-primary"><a href="javascript:void(0)">{{ __('Supplier Product List') }}</a></li> --}}
<li class="breadcrumb-item">{{ $supplier->name }}</li>

@endsection
@section('content')

<x-lists>

    <div class="container-fluid py-5">

        <div class="row align-items-center gy-3 gx-3 position-relative">

            <x-search-box input_id="search" />

            <x-actions.button url="javascript:void(0)" class="col-sm-2 ms-5 btn btn-sm btn-link">
                <x-forms.select name="supplier" id="supplier" class="form-select-sm">
                    @foreach ($suppliers as $key => $supplierData)
                        <x-forms.select-options value="{{ $key }}" title="{{ $supplierData }}" />
                    @endforeach
                </x-forms.select>
            </x-actions.button>

            <div class="col-sm-auto ms-auto text-right-sm">

                <x-actions.button url="javascript:void(0)" id="add_purchase_order" class="btn btn-sm btn-primary"
                    title="Create PO">
                    <i class="fa-regular fa-plus"></i>
                </x-actions.button>

                <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-regular fa-bars-filter fs-4"></i>
                </x-actions.button>
            </div>
            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                {{-- <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" /> --}}
                <x-filters.filter_msg title="Min FBA Qty" parent_id="min_fba_qty_span" child_id="min_fba_qty_data" />
                <x-filters.filter_msg title="Max FBA Qty" parent_id="max_fba_qty_span" child_id="max_fba_qty_data" />
                <x-filters.filter_msg title="Min Inbound Qty" parent_id="min_inbound_qty_span" child_id="min_inbound_qty_data" />
                <x-filters.filter_msg title="Max Inbound Qty" parent_id="max_inbound_qty_span" child_id="max_inbound_qty_data" />
                <x-filters.filter_msg title="Min Suggested Qty" parent_id="min_suggested_qty_span" child_id="min_suggested_qty_data" />
                <x-filters.filter_msg title="Max Suggested Qty" parent_id="max_suggested_qty_span" child_id="max_suggested_qty_data" />
                <x-filters.filter_msg title="Min Unit Price" parent_id="min_price_span" child_id="min_price_data" />
                <x-filters.filter_msg title="Max Unit Price" parent_id="max_price_span" child_id="max_price_data" />
                <x-filters.filter_msg title="Min Buy box Price" parent_id="min_buybox_price_span" child_id="min_buybox_price_data" />
                <x-filters.filter_msg title="Max Buy box Price" parent_id="max_buybox_price_span" child_id="max_buybox_price_data" />
                <x-filters.filter_msg title="Min Selling Price" parent_id="min_selling_price_span" child_id="min_selling_price_data" />
                <x-filters.filter_msg title="Max Selling Price" parent_id="max_selling_price_span" child_id="max_selling_price_data" />

                <x-filters.filter_msg title="Hazmat" parent_id="hazmat_span" child_id="hazmat_data" />
                <x-filters.filter_msg title="Oversize" parent_id="oversize_span" child_id="oversize_data" />
            </x-applied-filters>

        </div>
        <!-- Show selected filter in alert warning box -->
        <x-applied-filters>
            <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" />
        </x-applied-filters>

    </div>
    @php
    $tableId = 'restock-product-table';
    @endphp
    {{ Form::open(['route' => ['update-order-qty', ['supplier_id' => $supplier_id]], 'name' => 'update_order_qty', 'id' => 'update_order_qty', 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    {{ $dataTable->table(['id' => 'restock-product-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    <input type="hidden" name="supplier_products_id" id="supplier_products_id" value="">
    <input type="hidden" name="sum_of_order_qty" id="sum_of_order_qty" value="{{ $sumOfOrderQty }}">
    {{ Form::close() }}

</x-lists>

<!-- Filter Box -->
<x-filters>
    {{-- FBA Qty filter --}}
    <label>FBA Qty</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min FBA Qty" name="min_fba_qty" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max FBA Qty" name="max_fba_qty" type="number" />
        <span class="error" id="max_fba_qty_err" style="color: #F1416C;"></span>
    </div>

    {{-- Inbound FBA Qty filter --}}
    <label>Inbound FBA Qty</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Inbound Qty" name="min_inbound_qty" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Inbound Qty" name="max_inbound_qty" type="number" />
        <span class="error" id="max_inbound_qty_err" style="color: #F1416C;"></span>
    </div>

    {{-- Suggested Reorder Qty filter --}}
    <label>Suggested Reorder Qty</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Suggested Reorder Qty" name="min_suggested_qty" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Suggested Reorder Qty" name="max_suggested_qty" type="number" />
        <span class="error" id="max_suggested_qty_err" style="color: #F1416C;"></span>
    </div>

    {{-- Unit Price filter --}}
    <label>Unit Price</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Unit Price" name="min_price" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Unit Price" name="max_price" type="number" />
        <span class="error" id="max_price_err" style="color: #F1416C;"></span>
    </div>

    {{-- Profit / Margin (Buy box price) filter --}}
    <label>Profit / Margin (Buy box price)</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Buy box Price" name="min_buybox_price" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Buy box Price" name="max_buybox_price" type="number" />
        <span class="error" id="max_buybox_price_err" style="color: #F1416C;"></span>
    </div>

    {{-- Profit / Margin (Selling price) filter --}}
    <label>Profit / Margin (Selling price)</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Selling Price" name="min_selling_price" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Selling Price" name="max_selling_price" type="number" />
        <span class="error" id="max_selling_price_err" style="color: #F1416C;"></span>
    </div>

    <x-filters.list title="is_hazmat" label="Select Hazmat" :options="[1 => 'Yes', 0 => 'No']" />
    <x-filters.list title="is_oversize" label="Select Oversize" :options="[1 => 'Yes', 0 => 'No']" />
</x-filters>

@include('restocks.add_purchase_order_modal')

@endsection

@section('page-script')

{{ $dataTable->scripts() }}
<script src="{{ asset('js/restocks/form.js') }}" type="text/javascript"></script>
{!! JsValidator::formRequest('App\Http\Requests\SupplierProductsRequest', '#update_order_qty') !!}
{!! JsValidator::formRequest('App\Http\Requests\RestockRequest', '#add_new_po_form') !!}

    

    <script>
        const tableId = "{{ $tableId }}";
        const filterList = [
            ['min_fba_qty', 'min_fba_qty_span', 'min_fba_qty_data','number'],
            ['max_fba_qty', 'max_fba_qty_span', 'max_fba_qty_data','number', {'validation': {
                'compare_field': 'min_fba_qty',
                'message': 'The max FBA Qty field must be greater than or equal to min FBA Qty.'
            }}],
            ['min_inbound_qty', 'min_inbound_qty_span', 'min_inbound_qty_data','number'],
            ['max_inbound_qty', 'max_inbound_qty_span', 'max_inbound_qty_data','number', {'validation': {
                'compare_field': 'min_inbound_qty',
                'message': 'The max Inbound FBA Qty field must be greater than or equal to min Inbound FBA Qty.'
            }}],
            ['min_suggested_qty', 'min_suggested_qty_span', 'min_suggested_qty_data','number'],
            ['max_suggested_qty', 'max_suggested_qty_span', 'max_suggested_qty_data','number', {'validation': {
                'compare_field': 'min_suggested_qty',
                'message': 'The max Suggested Reorder Qty field must be greater than or equal to min Suggested Reorder Qty.'
            }}],
            ['min_price', 'min_price_span', 'min_price_data','number'],
            ['max_price', 'max_price_span', 'max_price_data','number', {'validation': {
                'compare_field': 'min_price',
                'message': 'The max Unit Price field must be greater than or equal to min Unit Price.'
            }}],
            ['min_buybox_price', 'min_buybox_price_span', 'min_buybox_price_data','number'],
            ['max_buybox_price', 'max_buybox_price_span', 'max_buybox_price_data','number', {'validation': {
                'compare_field': 'min_buybox_price',
                'message': 'The max Buy box price field must be greater than or equal to min Buy box price.'
            }}],
            ['min_selling_price', 'min_selling_price_span', 'min_selling_price_data','number'],
            ['max_selling_price', 'max_selling_price_span', 'max_selling_price_data','number', {'validation': {
                'compare_field': 'min_selling_price',
                'message': 'The max Selling price field must be greater than or equal to min Selling price.'
            }}],
            ['is_hazmat', 'hazmat_span', 'hazmat_data'],
            ['is_oversize', 'oversize_span', 'oversize_data'],
        ]
    </script>

    <script>
        $(document).ready(function() {
            $('#supplier').val("{{ $supplier->id }}").trigger('change');

            var totalOrderQty = $('#sum_of_order_qty').val();
            if (totalOrderQty <= 0) {
                $('#add_purchase_order').addClass('disabled');
            } else {
                $('#add_purchase_order').removeClass('disabled');
            }

            $('.suggested_quantity_class').hover(function() {
                // Get the new tooltip content based on the header's text or any other criteria
                const newTooltipContent = '(Days of Stock Holding + Total Lead Time) * 30 days ROS - Qty in Amazon Warehouse - Qty in Purchase Order';
                // Update the title attribute with the new content
                $(this).attr('title', newTooltipContent);
            })

            $(document).on('click', '.moredata-link', function() {
                $(this).closest('.multidata-td').toggleClass('d-filter-show-hidebox');
                if ($(this).parent().parent().attr('class') == 'row selling_price_profit') {
                    $('.buybox_price').css('display', 'none');
                    $('.buybox_referral_fees').css('display', 'none');
                    $('.selling_price').css('display', 'flex');
                    $('.referral_fee').css('display', 'flex');
                } else if ($(this).parent().parent().attr('class') == 'row buybox_price_profit') {
                    $('.selling_price').css('display', 'none');
                    $('.referral_fee').css('display', 'none');
                    $('.buybox_price').css('display', 'flex');
                    $('.buybox_referral_fees').css('display', 'flex');
                }

                var toggleClass = $(this).closest('.multidata-td');

                $(document).mouseup(function(event) {
                    //alert("hi");
                    var hideBox = $(".moredata-link");
                    if (!hideBox.is(event.target) && hideBox.has(event.target).length === 0) {
                        toggleClass.removeClass('d-filter-show-hidebox');
                    }
                });
            });

            $(document).on('change', '#supplier', function(){
                if($(this).val() && $(this).val() != 'undefined')
                {
                    let redirectUrl = "{{ route('restock-supplier-products', ['supplier_id' => ':supplier_id']) }}";
                    redirectUrl = redirectUrl.replace(':supplier_id', $(this).val());
                    window.location.href = redirectUrl;
                }
            })
        });
    </script>
    <script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
    {{-- {!! JsValidator::formRequest('App\Http\Requests\RestockProductFilterRequest', '#advance-filter', ['method' => 'GET']) !!} --}}
@stop
