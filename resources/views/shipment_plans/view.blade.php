@extends('layouts.app')

@section('title', 'FBA Shipping Plan')
@section('breadcrumb')
    <li class="breadcrumb-item text-primary link-class"><a href="{{ route('shipment-plans.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i>Shipping Plan List</a></li>
    <li class="breadcrumb-item text-primary">View Shipping Plan</li>
@endsection

@section('content')

<x-forms.parent>

    <div class="row">
        <div class="tab-content border rounded p-3 border-secondary shadow-lg">
            
            <div class="row">
                <x-forms>
                    <x-forms.form-div class="col-sm-3" margin="mb-5">
                        <x-forms.label title="Store" />
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
                        <x-forms.label title="Shipment From" />
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
                        <x-forms.label title="Shipping Plan Name"/>
                        {{ Form::text('plan_name', !empty($shipmentPlan->plan_name) ? $shipmentPlan->plan_name : old('plan_name'), ['id' => 'plan_name', "class" => "form-control validate",'placeholder' => 'Shipping Plan Name', 'disabled' => true]) }}
                        @error('plan_name')
                            <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Prep preference" />
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
                        <x-forms.label title="Marketplace" />
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
                        <x-forms.label title="Box Content Info"/>
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
                        <x-forms.label title="Packing Details" />
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
                        <span id="tota_hazmat_qty">Total Hazmat : {{ $totalHazmatProduct }} {{ "(Qty: $totalHazmatQty)" }}</span><br>
                        <span id="total_oversize_qty">Total Oversized : {{ $totalOversizedProduct }} {{ "(Qty: $totalOversizedQty)" }}</span><br>
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
                </div>
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative"
                    id="view_fba_product">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Details</th>
                            <th>Warehouse Qty</th>
                            <th>FBA Qty</th>
                            <th>Per Case Qty</th>
                            <th>No Of Case</th>
                            <th>Total qty</th>
                            <th>Sellable units</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipmentPlanDetails as $shipmentPlanval)
                            <tr fba_product_id="{{ $shipmentPlanval->product->id }}">
                               
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
                                            @endif &emsp13;
    
                                            @if ($shipmentPlanval->product->is_oversize == 1)
                                                @include('badge', [
                                                    'badgeArr' => [
                                                        'title' => 'Oversized',
                                                        'bgColor' => 'label-light-primary',
                                                    ],
                                                ])
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <span>
                                            <strong class="fw-700">WH Qty:
                                            </strong>
                                            <span>{{ $shipmentPlanval->product->wh_qty }}</span>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
                                        <span>
                                            <strong class="fw-700">Reserved Qty:
                                            </strong>
                                            <span>{{ $shipmentPlanval->product->reserved_qty }}</span>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-start align-items-center mt-2">
    
                                        <strong class="fw-700" style="margin-right: 5px">Sellable Units:
                                        </strong>{{ $shipmentPlanval->product->sellable_units }}
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
                                    <span class="total_qty">{{$totalQty}}</span>
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
                                                'disabled' => true
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
                                        product-id="{{ $shipmentPlanval->product->id }}" disabled>{{ $shipmentPlanval->product->product_note }}</textarea>
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
                    <x-forms.button :link="route('shipment-plans.index')" name="Back"/>
                </x-forms.form-footer>
            </div>

    </div>
</x-forms.parent>

@endsection

@section('page-script')
<script>

    $(document).ready(function() {
        $("#search_data").on("input", function() {
            var searchText = $(this).val().toLowerCase();
            $("#view_fba_product tbody tr").each(function() {
                var listItemText = $(this).text().toLowerCase();
                if (listItemText.indexOf(searchText) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });
    });

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

</script>
@endsection

