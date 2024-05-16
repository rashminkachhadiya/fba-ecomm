@extends('layouts.app')

@section('title', 'FBA Shipments')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="javascript:void(0)">{{__('Shipments List')}}</a></li>
@endsection

@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

    <!-- Tabs : Start -->
    @include('fba.fba_shipment.shipment_menu_tabs', ['activeTab' => $status])
    <!-- Tabs : End -->

    <!-- Tabs Content : Start -->
    <x-fba_shipment.tab_contant>
        <div class="container-fluid py-5">

            <div class="row align-items-center gy-3 gx-3 position-relative">
                <x-search-box input_id="search" />
                 <!-- Show selected filter in alert warning box -->
             <x-applied-filters>
                <x-filters.filter_msg title="Search" parent_id="search-span" child_id="search-data"/>
             </x-applied-filters>
            </div>
        </div>

            @php
                $tableId = 'shipment-common-data-table'
            @endphp
            {{ $dataTable->table(['id'=>'shipment-common-data-table','class'=>'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7 tab-table'],true) }}
    </x-fba_shipment.tab_contant>
    <!-- Tabs Content : End -->

</div>

@endsection

@section('page-script')
{{ $dataTable->scripts() }}
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
<script>
    const tableId = "{{ $tableId }}"
</script>
@endsection