@if($value->shipment_created_from == 2)
	<b><span class="fs-4">{{ $value->shipment_name }}</span></b>
	<br />
	<span class="badge badge-success bg-success mt-2">Synced from Amazon</span>
@else
	<b><u><span class="fs-4">{{ $value->shipment_name }}</span></u></b>
	<span class="fs-8 text-muted"> (#{{ $value->fba_shipment_plan_id }})</span>
@endif