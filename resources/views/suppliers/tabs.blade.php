@php
    $suppliers_segment = request()->segment(1);
    if (isset($supplier_id) && !empty($supplier_id)) {
        $basic_info_route = route('suppliers.edit', ['supplier' => $supplier_id]);
    }else {
        $basic_info_route = route('suppliers.create');
    }
@endphp

<x-tabs>
    <li class="nav-item @if (isset($suppliers_segment) && $suppliers_segment == 'suppliers') active @endif">
        <a href={{ $basic_info_route }}>{{ __('Basic Information') }}</a></li>
    <li class="nav-item @if (isset($suppliers_segment) && $suppliers_segment == 'supplier_contact_info') active @endif" id="supplier_contact_info">
        <a href="javascript:void(0)">{{ __('Contact Information') }}</a></li>
    <li class="nav-item @if (isset($suppliers_segment) && $suppliers_segment == 'supplier_products') active @endif">
        <a href="javascript:void(0)" id="supplier_products">{{ __('Products') }}</a></li>
</x-tabs>
