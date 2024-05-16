@props(['isForm' => true])
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-fluid px-0">
            <div class="container-fluid">
                <div class="card ">
                    <div class="card-body px-0 pb-20 mb-20">
                        @if ($isForm)
                            <div class="tab-content mt-5">
                                {{ $slot }}
                            </div>
                        @else
                            {{ $slot }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>