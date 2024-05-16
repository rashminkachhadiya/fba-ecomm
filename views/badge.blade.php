{{-- @php
    $isActive = ($value->is_active == '1') ? true : false;
@endphp --}}
<span @class([
        'label',
        'label-lg',
        'label-inline',
        // 'label-light-success' => $isActive,
        'label-light-success' => (isset($badgeArr['status'])) ? $badgeArr['status'] : false,
        'label-light-danger' => (isset($badgeArr['status'])) ? !$badgeArr['status'] : false,
        'label-light-warning' => (isset($badgeArr['incomplete'])) ? $badgeArr['incomplete'] : false,
        // 'label-light-danger' => !$isActive,
        isset($badgeArr['bgColor']) ? $badgeArr['bgColor'] : ''
    ])
>
    {{ $badgeArr['title'] }}
</span>