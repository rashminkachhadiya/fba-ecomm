<div class="multidata-td w-100" style="width:10px; height:50;">
    <div class="row mb-2">
        <div class="moredata-link">
            {{-- <div class="col"><strong>Inbound Qty: </strong></div> --}}
            <a href="javascript:void(0);"><strong>{{$suggestedShipmentQty}} </strong></a>
        </div>
    </div>
    <div class="d-show-hide">
        <div class="border m-2 overflow-hidden border-gray-300">
            <div class="row border-bottom">
                <div class="col"><strong>Target qty on hands days: </strong></div>
                <div class="col-auto text-end">{{ !empty($setting) ? $setting->day_stock_holdings : 0 }}
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>Total Lead Time: </strong></div>
                <div class="col-auto text-end">{{ !empty($setting) ? $setting->supplier_lead_time : 0 }}
                </div>
            </div>
            <div class="row border-bottom">
                <div class="col"><strong>ROS (30 Days): </strong></div>
                <div class="col-auto text-end">{{ $ros_30 }}
                </div>
            </div>
            <div class="row">
                <div class="col"><strong>Current Amazon inventory: </strong></div>
                <div class="col-auto text-end">
                    {{ $totalFBAQty }}
                </div>
            </div>
        </div>
    </div>
</div>