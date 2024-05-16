<div class="multidata-td w-100" style="width:10px; height:50;">
    <div class="row mb-2">
        {{-- <div class="col-sm-8">
            <a href="javascript:void(0)" class="moredata-link"><strong>Units At Amazon </strong>
                <i class="far fa-angle-down ms-1 text-primary" aria-hidden="true"></i>
            </a>
        </div> --}}

        
        <div class="col"><strong>Fulfillable Qty: </strong>
        <!-- <div class="col-auto"> -->
            {{ !empty($value->qty) ? $value->qty : '0' }}
            </div>
        <!-- </div> -->
        <div class="col"><strong>Reserved Qty: </strong>
        <!-- <div class="col-auto"> -->
            {{ !empty($value->afn_reserved_quantity) ? $value->afn_reserved_quantity : '0' }}
        <!-- </div> -->
        </div>
        @php
        $inboundWorking = !empty($value->afn_inbound_working_quantity) ? $value->afn_inbound_working_quantity : 0;
        $inboundShipped = !empty($value->afn_inbound_shipped_quantity) ? $value->afn_inbound_shipped_quantity : 0;
        $inboundReceiving = !empty($value->afn_inbound_receiving_quantity) ? $value->afn_inbound_receiving_quantity : 0;
        $inbound = $inboundWorking + $inboundShipped + $inboundReceiving; 
        @endphp 
        <div class="moredata-link row m-0 p-0">
        <div class="col"><strong>Inbound Qty: </strong>
        <a href="javascript:void(0);"><strong>{{$inbound}} </strong></a></div>
        </div>
    </div>
    <div class="d-show-hide">
        <div class="border m-2 overflow-hidden border-gray-300">
            <div class="row border-bottom bg-light-info">
                <div class="col"><strong>Inbound Detail</strong></div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>Inbound Working: </strong></div>
                <div class="col-auto text-end">{{ !empty($value->afn_inbound_working_quantity) ? $value->afn_inbound_working_quantity : 0 }}
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>Inbound Shipped: </strong></div>
                <div class="col-auto text-end">{{ !empty($value->afn_inbound_shipped_quantity) ? $value->afn_inbound_shipped_quantity : 0 }}
                </div>
            </div>
            <div class="row">
                <div class="col"><strong>Inbound Receiving: </strong></div>
                <div class="col-auto text-end">
                    {{ !empty($value->afn_inbound_receiving_quantity) ? $value->afn_inbound_receiving_quantity : 0 }}
                </div>
            </div>
        </div>
    </div>
</div>