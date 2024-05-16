@props([
    'method' => 'POST'
])
<div class="bg-white" data-kt-drawer="true" data-kt-drawer-activate="true" data-kt-drawer-toggle="#Filter_drawer" data-kt-drawer-close=".close_drawer" data-kt-drawer-width="{default:'300px', 'md': '400px'}">
    <div class="card w-100 rounded-0">
        <div class="card-header pe-5">
            <div class="card-title">
                <div class="d-flex justify-content-center flex-column me-3">
                    <div class="fs-4 fw-bolder text-gray-900 me-1 lh-1">Filter</div>
                </div>
            </div>
            <div class="card-toolbar">
                <div class="btn btn-sm btn-icon btn-active-light-primary close_drawer">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black"></rect>
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black"></rect>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            {{ Form::open(['name' => 'advance_filter_form', 'id' => 'advance-filter', 'onsubmit' => 'return false', 'method' => $method]) }}
            <div class="card-body p-0">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item border-0">

                        {{ $slot }}

                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-light" type="button" id="advance-filter-reset">Reset</button>
            <button class="btn btn-primary" type="submit" name="submit">Apply</button>
        </div>
        {{ Form::close() }}

    </div>
</div>