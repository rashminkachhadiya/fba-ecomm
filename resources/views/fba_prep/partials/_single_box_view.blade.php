<!--begin::View component-->
<div id="single_view" class="bg-transparent overflow-hidden" data-kt-drawer="true" data-kt-drawer-activate="true" data-kt-drawer-toggle="#single_view_button" data-kt-drawer-close="#single_view_close" data-kt-drawer-width="600px">
    <div class="ms-auto card w-550px position-relative">

        <div class="border-bottom d-flex align-items-center p-3 bg-dark">
            <h4 class="mb-0 text-white"><b> FNSKU: </b> <span id="drw-mod-title"></span></h4>
            <div class="ms-auto btn btn-sm btn-icon btn-active-light-primary" id="single_view_close">
                <span class="svg-icon svg-icon-2">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="#fff"></rect>
                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="#fff"></rect>
                    </svg>
                </span>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="d-flex p-3">
                <div id="drw_lbl_img"></div>

                <div class="ms-4">
                    <p class="text-gray-500 mb-0" id="drw_lbl_title"></p>
                    <span class="text-black" id="drw_lbl_asin"></span>
                    
                </div>
            </div>
            <fieldset id="boxSingleFieldSetDrw">  
                <input type="hidden" name="asin" id="drw_asin" value="">
                <input type="hidden" name="totalShippedUnits" id="drw_totalShippedUnits" value="">
                <input type="hidden" name="total_qty" id="drw_tot_qty" value="">
                <input type="hidden" name="remaining_qty" id="drw_remaining_qty" value="">
                <input type="hidden" name="fnsku" id="drw_product_fnsku" value="">
                <input type="hidden" name="sku" id="drw_productSku" value="">
                <input type="hidden" name="fba_shipment_item_id" id="drw_fba_shipment_item_id" value="">                    
                <input type="hidden" name="shipment_name" value="{{ $shipment->shipment_name }}">
                <input type="hidden" name="fba_shipment_id" value="{{ !empty($shipment->shipment_id) ? $shipment->shipment_id : '' }}">
                <input type="hidden" name="destination" value="{{ !empty($shipment->destination_fulfillment_center_id) ? $shipment->destination_fulfillment_center_id : '' }}">
            
            <div class="mb-3 px-3 d-flex align-items-center">
                <form action="" class="form">
                    <div class="input-group">
                        <input type="text" placeholder="Search by Box ID" class="form-control py-1 numberonlyText" id="box_no_search_data" onchange="getSearchBoxNumber(this.value, 'single');">
                        <span class="input-group-text border-start bg-white" onclick="getSearchBox('single');" style="cursor:pointer;"><i class="fa-solid fa-search text-primary"></i></span>
                    </div>
                </form>
                <div class="ms-auto" id="bxicons">
                    <span class="me-3">All</span>
                    <span class="border p-2 me-3" style="border-radius: 50%; cursor:pointer;" onclick="getPrintAllBoxLabel();"><i class="fa-solid fa-print fa-lg text-success"></i></span>
                    <span class="border p-2 me-3" style="border-radius: 50%" id="drw_delete_all"></span>
                </div>
            </div>
            <div class="table-responsive pb-5" style="height:80vh;">
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
                    <tbody class="singHtml">
        
                    </tbody>
                    <tbody id="srchHtml" style="display:none;"></tbody>
                </table>
            </fieldset> 
            </div>
        </div>
        <button id="single_view_close" class="btn btn-dark position-absolute end-100 top-0 rounded-0 rounded-top py-2 px-3 mt-20 me-1" style="transform: rotateZ(270deg); transform-origin: 76% 53%; z-index: 120;">HIDE</button>
    </div>
</div>