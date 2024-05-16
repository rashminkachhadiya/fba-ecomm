<div class="row multidata-td">
    <div class="col-auto text-end">{{config('constants.currency_symbol')}}{{ $value->buybox_price }}</div>
    @php $sellerId = "A3DWYIK6Y9EEQB"; @endphp
    @if($value->buybox_seller_id != null && !($value->buybox_seller_id == $sellerId))
    @php $url = "https://www.amazon.ca/sp?seller=".$value->buybox_seller_id; @endphp
    @else
    @php $url = 'javascript:void(0)'; @endphp
    @endif
    @if($value->buybox_seller_id != null)
    <a href="{{$url}}" @if($value->buybox_seller_id != null && !($value->buybox_seller_id == $sellerId)) target="_blank" @endif ><div class="col"><strong>@if($value->buybox_seller_id == $sellerId) Amazon Seller @elseif($value->is_buybox_fba == 1) FBA Seller @else FBM Seller @endif</strong></div></a>
    @endif
    
</div>