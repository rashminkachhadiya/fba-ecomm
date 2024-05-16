@extends('layouts.app')

@section('title', 'FBA Shipments')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('fba-shipments.fba_working_shipment_list') }}">{{ __('FBA Shipment') }}</a>
    </li>
    <li class="breadcrumb-item">{{ __('Transport Info') }}</li>

@endsection

@section('content')

    <x-forms.parent>
        <div class="row">
            <h4 class="mb-0">
                <strong> Shipment Transport Info<br><br>
                    <div>Shipment Name:  @if (isset($shipmentData) && !empty($shipmentData)) {{$shipmentData['shipment_name'].' ('.$shipmentData['shipment_id'].')'}}@endif
                    </div> 
                </strong>
            </h4>

            <!-- Card body -->
            <div class="card-body">

                <div class="col-sm-12">

                    <form id="update_transport_info_form" name="update_transport_info_form" action="{{route('save_transport_info')}}"
                        onsubmit="return false;" method='POST'>

                        <div class="row">

                            <input type='hidden' name='shipment_log_id' id="shipment_log_id" value='{{$shipmetId}}'>

                            {{-- @if (isset($transportInfo) && $transportInfo->transport_status != null) --}}
                                <input type='hidden' id='if_post_transport_info' name='if_post_transport_info' value='1'>
                            {{-- @else --}}
                                {{-- <input type='hidden' id='if_post_transport_info' name='if_post_transport_info' value='0'> --}}
                            {{-- @endif --}}

                            <div class="tab-content border rounded p-5 border-secondary shadow col-sm-6">

                                    <strong class="fw-800">Shipping Method :</strong>

                                    <div class="mt-3">
                                        <div class="radio-inline">
                                            <input type="radio" class="shipping_method_radio"
                                                name="shipping_method_radio" id="sp_radio" value="SP" {{isset($transportInfo) && ($transportInfo->shipping_method == 'SP') ? 'checked' : 'checked'}}>
                                            <label for='sp_radio'>
                                                Small Parcel Delivery (SP)
                                            </label>
                                            <p>
                                                I'm shipping individual boxes
                                            </p>
                                        </div>
                                        <br>
                                        <div class="radio-inline">
                                            <input type="radio" class="shipping_method_radio"
                                                name="shipping_method_radio" id="ltl_radio" value="LTL" {{isset($transportInfo) && ($transportInfo->shipping_method == 'LTL') ? 'checked' : ''}}>
                                            <label for='ltl_radio'>
                                                Less Than Truckload (LTL)
                                            </label>
                                            <p>
                                                I'm shipping pallet(s); shipment at least 150lb.
                                            </p>
                                        </div>
                                        <strong class="fw-800 mt-2" style="font-size: 12px;">Note: Using stacked
                                            pallet(s) provides the most optimized rate. Rates may be
                                            higher for unstacked pallet(s) in some cases. If you select stacked pallets
                                            in the workflow, the carrier will arrive prepared to load stacked pallet(s).
                                            A subsequent decision to use unstacked pallet(s) may lead to delay and
                                            additional cost.</strong>
                                    </div>

                            </div>

                            <div class="tab-content border rounded p-5 border-secondary shadow col-sm-6">

                                <strong class="fw-800">Shipping Carrier :</strong>

                                <div class="mt-3">
                                    <div class="radio-inline">
                                        <input type="radio" name="shipping_carrier_radio" id="ap_radio"
                                            value="1" {{isset($transportInfo) && ($transportInfo->shipping_carrier == '1') ? 'checked' : 'checked'}}>
                                        <label for='ap_radio'>
                                            Amazon-Partnered Carrier (UPS)
                                        </label>
                                    </div>

                                    <div class="radio-inline">
                                        <input type="radio" name="shipping_carrier_radio" id="nap_radio"
                                            value="0" {{isset($transportInfo) && ($transportInfo->shipping_carrier == '0') ? 'checked' : ''}}>
                                        <label for='nap_radio'>
                                            Non Amazon-Partnered Carrier
                                        </label>

                                        <div class="row mt-4">
                                            <div class="non_partner_carrier {{isset($transportInfo) && (($transportInfo->shipping_carrier == '0')) ? '' : 'd-none'}}">
                                                <strong class="custom-label fw-800">Carrier:</strong>
                                                <x-forms.select id="other_shipping_carrier" name="other_shipping_carrier" required="required">
                                                    @if (isset($carrierNameArray))
                                                        @foreach ($carrierNameArray as $key => $item)
                                                            <x-forms.select-options value="{{$item}}" title="{{$item}}" :selected="((isset($transportInfo) && $transportInfo->other_shipping_carrier == $key)) ? 'selected' : null"/>
                                                        @endforeach
                                                    @endif
                                                </x-forms.select>
                                            </div>
                                            <div class="mt-2 pro_number {{isset($transportInfo) && (($transportInfo->shipping_carrier == '0') && ($transportInfo->shipping_method == 'LTL')) ? '' : 'd-none'}}" >
                                                <label class="form-label required fw-800">Pro Number</label>
                                                <input type="text" id="pro_number" name="pro_number"
                                                    class="form-control" value="{{isset($transportInfo) && $transportInfo->pro_number ? $transportInfo->pro_number : ''}}"
                                                    maxlength="10" onblur="this.value = $.trim(this.value);">
                                                    <div id="pro_number_error" class="help-block error-help-block"></div>
                                            </div>
                                            <div class="mt-2 tracking_id {{isset($transportInfo) && (($transportInfo->shipping_carrier == '0') && ($transportInfo->shipping_method == 'SP')) ? '' : 'd-none'}}" >
                                                <label class="form-label required fw-800">Tracking ID</label>
                                                <input type="text" id="tracking_id" name="tracking_id"
                                                    class="form-control" value="{{isset($transportInfo) && $transportInfo->tracking_id ? $transportInfo->tracking_id : ''}}"
                                                     onblur="this.value = $.trim(this.value);">
                                                    <div id="tracking_id_error" class="help-block error-help-block"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <strong class="fw-800" style="font-size: 12px;">Note : The Amazon Partnered
                                        Carrier program is only available in the US
                                        only.</strong>

                                </div>
                            </div>

                            <div class="tab-content border rounded p-5 border-secondary shadow col-sm-6 form-group freight mt-4 {{isset($transportInfo) && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'LTL')) ? '' : 'd-none'}}">

                                <strong class="custom-label fw-800">Freight Details : </strong>

                                <div class="row mt-3">

                                    <div id="div_freight_ready_date ">
                                        <strong class="form-label"> Freight Ready Date</strong>
                                            <x-datepicker>
                                                {{ Form::text('freight_ready_date', isset($transportInfo) && $transportInfo->freight_ready_date ? $transportInfo->freight_ready_date : '', ['id' => 'freight_ready_date', 'class' => 'form-control', 'name' => 'freight_ready_date']) }}
                                                <x-datepicker.calendar />
                                            </x-datepicker>
                                            <div id="freight_ready_date_error" class="help-block error-help-block"></div>
                                    </div>

                                    <div class="mt-5">
                                        <strong class="seller_declared_value custom-label"> Seller Declared
                                            Value :
                                        </strong>
                                        <input type='text' name='seller_declared_value'
                                            id='seller_declared_value' class='form-control' value="{{isset($transportInfo) && $transportInfo->seller_declared_value ? $transportInfo->seller_declared_value : '0'}}">
                                        <span class='custom-label fw-800' style="font-size: 12px;">Note: If not
                                            provided,
                                            declared value will be calculated at $1.50/lb.</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row number_boxes_class {{isset($transportInfo) && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'LTL')) ? '' : 'd-none'}}">
                            <div class="mt-4 tab-content border rounded p-5 border-secondary shadow col-sm-6">
                                <div id="shipment_label">

                                    <div class="">
                                        <strong class="fw-800">Shipping Package Labels:</strong>

                                        <div class="mt-2 mb-2">
                                            <strong class="boxes_from form-label required"># of boxes (for LTL):
                                            </strong>
                                            <input class="form-control" type='number' name='number_boxes'
                                                id='number_boxes' min="1" value='{{isset($transportInfo) && !empty($transportInfo->number_boxes) ? $transportInfo->number_boxes : $totalBox}}'
                                                onkeypress="return isNumber(event)" {{(isset($transportInfo) && ($transportInfo->system_status == '1') && ($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'LTL')) ? 'readonly' : ''}}>
                                                <div id="number_boxes_error" class="help-block error-help-block"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                    <div class="row">
                        <div class="mt-4 tab-content border rounded p-5 border-secondary shadow col-sm-12 {{isset($transportInfo) && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'LTL')) ? '' : 'd-none'}}" id="pallet_main_div">
                            <form id="update_pallet_details_form" name="update_pallet_details_form" onsubmit="return false;"
                                onsubmit="return false;">
                                <input type="hidden" name="delete_transport_pallet" id="delete_transport_pallet" value="{{route('delete_transport_pallet')}}">
                                <label class='custom-label mb-2 mt-1 fw-800'> Pallet Details : </label>
                                    <div id="pallet_list_ajax_data">
                                        <style>
                                            .table>tbody>tr>td {
                                                vertical-align: middle;
                                                text-align: center;
                                            }

                                            input.error {
                                                margin-top: 0 !important;
                                            }
                                        </style>
                                        <table id='pallet_table'
                                            class="table table-hover table-custom-blue top-align-table sticky-header table-responsive">
                                            <thead>
                                                <tr class="table-header">

                                                    <th scope="col" class="text-left" colspan="2">
                                                        Pallet Dimension (In)
                                                    </th>
                                                    <th scope="col">
                                                        Pallet Weight (lb)
                                                    </th>

                                                    <th scope="col">
                                                        # of Pallet(s)
                                                    </th>

                                                    <th scope="col">
                                                        Total Weight(lb)
                                                    </th>

                                                    <th scope="col">
                                                        Stackable Pallet(s)
                                                    </th>
                                                    <th scope="col" class="text-left action_td">
                                                        Action
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="fw-700">Length x Width</td>
                                                    <td class="fw-700">Height</td>
                                                </tr>
                                            </thead>
                                            <tbody class="pallet_tr_div">
                                                @if (isset($isPalletArray) && !empty($isPalletArray) && count($isPalletArray) > 0)
                                                    @foreach ($isPalletArray as $item)
                                                    <tr class='pallet_existing_data'>

                                                        <td class="text-left" width="10%">
                                                            48 x 40 x </td>
    
                                                        <td class="text-left" width="10%">
                                                            <input type="number" name='pallet_height[{{$item->id}}]'
                                                                class="form-control required pallet_height" value="{{$item->pallet_height}}"
                                                                max="72" only_numeric greaterThanZero="true"
                                                                data-palletid="{{$item->id}}" id="pallet_height_{{$item->id}}"
                                                                onkeypress="return isNumberKey(event)">
                                                        </td>
    
                                                        <td>
                                                            <input type="number" name="pallet_weight[{{$item->id}}]"
                                                                class="form-control calc_total_pallet_weight required pallet_weight"
                                                                value="{{$item->pallet_weight}}" only_numeric greaterThanZero="true"
                                                                max="1500" data-palletid="{{$item->id}}" id="pallet_weight_{{$item->id}}"
                                                                onkeypress="return isNumberKey(event)">
                                                        </td>
    
                                                        <td>
                                                            <input type="number" name="number_of_pallet[{{$item->id}}]"
                                                                class="form-control pallet_count calc_total_pallet required number_of_pallet"
                                                                value="{{$item->number_of_pallet}}" only_numeric greaterThanZero="true"
                                                                max="26" min='1' id="number_of_pallet_{{$item->id}}"
                                                                data-palletid="{{$item->id}}" onkeypress="return isNumber(event)">
                                                        </td>
    
                                                        <td>
                                                            <input type="number" name="pallet_total_weight[{{$item->id}}]"
                                                                class="form-control total_weight required"
                                                                value="{{$item->pallet_total_weight}}" data-palletid="{{$item->id}}"
                                                                id="pallet_total_weight_{{$item->id}}" readonly="readonly">
                                                        </td>
    
                                                        <td class="text-center">
                                                            <input type="checkbox" name="is_stackable[{{$item->id}}]"
                                                                class="form-check form-check-input w-4px d-flex-inline is_stackable" data-palletid="{{$item->id}}"
                                                                value="{{$item->is_stackable}}" id="is_stackable_{{$item->id}}" {{isset($item) && ($item->is_stackable == '1') ? 'checked' : ''}}>
                                                        </td>
                                                        <td class="text-left action_td">
                                                            <a href="javascript:void(0);"
                                                                unique_id='{{$item->id}}' delete-for="pallet" title="Delete" onclick="deletePalletPackage($(this))">
                                                                <i aria-hidden="true" class="fa fa-trash" style="color:red"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                @endif
                                              
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-sm-12 mt-2 mb-1">
                                        <div class="row">
                                            <div class="col-sm-2">
                                                <input type='number' id='add_num_of_pallets' only_numeric
                                                    class='form-control' name='add_num_of_pallets'
                                                    onkeypress="return isNumber(event)" value='1' min="1" max="100" data-url="{{route('create_transport_pallet')}}">
                                            </div>
                                            <div class="col-sm-1" id="add_pallet_btn_div">
                                                <a href="javascript:void(0);" data-toggle="tooltip"
                                                    data-placement="bottom" title="Add Pallet"
                                                    id='add_pallet_btn' data-btn-type="add_pallet" class="btn btn-icon-only blue" onclick="addMorePalletPackage($(this))">
                                                    <i aria-hidden="true" class="fa fa-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mt-4 tab-content border rounded p-5 border-secondary shadow col-sm-12 {{isset($transportInfo) && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP')) ? '' : 'd-none'}}" id="package_main_div">
                            <form id="update_package_details_form" name="update_package_details_form" onsubmit="return false;"
                                onsubmit="return false;">
                                <label class='custom-label mb-2 mt-1 fw-800'> Package Details : </label>
                                    <div id="package_list_ajax_data">
                                        <table id='package_table'
                                            class="table table-hover table-custom-blue top-align-table  table-responsive">
                                            <thead>
                                                <tr class="table-header">

                                                    <th scope="col" class="text-left" colspan="3" style="width:20%">
                                                        Package Dimension (In)<br>
                                                    </th>
                                                    <th scope="col">
                                                        Package Weight (lb)
                                                    </th>
                                                    <th scope="col">
                                                        # of Package(s)
                                                    </th>
                                                    <th scope="col">
                                                        Total Weight(lb)
                                                    </th>
                                                    <th scope="col" class="text-left action_td {{(isset($transportInfo) && ($transportInfo->system_status != '0') && ($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP')) ? 'd-none' : ''}}">
                                                        Action
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="fw-700">Length</td>
                                                    <td class="fw-700">Width</td>
                                                    <td class="fw-700">Height</td>
                                                </tr>
                                            </thead>
                                            <tbody class="package_tr_div">
                                                @if (isset($isPackageArray) && !empty($isPackageArray) && count($isPackageArray) > 0)
                                                    @foreach ($isPackageArray as $item)
                                                    <tr class='package_existing_data'>

                                                        <td class="text-left" width="10%">
                                                            <input type="number" name='package_length[{{$item->id}}]'
                                                            class="form-control required package_length" value="{{$item->package_length}}"
                                                            max="25" only_numeric greaterThanZero="true"
                                                            data-packageid="{{$item->id}}" id="package_length_{{$item->id}}"
                                                            onkeypress="return isNumberKey(event)">
                                                        </td>

                                                        <td class="text-left" width="10%">
                                                            <input type="number" name='package_width[{{$item->id}}]'
                                                            class="form-control required package_width" value="{{$item->package_width}}"
                                                            max="8" only_numeric greaterThanZero="true"
                                                            data-packageid="{{$item->id}}" id="package_width_{{$item->id}}"
                                                            onkeypress="return isNumberKey(event)">
                                                        </td>
    
                                                        <td class="text-left" width="10%">
                                                            <input type="number" name='package_height[{{$item->id}}]'
                                                                class="form-control required package_height" value="{{$item->package_height}}"
                                                                max="14" only_numeric greaterThanZero="true"
                                                                data-packageid="{{$item->id}}" id="package_height_{{$item->id}}"
                                                                onkeypress="return isNumberKey(event)">
                                                        </td>
    
                                                        <td>
                                                            <input type="number" name="package_weight[{{$item->id}}]"
                                                                class="form-control calc_total_package_weight required package_weight"
                                                                value="{{$item->package_weight}}" only_numeric greaterThanZero="true"
                                                                max="1500" data-packageid="{{$item->id}}" id="package_weight_{{$item->id}}"
                                                                onkeypress="return isNumberKey(event)">
                                                        </td>

                                                        <td>
                                                            <input type="number" name="number_of_package[{{$item->id}}]"
                                                                class="form-control package_count calc_total_package required number_of_package" {{(isset($transportInfo) && ($transportInfo->system_status != '0') && ($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP')) ? 'readonly' : ''}}
                                                                value="{{$item->number_of_package}}" only_numeric greaterThanZero="true"
                                                                max="26" min='1' id="number_of_package_{{$item->id}}"
                                                                data-packageid="{{$item->id}}" onkeypress="return isNumber(event)">
                                                        </td>
    
                                                        <td>
                                                            <input type="number" name="package_total_weight[{{$item->id}}]"
                                                                class="form-control total_weight required"
                                                                value="{{$item->package_total_weight}}" data-packageid="{{$item->id}}"
                                                                id="package_total_weight_{{$item->id}}" readonly="readonly">
                                                        </td>
                                                        <td class="text-left action_td {{(isset($transportInfo) && ($transportInfo->system_status != '0') && ($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP')) ? 'd-none' : ''}}">
                                                            <a href="javascript:void(0);"
                                                                unique_id='{{$item->id}}' delete-for="package" title="Delete" onclick="deletePalletPackage($(this))">
                                                                <i aria-hidden="true" class="fa fa-trash" style="color:red"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                @endif
                                              
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-sm-12 mt-2 mb-1 {{(isset($transportInfo) && ($transportInfo->system_status != '0') && ($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP')) ? 'd-none' : ''}}">
                                        <div class="row">
                                            <div class="col-sm-2">
                                                <input type='number' id='add_num_of_packages' only_numeric
                                                    class='form-control' name='add_num_of_packages'
                                                    onkeypress="return isNumber(event)" value='1' min="1" max="100" data-url="{{route('create_transport_pallet')}}">
                                            </div>
                                            <div class="col-sm-1" id="add_pallet_btn_div">
                                                <a href="javascript:void(0);" data-toggle="tooltip"
                                                    data-placement="bottom" title="Add Package"
                                                    id='add_pallet_btn' data-btn-type="add_package" class="btn btn-icon-only blue" onclick="addMorePalletPackage($(this))">
                                                    <i aria-hidden="true" class="fa fa-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>

                    @if (isset($transportInfo) && ($transportInfo->system_status == '1' || $transportInfo->system_status == '2') && !empty($transportInfo->estimate_shipping_cost))
                    <div class="row mt-5">
                        <div class="tab-content border rounded p-5 border-secondary shadow col-sm-6">

                            <strong class="fw-800">Fee Summary:</strong>
                            <div class="mt-3">
                                <label for="estimated_fee">Estimated Fee: </label>
                                <strong class="fw-800">${{$transportInfo->estimate_shipping_cost }}</strong>
                            </div>
                            <div class="mt-3">
                                <label for="confirm_deadline">Deadline for confirmation: </label>
                                <strong class="fw-800">{{($transportInfo->confirm_deadline) == null ? '-' : $transportInfo->confirm_deadline }}</strong>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if (isset($transportInfo) && ($transportInfo->system_status == '3') && !empty($transportInfo->estimate_shipping_cost))
                    <div class="row mt-5">
                        <div class="tab-content border rounded p-5 border-secondary shadow col-sm-6">

                            <strong class="fw-800">Fee Summary:</strong>
                            <div class="mt-3">
                                <label for="estimated_fee">Estimated Fee: </label>
                                <strong class="fw-800">${{$transportInfo->estimate_shipping_cost }}</strong>
                            </div>
                        </div>
                    </div>
                    @endif

                        @if (isset($transportInfo) && ($transportInfo->system_status == '1' || $transportInfo->system_status == '2') && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP' || $transportInfo->shipping_method == 'LTL')))
                        <x-forms.button id="estimate_transport" name="Estimate" class="btn btn-custom-warning mt-5" data-url="{{route('estimate_transport_detail')}}"/>
                        @endif
                        @if (isset($transportInfo) && ($transportInfo->system_status == '2') && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP' || $transportInfo->shipping_method == 'LTL')))
                        <x-forms.button id="confirm_transport" name="Confirm Transport" class="btn btn-custom-success mt-5" data-url="{{route('confirm_transport_detail')}}"/>
                        @endif
                        @if (isset($transportInfo) && ($transportInfo->system_status == '3') && (($transportInfo->shipping_carrier == '1') && ($transportInfo->shipping_method == 'SP' || $transportInfo->shipping_method == 'LTL')))
                        <x-forms.button id="void_transport" name="Void Transport" class="btn btn-danger mt-5" data-url="{{route('void_transport_detail')}}"/>
                        @endif

                    <x-forms.form-footer>
                        <x-forms.button id="save_all_pallets" name="Save Transport Info"/>
                        <x-forms.button id="save_post_shipment" name="Save & Post on Amazon" form="update_transport_info_form" class="btn btn-info" data-url="{{route('sent_transport_detail')}}"/>
                        <x-forms.button :link="route('fba-shipments.fba_working_shipment_list')" name="Back to Shipment"/>
                    </x-forms.form-footer>

                </div>
            </div>
        </div>
    </x-forms.parent>
@endsection

@section('page-script')
    {!! JsValidator::formRequest('App\Http\Requests\FBATransportInfoRequest', '#update_transport_info_form') !!}
    {!! JsValidator::formRequest('App\Http\Requests\FBATransportInfoRequest', '#update_pallet_details_form') !!}
    {!! JsValidator::formRequest('App\Http\Requests\FBATransportInfoRequest', '#update_package_details_form') !!}

    <script src="{{ asset('js/fba_shipment/transport_info.js') }}"></script>
@stop

