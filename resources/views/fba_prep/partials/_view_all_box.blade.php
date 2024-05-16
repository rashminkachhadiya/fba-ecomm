<div id="view_all_{{ $shipment->shipment_id }}" class="bg-transparent overflow-hidden" data-kt-drawer="true" data-kt-drawer-activate="true" data-kt-drawer-toggle="#view_all_button_{{ $shipment->shipment_id }}" data-kt-drawer-close="#view_all_close_{{ $shipment->shipment_id }}" data-kt-drawer-width="600px">
    <div class="ms-auto card w-550px position-relative">
        <div class="border-bottom d-flex align-items-center p-3 bg-dark">
            <h4 class="mb-0 text-white">SHIPMENT ID: {{ $shipment->shipment_id }} </h4>
            <div class="ms-auto btn btn-sm btn-icon btn-active-light-primary" id="view_all_close_{{ $shipment->shipment_id }}">
                <span class="svg-icon svg-icon-2">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="#fff"></rect>
                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="#fff"></rect>
                    </svg>
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="my-3 px-3 d-flex align-items-center">
                <form class="form flex-fill">
                    <div class="input-group">
                        <input type="text" placeholder="Search by Box ID" class="form-control py-1 numberonlyText" id="view_all_box_no_search_data" onchange="getSearchBoxNumber(this.value, 'viewAll');">
                        <span class="input-group-text border-start bg-white" style="cursor:pointer;" onclick="getSearchBox('viewAll');"><i class="fa-solid fa-search text-primary"></i></span>
                    </div>
                </form>
            </div>
            <div class="table-responsive pb-5"  style="height:80vh;">
                <table class="table table-bordered dataTable tab-table" style="height: 1px;">
                    <thead>
                        <tr>
                            <th class="text-nowrap pr-custom text-center">BOX ID</th>
                            <th class="text-nowrap pr-custom text-center">IMAGE</th>
                            <th class="text-nowrap pr-custom text-center">QTY</th>
                            <th class="text-nowrap pr-custom text-center">SKU</th>
                            <th class="text-nowrap pr-custom text-center">EXPIRY DATE</th>
                            <th class="text-nowrap pr-custom text-center">ACTION</th>
                        </tr>
                    </thead>    
                    <tbody id="vhtml">
                   
                    </tbody>
                    <tbody id="srchHtmlAll" style="display:none;"></tbody>
                </table>
            </div>
        </div>
        {{-- <button id="view_all_close_{{ $shipment->shipment_id }}" class="btn btn-dark position-absolute end-100 top-0 rounded-0 rounded-top py-2 px-3 mt-20 me-1" style="transform: rotateZ(270deg); transform-origin: 76% 53%; z-index: 120;">HIDE</button> --}}
    </div>
</div>
<!--end::View component-->