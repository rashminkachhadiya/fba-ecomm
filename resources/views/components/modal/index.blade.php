@props(['id', 'dialog', 'style'])

<div class="modal fade" tabindex="-1" id={{ $id }}>
    <div class="modal-dialog {{ ($dialog) ?? '' }}" style="{{$style ?? ''}}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
