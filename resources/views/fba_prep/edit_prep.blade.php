@extends('layouts.app')

@section('title', 'Edit Prep Detail')
@section('breadcrumb')
    <li class="breadcrumb-item text-primary"><a href="{{ route('prep_list') }}"><i class="zmdi zmdi-home"
                aria-hidden="true"></i> {{ __('Prep List') }}</a></li>
    <li class="breadcrumb-item"><a href="javascript:;">{{ __('Prep Shipment Details') }}</a></li>
@endsection

@section('content')

    <div class="content d-flex flex-column flex-column-fluid position-relative" id="kt_content">
        <div class="">
            <div class="py-4">
                <div class="d-md-flex mx-md-3 justify-content-between">
                    <div class="flex-grow-1">
                        <div class="d-flex px-4">
                            <div class="me-5 pe-4">
                                <p class="mb-0" style="letter-spacing: 0.05em;">
                                    <span> Shipment ID: </span> 
                                    <strong class="fw-700">{{ $shipment->shipment_id }}</strong>
                                </p>
                            </div>
                            <div class="me-5 pe-4">
                                <p class="mb-0" style="letter-spacing: 0.05em;">
                                    <span> Destination: </span>
                                    <strong class="fw-700">{{ $shipment->destination_fulfillment_center_id }}</strong>
                                </p>
                            </div>
                            <div class="me-5 pe-4">
                                <p class="mb-0" style="letter-spacing: 0.05em;">
                                    <span>Shipment Status: </span>
                                    <strong class="fw-700">{{ $shipment->shipment_status == 0 ? 'WORKING' : App\Helpers\CommonHelper::returnStatusNameById($shipment->shipment_status) }}</strong>
                                </p>
                            </div>
                            <div class="me-5 pe-4">
                                <p class="mb-0" style="letter-spacing: 0.05em;">
                                    <span> SKU: </span>
                                    <strong class="fw-700">
                                        @if (!empty($totalSkus))
                                            {{ $totalSkus }}
                                        @endif
                                    </strong>
                                </p>
                            </div>
                            <div class="me-5 pe-4">
                                <p class="mb-0" style="letter-spacing: 0.05em;">
                                    <span> SKUs Prepped: </span>
                                    <strong class="fw-700">
                                        @if (!empty($skusPrepped))
                                            {{ $skusPrepped }}
                                        @else
                                            {{ 0 }}
                                        @endif
                                    </strong>
                                </p>
                            </div>
                        </div>
                        <!-- Progress Bar... -->
                        <div class="px-4 me-5 position-relative">
                            <p class="position-absolute text-center w-100 fw-700">
                                {{ number_format($totalDoneUnits, 0) }} / {{ $totalShippedUnits }}
                            </p>
                            <div class="progress my-4 rounded border border-secondary" style="height: 20px;">
                                <div class="progress-bar btn-custom-success" role="progressbar"
                                    style="width: {{ $cal_percentage . '%' }}" aria-valuenow="{{ $cal_percentage }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <!-- Progress Bar... -->
                    </div>
                    <div class="d-md-flex align-self-center align-items-start justify-content-md-end">
                        @if ($shipment->shipment_status != 6)
                            {{-- <a href="javascript:void(0);" data-shipment_id="{{ $shipment->id }}"
                                class="btn btn-custom-success mx-3 my-2" onclick="showPrintPalletLabelModal(this)">Print
                                Pallet Label</a> --}}

                            {{-- <a href="javascript:;" data-shipment_id="{{ $shipment->id }}" class="btn btn-custom-warning text-white mx-3 my-2" onclick="addTransportDetailModal(this)">Update Transport Information</a> --}}

                            @if (!empty($shipment->prep_status) && $shipment->prep_status != 2)
                                <a href="javascript:void(0);"
                                    data-url="{{ route('fba_prep.check_shipment_discrepancy') }}"
                                    data-shipment_id="{{ $shipment->id }}" class="btn btn-primary mx-3 my-2"
                                    onclick="completePrepModal(this)">Complete Prep</a>
                            @endif
                        @endif
                    </div>
                </div>
                <hr class="my-md-5">
                <div class="row mx-md-3 py-0">
                    <div class="col-md-6 col-xl-4 col-xl-4 ps-4">
                        <div class="input-group flex-nowrap input-group-sm">
                            {{ Form::text('search', Request::has('product_info_search') && Request::get('product_info_search') != '' ? base64_decode(Request::get('product_info_search')) : '', ['id' => 'prep_search_data', 'autocomplete' => 'off', 'class' => 'form-control px-5', 'placeholder' => 'Search by Product Title, ASIN, UPC, Item Code, SKU, FNSKU, Supplier Name, PO Number, Pallet ID']) }}
                            <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px"
                                type="button" id="prep_search_button"><i
                                    class="fa-regular fa-magnifying-glass text-primary" aria-hidden="true"></i></button>
                            <a class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px refresh invoice_clear_search"
                                title="Refresh" id="invoice_clear_search"><i class="fas fa-sync-alt"
                                    aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl d-md-flex align-self-centr justify-content-md-end text-center px-0">

                        @if (number_format($totalDoneUnits, 0) != $totalShippedUnits)
                            <a href="javascript:void(0);" class="btn bg-gray-300 mx-3 py-2 my-2" id="create_multi_skus_box">Create Multi SKU Box</a>

                            <a href="javascript:void(0);" class="btn bg-gray-300 mx-3 py-2 my-2" onclick="printAllLabel();">Print Label</a>
                        @endif

                        @if (isset($fbaPrepAllBoxDetail) && !empty($fbaPrepAllBoxDetail) && count($fbaPrepAllBoxDetail) > 0)
                            <a href="javascript:;" class="btn bg-gray-300 mx-3 py-2 my-2"
                                id="view_all_button_{{ $shipment->shipment_id }}">View All Boxes</a>
                        @else
                            <a href="javascript:;" class="btn bg-gray-300 mx-3 py-2 my-2" onclick="getAlert();">View All
                                Boxes</a>
                        @endif

                    </div>
                    <div id="ajx_srchbar"></div>
                    @if (Request::has('product_info_search') && Request::get('product_info_search') != '')
                        <div class="alert alert-warning align-items-center py-3 px-4 mt-3 mb-0" id="filter_by_div"
                            style="display: flex;">
                            <!--begin::Svg Icon | path: icons/duotune/general/gen048.svg-->
                            <span class="svg-icon svg-icon-2hx svg-icon-warning me-4">
                                <i class="fa-duotone fa-filter-list fs-3 text-primary" aria-hidden="true"></i>
                            </span>
                            <!--end::Svg Icon-->
                            <div class="d-flex flex-column">
                                <span id="test">
                                    Filter by
                                    <span id="search_span" style="">
                                        <span class="fw-700 me-1">Search:</span> <span id="search_val"></span>
                                    </span>
                                    <span class="mx-2 partition-span" style=""></span>
                                    <span id="sup_span" style="display: none;">
                                        <span class="fw-700 me-1">Prep Status:</span> <span id="prep_status"></span>
                                    </span>

                                    <span class="mx-2 partition-span" style="display: none;"> |</span>

                                    <a href="javascript:;" class="invoice_clear_search">Reset</a> it.
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <!-- Show Prep Shipment products -->
            {{-- <div class="table-responsive"> --}}
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="product-info">
                    <thead>
                        <tr class="fs-7">
                            <th class="text-nowrap w-20px pr-custom">#ID</th>
                            <th class="text-nowrap w-350px pr-custom">Product Information</th>
                            <th class="text-nowrap w-100px pr-custom">Pack Information</th>
                            <th class="text-nowrap w-50px pr-custom">Prep Note</th>
                            <th class="text-nowrap pr-custom" width="5%">Qty</th>
                            <th class="text-nowrap pr-custom" width="5%">Done</th>
                            <th class="text-nowrap w-50px pr-custom" width="5%">Discrepancy</th>
                            <th class="text-nowrap w-50px pr-custom">Discrepancy Notes</th>
                            <th class="text-nowrap pr-custom" width="5%">Action</th>
                            {{-- <th class="text-nowrap pr-custom">#ID</th>
                            <th class="text-nowrap pr-custom">Product Information</th>
                            <th class="text-nowrap pr-custom">Pack Information</th>
                            <th class="text-nowrap pr-custom">Prep Note</th>
                            <th class="text-nowrap pr-custom" width="5%">Qty</th>
                            <th class="text-nowrap pr-custom" width="5%">Done</th>
                            <th class="text-nowrap pr-custom" width="5%">Discrepancy</th>
                            <th class="text-nowrap pr-custom">Discrepancy Notes</th>
                            <th class="text-nowrap pr-custom" width="5%">Action</th> --}}
                        </tr>
                    </thead>
                    <!-- prep working products -->
                    <input type="hidden" id="actItmId">
                    <input type="hidden" id="add_more_index" value="0">
                    <input type="hidden" id="add_more_index_extra" value="0">
                    <tbody id="load_more_ajx">
                        @include('fba_prep.partials._prep_working_data')
                    </tbody>
                </table>
                {{-- {!! $shipmentItems->links() !!} --}}
            {{-- </div> --}}
        </div>

        <input type="hidden" id="total_done_unit" value="{{ $totalDoneUnits }}">

        @include('fba_prep.partials._item_label_modal')
        @include('fba_prep.partials._box_label_modal')
        @include('fba_prep.partials._single_box_view')
        @include('fba_prep.partials._view_all_box')
        
        @include('fba_prep.partials.update_product_qty_modal', ['allShipmentIds' => $allShipmentIds])

    </div>

    <div class="modal fade" id="completePrepShipmentModal" aria-labelledby="completePrepShipmentModalLabel"
        role="dialog" aria-hidden="true">

    </div>
    <!-- Add sku qty to the plan Modal : Start -->
    <div class="modal fade" tabindex="-1" id="move_and_delete_model">

        {{ Form::open(['route' => ['users.store'], 'name' => '', 'enctype' => 'multipart/form-data', 'id' => 'addToPlanFormSubmit', 'onsubmit' => 'return false']) }}

        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark p-4">
                    <h3 class="modal-title text-white">SKU: <span id="add_to_plan_sku"></span></h3><br />

                    <input type="hidden" name="sku" id="hidden_sku">

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <span class="svg-icon svg-icon-1"></span>
                    </div>
                    <!--end::Close-->
                </div>
                <div class="bg-gray-100 p-4">
                    <div class="d-flex justify-content-center align-items-center only3d"><span
                            class="fw-700 mx-2 bxlbl">Add SKU quantity to a plan</span></div>
                </div>

                <div class="modal-body text-center">

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card card-body bg-light">
                                <div class="form-check form-check-inline fs-4 text-primary m-3 mx-7">
                                    <input class="form-check-input selected_move_type" type="radio" name="move_type"
                                        id="inlineRadio1" value="1">
                                    <label class="form-check-label" for="inlineRadio1"><b>Add to an existing Draft
                                            Plan</b></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-body bg-light">
                                <div class="form-check form-check-inline fs-4 text-primary m-3 mx-7">
                                    <input class="form-check-input selected_move_type" type="radio" name="move_type"
                                        id="inlineRadio2" value="2">
                                    <label class="form-check-label" for="inlineRadio2"><b>Create a new Draft
                                            Plan</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br /><br />
                    <div class="row mb-5">
                        <div class="col-sm-3 form-label required">SKU Quantity:</div>
                        <div class="col-sm-4">
                            <input type="text" id="sku_qty" class="form-control w-100" placeholder="EX. 50"
                                name="sku_qty" onkeypress="return isNumberKey(this,event)">
                            <span class="text-danger text-left" id="sku_qty_error"></span>
                        </div>
                    </div>

                    <div class="row" id="move_to_selection_1">

                        <table class="table table-sm table-bordered  border fs-5 text-center">
                            <thead class="thead-light border bg-secondary">
                                <tr>
                                    <th>Draft Plan Name (Total SKU)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($planList))
                                    @foreach ($planList as $plan)
                                        <tr
                                            class="border border-secondary @if (!empty($plan['shipment_products']) && $plan['shipment_products'][0]['total_sku'] >= 200) custom-bg-light @endif">
                                            <td><b>{{ $plan['plan_name'] }}
                                                    ({{ !empty($plan['shipment_products_count']) ? $plan['shipment_products_count'] : 0 }})</b>
                                                <span class="fs-8 text-muted"> (#{{ $plan['id'] }})</span>
                                            </td>

                                            <td>
                                                @if (!empty($plan['shipment_products_count']) && $plan['shipment_products_count'] >= 200)
                                                @else
                                                    <input type="radio" role="button"
                                                        class="form-check-input radio radio-lg radio-accent radio-success selected_draft_plan"
                                                        name="planId" value="{{ $plan['id'] }}">
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
                        <div class="col-sm-4">
                            <input type="text" id="create_new_plan_name" class="form-control w-100"
                                placeholder="Enter Plan Name" name="input_plan_name">
                            <span class="text-danger text-left" id="plan_name_error"></span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" id="move_delete_shipment_btn" value="Submit">
                </div>
            </div>
        </div>
        {{ Form::close() }}
    </div>
    <!-- Add sku qty to the plan Modal : Close -->

    <!-- Box Dimension Popup -->
    <x-modal id="box_dimension_details" dialog="modal-lg">
        
        <x-modal.header title="Add Box Dimension Details" />

        <x-modal.body style="max-height: 430px; overflow-y: auto;">
            <div class="row">
                <x-forms>
                    <div class="col-sm-3">
                        <x-forms.label title="Box Weight (Pound)" required="required" />
                        {{ Form::text('box_weight', !empty(old('box_weight')) ? old('box_weight') : 0, ['id' => 'box_weight', "class" => "form-control form-control-solid validate","placeholder"=>"Box Weight", 'onkeypress'=>"return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"]) }}
                        <span class="box_weight_error" style="color: #F1416C;"></span>
                    </div>

                    <div class="col-sm-3">
                        <x-forms.label title="Box Width" required="required" />
                        {{ Form::text('box_width', !empty(old('box_width')) ? old('box_width') : 0, ['id' => 'box_width', "class" => "form-control form-control-solid validate","placeholder"=>"Box Width", 'onkeypress'=>"return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"]) }}
                        <span class="box_width_error" style="color: #F1416C;"></span>
                    </div>

                    <div class="col-sm-3">
                        <x-forms.label title="Box Height" required="required" />
                        {{ Form::text('box_height', !empty(old('box_height')) ? old('box_height') : 0, ['id' => 'box_height', "class" => "form-control form-control-solid validate","placeholder"=>"Box Height"]) }}
                        <span class="box_height_error" style="color: #F1416C;"></span>
                    </div>

                    <div class="col-sm-3">
                        <x-forms.label title="Box Length" required="required" />
                        {{ Form::text('box_length', !empty(old('box_length')) ? old('box_length') : 0, ['id' => 'box_length', "class" => "form-control form-control-solid validate","placeholder"=>"Box Length"]) }}
                        <span class="box_length_error" style="color: #F1416C;"></span>
                    </div>

                </x-forms>
            </div>
        </x-modal.body>

        <x-modal.footer name="Save" id="box_dimension_detail_submit" type="button" />

    </x-modal>

    <style>
        .table-responsive {
            overflow-y: auto;
            height: 90vh;
        }

        .table th {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 1;
            background: #fff;
        }
    </style>
    <?php
    $currentDate = date('Y-m-d');
    //increment 105 days
    $maxDate = strtotime($currentDate . '+ 105 days');
    ?>

    @include('fba_prep.multi_skus_box_modal')

    @include('fba_prep.multi_skus_add_products_modal')
    @php
        $currentYear = \Carbon\Carbon::now()->format("Y");
        $minDate = \Carbon\Carbon::now()->addDays(10)->format('m/d/Y');
    @endphp
@endsection
@section('page-script')
    <script src="{{ asset('plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script>

    <script>
        // $("body").css('overflow','hidden')
        // const pageHeight = $("body").height();
        // const tableTitleOffset = $("table thead").offset().top;
        // // console.log("Page height:", pageHeight);
        // // console.log("table offset:", tableTitleOffset);
        // $("table#product-info thead, tbody").css('display','block');
        // // $("table#product-info tbody").css('width','100%');
        // $("table#product-info tbody").css('overflow-y','auto');
        // const height = pageHeight - tableTitleOffset;
        // // console.log(height);
        // $("table#product-info tbody").css('max-height',`${height}px`);
    </script>

    <script>
        $(document).ready(function() {
            $('.numberonly').keypress(function(e) {
                var charCode = (e.which) ? e.which : event.keyCode
                if (String.fromCharCode(charCode).match(/[^0-9 /]/g))
                    return false;
            });


            $('body').on('click', '.showMoreDetailsLink', function(e) {
                // $(".supliers-details").hide();
                if ($(this).siblings().find(".supliers-details").css("display") == "none") {
                    $(this).siblings().find(".supliers-details").show();
                } else {
                    $(this).siblings().find(".supliers-details").hide();
                }
                event.stopPropagation();
            });
        });

        $('#printLabels').on('hidden.bs.modal', function(e) {
            $(this)
                .find("input,textarea,select")
                .val('')
                .end()
                .find("input[type=checkbox], input[type=radio]")
                .prop("checked", "")
                .end();

            $("#warning_itemPrintCount").html("");
        });
        //empty past input values in modal...
        $('#printBoxLabels').on('hidden.bs.modal', function(e) {
            $(this)
                .find("input,textarea,select")
                .val('')
                .end()
                .find("input[type=checkbox], input[type=radio]")
                .prop("checked", "")
                .end();

            $(this).find('#box_suggestion').html('');
            $("#totQanHtm").html('');
            $("#first").val("0");
            $("#second").val("0");
            $("#third").val("0");
            $("#fourth").val("0");
            $("#fifth").val("0");
        });

        //Allow only numbers and decimal numbers...
        //var asin_weight = document.getElementById('asin_weight');
        $('#asin_weight').keypress(function(event) {
            var $this = $(this);
            if ((event.which != 46 || $this.val().indexOf('.') != -1) &&
                ((event.which < 48 || event.which > 57) &&
                    (event.which != 0 && event.which != 8))) {
                event.preventDefault();
            }

            var text = $(this).val();
            if ((event.which == 46) && (text.indexOf('.') == -1)) {
                setTimeout(function() {
                    if ($this.val().substring($this.val().indexOf('.')).length > 3) {
                        $this.val($this.val().substring(0, $this.val().indexOf('.') + 3));
                    }
                }, 1);
            }

            if ((text.indexOf('.') != -1) &&
                (text.substring(text.indexOf('.')).length > 2) &&
                (event.which != 0 && event.which != 8) &&
                ($(this)[0].selectionStart >= text.length - 2)) {
                event.preventDefault();
            }
        });

        $('#asin_weight').bind("paste", function(e) {
            var text = e.originalEvent.clipboardData.getData('Text');
            if ($.isNumeric(text)) {
                if ((text.substring(text.indexOf('.')).length > 3) && (text.indexOf('.') > -1)) {
                    e.preventDefault();
                    $(this).val(text.substring(0, text.indexOf('.') + 3));
                }
            } else {
                e.preventDefault();
            }
        });

        //Allow only numbers and decimal numbers...
        $('#casesInHand').keypress(function(e) {
            var arr = [];
            var kk = e.which;
            for (i = 48; i < 58; i++)
                arr.push(i);

            if (!(arr.indexOf(kk) >= 0))
                e.preventDefault();
        });

        $('#casesInHand').bind("paste", function(e) {
            var text = e.originalEvent.clipboardData.getData('Text');
            if ($.isNumeric(text)) {
                if ((text.substring(text.indexOf('.')).length > 5) && (text.indexOf('.') > -1)) {
                    e.preventDefault();
                    let numbr = text.substring(0, text.indexOf('.') + 5);
                    let decimal = Math.trunc(numbr);

                    $(this).val(decimal);
                }
            } else {
                e.preventDefault();
            }
        });

        $(document).ready(function() {
            $('body').on('keypress', '.numberonlyText', function(e) {
                var charCode = (e.which) ? e.which : event.keyCode
                if (String.fromCharCode(charCode).match(/[^0-9]/g))
                    return false;
            });
        });

        function validateDate(inputDate) {
            if (inputDate != "")
            {
                var maxDate = "<?php echo date('m/d/Y', $maxDate); ?>";
                var date1 = new Date(inputDate);
                var date2 = new Date(maxDate);
                if (date1 >= date2)
                {
                    var nDate = new Date(inputDate);
                    var getFullYear = nDate.getFullYear();
                    if (getFullYear > '2032')
                    {
                        $('.daterangepicker .cancelBtn').trigger('click');
                        $("#expDate").val("");
                        $(".exp_box_date").val("");
                        Swal.fire({
                            title: 'Please enter a valid expiration date.',
                            //text: "",
                            icon: 'error',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonColor: '#009ef7',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'OK',
                            dangerMode: true,
                        });
                    }
                } else {
                    // $("#error_expDate").text("Please enter valid expiration date");
                    $("#expDate").val("");
                    $(".exp_box_date").val("");
                    Swal.fire({
                        title: 'Please enter a valid expiration date',
                        //text: "",
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                }
            }
        }

        function validateOnPaste(inputDate) {
            if (inputDate != "") {
                var maxDate = "<?php echo date('m/d/Y', $maxDate); ?>";
                var date1 = new Date(inputDate);
                var date2 = new Date(maxDate);
                if (date1 >= date2)
                {
                    var nDate = new Date(inputDate);
                    var getFullYear = nDate.getFullYear();
                    if (getFullYear > '2032')
                    {
                        $('.daterangepicker .cancelBtn').trigger('click');
                        $("#expDate").val("");
                        $(".exp_box_date").val("");
                        Swal.fire({
                            title: 'Please enter a valid expiration date.',
                            //text: "",
                            icon: 'error',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonColor: '#009ef7',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'OK',
                            dangerMode: true,
                        });
                    }
                } else {
                    // $("#error_expDate").text("Please enter valid expiration date");
                    $("#expDate").val("");
                    $(".exp_box_date").val("");
                    Swal.fire({
                        title: 'Please enter a valid expiration date',
                        //text: "",
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                }
            }
        }

        $('body').on('click', '#single_view_button', function() {
            var itemId = $(this).attr('data-id');
            var shipmentId = "{{ $shipment->shipment_id }}";
            var tbodyId = "mhtml_" + itemId;

            $(".singHtml").attr('id', tbodyId);
            $.ajax({
                url: "{{ url('fba-shipment/get-single-item-label-data') }}",
                type: "POST",
                data: {
                    itemId: itemId,
                    shipmentId: shipmentId
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    //show_loader();
                },
                success: function(data) {
                    
                    if (data.type == 'success')
                    {
                        $("#drw-mod-title").text("-");
                        $("#drw_lbl_title").text("-");
                        $("#drw_lbl_asin").text("-");
                        $("#drw_asin").val("");
                        $("#drw_product_fnsku").val("");
                        $("#drw_productSku").val("");
                        $("#drw_tot_qty").val("");

                        $("#drw_fba_shipment_item_id").val("");
                        $("#drw_totalShippedUnits").val("");

                        var imgUrl = "<?php echo asset('media/no-image.jpeg'); ?>";
                        $("#drw_lbl_img").html('<img src="' + imgUrl +
                            '" class="w-100 p-3 border border-gray-300 rounded" alt="" style="width: 70px !important;">'
                            );

                        if (typeof(data.shipmentItem.amazon_data) != "undefined" && data.shipmentItem
                            .amazon_data !== null)
                        {
                            var smallImgUrl = data.shipmentItem.amazon_data.main_image;
                            if (typeof(smallImgUrl) != "undefined" && smallImgUrl !== null) {
                                var largemageUrl = smallImgUrl.replace('_SL75_', '_SL500_');
                                $("#drw_lbl_img").html('<img src="' + smallImgUrl +
                                    '" class="w-100 p-3 border border-gray-300 rounded" alt="">');
                            }
                            $("#drw-mod-title").text(data.shipmentItem.amazon_data.fnsku);
                            $("#drw_lbl_title").text(data.shipmentItem.amazon_data.title);
                            //$("#drw_lbl_asin").text();
                            $("#drw_asin").val(data.shipmentItem.amazon_data.asin);
                            $("#drw_product_fnsku").val(data.shipmentItem.amazon_data.fnsku);
                            $("#drw_productSku").val(data.shipmentItem.amazon_data.sku);

                            let asinHtml = data.shipmentItem.amazon_data.asin +
                                ' <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinButton(\'' +
                                data.shipmentItem.amazon_data.asin +
                                '\')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a>';

                            $("#drw_lbl_asin").html(asinHtml);
                        }
                        
                        if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !==
                            null) {
                            var remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                            $("#drw_tot_qty").val(data.shipmentItem.qty);
                            $("#drw_remaining_qty").val(remainQty);
                        }

                        $("#drw_totalShippedUnits").val(data.totalShippedUnits);
                        $("#drw_fba_shipment_item_id").val(itemId);

                        var prepType = "{{ $prepType }}";
                        var nHtml = '';
                        if (prepType == 'EditPrep') {
                            nHtml =
                                '<a href="javascript:;" onclick="getDeleteAllBoxes();"><i class="fa-solid fa-trash-alt fa-lg text-danger"></i></a>';
                        }
                        $("#drw_delete_all").html(nHtml);

                        //boxDetail display...
                        if (typeof(data.fbaPrepBoxDetail) != "undefined" && data.fbaPrepBoxDetail !==
                            null && data.fbaPrepBoxDetail.length > 0) {
                            var k = 1;
                            var html = '';
                            $.each(data.fbaPrepBoxDetail, function(key, value) {
                                k++;
                                if (value.main_image != "") {
                                    mainImg = value.main_image;
                                } else {
                                    mainImg = imgUrl;
                                }

                                let styleForBox = '';
                                let isMultiSku = false;
                                if (value.box_type == 1)
                                {
                                    styleForBox = 'background-color: #cbcbcb';
                                    isMultiSku = true;
                                }

                                var nDate = '-';
                                if (value.expiry_date != "" && value.expiry_date != '0000-00-00') {
                                    var dateArr = value.expiry_date.split('-');
                                    nDate = dateArr[1] + '/' + dateArr[2] + '/' + dateArr[0];
                                }

                                $("#bxicons").show();

                                var prepType = "{{ $prepType }}";
                                var deleteBxoHtml = '';
                                if (prepType == 'EditPrep') {
                                    deleteBxoHtml =
                                        '<a href="javascript:;" onclick="getDeleteSingleBox(' +
                                        value.id + ',' + value.units +
                                        ');"><i class="fa-solid fa-trash-alt fa-lg text-danger"></i></a>';
                                }

                                html +=
                                    '<tr class="border-top border-gray-300" id="boxprepid_' +
                                    value.id +
                                    '" style="'+styleForBox+'"><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_lbl_box_number_' +
                                    k + '">' + value.box_number +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_img_' +
                                    k + '"><img src="' + mainImg +
                                    '" class="w-100 p-3 border border-gray-300 rounded" alt=""></span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_units_' +
                                    k + '">' + value.units +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><a href="javascript:" class="text-dark text-truncate" onclick="copySkuToClipboardButton(\'' +
                                    value.sku + '\');" data-bs-toggle="tooltip" title="' + value
                                    .sku + '"><span id="drw_lbl_pro_sku_' + k + '">' + value
                                    .sku +
                                    '</span></a>&nbsp;<a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copySkuToClipboardButton(\'' +
                                    value.sku +
                                    '\')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_units_' +
                                    k + '">' + nDate +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="d-flex align-items-center justify-content-evenly h-100"><a href="javascript:;" onclick="getPrintSingleBoxItemLabel(' +
                                    value.id +
                                    ','+isMultiSku+');"><i class="fa-solid fa-print fa-xl text-success"></i></a>' +
                                    deleteBxoHtml + '</div></td></tr>';
                            });
                            $("#mhtml_" + itemId).html(html);

                        } else {
                            $("#bxicons").hide();
                            $("#mhtml_" + itemId).html(
                                "<td></td><td></td><td></td><td><h5 style='text-align:center;font-weight:600;'>No Box Information Available.</h5></td><td></td><td></td><td></td>"
                                );
                        }

                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (
                        typeof xhr.responseJSON.message != "undefined" &&
                        xhr.responseJSON.message.length > 0
                    ) {
                        if (typeof xhr.responseJSON.errors != "undefined") {
                            commonFormErrorShow(xhr, err);
                        } else {
                            displayErrorMessage(xhr.responseJSON.message);
                        }
                    } else {
                        displayErrorMessage(xhr.responseJSON.errors);
                    }
                },
            });
        });

        $('body').on('click', '#view_all_button_{{ $shipment->shipment_id }}', function() {
            var shipmentId = "{{ $shipment->shipment_id }}";
            $.ajax({
                url: "{{ url('fba-shipment/get-view-all-item-label-data') }}",
                type: "POST",
                data: {
                    shipmentId: shipmentId
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    //show_loader();
                },
                success: function(data) {

                    if (data.type == 'success') {

                        var imgUrl = "<?php echo asset('media/no-image.jpeg'); ?>";
                        $("#drw_lbl_img").html('<img src="' + imgUrl +
                            '" class="w-100 p-3 border border-gray-300 rounded" alt="" style="width: 70px !important;">'
                            );

                        //boxDetail display...
                        if (typeof(data.fbaPrepBoxDetail) != "undefined" && data.fbaPrepBoxDetail !==
                            null && data.fbaPrepBoxDetail.length > 0) {
                            var k = 1;
                            var html = '';
                            $.each(data.fbaPrepBoxDetail, function(key, value) {
                                k++;
                                if (value.main_image != "") {
                                    mainImg = value.main_image;
                                } else {
                                    mainImg = imgUrl;
                                }

                                let styleForBox = '';
                                let isMultiSku = false;
                                if(value.box_type == 1)
                                {
                                    styleForBox = 'background-color: #cbcbcb';
                                    isMultiSku = true;
                                }

                                var nDate = '-';
                                if (value.expiry_date != "" && value.expiry_date != '0000-00-00') {
                                    var dateArr = value.expiry_date.split('-');
                                    nDate = dateArr[1] + '/' + dateArr[2] + '/' + dateArr[0];
                                }

                                var prepType = "{{ $prepType }}";
                                var deleteBxoHtml = '';
                                if (prepType == 'EditPrep') {
                                    deleteBxoHtml =
                                        '<a href="javascript:;" onclick="getDeleteSingleBox(' +
                                        value.id + ',' + value.units +
                                        ');"><i class="fa-solid fa-trash-alt fa-lg text-danger"></i></a>';
                                }

                                html +=
                                    '<tr class="border-top border-gray-300" id="vboxprepid_' +
                                    value.id +
                                    '" style="'+styleForBox+'"><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="vrw_lbl_box_number_' +
                                    k + '">' + value.box_number +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="vrw_list_img_' +
                                    k + '"><img src="' + mainImg +
                                    '" class="w-100 p-3 border border-gray-300 rounded" alt=""></span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="vrw_list_units_' +
                                    k + '">' + value.units +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><a href="javascript:" class="text-dark text-truncate" onclick="copySkuToClipboardButton(\'' +
                                    value.sku + '\');" data-bs-toggle="tooltip" title="' + value
                                    .sku + '"><span id="vrw_lbl_pro_sku_' + k + '">' + value
                                    .sku +
                                    '</span></a>&nbsp;<a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copySkuToClipboardButton(\'' +
                                    value.sku +
                                    '\')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="vrw_list_units_' +
                                    k + '">' + nDate +
                                    '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="d-flex align-items-center justify-content-evenly h-100"><a href="javascript:;" onclick="getPrintSingleBoxItemLabel(' +
                                    value.id +
                                    ','+isMultiSku+');"><i class="fa-solid fa-print fa-xl text-success"></i></a>' +
                                    deleteBxoHtml + '</div></td></tr>';
                            });
                            $("#vhtml").html(html);

                        } else {
                            $("#vhtml").html(
                                "<td></td><td></td><td></td><td><h5 style='text-align:center;font-weight:600;'>No Box Information Available.</h5></td><td></td><td></td><td></td>"
                                );
                        }

                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (
                        typeof xhr.responseJSON.message != "undefined" &&
                        xhr.responseJSON.message.length > 0
                    ) {
                        if (typeof xhr.responseJSON.errors != "undefined") {
                            commonFormErrorShow(xhr, err);
                        } else {
                            displayErrorMessage(xhr.responseJSON.message);
                        }
                    } else {
                        displayErrorMessage(xhr.responseJSON.errors);
                    }
                },
            });
        });

        // print box label showing confirm box
        $('body').on('click', '#printBoxLabelsModal', function() {
            var maxDate = "{{ date('m/d/Y', strtotime(date('Y-m-d') . '+ 105 days')) }}";
            $("#max_expiry_date").val(maxDate);

            var itemId = $("#actItmId").val();
            var printType = $(this).attr('data-text');

            //carry forward date from item lable to box lable
            var currItemLblInputDate = $("#expDate").val();
            $(".exp_box_date").val(currItemLblInputDate);

            if (printType != "" && printType == "2D") {
                $("#2dbox").show();
                $("#boxBack").show();
                $("#3dbox").hide();
                $(".boxItemCounts").val("");
                $(".boxItemCounts").attr('readonly', false);
                openBoxLabelPrinterModal(itemId, printType);
            }
            if (printType != "" && printType == "3D") {
                $("#3dbox").show();
                $("#2dbox").hide();
                $("#boxBack").hide();
                var itemId = $(this).attr('data-id');
                $("#actItmId").val($(this).attr('data-id'));
                $(".only3d").html('<span class="fw-700 mx-2 bxlbl">Print 3 IN 1 Box Label</span>');
                $(".boxItemCounts").val("1");
                $(".boxItemCounts").attr('readonly', true);
                var aPack = $(this).attr('data-a_pack');
                var casePack = $(this).attr('data-case_pack');
                if (casePack == 1) {
                    openBoxLabelPrinterModal(itemId, printType);
                } else if (aPack == parseFloat(casePack)) {
                    openBoxLabelPrinterModal(itemId, printType);
                } else if (aPack != parseFloat(casePack)) {
                    Swal.fire({
                        title: "<b>Are You sure ?</b>",
                        text: "The case pack is not same as the A pack. Are you sure you want to print 3 in 1 Box Labels?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: '#009ef7',
                        confirmButtonText: 'Yes',
                        cancelButtonColor: '#d33',
                        cancelButtonText: "No"
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            openBoxLabelPrinterModal(itemId, printType);
                        } else {
                            return false;
                        }
                    });
                }
            }
        });

        // showing printer modal box
        var isShowBoxLabelPrintModal = false;

        function openBoxLabelPrinterModal(itemId, printType) {
            var shipmentId = "{{ $shipment->shipment_id }}";
            $('#boxBack').attr('data-id', itemId);
            if (!isShowBoxLabelPrintModal) {
                isShowBoxLabelPrintModal = true;
                $.ajax({
                    url: "{{ url('fba-shipment/get-single-item-label-data') }}",
                    type: "POST",
                    data: {
                        itemId: itemId,
                        shipmentId: shipmentId
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        //show_loader();
                        $("#box-mod-title").text("-");
                        $("#box_lbl_title").text("-");
                        $("#box_lbl_product_remain").text("-");
                        $("#product_fnsku").val("");
                        $("#productSku").val("");
                        $("#asin_weight").val("");
                        $("#box_lbl_qty").val("");
                        var imgUrl = "<?php echo asset('media/no-image.jpeg'); ?>";
                        $("#box_lbl_img").html('<img src="' + imgUrl +
                            '" class="w-100 p-3 border border-gray-300 rounded" alt="">');
                        $("#unitPerBoxesQty").text("");
                        $("#unitPerBoxesQty_second").text("");
                        $("#casesInHandQty").text("");
                    },
                    success: function(data) {
                        // hide_loader();
                        isShowBoxLabelPrintModal = false;
                        if (data.type == 'success') {
                            if (typeof(data.shipmentItem.amazon_data) != "undefined" && data.shipmentItem
                                .amazon_data !== null) {

                                var smallImgUrl = data.shipmentItem.amazon_data.main_image;
                                var typ = "boxes";
                                if (typeof(smallImgUrl) != "undefined" && smallImgUrl !== null) {
                                    var largemageUrl = smallImgUrl.replace('_SL75_', '_SL500_');
                                    if (typeof(largemageUrl) != "undefined" && largemageUrl !== null) {
                                        $("#box_lbl_img").html(
                                            '<a href="javascript:void" onclick="chkIsProductValidated(\'' +
                                            data.shipmentItem.amazon_data.asin + '\',\'' + itemId +
                                            '\',\'' + typ + '\',\'' + data.shipmentItem.is_validated +
                                            '\');"><img src="' + largemageUrl +
                                            '" class="w-100 p-3 border border-gray-300 rounded" alt="" style="width: auto !important;max-height: 250px;max-width: 250px;"></a>'
                                            );
                                    }
                                }
                                $("#box-mod-title").text(data.shipmentItem.amazon_data.fnsku);
                                $("#box_lbl_title").text(data.shipmentItem.amazon_data.title);
                                if (typeof(data.shipmentItem.orig_qty) != "undefined" && data.shipmentItem
                                    .orig_qty !== null && data.shipmentItem.orig_qty > 0) {
                                    $("#box_lbl_qty").val(data.shipmentItem.orig_qty);
                                } else {
                                    $("#box_lbl_qty").val(data.shipmentItem.qty);
                                }

                                $("#box_asin").val(data.shipmentItem.amazon_data.asin);
                                $("#product_fnsku").val(data.shipmentItem.amazon_data.fnsku);
                                $("#productSku").val(data.shipmentItem.amazon_data.sku);
                                $("#box_main_image").val(data.shipmentItem.amazon_data.main_image);
                                $('#product_title').val(data.shipmentItem.amazon_data.title);
                            }

                            //PO Number associate calculations...
                            //Check the PO Number associate with this ASIN...
                            if (printType != "" && printType == "2D") {
                                let asinWeight = $("#old_asin_weight").val();
                                if (typeof(asinWeight) != "undefined" && asinWeight !== null && asinWeight !==
                                    '' && asinWeight > 0) {
                                    if (typeof(data.shipmentItem.po_number) != "undefined" && data.shipmentItem
                                        .po_number !== null) {
                                        //Check the Case Pack exist...
                                        if (typeof(data.shipmentItem.case_pack) != "undefined" && data
                                            .shipmentItem.case_pack !== null && data.shipmentItem.case_pack !==
                                            '' && data.shipmentItem.case_pack > 1) {
                                            //Check the A Pack exist...
                                            if (typeof(data.shipmentItem.pack_of) != "undefined" && data
                                                .shipmentItem.a_pack !== null && data.shipmentItem.pack_of !==
                                                '' && data.shipmentItem.pack_of > 0) {
                                                // ( 1.) When Case In Hand is not empty...
                                                let caseInHand = $("#updcasesInHand").text();
                                                if (typeof(caseInHand) != "undefined" && caseInHand !== null &&
                                                    caseInHand !== "") {
                                                    let units_per_boxes = parseInt(data.shipmentItem
                                                        .case_pack) / parseInt(data.shipmentItem.pack_of);
                                                    let number_of_boxes = Math.floor(caseInHand);

                                                    $("#per_box_item_count_0").val(units_per_boxes);
                                                    $("#no_of_boxes_count_0").val(number_of_boxes);

                                                    let totQty = units_per_boxes * number_of_boxes;

                                                    $("#tot_qty_0").val(totQty);

                                                } else {
                                                    // ( 2.) When Case In Hand is empty...
                                                    let item_labels_printed = $("#updItemPrintCount").text();
                                                    let case_in_hand = parseInt(data.shipmentItem.pack_of) /
                                                        parseInt(data.shipmentItem.case_pack) * parseInt(
                                                            item_labels_printed);

                                                    let unitsPer_boxes = parseInt(data.shipmentItem.case_pack) /
                                                        parseInt(data.shipmentItem.pack_of);
                                                    let numberOf_boxes = Math.floor(case_in_hand);

                                                    $("#per_box_item_count_0").val(unitsPer_boxes);
                                                    $("#no_of_boxes_count_0").val(numberOf_boxes);

                                                    let totQty = unitsPer_boxes * numberOf_boxes;

                                                    $("#tot_qty_0").val(totQty);
                                                }
                                            }
                                        }
                                    }

                                    if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem
                                        .qty !== null) {
                                        var remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                                        $("#box_lbl_product_remain").html("<b>" + remainQty + "</b>");
                                        $("#remaining_qty").val(remainQty);
                                    }

                                    $("#bx_totalShippedUnits").val(data.totalShippedUnits);
                                    $("#fba_shipment_item_id").val(itemId);

                                    if (data.shipmentItem.asin_weight > 0) {
                                        $("#asin_weight").val(data.shipmentItem.asin_weight);
                                        $("#old_asin_weight").val(data.shipmentItem.asin_weight);
                                        getPerBoxItemCount(data.shipmentItem.asin_weight);
                                    }
                                } else {
                                    $("#newinput").html("");
                                    $("#extraBoxWarning").html("");

                                    $("#first").val(0);
                                    $("#sixt").val(0);

                                    $("#totQanHtm").html("");
                                }

                                if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !==
                                    null) {
                                    var remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                                    $("#box_lbl_product_remain").html("<b>" + remainQty + "</b>");
                                    $("#remaining_qty").val(remainQty);
                                }

                                $("#bx_totalShippedUnits").val(data.totalShippedUnits);
                                $("#fba_shipment_item_id").val(itemId);

                                $("#bx_shipment_name").val(data.shipment.shipment_name);

                                $("#bx_fba_shipment_id").val(data.shipment.shipment_id);

                                $("#bx_destination").val(data.shipment.destination_fulfillment_center_id);

                                var bx_totalDoneUnits = $('#total_done_unit').val();
                                $("#bx_totalDoneUnits").val(bx_totalDoneUnits);

                                if (data.shipmentItem.asin_weight > 0) {
                                    $("#asin_weight").val(data.shipmentItem.asin_weight);
                                    $("#old_asin_weight").val(data.shipmentItem.asin_weight);
                                    getPerBoxItemCount(data.shipmentItem.asin_weight);
                                }
                            }

                            if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !==
                                null) {
                                var remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                                $("#box_lbl_product_remain").html("<b>" + remainQty + "</b>");
                                $("#remaining_qty").val(remainQty);
                            }

                            $("#bx_totalShippedUnits").val(data.totalShippedUnits);
                            $("#fba_shipment_item_id").val(itemId);

                            if (data.shipmentItem.asin_weight > 0) {
                                $("#asin_weight").val(data.shipmentItem.asin_weight);
                                $("#old_asin_weight").val(data.shipmentItem.asin_weight);
                                getPerBoxItemCount(data.shipmentItem.asin_weight);
                            }

                            //PO Number associate calculations...
                            //Check the PO Number associate with this ASIN...
                            if (printType != "" && printType == "2D") {
                                let asinWeight = $("#old_asin_weight").val();
                                if (typeof(asinWeight) != "undefined" && asinWeight !== null && asinWeight !==
                                    '' && asinWeight > 0) {
                                    if (typeof(data.shipmentItem.po_number) != "undefined" && data.shipmentItem
                                        .po_number !== null) {
                                        //Check the Case Pack exist...
                                        if (typeof(data.shipmentItem.case_pack) != "undefined" && data
                                            .shipmentItem.case_pack !== null && data.shipmentItem.case_pack !==
                                            '' && data.shipmentItem.case_pack > 1) {
                                            //Check the A Pack exist...
                                            if (typeof(data.shipmentItem.pack_of) != "undefined" && data
                                                .shipmentItem.a_pack !== null && data.shipmentItem.pack_of !==
                                                '' && data.shipmentItem.pack_of > 0) {
                                                // ( 1.) When Case In Hand is not empty...
                                                let caseInHand = $("#updcasesInHand").text();

                                                if (typeof(caseInHand) != "undefined" && caseInHand !== null &&
                                                    caseInHand !== "") {
                                                    let units_per_boxes = parseInt(data.shipmentItem
                                                        .case_pack) / parseInt(data.shipmentItem.pack_of);

                                                    let Units_Per_Box_Count = units_per_boxes;

                                                    // Check IF Unit per box is in decimal...
                                                    if (typeof(Units_Per_Box_Count) != "undefined" &&
                                                        Units_Per_Box_Count !== null && isFloat(
                                                            Units_Per_Box_Count)) {
                                                        Units_Per_Box_Count = Math.floor(Units_Per_Box_Count);
                                                    }
                                                    let number_of_boxes = Math.floor(caseInHand);
                                                    $("#casesInHandQty").text(caseInHand);
                                                    let item_labels_printed = $("#changeItemPrintCount").text();
                                                    let totQty = 0;

                                                    if (Units_Per_Box_Count > 0) {

                                                        $("#per_box_item_count_0").val(Units_Per_Box_Count);
                                                        $("#no_of_boxes_count_0").val(number_of_boxes);
                                                        $("#unitPerBoxesQty").text(Units_Per_Box_Count);

                                                        totQty = Units_Per_Box_Count * number_of_boxes;
                                                        $("#tot_qty_0").val(totQty);

                                                        if (typeof(item_labels_printed) != "undefined" &&
                                                            item_labels_printed !== null &&
                                                            item_labels_printed != "" && isFloat(
                                                                units_per_boxes)) {

                                                            let secondUnitBoxQty = parseInt(
                                                                item_labels_printed) - parseInt(totQty);

                                                            //new input...
                                                            let httml = '';
                                                            if (secondUnitBoxQty > 0) {
                                                                $("#unitPerBoxesQty_second").text(
                                                                    secondUnitBoxQty);

                                                                httml = getHtml(secondUnitBoxQty);
                                                                $("#newinput").html(httml);

                                                                $("#extraBoxWarning").html(
                                                                    "<strong style='color:red;font-weight: 700;'>The system recommends an extra box here. Please review before printing the box labels.</strong>"
                                                                    );

                                                                $("#first").val(parseInt(totQty));
                                                                $("#sixt").val(parseInt(secondUnitBoxQty));
                                                                $("#add_more_index_extra").val("6");
                                                                let grandTotal = parseInt(totQty) + parseInt(
                                                                    secondUnitBoxQty);
                                                                $("#totQanHtm").html(
                                                                    '<b class="mb-0">Total: </b><span id="totQanty" style="font-size:15px;">' +
                                                                    grandTotal + '</span>');
                                                            } else {
                                                                $("#unitPerBoxesQty_second").text("");

                                                                $("#newinput").html("");
                                                                $("#extraBoxWarning").html("");
                                                                $("#add_more_index_extra").val("0");
                                                                $("#first").val(0);
                                                                $("#sixt").val(0);

                                                                $("#totQanHtm").html("");
                                                            }

                                                        } else {
                                                            $("#newinput").html("");
                                                            $("#extraBoxWarning").html("");
                                                            $("#add_more_index_extra").val("0");
                                                            $("#first").val(0);
                                                            $("#sixt").val(0);

                                                            $("#totQanHtm").html("");
                                                        }
                                                    } else {
                                                        $("#per_box_item_count_0").val("");
                                                        $("#no_of_boxes_count_0").val("");
                                                        $("#unitPerBoxesQty").text("");
                                                        $("#tot_qty_0").val("");
                                                    }

                                                    checkUnitsPerBoxVsMaxBoxQty(Units_Per_Box_Count);
                                                } else {

                                                    // ( 2.) When Case In Hand is empty...
                                                    let item_labels_printed = $("#updItemPrintCount").text();
                                                    let case_in_hand = parseInt(data.shipmentItem.pack_of) /
                                                        parseInt(data.shipmentItem.case_pack) * parseInt(
                                                            item_labels_printed);

                                                    let unitsPer_boxes = parseInt(data.shipmentItem.case_pack) /
                                                        parseInt(data.shipmentItem.pack_of);

                                                    let unitsPer_boxes_Count = unitsPer_boxes;
                                                    // Check IF Unit per box is in decimal...
                                                    if (typeof(unitsPer_boxes_Count) != "undefined" &&
                                                        unitsPer_boxes_Count !== null && isFloat(
                                                            unitsPer_boxes_Count)) {
                                                        unitsPer_boxes_Count = Math.floor(unitsPer_boxes_Count);
                                                    }

                                                    let numberOf_boxes = Math.floor(case_in_hand);
                                                    $("#casesInHandQty").text(case_in_hand);
                                                    let itemLabels_printed = $("#changeItemPrintCount").text();
                                                    let totQty = 0;
                                                    if (unitsPer_boxes_Count > 0) {
                                                        $("#per_box_item_count_0").val(unitsPer_boxes_Count);
                                                        $("#no_of_boxes_count_0").val(numberOf_boxes);
                                                        $("#unitPerBoxesQty").text(unitsPer_boxes_Count);

                                                        totQty = unitsPer_boxes_Count * numberOf_boxes;
                                                        $("#tot_qty_0").val(totQty);

                                                        if (typeof(itemLabels_printed) != "undefined" &&
                                                            itemLabels_printed !== null && itemLabels_printed !=
                                                            "" && isFloat(unitsPer_boxes)) {

                                                            let secondUnitBoxQt = parseInt(itemLabels_printed) -
                                                                parseInt(totQty);

                                                            //new input...
                                                            let htmtl = '';
                                                            if (secondUnitBoxQt > 0) {
                                                                $("#unitPerBoxesQty_second").text(
                                                                    secondUnitBoxQt);

                                                                htmtl = getHtml(secondUnitBoxQt);
                                                                $("#newinput").html(htmtl);
                                                                $("#extraBoxWarning").html(
                                                                    "<strong style='color:red;font-weight: 700;'>The system recommends an extra box here. Please review before printing the box labels.</strong>"
                                                                    );

                                                                $("#first").val(parseInt(totQty));
                                                                $("#sixt").val(parseInt(secondUnitBoxQt));
                                                                $("#add_more_index_extra").val("6");
                                                                let grandTotal = parseInt(totQty) + parseInt(
                                                                    secondUnitBoxQt);
                                                                $("#totQanHtm").html(
                                                                    '<b class="mb-0">Total: </b><span id="totQanty" style="font-size:15px;">' +
                                                                    grandTotal + '</span>');
                                                            } else {
                                                                $("#unitPerBoxesQty_second").text("");

                                                                $("#newinput").html("");
                                                                $("#extraBoxWarning").html("");
                                                                $("#add_more_index_extra").val("0");
                                                                $("#first").val(0);
                                                                $("#sixt").val(0);

                                                                $("#totQanHtm").html("");
                                                            }
                                                        } else {
                                                            $("#newinput").html("");
                                                            $("#extraBoxWarning").html("");
                                                            $("#add_more_index_extra").val("0");
                                                            $("#first").val(0);
                                                            $("#sixt").val(0);

                                                            $("#totQanHtm").html("");
                                                        }
                                                    } else {
                                                        $("#per_box_item_count_0").val("");
                                                        $("#no_of_boxes_count_0").val("");
                                                        $("#unitPerBoxesQty").text("");
                                                        $("#tot_qty_0").val("");
                                                    }

                                                    checkUnitsPerBoxVsMaxBoxQty(unitsPer_boxes_Count);
                                                }
                                            }
                                        }
                                    } else {
                                        $("#newinput").html("");
                                        $("#extraBoxWarning").html("");

                                        $("#first").val(0);
                                        $("#sixt").val(0);

                                        $("#totQanHtm").html("");
                                    }
                                } else {
                                    $("#newinput").html("");
                                    $("#extraBoxWarning").html("");

                                    $("#first").val(0);
                                    $("#sixt").val(0);

                                    $("#totQanHtm").html("");
                                }
                            }

                            var bx_shipment_name = "{{ $shipment->shipment_name }}";
                            $("#bx_shipment_name").val(bx_shipment_name);

                            var bx_fba_shipment_id =
                                "{{ !empty($shipment->shipment_id) ? $shipment->shipment_id : '' }}";
                            $("#bx_fba_shipment_id").val(bx_fba_shipment_id);

                            var bx_destination =
                                "{{ !empty($shipment->destination_fulfillment_center_id) ? $shipment->destination_fulfillment_center_id : '' }}";
                            $("#bx_destination").val(bx_destination);

                            var bx_totalDoneUnits = "{{ $totalDoneUnits }}";
                            $("#bx_totalDoneUnits").val(bx_totalDoneUnits);

                            //Check Product SKU is validated or not for print Item Lables...
                            if (typeof(data.shipmentItem.is_validated) != "undefined" && data.shipmentItem
                                .is_validated !== null) {
                                $("#is_product_bx_validated").val(data.shipmentItem.is_validated);
                                if (data.shipmentItem.is_validated == "0") {

                                    if (printType == "2D") {
                                        $("#2dbox").removeAttr("disabled");
                                        $("#infoBoxIco").html('');
                                    }

                                    if (printType == "3D") {
                                        $("#3dbox").attr("disabled", "disabled");

                                        $("#infoBoxIco").html(
                                            '<i class="fa-duotone fa-circle-info" style="--fa-primary-color: #000; --fa-primary-opacity: 1.0; --fa-secondary-color: #FFF019; --fa-secondary-opacity: 1.0;font-size: 35px;cursor: pointer;" data-toggle="tooltip" data-placement="top" title="User has not cross checked the product on the amazon Listing. Click on the product image to match the product."></i>'
                                            );
                                    }

                                } else {
                                    if (printType == "3D") {
                                        $("#3dbox").removeAttr("disabled");
                                        $("#infoBoxIco").html('');
                                    }
                                }
                            }

                        } else {
                            displayErrorMessage(data.message);
                        }
                    },
                    error: function(xhr, err) {
                        isShowBoxLabelPrintModal = false;
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }

            $("#printLabels").modal('hide');
            $("#printBoxLabels").modal('show');

            $(".printBoxLabels").on('shown.bs.modal', function() {
                $(".exp_box_date").daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    minYear: 2021,
                    maxYear: 2032,
                    minDate: "<?php echo date('m/d/Y', $maxDate); ?>",
                    drops: 'up',
                    showDropdowns: true,
                    "autoApply": true,
                    locale: {
                        format: "MM/DD/YYYY",
                    },
                }).off('focus');

                $(".exp_box_date").on("apply.daterangepicker", function(ev, picker) {
                    $(this).val(picker.startDate.format("MM/DD/YYYY"));
                });

                $(".exp_box_date").on("click", function() {
                    $(".daterangepicker").css("z-index", "1065");
                });
            });
        }

        function getHtml(secondUnitBoxQt) {
            let defaultDate = $("#exp_date_0").val();

            var html = '';
            if (secondUnitBoxQt > 0) {
                html =
                    '<div id="myhtm"><div class="row align-items-start my-2"><div class="col-10 d-flex px-1"><div class="px-1 w-25"><input type="text" class="form-control numberonlyText" id="per_box_item_count_6" name="per_box_item_count[]" value="' +
                    secondUnitBoxQt +
                    '" onchange="getCalculateTotal(6);getGrandTotal(6);" onkeyup="getErrorNo(this.id);" autocomplete="off"><span id="error_per_box_item_count_6" style="color:red;"></span></div><div class="px-1 w-25"><input type="text" class="form-control numberonlyText" name="no_of_boxes_count[]" id="no_of_boxes_count_6" value="1" onchange="getCalculateTotal(6);getGrandTotal(6);" onkeyup="getErrorNo(this.id);" autocomplete="off"><span id="error_no_of_boxes_count_6" style="color:red;"></span></div><div class="px-1 w-80px"><input type="text" class="form-control bg-gray-400" id="tot_qty_6" value="' +
                    secondUnitBoxQt +
                    '" name="tot_qty[]" readonly="" style="background: #DFDFDF;"><span id="error_tot_qty_6" style="color:red;"></span></div><div class="px-1 w-150px"><div class="input-group date datepicker" id="exp_date_datepicker_6"><input type="text" class="form-control exp_box_date numberonly" name="expiry_box_date[]" id="exp_date_6" value="' +
                    defaultDate +
                    '" onclick="getErrorNo(this.id);" onchange="validateDate(this.value);" autocomplete="off"><span class="input-group-append"><span class="input-group-text bg-light d-block"><i class="fa fa-calendar"></i></span></span><span id="error_exp_date_6" style="color:red;"></span></div></div></div><div class="col text-center align-self-center px-1"><a href="javascrip:;" class="remove_button_once" data-id="6" data-repeater-delete=""><i class="fa-solid fa-trash"></i></a></div></div></div>';
            }

            return html;
        }

        $("body").on("click", ".remove_button_once", function() {
            $(this).parents(".newinput div").remove();
            var indx = 6;

            if (indx > 0) {
                //$("#add_more_index").val(parseInt(indx)-1);
                var totQanty = $("#totQanty").html();

                var bxQty = 0;
                var incrt = $(this).attr("data-id");
                if (incrt == "6") {
                    bxQty = $("#sixt").val();
                }

                if (parseInt(totQanty) > 0 && parseInt(bxQty) > 0) {
                    $("#totQanty").html(parseInt(totQanty) - parseInt(bxQty));
                    if (incrt == "6") {
                        $("#sixt").val("0");
                        $("#add_more_index_extra").val("0");
                    }
                }
            }

        });

        function checkUnitsPerBoxVsMaxBoxQty(units_per_boxes) {
            // (3.) When Units per Box (as calculated above) is > Maximum Box Qty
            var maximumBoxQty = $("#maximumBoxQty").text();
            if (typeof(maximumBoxQty) != "undefined" && maximumBoxQty !== null && maximumBoxQty !== "" &&
                units_per_boxes !== "" && units_per_boxes > 0) {
                //check the condition given..
                if (units_per_boxes > parseInt(maximumBoxQty)) {
                    $("#per_box_item_count_0").val("");
                    $("#no_of_boxes_count_0").val("");
                    $("#tot_qty_0").val("");
                }
            } else {
                $("#per_box_item_count_0").val("");
                $("#no_of_boxes_count_0").val("");
                $("#tot_qty_0").val("");
            }
        }

        $('body').on('click', '.printIcn', function() {
            var maxDate = "{{ date('m/d/Y', strtotime(date('Y-m-d') . '+ 105 days')) }}";
            $("#max_expiry_date").val(maxDate);
            $("#item_max_expiry_date").val(maxDate);

            var itemId = $(this).attr('data-id');
            $("#actItmId").val($(this).attr('data-id'));
            var shipmentId = "{{ $shipment->shipment_id }}";
            var a_pack = $(this).attr('data-a_pack');
            var case_pack = $(this).attr('data-case_pack');

            if (case_pack > 3 && parseFloat(case_pack) == a_pack) {
                Swal.fire({
                    title: "Are You sure?",
                    text: "The Case Pack is equal to A Pack, hence the system suggests to Print 3 in 1 Box Label. Are you sure you want to Print 2 in 1 Box Label?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#009ef7',
                    confirmButtonText: 'Yes',
                    cancelButtonColor: '#d33',
                    cancelButtonText: "No"
                }).then(function(result) {
                    if (result.isConfirmed) {
                        printPrintSingleItemLabel(itemId, shipmentId)
                    }
                });
            } else {
                printPrintSingleItemLabel(itemId, shipmentId)
            }
        });

        function chkIsProductValidated(asin, itemId, type, is_valid) {
            
            if (asin != "" && itemId != "" && type != "" && is_valid != "")
            {

                var amazonProductUrl = "https://www.amazon.com/dp/" + asin;
                window.open(amazonProductUrl, '_blank');
                if (type == 'items')
                {
                    $("#chkItemPrintValidated").addClass('getGenerateItemLabel');
                    $(".chkNxtBtn").removeAttr("disabled");
                    $("#chkItemPrintValidated").css('opacity', '1.0');
                    $(".chkNxtBtn").css('opacity', '1.0');
                    $("#infoIco").html('');
                }

                if (type == 'boxes')
                {
                    $("#2dbox").removeAttr("disabled");
                    $("#3dbox").removeAttr("disabled");
                    $("#infoBoxIco").html('');
                }
            }
        }

        // print item label and 2in1 box label
        var isShowItemLabelPrintModal = false;

        function printPrintSingleItemLabel(itemId, shipmentId) {
            if (!isShowItemLabelPrintModal) {
                isShowItemLabelPrintModal = true;
                $.ajax({
                    url: "{{ url('fba-shipment/get-single-item-label-data') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    data: {
                        itemId: itemId,
                        shipmentId: shipmentId
                    },
                    async: false,
                    beforeSend: function() {
                        //show_loader();
                        $("#item-mod-title").text("-");
                        $("#productTitle").val("-");
                        $("#item_lbl_title").text("-");
                        $("#productFNsku").val("-");
                        $("#item_lbl_fnsku").text("-");
                        $("#item_lbl_sku").text("-");
                        $("#item_lbl_cpack").text("-");
                        $("#item_lbl_apack").text("-");
                        $("#item_lbl_qty").text("-");
                        $("#remaing_lbl_qty").text("-");
                        $("#item_lbl_instruction").text("-");
                        $("#item_lbl_prep_note").text("-");

                        var imgUrl = "<?php echo asset('media/no-image.jpeg'); ?>";
                        $("#item_lbl_img").html('<img src="' + imgUrl +
                            '" class="w-100 p-3 border border-gray-300 rounded" alt="">');
                    },
                    success: function(data) {
                        // hide_loader();
                        isShowItemLabelPrintModal = false;
                        if (data.type == 'success')
                        {
                            if (typeof(data.shipmentItem.amazon_data) != "undefined" && data.shipmentItem.amazon_data !== null)
                            {
                                $("#item-mod-title").text(data.shipmentItem.amazon_data.fnsku);
                                $("#productTitle").val(data.shipmentItem.amazon_data.title);
                                $("#item_lbl_title").text(data.shipmentItem.amazon_data.title);
                                $("#productFNsku").val(data.shipmentItem.amazon_data.fnsku);
                                $("#item_lbl_fnsku").text(data.shipmentItem.amazon_data.fnsku);

                                if (data.shipmentItem.amazon_data.sku !== null && typeof(data.shipmentItem.amazon_data.sku) != "undefined")
                                {
                                    $(".skusLink").show();
                                    $("#item_lbl_sku").html(data.shipmentItem.amazon_data.sku);
                                } else {
                                    $(".skusLink").hide();
                                }

                                var smallImgUrl = data.shipmentItem.amazon_data.main_image;
                                var typ = "items";
                                if (typeof(smallImgUrl) != "undefined" && smallImgUrl !== null)
                                {
                                    var largemageUrl = smallImgUrl.replace('_SL75_', '_SL500_');
                                    if (typeof(largemageUrl) != "undefined" && largemageUrl !== null)
                                    {
                                        $("#item_lbl_img").html(
                                            '<a href="javascript:void" onclick="chkIsProductValidated(\'' +
                                            data.shipmentItem.amazon_data.asin + '\',\'' + itemId +
                                            '\',\'' + typ + '\',\'' + data.shipmentItem.is_validated +
                                            '\');"><img src="' + largemageUrl +
                                            '" class="w-100 p-3 border border-gray-300 rounded" alt="" style="width: auto !important;max-height: 250px;max-width: 250px;"></a>'
                                            );
                                    }
                                }
                            }

                            //if Case Pack will be greater then 1 and po number is not null then cases needed input field will be displayed.
                            $("#item_cases_needed").hide();
                            $("#casesDiv").removeClass("d-flex");
                            $("#casesDiv").hide();

                            //Check the PO Number is associate with this ASIN...
                            if (typeof(data.shipmentItem.po_number) != "undefined" && data.shipmentItem.po_number !== null)
                            {
                                //Check the Case Pack exist...
                                if (typeof(data.shipmentItem.case_pack) != "undefined" && data.shipmentItem
                                    .case_pack !== null && data.shipmentItem.case_pack !== '' && data
                                    .shipmentItem.case_pack > 1)
                                {
                                    //Check the A Pack exist...
                                    if (typeof(data.shipmentItem.pack_of) != "undefined" && data.shipmentItem
                                        .a_pack !== null && data.shipmentItem.pack_of !== '' && data.shipmentItem
                                        .a_pack > 0)
                                    {
                                        $("#item_cases_needed").show();

                                        let remainQty = 1;
                                        if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !== null)
                                        {
                                            remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                                        }

                                        let casesNeeded = remainQty * parseInt(data.shipmentItem.pack_of) / parseInt(data.shipmentItem.case_pack);
                                        $("#cases_needed").text(Math.floor(casesNeeded));

                                        //Cases In Hand...
                                        $("#casesDiv").addClass("d-flex");
                                        $("#casesDiv").show();
                                    }
                                }
                            }

                            if (typeof(data.shipmentItem.asin_weight) != "undefined" && data.shipmentItem
                                .asin_weight !== null && data.shipmentItem.asin_weight > 0)
                            {
                                getPerBoxItemCount(data.shipmentItem.asin_weight);
                            }

                            if (typeof(data.shipmentItem.case_pack) != "undefined" && data.shipmentItem
                                .case_pack !== null) {
                                $("#item_lbl_cpack").html("<b>" + data.shipmentItem.case_pack + "</b>");
                            } else if (typeof(data.shipmentItem.amazon_data.amazon_product_case_pack) !=
                                "undefined" && data.shipmentItem.amazon_data.amazon_product_case_pack !== null
                                ) {
                                $("#item_lbl_cpack").html("<b>" + data.shipmentItem.amazon_data
                                    .amazon_product_case_pack + "</b>");
                            }

                            if (typeof(data.shipmentItem.pack_of) != "undefined" && data.shipmentItem.pack_of !==
                                null) {
                                var aPck = getAPackFormatedValue(data.shipmentItem.pack_of);
                                $("#item_lbl_apack").html("<b>" + aPck + "</b>");
                            } else if (typeof(data.shipmentItem.amazon_data.amazon_product_a_pack) !=
                                "undefined" && data.shipmentItem.amazon_data.amazon_product_a_pack !== null) {
                                // var aPck = getAPackFormatedValue(data.shipmentItem.amazon_data
                                //     .amazon_product_a_pack);
                                var aPck = data.shipmentItem.amazon_data.amazon_product_a_pack;
                                $("#item_lbl_apack").html("<b>" + aPck + "</b>");
                            }

                            if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !==
                                null) {
                                $("#item_lbl_qty").html("<b>" + data.shipmentItem.qty + "</b>");
                            }

                            if (data.prepInstructionsArr[data.prep_instruction_status] !== null) {
                                $("#item_lbl_instruction").html(data.prepInstructionsArr[data
                                    .prep_instruction_status]);
                            }
                            if (typeof(data.shipmentItem.prep_note) != "undefined" && data.shipmentItem
                                .prep_note !== null) {
                                var noto = data.shipmentItem.prep_note;
                                var prepNt = noto.substring(0, 255);
                                $("#item_lbl_prep_note").removeClass('text-black mb-0');
                                $("#item_lbl_prep_note").addClass('alert alert-danger fw-700 mb-0 p-3');
                                $("#item_lbl_prep_note").html(prepNt);
                            } else {
                                $("#item_lbl_prep_note").addClass('text-black mb-0');
                                $("#item_lbl_prep_note").removeClass('alert alert-danger fw-700 mb-0 p-3');
                            }
                            if (typeof(data.shipmentItem.qty) != "undefined" && data.shipmentItem.qty !==
                                null) {
                                var remainQty = data.shipmentItem.qty - data.shipmentItem.done_qty;
                                
                                if (remainQty <= 0) {
                                    remainQty = 1;
                                    $("#printBoxLabelsModal").hide();
                                    $(".bxlbl").hide();
                                } else {
                                    $("#printBoxLabelsModal").show();
                                    $(".bxlbl").show();
                                }

                                $("#itemPrintCount").val(remainQty);
                                $("#updItemPrintCount").text(remainQty);
                                $("#changeItemPrintCount").text(remainQty);
                                $("#updcasesInHand").text("");
                                $("#remaing_lbl_qty").html("<b>" + remainQty + "</b>");
                            }

                            //Check Product SKU is validated or not for print Item Lables...
                            if (typeof(data.shipmentItem.is_validated) != "undefined" && data.shipmentItem
                                .is_validated !== null)
                            {
                                $("#is_product_validated").val(data.shipmentItem.is_validated);
                                // if (data.shipmentItem.is_validated == "0") {
                                //     $("#chkItemPrintValidated").removeClass('getGenerateItemLabel');
                                //     $(".chkNxtBtn").attr("disabled", "disabled");

                                //     $("#chkItemPrintValidated").css('opacity', '0.5');
                                //     $(".chkNxtBtn").css('opacity', '0.5');

                                //     $("#infoIco").html(
                                //         '<i class="fa-duotone fa-circle-info" style="--fa-primary-color: #000; --fa-primary-opacity: 1.0; --fa-secondary-color: #FFF019; --fa-secondary-opacity: 1.0;font-size: 35px;cursor: pointer;" data-toggle="tooltip" data-placement="top" title="User has not cross checked the product on the amazon Listing. Click on the product image to match the product."></i>'
                                //         );
                                // } else {
                                    $("#chkItemPrintValidated").addClass('getGenerateItemLabel');
                                    // $(".chkNxtBtn").removeAttr("disabled");

                                    $("#chkItemPrintValidated").css('opacity', '1.0');

                                    if ((data.shipmentItem.qty - data.shipmentItem.done_qty) <= 0)
                                    {
                                        $(".chkNxtBtn").css('opacity', '0.0');
                                    }else{
                                        $(".chkNxtBtn").css('opacity', '1.0');
                                    }

                                    $("#infoIco").html('');
                                // }
                            }
                        } else {
                            displayErrorMessage(data.message);
                        }
                    },
                    error: function(xhr, err) {
                        isShowItemLabelPrintModal = false;
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }

            //Show modal...
            $("#printLabels").modal('show');
            $("#printBoxLabels").modal('hide');

            $("#printLabels").on('shown.bs.modal', function() {
                $(".expDate").daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    minYear: 2021,
                    maxYear: 2032,
                    autoclose: true,
                    minDate: "<?php echo date('m/d/Y', $maxDate); ?>",
                    drops: 'up',
                    showDropdowns: true,
                    "autoApply": true,
                    locale: {
                        format: "MM/DD/YYYY",
                    },
                }).off('focus');

                $(".expDate").on("apply.daterangepicker", function(ev, picker) {
                    $(this).val(picker.startDate.format("MM/DD/YYYY"));
                    $(".exp_box_date").val(picker.startDate.format("MM/DD/YYYY"));
                })

                $(".expDate").on("click", function() {
                    $(".daterangepicker").css("z-index", "1065");
                });
            });
        }

        $('body').on('keyup', '#itemPrintCount', function() {

            try {
                window.clearTimeout(timeoutID);
            } catch (e) {}
            timeoutID = window.setTimeout(updItemCount, 200); //delay

            function updItemCount() {
                let itemPrintCount = $("#itemPrintCount").val();
                $("#updItemPrintCount").text(itemPrintCount);
                $("#changeItemPrintCount").text(itemPrintCount);

            }
        });

        $('body').on('keyup', '#casesInHand', function() {

            try {
                window.clearTimeout(timeoutID);
            } catch (e) {}
            timeoutID = window.setTimeout(calCasesInHand, 500); //delay

            function calCasesInHand() {
                let casesInHand = $("#casesInHand").val();
                $("#updcasesInHand").text(casesInHand);
                if (typeof(casesInHand) != "undefined" && casesInHand !== null && casesInHand !== "") {
                    //Item label to print = Cases in Hand * (Case Pack/A Pack)
                    let case_pack = $("#item_lbl_cpack").text();
                    let a_pack = $("#item_lbl_apack").text();

                    if (typeof(case_pack) != "undefined" && case_pack !== null && case_pack !== "" && typeof(
                        a_pack) != "undefined" && a_pack !== null && a_pack !== "") {
                        let itemLabelPrint = parseInt(casesInHand) * parseInt(case_pack) / parseInt(a_pack);

                        if (typeof(itemLabelPrint) != "undefined" && itemLabelPrint !== null && Number.isInteger(
                                itemLabelPrint)) {
                            $("#warning_itemPrintCount").html("");
                        }

                        if (typeof(itemLabelPrint) != "undefined" && itemLabelPrint !== null && isFloat(
                                itemLabelPrint)) {
                            $("#warning_itemPrintCount").html("<b>The user will have some extra unit in hand.</b>");
                        }

                        $("#itemPrintCount").val(Math.floor(itemLabelPrint));
                        $("#changeItemPrintCount").text(Math.floor(itemLabelPrint));
                    } else {
                        let remaing_lbl_qty = $("#remaing_lbl_qty").text();
                        $("#itemPrintCount").val(parseInt(remaing_lbl_qty));
                        $("#changeItemPrintCount").text(parseInt(remaing_lbl_qty));
                    }
                } else {
                    let remaing_lbl_qty = $("#remaing_lbl_qty").text();
                    $("#itemPrintCount").val(parseInt(remaing_lbl_qty));
                }
            }
        });

        function isFloat(n) {
            return Number(n) === n && n % 1 !== 0;
        }

        function getAPackFormatedValue(aPack) {
            var aPackValue = aPack.split('.');
            var decimalValue = 0;
            if (typeof aPackValue[1] == "undefined" || aPackValue[1] != null) {
                decimalValue = aPackValue[1];
            }

            if (decimalValue != "" && decimalValue != '000') {
                return aPack.toLocaleString(3);
            } else {
                return parseInt(aPack);
            }
        }

        $(document).ready(function() {
            var maxFieldLimit = 4;
            var x = 1; //Initial field counter is 1
            $("body").on("click", ".add-more", function() {
                var itemId = $("#actItmId").val();
                if (x <= maxFieldLimit) {

                    var newRowAdd =
                        '<div><div class="row align-items-start my-2"><div class="col-10 d-flex px-1"><div class="px-1 w-25"><input type="text" class="form-control numberonlyText" id="per_box_item_count_' +
                        x + '" name="per_box_item_count[]" onchange="getCalculateTotal(' + x +
                        ');getGrandTotal(' + x +
                        ');" onkeyup="getErrorNo(this.id);" autocomplete="off"><span id="error_per_box_item_count_' +
                        x + '" style="color:red;"></span></div>' +
                        '<div class="px-1 w-25"><input type="text" class="form-control numberonlyText" name="no_of_boxes_count[]" id="no_of_boxes_count_' +
                        x + '" onchange="getCalculateTotal(' + x + ');getGrandTotal(' + x +
                        ');" onkeyup="getErrorNo(this.id);" autocomplete="off"><span id="error_no_of_boxes_count_' +
                        x + '" style="color:red;"></span></div>' +
                        '<div class="px-1 w-80px"><input type="text" class="form-control bg-gray-400" value="" id="tot_qty_' +
                        x +
                        '" name="tot_qty[]" readonly style="background: #DFDFDF;"><span id="error_tot_qty_' +
                        x + '" style="color:red;"></span></div>' +
                        '<div class="px-1 w-150px"><div class="input-group date datepicker" id="exp_date_datepicker_' +
                        x +
                        '"><input type="text" class="form-control exp_box_date numberonly" name="expiry_box_date[]" id="exp_date_' +
                        x +
                        '" onclick="getErrorNo(this.id);" onchange="validateDate(this.value);" autocomplete="off" /><span class="input-group-append"><span class="input-group-text bg-light d-block"><i class="fa fa-calendar"></i></span></span><span id="error_exp_date_' +
                        x + '" style="color:red;"></span></div></div></div>' +
                        '<div class="col text-center align-self-center px-1">' +
                        '<a href="javascrip:;" class="remove_button" data-id="' + x +
                        '" data-repeater-delete><i class="fa-solid fa-trash"></i></a>' +
                        '</div></div></div>';

                    $('#newinput').append(newRowAdd);

                    /////// default date display start///////
                    var checkBox = document.getElementById("myCheck");
                    const newDtte = new Date();
                    newDtte.setFullYear(newDtte.getFullYear() + 4);

                    var yyyy = newDtte.getFullYear().toString();
                    var mm = (newDtte.getMonth() + 1).toString();
                    var dd = newDtte.getDate().toString();

                    var mmChars = mm.split('');
                    var ddChars = dd.split('');
                    var newClosingDate = yyyy + '/' + (mmChars[1] ? mm : "0" + mmChars[0]) + '/' + (ddChars[
                        1] ? dd : "0" + ddChars[0]);

                    var nndate = newClosingDate.split('/');
                    var nnwData = nndate[1] + '/' + nndate[2] + '/' + nndate[0];
                    if (checkBox.checked == true) {
                        $(".exp_box_date").val(nnwData);
                    } else {
                        var firstDt = $("#exp_date_0").val();
                        if (firstDt != "") {
                            $(".exp_box_date").val(firstDt);
                        }
                    }
                    /////// default date display end///////

                    $("#exp_date_" + x).daterangepicker({

                        singleDatePicker: true,
                        autoUpdateInput: false,
                        minYear: 2021,
                        maxYear: 2032,
                        minDate: "<?php echo date('m/d/Y', $maxDate); ?>",
                        drops: 'up',
                        showDropdowns: true,
                        "autoApply": true,
                        locale: {
                            format: "MM/DD/YYYY",
                        },
                    }).off('focus');

                    $("#exp_date_" + x).on("apply.daterangepicker", function(ev, picker) {
                        $(this).val(picker.startDate.format("MM/DD/YYYY"));
                    });

                    $("#exp_date_" + x).on("click", function() {
                        $(".daterangepicker").css("z-index", "1065");
                    });
                    var indxn = $("#add_more_index").val();
                    $("#add_more_index").val(parseInt(indxn) + 1);

                    x++;
                }
            });

            $("body").on("click", ".remove_button", function() {
                $(this).parents(".newinput div").remove();
                var indx = $("#add_more_index").val();
                if (indx > 0) {
                    $("#add_more_index").val(parseInt(indx) - 1);
                    var totQanty = $("#totQanty").html();

                    var bxQty = 0;
                    var incrt = $(this).attr("data-id");
                    if (incrt == "1") {
                        bxQty = $("#second").val();
                    }
                    if (incrt == "2") {
                        bxQty = $("#third").val();
                    }
                    if (incrt == "3") {
                        bxQty = $("#fourth").val();
                    }
                    if (incrt == "4") {
                        bxQty = $("#fifth").val();
                    }

                    if (parseInt(totQanty) > 0 && parseInt(bxQty) > 0) {
                        $("#totQanty").html(parseInt(totQanty) - parseInt(bxQty));
                        if (incrt == "1") {
                            $("#second").val("0");
                        }
                        if (incrt == "2") {
                            $("#third").val("0");
                        }
                        if (incrt == "3") {
                            $("#fourth").val("0");
                        }
                        if (incrt == "4") {
                            $("#fifth").val("0");
                        }
                    }
                }
                x--;
                return false;
            });
        });
    </script>

    <script>
        var isItemLabelPrint = false;
        //generate Item Label
        $('body').on('click', '.getGenerateItemLabel', function() {
            var itemId = $("#actItmId").val();
            var shipmentId = "{{ $shipment->shipment_id }}";
            if (itemId != '' && !isItemLabelPrint) {

                isItemLabelPrint = true;

                var number_of_label = $("#itemPrintCount").val();
                if (number_of_label == "") {
                    //$("#error_itemPrintCount").text("Please enter item label qty.");
                    Swal.fire({
                        title: 'Please enter item label qty',
                        //text: "",
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                    isItemLabelPrint = false;
                    return false;
                } else if (number_of_label == "0") {
                    Swal.fire({
                        title: 'Please enter item label qty',
                        //text: "",
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                    isItemLabelPrint = false;
                    return false;
                }
                var expire_date = $("#expDate").val();
                // if(expire_date == ""){
                //     //$("#error_expDate").text("Please select expiration date");
                //     Swal.fire({
                //         title: 'Warning',
                //         text: "Please select expiration date",
                //         icon: 'warning',
                //         showCancelButton: false,
                //         confirmButtonColor: '#3085d6',
                //         cancelButtonColor: '#d33',
                //         confirmButtonText: 'OK'
                //     });
                //     return false;
                // }
                var product_condition = "New";
                var title = $("#productTitle").val();
                var fnsku = $("#productFNsku").val();
                var redirectUrl = "{{ url('fba-shipment/generate_prep_label_html') }}";
                var item_max_expiry_date = $("#item_max_expiry_date").val();
                $.ajax({
                    url: "{{ url('fba-shipment/generate-product-labels') }}",
                    type: "POST",
                    data: {
                        itemId: itemId,
                        number_of_label: number_of_label,
                        expire_date: expire_date,
                        product_condition: product_condition,
                        title: title,
                        fnsku: fnsku,
                        shipmentId: shipmentId,
                        item_max_expiry_date: item_max_expiry_date
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function(data) {
                        hide_loader();
                        isItemLabelPrint = false;

                        // var w = window.open('about:blank');
                        // w.document.open();
                        // w.document.write(data.table_view);
                        // w.document.close();

                        // var blob = new Blob([data], {type: 'application/pdf'});
                        // var downloadUrl = URL.createObjectURL(blob);
                        // const a = document.createElement("a");
                        // a.href = downloadUrl;
                        // a.download = "file.pdf";
                        // document.body.appendChild(a);
                        // a.click();
                        // var a = window.open(redirectUrl, '_blank');
                        // WinPrint.document.write(data);
                        // WinPrint.document.close();
                        // WinPrint.focus();
                        // a.print();
                        // WinPrint.close();
                        if (data.type == 'success') {
                            displaySuccessMessage(data.message);
                            var a = window.open(redirectUrl, '_blank');
                            a.print();
                            var isValid = $("#is_product_validated").val();
                            if (isValid == "0") {
                                updateProductValidate(itemId, shipmentId);
                            }

                        } else {
                            displayErrorMessage(data.message);
                        }
                    },
                    error: function(xhr, err) {
                        isItemLabelPrint = false;
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }
        });

        function updateProductValidate(itemId, shipmentId) {
            $.ajax({
                url: "{{ url('fba-shipment/get-sku-validate') }}",
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                data: {
                    fba_shipment_item_id: itemId,
                    fba_shipment_id: shipmentId
                },
                success: function(data) {
                    if (data.type == 'success') {
                        displaySuccessMessage(data.message);
                    } else {
                        displayErrorMessage(data.message);
                    }
                }
            });
        }

        function getErrorNo(id) {
            $("#error_" + id).html("");
        }

        $(document).ready(function() {
            $("#boxConfigerationFieldset").validate();
        });

        function defaultExpirationDate() {
            var checkBox = document.getElementById("myCheck");

            const newDate = new Date();
            newDate.setFullYear(newDate.getFullYear() + 4);

            var yyyy = newDate.getFullYear().toString();
            var mm = (newDate.getMonth() + 1).toString();
            var dd = newDate.getDate().toString();

            var mmChars = mm.split('');
            var ddChars = dd.split('');
            var newClosingDate = yyyy + '/' + (mmChars[1] ? mm : "0" + mmChars[0]) + '/' + (ddChars[1] ? dd : "0" + ddChars[
                0]);

            var ndate = newClosingDate.split('/');
            var nwData = ndate[1] + '/' + ndate[2] + '/' + ndate[0];

            if (checkBox.checked == true) {
                $(".exp_box_date").val(nwData);
            } else {
                $(".exp_box_date").val("");
            }
        }

        //generate box Label
        function getGenerateBoxLabel(label_type, elem) {
            $(elem).prop('disabled', true);
            var itemId = $("#actItmId").val();

            if (itemId != '') {

                var asin_weight = $("#asin_weight").val();
                if (asin_weight == "") {
                    //$("#error_asin_weight").text("Please enter asin weight.");
                    Swal.fire({
                        title: "Please Enter ASIN Weight",
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                    $(elem).prop('disabled', false);
                    return false;
                }

                var counter = $("#add_more_index").val();
                var remaining_qty = $("#remaining_qty").val();
                var totQty = 0;
                for (index = 0; index <= counter; index++) {

                    //first...
                    var per_box_item_count = $("#per_box_item_count_" + index).val();
                    var box_suggestion_qty = $("#box_suggestion_qty").val();

                    if (per_box_item_count == "" && parseInt(per_box_item_count) == 0) {

                        Swal.fire({

                            title: "Please enter units per box greater 0",
                            icon: 'error',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonColor: '#009ef7',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'OK',
                            dangerMode: true,
                        });
                        $(elem).prop('disabled', false);
                        return false;

                    }

                    if (per_box_item_count != "" && parseInt(box_suggestion_qty) < parseInt(per_box_item_count)) {
                        Swal.fire({

                            title: "Units per box can't be greater then maximum box Qty",
                            icon: 'error',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonColor: '#009ef7',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'OK',
                            dangerMode: true,
                        });
                        $(elem).prop('disabled', false);
                        return false;
                    }

                    //second...
                    var no_of_boxes_count = $("#no_of_boxes_count_" + index).val();
                    var tot_qty = parseInt($("#tot_qty_" + index).val());
                    totQty += tot_qty;
                    // alert(no_of_boxes_count);
                    if (no_of_boxes_count == "" && parseInt(no_of_boxes_count) == 0) {

                        Swal.fire({

                            title: "Please enter number of boxes greater then 0",
                            icon: 'error',
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonColor: '#009ef7',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'OK',
                            dangerMode: true,
                        });
                        $(elem).prop('disabled', false);
                        return false;
                    }

                    //third...
                    var expiry_box_date = $("#exp_date_" + index).val();
                    // if (expiry_box_date == "") {
                    //     //$("#error_exp_date_"+index).text("Please select expiry date.");
                    //     Swal.fire({

                    //         title: "Please select expiration date",
                    //         icon: 'error',
                    //         showCloseButton: true,
                    //         showCancelButton: false,
                    //         confirmButtonColor: '#009ef7',
                    //         cancelButtonColor: '#d33',
                    //         confirmButtonText: 'OK',
                    //         dangerMode: true,
                    //     });
                    //     $(elem).prop('disabled', false);
                    //     return false;
                    // }
                }

                var decimal_counter = $("#add_more_index_extra").val();
                if (decimal_counter > 0 && decimal_counter == "6") {
                    for (index = 6; index <= decimal_counter; index++) {

                        //first...
                        var per_box_item_count = $("#per_box_item_count_" + index).val();
                        var box_suggestion_qty = $("#box_suggestion_qty").val();

                        if (per_box_item_count == "" && parseInt(per_box_item_count) == 0) {

                            Swal.fire({

                                title: "Please enter units per box greater 0",
                                icon: 'error',
                                showCloseButton: true,
                                showCancelButton: false,
                                confirmButtonColor: '#009ef7',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'OK',
                                dangerMode: true,
                            });
                            $(elem).prop('disabled', false);
                            return false;

                        }

                        if (per_box_item_count != "" && parseInt(box_suggestion_qty) < parseInt(per_box_item_count)) {
                            Swal.fire({

                                title: "Units per box can't be greater then maximum box Qty",
                                icon: 'error',
                                showCloseButton: true,
                                showCancelButton: false,
                                confirmButtonColor: '#009ef7',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'OK',
                                dangerMode: true,
                            });
                            $(elem).prop('disabled', false);
                            return false;
                        }

                        //second...
                        var no_of_boxes_count = $("#no_of_boxes_count_" + index).val();
                        var tot_qty = parseInt($("#tot_qty_" + index).val());
                        totQty += tot_qty;
                        if (no_of_boxes_count == "" && parseInt(no_of_boxes_count) == 0) {

                            Swal.fire({

                                title: "Please enter number of boxes greater then 0",
                                icon: 'error',
                                showCloseButton: true,
                                showCancelButton: false,
                                confirmButtonColor: '#009ef7',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'OK',
                                dangerMode: true,
                            });
                            $(elem).prop('disabled', false);
                            return false;
                        }

                        //third...
                        var expiry_box_date = $("#exp_date_" + index).val();
                        if (expiry_box_date == "") {
                            //$("#error_exp_date_"+index).text("Please select expiry date.");
                            Swal.fire({

                                title: "Please select expiration date",
                                icon: 'error',
                                showCloseButton: true,
                                showCancelButton: false,
                                confirmButtonColor: '#009ef7',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'OK',
                                dangerMode: true,
                            });
                            $(elem).prop('disabled', false);
                            return false;
                        }
                    }
                }

                var tot_box_qty = $("#box_lbl_qty").val();
                var iNum = (parseInt(tot_box_qty) * 5 / 100);
                var overPackQty = 0;
                if (Math.round(iNum) > 6) {
                    overPackQty = Math.round(iNum);
                } else {
                    overPackQty = 6;
                }
                var MaxtotQty = parseInt(remaining_qty) + overPackQty;

                if (parseInt(MaxtotQty) < parseInt(totQty))
                {
                    //$("#error_no_of_boxes_count_"+counter).text("You are overpacking by limit.");
                    Swal.fire({
                        title: 'You are overpacking by limit',
                        icon: 'error',
                        showCloseButton: true,
                        showCancelButton: false,
                        confirmButtonColor: '#009ef7',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'OK',
                        dangerMode: true,
                    });
                    $(elem).prop('disabled', false);
                    return false;
                }

                if ((parseInt(remaining_qty) < parseInt(totQty)) && (parseInt(totQty) <= parseInt(MaxtotQty)))
                {
                    Swal.fire({
                        title: "Are You sure?",
                        text: "You are overpacking. Please confirm.",
                        icon: "warning",
                        showCloseButton: true,
                        showCancelButton: true,
                        confirmButtonColor: '#009ef7',
                        confirmButtonText: 'Yes',
                        cancelButtonColor: '#d33',
                        cancelButtonText: "No"
                    }).then(function(result) {
                        if (result['isConfirmed']) {
                            generateBoxLbl(label_type, itemId);
                        }else{
                            $(elem).prop('disabled', false);
                        }
                    });
                }
                if (parseInt(remaining_qty) >= parseInt(totQty)) {
                    generateBoxLbl(label_type, itemId);
                }
            } else {
                $(elem).prop('disabled', false);
            }
        }

        let isBoxLabelPrint = false;

        function generateBoxLbl(label_type, itemId) {
            var formData = $("#boxConfigerationFieldset").serializeArray();
            formData.push({
                name: 'label_type',
                value: label_type
            });
            var redirectUrl = "{{ url('fba-shipment/generate_box_label_html') }}";

            if (!isBoxLabelPrint) {
                isBoxLabelPrint = true;
                $.ajax({
                    url: "{{ url('fba-shipment/generate-box-labels') }}",
                    type: "POST",
                    data: formData,
                    async: false,
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function(data) {
                        hide_loader();
                        if (data.type == 'success') {
                            displaySuccessMessage(data.message);
                            var isValid = $("#is_product_bx_validated").val();
                            if (isValid == "0") {
                                var bx_fba_shipment_id = $("#bx_fba_shipment_id").val();
                                updateProductValidate(itemId, bx_fba_shipment_id);
                            }
                            $("#is_asin_weight_upd").val("");

                            var redirect_print = window.open(redirectUrl + '?labelType=' + label_type,
                            '_blank');

                            redirect_print.print();

                            // sessionStorage.setItem("box_label_html_page", redirectUrl);

                            // window.open(data.url, '_blank');
                            window.location.reload();
                            isBoxLabelPrint = false;
                        } else {
                            isBoxLabelPrint = false;
                            displayErrorMessage(data.message);
                        }
                    },
                    error: function(xhr, err) {
                        isBoxLabelPrint = false;
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }
        }

        // $(document).ready(function() {

        //     if (sessionStorage.hasOwnProperty("box_label_html_page")) {
        //         var redirect_print = window.open(sessionStorage.getItem("box_label_html_page"), '_blank');
        //         // redirect_print.print();
        //     }
        //     sessionStorage.removeItem("box_label_html_page");
        // })


        // Search in invoice tab
        $(document).ready(function() {
            var srch_val = "<?php echo Request::get('product_info_search'); ?>";
            srch_val = window.atob(srch_val);
            if (srch_val != "") {
                $('#search_val').text(srch_val);
            }

            $("#prep_search_data").keyup(function() {

                try {
                    window.clearTimeout(timeoutID);
                } catch (e) {}
                timeoutID = window.setTimeout(run, 1000); //delay

                function run() {
                    var searchValue = $("#prep_search_data").val();
                    if (searchValue != "") {
                        var encodedValue = window.btoa(searchValue);
                        set_query_para("product_info_search", encodedValue);
                        $("#ajx_srchbar").show();
                        searchPoInvoiceItem(encodedValue);
                    } else {
                        $("#prep_search_data").val("");
                        removeURLParameter("product_info_search");
                        var searchValue = $("#prep_search_data").val();
                        searchPoInvoiceItem(searchValue);
                        $("#ajx_srchbar").hide();
                        $("#filter_by_div").hide();
                    }
                }
            });

            $('body').on('click', '#prep_search_button', function(event) {
                var searchValue = $("#prep_search_data").val();

                if (searchValue != "") {
                    var encodedValue = window.btoa(searchValue);
                    set_query_para("product_info_search", encodedValue);
                    $("#ajx_srchbar").show();
                    searchPoInvoiceItem(encodedValue);
                    //dataFilterBy();
                }
            });

            $("#prep_search_data").keyup(function(event) {
                var keycode = event.keyCode ? event.keyCode : event.which;
                if (keycode == "13") {
                    var searchValue = $(this).val();
                    if (searchValue != "") {
                        var encodedValue = window.btoa(searchValue);
                        set_query_para("product_info_search", encodedValue);
                        $("#ajx_srchbar").show();
                        searchPoInvoiceItem(encodedValue);
                        // dataFilterBy();
                    }
                }
            });

            $('body').on('click', '.invoice_clear_search', function() {
                //$(".invoice_clear_search").on("click", function () { 
                $("#prep_search_data").val("");
                removeURLParameter("product_info_search");
                var searchValue = $("#prep_search_data").val();
                searchPoInvoiceItem(searchValue);
                $("#ajx_srchbar").hide();
                $("#filter_by_div").hide();
                //});
            });

            function searchPoInvoiceItem(seachData) {
                var actionUrl = document.location.href;
                var actualSearch = window.atob(seachData);

                $.ajax({
                    url: actionUrl,
                    type: "GET",
                    data: {
                        product_info_data: seachData
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        // show_loader();
                    },
                    success: function(data) {
                        //hide_loader();
                        //window.location.reload();
                        $("#prep_search_data").val(actualSearch);
                        if (data.html != "") {
                            var parsedJson = $.parseJSON(data.html);
                            if (parsedJson != "") {
                                $("#load_more_ajx").html(parsedJson);
                            } else {
                                $("#load_more_ajx").html(
                                    '<td></td><td></td><td></td><td></td><td class="text-nowrap w-100px pr-custom" style="font-weight:600;font-size: 15px;">No records found</td><td></td><td></td><td></td><td></td><td><td></td></td>'
                                    );
                            }

                            if (actualSearch != "") {
                                $("#filter_by_div").css("display", "none");
                                $("#ajx_srchbar").html(
                                    '<div class="alert alert-warning align-items-center py-3 px-4 mt-3 mb-0" id="filter_by_div" style="display: flex;"><span class="svg-icon svg-icon-2hx svg-icon-warning me-4"><i class="fa-duotone fa-filter-list fs-3 text-primary" aria-hidden="true"></i></span><div class="d-flex flex-column"><span id="test">Filter by<span id="search_span_ajx" style=""><span class="fw-700 me-1"> Search:</span><span id="search_val_ajx">' +
                                    actualSearch +
                                    '</span></span><span class="mx-2 partition-span" style=""></span><span class="mx-2 partition-span" style="display: none;">|</span><a href="javascript:" class="invoice_clear_search">Reset </a>it.</span></div></div>'
                                    );
                            } else {
                                $("#ajx_srchbar").hide();
                            }
                        }

                    },
                    error: function(xhr, err) {
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }
        });



        // For loading more data on page scroll
        $(document).ready(function() {
            var page = 1;
            $(window).scroll(function() {
                if ((Math.ceil($(window).scrollTop()) + $(window).height() + 1) >= $(document).height()) {
                    page++;

                    var urlParams = new URLSearchParams(window.location.search);
                    if (!urlParams.has('product_info_search')) {
                        loadPrepMoreData(page);
                    }
                }
            });

            function loadPrepMoreData(page) {
                var urlParams = new URLSearchParams(window.location.search);
                var urlLog = '?page=' + page;
                $.ajax({
                        url: urlLog,
                        type: "get",
                        async: false,
                    })
                    .done(function(data) {
                        var parsedJson = $.parseJSON(data.html);
                        if (parsedJson != "") {
                            $("#load_more_ajx").append(parsedJson);
                        }
                    });
            }
        });

        function getPerBoxItemCount(asin_weight = null) {
            var itemId = $("#actItmId").val();
            var shipmentId = "{{ $shipment->shipment_id }}";
            var box_asin = $("#box_asin").val();

            if (itemId != "" && shipmentId != "") {
                var asin_weight_count;
                if (asin_weight != "" && asin_weight != undefined) {
                    asin_weight_count = asin_weight;
                } else {
                    asin_weight_count = $("#asin_weight").val();
                }

                if (asin_weight_count != "") {

                    $.ajax({
                        url: "{{ url('fba-shipment/get-per-box-item-count') }}",
                        type: "POST",
                        data: {
                            itemId: itemId,
                            asin_weight: asin_weight_count,
                            shipmentId: shipmentId,
                            asin: box_asin
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
                                //displaySuccessMessage(data.message);
                                $("#box_suggestion").html("Maximum Box Qty: " + data.itemCount);
                                $("#box_suggestion_qty").val(data.itemCount);
                                $("#maximumBoxQty").text(data.itemCount);

                                let unitPerBoxesQty = $("#unitPerBoxesQty").text();
                                if (typeof(unitPerBoxesQty) != "undefined" && unitPerBoxesQty !== null &&
                                    unitPerBoxesQty !== '') {
                                    var maximumBoxQty = data.itemCount;
                                    if (typeof(maximumBoxQty) != "undefined" && maximumBoxQty !== null &&
                                        maximumBoxQty !== "") {

                                        //check the condition given..
                                        if (parseInt(unitPerBoxesQty) > parseInt(maximumBoxQty)) {
                                            $("#per_box_item_count_0").val("");
                                            $("#no_of_boxes_count_0").val("");
                                            $("#tot_qty_0").val("");

                                            $("#myhtm").html("");
                                            // $("#totQanHtm").hide();
                                            // $("#extraBoxWarning").hide();

                                            $("#unitPerBoxesQty_second").text("");

                                            $("#newinput").html("");
                                            $("#extraBoxWarning").html("");
                                            $("#add_more_index_extra").val("0");
                                            //$("#first").val(0);
                                            $("#second").val(0);
                                            $("#third").val(0);
                                            $("#fourth").val(0);
                                            $("#fifth").val(0);
                                            $("#sixt").val(0);
                                            $("#totQanHtm").html("");
                                        } else {
                                            let casesInHandQty = $("#casesInHandQty").text();
                                            $("#per_box_item_count_0").val(parseInt(unitPerBoxesQty));
                                            $("#no_of_boxes_count_0").val(parseInt(casesInHandQty));
                                            let totQty = parseInt(unitPerBoxesQty) * parseInt(casesInHandQty)
                                            $("#tot_qty_0").val(totQty);

                                            var per_box_item_count_6 = $("#sixt").val();
                                            let htm = getHtml(per_box_item_count_6);
                                            if (per_box_item_count_6 !== null && per_box_item_count_6 > 0) {
                                                let htm = getHtml(per_box_item_count_6);
                                                $("#myhtm").html(htm);
                                            }
                                            $("#extraBoxWarning").show();
                                            $("#totQanHtm").show();
                                        }
                                    }
                                }
                            } else {
                                displayErrorMessage(data.message);
                            }
                        },
                        error: function(xhr, err) {
                            hide_loader();
                            if (
                                typeof xhr.responseJSON.message != "undefined" &&
                                xhr.responseJSON.message.length > 0
                            ) {
                                if (typeof xhr.responseJSON.errors != "undefined") {
                                    commonFormErrorShow(xhr, err);
                                } else {
                                    displayErrorMessage(xhr.responseJSON.message);
                                }
                            } else {
                                displayErrorMessage(xhr.responseJSON.errors);
                            }
                        },
                    });
                }
            }
        }

        function getCalculateTotal(incr) {
            var no_of_boxes_count = $("#no_of_boxes_count_" + incr).val();
            var per_box_item_count = $("#per_box_item_count_" + incr).val();

            if (typeof(no_of_boxes_count) != "undefined" && no_of_boxes_count !== null && typeof(per_box_item_count) !=
                "undefined" && per_box_item_count !== null) {
                $("#tot_qty_" + incr).val(no_of_boxes_count * per_box_item_count);
                // $("#2dbox").removeAttr('disabled');
            } else {
                $("#tot_qty_" + incr).val("");
                // $("#2dbox").attr('disabled', true);
            }
        }

        function getGrandTotal(incr) {

            var no_of_boxes_count = $("#no_of_boxes_count_" + incr).val();
            var per_box_item_count = $("#per_box_item_count_" + incr).val();

            var grandTotal = 0;
            if (no_of_boxes_count != "" && per_box_item_count != "") {
                gTot = parseInt(no_of_boxes_count * per_box_item_count);
                if (incr == "0") {
                    $("#first").val(gTot);
                }
                if (incr == "1") {
                    $("#second").val(gTot);
                }
                if (incr == "2") {
                    $("#third").val(gTot);
                }
                if (incr == "3") {
                    $("#fourth").val(gTot);
                }
                if (incr == "4") {
                    $("#fifth").val(gTot);
                }
                if (incr == "6") {
                    $("#sixt").val(gTot);
                }

                if ($("#sixt").val() > 0) {
                    grandTotal = parseInt($("#first").val()) + parseInt($("#second").val()) + parseInt($("#third").val()) +
                        parseInt($("#fourth").val()) + parseInt($("#fifth").val()) + parseInt($("#sixt").val());
                } else {
                    grandTotal = parseInt($("#first").val()) + parseInt($("#second").val()) + parseInt($("#third").val()) +
                        parseInt($("#fourth").val()) + parseInt($("#fifth").val());
                }

                $("#totQanHtm").html('<b class="mb-0">Total: </b><span id="totQanty" style="font-size:15px;">' +
                    grandTotal + '</span>');
                $("#2dbox").removeAttr('disabled');
            } else {
                $("#tot_qty_" + incr).val("");
                $("#2dbox").attr('disabled', true);
            }
        }

        function getUpdateNotes(itemId, type, asin) {
            if (itemId != "" && type != "") {
                var notes;
                if (type == 'Discrepancy') {
                    notes = $("#discrepancy_note_" + itemId).val();
                }
                if (type == 'Prep') {
                    notes = $("#prep_note_" + itemId).val();
                }
                if (type == 'Warehouse') {
                    notes = $("#warehouse_notes_" + itemId).val();
                }
                var shipmentId = "{{ $shipment->shipment_id }}";

                //if(notes != ""){
                var maxchars = 255;
                remain = maxchars - parseInt(notes.length);

                if (parseInt(notes.length) >= maxchars) {
                    Swal.fire({
                        title: type + " note must not exceed 255 characters",
                        icon: 'error',
                        //text: "Maximum characters limit are - 255",
                        showCloseButton: true,
                        confirmButtonText: "OK",
                        confirmButtonColor: "#009ef7",
                        dangerMode: true,
                    });

                    return false;
                }

                $.ajax({
                    url: "{{ url('fba-shipment/update-prep-notes') }}",
                    type: "POST",
                    data: {
                        itemId: itemId,
                        notes: notes,
                        type: type,
                        asin: asin,
                        shipmentId: shipmentId
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function(data) {
                        hide_loader();
                        if (data.type == 'success') {
                            displaySuccessMessage(type + " " + data.message);
                        } else {
                            displayErrorMessage(data.message);
                        }
                    },
                    error: function(xhr, err) {
                        hide_loader();
                        if (
                            typeof xhr.responseJSON.message != "undefined" &&
                            xhr.responseJSON.message.length > 0
                        ) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
                //}
            }
        }

        function getPrintSingleBoxItemLabel(boxRowId, type) {
            if (boxRowId != "") {
                
                var shipmentName = "{{ $shipment->shipment_name }}";

                

                var formData = $("#boxSingleFieldSetDrw").serializeArray();
                formData.push({
                    name: 'label_type',
                    value: ''
                });
                formData.push({
                    name: 'boxRowId',
                    value: boxRowId
                });
                formData.push({
                    name: 'shipment_name',
                    value: shipmentName
                });

                let generateLabelRoute = "{{ url('fba-shipment/generate-single-box-labels') }}";
                let redirectUrl = "{{ url('fba-shipment/generate_box_label_html') }}";
                if(type == true)
                {
                    generateLabelRoute = "{{ route('fba_prep.generate_multisku_box_label') }}";
                    redirectUrl = "{{ route('generate-multi-skus-box') }}";
                }

                $.ajax({
                    url: generateLabelRoute,
                    type: "POST",
                    data: formData,
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        // show_loader();
                    },
                    success: function(data) {
                        hide_loader();
                        if (data.type == 'success') {
                            displaySuccessMessage(data.message);
                            // window.open(data.url, '_blank')
                            var redirectPrint = window.open(redirectUrl, '_blank');
                            redirectPrint.print();
                        } else {
                            displayErrorMessage(data.message);
                        }
                    }
                });
            }
        }

        function getPrintAllBoxLabel() {
            var shipment_id = "{{ $shipment->shipment_id }}";
            var itemId = $("#drw_fba_shipment_item_id").val();
            // var redirectUrl = "{{ url('fba-shipment/generate_box_label_html') }}";
            

            if (shipment_id != "" && itemId != "") {
                $.ajax({
                    url: "{{ url('fba-shipment/generate-all-box-labels') }}",
                    type: "POST",
                    data: {
                        shipment_id: shipment_id,
                        itemId: itemId
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        // show_loader();
                    },
                    success: function(data) {
                        hide_loader();
                        if (data.type == 'success') {
                            displaySuccessMessage(data.message);
                            // window.open(data.url, '_blank')
                            const redirectUrl = "{{ route('fba_prep.generate_box_label') }}";
                            var redirect_print = window.open(redirectUrl, '_blank');
                            redirect_print.print();
                        } else {
                            displayErrorMessage(data.message);
                        }
                    }
                });
            }
        }

        function getDeleteSingleBox(boxRowId, units) {
            if (boxRowId != "" && units != "") {
                var shipmentId = "{{ $shipment->shipment_id }}";
                Swal.fire({
                    title: "<b>Are You sure ?</b>",
                    text: "You will not be able to recover this box Id",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#009ef7',
                    confirmButtonText: 'Yes',
                    cancelButtonColor: '#d33',
                    cancelButtonText: "No"
                }).then(function(result) {
                    if (result['isConfirmed']) {
                        $.ajax({
                            url: "{{ url('fba-shipment/delete-single-box') }}",
                            type: "POST",
                            data: {
                                boxRowId: boxRowId,
                                shipmentId: shipmentId,
                                units: units
                            },
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            },
                            beforeSend: function() {
                                show_loader();
                            },
                            success: function(data) {
                                hide_loader();
                                if (data.type == 'success') {

                                    $("#boxprepid_" + boxRowId).hide();
                                    $("#vboxprepid_" + boxRowId).hide();
                                    displaySuccessMessage(data.message);

                                    var totalQt = trim($("#totalQt_" + data.itemId).text());
                                    var doneQt = trim($("#doneQt_" + data.itemId).text());
                                    var disQt = trim($("#disQt_" + data.itemId).text());

                                    if (doneQt > 0) {
                                        var afterDoneQt = parseInt(doneQt) - parseInt(units);
                                        $("#doneQt_" + data.itemId).text(afterDoneQt);
                                    }

                                    if (disQt.indexOf('+') > -1) {
                                        var afterDisQt = parseInt(disQt) - parseInt(units);
                                        if (parseInt(totalQt) < parseInt(afterDoneQt)) {
                                            $("#disQt_" + data.itemId).text('+' + afterDisQt);
                                        } else if (parseInt(totalQt) == parseInt(afterDoneQt)) {
                                            $("#disQt_" + data.itemId).text(afterDisQt);
                                        } else {
                                            $("#disQt_" + data.itemId).text('-' + afterDisQt);
                                        }
                                    } else {
                                        var afterDisQt = parseInt(disQt) - parseInt(units);
                                        $("#disQt_" + data.itemId).text(afterDisQt);
                                    }

                                    var newDisQt = trim($("#disQt_" + data.itemId).text());

                                    //if find discripency is greater then 0 then changed color yellow
                                    if (parseInt(afterDoneQt) > 0) {
                                        if (newDisQt.indexOf('-') > -1) {
                                            $("#row_id_" + data.itemId).css("background", "#FFF8DE");
                                        }
                                    }

                                    //if find discripency is 0 but done qty is greater then 0 changed color green
                                    if (parseInt(afterDoneQt) > 0 && parseInt(afterDoneQt) == parseInt(
                                            totalQt) && parseInt(afterDisQt) <= 0) {
                                        $("#row_id_" + data.itemId).css("background", "#D3F4CE");
                                    }

                                    //if find discripency less then 0 but done qty is greater then 0 changed color light blue
                                    if (parseInt(afterDoneQt) > parseInt(totalQt) && parseInt(
                                            afterDisQt) < 0) {
                                        $("#row_id_" + data.itemId).css("background", "#CCEBFD");
                                    }

                                    //if find discripency is 0 but done qty is greater then 0
                                    // if(afterDoneQt > 0 && afterDisQt == 0){
                                    //     $("#row_id_"+data.itemId).css("background","#D3F4CE");
                                    // }

                                    //if find discripency equs to total qty then changed color red...
                                    if (newDisQt.indexOf('-') > -1) {
                                        var newAfterDisQt = Math.abs(parseInt(newDisQt));
                                    } else {
                                        var newAfterDisQt = parseInt(afterDisQt);
                                    }

                                    if (parseInt(afterDoneQt) == 0 && parseInt(totalQt) == parseInt(
                                            newAfterDisQt)) {
                                        $("#row_id_" + data.itemId).css("background", "#FFE8E8");
                                    }

                                    window.location.reload();
                                } else {
                                    displayErrorMessage(data.message);
                                }
                            }
                        });
                    }
                });
            }
        }

        //3 IN 1 label menu display or not...
        $(document).ready(function() {
            // Show hide popover
            $("body").on("click", ".dropdown", function() {
                var itemId = $(this).attr('data-id');
                var urls = $(this).attr('data-urls');
                var shipmentId = "{{ $shipment->shipment_id }}";
                var dataSku = $(this).attr('data-sku');

                $.ajax({
                    url: "{{ url('fba-shipment/get-single-item-label-data') }}",
                    type: "POST",
                    data: {
                        itemId: itemId,
                        shipmentId: shipmentId
                    },
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    beforeSend: function() {
                        //show_loader();
                    },
                    success: function(data) {

                        if (data.type == 'success') {
                            if (typeof(data.shipmentItem.qty) != "undefined" && data
                                .shipmentItem.qty !== null) {
                                var remainQty = data.shipmentItem.qty - data.shipmentItem
                                    .done_qty;

                                var a_pack = '';
                                if (typeof(data.shipmentItem.pack_of) != "undefined" && data
                                    .shipmentItem.a_pack != null) {
                                    a_pack = data.shipmentItem.pack_of;
                                } else if (typeof(data.shipmentItem.amazon_data) !=
                                    "undefined" && data.shipmentItem.amazon_data != null) {
                                    a_pack = data.shipmentItem.amazon_data
                                    .amazon_product_a_pack;
                                }

                                var case_pack = '';
                                if (typeof(data.shipmentItem.case_pack) != "undefined" && data
                                    .shipmentItem.case_pack != null) {
                                    case_pack = data.shipmentItem.case_pack;
                                } else if (typeof(data.shipmentItem.amazon_data) !=
                                    "undefined" && data.shipmentItem.amazon_data != null) {
                                    case_pack = data.shipmentItem.amazon_data
                                        .amazon_product_case_pack;
                                }

                                if (remainQty > 0 && data.shipment.shipment_status != 6 && data
                                    .shipment.prep_status != 2) {
                                    if (dataSku != '') {
                                        $("#drawp_" + itemId).html(
                                            '<li class="border-bottom"><a class="dropdown-item py-3" href="#" id="single_view_button" data-id="' +
                                            itemId +
                                            '"><i class="text-dark me-2 fa-solid fa-box"></i> Box Label Listing</a></li><li id="row3in1"><a class="dropdown-item py-3" href="#" id="printBoxLabelsModal" data-id="' +
                                            itemId + '" data-text="3D" data-case_pack="' +
                                            case_pack + '" data-a_pack="' + a_pack +
                                            '"><i class="text-dark me-2 fa-solid fa-print "></i>Printing 3 in 1 Box Labels</a></li>'
                                            );

                                    } else {
                                        $("#drawp_" + itemId).html(
                                            '<li class="border-bottom"><a class="dropdown-item py-3" href="#" id="single_view_button" data-id="' +
                                            itemId +
                                            '"><i class="text-dark me-2 fa-solid fa-box"></i> Box Label Listing</a></li><li id="row3in1"><a class="dropdown-item py-3" href="#" id="printBoxLabelsModal" data-id="' +
                                            itemId + '" data-text="3D" data-case_pack="' +
                                            case_pack + '" data-a_pack="' + a_pack +
                                            '"><i class="text-dark me-2 fa-solid fa-print "></i>Printing 3 in 1 Box Labels</a></li>'
                                            );

                                    }
                                } else {
                                    if (dataSku != '') {
                                        $("#drawp_" + itemId).html(
                                            '<li class="border-bottom"><a class="dropdown-item py-3" href="#" id="single_view_button" data-id="' +
                                            itemId +
                                            '"><i class="text-dark me-2 fa-solid fa-box"></i> Box Label Listing</a></li>'
                                            );
                                    } else {
                                        $("#drawp_" + itemId).html(
                                            '<li class="border-bottom"><a class="dropdown-item py-3" href="#" id="single_view_button" data-id="' +
                                            itemId +
                                            '"><i class="text-dark me-2 fa-solid fa-box"></i> Box Label Listing</a></li>'
                                            );

                                    }
                                }
                            }
                        }
                    }
                });
            });
        });

        $("#box_no_search_data").keypress(function(event) {
            var keycode = event.keyCode ? event.keyCode : event.which;
            if (keycode == "13") {
                var searchValue = $(this).val();
                // if (searchValue != "") {
                    // e.preventDefault();
                    getSearchBoxNumber(searchValue, 'single');
                    return false;
                // }
            }
        });

        $("#view_all_box_no_search_data").keypress(function(event) {
            var keycode = event.keyCode ? event.keyCode : event.which;
            if (keycode == "13") {
                var searchValue = $(this).val();
                // if (searchValue != "") {
                    //e.preventDefault();
                    getSearchBoxNumber(searchValue, 'viewAll');
                    return false;
                // }
            }
        });

        function getSearchBox(type) {
            if (type == "viewAll") {
                var searchValue = $("#view_all_box_no_search_data").val();
                getSearchBoxNumber(searchValue, type);
            }

            if (type == "single") {
                var searchValue = $("#box_no_search_data").val();
                getSearchBoxNumber(searchValue, type);
            }
        }

        function getSearchBoxNumber(boxNumber, typeSrch) {
            var shipmentId = "{{ $shipment->shipment_id }}";

            if (typeSrch == 'single') {
                var itemId = $("#drw_fba_shipment_item_id").val();
            } else {
                var itemId = '';
            }

            if (shipmentId != "" && boxNumber != "") {
                $.ajax({
                    url: "{{ url('fba-shipment/search-box-number') }}",
                    type: "POST",
                    data: {
                        shipmentId: shipmentId,
                        itemId: itemId,
                        boxNumber: boxNumber,
                        typeSrch: typeSrch
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
                            //displaySuccessMessage(data.message);

                            //if(typeof(data.boxData.type) != "undefined" && data.boxData !== null) {
                            var k = 1;
                            var html = '';
                            if (data.boxData.main_image != "") {
                                mainImg = data.boxData.main_image;
                            } else {
                                mainImg = imgUrl;
                            }

                            let styleForBox = '';
                            let isMultiSku = false;
                            if(data.boxData.box_type == 1)
                            {
                                styleForBox = 'background-color: #cbcbcb';
                                isMultiSku = true;
                            }

                            var nDate = '-';
                            
                            if (data.boxData.expiry_date != "" && data.boxData.expiry_date != '0000-00-00') {
                                var dateArr = data.boxData.expiry_date.split('-');
                                nDate = dateArr[1] + '/' + dateArr[2] + '/' + dateArr[0];
                            }

                            var prepType = "{{ $prepType }}";
                            var deleteBxoHtml = '';
                            if (prepType == 'EditPrep') {
                                deleteBxoHtml = '<a href="javascript:;" onclick="getDeleteSingleBox(' + data
                                    .boxData.id + ',' + data.boxData.units +
                                    ');"><i class="fa-solid fa-trash-alt fa-lg text-danger"></i></a>';
                            }

                            html += '<tr class="border-top border-gray-300" id="boxprepid_' + data.boxData.id +
                                '" style="'+styleForBox+'"><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_lbl_box_number_' +
                                k + '">' + data.boxData.box_number +
                                '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_img_' +
                                k + '"><img src="' + mainImg +
                                '" class="w-100 p-3 border border-gray-300 rounded" alt=""></span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_units_' +
                                k + '">' + data.boxData.units +
                                '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><a href="javascript:" class="text-dark text-truncate" onclick="copySkuToClipboardButton(\'' +
                                data.boxData.sku + '\');" data-bs-toggle="tooltip" title="' + data.boxData.sku +
                                '" id="drw_lbl_pro_sku_' + k + '">' + data.boxData.sku +
                                '</a></div></td><td class="text-nowrap pr-custom text-center"><div class="h-100 d-flex align-items-center justify-content-center"><span id="drw_list_units_' +
                                k + '">' + nDate +
                                '</span></div></td><td class="text-nowrap pr-custom text-center"><div class="d-flex align-items-center justify-content-evenly h-100"><a href="javascript:;" onclick="getPrintSingleBoxItemLabel(' +
                                data.boxData.id +
                                ','+isMultiSku+');"><i class="fa-solid fa-print fa-lg text-success"></i></a>' + deleteBxoHtml +
                                '</div></td></tr>';

                            if (typeSrch == 'single') {
                                $("#mhtml_" + itemId).hide();
                                $("#srchHtml").show();
                                $("#srchHtml").html(html);
                            }

                            if (typeSrch == 'viewAll') {
                                $("#vhtml").hide();
                                $("#srchHtmlAll").show();
                                $("#srchHtmlAll").html(html);
                            }

                            //}
                        } else {
                            //displayErrorMessage(data.message);

                            if (typeSrch == 'single') {
                                $("#mhtml_" + itemId).hide();
                                $("#srchHtml").show();
                                $("#srchHtml").html(
                                    "<td></td><td></td><td></td><td><h5 style='text-align:center;font-weight:600;'>No Box Information Available.</h5></td><td></td><td></td><td></td>"
                                    );
                            }

                            if (typeSrch == 'viewAll') {
                                $("#vhtml").hide();
                                $("#srchHtmlAll").show();
                                $("#srchHtmlAll").html(
                                    "<td></td><td></td><td></td><td><h5 style='text-align:center;font-weight:600;'>No Box Information Available.</h5></td><td></td><td></td><td></td>"
                                    );
                            }
                        }
                    }
                });
            } else {
                if (typeSrch == 'single') {
                    $("#srchHtml").hide();
                    $("#mhtml_" + itemId).show();
                }
                if (typeSrch == 'viewAll') {
                    $("#srchHtmlAll").hide();
                    $("#vhtml").show();
                }
            }
        }

        //Copy SKU to clipboard
        function copySkuToClipboardButton(copyText) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("SKU copied");
        }

        // copy asin to clipbord
        function copyAsinToClipboardButton(elem) {
            var copyText = $(elem).prev('.asin-link').text();
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("ASIN copied");
        }

        function copyAsinButton(copyText) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("ASIN copied");
        }

        // skus to clipbord
        function copySKUButton(elem) {
            var copyText = $(elem).prev('.sku-link').text();
            var aux = document.createElement("span");
            aux.setAttribute("contentEditable", true);
            aux.innerHTML = copyText;
            aux.setAttribute("onfocus", "document.execCommand('selectAll',false,null)");
            $("#item_lbl_sku").html(aux);
            aux.focus();
            document.execCommand("copy");
            $("#item_lbl_sku").html("");
            $("#item_lbl_sku").html(aux);
            displaySuccessMessage("SKU copied");
            return false;
        }

        // asin modal to clipbord
        function copyAsinButtonModal(copyText) {
            var aux = document.createElement("span");
            aux.setAttribute("contentEditable", true);
            aux.innerHTML = copyText;
            aux.setAttribute("onfocus", "document.execCommand('selectAll',false,null)");
            $("#complt_lbl_asin").html(aux);
            aux.focus();
            document.execCommand("copy");
            $("#complt_lbl_asin").html("");
            $("#complt_lbl_asin").html(aux);
            displaySuccessMessage("ASIN copied");
            return false;
        }

        // sku modal to clipbord
        function copySKUButtonModal(copyText) {
            var aux = document.createElement("span");
            aux.setAttribute("contentEditable", true);
            aux.innerHTML = copyText;
            aux.setAttribute("onfocus", "document.execCommand('selectAll',false,null)");
            $("#complt_lbl_sku").html(aux);
            aux.focus();
            document.execCommand("copy");
            $("#complt_lbl_sku").html("");
            $("#complt_lbl_sku").html(aux);
            displaySuccessMessage("SKU copied");
            return false;
        }

        function getDeleteAllBoxes() {
            var shipmentId = "{{ $shipment->shipment_id }}";
            var itemId = $("#drw_fba_shipment_item_id").val();

            if (shipmentId != "" && itemId != "") {
                Swal.fire({
                    title: "Are you sure you want to delete all the box labels",
                    icon: 'error',
                    showCloseButton: true,
                    showCancelButton: false,
                    showDenyButton: true,
                    confirmButtonText: "Yes",
                    confirmButtonColor: "#009ef7",
                    cancelButtonText: "No",
                    cancelButtonColor: "#d33",
                    denyButtonText: `No`,
                    dangerMode: true,
                }).then(function(result) {
                    if (result['isConfirmed']) {
                        $.ajax({
                            url: "{{ url('fba-shipment/delete-all-boxes') }}",
                            type: "POST",
                            data: {
                                shipmentId: shipmentId,
                                itemId: itemId
                            },
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            },
                            beforeSend: function() {
                                show_loader();
                            },
                            success: function(data) {
                                hide_loader();
                                if (data.type == 'success') {
                                    displaySuccessMessage(data.message);
                                    $("#mhtml_" + itemId).html("");
                                    $("#bxicons").hide();
                                    $("#row_id_" + itemId).css("background", "#FFE8E8");
                                    var totalQt = $("#totalQt_" + itemId).text();
                                    $("#doneQt_" + itemId).text("0");
                                    $("#disQt_" + itemId).text('-' + totalQt);

                                } else {
                                    displayErrorMessage(data.message);
                                }
                            }
                        });
                    }
                });
            }
        }

        function getAlert() {
            var shipmentId = "{{ $shipment->shipment_id }}";
            Swal.fire({
                title: "No Box Information Available",
                icon: 'warning',
                text: "Shipment - " + shipmentId,
                showCloseButton: true,
                confirmButtonText: "OK",
                confirmButtonColor: "#009ef7",
                dangerMode: true,
            });

        }

        // Add to plan script: START

        $('#move_to_selection_1, #move_to_selection_2, #move_to_selection_1_plan, #move_to_selection_2_plan').hide();
        $('#move_delete_shipment_btn, #move_delete_plan_btn').prop('disabled', true);

        $('.selected_move_plan_type').click(function() {
            var selectedType = $(this).val();

            if (selectedType == 1) {
                $('#move_to_selection_1_plan').show();
                $('#move_to_selection_2_plan').hide();
                $('#create_new_plan_name1').val('');
                $('#move_delete_plan_btn').prop('disabled', true);
            } else {
                $('#move_to_selection_2_plan').show();
                $('#move_to_selection_1_plan').hide();
                $(".selected_draft_plan1").prop("checked", false);
                $('#move_delete_plan_btn').prop('disabled', true);
            }
        });

        $('.selected_move_type').click(function() {
            var selectedType = $(this).val();

            if (selectedType == 1) {
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

        $('.selected_draft_plan').click(function() {
            $('#move_delete_shipment_btn').prop('disabled', false);
        });

        $('.selected_draft_plan1').click(function() {
            $('#move_delete_plan_btn').prop('disabled', false);
        });

        // Commenting this code: As we are allowing to use duplicate name Task ID: AF2-T717
        $(document).on("keyup change", "#create_new_plan_name", function() {
            $('#plan_name_error').text('');
            $('#move_delete_shipment_btn').prop('disabled', true);

            if ($(this).val() != '') {
                $('#move_delete_shipment_btn').prop('disabled', false);
            } else {
                $('#move_delete_shipment_btn').prop('disabled', true);
            }
        });

        $(document).on("keyup change", "#create_new_plan_name1", function() {
            $('#plan_name_error1').text('');
            $('#move_delete_plan_btn').prop('disabled', true);

            if ($(this).val() != '') {
                $('#move_delete_plan_btn').prop('disabled', false);
            } else {
                $('#move_delete_plan_btn').prop('disabled', true);
            }
        });

        $('body').on('click', '.data-sku-link', function() {
            $('#add_to_plan_sku').text($(this).attr('data-sku'));
            $('#hidden_sku').val($(this).attr('data-sku'));
        });

        // Add to plan script: END

        // Move and delete shipment form submit
        $("form#addToPlanFormSubmit").submit(function(e) {

            e.preventDefault();
            $('#sku_qty_error').text('');

            var skuQty = $('#sku_qty').val();

            if (skuQty == '') {
                $('#sku_qty_error').text('Sku quantity field can not be empty');

                return false;
            }

            // Check if any asin is already in plan and we are trying to add it again.
            // This will give an alert confirmation message to proceed or not.
            var formData = new FormData($("#addToPlanFormSubmit")[0]);
            var submitUrl = $(this).attr("action");

            $.ajax({
                url: submitUrl,
                type: "POST",
                data: formData,
                contentType: false,
                cache: false,
                async: false,
                processData: false,
                success: function(data) {

                    if (!data.status) {
                        Swal.fire({
                            title: "<b style='color:red'>Warning</b>",
                            html: data.message,
                            showCloseButton: true,
                            showCancelButton: false,
                            customClass: 'swal-wide',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#009ef7',
                        }).then(function(res) {

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
                        }).then(function(res) {

                            location.reload();

                        });
                        return false;
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                },
            });
            return false;
        });
    </script>

    <script>
        function getOpenImageModal(amaz_product_id, asin, moduleName) {
            if (amaz_product_id != "" && asin != "" && moduleName != "") {
                $('#associate-product-img').modal('toggle');
                $(".amaz_product_id").val(amaz_product_id);
                $(".prodct_asin").val(asin);
                $(".module_name").val(moduleName);

                var amazonProductUrl = "https://www.amazon.com/dp/" + asin;
                $("#asinUpdAsinUrl").attr("href", amazonProductUrl);
                $("#asinUpdAsinUrl").text(asin);

                let asinHtml =
                    ' <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinCopyFrmModal(\'' +
                    asin +
                    '\')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a>';

                $("#copyUpdAsin").html(asinHtml);

                loadAlreadyUploadedImages(asin, amaz_product_id, moduleName);
            }
        }

        function printAllLabel()
        {
            // Swal.fire({
            //     title: "Print Item Label?",
            //     text: "Are you sure want to print all item label?",
            //     type: "warning",
            //     showCancelButton: true,
            //     confirmButtonColor: '#009ef7',
            //     confirmButtonText: 'Yes',
            //     cancelButtonColor: '#d33',
            //     cancelButtonText: "No"
            // }).then(function(result) {
            //     if (result.isConfirmed)
            //     {
                    printItemLabels();
            //     }
            // });
        }

        function printItemLabels()
        {
            $.ajax({
                url: "{{ route('print_all_label') }}",
                type: "POST",
                data: {
                    shipmentId: "{{ $shipment->shipment_id }}",
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    if (data.type == 'success')
                    {
                        displaySuccessMessage(data.message);
                        var a = window.open("{{ url('fba-shipment/generate_prep_label_html') }}", '_blank');
                        a.print();
                        // var isValid = $("#is_product_validated").val();
                        // if (isValid == "0")
                        // {
                        //     updateProductValidate(itemId, shipmentId);
                        // }

                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function() {
                    hide_loader();
                }
            });
        }
    </script>

    <script>
        var unitValidationMsg = "The units should not be 0 or greater than QTY in shipment";
        $(document).ready(function(){
            // When click on create multi skus box button
            $(document).on('click', '#create_multi_skus_box', function (e) {
                $('#product_list tbody').trigger("reset");

                // Show the modal and set focus when it's shown
                $('#multi_skus_search_modal').on('shown.bs.modal', function () {
                    $("#product_search_data").focus();
                    dateRangeOption();
                });

                // For get already addred skus
                const shipmentId = "{{ request()->segment(3) }}";
                $.ajax({
                    url: "{{ url('fba-prep/get-multi-skus') }}/"+shipmentId,
                    type:"GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function (response) {
                        hide_loader();
                        $("#multi_skus_body").html(response);
                        $('#multi_skus_search_modal').modal('show');
                        $('#multi_skus_all_modal').modal('hide');
                    },
                    error: function(err) {
                        hide_loader();
                    }
                })
            });

            // For show all product list popup
            function showAllProductModal()
            {
                $('#multi_skus_search_modal').modal('hide');
                $('#multi_skus_all_modal').modal('show');
            }

            // Search product with keyup event
            $("#product_search_data").keyup(function(e){
                if($(this).val() != '')
                {
                    setTimeout(() => {
                        searchSkus();
                    }, 1000);
                }
            })

            // Search product with enter key press
            $("#product_search_data").keypress(function(e){
                if(e.key == "Enter")
                {
                    searchSkus();
                }
            })

            // Search product on search button click
            $("#product_search_button").click(function(){
                searchSkus();
            })

            // Get searched product list
            function searchSkus(searchType = '')
            {
                if(searchType == '' && $("#product_search_data").val() == '')
                {
                    return false;
                }

                $.ajax({
                    url: "{{ route('search-skus') }}",
                    type:"POST",
                    data: {
                        searchSku: $("#product_search_data").val(),
                        shipmentId : "{{ request()->segment(3) }}",
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function (response) {
                        hide_loader();
                        if(response.length > 0)
                        {
                            if($("#product_search_data").val() == '')
                            {
                                showAllProductModal();
                                $("#multi_skus_all_products_body").html(response);
                                dateRangeOption();
                            }else{
                                $("#multi_skus_body").html(response);
                                dateRangeOption();
                            }
                        }else{
                            Swal.fire({
                                title: response.title,
                                text: response.message,
                                html:'',
                                showCloseButton: true,
                                showCancelButton: false,
                                showDenyButton: false,
                                confirmButtonText:'OK',
                                confirmButtonColor: '#009ef7',
                                cancelButtonText:'No',
                                cancelButtonColor: '#181c32',
                                denyButtonText: `No`,
                                dangerMode: true,
                            });
                        }
                    },
                    error: function(err) {
                        hide_loader();
                    }
                })
            }

            // When click on all product list popup cancel button
            $("#multi_skus_all_modal .cancel").click(function(){
                dateRangeOption();
                $('#multi_skus_search_modal').modal('show');
                $('#multi_skus_all_modal').modal('hide');
            })

            // For selected product add in database
            $("#select_multi_skus").click(function(){
                let selectedSkus = [];
                $(".product-checkbox").each(function () {
                    const isChecked = $(this).prop("checked");
                    const sellerSku = $(this).data("sku");
                    const shipmentItemId = $(this).data("shipment_item_id");

                    if (isChecked)
                    {
                        // Add the selected item to the array
                        selectedSkus.push({
                            'seller_sku': sellerSku,
                            'shipment_item_id': shipmentItemId
                        });
                    } else {
                        // Remove the item from the array if unchecked
                        const index = selectedSkus.indexOf(sellerSku);
                        if (index !== -1)
                        {
                            selectedSkus.splice(index, 1);
                        }
                    }

                });

                if(selectedSkus.length == 0)
                {
                    displayErrorMessage("Please Select Minimum One Product");
                }
                
                if(selectedSkus.length > 0)
                {
                    $.ajax({
                        url: "{{ route('add-mulit-skus') }}",
                        type:"POST",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            shipmentId: "{{ request()->segment(3) }}",
                            sellerSkus: selectedSkus
                        },
                        beforeSend: function() {
                            show_loader();
                        },
                        success: function (response) {
                            hide_loader();
                            $("#multi_skus_body").html(response);
                            $("#product_search_data").val('');
                            $('#multi_skus_search_modal').modal('show');
                            $('#multi_skus_all_modal').modal('hide');
                        },
                        error: function(err) {
                            hide_loader();
                        }
                    })
                }
            })

            // When click on add products button
            $(document).on('click','#add_products_btn', function() {
                $("#product_search_data").val('');
                searchSkus('all');
            })
        })

        // For remove product from search list
        function removeSku(skuId)
        {
            Swal.fire({
                title: "Delete Confirmation",
                text: "Are you sure you want to delete this record?",
                showCloseButton: true,
                showCancelButton: false,
                showDenyButton: true,
                confirmButtonText:'Yes',
                confirmButtonColor: '#009ef7',
                cancelButtonText:'No',
                cancelButtonColor: '#181c32',
                denyButtonText: `No`,
                dangerMode: true,
            }).then(function(result) {
                if (result.isConfirmed)
                {
                    $.ajax({
                        url: "{{ url('fba-prep/remove-sku') }}/"+skuId,
                        type:"DELETE",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            show_loader();
                        },
                        success: function (response) {
                            hide_loader();
                            $("#multi_skus_body").html(response);
                            $("#product_search_data").val('');
                            dateRangeOption();
                        },
                        error: function(err) {
                            hide_loader();
                        }
                    })
                }
            })
        }

        // For change sellable units of searched product and entered sellable units update in database
        $(document).on('keyup', '.sellable_units', function(e){
            var charCode = (e.which) ? e.which : event.keyCode;
            if ((charCode < 48 || charCode > 57) && ((charCode < 96 || charCode > 105)) && charCode != 8)
            {
                return false;
            }

            // let inputVal = $(this).val();
    
            // // Remove non-digit characters
            // inputVal = inputVal.replace(/\D/g, '');

            // // Ensure the input is no longer than 3 digits
            // if (inputVal.length > 3)
            // {
            //     inputVal = inputVal.slice(0, 3);
            //     $(this).val(inputVal);
            //     e.preventDefault();
            // }


            if($(this).val() == '' || $(this).val() == 0 || $(this).attr('id') == '')
            {
                return false;
            }

            if($(this).data('unit') < $(this).val())
            {
                $(this).parent().find('.sellable_units_error').text(unitValidationMsg);
                return false;
            }else{
                $(this).parent().find('.sellable_units_error').text('');
            }

            $.ajax({
                url: "{{ url('fba-prep/update-sku-unit') }}/"+$(this).attr('id'),
                type:"PUT",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    sellableUnits: $(this).val()
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function (response) {
                    hide_loader();
                    $("#multi_skus_body").html(response);
                    $("#product_search_data").val('');
                    $('#multi_skus_search_modal').modal('show');
                    $('#multi_skus_all_modal').modal('hide');

                    dateRangeOption();
                },
                error: function(err) {
                    hide_loader();
                }
            })
        })

        // When click on box dimension popup cancel button
        $("#box_dimension_details .cancel").click(function(){
            $('#multi_skus_search_modal').modal('show');
            $('#multi_skus_all_modal').modal('hide');
            $("#box_dimension_details").hide();
        })

        // When click on save and print label button then open box dimension popup
        $("#multi_sku_box_submit").click(function(){
            let isValid = [];
            $('.sellable_units').each(function(){
                if($(this).val() == '' || $(this).val() == 0 || $(this).attr('id') == '')
                {
                    $(this).parent().find('.sellable_units_error').text(unitValidationMsg);
                    isValid = [...isValid, true];
                    // return false;
                }
            });

            if($('.sellable_units').length < 2)
            {
                displayErrorMessage("Please Add Minimum Two Product in Multi SKU Box");
                isValid = [...isValid, true];
            }
            // const isValid = $('.sellable_units').filter(item => (item == '' || item == 0));

            // console.log(isValid.length);

            if(isValid.length == 0)
            {
                $('#multi_skus_search_modal').modal('hide');
                $('#multi_skus_all_modal').modal('hide');

                $('.box_width_error').text('');
                $('.box_height_error').text('');
                $('.box_length_error').text('');

                $('#box_dimension_details').modal('show');
            }
        })

        // For generate multi skus box label
        $("#box_dimension_detail_submit").click(function(){
            let isValid = [];
            if(($("#box_width").val() == '' || $("#box_width").val() == 0) && ($("#box_height").val() == '' || $("#box_height").val() == 0) && ($("#box_length").val() == '' || $("#box_length").val() == 0) && ($("#box_weight").val() == '' || $("#box_weight").val() == 0))
            {
                $('.box_width_error').text('The box width should not be 0 or empty');
                $('.box_height_error').text('The box height should not be 0 or empty');
                $('.box_length_error').text('The box length should not be 0 or empty');
                $('.box_weight_error').text('The box weight should not be 0 or empty');
                isValid = [...isValid, true];
            }

            if($("#box_width").val() == '' || $("#box_width").val() == 0)
            {
                $('.box_width_error').text('The box width should not be 0 or empty');
                isValid = [...isValid, true];
            }else{
                $('.box_width_error').text('');
            }

            if($("#box_height").val() == '' || $("#box_height").val() == 0)
            {
                $('.box_height_error').text('The box height should not be 0 or empty');
                isValid = [...isValid, true];
            }else{
                $('.box_height_error').text('');
            }

            if($("#box_length").val() == '' || $("#box_length").val() == 0)
            {
                $('.box_length_error').text('The box length should not be 0 or empty');
                isValid = [...isValid, true];
            }else{
                $('.box_length_error').text('');
            }

            if($("#box_weight").val() == '' || $("#box_weight").val() == 0)
            {
                $('.box_weight_error').text('The box weight should not be 0 or empty');
                isValid = [...isValid, true];
            }else if($("#box_weight").val() > 45) {
                $('.box_weight_error').text('The box weight should be less than 45 pound');
                isValid = [...isValid, true];
            }else{
                $('.box_weight_error').text('');
            }

            // if($("#box_weight").val() > 45)
            // {
            //     $('.box_weight_error').text('The box width should not be greater than 45 pound');
            //     isValid = [...isValid, true];
            // }else{
            //     $('.box_weight_error').text('');
            // }

            if(isValid.length == 0)
            {
                const shipmentItems = [];

                $(".sellable_units").each(function(){
                    shipmentItems.push({
                        fba_shipment_item_id: $(this).parent().parent().find('.fba_shipment_item_id').val(),
                        units: $(this).val(),
                        sku: $(this).parent().parent().find('.sku').find('.link').text(),
                        main_image: $(this).parent().parent().find('.main_image').children().attr('src'),
                        total_qty: $(this).parent().parent().find('.total_qty').val(),
                        fnsku: $(this).parent().parent().find('.fnsku').find('.link').text(),
                        box_width: $("#box_width").val(),
                        box_height: $("#box_height").val(),
                        box_length: $("#box_length").val(),
                        box_weight: $("#box_weight").val(),
                        expiry_date: $(this).parent().parent().find('.expiry_date').val()
                    })
                });

                $.ajax({
                    url: "{{ route('create-multi-skus-box') }}",
                    type:"POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        fba_shipment_id: "{{ request()->segment(3) }}",
                        no_of_boxes_count: 1,
                        shipmentItems: shipmentItems,
                        
                    },
                    beforeSend: function() {
                        show_loader();
                    },
                    success: function (response) {
                        hide_loader();
                        const redirectUrl = "{{ route('generate-multi-skus-box') }}";
                        let redirectPrint = window.open(redirectUrl,'_blank');
                        redirectPrint.print();
                        window.location.reload();
                    },
                    error: function(err) {
                        hide_loader();
                    }
                })
            }
        })

        // Apply date range picker for expiry date
        function dateRangeOption()
        {
            $(".expiry_date").daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                minYear: '{{ $currentYear }}',
                maxYear: 2032,
                minDate: '{{ $minDate }}',
                drops: 'auto',
                showDropdowns: true,
                "autoApply": true,
                locale: {
                    format: "MM/DD/YYYY",
                },
            }).off('focus');

            $(".expiry_date").on("apply.daterangepicker", function(ev, picker) {
                $(this).val(picker.startDate.format("MM/DD/YYYY"));
            });

            $(".expiry_date").on("click", function() {
                $(".daterangepicker").css("z-index", "1065");
            });
        }

        function completePrepModal(elem) {
            const url = $(elem).attr('data-url');
            const shipment_id = $(elem).attr('data-shipment_id');

            Swal.fire({
                title: "<b style='color:red'>Warning</b>",
                text: "Are you sure you want to complete the Prep for this shipment?",
                showCloseButton: true,
                showCancelButton: false,
                showDenyButton: true,
                customClass: 'swal-wide',
                confirmButtonText: 'Yes',
                confirmButtonColor: '#009ef7',
                cancelButtonText: 'No',
                cancelButtonColor: '#181c32',
                denyButtonText: `No`,
                dangerMode: true,
            }).then(function (result) {
                if (result.isConfirmed)
                {
                    $.ajax({
                        url: url,
                        type: "GET",
                        data: {shipmentId: shipment_id},
                        cache: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function () {
                            $('#completePrepShipmentModal').html('');
                            show_loader();
                        },
                        success: function (response) {
                            hide_loader();
                            if (response.status == 1) {
                                $('#completePrepShipmentModal').html(response.data);
                                // $('#complete_prep_modal_form').find('#shipment_id').val(shipment_id);
                                $('#completePrepShipmentModal').modal('show');
                            } else if (response.status == 200) {
                                displaySuccessMessage(response.message); 
                                window.location.reload();
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        },
                        error: function (xhr, err) {
                            putTransportDetail = false;
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
                } else {
                    return false;
                }
            });
        }

        $(document).on("input", '#product_search_data', function() {
            var searchText = $(this).val().toLowerCase();
            $("#product_list tbody tr").each(function() {
                var listItemText = $(this).text().toLowerCase();
                if (listItemText.indexOf(searchText) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        var updateCompletePrep = false;
        $('body').on('submit','form#complete_prep_modal_form', function(e){

            var status = $('#complete_prep_modal_form').find('#shipment_status').val();
            var statusString = $('#complete_prep_modal_form').find('#shipment_status_string').val();

            var msg = '';
            if (status != 0) {
                msg = 'This action will not update the quantity of the products under the shipment since the Shipment Status is ' + statusString;
            } else {
                msg = 'This will change the quantities in your shipment to match what you have prepped. Any Units or SKUs not yet prepped will be removed from your shipment. Do you want to continue?';
            }

            Swal.fire({
                title: "<b style='color:red'>Warning</b>",
                text: msg,
                showCloseButton: true,
                showCancelButton: false,
                showDenyButton: true,
                customClass: 'swal-wide',
                confirmButtonText: 'Yes',
                confirmButtonColor: '#009ef7',
                cancelButtonText: 'No',
                cancelButtonColor: '#181c32',
                denyButtonText: `No`,
                dangerMode: true,
            }).then(function (result) {
                
                if (result.isConfirmed) {
                    var frm = $('#complete_prep_modal_form');

                    if (!updateCompletePrep) {
                        
                        updateCompletePrep = true;

                        $.ajax({
                            url: $(frm).attr('action'),
                            type: "POST",
                            data: $(frm).serialize(),
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            beforeSend: function () {
                                show_loader();
                            },
                            success: function (data) {
                                updateCompletePrep = false;
                                hide_loader();

                                if (data.status == 200) {
                                    displaySuccessMessage(data.message); 
                                } else {
                                    displayErrorMessage(data.message);
                                }
                                
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            },
                            error: function (xhr, err) {
                                updateCompletePrep = false;
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
                } else {
                    return false;
                }
            });
        });
    </script>
@stop
