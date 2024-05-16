<x-toggle>
    <x-toggle.input class="form-check-input h-20px w-30px update_status" type="checkbox" data-url="{{ route('supplier-change-status') }}"  id="{{ $value->id }}" onchange="changeStatus('{{ $value->id }}', $(this), '{{ $value->status }}')" :checked="($value->status == '1') ? 'checked' : null" />
</x-toggle>