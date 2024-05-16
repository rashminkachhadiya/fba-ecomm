@extends('layouts.app')

@section('title', 'Create Shipping Plan')
@section('breadcrumb')
<li class="breadcrumb-item text-primary link-class"><a href="{{ route('shipment-plans.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i>Shipping Plan List</a></li>
<li class="breadcrumb-item text-primary">Create Shipping Plan</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['shipment-plans.store'], 'name' => 'store_shipment_plans', 'id' => 'store_shipment_plans', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
    {{ Form::hidden('po_id',$po_id) }}
    <div class="row">
        <div class="tab-content border rounded p-3 border-secondary shadow-lg">
            <div class="row">
                <x-forms style="flex-basis: 100%">
                    <x-forms.form-div class="col-sm-3" margin="mb-5">
                        <x-forms.label title="Store" required="required" />
                        <x-forms.select id="store_id" name="store_id">
                            @foreach($stores as $key => $store)
                            <x-forms.select-options :value="$key" :title="$store" />
                            @endforeach
                        </x-forms.select>
                        @error('store_id')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Shipment From" required="required" />
                        <x-forms.select id="warehouse_id" name="warehouse_id">
                            @foreach($wareHouses as $wareHouse)
                            <x-forms.select-options :value="$wareHouse->id" :title="$wareHouse->name" />
                            @endforeach
                        </x-forms.select>
                        @error('warehouse_id')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Shipping Plan Name" required="required" />
                        {{ Form::text('plan_name', !empty(old('plan_name')) ? old('plan_name') : null, ['id' => 'plan_name', "class" => "form-control validate","placeholder"=>"Shipping Plan Name", "Required"=>true]) }}
                        @error('plan_name')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Prep preference" required="required" />
                        <x-forms.select id="prep_preference" name="prep_preference" readonly>
                            @foreach($prepPreferences as $key => $prepPreference)
                            <x-forms.select-options :value="$key" :title="$prepPreference" />
                            @endforeach
                        </x-forms.select>
                        @error('prep_preference')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Marketplace" required="required" />
                        <x-forms.select id="destination_country" name="destination_country">
                            @foreach($marketplaces as $key => $marketplace)
                            <x-forms.select-options :value="$key" :title="$marketplace" />
                            @endforeach
                        </x-forms.select>
                        @error('destination_country')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Box Content Info" required="required" />
                        <x-forms.select id="box_content" name="box_content" readonly>
                            @foreach($boxContents as $key => $boxContent)
                            <x-forms.select-options :value="$key" :title="$boxContent" />
                            @endforeach
                        </x-forms.select>
                        @error('box_content')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Packing Details" required="required" />
                        <x-forms.select id="packing_details" name="packing_details">
                            @foreach($packingDetails as $key => $packingDetail)
                            <option value="{{ $key }}">{{ $packingDetail }}</option>
                            @endforeach
                        </x-forms.select>
                        @error('packing_details')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    @php 
                        $totalSkus = 0; 
                        $totalHazmatProduct = 0; 
                        $totalOversizedProduct = 0;
                        $totalHazmatQty = 0;
                        $totalOversizedQty = 0;
                    @endphp
                    @if ($poProducts->count() > 0)
                        @php
                        $totalHazmatProduct = $poProducts->sum(function($item){
                            if($item->product && $item->product->is_hazmat == 1)
                            {
                                return true;
                            }
                        });

                        $totalOversizedProduct = $poProducts->sum(function($item){
                            if($item->product && $item->product->is_oversize == 1)
                            {
                                return true;
                            }
                        });

                        
                        $totalSkus = $poProducts->count(function($item){
                            if($item->product)
                            {
                                return true;
                            }
                        });
                        @endphp
                    @endif

                    @if (count($amazonProducts) > 0)
                        @php
                        $totalHazmatProduct += $amazonProducts->sum(function($item){
                            if($item->is_hazmat == 1)
                            {
                                return true;
                            }
                        });

                        $totalOversizedProduct += $amazonProducts->sum(function($item){
                            if($item->is_oversize == 1)
                            {
                                return true;
                            }
                        });

                        
                        $totalSkus += $amazonProducts->count(function($item){
                            if($item)
                            {
                                return true;
                            }
                        });
                        @endphp
                    @endif
                    <div class="col-sm-3">
                        <div class="row border1 form-control rounded py-1 px-0 m-0">
                            <div class="col-auto">Total Skus :<span id="total_sku" class="fw-700">{{ $totalSkus }}</span></div>
                            <div class="col-auto">Total Oversized : <span id="total_oversize_product" class="fw-700">{{ $totalOversizedProduct }}</span></div>
                            <div class="col-auto">Total Hazmat : <span id="total_hazmat_product" class="fw-700">{{ $totalHazmatProduct }}</span></div>
                            {{-- <div class="col-auto">Total Skus :<span id="total_sku" class="fw-700">{{ $totalSkus }}</span></div>
                            <div class="col-auto">Total Oversized : <span id="total_oversize_product" class="fw-700">{{ $totalOversizedProduct }}</span><span>(Qty:</span><span id="total_oversize_qty">{{ $totalOversizedQty }}</span><span>)</span></div>
                            <div class="col-auto">Total Hazmat : <span id="total_hazmat_product" class="fw-700">{{ $totalHazmatProduct }}</span><span>(Qty:</span><span id="total_hazmat_qty">{{ $totalHazmatQty }}</span><span>)</span></div> --}}
                        </div>
                    </div>
                </x-forms>
            </div>
        </div>

        <div class="mt-10">
            <div class="row">
                <div class="input-group w-25 mb-7 flex-nowrap input-group-sm">
                    {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? base64_decode(Request::get('search')) : '', ['id' => $input_id ?? 'search_data', 'autocomplete' => 'off', 'class' => 'form-control px-5', 'placeholder' => 'Search']) }}
                    <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button" id="{{ $button_id ?? 'search_button' }}">
                        <i class="fa-regular fa-magnifying-glass text-primary"></i>
                    </button>
                </div>

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="add_fba_product_modal"
                    data-url="{{ route('add-products', ['po_id' => request()->segment(2)]) }}" po_id="{{ request()->segment(2) }}" class="btn btn-sm btn-primary" title="Add More Products">
                        <i class="fa-regular fa-plus"></i>
                    </x-actions.button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="po_product_list">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Image</th>
                            <th class="min-w-300px">Product Details</th>
                            <th>Available WH Qty</th>
                            <th class="min-w-150px">Received Qty</th>
                            <th class="suggested_shipment_qty">Suggested Shipment Qty</th>
                            <th>Per Case Qty</th>
                            <th>No Of Case</th>
                            <th>Total qty</th>
                            <th>Send Sellable units</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    {{-- <tbody id="product_data">
                        @php
                        $row = 1;
                        @endphp
                        @forelse ($poProducts as $poProduct)
                            <tr>
                                {{ Form::hidden('product_id',$poProduct->product->id,['class' => 'product_id']) }}
                                {{ Form::hidden('title['.$poProduct->product->id.']',$poProduct->product->title) }}
                                {{ Form::hidden('sku['.$poProduct->product->id.']',$poProduct->product->sku) }}
                                {{ Form::hidden('asin['.$poProduct->product->id.']',$poProduct->product->asin) }}
                                {{ Form::hidden('pack_of['.$poProduct->product->id.']',$poProduct->product->pack_of,['class' => 'pack_of']) }}
                                {{ Form::hidden('wh_qty['.$poProduct->product->id.']',$poProduct->product->wh_qty) }}
                                {{ Form::hidden('current_sellable_units['.$poProduct->product->id.']', $poProduct->product->sellable_units) }}
                                <td>{{ $row }}</td>
                                <td>
                                    @if($poProduct->product)
                                    <img src="{{ $poProduct->product->main_image }}" alt="">
                                    @endif
                                </td>
                                <td>
                                    <strong class="fw-800">Title:</strong> {{ $poProduct->product->title }}
                                    <div class="d-flex justify-content-start align-items-center">
                                        @include('copy-btn',[
                                        'value' => $poProduct->product->sku,
                                        'title' => 'SKU',
                                        ])
                                    </div>
                                    <div class="d-flex justify-content-start align-items-center">
                                        @include('copy-btn',[
                                        'value' => $poProduct->product->asin,
                                        'title' => 'ASIN',
                                        'link' => "https://www.amazon.ca/dp/".$poProduct->product->asin
                                        ])
                                    </div>
                                    <div class="grid column-gap-5 d-flex mt-2">
                                        @if ($poProduct->product->store)
                                        <div class="mt-2 me-2">
                                            @include('badge', [
                                            'badgeArr' => [
                                            'title' => $poProduct->product->store->store_name,
                                            'bgColor' => ($poProduct->product->store->id == 1) ? 'label-light-info' : 'label-light-success',
                                            ],
                                            ])
                                        </div>
                                        @endif

                                        @if ($poProduct->product->is_hazmat == 1)
                                        <div class="mt-2 me-2">
                                            @include('badge',['badgeArr' => [
                                            'title' => 'Hazmat',
                                            'bgColor' => 'label-light-danger',
                                            ]])
                                        </div>
                                        @endif

                                        @if ($poProduct->product->is_oversize == 1)
                                        <div class="mt-2 me-2">
                                            @include('badge', ['badgeArr' => [
                                            'title' => 'Oversized',
                                            'bgColor' => 'label-light-primary',
                                            ]])
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <strong class="fw-700" style="margin-right: 5px">WH Qty:</strong>{{ $poProduct->product->wh_qty }}
                                    </div>

                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <strong class="fw-700" style="margin-right: 5px">Reserved Qty:</strong>{{ $poProduct->product->reserved_qty }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <strong class="fw-700" style="margin-right: 5px">Received Qty:</strong>{{ $poProduct->received_qty }}
                                    </div>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <strong class="fw-700" style="margin-right: 5px">FBA Qty:</strong>{{ $poProduct->product->qty + $poProduct->product->afn_reserved_quantity + $poProduct->product->afn_unsellable_quantity + $poProduct->product->afn_inbound_working_quantity + $poProduct->product->afn_inbound_shipped_quantity + $poProduct->product->afn_inbound_receiving_quantity }}
                                    </div>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <strong class="fw-700" style="margin-right: 5px">Sellable Units:</strong>{{ $poProduct->product->sellable_units }}
                                    </div>
                                </td>
                                <td>
                                    @include('fba_products.suggested_shipment_qty',[
                                    'suggestedShipmentQty' => $poProduct->suggested_shipment_qty,
                                    'ros_30' => $poProduct->product->salesVelocity->ros_30,
                                    'setting' => $setting,
                                    'totalFBAQty' => $poProduct->total_fba_qty
                                    ])
                                </td>
                                <td>
                                    <span class="case_pack">{{ $poProduct->product->case_pack }}</span>
                                </td>
                                <td>
                                    <span class="no_of_case">0</span>
                                </td>
                                <td>
                                    <span class="total_qty">0</span>
                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-sm-10 justify-content-start align-items-center">
                                            {{ Form::number('sellable_unit['.$poProduct->product_id.']', !empty(old('sellable_unit')) ? old('sellable_unit') : null, ['id' => 'sellable_unit_'.$poProduct->product_id, "class" => "form-control validate sellable_unit","placeholder"=>"Send Sellable units", "Required"=>true]) }}
                                            @error('sellable_unit[{{ $poProduct->product_id }}]')
                                            <x-forms.error :message="$message" />
                                            @enderror
                                        </div>
                                        <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">&emsp13;
                                            <span title="Pack of: {{ $poProduct->product->pack_of }}"><i class="fa-solid fa-circle-info"></i></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="min-w-150px">
                                    <textarea name="product_note[{{ $poProduct->product->id }}]" id="product_note" class="form-control" cols="100" rows="2" maxlength="250">{{ $poProduct->product->product_note }}</textarea>
                                </td>
                            </tr>
                            @php
                            $row++;
                            @endphp
                        @empty
                        <tr>
                            <td colspan="10">No Products found</td>
                        </tr>
                        @endforelse
                    </tbody> --}}

                    <tbody id="product_data">
                        @if ($poProducts->count() > 0 || count($amazonProducts) > 0)
                            @php
                            $row = 1;
                            @endphp
                            @if($poProducts->count() > 0)
                                @foreach ($poProducts as $poProduct)
                                    <tr>
                                        {{ Form::hidden('product_id',$poProduct->product->id,['class' => 'product_id']) }}
                                        {{ Form::hidden('title['.$poProduct->product->id.']',$poProduct->product->title) }}
                                        {{ Form::hidden('sku['.$poProduct->product->id.']',$poProduct->product->sku) }}
                                        {{ Form::hidden('asin['.$poProduct->product->id.']',$poProduct->product->asin) }}
                                        {{ Form::hidden('pack_of['.$poProduct->product->id.']',$poProduct->product->pack_of,['class' => 'pack_of']) }}
                                        {{ Form::hidden('wh_qty['.$poProduct->product->id.']',$poProduct->product->wh_qty) }}
                                        {{ Form::hidden('current_sellable_units['.$poProduct->product->id.']', $poProduct->product->sellable_units) }}
                                        <td>{{ $row }}</td>
                                        <td>
                                            @if($poProduct->product)
                                            <img src="{{ $poProduct->product->main_image }}" alt="">
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="fw-800">Title:</strong> {{ $poProduct->product->title }}
                                            <div class="d-flex justify-content-start align-items-center">
                                                @include('copy-btn',[
                                                'value' => $poProduct->product->sku,
                                                'title' => 'SKU',
                                                ])
                                            </div>
                                            <div class="d-flex justify-content-start align-items-center">
                                                @include('copy-btn',[
                                                'value' => $poProduct->product->asin,
                                                'title' => 'ASIN',
                                                'link' => "https://www.amazon.ca/dp/".$poProduct->product->asin
                                                ])
                                            </div>
                                            <div class="grid column-gap-5 d-flex mt-2">
                                                @if ($poProduct->product->store)
                                                <div class="mt-2 me-2">
                                                    @include('badge', [
                                                    'badgeArr' => [
                                                    'title' => $poProduct->product->store->store_name,
                                                    'bgColor' => ($poProduct->product->store->id == 1) ? 'label-light-info' : 'label-light-success',
                                                    ],
                                                    ])
                                                </div>
                                                @endif

                                                @if ($poProduct->product->is_hazmat == 1)
                                                <div class="mt-2 me-2">
                                                    @include('badge',['badgeArr' => [
                                                    'title' => 'Hazmat',
                                                    'bgColor' => 'label-light-danger',
                                                    ]])
                                                </div>
                                                @endif

                                                @if ($poProduct->product->is_oversize == 1)
                                                <div class="mt-2 me-2">
                                                    @include('badge', ['badgeArr' => [
                                                    'title' => 'Oversized',
                                                    'bgColor' => 'label-light-primary',
                                                    ]])
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">WH Qty:</strong>{{ $poProduct->product->wh_qty }}
                                            </div>

                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Reserved Qty:</strong>{{ $poProduct->product->reserved_qty }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Received Qty:</strong>{{ $poProduct->received_qty }}
                                            </div>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">FBA Qty:</strong>{{ $poProduct->product->qty + $poProduct->product->afn_reserved_quantity + $poProduct->product->afn_unsellable_quantity + $poProduct->product->afn_inbound_working_quantity + $poProduct->product->afn_inbound_shipped_quantity + $poProduct->product->afn_inbound_receiving_quantity }}
                                            </div>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Sellable Units:</strong>{{ $poProduct->product->sellable_units }}
                                            </div>
                                        </td>
                                        <td>
                                            @include('fba_products.suggested_shipment_qty',[
                                            'suggestedShipmentQty' => $poProduct->suggested_shipment_qty,
                                            'ros_30' => $poProduct->product->salesVelocity->ros_30,
                                            'setting' => $setting,
                                            'totalFBAQty' => $poProduct->total_fba_qty
                                            ])
                                        </td>
                                        <td>
                                            <span class="case_pack">{{ $poProduct->product->case_pack }}</span>
                                        </td>
                                        <td>
                                            <span class="no_of_case">0</span>
                                        </td>
                                        <td>
                                            <span class="total_qty">0</span>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <div class="col-sm-10 justify-content-start align-items-center">
                                                    {{ Form::number('sellable_unit['.$poProduct->product_id.']', !empty(old('sellable_unit')) ? old('sellable_unit') : null, ['id' => 'sellable_unit_'.$poProduct->product_id, "class" => "form-control validate sellable_unit","placeholder"=>"Send Sellable units", "Required"=>true]) }}
                                                    @error('sellable_unit[{{ $poProduct->product_id }}]')
                                                    <x-forms.error :message="$message" />
                                                    @enderror
                                                </div>
                                                <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">&emsp13;
                                                    <span title="Pack of: {{ $poProduct->product->pack_of }}"><i class="fa-solid fa-circle-info"></i></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="min-w-150px">
                                            <textarea name="product_note[{{ $poProduct->product->id }}]" id="product_note" class="form-control" cols="100" rows="2" maxlength="250">{{ $poProduct->product->product_note }}</textarea>
                                        </td>
                                        <td>
                                            <x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" 
                                            data-url="{{ route('delete-plan-product', ['product_id' => $poProduct->product->id, 'po_id' => request()->segment(2)]) }}" 
                                            onclick="planProductDelete('{{ $poProduct->product->id }}', $(this))">
                                                <x-actions.icon class="fas fa-trash mt-4" style="color:red"/>
                                            </x-actions.button>
                                        </td>
                                    </tr>
                                    @php
                                    $row++;
                                    @endphp
                                @endforeach
                            @endif

                            @if(count($amazonProducts) > 0)
                                @foreach ($amazonProducts as $product)
                                    <tr>
                                        {{ Form::hidden('product_id',$product->id,['class' => 'product_id']) }}
                                        {{ Form::hidden('title['.$product->id.']',$product->title) }}
                                        {{ Form::hidden('sku['.$product->id.']',$product->sku) }}
                                        {{ Form::hidden('asin['.$product->id.']',$product->asin) }}
                                        {{ Form::hidden('pack_of['.$product->id.']',$product->pack_of,['class' => 'pack_of']) }}
                                        {{ Form::hidden('wh_qty['.$product->id.']',$product->wh_qty) }}
                                        {{ Form::hidden('current_sellable_units['.$product->id.']', $product->sellable_units) }}
                                        <td>{{ $row }}</td>
                                        <td>
                                            @if($product)
                                            <img src="{{ $product->main_image }}" alt="">
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="fw-800">Title:</strong> {{ $product->title }}
                                            <div class="d-flex justify-content-start align-items-center">
                                                @include('copy-btn',[
                                                'value' => $product->sku,
                                                'title' => 'SKU',
                                                ])
                                            </div>
                                            <div class="d-flex justify-content-start align-items-center">
                                                @include('copy-btn',[
                                                'value' => $product->asin,
                                                'title' => 'ASIN',
                                                'link' => "https://www.amazon.ca/dp/".$product->asin
                                                ])
                                            </div>
                                            <div class="grid column-gap-5 d-flex mt-2">
                                                @if ($product->store)
                                                <div class="mt-2 me-2">
                                                    @include('badge', [
                                                    'badgeArr' => [
                                                    'title' => $product->store->store_name,
                                                    'bgColor' => ($product->store->id == 1) ? 'label-light-info' : 'label-light-success',
                                                    ],
                                                    ])
                                                </div>
                                                @endif

                                                @if ($product->is_hazmat == 1)
                                                <div class="mt-2 me-2">
                                                    @include('badge',['badgeArr' => [
                                                    'title' => 'Hazmat',
                                                    'bgColor' => 'label-light-danger',
                                                    ]])
                                                    <input type="hidden" id="hazmat_item" value="1">
                                                </div>
                                                @endif

                                                @if ($product->is_oversize == 1)
                                                <div class="mt-2 me-2">
                                                    @include('badge', ['badgeArr' => [
                                                    'title' => 'Oversized',
                                                    'bgColor' => 'label-light-primary',
                                                    ]])
                                                    <input type="hidden" id="oversize_item" value="1">
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">WH Qty:</strong>{{ $product->wh_qty }}
                                            </div>

                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Reserved Qty:</strong>{{ $product->reserved_qty }}
                                            </div>
                                        </td>
                                        <td>
                                            {{-- <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Received Qty:</strong>{{ $received_qty }}
                                            </div> --}}
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">FBA Qty:</strong>{{ $product->qty + $product->afn_reserved_quantity + $product->afn_unsellable_quantity + $product->afn_inbound_working_quantity + $product->afn_inbound_shipped_quantity + $product->afn_inbound_receiving_quantity }}
                                            </div>
                                            <div class="d-flex justify-content-start align-items-center mt-2">
                                                <strong class="fw-700" style="margin-right: 5px">Sellable Units:</strong>{{ $product->sellable_units }}
                                            </div>
                                        </td>
                                        <td>
                                            @include('fba_products.suggested_shipment_qty',[
                                            'suggestedShipmentQty' => $product->suggested_shipment_qty,
                                            'ros_30' => $product->salesVelocity->ros_30,
                                            'setting' => $setting,
                                            'totalFBAQty' => $product->total_fba_qty
                                            ])
                                        </td>
                                        <td>
                                            <span class="case_pack">{{ $product->case_pack }}</span>
                                        </td>
                                        <td>
                                            <span class="no_of_case">0</span>
                                        </td>
                                        <td>
                                            <span class="total_qty">0</span>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <div class="col-sm-10 justify-content-start align-items-center">
                                                    {{ Form::number('sellable_unit['.$product->id.']', !empty(old('sellable_unit')) ? old('sellable_unit') : null, ['id' => 'sellable_unit_'.$product->id, "class" => "form-control validate sellable_unit","placeholder"=>"Send Sellable units", "Required"=>true]) }}
                                                    @error('sellable_unit[{{ $product->id }}]')
                                                    <x-forms.error :message="$message" />
                                                    @enderror
                                                </div>
                                                <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">&emsp13;
                                                    <span title="Pack of: {{ $product->pack_of }}"><i class="fa-solid fa-circle-info"></i></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="min-w-150px">
                                            <textarea name="product_note[{{ $product->id }}]" id="product_note" class="form-control" cols="100" rows="2" maxlength="250">{{ $product->product_note }}</textarea>
                                        </td>
                                        <td>
                                            <x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" 
                                            data-url="{{ route('delete-plan-product', ['product_id' => $product->id, 'po_id' => request()->segment(2)]) }}" 
                                            onclick="planProductDelete('{{ $product->id }}', $(this))">
                                                <x-actions.icon class="fas fa-trash mt-4" style="color:red"/>
                                            </x-actions.button>
                                        </td>
                                    </tr>
                                    @php
                                    $row++;
                                    @endphp
                                @endforeach
                            @endif
                        @else
                            <tr>
                                <td colspan="10">No Products found</td>
                            </tr>
                        @endif
                    </tbody>

                </table>
            </div>

            <x-forms.form-footer>
                <x-forms.button :link="url()->previous()" />
                <x-forms.button title="Create Plan" name="Create Plan" id="create_plan" />
            </x-forms.form-footer>
        </div>
    </div>

    {{ Form::close()}}
</x-forms.parent>

<x-modal id="add_fba_product_form" dialog="modal-xl">
    {{ Form::open(['route' => ['insert-selected-fba-product', ['po_id' => request()->segment(2)]], 'name' => 'insert_selected_fba_product', 'id' => 'insert_selected_fba_product', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    <x-modal.header />

    <x-modal.body style="max-height: 430px; overflow-y: auto;">

        <x-search-box class="col col-xl-5 col-xl-2 mb-5" input_id="product_search_data"
            button_id="product_search_button" />

        <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative"
            id="get_fba_product_list">
        </table>
    </x-modal.body>

    <x-modal.footer name="Add to Plan" id="add_fba_product_submit" type="button" />
    {{ Form::close() }}

</x-modal>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\StoreShipmentPlanRequest', '#store_shipment_plans'); !!}
<script>
    const url = "{{ route('shipment-plans.index') }}"
</script>
<script src="{{ asset('js/shipment_plans/form.js')}}" type="text/javascript"></script>

<script>
    const poId = "{{ request()->segment(2) }}"
    $(document).ready(function() {
        const productCount = "{{ $poProducts->count() }}";

        if (productCount == 0) {
            $("#create_plan").attr('disabled', 'disabled');
        } else {
            $("#create_plan").removeAttr('disabled');
        }
    });

    $("#store_id").change(function() {
        const storeId = $(this).val();
        const storeProductsUrl = "{{ route('storewise-products') }}";

        if (storeId) {
            $.ajax({
                url: storeProductsUrl,
                type: 'POST',
                data: {
                    storeId: storeId,
                    poId: poId
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    show_loader();
                },
                complete: function() {
                    hide_loader();
                },
                success: function(data) {
                    hide_loader();
                    $("#product_data").html(data);
                    $("#total_hazmat_product").text($(".total_hazmat_product").val());
                    $("#total_oversize_product").text($(".total_oversize_product").val());
                    $("#total_sku").text($(".total_sku").val());

                    const productCount = $(".product_count").val();
                    if (productCount == 0) {
                        $("#create_plan").attr('disabled', 'disabled');
                    } else {
                        $("#create_plan").removeAttr('disabled');
                    }
                },
                error: function(xhr, err) {
                    console.log(err);
                }
            })
        }
    })

    $(document).on('click', '.moredata-link', function() {
        $(this).closest('.multidata-td').toggleClass('d-filter-show-hidebox');

        var toggleClass = $(this).closest('.multidata-td');

        $(document).mouseup(function(event) {

            var hideBox = $(".moredata-link");
            if (!hideBox.is(event.target) && hideBox.has(event.target).length === 0) {
                toggleClass.removeClass('d-filter-show-hidebox');
            }
        });
    });

    $(document).on('mouseenter', '.suggested_shipment_qty', function() {
        // Get the new tooltip content based on the header's text or any other criteria
        const newTooltipContent = '((Target qty on hands days + Local Lead Time) * 30 days ROS) - Current Amazon inventory';
        // Update the title attribute with the new content
        $(this).attr('title', newTooltipContent);
    })
    const createShipment = true;
</script>
<script src="{{ asset('js/shipment_plans/edit-plan.js')}}" type="text/javascript"></script>
@stop