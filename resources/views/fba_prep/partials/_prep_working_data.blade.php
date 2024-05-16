@if(isset($shipmentItems) && count($shipmentItems) > 0)
@php ($i = ($shipmentItems->perPage() * ($shipmentItems->currentPage() - 1))) @endphp

@foreach($shipmentItems as $k => $item)
    <tr id="row_id_{{ $item['id'] }}" class="border-top border-gray-300" @if($item['skus_prepped']=="3") style="background: #CCEBFD" @elseif($item['skus_prepped']=="2") style="background: #D3F4CE" @elseif($item['skus_prepped']=="1") style="background: #FFF8DE" @else style="background: #FFE8E8" @endif>
        
        <input type="hidden" name="fba_shipment_item_id[]" value="{{ $item['id'] }}">
    
        {{-- @if(isset($item->amazonData) && !empty($item->amazonData)) --}}
            @php ($productAsin = isset($item->amazonData['asin']) && !empty($item->amazonData['asin']) ? $item->amazonData['asin'] : NULL) @endphp

            <?php 
                // $productImages = App\Models\ProductImage::where('asin',$productAsin)->get()->toArray();
                
                $amzSku = isset($item->amazonData['sku']) && !empty($item->amazonData['sku']) ? $item->amazonData['sku'] : '';

                // if(!empty($amzSku)){
                //     $amazonProductData = App\Models\AmazonProduct::where('sku',$amzSku)->get()->pluck('id')->toArray();
                //     $amaz_product_id = $amazonProductData[0];
                // }else{
                    $amaz_product_id = '';
                // }
            ?>
        {{-- @endif --}}

        @php 
            $discripencyQt = isset($item['qty']) && !empty($item['qty']) ? $item['qty'] - $item['done_qty'] : 0;
        @endphp

        {{-- <td class="min-w-30px">{{ ++$i }}</td> --}}
        <td class="w-20px">{{ ++$i }}</td>
        {{-- <td class="text-nowrap w-15px pr-custom"> --}}
        <td class="text-nowrap w-350px pr-custom">
            <div class="d-flex">
                <div class="me-4 position-relative" style="text-align: center;">
                    @if(isset($item->amazonData) && !empty($item->amazonData))
                        @php ($largemageUrl = str_replace('_SL75_', '_SL500_', isset($item->amazonData['main_image']) && !empty($item->amazonData['main_image']) ? $item->amazonData['main_image'] : ''))
                        
                        {!! App\Services\CommonService::tableImageZoom($item->amazonData['main_image'],$largemageUrl, '',''); !!}
        
                    @else
                        {!! App\Services\CommonService::tableImageZoom(asset('media/no-image.jpeg'),asset('media/no-image.jpeg'), '',''); !!}
                    @endif

                </div>
            
                <div class="multidata-td text-start position-relative fs-6 flex-fill">
                    @if(isset($item->amazonData) && !empty($item->amazonData))
                        <div class="row mb-2">
                        <div class="col-sm-12 text-wrap" title="{{ $item->amazonData['title'] }}">
                            <span class="">{{ empty($item->amazonData['title']) ? '-' : $item->amazonData['title'] }}</span>
                        </div>
                        </div>
        
                        <div class="row mb-2">
                            <div class="col-4"><span class="text-gray-500">ASIN: </span></div>
                            <div class="col-8 text-start fw-500"> <a href="https://www.amazon.com/dp/{{$item->amazonData['asin']}}" target="_blank" class="product-url asin-link">{{ empty($item->amazonData['asin']) ? '-' : $item->amazonData['asin'] }}</a>&nbsp;&nbsp;
                                <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinToClipboardButton($(this))"><span class='badge badge-circle badge-primary'> <i class="fa-solid fa-copy text-white"></i></span></a>
                            </div>
                        </div>
        
                        <div class="row mb-2">
                            <div class="col-4"><span class="text-gray-500">SKU: </span></div>
                            <div class="col-8 text-start fw-500 ">
                                {{ empty($item->amazonData['sku']) ? '-' : $item->amazonData['sku'] }}

                                <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copySkuToClipboardButton('{{ empty($item->amazonData['sku']) ? '-' : $item->amazonData['sku'] }}')"><span class="badge badge-circle badge-primary"> <i class="fa-solid fa-copy text-white"></i></span></a>

                            </div>
                        </div>
        
                        <div class="row mb-2">
                            <div class="col-4"><span class="text-gray-500">FNSKU: </span></div>
                            <div class="col-8 text-start fw-500 ">{{ empty($item->amazonData['fnsku']) ? '-' : $item->amazonData['fnsku'] }}</div>
                        </div>
                    @endif    
                    {{-- <div class="row mb-2">
                        <div class="col-4"><span class="text-gray-500">UPC: </span></div>
                        <div class="col-8 text-start fw-500 ">
                            <?php
                            if (isset($item->product_analyzer_upc) && !empty($item->product_analyzer_upc)) {
                                echo $item->product_analyzer_upc."<br>";
                            } elseif(!empty($item->amazonData['upc'])){ 
                                $upcArr = explode(',', $item->amazonData['upc']);
                                if(isset($upcArr) && count($upcArr) > 0){
                                    foreach($upcArr as $val){
                                        echo $val."<br>";
                                    }
                                }
                            }else{
                                echo "-";
                            }
                            ?>
                        </div> 
                    </div> --}}
                    {{-- <div class="row mb-2">
                        <?php
                            $poNumbr = '';
                            if (!empty($item['supplierInfos'])) {
                                $poNumbr = $item['supplierInfos'][0]['po_number'];
                            } elseif(!empty($item->po_number)) {
                                $poNumbr = $item->po_number;
                            }
                        ?>
                        <div class="col-8"><span class="text-gray-500">Item Code: @if(empty($poNumbr) && empty($item['item_code'])) <span style="color:#000;padding-left: 30px;">{{ "-" }}</span> @else <span  style="color:#000;padding-left: 30px;">{{ $item['item_code'] }} </span> @endif</span></div>
                    </div> --}}
                    {{-- <div class="row mb-2">
                        <div class="col-8"><span class="text-gray-500">ASIN Weight: @if(empty($item['asin_weight'])) <span style="color:#000;padding-left: 30px;">{{ "-" }}</span> @else <span  style="color:#000;padding-left: 30px;">{{ $item['asin_weight'] }} </span> @endif</span></div>
                    </div> --}}
                </div>
            </div>
        
        </td> 
        {{-- <td class="text-nowrap w-500px pr-custom">
            <div class="h-100 d-flex flex-column">
                <div class="multidata-td text-start position-relative fs-6">
                    <div class="row mb-0">
                        <div class="col-6 text-gray-500">Supplier:</div>
                        <?php
                            $supplierName = '-';
                            if (!empty($item['supplierInfos'])) {
                                $supplierName = $item['supplierInfos'][0]['supplier_name'];
                            } elseif(!empty($item->supplier_name)) {
                                $supplierName = $item->supplier_name;
                            }
                        ?>
                        <div class="col-6 text-start fw-500" style="white-space: initial;">{{ $supplierName }}</div>
                    </div>
                
                    <div class="row mb-0">
                        <div class="col-6 text-gray-500">PO Number:</div>
                        <?php
                            $poNumber = '-';
                            if (!empty($item['supplierInfos'])) {
                                $poNumber = $item['supplierInfos'][0]['po_number'];
                            } elseif(!empty($item->po_number)) {
                                $poNumber = $item->po_number;
                            }
                        ?>
                        <div class="col-6 text-start  fw-500" style="white-space: initial;">
                            @if(auth()->user()->role==1) 
                                <a href='{{ url("/purchase-orders/view/".$item['poId'].'?search='.base64_encode($productAsin)) }}' target="_blank">{{ $poNumber }}</a>
                            @else
                                {{ $poNumber }}
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-0">
                        <div class="col-6 text-gray-500">Pallet ID:</div>
                        <?php
                            $palletId = '-';
                            if (!empty($item['supplierInfos'])) {
                                $palletId = $item['supplierInfos'][0]['pallet_id'];
                            } elseif(!empty($item->pallet_id)) {
                                $palletId = $item->pallet_id;
                            }
                        ?>
                        <div class="col-6 text-start  fw-500">{{ $palletId }}</div>
                    </div>
                </div>
            </div>
        </td> --}}
        <td class="text-nowrap w-100px pr-custom">
            <div class="">
                <div class="multidata-td text-start position-relative fs-6">
                    <div class="row mb-0">
                        <div class="col-7 text-gray-500">Case Pack: </div>
                        <div class="col-5 text-start fw-500">
                            <?php $casePack = ''; ?>
                            @if(!empty($item->case_pack))
                                <?php $casePack = $item->case_pack; ?>
                                {{ $item->case_pack }}
                            @elseif(!empty($item->amazonData) && !empty($item->amazonData->amazon_product_case_pack))
                                <?php $casePack = $item->amazonData->amazon_product_case_pack; ?>
                                {{ $item->amazonData->amazon_product_case_pack }}
                                {{-- <i class="fa fa-info-circle" aria-hidden="true" title="{{ !empty($item->amazonData) && !empty($item->amazonData->casePackSupplier) ?  $item->amazonData->casePackSupplier->name : '' }}"></i> --}}
                            @else
                                {{ '-' }}
                            @endif
                        </div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-7 fw-700"><h4>Pack of: </h4></div> 
                        <div class="col-5 text-start fw-500">
                            <?php $aPack = ''; ?>
                            @if(!empty($item->a_pack))
                                <?php $aPack = $item->a_pack; ?>
                                {{ App\Services\CommonService::getAPackFormatedValue($item->a_pack) }}
                            @elseif(!empty($item->amazonData) && !empty($item->amazonData->amazon_product_a_pack))
                                <?php $aPack = $item->amazonData->amazon_product_a_pack; ?>
                                {{ App\Services\CommonService::getAPackFormatedValue($item->amazonData->amazon_product_a_pack) }}
                            @else
                                {{ '-' }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </td>
        <td class="text-nowrap w-50px pr-custom">
            <div class="h-100">
                <textarea id="prep_note_{{ $item['id'] }}" title="Prep Note" name="prep_note[]" class="form-control border-gray-300 w-150px" style="height: inherit;" @if($prepType=='EditPrep') onchange="getUpdateNotes({{ $item['id'] }}, 'Prep','{{ isset($productAsin) && !empty($productAsin) ? $productAsin : NULL }}')" @else {{ "readonly" }} @endif>{{ $item['prep_note'] }}</textarea>
            </div>
        </td>
        <td class="text-nowrap pr-custom text-center" width:="5%">
            <div class="d-flex h-100 align-items-center justify-content-center" id="totalQt_{{ $item['id']}}">{{ !empty($item['qty']) ? $item['qty'] : 0 }}
            </div>
        </td>
        <td class="text-nowrap pr-custom text-center" width:="5%">
            <div class="d-flex h-100 align-items-center justify-content-center" id="doneQt_{{ $item['id']}}"> {{ !empty($item['done_qty']) ? $item['done_qty'] : 0 }}</div>
        </td>
        <td class="text-nowrap w-50px pr-custom" width:="5%">
            <div class="d-flex h-100 ">
                <div class="col-7 text-center align-self-center" id="disQt_{{ $item['id']}}">
                    <?php 
                        $discrepancy = 0;
                        if(!empty($item['qty'])) {
                            $discrepancy = $item['done_qty'] - $item['qty'];
                        }
                    ?>

                    {{ ($discrepancy > 0) ? '+'.$discrepancy : $discrepancy }}
                </div>
        
                <div class="col-5 text-end">
                    <a href="javascript:void(0);" class="float-end printIcn" data-id="{{ $item['id']}}" data-case_pack="{{ $casePack }}" data-a_pack="{{ $aPack }}">
                        <i class="fa-solid fa-print fa-lg text-success" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </td>

        <td class="text-nowrap w-50px pr-custom">
            <div class="h-100">
            <textarea id="discrepancy_note_{{ $item['id'] }}" title="Discrepancy Note" name="discrepancy_note" class="form-control border-gray-300 w-150px" style="height: inherit;" @if($prepType=='EditPrep') onchange="getUpdateNotes({{ $item['id'] }}, 'Discrepancy','{{ isset($productAsin) && !empty($productAsin) ? $productAsin : NULL }}')" @else {{ "readonly" }} @endif>{{ $item['discrepancy_note'] }}</textarea>
            </div>
        </td>
        {{-- <td class="text-nowrap w-50px pr-custom">
            <div class="h-100">
                <textarea id="warehouse_notes_{{ $item['id'] }}" title="Warehouse Note" name="warehouse_notes" class="form-control border-gray-300 w-150px" style="height: inherit;" @if($prepType=='EditPrep') onchange="getUpdateNotes({{ $item['id'] }}, 'Warehouse','{{ isset($productAsin) && !empty($productAsin) ? $productAsin : NULL }}')" @else {{ "readonly" }} @endif>@if(isset($item['prep_warehouse_notes']) && !empty($item['prep_warehouse_notes'])) {{ $item['prep_warehouse_notes'] }} @elseif(isset($item['warehouse_notes']) && !empty($item['warehouse_notes'])) {{ $item['warehouse_notes'] }}  @endif</textarea>
            </div>
        </td> --}}
        <td class="text-nowrap pr-custom d-flex h-100" width:="5%">
                <div class="dropdown dropstart m-auto h-100 d-flex" id="dropStart_{{ $item['id'] }}" data-sku="{{ !empty($item->amazonData) ? $item->amazonData['sku'] : '' }}" data-id="{{ $item['id'] }}" data-urls="{{ url('fba-shipment/prep-detail-log/'.base64_encode($shipment->shipment_id).'/'.base64_encode($item->id).'/'.base64_encode($productAsin)) }}">
                    <a class="d-inline-block dropdown-toggle m-auto prep-details-dropdown text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="true">
                        <i class="text-dark fa-solid fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu py-0" id="drawp_{{ $item['id'] }}">
                        <li class="border-bottom"><a class="dropdown-item py-3" href="#" id="single_view_button" data-id="{{ $item['id']}}"><i class="text-dark me-2 fa-solid fa-box"></i> Box Label Listing</a></li>

                        @if($discripencyQt > 0 && ($prepType == 'EditPrep'))
                            <li id="row3in1"><a class="dropdown-item py-3" href="#" id="printBoxLabelsModal" data-id="{{ $item['id']}}" data-text="3D"><i class="text-dark me-2 fa-solid fa-print "></i>Printing 3 in 1 Box Labels</a></li>
                        @endif 
                
                   

                    </ul>
            </div>
        </td>
    </tr>
@endforeach
@endif


@if(count($shipmentItems) == 0 && Request::has('product_info_search') && Request::get('product_info_search') != '')
<tr><td></td><td></td><td></td><td></td><td class="text-nowrap w-100px pr-custom" style="font-weight:600;font-size: 16px;">No records found</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
@endif