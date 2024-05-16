@extends('layouts.app')

@section('title', 'Page 1')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fba-shipments.fba_working_shipment_list') }}">{{ __('Page 1') }}</a>
</li>
<li class="breadcrumb-item">{{ __('Page 1') }}</li>
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
                        <a href="" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-download fs-4"></i>
                            Download Invoice
                        </a>
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
                    </tbody>
                </table>
            </div>

            <div class="container-fluid py-5">
                <div class="row">
                    <div class="col-md-8">

                        <div class="card border shadow-sm">

                            <div class="card-header">
                                <h3 class="card-title">Package and Weight</h3>

                            </div>

                            <div class="card-body">
                                <form action="">

                                    <div class="row">
                                        <div class="col-12 col-sm-auto w-125px d-flex align-items-center">
                                            <label for="" class="fs-5 mb-1 mb-sm-0">Package size:</label>
                                        </div>
                                        <div class="col-12 col-sm">
                                            <div class="row d-flex align-items-center">
                                                <div class="col-auto mb-4 mb-sm-0"> <input type="text" class="form-control w-100px"></div>*
                                                <div class="col-auto mb-4 mb-sm-0"> <input type="text" class="form-control w-100px"></div>*
                                                <div class="col-auto mb-4 mb-sm-0"> <input type="text" class="form-control w-100px"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-12 col-sm-auto w-125px d-flex align-items-center">
                                            <label for="" class="fs-5 mb-1 mb-sm-0">Weight:</label>
                                        </div>
                                        <div class="col-12 col-sm">
                                            <div class="row">
                                                <div class="col-auto"> <input type="text" class="form-control w-100px"></div>

                                            </div>
                                        </div>
                                    </div>

                                </form>

                            </div>


                        </div>

                        <div class="card border shadow-sm mt-5">

                            <div class="card-header">
                                <h3 class="card-title"> Shiping Services</h3>
                            </div>

                            <div class="card-body">

                                <div class="mt-5">
                                    <form action="" class="d-flex">

                                        <div class="form-check form-check-custom form-check-solid form-check-lg mb-3 me-6">
                                            <input class="form-check-input" type="radio" name="ss" value="" id="flexRadioDefault" />
                                            <label class="form-check-label fs-5" for="flexRadioDefault">
                                                UPS
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid form-check-lg mb-3 me-6">
                                            <input class="form-check-input" type="radio" name="ss" value="" id="flexRadioDefault" />
                                            <label class="form-check-label fs-5" for="flexRadioDefault">
                                                Freigntcom
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid form-check-lg mb-3 me-6">
                                            <input class="form-check-input" type="radio" name="ss" value="" id="flexRadioDefault" />
                                            <label class="form-check-label fs-5" for="flexRadioDefault">
                                                Other
                                            </label>
                                        </div>

                                    </form>
                                </div>


                                <div class="row mt-4">
                                    <div class="col-12 col-sm-auto w-175px d-flex align-items-center">
                                        <label for="" class="fs-5 mb-1 mb-sm-0">Shipping Date:</label>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <div class=" row">
                                            <div class="col-auto">
                                                <div class="input-group" id="kt_td_picker_localization" data-td-target-input="nearest" data-td-target-toggle="nearest">
                                                    <input type="text" class="form-control" data-td-target="#kt_td_picker_localization" />
                                                    <span class="input-group-text" data-td-target="#kt_td_picker_localization" data-td-toggle="datetimepicker">
                                                        <i class="ki-duotone ki-calendar fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 col-sm-auto w-175px d-flex align-items-center">
                                        <label for="" class="fs-5 mb-1 mb-sm-0">Shipping Comapny:</label>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <div class="row">
                                            <div class="col-auto">
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 col-sm-auto w-175px d-flex align-items-center">
                                        <label for="" class="fs-5">Shipping Tracking:</label>
                                    </div>
                                    <div class="col-12 col-sm">
                                        <div class="row">
                                            <div class="col-auto">
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 col-sm-auto w-175px d-flex">
                                        <label for="" class="fs-5">Shipping Note:</label>
                                    </div>
                                    <div class="col-12 col-sm">
                                        <div class="row">
                                            <div class="col-auto">
                                                <textarea name="" id="" cols="30" rows="3" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <div class="col-md-4">

                        <div class="card border shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title">Summary</h3>

                            </div>
                            <div class="card-body">
                                <div class="mb-5 fs-5">
                                    Shiping Address : Shipping from
                                </div>
                                <div class="fw-bolder fs-3 mb-3">
                                    Total : <span>$ 2.7</span>
                                </div>
                                <div class="mb-2 fs-5 mt-6">
                                    <button class="btn btn-primary">Confirm Shipment</button>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
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
<script>
    new tempusDominus.TempusDominus(document.getElementById("kt_td_picker_localization"), {
        localization: {
            locale: "de",
            startOfTheWeek: 1,
            format: "dd/MM/yyyy"
        }
    });

    $("#kt_daterangepicker_2").daterangepicker({
        timePicker: true,
        startDate: moment().startOf("hour"),
        endDate: moment().startOf("hour").add(32, "hour"),
        locale: {
            format: "M/DD hh:mm A"
        }
    });
</script>

@endsection