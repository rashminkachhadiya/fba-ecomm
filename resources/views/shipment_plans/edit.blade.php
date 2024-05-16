@extends('layouts.app')

@section('title', 'FBA Shipping Plan')
@section('breadcrumb')
    <li class="breadcrumb-item text-primary link-class"><a href="{{ route('shipment-plans.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i>Shipping Plan List</a></li>
    <li class="breadcrumb-item text-primary">Edit Shipping Plan</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['shipment-plans.update', ['shipment_plan' => $shipmentPlan->id]], 'name' => 'update_shipment_plans', 'id' => 'update_shipment_plans_form', 'method' => 'PUT', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
    {{ Form::hidden('id', $shipmentPlan->id, ['id' => 'plan_id']) }}
    {{ Form::hidden('totalCount', $totalCount, ['id' => 'totalCount']) }}

    <div class="row">
        <div class="tab-content border rounded p-3 border-secondary shadow-lg">
            
            <div class="row">
                <x-forms>
                    <x-forms.form-div class="col-sm-3" margin="mb-5">
                        <x-forms.label title="Store" required="required" />
                        <x-forms.select id="store_id" name="store_id" disabled>
                            @foreach ($stores as $key => $store)
                                <x-forms.select-options :value="$key" :title="$store" :selected="(($shipmentPlan->store_id == $key)) ? 'selected' : null"/>
                            @endforeach
                        </x-forms.select>
                        @error('store_id')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Shipment From" required="required" />
                        <x-forms.select id="warehouse_id" name="warehouse_id" disabled>
                            @foreach ($wareHouses as $wareHouse)
                                <x-forms.select-options :value="$wareHouse->id" :title="$wareHouse->name" :selected="(($shipmentPlan->warehouse_id == $wareHouse->id)) ? 'selected' : null"/>
                            @endforeach
                        </x-forms.select>
                        @error('warehouse_id')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Shipping Plan Name" required="required" />
                        {{ Form::text('plan_name', !empty($shipmentPlan->plan_name) ? $shipmentPlan->plan_name : old('plan_name'), ['id' => 'plan_name', "class" => "form-control validate",'placeholder' => 'Shipping Plan Name', 'Required' => true, 'onkeyup'=>'updatePlanName(this,'.$shipmentPlan->id.')']) }}
                        @error('plan_name')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Prep preference" required="required" />
                        <x-forms.select id="prep_preference" name="prep_preference" disabled>
                            @foreach ($prepPreferences as $key => $prepPreference)
                                <x-forms.select-options :value="$key" :title="$prepPreference" :selected="(($shipmentPlan->prep_preference == $key)) ? 'selected' : null"/>
                            @endforeach
                        </x-forms.select>
                        @error('prep_preference')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Marketplace" required="required" />
                        <x-forms.select id="destination_country" name="destination_country" disabled>
                            @foreach ($marketplaces as $key => $marketplace)
                                <x-forms.select-options :value="$key" :title="$marketplace" :selected="(($shipmentPlan->destination_country == $key)) ? 'selected' : null"/>
                            @endforeach
                        </x-forms.select>
                        @error('destination_country')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Box Content Info" required="required"/>
                        <x-forms.select id="box_content" name="box_content" disabled>
                            @foreach ($boxContents as $key => $boxContent)
                                <x-forms.select-options :value="$key" :title="$boxContent" :selected="(($shipmentPlan->box_content == $key)) ? 'selected' : null"/>
                            @endforeach
                        </x-forms.select>
                        @error('box_content')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Packing Details" required="required" />
                        <x-forms.select id="packing_details" name="packing_details" disabled>
                            @foreach ($packingDetails as $key => $packingDetail)
                                <option value="{{ $key }}">{{ $packingDetail }}</option>
                            @endforeach
                        </x-forms.select>
                        @error('packing_details')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    @if ($shipmentPlanDetails->count() > 0)
                    @php
                    $totalHazmatQty = 0;
                    $totalOversizedQty = 0;
                    $totalHazmatProduct = $shipmentPlanDetails->sum(function($item){
                    if($item->product && $item->product->is_hazmat == 1)
                    {
                        return true;
                    }
                    });

                    $totalOversizedProduct = $shipmentPlanDetails->sum(function($item){
                    if($item->product && $item->product->is_oversize == 1)
                    {
                        return true;
                    }
                    });

                    $totalSkus = $shipmentPlanDetails->count(function($item){
                    if($item->product)
                    {
                    return true;
                    }
                    });
                    @endphp

                    @foreach ($shipmentPlanDetails as $value)
                        @if ($value->product->is_hazmat == 1)
                            @php
                                $totalHazmatQty += $value->sellable_unit * $value->product->pack_of;
                            @endphp
                        @endif
                        @if ($value->product->is_oversize == 1)
                            @php
                                $totalOversizedQty += $value->sellable_unit * $value->product->pack_of;
                            @endphp
                        @endif
                        
                    @endforeach
                    @endif

                    <div class="col-sm-3 mt-8" style="text-align: right;">
                        <span>Total Hazmat : {{ $totalHazmatProduct }} </span><span>{{'(Qty:'}}</span><span id="total_hazmat_qty">{{ $totalHazmatQty }}</span><span>{{')'}}</span><br>
                        <span>Total Oversized : {{ $totalOversizedProduct }} </span><span>{{'(Qty:'}}</span><span id="total_oversize_qty">{{ $totalOversizedQty }}</span><span>{{')'}}</span><br>
                        <span id="total_skus">Total Skus : {{ $totalSkus }}</span>
                    </div>
                    
                </x-forms>
            </div>
        </div>

        <div class="mt-10">
            <div class="row">
                <div class="input-group w-25 mb-7 flex-nowrap input-group-sm">
                    {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? base64_decode(Request::get('search')) : '', ['id' => $input_id ?? 'search_data', 'autocomplete' => 'off', 'class' => 'form-control px-5', 'placeholder' => 'Search']) }}
                    <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button"
                        id="{{ $button_id ?? 'search_button' }}">
                        <i class="fa-regular fa-magnifying-glass text-primary"></i>
                    </button>
                </div>

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="add_fba_product_modal"
                    data-url="{{ route('add-fba-products', ['plan_id' => $shipmentPlan->id]) }}" plan_id="{{$shipmentPlan->id}}" class="btn btn-sm btn-primary" title="Add More Products">
                        <i class="fa-regular fa-plus"></i>
                    </x-actions.button>
                </div>
            </div>
            <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative"
                id="edit_fba_product">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Warehouse Qty</th>
                        <th>FBA Qty</th>
                        <th class="suggested_shipment_qty">Suggested Shipment Qty</th>
                        <th>Per Case Qty</th>
                        <th>No Of Case</th>
                        <th>Total qty</th>
                        <th>Sellable units</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <input type="hidden" value="{{count($shipmentPlanDetails)}}" id="sku_count">
                    @forelse ($shipmentPlanDetails as $shipmentPlanval)
                        <tr fba_product_id="{{ $shipmentPlanval->product->id }}">
                           
                            {{ Form::hidden('shipment_product_id['.$shipmentPlanval->product->id.']', $shipmentPlanval->id) }}
                            {{ Form::hidden('product_id',$shipmentPlanval->product->id,['class' => 'product_id']) }}
                            {{ Form::hidden('title[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->product->title) }}
                            {{ Form::hidden('sku[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->product->sku) }}
                            {{ Form::hidden('asin[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->product->asin) }}
                            {{ Form::hidden('pack_of[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->product->pack_of, ['class' => 'pack_of']) }}
                            {{ Form::hidden('wh_qty[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->product->wh_qty, ['class' => 'wh_qty']) }}
                            {{ Form::hidden('old_sellable_unit[' . $shipmentPlanval->product->id . ']', $shipmentPlanval->sellable_unit) }}
                            {{ Form::hidden('current_sellable_units['.$shipmentPlanval->product->id.']', $shipmentPlanval->product->sellable_units) }}
                            {{ Form::hidden('current_reserved_qty['.$shipmentPlanval->product->id.']', $shipmentPlanval->product->reserved_qty) }}
                            <td>
                                @if ($shipmentPlanval)
                                    <img src="{{ $shipmentPlanval->product->main_image }}" alt="">
                                @endif
                            </td>
                            <td>
                                <strong class="fw-800">Title:</strong> {{ $shipmentPlanval->product->title }}
                                <div class="d-flex justify-content-start align-items-center min-w-200px">
                                    @include('copy-btn', [
                                        'value' => $shipmentPlanval->product->sku,
                                        'title' => 'SKU',
                                    ])
                                </div>
                                <div class="d-flex justify-content-start align-items-center">
                                    @include('copy-btn', [
                                        'value' => $shipmentPlanval->product->asin,
                                        'title' => 'ASIN',
                                        'link' => 'https://www.amazon.ca/dp/' . $shipmentPlanval->product->asin,
                                    ])
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">
                                    <div class="grid gap-0 column-gap-3">
                                        @if ($shipmentPlanval->product->is_hazmat == 1)
                                            @include('badge', [
                                                'badgeArr' => [
                                                    'title' => 'Hazmat',
                                                    'bgColor' => 'label-light-danger',
                                                ],
                                            ])
                                        <input type="hidden" id="hazmat_item" value="1">
                                        @endif &emsp13;

                                        @if ($shipmentPlanval->product->is_oversize == 1)
                                            @include('badge', [
                                                'badgeArr' => [
                                                    'title' => 'Oversized',
                                                    'bgColor' => 'label-light-primary',
                                                ],
                                            ])
                                        <input type="hidden" id="oversize_item" value="1">
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-start align-items-center mt-2">
                                    <span>
                                        <strong class="fw-700">WH Qty:
                                        </strong>
                                        <span id='wh_qty_{{$shipmentPlanval->product->id}}' class="total_wh_qty">{{ $shipmentPlanval->product->wh_qty }}</span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">

                                    <strong class="fw-700" style="margin-right: 5px">Sellable Units:
                                    </strong><span id='sellable_units_{{$shipmentPlanval->product->id}}'>{{ $shipmentPlanval->product->sellable_units }}</span>
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">
                                    <span>
                                        <strong class="fw-700">Reserved Qty:
                                        </strong>
                                        <span id='reserved_qty_{{$shipmentPlanval->product->id}}'>{{ $shipmentPlanval->product->reserved_qty }}</span>
                                    </span>
                                </div>
                                @if (isset($shipmentPlan) && !empty($shipmentPlan->po_id))
                                <div class="d-flex justify-content-start align-items-center mt-2">
                                    <span>
                                        <strong class="fw-700">PO Qty:
                                        </strong>
                                        <span>{{ $shipmentPlanval->received_qty }}</span>
                                    </span>
                                </div>
                                @endif
                            </td>
                            <td>
                                @include('inventory-detail', ['value' => $shipmentPlanval->product])
                            </td>
                            <td>
                                @include('fba_products.suggested_shipment_qty',[
                                'suggestedShipmentQty' => $shipmentPlanval->suggested_shipment_qty,
                                'ros_30' => $shipmentPlanval->product->salesVelocity->ros_30,
                                'setting' => $setting,
                                'totalFBAQty' => $shipmentPlanval->total_fba_qty
                                ])
                            </td>
                            <td>
                                <span class="case_pack">{{ $shipmentPlanval->product->case_pack }}</span>
                            </td>
                            @php
                                $casePack = $shipmentPlanval->product->case_pack ;
                                $totalQty = $shipmentPlanval->sellable_unit * $shipmentPlanval->product->pack_of;
                                $noOfCase = $totalQty / $casePack;

                                if ($totalQty <= $casePack) {
                                    $noOfCase = 1;
                                } else {
                                    $reminder = $totalQty % $casePack;

                                    if ($reminder === 0) {
                                        $noOfCase = $totalQty / $casePack;
                                    } else {
                                        $noOfCase = ceil($totalQty / $casePack);
                                    }
                                }

                            @endphp
                            <td>
                                <span class="no_of_case">{{$noOfCase}}</span>
                            </td>
                            <td>
                                <span class="total_qty" id='total_qty_{{$shipmentPlanval->product->id}}'>{{$totalQty}}</span>
                            </td>
                            <td>
                                <div class="row">
                                <div class="col-sm-10 justify-content-start align-items-center">
                            
                                    {{ Form::number(
                                        'sellable_unit[' . $shipmentPlanval->product->id . ']',
                                        !empty($shipmentPlanval->sellable_unit) ? $shipmentPlanval->sellable_unit : 
                                        (empty(old($shipmentPlanval->sellable_unit)) ? '0' : old($shipmentPlanval->sellable_unit)),
                                        [
                                            'id' => 'sellable_unit_' . $shipmentPlanval->product->id,
                                            'class' => 'form-control sellable_unit',
                                            'min' => '1',
                                            'step' => '1',
                                            'product-id' => $shipmentPlanval->product->id,
                                            'plan-id' => $shipmentPlan->id,
                                            'onkeyup'=>'updateSellableUnit(this,event)',
                                            'data-url'=>route('update-auto-shipment-detail')
                                        ],
                                    ) }}
                                   
                                </div>
                                    <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">
                                    &emsp13;<span title="Pack of: {{ $shipmentPlanval->product->pack_of }}"><i
                                            class="fa-solid fa-circle-info"></i></span>
                                </div>
                            </div>

                            </td>
                            <td class="min-w-150px">
                                <textarea name="product_note[{{ $shipmentPlanval->product->id }}]" id="product_note" class="form-control" cols="100" rows="2" maxlength="250"
                                    product-id="{{ $shipmentPlanval->product->id }}" onkeyup="updateProductNote('{{ $shipmentPlanval->id }}', $(this))" data-url={{route('update-auto-shipment-detail')}}>{{ $shipmentPlanval->product->product_note }}</textarea>
                            </td>
                            <td>
                                <x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" 
                                data-url="{{ route('delete-plan-product', ['product_id' => $shipmentPlanval->id]) }}" 
                                onclick="planProductDelete('{{ $shipmentPlanval->id }}', $(this))">
                                    <x-actions.icon class="fas fa-trash mt-4" style="color:red"/>
                                </x-actions.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">No Products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-forms.form-footer>
                <x-forms.button :link="route('shipment-plans.index')" />
                <x-forms.button name="Update Plan" />
            </x-forms.form-footer>
        </div>
    </div>
    {{ Form::close()}}
</x-forms.parent>

@include('shipment_plans.add_fba_product_modal')

@endsection
@section('page-script')
    {!! JsValidator::formRequest('App\Http\Requests\UpdateShipmentPlanRequest', '#update_shipment_plans_form'); !!}
    <script>
        const url = "{{ route('shipment-plans.index') }}"
        const deleteShipmentUrl = "{{ URL::to('shipment-plans') }}/"
        const createShipment = false;
    </script>
    <script src="{{ asset('js/shipment_plans/edit-plan.js')}}" type="text/javascript"></script>
    <script>
        $(document).on('mouseenter', '.suggested_shipment_qty', function() {
            // Get the new tooltip content based on the header's text or any other criteria
            const newTooltipContent = '((Target qty on hands days + Local Lead Time) * 30 days ROS) - Current Amazon inventory';
            // Update the title attribute with the new content
            $(this).attr('title', newTooltipContent);
        })  
    </script>
@stop