@extends('layouts.app')
@section('title', 'FBA Prep List')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('Prep Listing') }}</a></li>
@endsection

@section('content')
<x-lists>
    <div class="container-fluid py-5">

        <div class="row align-items-center gy-3 gx-3 position-relative">

            <x-search-box input_id="search" />

            <div class="col col-xl-6 align-items-center d-flex flex-fill justify-content-end border-end border-secondary pe-4">

                <strong class="float-end me-3">{{ !empty($latestFbaShipment->updated_at) ? 'Last Synced: ' . Carbon\Carbon::parse($latestFbaShipment->updated_at)->format('d-m-Y H:i:s') : '' }}</strong>

                <a href="javascript:void(0)" id="shipment_reverse_sync_btn" class="btn btn-sm btn-success bg-success float-end" title="Sync shipments from Amazon"><i class="fa fa-refresh" aria-hidden="true"></i> Sync Shipment</a><br>

            </div>

            <div class="col-sm-auto ms-auto">
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
            <x-filters.filter_msg title="Prep Status" parent_id="prep-status-span" child_id="prep-status-data" />
            <x-filters.filter_msg title="Shipment Status" parent_id="shipment-status-span" child_id="shipment-status-data" />
        </x-applied-filters>
    </div>
    @php
    $tableId = 'prep-data-table';
    @endphp
    {{ $dataTable->table(['id' => 'prep-data-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-3 gy-2 gx-3 fba-prep-table border-bottom border-gray-300 dataTable'], true) }}
    
</x-lists>

<x-filters>
    <x-filters.multi-select title="prep_status_filter" :options="$prepStatus" label="Prep Status" />
    <x-filters.multi-select title="shipment_status_filter" :options="$shipmentStatus" label="Shipment Status" />
</x-filters>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />
<input type="hidden" id="print_pallet_label_html_url" value="{{ route('fba_shipment.generate_pallet_label_html') }}">
@include('components.fba_prep.pallet_lable_model')

    <div class="modal fade" tabindex="-1" id="export_prep_data_modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_title_header">Export Prep Discrepancy For <span id="shipment_id"></span></h5>
                    <input type="hidden" name="prep_id" id="prep_id">
                    <input type="hidden" id="submit_type">
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="svg-icon svg-icon-2x"></span>
                    </div>
                    <!--end::Close-->
                </div>
    
                <div class="modal-body">
                    <span class="error text-danger mb-3" id="common_error"></span>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="mb-5">
                                <label>Export As</label>
                                <select name="export_type" id="export_type" class="form-control form-control-sm" data-placeholder="Select an option" data-allow-clear="true">
                                    <option value="">--Select--</option>
                                    <option value="1">CSV</option>
                                    <option value="2">XLS</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="add_btn" onclick="return exportPrepButton()">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
{{ $dataTable->scripts() }}

<script>
    const tableId = "{{ $tableId }}";
    const updateColumnVisibilityUrl = "{{ route('fba-prep-columns-visibility') }}";
    const filterList = [
        ['prep_status_filter', 'prep-status-span', 'prep-status-data', 'multi-select'],
        ['shipment_status_filter', 'shipment-status-span', 'shipment-status-data', 'multi-select'],
    ];
</script>
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

<script>
    // Harshad Code Start

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
                    console.log(xhr)
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
    // Harshad Code end


    // Shipment syncing
    $('#shipment_reverse_sync_btn').on('click', function() {
        $.ajax({
            url: '{{ route('fetch-shipment-sync') }}',
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                show_loader();
            },
            success: function(response) {
                hide_loader();
                displaySuccessMessage(response.message);
                window.location.reload();
            },
            error: function(xhr, err) {
                displayErrorMessage('Something went wrong. Please try after sometime.');
                if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message
                    .length > 0) {
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

    $(document).ready(function() {
        $('#total_max_sku').attr('min', '1');
        $('#total_min_sku').attr('min', '1');
        $('#total_max_sellable_unit').attr('min', '1');
        $('#total_min_sellable_unit').attr('min', '1');
    });

    $(document).ready(function() {
        $('body').on('click', '.prepsList', function(e) {
            var shipmentId = $(this).attr('data-id');
            var SITEURL = '{{url("/")}}';
            if (shipmentId != "") {
                var sku = $(this).attr('data-sku');
                var prepType = $(this).attr('data-type');
                if (sku > 0) {
                    $.ajax({
                        url: "{{url('fba-shipment/update-prep-listing-log')}}",
                        type: "POST",
                        data: {
                            shipmentId: shipmentId,
                            prepType: prepType
                        },
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                        },
                        beforeSend: function() {
                            //show_loader();
                        },
                        success: function(data) {
                            //hide_loader();
                            if (data.type == 'success') {
                                var urls = SITEURL + "/fba-shipment/edit-prep/" + shipmentId;
                                // window.open(urls, '_blank');
                                window.location.href = urls;
                            } else {
                                displayErrorMessage(data.message);
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        title: "There is no SKUs are available.",
                        icon: 'warning',
                        text: "Shipment - " + shipmentId,
                        showCloseButton: true,
                        confirmButtonText: "OK",
                        confirmButtonColor: "#009ef7",
                        dangerMode: true,
                    });
                }

            }
        });
    });

    $('#total_max_sku').on('input', function() {
        var min = parseFloat($('#total_min_sku').val());
        if (parseFloat($(this).val()) < min) {
            $(this).attr('value', min);
            $(this).val(min);
        }
    })

    $('#total_min_sku').on('input', function() {
        var max = parseFloat($('#total_max_sku').val());
        if (parseFloat($(this).val()) > max) {
            $(this).attr('value', max);
            $(this).val(max);
        }
    })

    $('#total_max_sellable_unit').on('input', function() {
        var min = parseFloat($('#total_min_sellable_unit').val());
        if (parseFloat($(this).val()) < min) {
            $(this).attr('value', min);
            $(this).val(min);
        }
    })
        $('#total_min_sellable_unit').on('input', function() {
            var max = parseFloat($('#total_max_sellable_unit').val());
            if (parseFloat($(this).val()) > max) {
                $(this).attr('value', max);
                $(this).val(max);
            }
        })

        function exportPrepDiscrepancy(id, shipmentId) {
            $('#export_prep_data_modal').find('#export_type').val('');
            $('#export_prep_data_modal').find('#prep_id').val(id);
            $('#export_prep_data_modal').find('span#shipment_id').text(shipmentId);
            $('#export_prep_data_modal').modal('show');
        }

        function exportPrepButton(argument) {
            const export_po_id = $('#export_prep_data_modal').find('#prep_id').val();
            const export_type = $('#export_prep_data_modal').find('#export_type').val();

            if(export_type == '')
            {
                alert("please select export option");
                return false;
            }

            const SITEURL = '{{url('/')}}';

            if (export_type == '1')
            {
                const exportURL = SITEURL + '/fba-prep/export-prep-csv/' + export_po_id;
                window.location.href = exportURL;
            } else {
                const exportURL = SITEURL + '/fba-prep/export-prep-xls/' + export_po_id;
                window.location.href = exportURL;
            }
            $('#export_prep_data_modal').modal('hide');
        }

    $('#total_min_sellable_unit').on('input', function() {
        var max = parseFloat($('#total_max_sellable_unit').val());
        if (parseFloat($(this).val()) > max) {
            $(this).attr('value', max);
            $(this).val(max);
        }
    })
</script>

@endsection