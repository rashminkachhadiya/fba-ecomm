@extends('layouts.app')

@section('title', 'Add User')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('users.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{__('User')}}</a></li>
<li class="breadcrumb-item">{{__('Create User')}}</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['users.store'], 'name' => 'add_new_user', 'id' => 'add_new_user_form', 'method' => 'POST', 'enctype'=>'multipart/form-data','onsubmit'=>'return false']) }}

    <div class="row">
        <x-forms>
            <x-forms.form-div>
                <x-forms.label title="User Name" required="required" />
                {{ Form::text('name', !empty(old('name')) ? old('name') : null, ['id' => 'name', "class" => " form-control validate","placeholder"=>"User Name"]) }}
                @error('name')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Email" required="required" />
                {{ Form::email('email', !empty(old('email')) ? old('email') : null, ['id' => 'email', "class" => " form-control validate","placeholder"=>"Email", "Required"=>true]) }}
                @error('name')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Password" required="required" />
                {{ Form::password('password', ['id' => 'password', "class" => " form-control validate","placeholder"=>"Password", "Required"=>true]) }}
                @error('password')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Confirm Password" required="required" />
                {{ Form::password('password_confirmation', ['id' => 'password_confirmation', "class" => " form-control validate","placeholder"=>"Confirmation Password", "Required"=>true]) }}
                @error('password_confirmation')
                <x-forms.error :message="$message" />
                @enderror
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
{!! JsValidator::formRequest('App\Http\Requests\UserRequest', '#add_new_user_form'); !!}
<script>
    const url = "{{ route('users.index') }}"
</script>
<script src="{{ asset('js/users/form.js')}}" type="text/javascript"></script>
@stop