@props([
    'title', 
    'required'
])

<label class="form-label {{ ($required) ?? '' }}">{{ $title }}</label>