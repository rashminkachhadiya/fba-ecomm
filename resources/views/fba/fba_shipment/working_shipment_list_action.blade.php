@if(!empty($value->fba_shipment_items_count) && $value->fba_shipment_items_count > 0)
<x-actions>
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="fba-shipments.show" :moduleData="['shipmentId' => $value->shipment_id]" title="View Shipment">
			<x-actions.icon class="las la-eye me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" onclick="showPrintPalletLabelModal('{{$value}}')" title="Print Pallet Label">
			<x-actions.icon class="fa-regular fa-edit me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	@if ($value->prep_status == 2)
	<x-actions.menu>
		<x-actions.button target="_blank" class="menu-link px-5 py-3" url="fba-shipments.transport_info" :moduleData="['shipmentId' => $value->id]" title="Shipment Transport Info">
			<x-actions.icon class="fa-regular fa-truck me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
	@endif

</x-actions>
@endif