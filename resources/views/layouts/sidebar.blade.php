<div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">

    <div class="aside-logo flex-column-auto position-relative" id="kt_aside_logo">
        <a href="{{ route('users.index') }}">
            {{-- <img alt="Logo" src="{{ asset('media/logo-1-dark.svg') }}" style="height: 32px; margin-left: 10px;"
            class="logo" /> --}}
            <img alt="Logo" src="{{ asset('media/Stanbi_logo_white.svg') }}" style="height: 32px; margin-left: 10px;" class="logo" />
            {{-- <img alt="Logo" src="{{ asset('media/europarts--1.png') }}" style="height: 40px; margin-left: -5px"
            class="logo-small" /> --}}
            {{-- <img alt="Logo" src="{{ asset('media/logo-icon.svg') }}" style="height: 40px; margin-left: -5px"
            class="logo-small" /> --}}
        </a>
        <div id="kt_aside_toggle" class="shadow-sm btn btn-icon w-30px h-30px px-0 btn-active-color-primary aside-toggle bg-white position-absolute top-50 start-100 translate-middle" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
            <i class="fa-regular fa-angles-left text-primary"></i>
        </div>
    </div>
    <div class="flex-column-fluid overflow-hidden">
        <div class="aside-menu">
            <div class="hover-scroll-overlay-y" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="0">
                <div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500" id="#kt_aside_menu" data-kt-menu="true" data-kt-menu-expand="false">
                   
                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'users' ? 'active' : null }}" href="@if (session()->has('user_list')) {{ session('user_list') }} @else {{ route('users.index') }} @endif ">
                            <span class="menu-icon">
                                <i class="fa fa-user-cog fa-box-check fa-lg"></i>
                            </span>
                            <span class="menu-title">User Management</span>
                        </a>
                    </div>
                    
                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'stores' ? 'active' : null }}"
                            href="@if (session()->has('store_list')) {{ session('store_list') }} @else {{ route('stores.index') }} @endif ">
                            <span class="menu-icon">
                                <i class="fa-solid fa-store fa-lg"></i>
                            </span>
                            <span class="menu-title">Store Management</span>
                        </a>
                    </div>

                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'suppliers' || Request::segment(1) == 'supplier_contact_info' || Request::segment(1) == 'supplier_products' ? 'active' : null }}" href="@if (session()->has('supplier_list')) {{ session('supplier_list') }} @else {{ route('suppliers.index') }} @endif ">
                            <span class="menu-icon">
                                <i class="fa-solid fa-person-carry-box fa-lg fs-1"></i>
                            </span>
                            <span class="menu-title">Supplier Management</span>
                        </a>
                        <a class="menu-link {{ Request::segment(1) === 'products' ? 'active' : null }}" href="@if (session()->has('product_list')) {{ session('product_list') }} @else {{ route('products.index') }} @endif ">
                            <span class="menu-icon">
                                <i class="fa-solid fa-box-open fa-lg"></i>
                            </span>
                            <span class="menu-title">Product Management</span>
                        </a>
                    </div>

                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'purchase_orders' || Request::segment(1) === 'po-receiving' ? 'active' : null }}" href="{{ route('purchase_orders.index') }} ">
                            <span class="menu-icon">
                                <span class="fa-solid fa-file-invoice-dollar fs-2"></span>
                            </span>
                            <span class="menu-title">Purchase Order</span>
                        </a>
                    </div>

                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'restocks' || Request::segment(1) === 'restock-supplier-products' ? 'active' : null }}"
                            href="{{ route('restocks.index') }} ">
                            <span class="menu-icon">
                                <span class="fa-solid fa-warehouse fs-4"></span>
                            </span>
                            <span class="menu-title">Restock Management</span>
                        </a>
                    </div>

                    <div class="menu-item mb-1 menu-accordion {{ Request::segment(1) === 'fba-products' || Request::segment(1) ==='fba_products.selected_products' || Request::segment(1) === 'shipment-plans' || Request::segment(1) === 'fba-shipments' || Request::segment(1) === 'fba-shipments-status' || Request::segment(1) === 'prep-list' || Request::segment(2) === 'edit-prep' || Request::segment(1) === 'fba-shipments-transport_info' ? 'show' : null }}"
                    data-kt-menu-trigger="click">
                        <a href="#"
                            class="menu-link {{ Request::segment(1) === 'fba-products' || Request::segment(1) ==='fba_products.selected_products' || Request::segment(1) === 'shipment-plans' || Request::segment(1) === 'fba-shipments' || Request::segment(1) === 'fba-shipments-status' || Request::segment(1) === 'prep-list' || Request::segment(2) === 'edit-prep' || Request::segment(1) === 'fba-shipments-transport_info' ? 'active' : null }}">
                            <span class="menu-icon">
                                <i class="fa-duotone fa-truck-fast fs-4"></i>
                            </span>
                            <span class="menu-title">FBA</span>
                            <span class="menu-arrow"></span>
                        </a>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('fba-products.index') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'fba-products' ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">FBA Products List</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('shipment-plans.index') }}"
                                    class="menu-link py-3 {{ (Request::segment(1) === 'shipment-plans' && Request::segment(2) == '') ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">FBA Plan List</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('fba-shipments.index') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'fba-shipments' || Request::segment(1) === 'fba-shipments-status' || Request::segment(1) === 'fba-shipments-transport_info' ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">FBA Shipments</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('prep_list') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'prep-list' || Request::segment(2) === 'edit-prep' ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">FBA Prep</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('prep-productivity-list') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'prep-productivity-list' ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Prep Productivity</span>
                                </a>
                            </div>
                        </div>
    

                    </div>

                    <div class="menu-item mb-1 menu-accordion {{ Request::segment(1) === 'orders' || Request::segment(1) === 'shopify-orders'  ? 'show' : null }}"
                    data-kt-menu-trigger="click">
                        <a href="#"
                            class="menu-link {{ Request::segment(1) === 'orders' || Request::segment(1) === 'shopify-orders' ? 'active' : null }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-rectangle-list fs-3"></i>
                            </span>
                            <span class="menu-title">Orders Management</span>
                            <span class="menu-arrow"></span>
                        </a>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('orders.list') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'orders'  ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Amazon Orders</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion pt-1">
                            <div class="menu-item mb-1">
                                <a href="{{ route('shopify-orders.index') }}"
                                    class="menu-link py-3 {{ Request::segment(1) === 'shopify-orders'  ? 'active' : null }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Shopify Orders</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item mb-1">
                        <a class="menu-link {{ Request::segment(1) === 'settings' ? 'active' : null }}" href="{{ route('settings.index') }} ">
                            <span class="menu-icon">
                                <i class="fa-solid fa-gear fs-3"></i>
                            </span>
                            <span class="menu-title">General Setting</span>
                        </a>
                    </div>

                

            </div>
        </div>
    </div>
</div>
</div>