@props(['name' , 'id' ,'type'])

<div class="modal-footer">
    <button type="button" class="btn btn-light cancel" data-bs-dismiss="modal">Cancel</button>
    <button type="{{($type) ?? 'submit'}}" class="btn btn-sm fs-6 btn-primary ms-3" id="{{($id) ?? ''}}">
        {{ ($name) ?? __('Save') }}
    </button>
</div>