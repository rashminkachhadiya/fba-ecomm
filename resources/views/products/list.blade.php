@extends('layouts.app')
@section('title', 'Product Management')
@section('breadcrumb')
<li class="breadcrumb-item">{{ __('Product List') }}</li>
@endsection
@section('content')

<x-lists>
    <div class="container-fluid py-5">

        <div class="row align-items-center gy-3 gx-3 position-relative">

            <x-search-box input_id="search" />

            <div class="col-sm-auto ms-auto text-right-sm">

                <button type="button" id="select_supplier" class="btn btn-sm btn-primary disabled">Select Supplier</button>
                {{-- <x-actions.button :url="route('products.create')" class="btn btn-sm btn-primary" title="Add Product">
                        <i class="fa-regular fa-plus"></i>
                    </x-actions.button> --}}
                <x-actions.button url="javascript:void(0)" id="column_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-solid fa-table-columns fs-4"></i>
                </x-actions.button>
                <x-actions.button url="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                    <i class="fa-regular fa-bars-filter fs-4"></i>
                </x-actions.button>
                <x-actions.icon class="fa fa-circle filter-apply-icon" id="amazon-product-filter" style="color: #009ef7; display: none;" />
            </div>

        </div>
        <!-- Show selected filter in alert warning box -->
        {{-- <x-applied-filters /> --}}
        <x-applied-filters>
            <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" />
            <x-filters.filter_msg title="Hazmat" parent_id="hazmat-span" child_id="hazmat-data" />
            <x-filters.filter_msg title="Oversize" parent_id="oversize-span" child_id="oversize-data" />
            <x-filters.filter_msg title="Store" parent_id="store-span" child_id="store-data" />
        </x-applied-filters>

    </div>

    @php
    $tableId = 'product-table'
    @endphp
    {{ $dataTable->table(['id' => 'product-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
</x-lists>
<!-- Filter Box -->
{{-- <x-filters title="product_status_filter" :statusArr="$statusArr" /> --}}
@php
$options = [
1 => 'Yes',
0 => 'No'
]
@endphp
<x-filters>
    <x-filters.list title="status" :options="$statusArr" />
    <x-filters.list label="Product Hazmat" title="hazmat" :options="$options" />
    <x-filters.list label="Product Oversize" title="oversize" :options="$options" />
    <x-filters.list label="Select Store" title="store" :options="$stores" />
</x-filters>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />

<!-- Supplier Detail Popup -->
<x-modal id="supplier_modal" dialog="modal-lg">
    {{ Form::open(['route' => ['supplier-products.update', ['supplierProductId' => 0]], 'name' => 'update_supplier_products_form', 'id' => 'update_supplier_products_form', 'method' => 'POST', 'onsubmit' => 'return false']) }}
    @method("PUT")
    <input type="hidden" name="bulk_supplier_products" value="1" />
    <x-modal.header title="Suppliers" />

    <div class="supplier-table-modal">
        <x-modal.body>

            <x-search-box class="col col-xl-5 col-xl-2 mb-5" input_id="supplier_search" button_id="supplier_search_button" />

            <x-applied-filters>
                <x-filters.filter_msg title="Supplier" parent_id="supplier-span" child_id="supplier-data" />
            </x-applied-filters>

            <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="supplier_list">
            </table>

        </x-modal.body>

        <x-modal.footer name="Save" id="supplier_product_submit" type="submit" />
    </div>
    {{ Form::close() }}
</x-modal>

<!-- Case Pack Information Popup -->
<x-modal id="update_product_modal" dialog="modal-md">
    {{ Form::open(['route' => ['update-product'], 'name' => 'update_product_form', 'id' => 'update_product_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    <input type="hidden" name="product_id">
    <x-modal.header title="Update Product Details" />

    <x-modal.body style="max-height: 430px; overflow-y: auto;">
        <div class="row">
            <x-forms>
                <div class="col-sm-6">
                    <x-forms.label title="Case Pack" required="required" />
                    {{ Form::text('case_pack', !empty(old('case_pack')) ? old('case_pack') : null, ['id' => 'case_pack', "class" => "form-control form-control-solid validate","placeholder"=>"Case Pack", "min" => 1, "maxlength"=>9, "onkeypress"=>"return onlyNumericAllowed(this,event)"]) }}
                    @error('case_pack')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>

                <div class="col-sm-6">
                    <x-forms.label title="Pack Of" required="required" />
                    {{ Form::text('pack_of', !empty(old('pack_of')) ? old('pack_of') : null, ['id' => 'pack_of', "class" => "form-control form-control-solid validate","placeholder"=>"Pack Of","min" => 1, "maxlength"=>9, "onkeypress"=>"return onlyNumericAllowed(this,event)"]) }}
                    @error('pack_of')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>

                <div class="col-sm-6">
                    <x-forms.label title="Inbound Shipping Cost" />
                    {{ Form::text('inbound_shipping_cost', !empty(old('inbound_shipping_cost')) ? old('inbound_shipping_cost') : null, ['id' => 'inbound_shipping_cost', "class" => "form-control form-control-solid validate","placeholder"=>"Inbound Shipping Cost", "maxlength"=>9, "onkeypress"=>"return onlyNumericAllowedAndDot(this,event)"]) }}
                    @error('inbound_shipping_cost')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
                <div class="col-sm-6">
                    <x-forms.label title="Warehouse Qty" />
                    {{ Form::text('wh_qty', !empty(old('wh_qty')) ? old('wh_qty') : null, ['id' => 'wh_qty', "class" => "form-control form-control-solid validate","placeholder"=>"Warehouse Qty","min" => 1, "maxlength"=>9, "onkeypress"=>"return onlyNumericAllowed(this,event)"]) }}
                    @error('wh_qty')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>

            </x-forms>
        </div>
    </x-modal.body>

    <x-modal.footer name="Save" id="product_update_submit" type="button" />
    {{ Form::close() }}

</x-modal>
<x-modal id="update_supplier_modal" dialog="modal-md">
    {{ Form::open(['route' => ['update-product-supplier'], 'name' => 'update_supplier_form', 'id' => 'update_supplier_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    <input type="hidden" name="product_id">
    <x-modal.header title="Update Product Supplier" />

    <x-modal.body style="max-height: 430px; overflow-y: auto;">
        <div class="row">
            <x-forms>
                <div class="col-sm-12">
                    <x-forms.label title="Select Supplier" required="required" />

                    <x-forms.select id="supplier_id" name="supplier_id" class="supplier_id">
                        <x-forms.select-options title="Select Supplier" />

                        @foreach ($suppliers as $key => $supplier)
                        <x-forms.select-options :value="$supplier->id" :title="$supplier->name" />
                        @endforeach
                    </x-forms.select>
                    @error('supplier_id')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
            </x-forms>
        </div>
    </x-modal.body>

    <x-modal.footer name="Save" id="update_supplier" type="button" />
    {{ Form::close() }}

</x-modal>
@endsection
@section('page-script')

{{ $dataTable->scripts() }}

{!! JsValidator::formRequest('App\Http\Requests\UpdateProductRequest', '#update_product_form'); !!}
{!! JsValidator::formRequest('App\Http\Requests\UpdateSupplierProductRequest', '#update_supplier_products_form'); !!}

<script>
    
    const tableId = "{{ $tableId }}";
    const updateColumnVisibilityUrl = "{{ route('products-columns-visibility') }}";
    const filterList = [
        ['status', 'status-span', 'status-data'],
        ['hazmat', 'hazmat-span', 'hazmat-data'],
        ['oversize', 'oversize-span', 'oversize-data'],
        ['store', 'store-span', 'store-data']
    ];
    const productArray = new Array();


    $('#select_supplier').on('click', function() {
        $('#update_supplier_modal').modal('show');
        $('.supplier_id').select2({
            dropdownParent: $('#update_supplier_modal')
            });
        $('#supplier_id').trigger('change');

    });

    $(document).on('click', '.select_checkbox', function() {
        $(".select_all_btn").prop('checked', false);
        if ($('input:checkbox.select_checkbox:checked').length > 0) {
            productArray.push($(this).val());
            $("#select_supplier").removeClass('disabled');
        } else {
            $("#select_supplier").addClass('disabled');
        }
    });
    $('.select_all_btn').on('click', function() {
        if ($(this).is(":checked")) {
            $(".select_row_btn").prop("checked", true);
            $("#select_supplier").removeClass('disabled');
        } else {
            $(".select_row_btn").prop("checked", false);
            $("#select_supplier").addClass('disabled');
        }
    });

    $("#update_supplier").click(function() {
        if ($("#update_supplier_form").valid()) {
            var formData = new FormData($("#update_supplier_form")[0]);
            $('input[name="product_select[]"]:checked').each(function() {
                productArray.push($(this).val());
            });
            formData.append("product_id", productArray);
            $.ajax({
                url: $("form#update_supplier_form").attr('action'),
                type: "POST",
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    displaySuccessMessage(data.message);
                    $('#update_supplier_modal').modal('hide');
                    $(".select_all_btn").prop('checked', false);
                    $('#supplier_id').val(1).trigger('change');

                    LaravelDataTables[tableId].draw();

                },
                error: function(xhr, err) {
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
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
    });


    $(document).on('click', '.moredata-link', function() {
        $(this).closest('.multidata-td').toggleClass('d-filter-show-hidebox');

        var toggleClass = $(this).closest('.multidata-td');

        $(document).mouseup(function(event) {
            var hideBox = $(".moredata-link");
            if (!hideBox.is(event.target) && hideBox.has(event.target).length === 0) {
                toggleClass.removeClass('d-filter-show-hidebox');
            }
        });
    });

    let supplierTable;

    function showSuppliers(productId) {
        $("#supplier_modal").modal('show');

        supplierTable = $('#supplier_list').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('supplier.list') }}",
                type: 'GET',
                data: function(d) {
                    d.product_id = productId
                },
                beforeSend: function() {
                    show_loader();
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                complete: function() {
                    hide_loader();
                },
            },
            columns: [{
                    "data": "name",
                    'name': 'suppliers.name',
                    'title': 'Supplier',
                    'searching': true
                },
                {
                    "data": "default_supplier",
                    'title': 'Default Supplier'
                },
                {
                    "data": "supplier_sku",
                    'title': 'Supplier SKU'
                },
                {
                    "data": "unit_price",
                    'title': 'Price($)'
                }
            ],
            autoWidth: false,
            columnDefs: [
                // {
                //     width: '20px',
                //     targets: [0]
                // },
                // {
                //     width: '40px',
                //     targets: [1]
                // },
                // {
                //     width: '60px',
                //     targets: [2]
                // },

            ],
            order: [
                [1, 'asc']
            ],
        });
    }

    $('#supplier_search').keyup(function(event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);

        if (keycode == '13') {
            supplierTable.search($('#supplier_search').val()).draw();
        }
    });

    $('#supplier_search_button').on('click', function(event) {
        supplierTable.search($('#supplier_search').val()).draw();
    });

    $("#supplier_modal").on('hidden.bs.modal', function() {
        // Clear the DataTable instance and remove the table completely
        if (supplierTable) {
            supplierTable.destroy();
            supplierTable = null; // Clear the variable to avoid conflicts
        }
    });

    function showModelPopup(productId = null) {
        if (productId) {
            let productDetailUrl = "{{ route('products.show', ['product' => ':product']) }}";
            productDetailUrl = productDetailUrl.replace(':product', productId);
            $.ajax({
                url: productDetailUrl,
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(res) {
                    hide_loader();
                    if (res.status && res.data) {
                        $("input[name='product_id']").val(res.data.id);
                        $("#case_pack").val(res.data.case_pack);
                        $("#pack_of").val(res.data.pack_of);
                        $("#inbound_shipping_cost").val(res.data.inbound_shipping_cost);
                        $("#wh_qty").val(res.data.wh_qty);

                    }
                },
                error: function(xhr, err) {
                    hide_loader();
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
        }

        $('#update_product_modal').modal('show');
    }


    $("#product_update_submit").click(function() {
        if ($("#update_product_form").valid()) {
            var formData = new FormData($("#update_product_form")[0]);

            $.ajax({
                url: $("form#update_product_form").attr('action'),
                type: "POST",
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    show_loader();
                },
                columns: [{
                        "data": "name",
                        'name': 'suppliers.name',
                        'title': 'Supplier',
                        'searching': true
                    },
                    {
                        "data": "supplier_sku",
                        'title': 'Supplier SKU'
                    },
                    {
                        "data": "unit_price",
                        'title': 'Price'
                    }
                ],
                autoWidth: false,
                columnDefs: [{
                        width: '20px',
                        targets: [0]
                    },
                    {
                        width: '40px',
                        targets: [1]
                    },
                    {
                        width: '60px',
                        targets: [2]
                    }
                ],
                success: function(data) {
                    hide_loader();
                    displaySuccessMessage(data.message);
                    $('#update_product_modal').modal('hide');
                    $("#product_id").val('');
                    LaravelDataTables[tableId].draw();

                },
                error: function(xhr, err) {
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
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
    });

    $('#update_product_modal').on('hidden.bs.modal', function() {
        const validator = $('#update_product_form').validate();
        validator.resetForm();
    });

    $(document).on('keyup', '.default_supplier_price', function(e) {
        if (e.keyCode == 13) {
            const supplierProductId = $(this).data('supplier-product');
            const unitPrice = $(this).val();
            $.ajax({
                url: "{{ url('supplier-products/update') }}/" + supplierProductId,
                type: "PUT",
                data: {
                    unit_price: unitPrice
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    if (data.status) {
                        displaySuccessMessage(data.message);
                        LaravelDataTables[tableId].draw();
                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (
                        typeof xhr.responseJSON.message != "undefined" &&
                        xhr.responseJSON.message.length > 0
                    ) {
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
    })

    $(document).on('keyup', '.default_supplier_sku', function(e) {
        if (e.keyCode == 13) {
            const supplierProductId = $(this).data('supplier-product');
            const supplierSku = $(this).val();
            $.ajax({
                url: "{{ url('supplier-products/update') }}/" + supplierProductId,
                type: "PUT",
                data: {
                    supplier_sku: supplierSku
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    if (data.status) {
                        displaySuccessMessage(data.message);
                        LaravelDataTables[tableId].draw();
                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (
                        typeof xhr.responseJSON.message != "undefined" &&
                        xhr.responseJSON.message.length > 0
                    ) {
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
    })

    $(document).on('change', '.product_note', function(e) {
        // if (e.keyCode == 13) {
            const product = $(this).data('product');
            const productNote = $(this).val();
            $.ajax({
                url: "{{ url('products') }}/" + product,
                type: "PUT",
                data: {
                    product_note: productNote
                },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                beforeSend: function() {
                    show_loader();
                },
                success: function(data) {
                    hide_loader();
                    if (data.status) {
                        displaySuccessMessage(data.message);
                        LaravelDataTables[tableId].draw();
                    } else {
                        displayErrorMessage(data.message);
                    }
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (
                        typeof xhr.responseJSON.message != "undefined" &&
                        xhr.responseJSON.message.length > 0
                    ) {
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
        // }
    })

    $(document).on('submit', 'form#update_supplier_products_form', function(e) {
        e.preventDefault();
        if ($("#update_supplier_products_form").valid()) {
            const dataString = new FormData($("#update_supplier_products_form")[0]);
            const supplierProductId = $(".supplier_sku").data('supplier-product');

            $.ajax({
                url: "{{ url('supplier-products/update') }}/" + supplierProductId,
                type: $(this).attr('method'),
                data: dataString,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    show_loader();
                },
                complete: function() {
                    hide_loader();
                },
                success: function(data) {
                    hide_loader();
                    displaySuccessMessage(data.message);
                    window.location.reload();
                },
                error: function(xhr, err) {
                    hide_loader();
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
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
    });

    $(document).on('change', '.default_supplier', function() {
        if ($(this).is(":checked")) {
            $(".default_supplier").not(this).prop('checked', false);
        } else {
            $(this).prop('checked', true);
        }
    })
</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop