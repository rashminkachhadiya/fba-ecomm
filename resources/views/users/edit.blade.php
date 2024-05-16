@extends('layouts.app')
@section('title', 'Edit User')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('users.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{__('User')}}</a></li>
<li class="breadcrumb-item">{{__('Edit User')}}</li>
@endsection

@section('content')

<x-forms.parent>
    {{ Form::open(['route' => ['users.update', ['user' => $user->id]], 'name' => 'update_user_record', 'id' => 'update_user_form', 'method' => 'PUT']) }}
    {{ Form::hidden('id', $user->id, ['id' => 'user_id']) }}
    <div class="row">

        <x-forms>

            <x-forms.form-div>
                <x-forms.label title="User Name" required="required" />
                {{ Form::text('name', !empty($user->name) ? $user->name : old('name'), ['id' => 'name', "class" => " form-control validate","placeholder"=>"First Name"]) }}
                @error('name')
                <x-forms.error :message="$message" />
                @enderror
            </x-forms.form-div>

            <x-forms.form-div>
                <x-forms.label title="Email" required="required" />
                {{ Form::email('email', !empty($user->email) ? $user->email : old('email'), ['id' => 'email', "class" => " form-control validate","placeholder"=>"Email", "Required"=>true, "disabled" => true]) }}
                @error('email')
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
{!! JsValidator::formRequest('App\Http\Requests\UserRequest', '#update_user_form'); !!}
<script>
    const url = "{{ route('users.index') }}"
</script>
<script src="{{ asset('js/users/form.js')}}" type="text/javascript"></script>
@stop