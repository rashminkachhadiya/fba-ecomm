@extends('layouts.app')

@section('title', 'Page 2')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fba-shipments.fba_working_shipment_list') }}">{{ __('Page 1') }}</a>
</li>
<li class="breadcrumb-item">{{ __('Page 2') }}</li>

@endsection

@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

    <div class="card border p-4">
        <div class="row px-4 fs-3 mb-5">
            <div class=" col">Order ID: <strong class="fw-700">739279437</strong></div>
            <div class=" col text-end">Order Date: <strong class="fw-700">11/04/23</strong></div>
        </div>
        <div class="d-flex flex-wrap px-4">
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Total Price: </span>
                    <strong class="fw-700">$90</strong>
                </p>
            </div>
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Customer Name: </span>
                    <strong class="fw-700">John Ed</strong>
                </p>
            </div>
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Shipping Address: </span>
                    <strong class="fw-700">238 hbun ujb</strong>
                </p>
            </div>
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Billing Address: </span>
                    <strong class="fw-700">238 hbun ujb</strong>
                </p>
            </div>
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Email: </span>
                    <strong class="fw-700">John@gmail.com</strong>
                </p>
            </div>
            <div class="me-5 pe-4 my-1">
                <p class="mb-0" style="letter-spacing: 0.05em;">
                    <span> Phone: </span>
                    <strong class="fw-700">8437890238</strong>
                </p>
            </div>

        </div>
    </div>

    <div class="post d-flex flex-column-fluid" id="kt_post">

        <div id="kt_content_container" class="container-fluid px-0">

            <div class="container-fluid py-5">
                <div class="row align-items-center gy-3 gx-3 position-relative">

                    <div class="col col-xl-3 col-xl-2">
                        <div class="input-group flex-nowrap input-group-sm">

                            <input class="form-control px-5">
                            <button class="btn btn-sm btn-icon btn-outline btn-outline-solid btn-outline-default w-md-40px" type="button" id="{{ $button_id ?? 'search_button' }}">
                                <i class="fa-regular fa-magnifying-glass text-primary"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-auto ms-auto text-right-sm">

                        <a href="javascript:void(0)" id="Filter_drawer" class="ms-5 btn btn-sm btn-link">
                            <i class="fa-regular fa-bars-filter fs-4"></i>

                        </a>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="align-middle table table-row-bordered table-row-gray-300 gs-7 gy-4 gx-7 dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Image</th>
                            <th class="min-w-150px">Title</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>SKU</th>
                            <th>Total Discount</th>
                            <th>Variant Id</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                        <tr>
                            <td>466157049</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008GREEN</td>
                            <td>$0.00</td>
                            <td>39072856</td>
                        </tr>
                        <tr>
                            <td>518995019</td>
                            <td>Image</td>
                            <td>Ipod Nano - 8gb</td>
                            <td>$199.00</td>
                            <td>1</td>
                            <td>IPOD2008RED</td>
                            <td>$0.00</td>
                            <td>49148385</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<div class="bg-white drawer drawer-end" data-kt-drawer="true" data-kt-drawer-activate="true" data-kt-drawer-toggle="#Filter_drawer" data-kt-drawer-close=".close_drawer" data-kt-drawer-width="{default:'300px', 'md': '400px'}" style="width: 400px !important;">
    <div class="card w-100 rounded-0">
        <div class="card-header pe-5">
            <div class="card-title">
                <div class="d-flex justify-content-center flex-column me-3">
                    <div class="fs-4 fw-bolder text-gray-900 me-1 lh-1">Filter</div>
                </div>
            </div>
            <div class="card-toolbar">
                <div class="btn btn-sm btn-icon btn-active-light-primary close_drawer">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black"></rect>
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black"></rect>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">

        </div>
        <div class="card-footer text-end">
            <button class="btn btn-light" type="button" id="advance-filter-reset">Reset</button>
            <button class="btn btn-primary" type="submit" name="submit">Apply</button>
        </div>


    </div>
</div>

@endsection