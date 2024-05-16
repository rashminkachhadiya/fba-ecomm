<x-modal id="multi_skus_all_modal" dialog="modal-xl">
    {{ Form::open(['route' => ['purchase_order_items.store'], 'name' => 'add_selected_product', 'id' => 'add_selected_product', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header title="Add Products" />
    <div class="po-edit-modal">
        <x-modal.body>

            <x-search-box class="col col-xl-5 col-xl-2 mb-5" input_id="product_search_data" button_id="product_search_button" placeholder="Search by title, sku and asin" />

            {{ Form::hidden('', route('product-list'), ['id' => 'get_supplier_contact_info']) }}

            <div class="table-responsive">
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative table-responsive" id="product_list">
                    <thead>
                        <th></th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>ASIN / SKU</th>
                        <th>QTY</th>
                        <th>Done</th>
                        <th>Notes</th>
                    </thead>
                    <tbody id="multi_skus_all_products_body">

                    </tbody>
                </table>
            </div>
        </x-modal.body>
    </div>
    <x-modal.footer name="Add" id="select_multi_skus" type="button" />
    {{ Form::close() }}

</x-modal>