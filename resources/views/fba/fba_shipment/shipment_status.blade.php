@if(empty($deletedAt))

    @if($isShipmentIdExpired)
        <span class="text-danger"> Shipment Id Expired</span>
    @else 
        <span class="text-primary">Pending Approval</span>
    @endif

@else 

    <span class="text-danger">
        Deleted on {{ $deletedAt }}
    </span> 
@endif

<br>

@if($value->is_approved == 4 && !empty($value->remark))
    <a href="javascript:void(0)" data-url="" class="badge badge-danger" onclick="showCreateShipmentError(this)">
        <i class="fa-regular fa-exclamation-triangle" aria-hidden="true"></i>
    </a>

@endif