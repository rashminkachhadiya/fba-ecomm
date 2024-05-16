<div class="modal fade" tabindex="-1" id="print_pallet_label_modal">
    <div class="modal-dialog modal-md">
    {{ Form::open(['route' => ['fba_shipment.print-pallet-label'], 'name' => 'print_pallet_label_form', 'id' => 'print_pallet_label_form', 'onsubmit' => 'return false']) }}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Pallet Label</h5>
                <!--begin::Close-->
                <button type="button" class="btn-close stock-modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                <!--end::Close-->
            </div>

            <div class="modal-body">
            <input type="hidden" name="shipment_id" id="shipment_id">
                <input type="hidden" name="shipment_id" id="shipment_id1">
               
                <div class="mb-5">
                    <label class="form-check-label mb-3">Number Of Pallet</label>
                    <input type="text" maxlength="2" class="form-control form-control-sm" name="number_of_pallet" id="number_of_pallet" onkeypress="return onlyNumericAllowed(this, event)">
                    <span class="error" id="number_of_pallet_error" style="color: #F1416C;"></span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Print</button>
            </div>
        </div>
    {{ Form::close()}}
    </div>
</div>