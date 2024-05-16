<x-modal id="add_purchase_order_form">
    {{ Form::open(['route' => ['create-po'], 'name' => 'add_new_po', 'id' => 'add_new_po_form', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'onsubmit' => 'return false']) }}
    @csrf
    <x-modal.header />

    <x-modal.body>
        <div class="row">
            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Name Of Purchase Order" required="required" />
                    {{ Form::text('po_number', isset($purchaseOrders) && !empty($purchaseOrders->po_number) ? $purchaseOrders->po_number : null, ['id' => 'po_number', 'class' => 'form-control validate', 'placeholder' => 'Name Of Purchase Order']) }}
                    @error('po_number')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-5">
                    <x-forms.label title="Order Date" required="required" />
                    <x-datepicker>
                        {{ Form::text('po_order_date', isset($purchaseOrders) && $purchaseOrders->po_order_date ? $purchaseOrders->po_order_date : date('m-d-Y'), ['id' => 'po_order_date', 'class' => 'form-control']) }}
                        <x-datepicker.calendar />
                        <x-datepicker.reset id="reset_po_order_date" />
                    </x-datepicker>
                    @error('po_order_date')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-5">
                    <x-forms.label title="Expected Delivery Date" />
                    <x-datepicker>
                        {{ Form::text('expected_delivery_date', isset($purchaseOrders) && $purchaseOrders->expected_delivery_date ? $purchaseOrders->expected_delivery_date : '', ['id' => 'expected_delivery_date', 'class' => 'form-control']) }}
                        <x-datepicker.calendar />
                        <x-datepicker.reset id="reset_expected_delivery_date" />
                    </x-datepicker>
                    @error('expected_delivery_date')
                        <x-forms.error :message="$message" />
                    @enderror
                </div>
            </div>

            <div class="col-sm-12">
                <div class="mb-5">
                    <x-forms.label title="Order Note" />
                        {{ Form::textarea('order_note', isset($purchaseOrders) && $purchaseOrders->order_note ? $purchaseOrders->order_note : '', ['id' => 'order_note', 'class' => 'form-control', 'rows'=>4]) }}
                </div>
            </div>

        </div>
        {{ Form::hidden('',$supplier_id, ['id' => 'supplier_id','name' => 'supplier_id']) }}
        {{ Form::hidden('', isset($purchaseOrders) ? $purchaseOrders->id : 0 , ['id' => 'purchase_order_id', 'name' => 'purchase_order_id']) }}
        {{ Form::hidden('', route('purchase_orders.index'), ['id' => 'purchase_order_list_url']) }}


    </x-modal.body>

    <x-modal.footer name="Create" />
    {{ Form::close() }}

</x-modal>
