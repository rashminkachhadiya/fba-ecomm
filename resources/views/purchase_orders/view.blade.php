@extends('layouts.app')
@section('title', 'Purchase Order Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('purchase_orders.index') }}">{{ __('Purchase Order List') }}</a></li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">
        <div class="row align-items-center gy-3 gx-3 position-relative">
            <h3>Purchase Order : <span class="text-primary">{{ $poDetails->po_number }}</span></h3>
        </div>
        <div class="row mt-10">
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Order Date" />
                    <x-datepicker>
                        {{ Form::text('po_order_date', isset($poDetails) && $poDetails->po_order_date ? $poDetails->po_order_date : date('m-d-Y'), ['id' => 'po_order_date', 'class' => 'form-control cursor-not-allowed', 'readonly' => true]) }}
                        <x-datepicker.calendar />
                    </x-datepicker>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Expected Delivery Date" />
                    <x-datepicker>
                        {{ Form::text('expected_delivery_date', isset($poDetails) && $poDetails->expected_delivery_date ? $poDetails->expected_delivery_date : '', ['id' => 'expected_delivery_date', 'class' => 'form-control cursor-not-allowed', 'readonly' => true]) }}
                        <x-datepicker.calendar />
                    </x-datepicker>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Order Note" />
                    {{ Form::textarea('order_note', isset($poDetails) && $poDetails->order_note ? $poDetails->order_note : '', ['id' => 'order_note', 'class' => 'form-control cursor-not-allowed', 'readonly' => true, 'rows' => 1]) }}
                </div>
            </div>
        </div>
    </div>

    @if (isset($purchaseOrder) && !empty($purchaseOrder) && $purchaseOrder > 0 && $purchaseOrder[0]['po_id'] != null)
    <div class="row">
        <table class="align-middle table table-row-bordered purchase-order-table table-row-gray-300 gs-7 gy-4 gx-7 dataTable">
            <thead>
                <thead>
                    <tr class="fs-7">
                        <th>Image</th>
                        <th>Title</th>
                        <th>SKU / ASIN</th>
                        <th>Supplier SKU</th>
                        <th>Unit Price</th>
                        <th>Order Qty</th>
                        <th>Total Price</th>
                        <th>Received Qty</th>
                        <th>Received Price</th>
                        <th>Difference Qty</th>
                        <th>Difference Price</th>
                    </tr>
                </thead>
            <tbody>
                @foreach ($purchaseOrder as $value)
                <tr>
                    <td class="text-nowrap"><img src="{{ $value['main_image']  }}" width="65" height="65" alt="image"></td>
                    <td class="text-nowrap" title="{{ $value['title'] }}">
                        {{ \Illuminate\Support\Str::limit(strip_tags($value['title']), 30, '...') }}
                    </td>
                    <td class="text-nowrap">{{ $value['sku'] }}<a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copySkuToClipboardButton('{{$value['sku']}}');">
                            <span class="badge badge-circle badge-primary">
                                <i class="fa-solid fa-copy text-white"></i>
                            </span></a>
                        <br><br><a href="https://www.amazon.com/dp/{{$value['asin']}}" target="_blank" class="product-url asin-link">{{$value['asin']}}</a>
                        <a href="javascript:void(0)" data-url="" class="menu-link me-1" title="Copy to clipboard" onclick="copyAsinToClipboardButton('{{$value['asin']}}');">
                            <span class="badge badge-circle badge-primary">
                                <i class="fa-solid fa-copy text-white"></i>
                            </span></a>
                    </td>
                    <td class="text-nowrap">{{ $value['supplier_sku'] }}</td>
                    <td class="text-nowrap">{{ $value['unit_price'] }}</td>
                    <td class="text-nowrap">{{ $value['order_qty'] }}</td>
                    <td class="text-nowrap">{{ $value['total_price'] }}</td>
                    <td class="text-nowrap">{{ $value['received_qty'] }}</td>
                    <td class="text-nowrap">{{ $value['received_price'] }}</td>
                    <td class="text-nowrap">{{ $value['difference_qty'] }}</td>
                    <td class="text-nowrap">{{ $value['difference_price'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <hr>
    @endif
</x-lists>
@endsection
@section('page-script')
<script>
    function copySkuToClipboardButton(copyText) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(copyText).select();
        document.execCommand("copy");
        $temp.remove();

        displaySuccessMessage("SKU copied");
    }

    function copyAsinToClipboardButton(copyText) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(copyText).select();
        document.execCommand("copy");
        $temp.remove();

        displaySuccessMessage("ASIN copied");
    }
</script>
@stop