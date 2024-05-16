@extends('layouts.app')

@section('title', 'FBA Shipments')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="javascript:void(0)">{{__('Shipments List')}}</a></li>
@endsection

@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

    <!-- Tabs : Start -->
    @include('fba.fba_shipment.shipment_menu_tabs', ['activeTab' => 'working'])
    <!-- Tabs : End -->

    <!-- Tabs Content : Start -->
    <x-fba_shipment.tab_contant>
        <div class="container-fluid py-5">

            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <div class="col col-xl-6 align-items-center d-flex flex-fill justify-content-end border-end border-secondary pe-4">

                    <strong class="float-end me-3">{{ !empty($latestFbaShipment->updated_at) ? 'Last Synced: ' . Carbon\Carbon::parse($latestFbaShipment->updated_at)->format('d-m-Y H:i:s') : ''}}</strong>

                    <a href="javascript:void(0)" id="shipment_reverse_sync_btn" class="btn btn-sm btn-success bg-success float-end" title="Sync shipments from Amazon"><i class="fa fa-refresh" aria-hidden="true"></i> Sync Shipment</a><br>
                   
                </div>

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-regular fa-bars-filter fs-4"></i>
                    </x-actions.button>

                </div>
            </div>

             <!-- Show selected filter in alert warning box -->
             <x-applied-filters>
                <x-filters.filter_msg title="Status" parent_id="prep-status-span" child_id="prep-status-data"/>
                <x-filters.filter_msg title="Min SKU" parent_id="total-min-sku-span" child_id="total-min-sku-data"/>
                <x-filters.filter_msg title="Max SKU" parent_id="total-max-sku-span" child_id="total-max-sku-data"/>
                <x-filters.filter_msg title="Min Sellable unit" parent_id="total-min-sellable-unit-span" child_id="total-min-sellable-unit-data"/>
                <x-filters.filter_msg title="Max Sellable unit" parent_id="total-max-sellable-unit-span" child_id="total-max-sellable-unit-data"/>         
            </x-applied-filters>
        </div>
        @php
            $tableId = 'shipment-working-data-table'
        @endphp

        {{ $dataTable->table(['id'=>'shipment-working-data-table','class'=>'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7 tab-table'],true) }}
    </x-fba_shipment.tab_contant>
    <!-- Tabs Content : End -->

     <!-- Filter Box -->
     <x-filters>
        <x-filters.multi-select label="Select Prep Status" title="prep_status" :options="$statusAttr" />
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

    </x-filters>

</div>
<input type="hidden" id="print_pallet_label_html_url" value="{{ route('fba_shipment.generate_pallet_label_html') }}">
@include('components.fba_prep.pallet_lable_model')
@endsection

@section('page-script')
    {{ $dataTable->scripts() }}

<script>
//    Harshad Code Start
function showPrintPalletLabelModal(elem) {
        var shipmentData = JSON.parse(elem);
        $('#shipment_id1').val(shipmentData.id);

        $('#shipment_id_error').text('');
        $('#number_of_pallet_error').text('');
        $('#number_of_pallet').val(1);
        $('#print_pallet_label_modal').modal('show');
    }
    var generateLabel = false;
    $('body').on('submit', 'form#print_pallet_label_form', function(e) {
      if($('#number_of_pallet').val() > 20){
        $('#number_of_pallet_error').html('The number of pallet field must be between 1 to 20.');
        return false;
      }
        if ($('#shipment_id1').val() == '' || $('#shipment_id1').val() == null) {
            $('#shipment_id_error').text('shipment id not found!');
            return false;
        }

        if ($('#number_of_pallet').val() == '' && $('#number_of_pallet').val() <= 0) {
            $('#number_of_pallet_error').text('Please enter number of pallet.');
            return false;
        }

        var redirectUrl = $('#print_pallet_label_html_url').val();
        var frm = $('#print_pallet_label_form');

        if (!generateLabel) {

            generateLabel = true;
            $.ajax({
                url: $(frm).attr('action'),
                type: "POST",
                data: $(frm).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    generateLabel = false;
                    hide_loader();
                    $('#print_pallet_label_modal').modal('hide');

                    if (data.status == 200) {
                        displaySuccessMessage(data.message);

                        var a = window.open(redirectUrl, '_blank');
                        a.print();

                        if (data.download_url != '') {
                            window.open(data.download_url, '_blank');
                        }

                    } else {
                        displayErrorMessage(data.message);
                    }

                    // LaravelDataTables["shipment-data-table"].draw();
                },
                error: function(xhr, err) {
                    generateLabel = false;
                    hide_loader();
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
                        if (typeof xhr.responseJSON.errors != "undefined") {
                            commonFormErrorShow(xhr, err);
                        } else {
                            displayErrorMessage(xhr.responseJSON.message);
                        }
                    } else {
                        displayErrorMessage(xhr.responseJSON.errors);
                    }
                }
            });
        }
    });
// Harshad Code End
    const tableId = "{{ $tableId }}"
    const filterList = [
            ['prep_status', 'prep-status-span', 'prep-status-data','multi-select'],
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
        ];

</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
<script>
    $(document).ready(function () {
        $('#total_max_sku').attr('min', '1');
        $('#total_min_sku').attr('min', '1');
        $('#total_max_sellable_unit').attr('min', '1');
        $('#total_min_sellable_unit').attr('min', '1');
    });

    // Shipment syncing
    $('#shipment_reverse_sync_btn').on('click', function() {
        $.ajax({
            url: '{{ route('fetch-shipment-sync') }}',
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                show_loader();
            },
            success: function (response) {
                hide_loader();
                displaySuccessMessage(response.message);
                window.location.reload();
            },
            error: function (xhr, err) {
                displayErrorMessage('Something went wrong. Please try after sometime.');
                if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
                     if (typeof xhr.responseJSON.errors != "undefined") {
                        commonFormErrorShow(xhr, err);
                    } else {
                        displayErrorMessage(xhr.responseJSON.message);
                    }
                } else {
                    displayErrorMessage(xhr.responseJSON.errors);
                }
            }
        });
    });

</script>
@endsection