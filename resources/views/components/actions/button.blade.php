@props([
    'url',
    'moduleData',
    'title'
])

<a href="{{ (isset($moduleData)) ? route($url, $moduleData) : $url }}" {{ $attributes }}>
    {{ $slot }}
    {{ ($title) ?? '' }}
</a>