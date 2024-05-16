<x-modal id="add_fba_product_form" dialog="modal-xl">
    {{ Form::open(['route' => ['insert-selected-fba-product'], 'name' => 'insert_selected_fba_product', 'id' => 'insert_selected_fba_product', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header />

    <x-modal.body style="max-height: 430px; overflow-y: auto;">

        <x-search-box class="col col-xl-5 col-xl-2 mb-5" input_id="product_search_data"
            button_id="product_search_button" />

        <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative"
            id="get_fba_product_list">
        </table>
    </x-modal.body>

    <x-modal.footer name="Add to Plan" id="add_fba_product_submit" type="button" />
    {{ Form::close() }}

</x-modal>
