@props(['margin'])

<div class="{{ (isset($attributes['class'])) ? $attributes['class'] : 'col-sm-4' }}">
    <div class="{{$margin ?? 'mb-10'}}">
        {{ $slot }}
    </div>
</div>