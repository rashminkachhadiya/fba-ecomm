@props(['style'])

<div class="modal-body" style="{{ ($style) ?? '' }}">
    {{$slot}}
</div>