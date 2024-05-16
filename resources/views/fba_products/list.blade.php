@extends('layouts.app')
@section('title', 'FBA Products')
@section('breadcrumb')
    <li class="breadcrumb-item text-primary"><a href="javascript:void(0)">{{ __('FBA Product List') }}</a></li>
@endsection
@section('content')

    <x-lists>
        <div class="container-fluid py-5">

            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <x-bulk-option>
                    @foreach ($stores as $key => $item)
                        <x-bulk-option.bulk_select_option value={{$key}} title={{$item}} />
                    @endforeach
                </x-bulk-option>
     
                <div class="col-sm-auto ms-auto">
                    <x-actions.button url="javascript:void(0)" id="create_fba_shipment" class="btn btn-sm btn-primary"
                        title="Create Shipment Plan" data-url="{{route('fba-products.create')}}">
                        <i class="fa-regular fa-plus"></i>
                    </x-actions.button>
                    <x-actions.button url="javascript:void(0)" id="column_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-solid fa-table-columns fs-4"></i>
                    </x-actions.button>
                    <button type="button" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-regular fa-bars-filter fs-4" aria-hidden="true"></i>
                    </button>
                    <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter" style="color: #009ef7; display: none;" />
                </div>
            </div>
            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                <x-filters.filter_msg title="SKU" parent_id="sku-span" child_id="sku-data" />
                <x-filters.filter_msg title="ASIN" parent_id="asin-span" child_id="asin-data" />
                <x-filters.filter_msg title="FNSKU" parent_id="fnsku-span" child_id="fnsku-data" />
                <x-filters.filter_msg title="Supplier" parent_id="supplier-span" child_id="supplier-data" />
            </x-applied-filters>

        </div>
        <input type="hidden" id="selectAllCheckedRoute" name='selectAllChecked' value="{{route('selectAllChecked')}}">
        @php
            $tableId = 'fba-product-table'
        @endphp
        {{ $dataTable->table(['id' => 'fba-product-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </x-lists>

    <x-filters>
        <div class="row">
            <div class="col-sm-12">
                <x-forms.label title="SKU" />
                <x-filters.input label="SKU" name="sku_filter"/>
            </div>
            <div class="col-sm-12">
                <x-forms.label title="ASIN" />
                <x-filters.input label="ASIN" name="asin_filter"/>
            </div>
            <div class="col-sm-12">
                <x-forms.label title="FNSKU" />
                <x-filters.input label="FNSKU" name="fnsku_filter"/>
            </div>
        </div>
        <x-filters.multi-select title="supplier_filter" :options="$suppliers" label="Select Supplier" />

    </x-filters>

     <!-- Columns list component -->
     <x-table_columns :fields="$listingCols" />

    @endsection
@section('page-script')

    {{ $dataTable->scripts() }}

    <script>
        const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('fba-products-columns-visibility') }}";
        const filterList = [
            ['supplier_filter', 'supplier-span', 'supplier-data','multi-select'],
            ['sku_filter', 'sku-span', 'sku-data', 'sku-input'],
            ['asin_filter', 'asin-span', 'asin-data', 'asin-input'],
            ['fnsku_filter', 'fnsku-span', 'fnsku-data', 'fnsku-input'],

        ];
        $(document).on('click', '.moredata-link', function(){
            $(this).closest('.multidata-td').toggleClass('d-filter-show-hidebox');

            var toggleClass = $(this).closest('.multidata-td');

            $(document).mouseup(function(event) {

                var hideBox = $(".moredata-link");
                if (!hideBox.is(event.target) && hideBox.has(event.target).length === 0)
                {
                    toggleClass.removeClass('d-filter-show-hidebox');
                }
            });
        });

        function copyFnskuToClipboardButton(copyText) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("FNSKU copied");
        }

        $(document).on('mouseenter', '.suggested_shipment_qty', function () {
            // Get the new tooltip content based on the header's text or any other criteria
            const newTooltipContent = '((Target qty on hands days + Local Lead Time) * 30 days ROS) - Current Amazon inventory';
            // Update the title attribute with the new content
            $(this).attr('title', newTooltipContent);
        })
    </script>
<script src="{{ asset('js/fba_products/form.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

@stop
