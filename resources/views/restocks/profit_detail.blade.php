@php $sellerId = "A3DWYIK6Y9EEQB"; @endphp
@if($value->buybox_seller_id != null && !($value->buybox_seller_id == $sellerId))
@php $url = "https://www.amazon.ca/sp?seller=".$value->buybox_seller_id; @endphp
@else
@php $url = 'javascript:void(0)'; @endphp
@endif

<div class="multidata-td w-100">
    <div class="row mb-2">
        <div class="">
            <div class="row selling_price_profit">
                <div class="col"><strong>Selling Price: </strong></div>
                <a href="javascript:void(0)" style="color: {{ ($value->selling_price_profit > 0) ? 'green' : 'red' }}">
                    <div class="col-auto moredata-link">{{ config('constants.currency_symbol').$value->selling_price_profit." (".$value->selling_price_margin."%)" }}
                    </div>
                </a>
            </div>
        </div>
        <div class="">
            <div class="row buybox_price_profit">
                <div class="col"><strong>Buybox Price: </strong></div>
                <a href="javascript:void(0)" style="color: {{ ($value->buybox_price_profit > 0) ? 'green' : 'red' }}">
                    <div class="col-auto moredata-link">{{ config('constants.currency_symbol').$value->buybox_price_profit." (".$value->buybox_price_margin."%)" }}
                    </div>

                </a>
            </div>
        </div>
    </div>
    <div class="d-show-hide">
        <div class="border m-2 overflow-hidden">
            {{-- <div class="row">
                <div class="col"><strong>COG: </strong></div>
                <div class="col-auto text-end">
                    {{ $value->unit_price }}
        </div>
    </div> --}}
    <div class="row selling_price">
        <div class="col"><strong>Selling Price: </strong></div>
        <div class="col-auto text-end">
            {{ config('constants.currency_symbol').$value->selling_price }}
        </div>
    </div>
    <div class="row buybox_price">
        <div class="col"><strong>Buybox Price: </strong></div>
        <div class="col-auto">
            <div class="text-end">
            {{ config('constants.currency_symbol').$value->buybox_price }}
            @if($value->buybox_seller_id != null)
            <a href="{{$url}}" class="text-end" @if($value->buybox_seller_id != null && !($value->buybox_seller_id == $sellerId)) target="_blank" @endif ><strong class="text-end" style="margin-left: 52px;">@if($value->buybox_seller_id == $sellerId) Amazon @elseif($value->is_buybox_fba == 1) FBA @else FBM @endif</strong></a>
            @endif
        </div>
    </div>
    </div>
    <div class="row referral_fee">
        <div class="col"><strong>Referral Fee: </strong></div>
        <div class="col-auto text-end">{{ config('constants.currency_symbol').$value->referral_fees }}
        </div>
    </div>
    <div class="row buybox_referral_fees" >
        <div class="col "><strong>Buybox Referral Fee: </strong></div>
        <div class="col-auto text-end">
            {{ config('constants.currency_symbol').$value->buybox_referral_fees }}
        </div>
    </div>
    <div class="row">
        <div class="col"><strong>FBA Fee: </strong></div>
        <div class="col-auto text-end">{{ config('constants.currency_symbol').$value->fba_fees }}
        </div>
    </div>
</div>
</div>
</div>