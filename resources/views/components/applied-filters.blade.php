<div class="alert alert-warning align-items-center py-3 px-4 mt-3 mb-0" id="filter_by_div" style="display: none;">
    <!--begin::Svg Icon | path: icons/duotune/general/gen048.svg-->
    <span class="svg-icon svg-icon-2hx svg-icon-warning me-4">
        <i class="fa-duotone fa-filter-list fs-3 text-primary" aria-hidden="true"></i>
    </span>
    <!--end::Svg Icon-->
    <div class="d-flex flex-column">
        <span id="test">
            Filter by
            {{-- <span id="search_span" style="display: none;">
                <span class="fw-700 me-1">Search:</span> <span id="search_val"></span>
            </span> --}}

            <span id="search-span" style="display: none;">
                <span class="fw-700 me-1">Search:</span> <span id="search-data"></span>
            </span>
            <span class="mx-2 partition-span" style="display: none;"> |</span>
            {{ $slot }}
            {{-- <span id="is_active_span" style="display: none;">
                <span class="fw-700 me-1">Status:</span> <span id="is_active_val"></span>
            </span>
            <span class="mx-2 partition-span" style="display: none;"> |</span>
            <span id="is_hazmat_span" style="display: none;">
                <span class="fw-700 me-1">Hazmat:</span> <span id="is_hazmat_val"></span>
            </span>
            <span class="mx-2 partition-span" style="display: none;"> |</span>
            <span id="is_oversize_span" style="display: none;">
                <span class="fw-700 me-1">Oversize:</span> <span id="is_oversize_val"></span>
            </span>
            <span class="mx-2 partition-span" style="display: none;"> |</span>
            <span id="search_span" style="display: none;">
                <span class="fw-700 me-1">Search:</span> <span id="search_val"></span>
            </span>
            <span class="mx-2 partition-span" style="display: none;"> |</span> --}}

            <a href="javascript:void(0)" id="edit-filter-btn" class="ms-3">Edit</a> filter or
            <a href="javascript:void(0)" class="clear_search" id="clear_search">Reset</a> it.
        </span>
    </div>
</div>
