@extends('layouts.app')

@section('title', 'FBA Shipments')

@section('breadcrumb')
<li class="breadcrumb-item text-primary link-class"><a href="{{ route('fba-shipments.index') }}">{{__('FBA Shipment')}}</a></li>
<li class="breadcrumb-item text-primary"><a href="javascript:void(0)">{{__('Details')}}</a></li>
@endsection

@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

    <!-- Create Draft Plan -->
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-fluid px-0">
            <div class="container-fluid">
                <div class="card ">
                    <div class="card-body px-0 pb-10">
                        <div class="tab-content mt-5 border rounded p-2 border-secondary shadow-lg">
                            <div class="row">
                                <div class="col">
                                    <div class="row align-items-center">
                                        <div class="col-sm-6 px-5">
                                         
                                            <h4><span class="text-success">Shipment Name:</span> &nbsp;{{ $fbaShipment ? $fbaShipment->shipment_name : '' }} 
                                           
                                            @if($fbaShipment && !empty($fbaShipment->fba_shipment_plan_id))
                                                <span class="fs-8 text-muted"> (#{{ $fbaShipment->fba_shipment_plan_id }})</span></h4>
                                            @endif

                                            <h4><span class="text-success">No. of shipments:</span> &nbsp;{{ !empty($plan) ? $plan['fba_shipment_count'] : '' }}</h4>
                                            <br /> <a href="{{ route('fba-shipments.index') }}" class="btn btn-sm btn-secondary">Back</a>

                                        </div>
                                        
                                        <div class="col-sm-6">
                                            @if(!empty($shipmentTableData))
                                                <table class="table table-sm table-responsive border border-2 shadow text-center">
                                                    <thead>
                                                        <tr>
                                                            <th>Shipment ID</th>
                                                            <th>STATUS</th>
                                                            <th>SKU</th>
                                                            <th>Sellable Units</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php($skuCount = 0)
                                                        @php($unitCount = 0)

                                                        @foreach($shipmentTableData as $row)
                                                            <tr @if(!empty($row['deleted_at'])) class="bg-custom-danger"  @endif>
                                                                <td class="border">{{ $row['shipment_id'] }}</td>
                                                                <td class="border">

                                                                    @if(is_numeric($row['shipment_status']))
                                                                    @switch($row['shipment_status'])

                                                                    @case(0)
                                                                    <span>WORKING </span>
                                                                    @break

                                                                    @case(1)
                                                                    <span> READY_TO_SHIP</span>
                                                                    @break

                                                                    @case(2)
                                                                    <span>SHIPPED </span>
                                                                    @break

                                                                    @case(3)
                                                                    <span> RECEIVING</span>
                                                                    @break

                                                                    @case(4)
                                                                    <span> CANCELLED</span>
                                                                    @break

                                                                    @case(5)
                                                                    <span> DELETED</span>
                                                                    @break

                                                                    @case(6)
                                                                    <span> CLOSED</span>
                                                                    @break

                                                                    @case(7)
                                                                    <span>ERROR </span>
                                                                    @break

                                                                    @case(8)
                                                                    <span> IN_TRANSIT</span>
                                                                    @break

                                                                    @case(9)
                                                                    <span>DELIVERED </span>
                                                                    @break

                                                                    @case(10)
                                                                    <span> CHECKED_IN</span>
                                                                    @break

                                                                    @default
                                                                    <span>-</span>
                                                                    @endswitch
                                                                    @else
                                                                    <span>DRAFT</span>
                                                                    @endif
                                                                </td>
                                                                <td class="border">{{ $row['fba_shipment_items_count'] }}
                                                                    @php($skuCount += $row['fba_shipment_items_count'])
                                                                </td>
                                                                <td class="border">
                                                                    @if(!empty($row['fba_shipment_items']))
                                                                        {{ $row['fba_shipment_items'][0]['total_units'] }}
                                                                        @php($unitCount += $row['fba_shipment_items'][0]['total_units'] )
                                                                    @else
                                                                        0
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <td class="border"></td>
                                                            <td class="border"></td>
                                                            <td class="border"><b>Total: {{ $skuCount }} <br/>(Unique SKU: {{ $uniqueSkuCount }})</b></td>
                                                            <td class="border"><b>Total: {{ $unitCount  }}</b></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>

                                        @if(!empty($repeatSkuTableData['repeat_skus']))
                                            <div class="col-sm-4">
                                                <table class="table table-sm table-responsive border border-2 shadow text-center align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-primary">Repeated SKU</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if(!empty($repeatSkuTableData['repeat_detail']))
                                                            @foreach($repeatSkuTableData['repeat_detail'] as $row)
                                                                <tr>
                                                                    <td class="border">{{ $row['sku'] }} <br/><span class="badge btn-custom-warning mt-2">Total Qty: {{ $row['total'] }}</span></td>
                                                                    @foreach($row['detail'] as $d)
                                                                        <td class="border">
                                                                            <span @if($row['max_qty'] == $d['quantity']) class="badge btn-custom-warning" @endif>{{ $d['quantity'] }}</span>
                                                                            <br/>
                                                                        <small><span class="">{{ $d['shipment_id'] }}</span></small></td>   
                                                                    @endforeach
                                                                                                                        
                                                                </tr>
                                                            @endforeach
                                                        @endif  
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-10">
                    <div class="col-sm-5">
                        <div class="input-group flex-nowrap input-group">
                           
                            {{-- {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? Request::get('search') :'', ['id' => 'search_data', "autocomplete" => "off", "class" => "form-control px-5", 'placeholder'=>'Product Title, ASIN, SKU, FNSKU']) }} --}}
                            {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? Request::get('search') :'', ['id' => 'search_data', "autocomplete" => "off", "class" => "form-control px-5", 'placeholder'=>'Product Title, ASIN, SKU, FNSKU']) }}
                            <button class="btn btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button" id="search_button"><i class="fa-regular fa-magnifying-glass text-primary" aria-hidden="true"></i></button>
                            <a class="btn btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px refresh" title="Refresh" id="clear_search"><i class="fas fa-sync-alt" aria-hidden="true"></i></a>

                        </div>
                    </div>
                    @if(Request::has('search') && Request::get('search'))
                        <div class="alert alert-warning mt-3 mx-3">
                            Filter by Search: {{ Request::get('search') }}
                            <a href="javascript:void(0)" class="clear_search ms-5" id="clear_search">Reset</a> it.
                        </div>
                    @endif
                </div>

                @if(!empty($mainData))
                    @php ($i = 0)
                    @foreach($mainData as $row)

                        <!-- Each Shipment: Starts -->
                        <div class="mb-20">

                            <h3 class="text-uppercase">Shipment {{ ++$i }}</h3>
                            <div @if(!empty($row['shipmentInfo']['deleted_at'])) class="bg-custom-danger px-10 border border-secondary border-2" @else  class="px-10 border border-secondary border-2" @endif>

                                @if(!empty($row['shipmentInfo']))
                                    <!-- General Shipment details: Starts -->
                                    @if(empty($row['shipmentInfo']['deleted_at']))
                                        <div class="row mt-3">
                                            <div class="col-sm-auto ms-auto text-right-sm">
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Button group with nested dropdown"></div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row mt-3 align-items-center">
                                        <div class="col-sm-3 card card-body bg-secondary w-auto m-2">
                                            <label class="form-label fs-4">Shipment ID: {{ $row['shipmentInfo']['shipment_id'] }}</label>
                                            <label class="form-label fs-4">Shipment Status:
                                                @if(is_numeric($row['shipmentInfo']['shipment_status']))
                                                    @switch($row['shipmentInfo']['shipment_status'])

                                                        @case(0)
                                                            <span>WORKING </span>
                                                            @break

                                                        @case(1)
                                                            <span> READY_TO_SHIP</span>
                                                            @break

                                                        @case(2)
                                                            <span>SHIPPED </span>
                                                            @break

                                                        @case(3)
                                                            <span> RECEIVING</span>
                                                            @break

                                                        @case(4)
                                                            <span> CANCELLED</span>
                                                            @break

                                                        @case(5)
                                                            <span> DELETED</span>
                                                            @break

                                                        @case(6)
                                                            <span> CLOSED</span>
                                                            @break

                                                        @case(7)
                                                            <span>ERROR </span>
                                                            @break

                                                        @case(8)
                                                            <span> IN_TRANSIT</span>
                                                            @break

                                                        @case(9)
                                                            <span>DELIVERED </span>
                                                            @break

                                                        @case(10)
                                                            <span> CHECKED_IN</span>
                                                            @break

                                                        @default
                                                            <span>-</span>
                                                    @endswitch

                                                @else
                                                    <span>Draft Shipment</span>
                                                @endif
                                            </label>
                                            <label class="form-label fs-4">Destination ID: {{ $row['shipmentInfo']['destination_fulfillment_center_id'] }}</label>
                                            <label class="form-label fs-4">Label Type:

                                                @if($row['shipmentInfo']['label_prep_type'] == 1)
                                                    SELLER_LABEL
                                                @elseif($row['shipmentInfo']['label_prep_type'] == 2)
                                                    AMAZON_LABEL
                                                @else
                                                    NO_LABEL
                                                @endif
                                            </label>
                                            <br />
                                        </div>
                                        <div class="col-sm-3 card card-body bg-secondary w-auto m-2">
                                            <div class="row">
                                                <div class="col-sm-4 fs-4">
                                                    <label class="form-label fs-4">Shipment From: </label><br />
                                                    {{ $row['shipmentInfo']['ship_from_addr_name'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_from_addr_line1'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_from_addr_city'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_from_addr_state_province_code'] }} {{ $row['shipmentInfo']['ship_from_addr_district_county'] }} {{ $row['shipmentInfo']['ship_from_addr_country_code'] }} {{ $row['shipmentInfo']['ship_from_addr_postal_code'] }}

                                                </div>
                                                <div class="col-sm-4 fs-4">
                                                    <label class="form-label fs-4">Shipment To: </label><br />
                                                    {{ $row['shipmentInfo']['ship_to_addr_name'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_to_addr_line1'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_to_addr_line2'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_to_addr_city'] }} <br />
                                                    {{ $row['shipmentInfo']['ship_to_addr_state_province_code'] }} {{ $row['shipmentInfo']['ship_to_addr_district_county'] }} {{ $row['shipmentInfo']['ship_to_addr_country_code'] }} {{ $row['shipmentInfo']['ship_to_addr_postal_code'] }}

                                                </div>
                                                <div class="col-sm-4 fs-4">
                                                    <label class="form-label fs-4">Created At: </label><br />
                                                    {{ (!empty($row['shipmentInfo']['created_at'])) ? \Carbon\Carbon::parse($row['shipmentInfo']['created_at'])->format('d-m-Y H:i') : '-' }} <br />
                                                    <label class="form-label fs-4">Updated At: </label><br />
                                                    {{ (!empty($row['shipmentInfo']['updated_at'])) ? \Carbon\Carbon::parse($row['shipmentInfo']['updated_at'])->format('d-m-Y H:i') : '-' }}
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <!-- General Shipment details: Ends -->
                                @endif

                                @if(!empty($row['shipmentProductInfo']))
                                    <!-- Shipment Items Table:Starts -->
                                    <div class="table-responsive my-10 align-items-center">
                                        <table class="table table-row-gray-300 gs-7 gy-4 gx-5 dataTable tab-table border border-2">
                                            <thead>
                                                <tr class="fs-7">
                                                    <th class="text-nowrap w-25px">#</th>
                                                    <th class="text-nowrap">Image</th>
                                                    <th class="w-200px">Product Title</th>
                                                    <th class="text-nowrap">SKU / ASIN / FNSKU</th>
                                                    {{-- <th class="text-nowrap">ASIN</th> --}}
                                                    {{-- <th class="text-nowrap">Seller SKU</th> --}}
                                                    {{-- <th class="text-nowrap">FNSKU</th> --}}

                                                    <!-- Show the column if the shipment status in CLOSED or RECEIVED -->
                                                    @if($row['shipmentInfo']['shipment_status'] == 3 || $row['shipmentInfo']['shipment_status'] == 6)
                                                        <th class="text-nowrap text-center">Shipped Qty</th>
                                                    @else
                                                        <th class="text-nowrap text-center">Sellable Units</th>
                                                    @endif

                                                    <!-- Show the column if the shipment status in CLOSED or RECEIVED -->
                                                    @if($row['shipmentInfo']['shipment_status'] == 3 || $row['shipmentInfo']['shipment_status'] == 6)
                                                        <th class="text-nowrap text-center">Prepped Qty</th>
                                                    @endif

                                                    <!-- Show the column if the shipment status in CLOSED or RECEIVED -->
                                                    @if($row['shipmentInfo']['shipment_status'] == 3 || $row['shipmentInfo']['shipment_status'] == 6)
                                                        <th class="text-nowrap text-center">Received Qty</th>
                                                    @endif

                                                    {{-- <th>Total Qty</th> --}}
                                                    <th class="text-nowrap">Prep Instructions</th>
                                                    <th class="text-nowrap">Prep Owner</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php($j=0)
                                                @php($sumQty = 0)
                                                @foreach($row['shipmentProductInfo'] as $productRow)
                                                    <tr>
                                                        <td class="text-nowrap w-25px border border-1">{{ ++$j }}</td>
                                                        <td class="border border-1">
                                                            @if(!empty($productRow['main_image'])) 
                                                                <a href="{{ $productRow['main_image'] }}" target="_blank"><img src="{{ $productRow['main_image'] }}" width="75" height="75"></a>
                                                            @endif
                                                            {{-- @if(!empty($productRow['main_image'])) --}}
                                                            {{-- @php ($largemageUrl = str_replace('_SL75_', '_SL500_', !empty($productRow['main_image']) ? $productRow['main_image'] : '')) --}}
                                                            {{-- {!! App\Helpers\HtmlHelper::tableImageZoom($productRow['main_image'],$largemageUrl, '',''); !!} --}}
                                                            {{-- @else --}}
                                                            {{-- -
                                                            @endif --}}
                                                        </td>
                                                        <td class="border border-1">{{ !empty($productRow['title']) ? $productRow['title'] : '-' }}</td>
                                                        <td class="border border-1">
                                                            @include('copy-btn', [
                                                                'value' => $productRow['sku'],
                                                                'title' => 'SKU',
                                                            ])
                                                            @include('copy-btn', [
                                                                'value' => $productRow['asin'],
                                                                'title' => 'ASIN',
                                                                'link' => "https://www.amazon.ca/dp/".$productRow['asin']
                                                            ])
                                                            @if ($productRow['fnsku'] != '')
                                                                
                                                                @include('copy-btn', [
                                                                    'value' => !empty($productRow['fnsku']) ? $productRow['fnsku'] : '-',
                                                                    'title' => 'FNSKU',
                                                                ])
                                                            @endif

                                                            @if ($productRow['is_hazmat'] == 1)
                                                                @include('badge', [
                                                                    'badgeArr' => [
                                                                        'title' => 'Hazmat',
                                                                        'bgColor' => 'label-light-danger',
                                                                    ],
                                                                ])
                                                            @endif &emsp13;

                                                            @if ($productRow['is_oversize'] == 1)
                                                                @include('badge', [
                                                                    'badgeArr' => [
                                                                        'title' => 'Oversized',
                                                                        'bgColor' => 'label-light-primary',
                                                                    ],
                                                                ])
                                                            @endif
                                                        </td>
                                                        {{-- <td class="border border-1">

                                                            @if(!empty($productRow['asin']))
                                                            <a href="https://www.amazon.com/dp/{{$productRow['asin']}}" target="_blank" class="product-url asin-link">{{$productRow['asin']}}</a>

                                                            <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinToClipboardButton($(this))"><span class='badge badge-circle badge-primary'> <i class="fa-solid fa-copy text-white"></i></span></a>
                                                            @else
                                                            -
                                                            @endif
                                                        </td> --}}
                                                        {{-- <td class="border border-1">
                                                            {{ $productRow['seller_sku']  }}

                                                            @if(!empty($productRow['seller_sku']))
                                                                <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copySkuToClipboardButton('{{ $productRow['seller_sku'] }}')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a>
                                                            @endif
                                                        </td> --}}
                                                        {{-- <td class="border border-1">
                                                            {{ !empty($productRow['fnsku']) ? $productRow['fnsku'] : '-' }}
                                                        </td> --}}

                                                        <td class="border border-1 text-center">
                                                            {{ $productRow['sellable_asin_qty'] }}
                                                            <span title="Pack of: {{ $productRow['pack_of'] }}"><i class="fa-solid fa-circle-info"></i></span>
                                                            @php($sumQty += $productRow['sellable_asin_qty'])
                                                            @php($totalSkuQty = null)
                                                            <!-- For showing repeated sku total qty -->
                                                            @if(!empty($repeatSkuTableData['repeat_skus']))
                                                                @if(!empty($repeatSkuTableData['repeat_detail']))
                                                                    @foreach($repeatSkuTableData['repeat_detail'] as $repeat)
                                                                    @if($repeat['sku'] == $productRow['seller_sku'])
                                                                        <br/>
                                                                        @php($totalSkuQty = $repeat['total'])
                                                                        <span class="badge btn-custom-warning"><b>Total Qty: {{ $repeat['total'] }}</b></span>
                                                                    @break
                                                                    @endif

                                                                    @endforeach
                                                                @endif
                                                            @endif
                                                            <!-- For showing repeated sku total qty -->

                                                            @if(!empty($row['shipmentInfo']))
                                                                @if(empty($row['shipmentInfo']['deleted_at']))
                                                                    @if($row['shipmentInfo']['shipment_status'] == '0' && $productRow['sellable_asin_qty'] > 0)
                                                                        <a href="javascript:void(0)" title="Edit Plan" class="" data-product_row_id="{{ $productRow['id'] }}" data-original_quantity_shipped="{{ $productRow['original_quantity_shipped'] }}" onclick="editSellableAsinQty(this, {{$productRow['sellable_asin_qty']}}, {{ $totalSkuQty }})"><i class="fa fa-edit"></i></a>
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        </td>

                                                        {{-- <td class="border border-1 text-center">
                                                            {{ $productRow['sellable_asin_qty'] * $productRow['pack_of'] }}
                                                        </td> --}}
                                                        <!-- Show the column if the shipment status in CLOSED or RECEIVED -->
                                                        @if($row['shipmentInfo']['shipment_status'] == 3 || $row['shipmentInfo']['shipment_status'] == 6)
                                                    
                                                            <td class="border border-1 text-center">
                                                            {{ !empty($productRow['fba_prep_detail']) ? $productRow['fba_prep_detail']['done_qty'] : '-' }}
                                                        </td>
                                                        @endif

                                                        <!-- Show the column if the shipment status in CLOSED or RECEIVED -->
                                                        @if($row['shipmentInfo']['shipment_status'] == 3 || $row['shipmentInfo']['shipment_status'] == 6)
                                                        <td class="border border-1 text-center">
                                                            @php($diffQty = bcsub($productRow['sellable_asin_qty'], $productRow['quantity_received']))
                                                            <span @if($diffQty > 0) class="text-danger" style="font-weight:bold" @endif>
                                                            {{ $productRow['quantity_received']  }}
                                                            </span>
                                                        </td>
                                                        @endif

                                                        <td class="border border-1 text-center">{{ !empty($productRow['item_prep_detail']) ? $productRow['item_prep_detail']['prep_instruction_value'] : '-' }}</td>
                                                        <td class="border border-1 text-center">
                                                            @if(!empty($productRow['item_prep_detail']))
                                                                @if($productRow['item_prep_detail']['prep_owner'] == 0)
                                                                    AMAZON
                                                                @else 
                                                                    SELLER
                                                                @endif

                                                            @else 
                                                                SELLER
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr class="text-center border">
                                                    <td colspan="4" class="fs-3"><b>Total SKUs : {{ $j }} </b></td>
                                                    <td colspan="4" class="fs-3"><b>Total Sellable Units : {{ $sumQty }}</b></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- Shipment Items Table: Ends -->
                                @endif
                            </div>

                        </div>
                        <!-- Each Shipment: Ends -->
                    @endforeach
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
// Search in invoice tab
$(document).ready(function () {
    $("#search_button").on("click", function (event) {
        var searchValue = $("#search_data").val();

        if (searchValue != "") {
            var encodedValue = searchValue;
            set_query_para("search", encodedValue);
            window.location.reload();
        }
    });

    $("#search_data").keyup(function (event) {
        var keycode = event.keyCode ? event.keyCode : event.which;
        if (keycode == "13") {
            var searchValue = $(this).val();
            if (searchValue != "") {
                var encodedValue = searchValue;
                set_query_para("search", encodedValue);
                window.location.reload();
            }
        }
    });

    $(document).on("click","#clear_search", function () {
        var sPageURL = window.location.search.substring(1);
        $("#search_data").val("");
        removeURLParameter("search");
        window.location.reload();
    });
});
</script>
    

    {{-- <script type="text/javascript">
        function editSellableAsinQty(elem, qty, totalSkuQty) {
            var item_id = $(elem).attr('data-product_row_id');
            $('#showTotalSkuQty').text('');

            var original_quantity_shipped = $(elem).attr('data-original_quantity_shipped');

            $('#updateProductQtyModal').find('#product_item_id').val(item_id);
            $('#updateProductQtyModal').find('#new_sellable_asin_qty').val(qty);
            $('#updateProductQtyModal').find('#old_sellable_asin_qty').val(original_quantity_shipped);
            $('#updateProductQtyModal').find('#new_sellable_asin_qty_error').text('');

            if(totalSkuQty != null){
                $('#showTotalSkuQty').text('Total SKU Qty: '+totalSkuQty);
            }
           
            $('#updateProductQtyModal').modal('show');
        }

        var is_shipment_qty_update = false;
        $(document).on('submit','form#update_shipment_product_qty_form',function(e)
        {
            $('#updateProductQtyModal').find('#new_sellable_asin_qty_error').text('');
            
            if (!is_shipment_qty_update) {

                is_shipment_qty_update = true;

                $.ajax({
                    url: $('#update_shipment_product_qty_form').attr('action'),
                    type: "POST",
                    data: $('#update_shipment_product_qty_form').serialize(),
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        show_loader();
                    },
                    success: function (response) {
                        hide_loader();
                        is_shipment_qty_update = false;

                        if (typeof response.type !== 'undefined' && response.type == 'success') {
                            displaySuccessMessage(response.message);
                        } else if (typeof response.type !== 'undefined' && response.type == 'error') {
                            displayErrorMessage(response.message);
                        }

                        window.location.reload();
                    },
                    error: function (xhr, err) {
                        hide_loader();
                        is_shipment_qty_update = false;

                        if(xhr.status === 422) {
                            var errors = $.parseJSON(xhr.responseText);
                            $.each(errors.errors, function (key, val) {
                                $("#update_shipment_product_qty_form").find("span#"+key+"_error").text(val[0]);
                            });

                        } else if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    },
                });
            }
        });

        // copy asin to clipbord
        function copyAsinToClipboardButton(elem) {
            var copyText = $(elem).prev('.asin-link').text();
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("ASIN copied");
        }

        //copy SKU to clipboard
        function copySkuToClipboardButton(copyText){
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(copyText).select();
            document.execCommand("copy");
            $temp.remove();

            displaySuccessMessage("SKU copied");
        }
    </script> --}}
{{-- <script src="{{ asset('js/filter.js') }}" type="text/javascript"></script> --}}
@endsection