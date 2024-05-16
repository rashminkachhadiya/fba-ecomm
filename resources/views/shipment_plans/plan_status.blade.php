@if ($value->plan_status == 'Finalized')
    {{-- <span class='badge bg-success fs-6'> --}}
        Created Draft Shipment
    {{-- </span> --}}
@else
    {{-- <span class='badge badge-danger fs-6'>{{ $value->plan_status }}</span> --}}
    {{ $value->plan_status }}
    @if($value->status == 5)
        <a href="javascript:void(0)" data-url="{{ route('shipment-plans.error_log', ['shipment_plan' => $value->id]) }}" onclick="showShipmentPlanError(this)" style="margin-left: 10px;">
            <img src="{{url('media/error.svg')}}">
        </a>
    @endif
@endif