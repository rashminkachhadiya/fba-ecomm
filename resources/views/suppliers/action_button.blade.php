<x-actions>
    <x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="suppliers.show" :moduleData="['supplier' => $value->id]" title="View">
			<x-actions.icon class="fa-regular fa-eye me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="suppliers.edit" :moduleData="['supplier' => $value->id]" title="Edit">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('suppliers.destroy', ['supplier' => $value->id]) }}" title="Delete" onclick="supplierDelete('{{ $value->id }}', $(this))">
			<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
</x-actions>