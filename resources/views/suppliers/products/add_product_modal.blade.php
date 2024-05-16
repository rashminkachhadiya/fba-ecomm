<x-modal id="add_product_form" dialog="modal-xl">
    {{ Form::open(['route' => ['supplier_products.store'], 'name' => 'add_selected_product', 'id' => 'add_selected_product', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header />
    <div class="po-edit-modal">
        <x-modal.body>

            <x-search-box class="col col-xl-5 col-xl-2 mb-5" input_id="product_search_data" button_id="product_search_button" />
            {{ Form::hidden('', route('product-list'), ['id' => 'get_supplier_contact_info']) }}
            {{ Form::hidden('', '', ['id' => 'product_supplier_id']) }}

            <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="product_list">
            </table>
        </x-modal.body>
    </div>
    <x-modal.footer name="Add Product" id="add_product_submit" type="button" />
    {{ Form::close() }}

</x-modal>