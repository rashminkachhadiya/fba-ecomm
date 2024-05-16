@extends('layouts.app')

@section('title', 'View Supplier')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('suppliers.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('Supplier') }}</a></li>
<li class="breadcrumb-item">{{ __('View Supplier') }}</li>
@endsection

@section('content')

<x-forms.parent>
    <div class="row">
        <x-forms>
            <x-forms.form-div>
                <x-forms.label title="Supplier Name" />
                {{ Form::text('name', !empty($supplier->name) ? $supplier->name : old('name'), ['id' => 'name', 'class' => 'form-control form-control-solid cursor-not-allowed', 'placeholder' => 'Supplier Name', 'readonly' => true]) }}
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Email" />
                {{ Form::email('email', !empty($supplier->email) ? $supplier->email : old('email'), ['id' => 'email', 'class' => 'form-control form-control-solid cursor-not-allowed', 'placeholder' => 'Email', 'readonly' => true]) }}
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Website" />
                @php
                if (!empty($supplier->url)) {
                $url = trim($supplier->url);
                if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
                $url = 'http://' . $url;
                } else {
                $url = $url;
                }
                }
                @endphp
                <a href="{{ !empty($supplier->url) ? $url : old('url') }}" target="{{ !empty($supplier->url) ? '_blank' : '' }}" style="min-height:42.94px" class="form-control form-control-solid {{ empty($supplier->url) ? 'cursor-not-allowed' : '' }}">{{ !empty($supplier->url) ? $supplier->url : old('url') }}</a>
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Account Number" />
                {{ Form::text('account_number', !empty($supplier->account_number) ? $supplier->account_number : old('account_number'), ['id' => 'account_number', 'class' => 'form-control form-control-solid cursor-not-allowed', 'placeholder' => 'Account Numbe', 'readonly' => true]) }}
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Phone Number" />
                {{ Form::text('phone_number', !empty($supplier->phone_number) ? $supplier->phone_number : old('phone_number'), ['id' => 'phone_number', 'class' => 'form-control form-control-solid cursor-not-allowed', 'placeholder' => '123-45-678', 'readonly' => true]) }}
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Address" />
                {{ Form::textarea('address', !empty($supplier->address) ? $supplier->address : old('address'), ['id' => 'address', 'class' => 'form-control form-control-solid cursor-not-allowed', 'readonly' => true, 'rows' => 1]) }}
            </x-forms.form-div>

                <x-forms.form-div>
                    <x-forms.label title="Lead Time" />
                    {{ Form::number('lead_time', !empty($supplier->lead_time) ? $supplier->lead_time : old('lead_time'), ['id' => 'lead_time', 'class' => 'form-control form-control-solid cursor-not-allowed', 'placeholder' => '0 Day', 'min' => '0', 'readonly' => true]) }}
                </x-forms.form-div>
            </x-forms>

    </div>
    <hr class="bg-dark bg-opacity-25">

    @if (isset($contacts) && !empty($contacts) && $contacts > 0)
    <div class="row mt-10">
        <h3 class="fw-700 mb-5">Contacts Information</h3>
        <div class="table-responsive">
            <table class="align-middle table table-row-bordered purchase-order-table table-row-gray-300 gs-3 gy-3 dataTable">
                <thead>
                    <thead>
                        <tr class="fs-7">
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                <tbody>
                    @foreach ($contacts as $value)
                    <tr>
                        <td class="text-nowrap">{{ $value['name'] }}</td>
                        <td class="text-nowrap">{{ $value['email'] }}</td>
                        <td class="text-nowrap">{{ $value['phone_number'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <hr class="bg-dark bg-opacity-25 mt-15">
    @endif

    @if (isset($products) && !empty($products) && $products > 0)
    <div class="row mt-10">
        <h3 class="fw-700 mb-5">Product Information</h3>
        <div class="table-responsive">
            <table class="align-middle table table-row-bordered purchase-order-table table-row-gray-300 gs-3 gy-3 dataTable">
                <thead>
                    <thead>
                        <tr class="fs-7">
                            <th class="text-center">Image</th>
                            <th>Title</th>
                            <th>SKU</th>
                            <th>ASIN</th>
                            <th>Supplier SKU</th>
                            <th>Unit Price</th>
                            <th>Additional Cost</th>
                        </tr>
                    </thead>
                <tbody>
                    @foreach ($products as $value)
                    <tr>
                        <td class="text-nowrap text-center"><img src="{{ $value['main_image'] }}" width="65" height="65"></td>
                        <td class="text-nowrap" title="{{ $value['title'] }}">
                            {{ \Illuminate\Support\Str::limit(strip_tags($value['title']), 30, '...') }}
                        </td>
                        <td class="text-nowrap">{{ $value['sku'] }}</td>
                        <td class="text-nowrap">{{ $value['asin'] }}</td>
                        <td class="text-nowrap">{{ $value['supplier_sku'] }}</td>
                        <td class="text-nowrap">{{ $value['unit_price'] }}</td>
                        <td class="text-nowrap">{{ $value['additional_cost'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif

    <x-forms.form-footer>
        <a href="{{ route('suppliers.index') }}" type="submit" class="btn btn-sm fs-6 btn btn-secondary">{{ __('Cancel') }}</a>
    </x-forms.form-footer>

</x-forms.parent>
@endsection