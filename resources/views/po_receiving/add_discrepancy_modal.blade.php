<x-modal id="discrepancy_modal">
   
    {{ Form::open(['route' => ['add-discrepancy'], 'name' => 'discrepancy_modal_form', 'id' => 'discrepancy_modal_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header title="Add Discrepancy"/>

    <x-modal.body>

        {{ Form::hidden('', '', ['id' => 'po_item_id', 'name' => 'po_item_id']) }}
        {{ Form::hidden('', '', ['id' => 'po_id', 'name' => 'po_id']) }}
        @php
            $reasons = array_column(config('constants.discrepancy_reason'),'title');
        @endphp
        <div class="row">
            <div class="col-sm-6">
                <x-forms.label title="Discrepancy reason" required="required" />
                    <x-forms.select id="reason" name="reason" class="reason">
                        <x-forms.select-options title="Discrepancy reason" />
                        @foreach ($reasons as $reason)
                            <x-forms.select-options :value="$reason" :title="$reason" />
                        @endforeach
                    </x-forms.select>
            </div>
            <div class="col-sm-6">
                <div class="mb-5">
                    <x-forms.label title="Discrepancy count" required="required" />
                        {{ Form::number('discrepancy_count','', ['id' => 'discrepancy_count', 'class' => 'form-control','onkeypress'=>"return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))", 'min' => '1']) }}
                    @error('discrepancy_count')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Discrepancy Description" />
                        {{ Form::textarea('discrepancy_note','',
                        ['id' => 'discrepancy_note', 'class' => 'form-control','rows'=>3]) }}
                    <span class='help-block error-help-block' id="error_msg_id">
                    </span>
                </div>
            </div>

        </div>
    </x-modal.body>

    <x-modal.footer name="Add" id="add_discrepancy_submit"/>
    {{ Form::close() }}

</x-modal>
