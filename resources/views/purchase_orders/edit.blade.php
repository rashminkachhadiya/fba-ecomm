@extends('layouts.app')
@section('title', 'Purchase Order Management')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('purchase_orders.index') }}">{{ __('Purchase Order List') }}</a></li>
<li class="breadcrumb-item">{{ __('Edit Purchase Order') }}</li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">
        <div class="row align-items-center gy-3 gx-3 position-relative">
            <h3>Purchase Order : <span class="text-primary">{{ $poDetails->po_number }}</span></h3>
        </div>
        {{ Form::open(['route' => ['purchase_orders.update', ['purchase_order' => $poDetails->id]], 'name' => 'edit_po', 'id' => 'edit_po_form', 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
        {{ Form::hidden('po_id', $poDetails->id, ['id' => 'po_id']) }}
        {{ Form::hidden('supplier_id', $poDetails->supplier_id, ['id' => 'supplier_id']) }}
        {{ Form::hidden('', route('po-item-bulk-delete'), ['id' => 'bulk_delete_url']) }}

        <div class="row mt-3">
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Order Date" />
                    <x-datepicker>
                        {{ Form::text('po_order_date', $poDetails->po_order_date, ['id' => 'po_order_date', 'class' => 'form-control cursor-not-allowed', 'readonly' => true]) }}
                        <x-datepicker.calendar />
                    </x-datepicker>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Expected Delivery Date" />
                    <x-datepicker>
                        {{ Form::text('expected_delivery_date', isset($poDetails) && $poDetails->expected_delivery_date ? $poDetails->expected_delivery_date : '', ['id' => 'expected_delivery_date', 'class' => 'form-control', 'name' => 'expected_delivery_date']) }}
                        <x-datepicker.calendar />
                    </x-datepicker>

                    @error('expected_delivery_date')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-5">
                    <x-forms.label title="Order Note" />
                    {{ Form::textarea('order_note', isset($poDetails) && $poDetails->order_note ? $poDetails->order_note : '', ['id' => 'order_note', 'class' => 'form-control', 'rows' => 1]) }}
                </div>
            </div>

            <x-search-box input_id="search" />

            <!-- Bulk option start -->
            <x-bulk-option>
                <x-bulk-option.bulk_select_option value="0" title="Bulk Option" />
                <x-bulk-option.bulk_select_option value="1" title="Delete" />
            </x-bulk-option>
            <!-- Bulk option end -->

            <div class="col-sm-auto ms-auto text-right-sm">
                <x-actions.button url="javascript:void(0)" id="add_po_item_modal" data-url="{{ route('po-item-list') }}" class="btn btn-sm btn-primary" title="Add Product">
                    <i class="fa-regular fa-plus"></i>
                </x-actions.button>
            </div>

        </div>
        {{ Form::close() }}
        <!-- Show selected filter in alert warning box -->
        <x-applied-filters>
            <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" />
        </x-applied-filters>
    </div>
    @php
    $tableId = 'edit-po-table';
    @endphp
    {{ Form::open(['route' => ['update-po-item-order-qty'], 'name' => 'update_po_item_order_qty', 'id' => 'update_po_item_order_qty', 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    {{ $dataTable->table(['id' => 'edit-po-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    {{ Form::close() }}

</x-lists>
<!-- Filter Box -->

@include('purchase_orders.add_po_item_modal')

@endsection

@section('page-script')

{{ $dataTable->scripts() }}
{!! JsValidator::formRequest('App\Http\Requests\PurchaseOrderRequest', '#edit_po_form') !!}
{!! JsValidator::formRequest('App\Http\Requests\SupplierProductsRequest', '#update_po_item_order_qty') !!}
<script src="{{ asset('js/purchase_order/po_item.js') }}" type="text/javascript"></script>

<script>
    const tableId = "{{ $tableId }}";
</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

<script>
    $(document).ready(function() {

        $("#expected_delivery_date").daterangepicker({
            opens: 'left',
            singleDatePicker: true,
            autoUpdateInput: false,
            minYear: 2021,
            maxYear: 2032,
            showDropdowns: true,
            "autoApply": true,
            locale: {
                format: "DD-MM-YYYY",
            },
        }).off('focus');

        $("#expected_delivery_date").on("apply.daterangepicker", function(ev, picker) {
            var date = picker.startDate.format("DD-MM-YYYY");
            $(this).val(date);
            onUpdateDeliveryDate(date);
        });


        function onUpdateDeliveryDate(date) {
            var targetElement = $('#expected_delivery_date');
            if (targetElement.valid()) {
                $.ajax({
                    url: $('form#edit_po_form').attr('action'),
                    type: 'PUT',
                    data: {
                        'updateFor': 'update_expected_delivery_date',
                        'selected_date': date,
                    },
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        displaySuccessMessage(data.message);
                    },
                    error: function(xhr, err) {
                        if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON
                            .message.length > 0) {
                            if (typeof xhr.responseJSON.errors != "undefined") {
                                commonFormErrorShow(xhr, err);
                            } else {
                                displayErrorMessage(xhr.responseJSON.message);
                            }
                        } else {
                            displayErrorMessage(xhr.responseJSON.errors);
                        }
                    }
                });
            } else {
                return false;
            }
        }

        $("#order_note").keyup(function() {
            var order_note = $('#order_note').val();
            $.ajax({
                url: $('form#edit_po_form').attr('action'),
                type: 'PUT',
                data: {
                    'updateFor': 'update_order_note',
                    'order_note': order_note,
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    // hide_loader();
                    // displaySuccessMessage(data.message);
                },
                error: function(xhr, err) {
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON
                        .message.length > 0) {
                        if (typeof xhr.responseJSON.errors != "undefined") {
                            commonFormErrorShow(xhr, err);
                        } else {
                            displayErrorMessage(xhr.responseJSON.message);
                        }
                    } else {
                        displayErrorMessage(xhr.responseJSON.errors);
                    }
                }
            });
        });
    });

    var issPOItemDelete = false;

    function poItemDelete(poId, elem) {
        Swal.fire({
            title: "Delete Confirmation",
            text: "Are you sure you want to delete this record?",
            showCloseButton: true,
            showCancelButton: false,
            showDenyButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: '#009ef7',
            cancelButtonText: 'No',
            cancelButtonColor: '#181c32',
            denyButtonText: `No`,
            dangerMode: true,
        }).then(function(result) {
            if (result.isConfirmed) {
                if (!issPOItemDelete) {
                    issPOItemDelete = true;

                    $.ajax({
                        url: $(elem).attr('data-url'),
                        type: "DELETE",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            show_loader();
                        },
                        complete: function() {
                            hide_loader();
                        },
                        success: function(response) {
                            hide_loader();
                            issPOItemDelete = false;
                            displaySuccessMessage(response.message);
                            LaravelDataTables["edit-po-table"].draw();
                        },
                        error: function(xhr, err) {
                            issPOItemDelete = false;
                            if (typeof xhr.responseJSON.message != "undefined" && xhr
                                .responseJSON.message.length > 0) {
                                if (typeof xhr.responseJSON.errors != "undefined") {
                                    commonFormErrorShow(xhr, err);
                                } else {
                                    displayErrorMessage(xhr.responseJSON.message);
                                }
                            } else {
                                displayErrorMessage(xhr.responseJSON.errors);
                            }
                        }
                    });
                }
            }
        });
    }
</script>

@stop