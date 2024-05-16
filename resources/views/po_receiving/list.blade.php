@extends('layouts.app')
@section('title', 'Purchase Order Received')
@section('breadcrumb')
<li class="breadcrumb-item">{{ __('Purchase Order Received') }}</li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">
        <div class="row align-items-center gy-3 gx-3 position-relative">
            <h3>Purchase Order : <span class="text-primary">{{ $poName }}</span></h3>
        </div>

        <div class="row align-items-center gy-3 gx-3 position-relative mt-4">
            <input type="hidden" id="po_listing_url" value="{{route('purchase_orders.index')}}">
            <x-search-box input_id="search" />

            <div class="col-sm-auto ms-auto text-right-sm" style="display: flex">
                {{ Form::open(['route' => ['update-po-status'], 'id' => 'update_po_status_form', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
                <input type="hidden" name="po_id" value="{{ request()->segment(2) }}">
                <input type="hidden" name="po_status" value="Partial Received">
                <x-forms.button name="Received Partially" />
                {{ Form::close()}}

                {{ Form::open(['route' => ['update-po-status'], 'id' => 'update_status_complete_received', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}
                <input type="hidden" name="po_id" value="{{ request()->segment(2) }}">
                <input type="hidden" name="po_status" value="Received">
                <x-forms.button name="Received Completely" />
                {{ Form::close()}}
            </div>

        </div>

        @php
        $tableId = 'po-received'
        @endphp
        {{ $dataTable->table(['id' => $tableId, 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}

        @include('po_receiving.add_discrepancy_modal')
        @include('po_receiving.edit_discrepancy_modal')
</x-lists>

@endsection
@section('page-script')

{{ $dataTable->scripts() }}
{!! JsValidator::formRequest('App\Http\Requests\DiscrepancyRequest', '#discrepancy_modal_form'); !!}
{!! JsValidator::formRequest('App\Http\Requests\DiscrepancyRequest', '#edit_discrepancy_modal_form'); !!}

<script>
    const tableId = "{{ $tableId }}";
    let poId = 0;
    let url = "{{ route('po_receiving.update', ['id' => ':poId']) }}";

    // $(document).ready(function () {
    //     $(document).on('click keyup',".received_qty", function(event){

    //         const receivedQty = $(this).val();
    //         if(receivedQty == '')
    //         {
    //             return;
    //         }

    //         const orderQty = $(this).closest('tr').find('.order_qty').text();
    //         const calculateQty = orderQty - receivedQty;
    //         $(this).closest('tr').find('.difference_qty').text(calculateQty);

    //         const unitPrice = $(this).closest('tr').find('.unit_price').text();
    //         const priceDiff = (unitPrice * calculateQty).toFixed(2);
    //         $(this).closest('tr').find('.difference_price').text(priceDiff);

    //         const receivedPrice = (receivedQty * unitPrice).toFixed(2);
    //         $(this).closest('tr').find('.received_price').text(receivedPrice);

    //         const poId = $(this).attr('id');
    //         let url = "{{ route('po_receiving.update', ['id' => ':poId']) }}";
    //         url = url.replace(':poId', poId);

    //         $.ajax({
    //             url: url,
    //             type: 'PUT',
    //             data: { 
    //                 unitPrice: unitPrice,
    //                 orderQty: orderQty,
    //                 totalPrice: (orderQty * unitPrice).toFixed(2),
    //                 receivedQty: receivedQty,
    //                 receivedPrice: receivedPrice,
    //                 differenceQty: calculateQty,
    //                 differencePrice: priceDiff
    //             },
    //             headers: {
    //                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //             },
    //             beforeSend: function () {
    //                 // show_loader();
    //             },
    //             success: function (res) {
    //                 // hide_loader();
    //             },
    //             error: function (xhr, err) {
    //                 // hide_loader();
    //                 if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
    //                     if (typeof xhr.responseJSON.errors != "undefined") {
    //                         commonFormErrorShow(xhr, err);
    //                     } else {
    //                         displayErrorMessage(xhr.responseJSON.message);
    //                     }
    //                 } else {
    //                     displayErrorMessage(xhr.responseJSON.errors);
    //                 }
    //             }
    //         });
    //     });
    // });
</script>
<script src="{{ asset('js/po_receiving/form.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop