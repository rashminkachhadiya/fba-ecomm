{{-- @php
    $isActive = ($hazmatArr->is_active == '1') ? true : false;
@endphp --}}
<div class="mb-4 multidata-td"> 
<label for="hazmat"><strong class="font-weight: 0;">Hazmat  : </strong></label>
<span @class([
        'label',
        'label-lg',
        'label-inline',
        // 'label-light-success' => $isActive,
        'label-light-success' => (isset($hazmatArr['status'])) ? $hazmatArr['status'] : false,
        'label-light-danger' => (isset($hazmatArr['status'])) ? !$hazmatArr['status'] : false,
        // 'label-light-danger' => !$isActive,
        isset($hazmatArr['bgColor']) ? $hazmatArr['bgColor'] : ''
    ])
>
    {{ $hazmatArr['title'] }}
</span>
</div>
<!-- oversize -->
<div class="mb-4 multidata-td">
<label for="oversize"><strong>Oversize : </strong></label>
{{-- @php
    $isActive = ($oversizeArr->is_active == '1') ? true : false;
@endphp --}}

<span @class([
        'label',
        'label-lg',
        'label-inline',
        // 'label-light-success' => $isActive,
        'label-light-success' => (isset($oversizeArr['status'])) ? $oversizeArr['status'] : false,
        'label-light-danger' => (isset($oversizeArr['status'])) ? !$oversizeArr['status'] : false,
        // 'label-light-danger' => !$isActive,
        isset($oversizeArr['bgColor']) ? $oversizeArr['bgColor'] : ''
    ])
>
    {{ $oversizeArr['title'] }}
</span>
</div>