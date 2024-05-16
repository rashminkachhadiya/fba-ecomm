@extends('layouts.app')
@section('title', 'Edit Store')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('stores.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{__('Store')}}</a></li>
<li class="breadcrumb-item">{{__('Edit Store')}}</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['stores.update', ['store' => $store->id]], 'name' => 'update_store_record', 'id' => 'update_store_form', 'method' => 'PUT', 'autocomplete' => "off"]) }}
    {{ Form::hidden('id', $store->id, ['id' => 'store_id']) }}
    <div class="row">

        <x-forms>

            <x-forms.form-div>
                <x-forms.label title="Store Name" required="required" />
                {{ Form::text('store_name', !empty($store->store_name) ? $store->store_name : old('store_name'), ['id' => 'store_name', "class" => " form-control validate","placeholder"=>"Store Name"]) }}

                @error('store_name')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Store Configuration" required="required" />
                <x-forms.select id="store_config_id" name="store_config_id">
                    <x-forms.select-options title="Select Store Config" />
                    @foreach($storeTypes as $storeType)
                    <x-forms.select-options :value="$storeType->id" :title="$storeType->store_type" :selected="(($store->store_config_id == $storeType->id)) ? 'selected' : null" />
                    @endforeach
                </x-forms.select>
                @error('store_config_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Client ID" required="required" />
                {{ Form::text('client_id', !empty($store->client_id) ? $store->client_id : old('client_id'), ['id' => 'client_id', "class" => " form-control validate","placeholder"=>"Client ID"]) }}

                @error('client_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Client Secret" required="required" />
                {{ Form::text('client_secret', !empty($store->client_secret) ? $store->client_secret : old('client_secret'), ['id' => 'client_secret', "class" => " form-control validate","placeholder"=>"Client Secret"]) }}

                @error('client_secret')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="AWS Access Key ID" required="required" />
                {{ Form::text('aws_access_key_id', !empty($store->aws_access_key_id) ? $store->aws_access_key_id : old('aws_access_key_id'), ['id' => 'aws_access_key_id', "class" => " form-control validate","placeholder"=>"AWS Access Key ID"]) }}

                @error('aws_access_key_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="AWS Secret Key" required="required" />
                {{ Form::text('aws_secret_key', !empty($store->aws_secret_key) ? $store->aws_secret_key : old('aws_secret_key'), ['id' => 'aws_secret_key', "class" => " form-control validate","placeholder"=>"AWS Secret Key"]) }}

                @error('aws_secret_key')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Refresh Token" required="required" />
                {{ Form::text('refresh_token', !empty($store->refresh_token) ? $store->refresh_token : old('refresh_token'), ['id' => 'refresh_token', "class" => " form-control validate","placeholder"=>"Refresh Token"]) }}

                @error('refresh_token')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="ARN Role" />
                {{ Form::text('role_arn', !empty($store->role_arn) ? $store->role_arn : old('role_arn'), ['id' => 'role_arn', "class" => " form-control validate","placeholder"=>"ARN Role"]) }}
            </x-forms.form-div>

        </x-forms>

        <x-forms.form-footer>
            <x-forms.button :link="url()->previous()" />
            <x-forms.button />
        </x-forms.form-footer>

    </div>
    {{ Form::close()}}
</x-forms.parent>

@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\StoreRequest', '#update_store_form'); !!}
<script>
    const url = "{{ route('stores.index') }}"
</script>
<script src="{{ asset('js/stores/form.js')}}" type="text/javascript"></script>
@stop