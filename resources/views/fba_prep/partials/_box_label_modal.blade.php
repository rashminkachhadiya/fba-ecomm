<div class="modal fade printBoxLabels" tabindex="-1" id="printBoxLabels" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark py-2 px-3">
                <h3 class="modal-title text-white px-2"><b> FNSKU: </b> <span id="box-mod-title"></span>  </h3>
                <div class="btn btn-icon btn-sm text-white ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <span class="text-white"><i class="fa-solid fa-xmark"></i></span>
                </div>
            </div>
            <div class="bg-gray-100 p-4">
                <div class="d-flex justify-content-center align-items-center only3d">
                    <div class="btn btn-icon btn-sm mx-2 text-white fw-700" style="background: #339A23;" data-bs-dismiss="modal" aria-label="Close">
                        <span>1</span>
                    </div>
                    <span class="fw-700 mx-2">Print Items Labels</span>
                    <span class="border-bottom border border-1 w-50px border-gray-400 mx-3 bxlbl"></span>
                    <div class="btn btn-icon btn-sm mx-2 text-white fw-700 bxlbl" style="background: #FF8E28;" data-bs-dismiss="modal" aria-label="Close">
                        <span>2</span>
                    </div>
                    <span class="fw-700 mx-2 bxlbl">Print Box Labels</span>
                </div>
            </div>
        
            <div class="modal-body">
                <fieldset id="boxConfigerationFieldset" autocomplete="off">
                
                    <input type="hidden" name="asin" id="box_asin" value="">
                    <input type="hidden" name="totalDoneUnits" id="bx_totalDoneUnits" value="">
                    <input type="hidden" name="totalShippedUnits" id="bx_totalShippedUnits" value="">
                    <input type="hidden" name="remaining_qty" id="remaining_qty" value="">
                    <input type="hidden" name="fnsku" id="product_fnsku" value="">
                    <input type="hidden" name="sku" id="productSku" value="">
                    <input type="hidden" name="fba_shipment_item_id" id="fba_shipment_item_id" value="">
                    <input type="hidden" name="main_image" id="box_main_image" value="">
                    <input type="hidden" name="box_lbl_qty" id="box_lbl_qty" value="">
                    <input type="hidden" name="product_title" id="product_title" value="">
                    
                    <input type="hidden" name="shipment_name" id="bx_shipment_name" value="">

                    <input type="hidden" name="fba_shipment_id" id="bx_fba_shipment_id" value="">

                    <input type="hidden" name="destination" id="bx_destination" value="">

                    <input type="hidden" name="old_asin_weight" id="old_asin_weight" value="">

                    <?php $todayDate = date("Y-m-d"); ?>
                    <input type="hidden" name="max_expiry_date" id="max_expiry_date" value="{{ date('m/d/Y', strtotime($todayDate.'+105 day')) }}">
                   
                    <div class="row">
                        <div class="col-lg-3 text-center">
                            <div id="box_lbl_img"></div>
                        </div>
                        <div class="col-lg-9">
                            <div class="row align-items-center mb-3">
                                <div class="col-3">
                                    <p class="fw-700 mb-0">Title</p>
                                </div>
                                <div class="col-9">
                                    <p class="text-black mb-0" id="box_lbl_title"></p>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-3">
                                    <p class="mb-0 fw-700">ASIN Weight</p>
                                </div>
                                <div class="col-9">
                                    <div class="w-50 d-flex">
                                        <input type="text" class="form-control py-2 me-4" id="asin_weight" name="asin_weight" value="" onblur="getPerBoxItemCount();" onkeyup="getErrorNo(this.id);" autocomplete="off">

                                        <a class="form-control py-2 pond" value="" style="background: #DFDFDF;" readonly>Pound</a>
                                    </div>
                                    <span id="error_asin_weight" style="color:red;"></span>

                                    @error('asin_weight')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-3">
                                    <p class="fw-700 mb-0">Product Remaining</p>
                                </div>
                                <div class="col-9">
                                    <p class="text-black mb-0" id="box_lbl_product_remain"></p>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-3">
                                    <p class="mb-0 fw-700">Box configuration</p>
                                </div>
                                <div class="col-9">
                                    <div class="bg-gray-100 p-3 d-flex flex-column">
                                        <div class="row align-items-start">
                                            <div class="col-10 d-flex px-1">
                                                    <div class="px-1 w-25">
                                                        <label class="fw-700 text-black" style="font-size: 10px">Units Per Box</label>
                                                    </div>
                                                    <div class="px-1 w-25">
                                                        <label class="fw-700 text-black" style="font-size: 10px">Number of Boxes</label>
                                                    </div>
                                                    <div class="px-1 w-80px">
                                                        <label class="fw-400 text-gray-700" style="font-size: 10px">Total Quantity</label>
                                                    </div>
                                                    <div class="px-1 w-150px">
                                                        <label class="fw-700 text-black" style="font-size: 10px">Expiration Date</label>
                                                    </div>
                                            </div>
                                            <div class="col px-1">
                                                <a class="btn btn-primary px-1 py-2 w-100 add-more" style="font-size: 10px" id="addMorebtn">Add Box</a>
                                            </div>
                                        </div>
                                        <div class="row align-items-start my-2">
                                            <div class="col-10 d-flex px-1">
                                                <div class="px-1 w-25">
                                                    <input type="text" class="form-control boxItemCounts numberonlyText" id="per_box_item_count_0" name="per_box_item_count[]" onkeyup="getErrorNo(this.id);" onchange="getCalculateTotal(0);getGrandTotal(0);" autocomplete="off">
                                                    <span id="error_per_box_item_count_0" style="color:red;"></span>

                                                    @error('per_box_item_count')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                                <div class="px-1 w-25">
                                                    <input type="text" class="form-control numberonlyText" id="no_of_boxes_count_0" onchange="getCalculateTotal(0);getGrandTotal(0);" name="no_of_boxes_count[]" onkeyup="getErrorNo(this.id);" autocomplete="off">
                                                    <span id="error_no_of_boxes_count_0" style="color:red;"></span>
                                                </div>
                                                <div class="px-1 w-80px">
                                                    <input type="text" class="form-control bg-gray-400" value="" id="tot_qty_0" readonly style="background: #DFDFDF;" name="tot_qty[]" autocomplete="off">
                                                    <span id="error_tot_qty_0" style="color:red;"></span>
                                                </div>
                                                <div class="px-1 w-150px">
                                                    <div class="input-group date datepicker" id="exp_date_datepicker">
                                                        <input type="text" class="form-control exp_box_date numberonly" name="expiry_box_date[]" id="exp_date_0" onclick="getErrorNo(this.id);" onchange="validateDate(this.value);" autocomplete="off"/>
                                                        <span class="input-group-append">
                                                            <span class="input-group-text bg-light d-block">
                                                                <i class="fa fa-calendar"></i>
                                                            </span>
                                                        </span>
                                                        <span id="error_exp_date_0" style="color:red;"></span>
                                                    </div> 
                                                </div>
                                            </div>
                                        </div>
                                        <div class="newinput" id="newinput"></div>
                                        <p id="maximumBoxQty" style="display: none;"></p>
                                        <p id="unitPerBoxesQty" style="display: none;"></p>
                                        <p id="unitPerBoxesQty_second" style="display: none;"></p>
                                        <p id="casesInHandQty" style="display: none;"></p>
                                        <div class="mt-auto"><div class="d-flex mt-auto align-items-center justify-content-between"><p class="mb-0" id="extraBoxWarning"></p></div></div>
                                        <div class="mt-auto">
                                            <input type="hidden" id="box_suggestion_qty">
                                            <div class="d-flex mt-auto align-items-center justify-content-between">
                                                <p class="mb-0"><input type="checkbox" class="me-3" id="myCheck" onclick="defaultExpirationDate()">Select Default expiration date</p>
                                                <input type="hidden" id="first" value="0">
                                                <input type="hidden" id="second" value="0">
                                                <input type="hidden" id="third" value="0">
                                                <input type="hidden" id="fourth" value="0">
                                                <input type="hidden" id="fifth" value="0">
                                                <input type="hidden" id="sixt" value="0">
                                                <p class="mb-0" style="margin-right: 85px;" id="totQanHtm"></p>
                                                <p class="text-center big mt-1 mb-0 fw-400" style="color: #BB1515;" id="box_suggestion"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form> 
            </div>

            <div class="modal-footer justify-content-center" style="padding-top:130px">
                
                <div class="py-4">
                    <button type="button" class="btn btn-secondary mx-3 printIcn" id="boxBack">Back</button style="margin-top:100px">
                    
                    <button type="button" class="btn btn-custom-success mx-3 print-btn 2dbox" id="2dbox" style="background: #339A23;display:none;float: right;" onclick="getGenerateBoxLabel('', this);" style="margin-top:100px">Save & Print 2D Box Label</button>

                    <button type="button" class="btn btn-custom-success mx-3 print-btn 3dbox" id="3dbox" style="background: #339A23;display:none;float: right;" onclick="getGenerateBoxLabel('3in1', this);">Save & Print 3 IN 1 Box Label</button> 
                </div>
                <input type="hidden" id="is_product_bx_validated">
                <div id="infoBoxIco"></div>
            </div>
           
        </div>
    </div>
</div>
