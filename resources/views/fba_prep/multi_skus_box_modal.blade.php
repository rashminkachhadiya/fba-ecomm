<x-modal id="multi_skus_search_modal" dialog="modal-xl">
    {{-- {{ Form::open(['route' => ['purchase_order_items.store'], 'name' => 'add_selected_product', 'id' => 'add_selected_product', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf --}}
    <x-modal.header title="MultiSKU Box" />
    <div class="po-edit-modal">
        <x-modal.body>

            <div class="row">
                <x-search-box class="col-5 mb-5" input_id="product_search_data" button_id="product_search_button" placeholder="Search by fnsku, sku and asin" />
                {{ Form::hidden('', route('product-list'), ['id' => 'get_supplier_contact_info']) }}
                <div class="col-4"></div>
                <div class="col-3 d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-sm fs-6 btn-primary ms-3" id="add_products_btn">
                        Add Products
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-gray-300 table-bordered gs-7 gy-4 gx-4 dataTable tab-table position-relative" id="product_list">
                    <thead>
                        <th>Units</th>
                        <th>Expiry Date</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>SKU / FNSKU</th>
                        <th>QTY</th>
                        <th>Prep Instruction</th>
                        <th>Action</th>
                    </thead>
                    <tbody id="multi_skus_body">

                    </tbody>
                </table>
            </div>
            
        </x-modal.body>
    </div>
    <x-modal.footer name="Save & Print Box Label" id="multi_sku_box_submit" type="button" />
    {{-- {{ Form::close() }} --}}

</x-modal>