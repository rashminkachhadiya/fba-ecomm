@extends('layouts.app')
@section('title', 'User Management')
@section('breadcrumb')
<li class="breadcrumb-item">{{ __('User List') }}</li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">

        <div class="row align-items-center gy-3 gx-3 position-relative">

            <x-search-box input_id="search" />

            <div class="col-sm-auto ms-auto text-right-sm">
                <x-actions.button :url="route('users.create')" class="btn btn-sm btn-primary" title="Add User">
                    <i class="fa-regular fa-plus"></i>
                </x-actions.button>
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
            <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" />
        </x-applied-filters>

    </div>

    @php
    $tableId = 'user-table'
    @endphp
    {{ $dataTable->table(['id' => $tableId, 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
</x-lists>
<!-- Filter Box -->
<x-filters>
    <x-filters.list title="status" :options="$statusArr" />
</x-filters>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />
@endsection
@section('page-script')

{{ $dataTable->scripts() }}
<script src="{{ asset('js/users/form.js') }}" type="text/javascript"></script>

<script>
    const tableId = "{{ $tableId }}";
    const updateColumnVisibilityUrl = "{{ route('users-columns-visibility') }}";
    const filterList = [
        ['status', 'status-span', 'status-data']
    ];
</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop