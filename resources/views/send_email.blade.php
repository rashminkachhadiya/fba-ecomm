<x-modal id="send_email_modal" dialog="modal-xl" style="max-width: 740px">
    {{ Form::open(['route' => ['submit-email-po'], 'name' => 'send_email_modal_form', 'id' => 'send_email_modal_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header title="Compose"/>

    <x-modal.body style="max-height: 430px; overflow-y: auto;">
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-5">
                    <x-forms.label title="To" required="required" />
                    {{ Form::text('to','', ['id' => 'to', 'class' => 'form-control', 'placeholder' => '']) }}
                    @error('to')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
          
            <div class="col-sm-6">
                <div class="mb-5">
                    <x-forms.label title="Subject" />
                    {{ Form::text('subject', '',['id' => 'subject', 'class' => 'form-control', 'placeholder' => '']) }}
                    @error('subject')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>

            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Message" />
                        {{ Form::textarea('message','', ['id' => 'ckeditor']) }}
                </div>
            </div>

            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Attachment" /><br>
                    <x-actions.icon class="fa-regular fa-file-pdf me-4 fs-6" />
                    <a href="" id="show_po_file" name="show_po_file" value=""></a>
                </div>
            </div>

        </div>
        {{ Form::hidden('','', ['id' => 'po_id','name' => 'po_id']) }}
        {{ Form::hidden('','', ['id' => 'attach_po_file','name' => 'attach_po_file']) }}
        {{ Form::hidden('','', ['id' => 'supplier_contact_id','name' => 'supplier_contact_id']) }}

    </x-modal.body>

    <x-modal.footer name="Send"  id="send_po_email_submit" type="button"/>

    {{ Form::close() }}

</x-modal>