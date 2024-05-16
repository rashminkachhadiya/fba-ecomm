@extends('layouts.app')
@section('title', 'Restock Management')
@section('breadcrumb')
<li class="breadcrumb-item">{{ __('Restock List') }}</li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">

        <div class="row align-items-center gy-3 gx-3 position-relative">

            <x-search-box input_id="search"/>

            <div class="col-sm-auto ms-auto text-right-sm">
                <x-actions.button url="javascript:void(0)" id="column_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-solid fa-table-columns fs-4"></i>
                </x-actions.button>
                <button type="button" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-regular fa-bars-filter fs-4" aria-hidden="true"></i></button>
                <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter"
                    style="color: #009ef7; display: none;" />
            </div>

            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                <x-filters.filter_msg title="Supplier" parent_id="supplier-span" child_id="supplier-data" />
            </x-applied-filters>

        </div>
        @php
            $tableId = 'restock-table'
        @endphp
        {{ $dataTable->table(['id' => 'restock-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </x-lists>
    <!-- Filter Box -->
    <x-filters>
        <x-filters.multi-select title="supplier_filter" :options="$suppliers" label="Select Supplier"/>
    </x-filters>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />
@endsection
@section('page-script')

{{ $dataTable->scripts() }}

    <script>
         const tableId = "{{ $tableId }}";
        const updateColumnVisibilityUrl = "{{ route('restock-columns-visibility') }}";
        const filterList = [
            ['supplier_filter', 'supplier-span', 'supplier-data','multi-select'],
        ];
    </script>
    <script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

@stop