<x-actions>
    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="shipment-plans.show" :moduleData="['shipment_plan' => $value->id]" title="View">
			<x-actions.icon class="fa-regular fa-eye me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="shipment-plans.edit" :moduleData="['shipment_plan' => $value->id]" title="Edit">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('shipment-plans.destroy', ['shipment_plan' => $value->id]) }}" title="Delete" onclick="shipmenetPlanDelete($(this))">
			<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	@if ($value->status == 0 || $value->status == 5)
		<x-actions.menu>
			<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('shipment-plans.submit', ['shipment_plan' => $value->id]) }}" shipment_plan_id="{{$value->id}}" title="Submit shipment plan" onclick="shipmentPlanSubmit($(this))" get-shipment-url="{{route('get-empty-sellable-units')}}">
				<x-actions.icon class="fa fa-paper-plane me-4 fs-6" />
			</x-actions.button>
		</x-actions.menu>	
	@endif
</x-actions>