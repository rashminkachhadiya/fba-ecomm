@props(['title' => '', 'options' => [], 'label' => 'All Status'])
{{-- {{ dd($options) }} --}}
<div class="mb-5">
    <label class="form-label">{{ $label }}</label>
    {!! Form::select(
    $title,
    $options,
    Request::has($title) && Request::get($title) != ''
    ? base64_decode(Request::get($title))
    : '',
    ['class' => 'form-select store-dropdown-filter', 'id' => $title, 'placeholder' => $label,'data-control' => 'select2', 'data-hide-search' => 'true'],
    ) !!}
</div>