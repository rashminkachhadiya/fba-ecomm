<div class="modal fade" id="updateProductQtyModal" aria-labelledby="updateProductQtyModalLabel" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        {{ Form::open(['route' => ['users.store'], 'name' => 'update_shipment_product_qty_form', 'id' => 'update_shipment_product_qty_form', 'onsubmit' => 'return false']) }}
        {{-- {{ Form::open(['route' => ['fba_shipment.update-product-qty'], 'name' => 'update_shipment_product_qty_form', 'id' => 'update_shipment_product_qty_form', 'onsubmit' => 'return false']) }} --}}
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Update shipped quantity</h5>
                    <button type="button" class="btn-close stock-modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="product_item_id" id="product_item_id" value=""> 
                    <input type="hidden" name="old_sellable_asin_qty" value="" id="old_sellable_asin_qty">

                    <div class="row p-3">
                        <div class="col-sm-12">
                            <label class="form-label">Sellable ASIN Qty</label>
                            <input type="text" class="form-control form-control-sm" name="new_sellable_asin_qty" value="" id="new_sellable_asin_qty" onkeypress="return isNumberKey(this,event, 15)">
                            <span class="error" id="new_sellable_asin_qty_error" style="color: #F1416C;"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="update_shipment_product_qty_form_btn">Save</button>
                </div>
            </div>
        {{ Form::close() }}
    </div>
</div>