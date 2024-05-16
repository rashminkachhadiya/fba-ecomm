<x-actions>
    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="shopify-orders.show" :moduleData="['shopify_order' => $value->id]" title="View Order">
			<x-actions.icon class="fa-regular fa-eye me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)"  title="Edit Order">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)"  title="Cancel Order" >
			<x-actions.icon class="fa-regular fa-remove me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)"  title="Print Order Invoice" >
			<x-actions.icon class="fa-regular fa-print me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)"  title="Pack Order" >
			<x-actions.icon class="fa-regular fa-box-open me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>
</x-actions>