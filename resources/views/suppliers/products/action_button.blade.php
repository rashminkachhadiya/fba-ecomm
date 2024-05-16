<x-actions.menu>
	<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('supplier_products.destroy', ['supplier_product' => $value->id]) }}" onclick="productDelete('{{ $value->id }}', $(this))">
		<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
	</x-actions.button>
</x-actions.menu>