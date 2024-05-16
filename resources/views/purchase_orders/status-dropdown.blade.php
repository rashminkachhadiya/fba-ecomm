{{-- {!! Form::select(
    $value->status,
    $options,
    ['class' => 'form-select po-status', 'data-id' => $value->id],
); !!} --}}

<select class="form-select po-status" data-id="{{ $value->id }}" data-current-status="{{ $value->status }}">
    @foreach ($options as $option)
        <option value="{{ $option }}">{{ $option }}</option>
    @endforeach
</select>