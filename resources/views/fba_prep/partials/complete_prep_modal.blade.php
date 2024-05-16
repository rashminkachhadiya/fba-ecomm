<div class="modal-dialog modal-dialog-scrollable modal-xl">
    {{ Form::open(['route' => ['fba_prep.update_shipment_complete_prep_status'], 'name' => 'complete_prep_modal_form', 'id' => 'complete_prep_modal_form', 'onsubmit' => 'return false']) }}
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="completePrepShipmentModalLabel">Complete Prep Shipment Id: <span id="shipment_id_text">{{ isset($shipment) ? $shipment->shipment_id : '' }}</span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            @if($shipment->shipment_status != '0')
                <p style="color: red; font-weight: 700;">The Quantities cannot be adjusted because the shipment status is {{ App\Helpers\CommonHelper::returnStatusNameById($shipment->shipment_status) }}</p>
            @endif
            <input type="hidden" name="shipmentId" id="shipment_id" value="{{ $shipment->id }}">
            <input type="hidden" name="shipment_status" id="shipment_status" value="{{ $shipment->shipment_status }}">
            <input type="hidden" name="shipment_status_string" id="shipment_status_string" value="{{ $shipment->shipment_status == 0 ? 'WORKING' : App\Helpers\CommonHelper::returnStatusNameById($shipment->shipment_status) }}">

            <div class="table-responsive">
                <table class="table align-middle table-row-bordered">
                    <thead>
                        <th>Product Name</th>
                        <th>Asin</th>
                        <th>FNSKU</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Done</th>
                        <th>Discrepancy</th>
                        <th>Updated Qty</th>
                    </thead>
                    <tbody>
                        @foreach ($shipmentItems as $item)
                            <?php
                                $allowedUpdatedQty = App\Services\PrepService::actualQtyShippedCalculate($item->original_quantity_shipped, $item->done_qty); 

                                $discrepancy = 0;
                                if(!empty($item->quantity_shipped))
                                {
                                    $discrepancy = $item->done_qty - $item->quantity_shipped;
                                }
                            ?>
                            <tr attr-parent-id="{{ $item->id }}">
                                <td>{{ !empty($item->title) ? $item->title : '-' }}</td>
                                <td>
                                    <span id="complt_lbl_asin">{{ !empty($item->asin) ? $item->asin : '-' }}</span> 
                                  
                                    @if(!empty($item->asin) && !empty($item->asin))
                                        &nbsp;<a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinButtonModal('{{ $item->asin }}')"><span class='badge badge-circle badge-primary'> <i class="fa-solid fa-copy text-white"></i></span></a>
                                    @endif
                                </td>
                                <td>{{ !empty($item->fnsku) ? $item->fnsku : '-' }}</td>
                                <td>
                                    <span id="complt_lbl_sku" class="sku-link">{{ !empty($item->sku) ? $item->sku : '-' }}</span>

                                   @if(!empty($item->sku) && !empty($item->sku))
                                        &nbsp;<a href="javascript:void(0)" data-url="" class="menu-link me-1 skusLink" title="Copy to clipboard" onclick="copySKUButtonModal('{{ $item->amazonData->sku }}')"><span class='badge badge-circle badge-primary'> <i class="fa-solid fa-copy text-white"></i></span></a>
                                    @endif
                                </td>
                                <td>{{ !empty($item->quantity_shipped) ? $item->quantity_shipped : '-' }}</td>
                                <td>{{ !empty($item->done_qty) ? $item->done_qty : '0' }}</td>
                                <td>{{ ($discrepancy > 0) ? '+'.$discrepancy : $discrepancy }}</td>
                                <td>{{ $allowedUpdatedQty }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Confirm</button>
        </div>
    </div>
    {{ Form::close()}}
</div>