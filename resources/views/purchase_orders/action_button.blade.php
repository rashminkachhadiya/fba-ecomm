<x-actions>
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="purchase_orders.show" :moduleData="['purchase_order' => $value->id]" title="View">
			<x-actions.icon class="fa-regular fa-eye me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	@if ($value->status != 'Closed')
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="purchase_orders.edit" :moduleData="['purchase_order' => $value->id]" title="Edit" onclick="show_loader()">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>
	@endif

	@if (in_array($value->status, ['Draft','Sent','Shipped']))
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('purchase_orders.destroy', ['purchase_order' => $value->id]) }}" title="Cancel" onclick="purchaseOrderDelete('{{ $value->id }}', $(this))">
			<x-actions.icon class="fa-regular fa-close me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
	@endif

	@if (in_array($value->status, ['Partial Received','Received','Closed']))
		<x-actions.menu>
			<x-actions.button class="menu-link px-5 py-3" url="{{ route('shipment-plans.create',['po_id' => $value->id]) }}" title="Create Draft Shipment Plan">
				{{-- <x-actions.icon class="fa-regular fa-close me-4 fs-6" /> --}}
				<x-actions.icon class="fa-solid fa-circle-plus me-4 fs-6" />
			</x-actions.button>
		</x-actions.menu>
	@endif

	@if (!in_array($value->status, ['Draft','Sent','Closed','Cancelled']))
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 pe-0 py-3" url="javascript:void(0)" title="Update Shipping Detail" onclick="showShippingDetailPopup('{{ $value->id }}')">
			<x-actions.icon class="fa-regular fa-pen-to-square me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
	@endif

	@forelse ($nextActions as $action)
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3 po-status" url="javascript:void(0)" data-status="{{ $action }}" title="{{ config('constants.po_status.'.$action.'.action') }}" onclick="poStatusUpdate('{{ $value->id }}', $(this))">
			<x-actions.icon class="{{ config('constants.po_status.'.$action.'.icon') }} me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
	@empty

	@endforelse

	@if ($value->status != 'Closed')
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('send-po-email') }}" title="Send Email" onclick="poEmailSend('{{ $value->id }}', $(this))" pdf-generate-url="{{route('generate-pdf',['poId' => $value->id])}}">
			<x-actions.icon class="fa fa-paper-plane me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
	@endif

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="generate-pdf" :moduleData="['poId' => $value->id]" title="Download as PDF">
			<x-actions.icon class="fa-regular fa-file-pdf me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="export-po-excel" :moduleData="['po_id' => $value->id]" title="Download as Excel">
			<x-actions.icon class="fa-regular fa-file-excel me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

</x-actions>