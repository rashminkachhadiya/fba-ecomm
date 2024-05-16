@extends('layouts.app')
@section('title', 'Purchase Order Management')
@section('breadcrumb')
<li class="breadcrumb-item">{{ __('Purchase Order List') }}</li>
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
                <button type="button" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-regular fa-bars-filter fs-4" aria-hidden="true"></i></button>
                <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter" style="color: #009ef7; display: none;" />
            </div>

        </div>
        <!-- Show selected filter in alert warning box -->
        <x-applied-filters>
            <x-filters.filter_msg title="Status" parent_id="is_active_span" child_id="is_active_val" />
            <x-filters.filter_msg title="Search" parent_id="search_span" child_id="search_val" />
            <x-filters.filter_msg title="Supplier" parent_id="sup_span" child_id="suppliers_name" />
            <x-filters.filter_msg title="PO Name" parent_id="po_span" child_id="po_name_val" />
            <x-filters.filter_msg title="SKU" parent_id="sku_span" child_id="sku_val" />
            <x-filters.filter_msg title="ASIN" parent_id="asin_span" child_id="asin_val" />
            <x-filters.filter_msg title="Created start at" parent_id="created_start_date_span" child_id="created_start_date_val" />
            <x-filters.filter_msg title="Created end at" parent_id="created_end_date_span" child_id="created_end_date_val" />
            <x-filters.filter_msg title="Updated start at" parent_id="updated_start_date_span" child_id="updated_start_date_val" />
            <x-filters.filter_msg title="Updated end at" parent_id="updated_end_date_span" child_id="updated_end_date_val" />
        </x-applied-filters>

    </div>

    @php
        $tableId = 'po-table';
    @endphp

    {{ $dataTable->table(['id' => 'po-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
</x-lists>
<!-- Filter Box -->
<x-filters>
    <div class="row">
        <div class="col-sm-6">
            <x-forms.label title="SKU" />
            <x-filters.input label="SKU" name="sku_filter" />
        </div>
        <div class="col-sm-6">
            <x-forms.label title="ASIN" />
            <x-filters.input label="ASIN" name="asin_filter" />
        </div>
    </div>
    <x-forms.label title="PO Name" />
    <x-filters.input label="PO Name" name="po_number_filter" />
    <x-filters.multi-select title="po_status_filter" :options="$statusArr" /><br>
    <x-filters.multi-select title="supplier_filter" :options="$suppliers" label="Select Supplier" />
    <x-filters.date_range title="Created Date Range" start_date_name="created_start_date" start_date_id="created_start_date" end_date_name="created_end_date" end_date_id="created_end_date" />
    <x-filters.date_range title="Updated Date Range" start_date_name="updated_start_date" start_date_id="updated_start_date" end_date_name="updated_end_date" end_date_id="updated_end_date" />

</x-filters>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />

<!-- Shipping Detail Popup -->
<x-modal id="shipping_detail_modal" dialog="modal-md">
    {{ Form::open(['route' => ['update-po-status'], 'name' => 'shipping_detail_form', 'id' => 'shipping_detail_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    <input type="hidden" name="po_id">
    <x-modal.header title="Add shipping details" />

    <x-modal.body style="max-height: 430px; overflow-y: auto;">
        <div class="row">
            <x-forms>
                <div class="col-sm-12">
                    <x-forms.label title="Shipping Date" required="required" />
                    <x-datepicker>
                        {{ Form::text('shipping_date', date('m-d-Y'), ['id' => 'shipping_date', 'class' => 'form-control']) }}
                        <x-datepicker.calendar />
                        {{-- <x-datepicker.reset id="reset_shipping_date" /> --}}
                    </x-datepicker>
                    @error('shipping_date')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
           
                <div class="col-sm-12 mt-3">
                    <x-forms.label title="Shipping Company" />
                    {{ Form::text('shipping_company', !empty(old('shipping_company')) ? old('shipping_company') : null, ['id' => 'shipping_company', 'class' => 'form-control validate', 'placeholder' => 'Shipping Company']) }}
                </div>

                <div class="col-sm-12 mt-3">
                    <x-forms.label title="Shipping ID" />
                    {{ Form::text('shipping_id', !empty(old('shipping_id')) ? old('shipping_id') : null, ['id' => 'shipping_id', 'class' => 'form-control validate', 'placeholder' => 'ShippingID']) }}
                </div>

            </x-forms>
        </div>
    </x-modal.body>

    <x-modal.footer name="Save" id="shipping_detail_submit" type="button" />
    {{ Form::close() }}

</x-modal>

<!-- PO email modal  -->
@include('send_email')
@endsection
@section('page-script')

{{ $dataTable->scripts() }}

    <script>
        const updatePOStatusUrl = "{{ route('update-po-status') }}";
        let url = "{{ route('po_receiving.list', ['poId' => ':poId']) }}";
        let shippingInfoUrl = "{{ route('get-shipping-info', ['poId' => ':poId']) }}";

        const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('po-columns-visibility') }}";
        const filterList = [
            ['sku_filter', 'sku_span', 'sku_val', 'sku-input'],
            ['asin_filter', 'asin_span', 'asin_val', 'asin-input'],
            ['po_number_filter', 'po_span', 'po_name_val', 'po-input'],
            ['po_status_filter', 'is_active_span', 'is_active_val', 'multi-select'],
            ['supplier_filter', 'sup_span', 'suppliers_name', 'multi-select'],
            ['created_start_date', 'created_start_date_span', 'created_start_date_val', 'date'],
            ['created_end_date', 'created_end_date_span', 'created_end_date_val', 'date'],
            ['updated_start_date', 'updated_start_date_span', 'updated_start_date_val', 'date'],
            ['updated_end_date', 'updated_end_date_span', 'updated_end_date_val', 'date']
        ];
    </script>
    <script src="{{ asset('js/purchase_order/form.js') }}" type="text/javascript"></script>
    {!! JsValidator::formRequest('App\Http\Requests\ShippingDetailRequest', '#shipping_detail_form') !!}
    {!! JsValidator::formRequest('App\Http\Requests\POEmailRequest', '#send_email_modal_form') !!}
    <script src="{{ asset('js/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

@stop
