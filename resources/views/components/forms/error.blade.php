@props(['message'])

<span {{ $attributes->merge(['class' => 'invalid-feedback']) }} role="alert">
    <strong>{{ ($message) ?? '' }}</strong>
</span>