@props(['class', 'input_id', 'button_id', 'placeholder' => 'Search'])

<div class="{{ $class ?? 'col col-xl-3 col-xl-2' }}">
    <div class="input-group flex-nowrap input-group-sm">
        {{ Form::text('search', Request::has('search') && Request::get('search') != '' ? base64_decode(Request::get('search')) : '', ['id' => $input_id ?? 'search_data', 'autocomplete' => 'off', 'class' => 'form-control px-5', 'placeholder' => $placeholder]) }}
        <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button"
            id="{{ $button_id ?? 'search_button' }}">
            <i class="fa-regular fa-magnifying-glass text-primary"></i>
        </button>
    </div>
</div>
