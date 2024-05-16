<x-actions>
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="fba-shipments.show" :moduleData="['shipmentId' => $value->shipment_id]" title="View Shipment">
			<x-actions.icon class="las la-eye me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('confirm-shipment', ['shipmentId' => $value->shipment_id]) }}" title="Confirm Shipment" onclick="shipmentConfirm($(this))">
			<x-actions.icon class="fa-regular fa-check me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('fba-shipments.destroy', ['fba_shipment' => $value->id]) }}" title="Delete Shipment" onclick="shipmentDelete($(this))">
			<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	{{-- <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#move_and_delete_model" data-url="{{ route('confirm-shipment', ['shipmentId' => $value->shipment_id]) }}" title="Move Products & Delete Shipment" onclick="assignShipmentId('{{ $value->shipment_id }}', '{{ $value->fba_shipment_items_count }}')">
			<x-actions.icon class="fa-regular fa-solid fa-truck-ramp-box me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu> --}}
	{{-- <div class="menu-item">
		<a href="javascript:void(0)" class="menu-link px-5 py-3" data-bs-toggle="modal" title="Move Products and delete shipment" data-bs-target="#move_and_delete_model" onclick="assignShipmentId('{{ $value->shipment_id }}', '{{ $value->fba_shipment_item_amazon_count }}')">
			<i class="fa-regular fa-solid fa-truck-ramp-box me-4 fs-6" aria-hidden="true"></i>Move Products &<br/> Delete Shipment
		</a>
	</div> --}}
</x-actions>
