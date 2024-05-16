@extends('layouts.app')

@section('title', 'FBA Shipping Plan')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('fba-products.index') }}" class="link-class"><i class="zmdi zmdi-home" aria-hidden="true"></i>FBA Products List</a></li>
<li class="breadcrumb-item text-primary">Create FBA Shipping Plan</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['shipment-plans.store'], 'name' => 'store_shipment_plans', 'id' => 'store_shipment_plans', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    <div class="row">
        <div class="tab-content border rounded p-3 border-secondary shadow-lg">
            <div class="row">
                <x-forms style="flex-basis: 100%">
                    <x-forms.form-div class="col-sm-3" margin="mb-5">
                        <x-forms.label title="Store" required="required" />
                        <x-forms.select id="store_id" name="store_id" disabled>
                            @foreach ($stores as $key => $store)
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
                            @foreach ($wareHouses as $wareHouse)
                            <x-forms.select-options :value="$wareHouse->id" :title="$wareHouse->name" />
                            @endforeach
                        </x-forms.select>
                        @error('warehouse_id')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Shipping Plan Name" required="required" />
                        {{ Form::text('plan_name', !empty(old('plan_name')) ? old('plan_name') : '', ['id' => 'plan_name', 'class' => 'form-control validate', 'placeholder' => 'Shipping Plan Name', 'Required' => true]) }}
                        @error('plan_name')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Prep preference" required="required" />
                        <x-forms.select id="prep_preference" name="prep_preference">
                            @foreach ($prepPreferences as $key => $prepPreference)
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
                            @foreach ($marketplaces as $key => $marketplace)
                            <x-forms.select-options :value="$key" :title="$marketplace" />
                            @endforeach
                        </x-forms.select>
                        @error('destination_country')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    <x-forms.form-div class="col-sm-3" margin="mb-3">
                        <x-forms.label title="Box Content Info" required="required" />
                        <x-forms.select id="box_content" name="box_content">
                            @foreach ($boxContents as $key => $boxContent)
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
                            @foreach ($packingDetails as $key => $packingDetail)
                            <option value="{{ $key }}">{{ $packingDetail }}</option>
                            @endforeach
                        </x-forms.select>
                        @error('packing_details')
                        <x-forms.error :message="$message" />
                        @enderror
                    </x-forms.form-div>

                    @if ($fbaProductsData->count() > 0)
                    @php
                    $totalSkus = $fbaProductsData->count(function($item){
                    if($item->id)
                    {
                    return true;
                    }
                    });
                    @endphp
                    @endif

                    <div class="col-sm-3 mt-8" style="text-align: right;">
                        <span>Total Hazmat : {{ $totalOversize }} </span><span>{{'(Qty:'}}</span><span id="total_hazmat_qty">{{ 0 }}</span><span>{{')'}}</span><br>
                        <span>Total Oversized : {{ $totalOversize }} </span><span>{{'(Qty:'}}</span><span id="total_oversize_qty">{{ 0 }}</span><span>{{')'}}</span><br>
                        <span id="total_skus">Total Skus : {{ $totalSkus }}</span>
                    </div>
                </x-forms>
            </div>
        </div>

        <div class="mt-10">
            <div class="input-group w-25 mb-7 flex-nowrap input-group-sm">
                {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? base64_decode(Request::get('search')) : '', ['id' => $input_id ?? 'search_data', 'autocomplete' => 'off', 'class' => 'form-control px-5', 'placeholder' => 'Search']) }}
                <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button" id="{{ $button_id ?? 'search_button' }}">
                    <i class="fa-regular fa-magnifying-glass text-primary"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="fba_selected_product">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th class="min-w-300px">Product Details</th>
                            <th class="min-w-150px">Warehouse Qty</th>
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
                        @forelse ($fbaProductsData as $fbaProducts)
                        <tr fba_product_id="{{ $fbaProducts->id }}">
                            {{ Form::hidden('product_id',$fbaProducts->id,['class' => 'product_id']) }}
                            {{ Form::hidden('title[' . $fbaProducts->id . ']', $fbaProducts->title) }}
                            {{ Form::hidden('sku[' . $fbaProducts->id . ']', $fbaProducts->sku) }}
                            {{ Form::hidden('asin[' . $fbaProducts->id . ']', $fbaProducts->asin) }}
                            {{ Form::hidden('pack_of[' . $fbaProducts->id . ']', $fbaProducts->pack_of, ['class' => 'pack_of']) }}
                            {{ Form::hidden('wh_qty[' . $fbaProducts->id . ']', $fbaProducts->wh_qty) }}
                            {{ Form::hidden('current_sellable_units[' . $fbaProducts->id . ']', $fbaProducts->sellable_units) }}
                            <td>
                                @if ($fbaProducts)
                                <img src="{{ $fbaProducts->main_image }}" alt="">
                                @endif
                            </td>
                            <td>
                                <strong class="fw-800">Title:</strong> {{ $fbaProducts->title }}
                                <div class="d-flex justify-content-start align-items-center min-w-200px">
                                    @include('copy-btn', [
                                    'value' => $fbaProducts->sku,
                                    'title' => 'SKU',
                                    ])
                                </div>
                                <div class="d-flex justify-content-start align-items-center">
                                    @include('copy-btn', [
                                    'value' => $fbaProducts->asin,
                                    'title' => 'ASIN',
                                    'link' => 'https://www.amazon.ca/dp/' . $fbaProducts->asin,
                                    ])
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">
                                    <div class="grid gap-0 column-gap-3">
                                        @if ($fbaProducts->is_hazmat == 1)
                                        @include('badge', [
                                        'badgeArr' => [
                                        'title' => 'Hazmat',
                                        'bgColor' => 'label-light-danger',
                                        ],
                                        ])
                                        <input type="hidden" id="hazmat_item" value="1">
                                        @endif &emsp13;

                                        @if ($fbaProducts->is_oversize == 1)
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
                                    <strong class="fw-700" style="margin-right: 5px">Available WH Qty:
                                    </strong>{{ $fbaProducts->wh_qty }}
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">

                                    <strong class="fw-700" style="margin-right: 5px">Sellable Units:
                                    </strong>{{ $fbaProducts->sellable_units }}
                                </div>
                                <div class="d-flex justify-content-start align-items-center mt-2">

                                    <strong class="fw-700" style="margin-right: 5px">Reserved Qty:
                                    </strong>{{ $fbaProducts->reserved_qty }}
                                </div>
                            </td>
                            <td>
                                @include('inventory-detail', ['value' => $fbaProducts])
                            </td>
                            <td>
                                @include('fba_products.suggested_shipment_qty',[
                                'suggestedShipmentQty' => $fbaProducts->suggested_shipment_qty,
                                'ros_30' => $fbaProducts->salesVelocity->ros_30,
                                'setting' => $setting,
                                'totalFBAQty' => $fbaProducts->total_fba_qty
                                ])
                            </td>
                            <td>
                                <span class="case_pack">{{ $fbaProducts->case_pack }}</span>
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
                                        {{ Form::number(
                                            'sellable_unit[' . $fbaProducts->id . ']',
                                            !empty(old('sellable_unit')) ? old('sellable_unit') : null,
                                            [
                                                'id' => 'sellable_unit_' . $fbaProducts->id,
                                                'class' => 'form-control sellable_unit',
                                                'min' => '1',
                                                'step' => '1',
                                            ],
                                        ) }}

                                    </div>
                                    <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">
                                        &emsp13;<span title="Pack of: {{ $fbaProducts->pack_of }}"><i class="fa-solid fa-circle-info"></i></span>
                                    </div>
                                </div>

                            </td>
                            <td class="min-w-150px">
                                <textarea name="product_note[{{ $fbaProducts->id }}]" id="product_note" class="form-control" cols="100" rows="2" maxlength="250" product-id="{{ $fbaProducts->id }}">{{ $fbaProducts->product_note }}</textarea>
                            </td>
                            <td>
                                <x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('fba-products.create') }}" onclick="fbaShipmentPlanProductDelete('{{ $fbaProducts->id }}', $(this))">
                                    <x-actions.icon class="fas fa-trash mt-4" style="color:red" />
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
            </div>

            <x-forms.form-footer>
                <x-forms.button :link="route('fba-products.index')" />
                <x-forms.button title="Create Plan" name="Create Plan" />
            </x-forms.form-footer>

        </div>
    </div>
    {{ Form::close() }}
</x-forms.parent>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\StoreShipmentPlanRequest', '#store_shipment_plans') !!}
<script>
    const url = "{{ route('shipment-plans.index') }}"
</script>
<script src="{{ asset('js/fba_products/form.js') }}" type="text/javascript"></script>
{{-- <script src="{{ asset('js/shipment_plans/form.js') }}" type="text/javascript"></script> --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"></script>

<script>
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
</script>
@stop