@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Supplier') }}</a></li>
<li class="breadcrumb-item">{{ __('Product List') }}</li>
@endsection
@section('content')

<x-lists>
    @include('suppliers.tabs')
    <div class="container-fluid py-5">
        <div id="product_info" class="tab-pane fade in active">

            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:void(0)" id="add_product_modal" data-url="{{ route('product-list') }}" class="btn btn-sm btn-primary" title="Add Product">
                        <i class="fa-regular fa-plus"></i>
                    </x-actions.button>
                    <x-actions.button url="javascript:void(0)" id="column_drawer" class="ms-5 btn btn-sm btn-link">
                        <i class="fa-solid fa-table-columns fs-4"></i>
                    </x-actions.button>
                </div>

            </div>
        </div>
        <!-- Show selected filter in alert warning box -->
        <x-applied-filters>
            <x-filters.filter_msg title="Status" parent_id="status-span" child_id="status-data" />
        </x-applied-filters>
    </div>

    <input type="hidden" name="supplier_id" id="supplier_id" value="{{ $supplier_id }}">
    <input type="hidden" name="supplier_list_url" id="supplier_list_url" value="{{ route('supplier_contact_info.index') }}">

    @php
    $tableId = 'supplier-product-table';
    @endphp

    {{ Form::open(['route' => ['supplier_products.update', ['supplier_product' => $supplier_id]], 'method' => 'PUT', 'name' => 'update_supplier_product', 'id' => 'update_supplier_product', 'enctype' => 'multipart/form-data']) }}
    {{ $dataTable->table(['id' => 'supplier-product-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    <input type="hidden" name="supplier_products_id" id="supplier_products_id" value="">
    {{ Form::close() }}

    </div>
</x-lists>

<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />

@include('suppliers.products.add_product_modal')

@endsection

@section('page-script')

{{ $dataTable->scripts() }}
<script src="{{ asset('js/suppliers/product.js') }}" type="text/javascript"></script>
{!! JsValidator::formRequest('App\Http\Requests\SupplierProductsRequest', '#update_supplier_product') !!}

<script>
    const tableId = "{{ $tableId }}";
    const updateColumnVisibilityUrl = "{{ route('product-columns-visibility') }}";
</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>

@stop