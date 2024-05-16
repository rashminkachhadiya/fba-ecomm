@props(['label', 'name' ,'type' => 'text'])

<div class="input-group flex-nowrap input-group-sm mb-4">
    {{-- <label class="form-label" for={{$name}}>{{ $label }}</label>&nbsp; --}}
    {!! Form::$type(
        $name,
        Request::has($name) && Request::get($name) != ''
            ? base64_decode(Request::get($name))
            : '',
        ['class' => 'form-control px-5', 'id' => $name, 'placeholder'=> $label],
    ) !!}

</div>
