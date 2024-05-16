<x-actions>
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" id="edit_supplier_contact_info" data-id="{{ $value->id }}" 
            url="javascript:;" data-url="{{ route('supplier_contact_info.edit', ['supplier_contact_info' => $value->id]) }}" title="Edit">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('supplier_contact_info.destroy', ['supplier_contact_info' => $value->id]) }}" title="Delete" onclick="contactDelete('{{ $value->id }}', $(this))">
			<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
</x-actions>