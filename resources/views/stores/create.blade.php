@extends('layouts.app')

@section('title', 'Add Store')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('stores.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{__('Store')}}</a></li>
<li class="breadcrumb-item">{{__('Create Store')}}</li>
@endsection

@section('content')

<x-forms.parent>

    {{ Form::open(['route' => ['stores.store'], 'name' => 'add_new_store', 'id' => 'add_new_store_form', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false', 'autocomplete' => "off"]) }}

    <div class="row">

        <x-forms>

            <x-forms.form-div>
                <x-forms.label title="Store Name" required="required" />
                {{ Form::text('store_name', !empty(old('store_name')) ? old('store_name') : null, ['id' => 'store_name', "class" => "form-control form-control-solid validate","placeholder"=>"Store Name"]) }}

                @error('store_name')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Store Configuration" required="required" />
                <x-forms.select id="store_config_id" name="store_config_id">
                    <x-forms.select-options title="Select Store Config" />
                    @foreach($storeTypes as $storeType)
                    <x-forms.select-options :value="$storeType->id" :title="$storeType->store_type" />
                    @endforeach
                </x-forms.select>
                @error('store_config_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Client ID" required="required" />
                {{ Form::text('client_id', !empty(old('client_id')) ? old('client_id') : null, ['id' => 'client_id', "class" => "form-control form-control-solid validate","placeholder"=>"Client ID"]) }}

                @error('client_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Client Secret" required="required" />
                {{ Form::text('client_secret', !empty(old('client_secret')) ? old('client_secret') : null, ['id' => 'client_secret', "class" => "form-control form-control-solid validate","placeholder"=>"Client Secret"]) }}

                @error('client_secret')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="AWS Access Key ID" required="required" />
                {{ Form::text('aws_access_key_id', !empty(old('aws_access_key_id')) ? old('aws_access_key_id') : null, ['id' => 'aws_access_key_id', "class" => "form-control form-control-solid validate","placeholder"=>"AWS Access Key ID"]) }}

                @error('aws_access_key_id')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="AWS Secret Key" required="required" />
                {{ Form::text('aws_secret_key', !empty(old('aws_secret_key')) ? old('aws_secret_key') : null, ['id' => 'aws_secret_key', "class" => "form-control form-control-solid validate","placeholder"=>"AWS Secret Key"]) }}

                @error('aws_secret_key')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Refresh Token" required="required" />
                {{ Form::text('refresh_token', !empty(old('refresh_token')) ? old('refresh_token') : null, ['id' => 'refresh_token', "class" => "form-control form-control-solid validate","placeholder"=>"Refresh Token"]) }}

                @error('refresh_token')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="ARN Role" />
                {{ Form::text('role_arn', !empty(old('role_arn')) ? old('role_arn') : null, ['id' => 'role_arn', "class" => "form-control form-control-solid validate","placeholder"=>"ARN Role"]) }}
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
{!! JsValidator::formRequest('App\Http\Requests\StoreRequest', '#add_new_store_form'); !!}
<script>
    const url = "{{ route('stores.index') }}";
</script>
<script src="{{ asset('js/stores/form.js')}}" type="text/javascript"></script>
@stop