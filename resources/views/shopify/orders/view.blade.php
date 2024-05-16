@extends('layouts.app')
@section('title', 'Shopify Order')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shopify-orders.index') }}">{{ __('Shopify Order List') }}</a>
</li>
<li class="breadcrumb-item">{{ __('View Shopify Order') }}</li>
@endsection

@section('content')

<x-forms.parent>

    <div class="row">
        <div class="tab-content border rounded p-3 border-secondary shadow-lg">
            <div class="row align-items-center">
                <div class="row px-4 fs-3 mb-5">
                    <div class=" col">Order ID: <strong class="fw-700">{{$orders->shopify_unique_id}}</strong></div>
                    <div class=" col text-end">Order Date: <strong class="fw-700">{{Carbon\Carbon::parse($orders->order_date)->format('d-m-Y H:i:s')}}</strong></div>
                </div>
                <div class="d-flex flex-wrap px-4">
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Total Price: </span>
                            <strong class="fw-700">{{config('constants.currency_symbol').$orders->total_price}}</strong>
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Total SKUs: </span>
                            <strong class="fw-700">{{$orders->items_count}}</strong>
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Total Quantity: </span>
                            <strong class="fw-700">{{$totalQty}}</strong>
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Total Discounts: </span>
                            <strong class="fw-700">{{config('constants.currency_symbol').$orders->total_discounts}}</strong>
                        </p>
                    </div>
                    @if (is_array($orders->orderDetails) && isset($orders->orderDetails[0]) && !empty($orders->orderDetails))
                        @php
                            $unserialized = unserialize($orders->orderDetails[0]['shopify_customer']);
                            $customer_name = $unserialized['first_name'].' '.$unserialized['last_name'];
                        @endphp
                    @endif
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Customer Name: </span>
                            <strong class="fw-700">{{isset($customer_name) ? $customer_name : '' }}</strong>
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Shipping Address: </span>
                            @if (isset($orders->shipping_address_address1) && !empty($orders->shipping_address_address1))
                            <strong class="fw-700">{{$orders->shipping_address_address1. ' '. $orders->shipping_address_address2. ',' . $orders->shipping_address_city. ','}}</strong><br>
                            <strong class="fw-700">{{$orders->shipping_address_zip.','.$orders->shipping_address_country}}</strong>
                            @else
                            <strong class="fw-700"> - </strong>
                            @endif
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Billing Address: </span>
                            @if (isset($orders->billing_address_address1) && !empty($orders->billing_address_address1))
                            <strong class="fw-700">{{$orders->billing_address_address1. ' '. $orders->billing_address_address2. ',' . $orders->billing_address_city. ','}}</strong><br>
                            <strong class="fw-700">{{$orders->billing_address_zip.','.$orders->billing_address_country}}</strong>
                            @else
                            <strong class="fw-700"> - </strong>
                            @endif
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Email: </span>
                            @if (isset($orders->buyer_email) && !empty($orders->buyer_email))
                            <strong class="fw-700">{{$orders->buyer_email}}</strong>
                            @else
                            <strong class="fw-700"> - </strong>
                            @endif
                        </p>
                    </div>
                    <div class="me-5 pe-4 my-1">
                        <p class="mb-0" style="letter-spacing: 0.05em;">
                            <span> Phone: </span>
                            @if (isset($orders->shipping_address_phone) && !empty($orders->shipping_address_phone))
                            <strong class="fw-700">{{$orders->shipping_address_phone}}</strong>
                            @else
                            <strong class="fw-700"> - </strong>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-lists>
        <div class="container-fluid py-5 mt-2">

            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-regular fa-bars-filter fs-4"></i>
                    </x-actions.button>
                    <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter" style="color: #009ef7; display: none;" />
                </div>

            </div>
          
            <!-- Show selected filter in alert warning box -->
            <x-applied-filters>
                <x-filters.filter_msg title="Min QTY" parent_id="total-min-qty-span" child_id="total-min-qty-data"/>
                <x-filters.filter_msg title="Max QTY" parent_id="total-max-qty-span" child_id="total-max-qty-data"/>
                <x-filters.filter_msg title="Min Total Price" parent_id="total-min-total-price-span" child_id="total-min-total-price-data"/>
                <x-filters.filter_msg title="Max Total Price" parent_id="total-max-total-price-span" child_id="total-max-total-price-data"/>
                <x-filters.filter_msg title="Min Discount" parent_id="total-min-discount-span" child_id="total-min-discount-data"/>
                <x-filters.filter_msg title="Max Discount" parent_id="total-max-discount-span" child_id="total-max-discount-data"/>
            </x-applied-filters>

            @php
                $tableId = 'shopify-order-items-table'
            @endphp
        
        </div>

        {{ $dataTable->table(['id' => 'shopify-order-items-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}

    </x-lists>

</x-forms.parent>

 <!-- Filter Box -->
 <x-filters>
    <label class="mt-3">Quantity</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min QTY" name="total_min_qty" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max QTY" name="total_max_qty" type="number" />
        <span class="error" id="total_max_qty_err" style="color: #F1416C;"></span>
    </div>

    <label>Price</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Price" name="total_min_total_price" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Price" name="total_max_total_price" type="number" />
        <span class="error" id="total_max_total_price_err" style="color: #F1416C;"></span>
    </div>

    <label>Total Discount</label>
    <div class="d-flex align-items-baseline">
        <x-filters.input label="Min Discount" name="total_min_discount" type="number" />
        <div class="mx-3">TO</div>
        <x-filters.input label="Max Discount" name="total_max_discount" type="number" />
        <span class="error" id="total_max_discount_err" style="color: #F1416C;"></span>
    </div>
</x-filters>

@endsection

@section('page-script')
{{ $dataTable->scripts() }}

<script>
    const tableId = "{{ $tableId }}";

    const filterList = [
        ['total_min_qty', 'total-min-qty-span', 'total-min-qty-data', 'number'],
        ['total_max_qty', 'total-max-qty-span', 'total-max-qty-data', 'number', {'validation': {
            'compare_field': 'total_min_qty',
            'message': 'The max qty field must be greater than or equal to min qty.'
        }}],
        ['total_min_total_price', 'total-min-total-price-span', 'total-min-total-price-data', 'number'],
        ['total_max_total_price', 'total-max-total-price-span', 'total-max-total-price-data', 'number', {'validation': {
            'compare_field': 'total_min_total_price',
            'message': 'The max total price must be greater than or equal to min total price.'
        }}],
        ['total_min_discount', 'total-min-discount-span', 'total-min-discount-data', 'number'],
        ['total_max_discount', 'total-max-discount-span', 'total-max-discount-data', 'number', {'validation': {
            'compare_field': 'total_min_discount',
            'message': 'The max discount must be greater than or equal to min discount.'
        }}],
    ];
</script>
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop
