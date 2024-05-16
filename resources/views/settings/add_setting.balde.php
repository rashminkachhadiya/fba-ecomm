@extends('layouts.app')

@section('title', 'General Settings')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{__('Settings')}}</a></li>
<li class="breadcrumb-item"><a href="javascript:void(0)">{{__('General Settings')}}</a></li>
@endsection

@section('content')


<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-fluid px-0">
            <div class="container-fluid">
                <div class="card ">
                    <div class="card-body px-0 pb-20 mb-20">

                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_pane_4">Add Settings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#warehouse_tab">Warehouse Detail</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_pane_5">Setting Logs</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="kt_tab_pane_4" role="tabpanel">
                                <div class="tab-content mt-10">
                                    {{ Form::open(['route' => ['settings.save_general_setting'], 'name' => 'add_general_setting', 'id' => 'add_general_setting_form', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
                                    @csrf
                                    <div class="row">
                                        <div class="col">
                                            <div class="row">

                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label required"> Supplier Lead Time <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title=" The time it takes from the time you place an order with your supplier until the items reach your warehouse"></i></label>
                                                        {{ Form::text('supplier_lead_time', !empty($settings) ? $settings->supplier_lead_time : null, ['id' => 'supplier_lead_time', "class" => "form-control form-control-solid validate","placeholder"=>"Supplier Lead Time", 'onkeypress'=>'return onlyNumericAllowed(this,event)', 'onchange' => 'changeTotalLeadTime()']) }}
                                                        @error('supplier_lead_time')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label required"> Prep Time <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="The time taken to prep the produts to sent to FBA"></i></label>
                                                        {{ Form::text('prep_time', !empty($settings) ? $settings->prep_time : null, ['id' => 'prep_time', "class" => "form-control form-control-solid validate","placeholder"=>"Prep Time", 'onkeypress'=>'return onlyNumericAllowed(this,event)', 'onchange' => 'changeTotalLeadTime()']) }}
                                                        @error('prep_time')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="row">

                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label required"> Time to reach Amazon Warehouse <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="The time it will take for the items to reach FBA inventory"></i></label>
                                                        {{ Form::text('time_to_reach_amazon_warehouse', !empty($settings) ? $settings->time_to_reach_amazon_warehouse : null, ['id' => 'time_to_reach_amazon_warehouse', "class" => "form-control form-control-solid validate","placeholder"=>"Time to reach Amazon Warehouse", 'onkeypress'=>'return onlyNumericAllowed(this,event)', 'onchange' => 'changeTotalLeadTime()']) }}
                                                        @error('time_to_reach_amazon_warehouse')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label required"> Safety Stock <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="The amount of buffer or contingency inventory you always want to have on hand"></i></label>
                                                        {{ Form::text('safety_stock', !empty($settings) ? $settings->safety_stock : null, ['id' => 'safety_stock', "class" => "form-control form-control-solid validate","placeholder"=>"Safety Stock", 'onkeypress'=>'return onlyNumericAllowed(this,event)']) }}
                                                        @error('safety_stock')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="mb-10">
                                                    <label class="form-label required"> Total Lead Time <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="Total Lead Time: Supplier Lead Time + Prep Time + Time to reach Amazon Warehouse"></i></label>
                                                    <input type="text" value="{{ !empty($settings) ? $settings->total_lead_time : null }}" id="total_lead_time" class="form-control form-control-solid validate" disabled>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="mb-10">
                                                    <label class="form-label required"> Day stock holdings <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="Day Stock Holding is the number of days you need stock in amazon. Example: 30 days"></i></label>
                                                    {{ Form::text('day_stock_holdings', !empty($settings) ? $settings->day_stock_holdings : null, ['id' => 'day_stock_holdings', "class" => "form-control form-control-solid validate","placeholder"=>"Day Stock Holding", 'onkeypress'=>'return onlyNumericAllowed(this,event)']) }}
                                                    @error('day_stock_holdings')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="mb-10">
                                                    <?php
                                                    $MeltableDate = '';
                                                    if (isset($settings)) {
                                                        $startDate = \Carbon\Carbon::parse($settings->meltable_inventory_accepting_range_start)->format('m/d/Y');
                                                        $endDate = \Carbon\Carbon::parse($settings->meltable_inventory_accepting_range_end)->format('m/d/Y');
                                                        $MeltableDate =  $startDate . ' - ' . $endDate;
                                                    }

                                                    ?>
                                                    <label class="form-label required"> Meltable Inventory Accepting Range <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="FBA accepts meltable products from October 16 to April 14 only"></i></label>
                                                    {{ Form::text('meltable_inventory_accepting_range', !empty($MeltableDate) ? $MeltableDate : null, ['id' => 'meltable_inventory_accepting_range',"class" => "form-control form-control-solid validate","placeholder"=>"Meltable Inventory Accepting Range"]) }}
                                                    @error('meltable_inventory_accepting_range')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="mb-10">
                                                    <label class="form-label required"> Small & Light enrollment <i class="fa-solid fa-circle-info text-gray" data-bs-toggle="tooltip" data-bs-placement="top" title="Selecting YES will make user able to enroll products in small & light program."></i></label>
                                                    <div class="row">
                                                        <div class="col-auto">
                                                            <div class="form-check form-check-custom">
                                                                <input name="is_small_and_light_enrollment_enabled" class="form-check-input" type="radio" value="0" id="is_small_and_light_enrollment_enabled_no" @if($settings->is_small_and_light_enrollment_enabled == 0) checked @endif>
                                                                <label class="form-check-label" for="is_small_and_light_enrollment_enabled_no">
                                                                    No
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="form-check form-check-custom">
                                                                <input name="is_small_and_light_enrollment_enabled" class="form-check-input" type="radio" value="1" id="is_small_and_light_enrollment_enabled_yes" @if($settings->is_small_and_light_enrollment_enabled == 1) checked @endif>
                                                                <label class="form-check-label" for="is_small_and_light_enrollment_enabled_yes">
                                                                    Yes
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="footer-fixed">
                                            <div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
                                                <!--begin::Container-->
                                                <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-end">
                                                    <!--begin::Copyright-->
                                                    <div class="text-dark order-2 order-md-1">
                                                        <a href="{{ url()->previous() }}" type="submit" class="btn btn-sm fs-6 btn btn-secondary">{{ __('Cancel') }}</a>
                                                        <button type="submit" class="btn btn-sm fs-6 btn-primary ms-3">
                                                            {{ __('Save') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{ Form::close()}}
                                </div>
                                {{ Form::hidden('', route('settings.general_setting'), ['id' => 'general_setting_url']) }}
                            </div>
                            <!-- Warehouse Settings -->
                            <div class="tab-pane fade" id="warehouse_tab" role="tabpanel">
                                <div class="tab-content mt-10">
                                    {{ Form::open(['route' => ['settings.save_warehouse_setting'], 'name' => 'add_warehouse_setting', 'id' => 'add_warehouse_setting_form', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
                                    @csrf
                                    <div class="row">
                                        <div class="col">
                                            <div class="row">

                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label required"> Warehouse Name </label>
                                                        {{ Form::text('warehouse_name', !empty($warehouse) ? $warehouse->warehouse_name : null, ['id' => 'warehouse_name', "class" => "form-control form-control-solid validate","placeholder"=>"Ex. Sebago Foods Warehouse"]) }}
                                                        @error('warehouse_name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                        @enderror
                                                    </div>
                                                </div>


                                            </div>
                                            <div class="row">


                                                <div class="col-sm-4">
                                                    <div class="mb-10">
                                                        <label class="form-label"> Warehouse Address </label>
                                                        {{ Form::textarea('address', !empty($warehouse) ? $warehouse->address : null, ['id' => 'address', "class" => "form-control form-control-solid validate","placeholder"=>"Add warehouse address here"]) }}

                                                    </div>
                                                </div>
                                            </div>
                                        </div>



                                        <div class="footer-fixed">
                                            <div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
                                                <!--begin::Container-->
                                                <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-end">
                                                    <!--begin::Copyright-->
                                                    <div class="text-dark order-2 order-md-1">
                                                        <a href="{{ url()->previous() }}" type="submit" class="btn btn-sm fs-6 btn btn-secondary">{{ __('Cancel') }}</a>
                                                        <button type="submit" class="btn btn-sm fs-6 btn-primary ms-3">
                                                            {{ __('Save') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{ Form::close()}}
                                </div>
                                {{ Form::hidden('', route('settings.general_setting'), ['id' => 'general_setting_url']) }}
                                {{ Form::hidden('', route('settings.save_warehouse_setting'), ['id' => 'warehouse_setting_url']) }}
                            </div>
                            <!-- Setting Logs -->
                            <div class="tab-pane fade" id="kt_tab_pane_5" role="tabpanel">
                                @if(!empty($logs))
                                <div class="table-responsive">
                                    <table class="table table-striped gy-7 gs-7">
                                        <thead>
                                            <tr class="fw-semibold fs-6 text-gray-800 border-bottom border-gray-200">
                                                <th class="col-sm-4">Title</th>
                                                <th class="col-sm-6">Description</th>
                                                <th class="col-sm-2">Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($logs as $log)
                                            <tr>
                                                <td>{{ $log['title'] }}</td>
                                                <td>{!! $log['description'] !!}</td>
                                                <td>{{ $log['created_at'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="row">
                                    <div class="col-sm-2">
                                        <p class="m-10">No logs found</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script type="text/javascript" src="{{ asset('js/jquery.validate.min.js')}}"></script>
{!! JsValidator::formRequest('App\Http\Requests\SettingRequest', '#add_general_setting_form'); !!}
<script src="{{ asset('js/settings/form.js')}}" type="text/javascript"></script>
@stop