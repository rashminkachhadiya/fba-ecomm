<div class="min-w-125px">
            <strong class="fw-700">Unit Price :</strong>
                <span class="link">{{config('constants.currency_symbol') . $value->unit_price}}</span>    
</div>
<div class="min-w-125px">
            <strong class="fw-700">Supplier SKU :</strong>
                <span class="link">{{$value->sku}}</span>
    
        <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="upcCopyButton($(this), 'SKU')">
        <span class="badge badge-circle badge-primary"> 
            <i class="fa-solid fa-copy text-white"></i>
        </span>
    </a>
    
</div>
<div class="min-w-125px">
            <strong class="fw-700">Supplier Name :</strong>
                <span class="link">{{$value->name}}</span>    
</div>