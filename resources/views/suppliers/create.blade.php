@extends('layouts.app')

@section('title', 'Add Supplier')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Supplier') }}</a></li>
<li class="breadcrumb-item">{{ __('Create Supplier') }}</li>
@endsection

@section('content')

<x-lists>
    @include('suppliers.tabs')
    <div class="container-fluid py-5">
        {{-- Supplier's Basic Info Tab Start --}}
        <div id="basic_info" class="tab-pane fade in active">
            <div class="tab-content mt-7">
                {{ Form::open(['route' => ['suppliers.store'], 'name' => 'add_new_supplier', 'id' => 'add_new_supplier_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
                @csrf
                <div class="row">
                    <x-forms>
                        <x-forms.form-div>
                            <x-forms.label title="Supplier Name" required="required" />
                            {{ Form::text('name', !empty(old('name')) ? old('name') : null, ['id' => 'name', 'class' => 'form-control', 'placeholder' => 'Supplier Name', 'Required' => true]) }}
                            @error('name')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Email" required="required" />
                            {{ Form::email('email', !empty(old('email')) ? old('email') : null, ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'Email', 'Required' => true]) }}
                            @error('email')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Website" />
                            {{ Form::text('url', !empty(old('url')) ? old('url') : null, ['id' => 'url', 'class' => 'form-control', 'placeholder' => 'Website']) }}
                            @error('url')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Account Number" />
                            {{ Form::text('account_number', !empty(old('account_number')) ? old('account_number') : null, ['id' => 'account_number', 'class' => 'form-control', 'placeholder' => 'Account Number']) }}
                            @error('account_number')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Phone Numebr" />
                            {{ Form::text('phone_number', !empty(old('phone_number')) ? old('phone_number') : null, ['id' => 'phone_number', 'class' => 'form-control', 'placeholder' => '123-45-678']) }}
                            @error('phone_number')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Address" />
                            {{ Form::textarea('address', !empty(old('address')) ? old('address') : null, ['id' => 'address', 'class' => 'form-control', 'rows' => 1]) }}
                            @error('address')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>

                        <x-forms.form-div>
                            <x-forms.label title="Lead Time" required="required" />
                            {{ Form::number('lead_time', !empty(old('lead_time')) ? old('lead_time') : null, ['id' => 'lead_time', 'class' => 'form-control', 'placeholder' => '0 Day', 'min' => '0', 'Required' => true]) }}
                            @error('lead_time')
                            <x-forms.error :message="$message" />
                            @enderror
                        </x-forms.form-div>
                    </x-forms>

                    <x-forms.form-footer>
                        <x-forms.button :link="route('suppliers.index')" />
                        <x-forms.button name='Save & Next' />
                    </x-forms.form-footer>
                </div>
                {{ Form::hidden('', route('supplier_contact_info.index'), ['id' => 'supplier_list_url']) }}
                {{ Form::hidden('', route('supplier_products.index'), ['id' => 'supplier_produc`t_list']) }}

            </div>
            {{ Form::close() }}
        </div>
        {{-- Supplier's Basic Info Tab End --}}
    </div>
</x-lists>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\SupplierRequest', '#add_new_supplier_form') !!}
<script src="{{ asset('js/suppliers/form.js') }}" type="text/javascript"></script>
@stop