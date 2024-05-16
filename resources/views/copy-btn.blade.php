<div class="min-w-125px">
    @if (!is_null($value) && $title != "Supplier SKU")
        <strong class="fw-700">{{$title}} :</strong>
    @endif
    @if (isset($link))
        <a href="{{ $link }}" target="_blank" class="link link-class">{{ $value }}</a>
    @else
        <span class="link">{{ $value }}</span>
    @endif

    @if (!is_null($value))
    <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="upcCopyButton($(this), '{{ $title }}')">
        <span class="badge badge-circle badge-primary"> 
            <i class="fa-solid fa-copy text-white"></i>
        </span>
    </a>
    @endif

</div>