@php
    $row = 1;
    $totalHazmatProduct = 0;
    $totalOversizedProduct = 0;
    $totalSkus = 0;
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

<input type="hidden" class="total_hazmat_product" value="{{ $totalHazmatProduct }}" />
<input type="hidden" class="total_oversize_product" value="{{ $totalOversizedProduct }}" />
<input type="hidden" class="total_sku" value="{{ $totalSkus }}" />
<input type="hidden" class="product_count" value="{{ $poProducts->count() }}" />

@forelse ($poProducts as $poProduct)
    <tr>
        {{ Form::hidden('product_id', $poProduct->product->id, ['class' => 'product_id']) }}
        {{ Form::hidden('title[' . $poProduct->product->id . ']', $poProduct->product->title) }}
        {{ Form::hidden('sku[' . $poProduct->product->id . ']', $poProduct->product->sku) }}
        {{ Form::hidden('asin[' . $poProduct->product->id . ']', $poProduct->product->asin) }}
        {{ Form::hidden('pack_of[' . $poProduct->product->id . ']', $poProduct->product->pack_of, ['class' => 'pack_of']) }}
        {{ Form::hidden('wh_qty[' . $poProduct->product->id . ']', $poProduct->product->wh_qty) }}
        <td>{{ $row }}</td>
        <td>
            @if ($poProduct->product)
                <img src="{{ $poProduct->product->main_image }}" alt="">
            @endif
        </td>
        <td>
            <strong class="fw-800">Title:</strong> {{ $poProduct->product->title }}
            <div class="d-flex justify-content-start align-items-center">
                @include('copy-btn', [
                    'value' => $poProduct->product->sku,
                    'title' => 'SKU',
                ])
            </div>
            <div class="d-flex justify-content-start align-items-center">
                @include('copy-btn', [
                    'value' => $poProduct->product->asin,
                    'title' => 'ASIN',
                    'link' => 'https://www.amazon.ca/dp/' . $poProduct->product->asin,
                ])
            </div>
            <div class="grid column-gap-5">
                @if ($poProduct->product->store)
                    <div style="margin-top: 10px">
                        @include('badge', [
                            'badgeArr' => [
                                'title' => $poProduct->product->store->store_name,
                                'bgColor' => ($poProduct->product->store->id == 1) ? 'label-light-info' : 'label-light-success',
                            ],
                        ])
                    </div>
                @endif

                @if ($poProduct->product->is_hazmat == 1)
                    <div style="margin-top: 10px">
                        @include('badge',['badgeArr' => [
                            'title' => 'Hazmat',
                            'bgColor' => 'label-light-danger',
                        ]])
                    </div>
                @endif

                @if ($poProduct->product->is_oversize == 1)
                    <div style="margin-top: 10px">
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
                <strong class="fw-700" style="margin-right: 5px">Reserved
                    Qty:</strong>{{ $poProduct->product->reserved_qty }}
            </div>

            {{-- <span class="wh_qty">{{ $poProduct->product->wh_qty }}</span> --}}
        </td>
        <td>
            <div class="d-flex justify-content-start align-items-center mt-2">
                <strong class="fw-700" style="margin-right: 5px">Received Qty:</strong>{{ $poProduct->received_qty }}
            </div>
            <div class="d-flex justify-content-start align-items-center mt-2">
                <strong class="fw-700" style="margin-right: 5px">FBA
                    Qty:</strong>{{ $poProduct->product->qty + $poProduct->product->afn_reserved_quantity + $poProduct->product->afn_unsellable_quantity + $poProduct->product->afn_inbound_working_quantity + $poProduct->product->afn_inbound_shipped_quantity + $poProduct->product->afn_inbound_receiving_quantity }}
            </div>
            <div class="d-flex justify-content-start align-items-center mt-2">
                <strong class="fw-700" style="margin-right: 5px">Sellable
                    Units:</strong>{{ $poProduct->product->sellable_units }}
            </div>
        </td>
        {{-- <td>{{ $poProduct->product->reserved_qty }}</td>
                                <td>
                                    {{ $poProduct->product->qty + $poProduct->product->afn_reserved_quantity + $poProduct->product->afn_unsellable_quantity + $poProduct->product->afn_inbound_working_quantity + $poProduct->product->afn_inbound_shipped_quantity + $poProduct->product->afn_inbound_receiving_quantity }}
                                </td> --}}
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
                    {{ Form::number('sellable_unit[' . $poProduct->product_id . ']', !empty(old('sellable_unit')) ? old('sellable_unit') : null, ['id' => 'sellable_unit_' . $poProduct->product_id, 'class' => 'form-control validate sellable_unit', 'placeholder' => 'Send Sellable units', 'Required' => true]) }}
                    @error('sellable_unit[{{ $poProduct->product_id }}]')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
                <div class="col-sm-2" style="margin-top:-6px; margin-left:-8px">&emsp13;
                    <span title="Pack of: {{ $poProduct->product->pack_of }}"><i
                            class="fa-solid fa-circle-info"></i></span>
                </div>
            </div>
        </td>
        <td class="min-w-150px">
            <textarea name="product_note[{{ $poProduct->product->id }}]" id="product_note" class="form-control" cols="100"
                rows="2" maxlength="250" onkeyup="updateProductNote(event)" product-id="{{ $poProduct->product->id }}"
                data-url="{{ route('update-product-note') }}">{{ $poProduct->product->product_note }}</textarea>
        </td>
    </tr>
    @php
        $row++;
    @endphp
@empty
    <tr>
        <td colspan="10" class="text-center">No Products found</td>
    </tr>
@endforelse
