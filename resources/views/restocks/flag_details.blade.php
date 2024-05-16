
<div class="multidata-td w-100">
    <div class="row mb-2">
        <div class="moredata-link text-center">
            <label for="Suggested_ship_qty"><strong>Suggested Ship Qty : </strong></label>
            <a href="javascript:void(0)"><strong>{{ $value->suggested_quantity }}</strong></a>
            <span><a href="javascript:void(0)">{!! $flag !!}</a></span>
        </div>
    </div>
    <div class="d-show-hide w-300px">
        {{-- <div class="border m-2 overflow-hidden">
            <div class="row">
                <div class="col"><strong>Formula: </strong></div>
                <div class="col-auto">((30D ROS * Supplier Lead Time) > FBA Qty)</div>
            </div>
        </div> --}}
        <div class="border m-2 overflow-hidden border-gray-300">
            <div class="row border-bottom">
                <div class="col"><strong>30D ROS: </strong></div>
                <div class="col-auto text-end">{{ $value->ros_30 }}</div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>Supplier Lead Time: </strong></div>
                <div class="col-auto text-end">{{ $value->lead_time }}</div>
            </div>
        </div>
        <div class="border m-2 overflow-hidden border-gray-300">
            <div class="row border-bottom">
                <div class="col"><strong>Threshold Qty: </strong></div>
                <div class="col-auto text-end">{{ $value->threshold_qty }}</div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>FBA Qty: </strong></div>
                <div class="col-auto text-end">
                    {{ ($value->qty + ($value->afn_inbound_working_quantity + $value->afn_inbound_shipped_quantity + $value->afn_inbound_receiving_quantity)) - $value->afn_reserved_quantity }}
                </div>
            </div>
        </div>
    </div>
</div>
<div class="multidata-td text-center">
    <label for="wh_qty"><strong>WH Qty : </strong></label>
         <span>{{ !empty($value->wh_qty) ? $value->wh_qty : 0}}</span>
</div>