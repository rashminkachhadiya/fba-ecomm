@if (isset($allProducts) && $shipmentProducts->count() > 0)
    @foreach ($shipmentProducts as $shipmentProduct)
        <tr>
            <td>
                <input type="checkbox" class="product-checkbox" data-sku="{{ $shipmentProduct->sku }}" data-shipment_item_id="{{ $shipmentProduct->id }}">
            </td>
            <td>
                <img src="{{ $shipmentProduct->main_image }}" alt="">
            </td>
            <td style="width: 250px">{{ $shipmentProduct->title }}</td>
            <td style="width: 200px">
                @include('copy-btn', [
                    'value' => $shipmentProduct->sku,
                    'title' => 'SKU',
                ]) 
                @include('copy-btn', [
                    'value' => $shipmentProduct->asin,
                    'title' => 'ASIN',
                    'link' => "https://www.amazon.ca/dp/$shipmentProduct->asin",
                ])
            </td>
            <td>{{ $shipmentProduct->qty }}</td>
            <td>{{ ($shipmentProduct->done_qty) ?? 0 }}</td>
            <td>{{ ($shipmentProduct->product_note) ?? '-' }}</td>
        </tr>
    @endforeach
@else
    @if (isset($multiSkusData) && $multiSkusData->count() > 0)
        @foreach ($multiSkusData as $multiSku)
            <tr>
                <input type="hidden" class="fba_shipment_item_id" value="{{ $multiSku->fba_shipment_item_id }}">
                <input type="hidden" class="total_qty" value="{{ $multiSku->qty }}">
                <td>
                    <input type="number" step="1" min="0" class="form-control sellable_units" data-unit="{{ $multiSku->qty - $multiSku->done_qty }}" id="{{ $multiSku->id }}" name="sellable_units[]" style="width:100px" value="{{ $multiSku->sellable_units }}" maxlength="3">
                    <span class="sellable_units_error" style="color: #F1416C;"></span>
                </td>
                <td>
                    {{ Form::text('expiry_date[]', null, ['id' => 'expiry_date', 'class' => 'form-control expiry_date']) }}
                </td>
                <td class="main_image">
                    <img src="{{ $multiSku->main_image }}" alt="">
                </td>
                <td style="width: 200px">{{ $multiSku->title }}</td>
                <td style="width: 170px">
                    <span class="sku">
                    @include('copy-btn', [
                        'value' => $multiSku->sku,
                        'title' => 'SKU',
                    ])
                    </span>
                    <span class="fnsku">
                    @include('copy-btn', [
                        'value' => $multiSku->fnsku,
                        'title' => 'FNSKU',
                    ])
                    </span>
                </td>
                <td class="remaining_qty">{{ $multiSku->qty - $multiSku->done_qty }}</td>
                <td>{{ ($multiSku->prep_note == '') ? '-' : $multiSku->prep_note }}</td>
                <td>
                    <i class="fa-regular fa-trash-can me-4 fs-6 text-danger" style="cursor: pointer" aria-hidden="true" onclick="removeSku('{{ $multiSku->id }}')"></i>
                </td>
            </tr>
        @endforeach
    @endif
@endif