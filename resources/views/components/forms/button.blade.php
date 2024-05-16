@props(['name','link'])

@if (isset($link))
<a href="{{ $link }}" {{ $attributes->merge(['class' => 'btn btn-sm fs-6 btn btn-secondary']) }}>{{ ($name) ?? __('Cancel') }}</a>
@else
<button type="submit" {{ $attributes->merge(['class' => 'btn btn-sm fs-6 btn-primary ms-3']) }}>
    {{ ($name) ?? __('Update') }}
</button>
@endif