<!-- Item Label Modal Start Here -->
<div class="modal fade printLabels" id="printLabels" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark py-2 px-3">
                <h3 class="modal-title text-white px-2"><b> FNSKU: </b> <span id="item-mod-title"></span>  </h3>
                <div class="btn btn-icon btn-sm text-white ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <span class="text-white"><i class="fa-solid fa-xmark"></i></span>
                </div>
            </div>
            <div class="bg-gray-100 p-4">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="btn btn-icon btn-sm mx-2 text-white fw-700" style="background: #FF8E28;" data-bs-dismiss="modal" aria-label="Close">
                        <span>1</span>
                    </div>
                    <span class="fw-700 mx-2">Print Items Labels</span>
                    <span class="border-bottom border border-1 w-50px border-gray-400 mx-3 bxlbl"></span>
                    <div class="btn btn-icon btn-sm mx-2 text-black fw-700 bxlbl" style="background: rgba(0,0,0,0.2);" data-bs-dismiss="modal" aria-label="Close">
                        <span>2</span>
                    </div>
                    <span class="fw-700 mx-2 bxlbl">Print Box Labels</span>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    
                    <div class="col-4 text-center">
                        <div id="item_lbl_img"></div>
                    </div>
                    <div class="col-8">
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0"><strong>Title</strong></p>
                            </div>
                            <div class="col-8">
                                <input type="hidden" id="productTitle" value="">
                                <p class="text-black mb-0" id="item_lbl_title"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0"><strong>FNSKU</strong></p>
                            </div>
                            <div class="col-8">
                                <input type="hidden" id="productFNsku" value="">
                                <p class="text-black mb-0" id="item_lbl_fnsku"></p> 
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0"><strong>SKU</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-0">
                                    <span id="item_lbl_sku" class="sku-link"></span>
                                    <a href="javascript:void(0)" data-url="" class="menu-link me-1 skusLink" title="Copy to clipboard" onclick="copySKUButton($(this))" style="display: none;"><span class='badge badge-circle badge-primary'> <i class="fa-solid fa-copy text-white"></i></span></a>
                                </p> 
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0" style="font-size: 14px;"><strong>Case Pack</strong></p>
                            </div>
                            <div class="col-8">
                               <p class="text-black mb-1" style="font-size: 14px;" id="item_lbl_cpack"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0" style="font-size: 14px;"><strong>A Pack</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-1" style="font-size: 14px;" id="item_lbl_apack"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0" style="font-size: 14px;"><strong>Total Qty</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-1" style="font-size: 14px;" id="item_lbl_qty"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0" style="font-size: 14px;"><strong>Qty Remaining</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-1" style="font-size: 14px;" id="remaing_lbl_qty"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3" id="item_cases_needed" style="display: none;">
                            <div class="col-4">
                                <p class="text-black-700 mb-0" style="font-size: 14px;"><strong>Cases Needed</strong></p>
                            </div>
                            <div class="col-8">
                               <p class="text-black mb-1" style="font-size: 14px;" id="cases_needed"></p>
                            </div>
                        </div>

                        
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0"><strong>Amazon prep Instructions</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-1" style="font-size: 14px;" id="item_lbl_instruction"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <p class="text-black-700 mb-0"><strong>Prep Notes</strong></p>
                            </div>
                            <div class="col-8">
                                <p class="text-black mb-0" id="item_lbl_prep_note"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-0">
            <div class="bg-white p-4 my-4">
                <form action="" class="form" autocomplete="off">
                    <div class="d-flex justify-content-center align-items-center">

                        <div class="mx-2 align-items-center mx-3" id="casesDiv">
                            <label for="casesInHand" class="text-nowrap me-3 fw-700">Cases In Hand</label>
                            <p id="updcasesInHand" style="display: none;"></p>
                            <input type="text" class="form-control border-gray-300 numberonly" id="casesInHand" maxlength="4" value="" onkeyup="getErrorNo(this.id);">

                           <span id="error_casesInHand" style="color:red;"></span> 
                        </div>

                        <div class="d-flex mx-2 align-items-center mx-3">
                            <label for="itemPrintCount" class="text-nowrap me-3 fw-700">Items labels to print</label>
                            <p id="updItemPrintCount" style="display: none;"></p>
                            <p id="changeItemPrintCount" style="display: none;"></p>
                            <input type="text" class="form-control border-gray-300 numberonlyText" id="itemPrintCount" value="" onkeyup="getErrorNo(this.id);">

                           <span id="error_itemPrintCount" style="color:red;"></span> 
                        </div>
                      
                        <?php 
                            $currentDate = date("Y-m-d");
                            //increment 105 days
                            $maxDate = strtotime($currentDate."+ 105 days"); 
                        ?>
                        <input type="hidden" name="item_max_expiry_date" id="item_max_expiry_date" value="{{ date('m/d/Y', strtotime($currentDate.'+105 day')) }}">

                        <div class="d-flex mx-2 align-items-center mx-3" id="expDt">
                            <label for="expDate" class="text-nowrap me-3 fw-700">Expiration Date</label>
                            <div class="input-group date datepicker" id="expDate_datepicker">
                                <input type="text" class="form-control expDate exdt numberonly" name="expDate" id="expDate" onclick="getErrorNo(this.id);" onchange="validateDate(this.value);" onpaste="validateOnPaste(this.value)"/>
                                <span class="input-group-append">
                                    <span class="input-group-text bg-light d-block">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </span>
                                <span id="error_expDate" style="color:red;"></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="d-flex mx-2 align-items-center mx-3"></div>
                        <div class="d-flex mx-2 align-items-center mx-3">
                            <span id="warning_itemPrintCount" style="color:red;"></span> 
                        </div> 
                        <div class="d-flex mx-2 align-items-center mx-3"></div>
                    </div>
                </form>
            </div>
            <input type="hidden" id="shipment_name" value="">
            <input type="hidden" id="shipment_id" value="">
            <input type="hidden" id="destination_fulfillment_center_id" value="">
            <hr class="my-0">
            <div class="modal-footer justify-content-center">
                
                <div class="py-4">                    
                    <a class="btn btn-custom-success mx-3 print-btn getGenerateItemLabel" style="background: #339A23;" target="_blank" id="chkItemPrintValidated">Print Item Label</a>
                
                    @if($prepType=='EditPrep')
                        <button type="button" class="btn btn-primary mx-3 chkNxtBtn" id="printBoxLabelsModal" data-text="2D">Next</button>
                    @endif
                </div>
                <input type="hidden" id="is_product_validated">
                <div id="infoIco"></div>
            </div>
        </div>
    </div>
</div>
<!-- End Modal -->