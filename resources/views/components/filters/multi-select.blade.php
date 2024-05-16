@props(['title' => '', 'options' => [], 'label' => 'All Status'])

<div class="mb-5">
    <label class="form-label">{{ $label }}</label>
    {!! Form::select(
    $title,
    $options,
    Request::has($title) && Request::get($title) != ''
    ? base64_decode(Request::get($title))
    : '',
    ['class' => 'form-select', 'id' => $title, 'multiple'=>'multiple', 'data-control'=>'select2', 'data-placeholder'=>'Select an option', 'data-allow-clear'=>'true'],
    ) !!}
</div>