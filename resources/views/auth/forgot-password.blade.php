<!DOCTYPE html>
<html lang="en">
@include('layouts.head')

<!--begin::Body-->

<body id="kt_body" class="bg-body">
    <!--begin::Main-->
    <div class="d-flex flex-column flex-root">
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Aside-->
            <div class="d-flex flex-column flex-lg-row-fluid h-100" style="background-color: #eee">
                <!--begin::Wrapper-->
                <div class="d-flex flex-column align-items-center justify-content-center h-100">

                    <div style="background:#e7e9ee url('media/auth-img.jpg') center center no-repeat; width: 100%; height: 100%; background-size: 100% auto; ">
                        <!-- <img src="{{ asset('media/auth-img.png') }}" class=""> -->
                    </div>

                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Aside-->
            <!--begin::Body-->
            <div class="d-flex flex-column flex-lg-row-auto w-lg-700px w-xl-600px positon-xl-relative">
                <!--begin::Content-->
                <div class="d-flex flex-center flex-column flex-column-fluid">
                    @include('layouts.messages')
                    <!--begin::Wrapper-->
                    <div class="w-lg-500px p-10 p-lg-15 mx-auto">
                        <div class="d-flex justify-content-center">
                            <!-- <h1 class="fs-3x fw-800 mb-10">LOGO <span class="text-primary">HERE</span></h1> -->
                            <h1 class="fs-3x fw-800 mb-10">
                                <img alt="Logo" src="{{ asset('media/Stanbi_logo.svg') }}" />
                            </h1>
                        </div>
                        <!--begin::Form-->
                        <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate" method="POST" action="{{ route('password-reset-link') }}">
                            @csrf
                            <div class="text-center mb-10">
                                <h1 class="text-dark mb-3">Forgot Password ?</h1>
                                <div class="text-gray-400 fw-bold fs-4">Enter your email to reset your password.</div>
                            </div>
                            <div class="fv-row mb-10 fv-plugins-icon-container">
                                <label class="form-label fw-bolder text-gray-900 fs-6">Email</label>
                                <input class=" form-control" type="email" name="email" autocomplete="off" placeholder="{{ __('Email') }}" value="{{ old('email') }}" required autofocus />
                                @error('email')
                                <span class="invalid-feedback" role="alert" style="display: block !important;">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center pb-lg-0">
                                <a href="{{ route('login') }}" class="btn btn-lg btn-light-primary fw-bolder me-4">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Submit') }}
                                </button>
                            </div>
                            <!--end::Actions-->
                            <div></div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Content-->

            </div>
            <!--end::Body-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Main-->
    @include('layouts.footer')
    <!--begin::Page Custom Javascript(used by this page)-->
    <script src="../assets/js/custom/authentication/sign-in/general.js"></script>
    <!--end::Page Custom Javascript-->
    <script type="text/javascript">
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').hide();
            }, 3000);
        });
    </script>
</body>
<!--end::Body-->

</html>