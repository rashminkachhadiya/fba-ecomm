@extends('layouts.app')

@section('title', 'FBA Shipments')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="javascript:void(0)">{{__('Shipments List')}}</a></li>
@endsection

@section('content')

    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

        <!-- Tabs : Start -->
        @include('fba.fba_shipment.shipment_menu_tabs', ['activeTab' => 'draft'])
        <!-- Tabs : End -->

        <!-- Tabs Content : Start -->
        <x-fba_shipment.tab_contant>

            <div class="container-fluid py-5">

                <div class="row align-items-center gy-3 gx-3 position-relative">

                    <x-search-box input_id="search" />

                    <div class="col col-xl-6 col-xl-6 align-items-center d-flex flex-fill justify-content-end border-end border-secondary pe-4">
                    </div>

                    <div class="col-sm-auto text-right-sm d-flex">
                        <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                            <i class="fa-regular fa-bars-filter fs-4"></i>
                        </x-actions.button>
                    </div>

                </div>

                <!-- Show selected filter in alert warning box -->
                <x-applied-filters>
                    <x-filters.filter_msg title="Shipment Status" parent_id="shipment-status-span" child_id="shipment-status-data"/>
                    <x-filters.filter_msg title="Shipment start at" parent_id="shipment_start_date_span" child_id="shipment_start_date_val" />
                    <x-filters.filter_msg title="Shipment end at" parent_id="shipment_end_date_span" child_id="shipment_end_date_val" />
                </x-applied-filters>

            </div>

            @php
                $tableId = 'shipment-data-table'
            @endphp

            <div class="col-sm-12">
                {{ $dataTable->table(['id'=>$tableId,'class'=>'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7 tab-table'],true) }}
            </div>
        </x-fba_shipment.tab_contant>
        <!-- Tabs Content : End -->

        <!-- Filter Box -->
        <x-filters>
            <x-filters.multi-select label="Status" title="shipment_status" :options="$draftTabStatus" />
            <x-filters.date_range 
                title="Shipment Date Range" 
                start_date_name="shipment_start_date" 
                start_date_id="shipment_start_date" 
                end_date_name="shipment_end_date" 
                end_date_id="shipment_end_date"
            />
        </x-filters>
    </div>

    @php
        $shipmentStatus = 0;
    @endphp

    <!-- Add move and delete shipment Modal : Start -->
    <div class="modal fade" tabindex="-1" id="move_and_delete_model">
   
        {{ Form::open(['route' => ['fba_shipments.move-delete-shipment'], 'name' => '', 'enctype'=>'multipart/form-data', 'id' => 'moveDeleteShipmentFormSubmit',  'onsubmit' => 'return false']) }}

        <input type="hidden" name="shipment_id" id="modelShipmentId">

        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Move products and delete shipment</h3><br/>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="svg-icon svg-icon-1"></span>
                    </div>
                    <!--end::Close-->
                </div> 

                <div class="modal-body text-center">
                    <h3 class="modal-title mb-3 text-primary">Shipment Name: <span id="showModelShipmentId"></span> (SKU: <span id="showModelSkuCount"></span>)</h3>

                   <h4>Before deleting the shipment, move the products to a new draft plan or create new draft plan</h4>
                  <br/>
                  <div class="row">
                    <div class="col-sm-6">
                        <div class="card card-body bg-light">
                            <div class="form-check form-check-inline fs-4 text-primary m-3 mx-7">
                                <input class="form-check-input selected_move_type" type="radio" name="move_type" id="inlineRadio1" value="1" style="height: 1.75rem">
                                <label class="form-check-label" for="inlineRadio1"><b>Add to an existing Draft Plan</b></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card card-body bg-light">
                        <div class="form-check form-check-inline fs-4 text-primary m-3 mx-7">
                            <input class="form-check-input selected_move_type" type="radio" name="move_type" id="inlineRadio2" value="2" style="height: 1.75rem">
                            <label class="form-check-label" for="inlineRadio2"><b>Create a new Draft Plan</b></label>
                        </div>
                    </div>  
                    </div>
                  </div>
            <br/><br/>
                  <div class="row" id="move_to_selection_1">
                    <h4 style="text-align:left">Please select the draft plan:</h4><br/>
                    <div class="alert alert-warning">Notice: Duplicate SKUs quantity will be merged together.</div>
                    <table class="table table-sm table-bordered  border fs-5 text-center">
                    <thead class="thead-light border bg-secondary">
                    <tr>
                        <th>Draft Plan Name (Total SKU)</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(!empty($planList))
                        @foreach($planList as $plan)
                        <tr class="border border-secondary @if(!empty($plan['shipment_products']) && $plan['shipment_products'][0]['total_sku'] >= 200) custom-bg-light @endif">
                            <td class="min-w-100px">
                                <b>{{ $plan['plan_name'] }} ({{ !empty($plan['shipment_products_count']) ? $plan['shipment_products_count'] : 0   }})</b>
                                <span class="fs-8 text-muted"> (#{{ $plan['id'] }})</span>
                            </td>
                            <td>
                                @if(!empty($plan['shipment_products_count']) &&  $plan['shipment_products_count'] >= 200)
                                @else
                                <input type="radio" role="button" class="form-check-input radio radio-lg radio-accent radio-success selected_draft_plan" name="planId" value="{{ $plan['id'] }}" style="height: 1.75rem">

                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                    </table>
                  </div>
                  <div class="row hide" id="move_to_selection_2">
                    <div class="col-sm-3 form-label required">New Draft Plan Name</div>
                    <div class="col-sm-6">
                        <input type="text" id="create_new_plan_name" class="form-control w-100" placeholder="Enter plan name" name="input_plan_name"></div>
                        <span class="text-danger text-left" id="plan_name_error"></span>
                    </div>
                                
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" id="move_delete_shipment_btn" value="Move products and Delete shipment">
                </div>
            </div>
        </div>
        {{ Form::close()}}
    </div>
    <!-- Add Move and delete shipment Modal : Close -->
@endsection

@section('page-script')
{{ $dataTable->scripts() }}

<script>
    $('#move_delete_shipment_btn, #move_delete_plan_btn').prop('disabled', true);
    $('#move_to_selection_1, #move_to_selection_2').hide();

    $("#shipment_status").select2({
        multiple: false
    })
    const shipmentStatus = "{{ $shipmentStatus }}";
    set_query_para('shipment_status',window.btoa(shipmentStatus));

    $("#shipment_status").val(0).trigger('change');

    const tableId = "{{ $tableId }}"

    $("#shipment_start_date").daterangepicker({
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

    $("#shipment_start_date").on("apply.daterangepicker", function (ev, picker) {
        $(this).val(picker.startDate.format("DD-MM-YYYY"));
    });

    $("#shipment_end_date").daterangepicker({
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

    $("#shipment_end_date").on("apply.daterangepicker", function (ev, picker) {
        $(this).val(picker.startDate.format("DD-MM-YYYY"));
    });

    const filterList = [
        ['shipment_status', 'shipment-status-span', 'shipment-status-data','multi-select'],
        ['shipment_start_date', 'shipment_start_date_span', 'shipment_start_date_val','date'],
        ['shipment_end_date', 'shipment_end_date_span', 'shipment_end_date_val','date']
    ];
    
    $("#clear_search").click(function(){
        window.location.href = "{{ route('fba-shipments.index') }}";
    })

    // When click on reset button
    $('#advance-filter-reset').on('click', function() {
        window.location.href = "{{ route('fba-shipments.index') }}";
    });

    function assignShipmentId(shipmentId, skuCount)
    {
        $('#modelShipmentId').val(shipmentId);
        $('#showModelShipmentId').text(shipmentId);
        $('#showModelSkuCount').text(skuCount);
    }

    $('.selected_move_type').click(function(){
        var selectedType = $(this).val();
        
        if(selectedType == 1){
            $('#move_to_selection_1').show();
            $('#move_to_selection_2').hide();
            $('#create_new_plan_name').val('');
            $('#move_delete_shipment_btn').prop('disabled', true);
        } else {
            $('#move_to_selection_2').show();
            $('#move_to_selection_1').hide();
            $(".selected_draft_plan").prop("checked", false);
            $('#move_delete_shipment_btn').prop('disabled', true);
        }
    });

    $('.selected_draft_plan').click(function(){
        $('#move_delete_shipment_btn').prop('disabled', false);
    });

    $(document).on("keyup change", "#create_new_plan_name", function () {
        $('#plan_name_error').text('');
        $('#move_delete_shipment_btn').prop('disabled', true);
        
        if($(this).val() != '')
        {
            $('#move_delete_shipment_btn').prop('disabled', false);
        } else {
            $('#move_delete_shipment_btn').prop('disabled', true);
        }
    });

    // Move and delete shipment form submit
    $("form#moveDeleteShipmentFormSubmit").submit(function (e) {
        e.preventDefault();

        // Check if any asin is already in plan and we are trying to add it again.
        // This will give an alert confirmation message to proceed or not.
        var formData = new FormData($("#moveDeleteShipmentFormSubmit")[0]);
        var submitUrl =  $(this).attr("action");
    
        $.ajax({
            url: submitUrl,
            type: "POST",
            data: formData,
            contentType: false,
            cache: false,
            async: false,
            processData: false,
            success: function (data) {
                if(!data.status)
                { 
                    Swal.fire({
                        title: "<b style='color:red'>Warning</b>",
                        html: data.message,
                        showCloseButton: true,
                        showCancelButton: false,
                        customClass: 'swal-wide',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#009ef7',
                    }).then(function (res) {
                        location.reload();
                    });
                    return false;
                } else {
                    Swal.fire({
                        title: "<b style='color:green'>Success</b>",
                        html: data.message,
                        showCloseButton: true,
                        showCancelButton: false,
                        customClass: 'swal-wide',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#009ef7',
                    }).then(function (res) {
                        location.reload();
                    });
                    return false;
                }
            },
            error: function (xhr, err) {
            },
        });
        return false;
    });
</script>

<script src="{{ asset('js/fba_shipment/form.js') }}"></script>
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

@endsection