{{-- <i class="btn btn-white btn-sm fas fa-ellipsis-v" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" aria-hidden="true"></i>

<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-150px" data-kt-menu="true">
	<div class="menu-item">
		<a href="{{ route('users.edit', ['user' => $value->id]) }}" class="menu-link px-5 py-3">
			<i class="las la-pen me-4 fs-3" aria-hidden="true"></i>Edit
		</a>
	</div>
	<div class="menu-item">
		<a href="javascript:void(0)" data-url="{{ route('users.destroy', ['user' => $value->id]) }}" class="menu-link px-5 py-3" title="Delete" onclick="userDelete('{{ $value->id }}', $(this))">
			<i class="fa-regular fa-trash-can me-4 fs-6" aria-hidden="true"></i>Delete
		</a>
	</div>
</div> --}}
<x-actions>
	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="users.edit" :moduleData="['user' => $value->id]" title="Edit">
			<x-actions.icon class="las la-pen me-4 fs-3" />
		</x-actions.button>
	</x-actions.menu>

	<x-actions.menu>
		<x-actions.button class="menu-link px-5 py-3" url="javascript:void(0)" data-url="{{ route('users.destroy', ['user' => $value->id]) }}" title="Delete" onclick="userDelete('{{ $value->id }}', $(this))">
			<x-actions.icon class="fa-regular fa-trash-can me-4 fs-6" />
		</x-actions.button>
	</x-actions.menu>
</x-actions>
