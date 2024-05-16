@if(!empty($value->fba_shipment_items_count) && $value->fba_shipment_items_count > 0)
    <div class="d-flex align-items-center">
        <i class="btn btn-white btn-sm fas fa-ellipsis-v show menu-dropdown" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" aria-hidden="true"></i>

        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px" data-kt-menu="true" style="">
            <div class="menu-item">
                @if($value->shipment_status != 6)
                    {{-- <a href="{{ route('edit-prep', ['shipmentId' => $value->shipment_id]) }}" class="menu-link px-5 py-3 prepsList" data-id="{{ $value->shipment_id }}"
                        data-sku="{{ $value->fba_shipment_items_count }}"  
                        @if($value->prep_status=="0") data-type="{{ 'edit' }}" 
                        @elseif($value->prep_status=="1") data-type="{{ 'edit' }}" 
                        @elseif($value->prep_status=="2") data-type="{{ 'view' }}" 
                        @endif>
                        <i class="fa-regular fa-pencil me-4 fs-6" aria-hidden="true"></i> @if($value->prep_status=="0") {{ "Start" }} @elseif($value->prep_status=="1") {{ "Continue" }} @elseif($value->prep_status=="2") {{ "View" }} @endif Prep
                    </a> --}}
                    <a href="javascript:void(0);" class="menu-link px-5 py-3 prepsList" data-id="{{ $value->shipment_id }}" data-sku="{{ $value->fba_shipment_items_count }}"  @if($value->prep_status=="0") data-type="{{ 'edit' }}" @elseif($value->prep_status=="1") data-type="{{ 'edit' }}" @elseif($value->prep_status=="2") data-type="{{ 'view' }}" @endif>
                        <i class="fa-regular fa-pencil me-4 fs-6" aria-hidden="true"></i> @if($value->prep_status=="0") {{ "Start" }} @elseif($value->prep_status=="1") {{ "Continue" }} @elseif($value->prep_status=="2") {{ "View" }} @endif Prep
                    </a>
                @else
                    <a href="javascript:void(0);" class="menu-link px-5 py-3 prepsList" data-id="{{ $value->shipment_id }}" data-sku="{{ $value->fba_shipment_items_count }}" data-type="view">
                        <i class="fa-regular fa-pencil me-4 fs-6" aria-hidden="true"></i>View Prep
                    </a>
                @endif
            </div>

            @if(!empty($value->prep_status) && $value->prep_status != '0')
                <div class="menu-item">
                    <a href="javascript:void(0);" class="menu-link px-5 py-3" onclick="exportPrepDiscrepancy('{{ $value->id }}', '{{$value->shipment_id}}')">
                        <i class="fa-light fa-file-export me-3 fs-4" aria-hidden="true"></i>
                        View Discrepency
                    </a>
                </div>
            {{-- @endif --}}
            <div class="menu-item">
                    <a href="javascript:void(0);" onclick="showPrintPalletLabelModal('{{$value}}')" class="menu-link px-5 py-3"><i class="fa-solid fa-print me-3 fs-4" aria-hidden="true"></i>Print Pallet Label</a>
                </div>
            @endif
        </div>
    </div>
@endif
