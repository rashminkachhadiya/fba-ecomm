@php
    $reasons = array_column(config('constants.discrepancy_reason'),'title');
@endphp
<x-modal id="edit_discrepancy_modal" dialog="modal-xl" style="width:800px;">
    {{ Form::open(['route' => ['update-discrepancy'], 'name' => 'edit_discrepancy_modal_form', 'id' => 'edit_discrepancy_modal_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header title="Edit Discrepancy"/>

    <x-modal.body style="max-height: 430px; overflow-y: auto;">

        <div class="col-sm-auto ms-auto mb-3" style="float: right;">
        <button type="button" class="btn btn-sm fs-6 btn-primary ms-3" id="add_discrepancy_form_edit"><i class="fa-regular fa-plus"></i>Add More</button>
        </div>
        {{ Form::hidden('', '', ['id' => 'edit_po_item_id', 'name' => 'edit_po_item_id']) }}
        {{ Form::hidden('', '', ['id' => 'edit_po_id', 'name' => 'edit_po_id']) }}
        {{ Form::hidden('', route('edit-discrepancy'), ['id' => 'edit_discrepancy_url', 'name' => 'edit_discrepancy_url']) }}
        {{ Form::hidden('', route('delete-discrepancy'), ['id' => 'delete_discrepancy_url', 'name' => 'delete_discrepancy_url']) }}

        <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative">
            <thead>
                <tr>
                    <th class="w-25">Reason</th>
                    <th class="w-50">Description</th>
                    <th class="w-25">Discrepancy QTY</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id='append_discrepancy_data'>
            </tbody>
        </table>
    </x-modal.body>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary edit-save" data-bs-dismiss="modal">Save</button>
    </div>
    {{ Form::close() }}

</x-modal>
