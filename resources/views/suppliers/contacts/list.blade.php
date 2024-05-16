@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Supplier') }}</a></li>
<li class="breadcrumb-item">{{ __('Contact List') }}</li>
@endsection
@section('content')
<x-lists>
    @include('suppliers.tabs')
    <div class="container-fluid py-5">

        <div id="contact_info" class="tab-pane fade in active">
            <div class="row align-items-center gy-3 gx-3 position-relative">

                <x-search-box input_id="search" />

                <div class="col-sm-auto ms-auto text-right-sm">
                    <x-actions.button url="javascript:;" id="add_contact_info_modal" class="btn btn-sm btn-primary" title="Add Contact">
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

    @php
    $tableId = 'supplier-contact-table';
    @endphp
    {{ $dataTable->table(['id' => 'supplier-contact-table', 'class' => 'align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7'], true) }}
    </div>
</x-lists>

<x-modal id="add_contact_info_form">
    {{ Form::open(['route' => ['supplier_contact_info.store'], 'name' => 'add_new_contact', 'id' => 'add_new_contact_form_data', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <input type="hidden" name="supplier_id" id="supplier_id" value="{{ $supplier_id }}">
    <input type="hidden" name="contact_info_id" id="contact_info_id">

    <x-modal.header />

    <x-modal.body>
        <div class="row">
            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Name" required="required" />
                    {{ Form::text('name', !empty(old('name')) ? old('name') : null, ['id' => 'name', 'class' => ' form-control', 'placeholder' => 'Name', 'Required' => true]) }}
                    @error('name')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Email" required="required" />
                    {{ Form::email('email', !empty(old('email')) ? old('email') : null, ['id' => 'email', 'class' => ' form-control', 'placeholder' => 'Email', 'Required' => true]) }}
                    @error('email')
                    <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Phone Number" />
                    {{ Form::text('phone_number', !empty(old('phone_number')) ? old('phone_number') : null, ['id' => 'phone_number', 'class' => ' form-control', 'placeholder' => '123-45-678']) }}
                </div>
            </div>
        </div>

        <x-toggle>
            <span>
                <x-forms.label title="Is Default Contact" />
            </span>&nbsp;
            <x-toggle.input class="form-check-input h-20px w-30px" type="checkbox" name="is_default" id="is_default" />
        </x-toggle>

    </x-modal.body>

    <x-modal.footer />
    {{ Form::hidden('', route('supplier_contact_info.index'), ['id' => 'supplier_contact_list_url']) }}
    {{ Form::hidden('', route('supplier_products.index'), ['id' => 'supplier_product_list']) }}

    {{ Form::close() }}
</x-modal>
<!-- Columns list component -->
<x-table_columns :fields="$listingCols" />
<x-forms.form-footer>
    <x-forms.button :link="route('suppliers.index')" />
    <x-forms.button name='Save & Next' id="redirect_product_list" />
</x-forms.form-footer>
@endsection

@section('page-script')

{{ $dataTable->scripts() }}
{!! JsValidator::formRequest('App\Http\Requests\ContactRequest', '#add_new_contact_form_data') !!}
<script src="{{ asset('js/suppliers/contact-info.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/suppliers/form.js') }}" type="text/javascript"></script>

<script>
    const tableId = "{{ $tableId }}";
    const updateColumnVisibilityUrl = "{{ route('contact-columns-visibility') }}";
</script>

<script src="{{ asset('js/filter.js') }}" type="text/javascript"></script>
@stop