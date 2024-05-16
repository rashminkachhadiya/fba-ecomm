@props([
'name'
])

<div class="select2-dd position-relative mb-12 mb-sm-0">
    <select name={{ $name }} {{ $attributes->merge(['class' => 'form-select']) }} data-control="select2" data-placeholder="Select an option" data-allow-clear="true">
        {{ $slot }}
    </select>
</div>