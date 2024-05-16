@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Supplier') }}</a></li>
<li class="breadcrumb-item">{{ __('Edit Supplier') }}</li>
@endsection

@section('content')
<x-lists>
    @include('suppliers.tabs')
    <div class="container-fluid py-5">
        <div id="basic_info" class="tab-pane fade in active">
            <div class="tab-content mt-7">
                {{ Form::open(['route' => ['suppliers.update', ['supplier' => $supplier->id]], 'name' => 'update_supplier_record', 'id' => 'update_supplier_form', 'method' => 'PUT']) }}
                @csrf
                {{ Form::hidden('id', $supplier->id, ['id' => 'supplier_id']) }}
                <div class="row">
                    <x-forms>
                        <x-forms.form-div>
                            <x-forms.label title="Supplier Name" required="required" />
                            {{ Form::text('name', !empty($supplier->name) ? $supplier->name : old('name'), ['id' => 'name', 'class' => 'form-control', 'placeholder' => 'First Name']) }}
                            @error('name')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Email" required="required" />
                            {{ Form::email('email', !empty($supplier->email) ? $supplier->email : old('email'), ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'Email', 'Required' => true]) }}
                            @error('email')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Website" />
                            {{ Form::text('url', !empty($supplier->url) ? $supplier->url : old('url'), ['id' => 'url', 'class' => 'form-control', 'placeholder' => 'Website']) }}
                            @error('url')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Account Number" />
                            {{ Form::text('account_number', !empty($supplier->account_number) ? $supplier->account_number : old('account_number'), ['id' => 'account_number', 'class' => 'form-control', 'placeholder' => 'Account Numbe']) }}
                            @error('account_number')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Phone Numebr" />
                            {{ Form::text('phone_number', !empty($supplier->phone_number) ? $supplier->phone_number : old('phone_number'), ['id' => 'phone_number', 'class' => 'form-control', 'placeholder' => '123-45-678']) }}
                            @error('phone_number')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Address" />
                            {{ Form::textarea('address', !empty($supplier->address) ? $supplier->address : old('address'), ['id' => 'address', 'class' => 'form-control', 'rows' => 1]) }}
                            @error('address')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Lead Time" required="required" />
                            {{ Form::number('lead_time', !empty($supplier->lead_time) ? $supplier->lead_time : old('lead_time'), ['id' => 'lead_time', 'class' => 'form-control', 'placeholder' => '0 Day', 'min' => '0', 'Required' => true]) }}
                            @error('lead_time')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>
                    </x-forms>

                </div>

                <x-forms.form-footer>
                    <x-forms.button :link="route('suppliers.index')" />
                    <x-forms.button name='Save & Next' />
                </x-forms.form-footer>

                {{ Form::hidden('', route('supplier_contact_info.index'), ['id' => 'supplier_list_url']) }}
                {{ Form::hidden('', route('supplier_products.index'), ['id' => 'supplier_product_list']) }}

            </div>
            {{ Form::close() }}
        </div>
    </div>
</x-lists>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\SupplierRequest', '#update_supplier_form') !!}
<script src="{{ asset('js/suppliers/form.js') }}" type="text/javascript"></script>
@stop