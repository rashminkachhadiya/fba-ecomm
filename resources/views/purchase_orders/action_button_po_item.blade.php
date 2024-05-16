<x-actions.menu>
    <x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)"
        data-url="{{ route('purchase_order_items.destroy', ['purchase_order_item' => $value->id]) }}"
        onclick="poItemDelete('{{ $value->id }}', $(this));">
        <x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
    </x-actions.button>
</x-actions.menu>
