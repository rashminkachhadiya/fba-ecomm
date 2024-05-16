@props(['type'])

<input type="{{ isset($type) ? $type : 'text' }}" {{ $attributes }} />