<ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-6 p-6 pb-0" id="ship_tabs">
    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'draft') active @endif"
            href="{{ route('fba-shipments.index') }}"><i class="fa-regular fa-pen-ruler"></i> Draft</a>
    </x-fba_shipment.tab_menu>

    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'working') active @endif"
            href="{{ route('fba-shipments.fba_working_shipment_list') }}"><i class="fa-regular fa-bars-progress"></i>
            Working </a>
    </x-fba_shipment.tab_menu>

    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'shipped') active @endif"
            href="{{ route('fba-shipments.fba_common_shipment_list', ['status' => 'shipped']) }}">
            <i class="fa-regular fa-person-dolly"></i>Shipped 
        </a>
    </x-fba_shipment.tab_menu>
    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'in_transit') active @endif"
            href="{{ route('fba-shipments.fba_common_shipment_list', ['status' => 'in_transit']) }}">
            <i class="fa-solid fa-truck-fast"></i> In Transit 
        </a>
    </x-fba_shipment.tab_menu>
    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'receiving') active @endif"
            href="{{ route('fba-shipments.fba_common_shipment_list', ['status' => 'receiving']) }}">
            <i class="fa-solid fa-warehouse-full"></i> Receiving 
        </a>
    </x-fba_shipment.tab_menu>
    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'closed') active @endif"
            href="{{ route('fba-shipments.fba_common_shipment_list', ['status' => 'closed']) }}">
            <i class="fa-regular fa-check-circle"></i> Closed 
        </a>
    </x-fba_shipment.tab_menu>
    <x-fba_shipment.tab_menu>
        <a class="nav-link px-10 @if (isset($activeTab) && $activeTab == 'cancelled') active @endif"
            href="{{ route('fba-shipments.fba_common_shipment_list', ['status' => 'cancelled']) }}">
            <i class="fa-regular fa-ban"></i> Cancelled 
        </a>
    </x-fba_shipment.tab_menu>

</ul>
