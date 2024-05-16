@extends('layouts.app')

@section('title', 'Setting')
@section('breadcrumb')
<li class="breadcrumb-item">
    <div>
        <i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Add Setting') }}
    </div>
</li>
{{-- <li class="breadcrumb-item">{{__('Create Setting')}}</li> --}}
@endsection

@section('content')

<x-forms.parent :isForm=false>
    <!-- Tabs : Start -->
    {{-- @include('fba.fba_shipment.shipment_menu_tabs', ['activeTab' => 'draft']) --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="ship_tabs">
        <x-fba_shipment.tab_menu>
            <a class="nav-link px-10 active" data-bs-toggle="tab" href="#setting_tab" onclick="tabChange('setting');">Add Setting</a>
        </x-fba_shipment.tab_menu>
        <x-fba_shipment.tab_menu>
            <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'warehouse') active @endif" data-bs-toggle="tab" href="#warehouse_tab" onclick="tabChange('warehouse');">Warehouse Details</a>
        </x-fba_shipment.tab_menu>
    </ul>
    <!-- Tabs : End -->

    <x-fba_shipment.tab_contant id="setting_tab">
        {{ Form::open(['route' => ['settings.store'], 'name' => 'add_new_setting', 'id' => 'add_new_setting_form', 'method' => 'POST', 'enctype' => 'multipart/form-data']) }}

        <div class="row">
            <x-forms>
                <x-forms.form-div>
                    <x-forms.label title="Supplier lead time" required="required" />
                    {{ Form::number('supplier_lead_time', !empty($setting->supplier_lead_time) ? $setting->supplier_lead_time : old('supplier_lead_time'), ['id' => 'supplier_lead_time', 'class' => ' form-control validate', 'placeholder' => 'Supplier lead time', 'min' => 0]) }}
                    @error('supplier_lead_time')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Day stock holdings" required="required" />
                    {{ Form::number('day_stock_holdings', !empty($setting->day_stock_holdings) ? $setting->day_stock_holdings : old('day_stock_holdings'), ['id' => 'day_stock_holdings', 'class' => ' form-control validate', 'placeholder' => 'Day stock holdings', 'min' => 0]) }}
                    @error('day_stock_holdings')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Shipping Address" required="required" />
                    {{ Form::textarea('shipping_address', !empty($setting->shipping_address) ? $setting->shipping_address : old('shipping_address'), ['id' => 'shipping_address', 'class' => ' form-control validate', 'placeholder' => 'Shipping Address', 'rows' => 1]) }}
                    @error('shipping_address')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Company Address" required="required" />
                    {{ Form::textarea('company_address', !empty($setting->company_address) ? $setting->company_address : old('company_address'), ['id' => 'company_address', 'class' => ' form-control validate', 'placeholder' => 'Company Address', 'rows' => 1]) }}
                    @error('company_address')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Company Phone Number" required="required" />
                    {{ Form::text('company_phone', !empty($setting->company_phone) ? $setting->company_phone : old('company_phone'), ['id' => 'company_phone', 'class' => ' form-control', 'placeholder' => 'Company Phone Number']) }}
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Company Email" required="required" />
                    {{ Form::email('company_email', !empty($setting->company_email) ? $setting->company_email : old('company_email'), ['id' => 'company_email', 'class' => ' form-control', 'placeholder' => 'Company email', 'Required' => true]) }}
                    @error('company_email')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Warehouse Address" required="required" />
                    {{ Form::textarea('warehouse_address', !empty($setting->warehouse_address) ? $setting->warehouse_address : old('warehouse_address'), ['id' => 'warehouse_address', 'class' => ' form-control', 'placeholder' => 'Warehouse Address', 'rows' => 1]) }}
                    @error('warehouse_address')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

            </x-forms>

            <x-forms.form-footer>
                <x-forms.button :link="url()->previous()" />
                <x-forms.button />
            </x-forms.form-footer>
        </div>

        {{ Form::close() }}
    </x-fba_shipment.tab_contant>
    <!-- WereHouse Page -->
    <x-fba_shipment.tab_contant id="warehouse_tab" style="display: none;">
        {{ Form::open(['route' => ['update.warehouse'], 'name' => 'warehouse_details', 'id' => 'warehouse_details_form', 'method' => 'POST', 'enctype' => 'multipart/form-data']) }}

        <div class="row">
            <x-forms>
                <x-forms.form-div >
                    <x-forms.label title="Warehouse Name" required="required" />
                    {{ Form::text('warehouse_name', !empty($warehouse->name) ? $warehouse->name : old('name'), ['id' => 'warehouse_name', 'class' => ' form-control validate', 'placeholder' => 'Warehouse Name']) }}
                    @error('warehouse_address')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div >
                    <x-forms.label title="Country"/>
                    {{ Form::text('country', !empty($warehouse->country) ? $warehouse->country : old('country'), ['id' => 'country', 'class' => ' form-control validate', 'placeholder' => 'Country']) }}
                    @error('country')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>                
                
                <x-forms.form-div >
                    <x-forms.label title="City" required="required" />
                    {{ Form::text('city', !empty($warehouse->city) ? $warehouse->city : old('city'), ['id' => 'city', 'class' => ' form-control validate', 'placeholder' => 'City']) }}
                    @error('city')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div >
                    <x-forms.label title="State/Province Code" required="required" />
                    {{ Form::text('state_or_province_code', !empty($warehouse->state_or_province_code) ? $warehouse->state_or_province_code : old('state_or_province_code'), ['id' => 'state_or_province_code', 'class' => ' form-control validate', 'placeholder' => 'State/Province Code']) }}
                    @error('state_or_province_code')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>
                @if(!empty($warehouse))
               <input type="hidden" name="warehouse_id" value="{{$warehouse->id}}">
               @endif
                <x-forms.form-div >
                    <x-forms.label title="Country Code" required="required" />
                    {{ Form::text('country_code', !empty($warehouse->country_code) ? $warehouse->country_code : old('country_code'), ['id' => 'country_code', 'class' => ' form-control validate', 'placeholder' => 'country Code']) }}
                    @error('country_code')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div >
                    <x-forms.label title="Postal Code" required="required" />
                    {{ Form::text('postal_code', !empty($warehouse->postal_code) ? $warehouse->postal_code : old('postal_code'), ['id' => 'postal_code', 'class' => ' form-control validate', 'placeholder' => 'Postal Code']) }}
                    @error('postal_code')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div >
                    <x-forms.label title="Warehouse Address 1" required="required" />
                    {{ Form::textarea('warehouse_address_1', !empty($warehouse->address_1) ? $warehouse->address_1 : old('address_1'), ['id' => 'warehouse_address_1', 'class' => ' form-control validate', 'placeholder' => 'Warehouse Address 1','rows' => 1]) }}
                    @error('warehouse_address_1')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

                <x-forms.form-div >
                    <x-forms.label title="Warehouse Address 2" />
                    {{ Form::textarea('warehouse_address_2', !empty($warehouse->address_2) ? $warehouse->address_2 : old('address_2'), ['id' => 'warehouse_address_2', 'class' => ' form-control validate', 'placeholder' => 'Warehouse Address 2','rows' => 1]) }}
                    @error('warehouse_address_2')
                    <x-forms.error :message="$message" />
                    @enderror
                </x-forms.form-div>

            </x-forms>

            <x-forms.form-footer>
                <x-forms.button :link="url()->previous()" />
                <x-forms.button />
            </x-forms.form-footer>
        </div>

        {{ Form::close() }}
    </x-fba_shipment.tab_contant>

</x-forms.parent>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\SettingRequest', '#add_new_setting_form') !!}
{!! JsValidator::formRequest('App\Http\Requests\WarehouseRequest', '#warehouse_details_form') !!}

<script>
    const url = "{{ route('settings.index') }}"

    function tabChange(tabName) {
        // alert(tabName);
        if (tabName == "setting") {
            $('#warehouse_tab').attr('style', "display: none;");
            $('#setting_tab').attr('style', "display: block;");
        } else if (tabName == "warehouse") {
            $('#setting_tab').attr('style', "display: none;");
            $('#warehouse_tab').attr('style', "display: block;");
        }
    }
    $("form#warehouse_details_form").submit(function(e) {
        e.preventDefault();
        if ($("#warehouse_details_form").valid()) {
            var formData = new FormData($("#warehouse_details_form")[0]);
            var redirectUrl = url;
            $.ajax({
                url: $("form#warehouse_details_form").attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    displaySuccessMessage(data.message);
                    // window.location.href = redirectUrl;
                },
                error: function(xhr, err) {
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON
                        .message.length > 0) {
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

    $("form#add_new_setting_form").submit(function(e) {
        e.preventDefault();
        if ($("#add_new_setting_form").valid()) {
            var formData = new FormData($("#add_new_setting_form")[0]);
            var redirectUrl = url;
            $.ajax({
                url: $("form#add_new_setting_form").attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    displaySuccessMessage(data.message);
                    // window.location.href = redirectUrl;
                },
                error: function(xhr, err) {
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON
                        .message.length > 0) {
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
</script>
@stop