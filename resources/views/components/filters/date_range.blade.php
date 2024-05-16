@props(['title', 'start_date_id', 'start_date_name','end_date_name','end_date_id'])

<div class="row mt-3">
<label class="form-label">{{ $title }}</label>
    <div class="col-sm-6">
        <x-datepicker>
            {!! Form::text(
                $start_date_name,
                Request::has($start_date_name) && Request::get($start_date_name) != ''
                    ? base64_decode(Request::get($start_date_name))
                    : '',
                ['class' => 'form-control', 'id' => $start_date_id, 'placeholder' => 'From'],
            ) !!}
            <x-datepicker.calendar />
        </x-datepicker>
    </div>
    <div class="col-sm-6">
        <x-datepicker>
            {!! Form::text(
                $end_date_name,
                Request::has($end_date_name) && Request::get($end_date_name) != ''
                    ? base64_decode(Request::get($end_date_name))
                    : '',
                ['class' => 'form-control', 'id' => $end_date_id, 'placeholder' => 'To'],
            ) !!}
            <x-datepicker.calendar />
        </x-datepicker>
    </div>
</div>
