@props([
    'value' => '',
    'title' => 'Select Option'
])

<option value={{ $value }} {{ $attributes }}>{{ $title }}</option>