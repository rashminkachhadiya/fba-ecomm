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
                            <h1 class="fs-3x fw-800 mb-10 text-center">
                                {{-- <img  class="w-50" src="{{ asset('media/logo-login.svg') }}"> --}}
                                <img class="w-50" alt="Logo" src="{{ asset('media/Stanbi_logo.svg') }}" />
                                {{-- <img  class="w-50" src="{{ asset('media/europarts--1.png') }}"> --}}
                            </h1>
                        </div>
                        <?php ?>
                        <!--begin::Form-->
                        <form method="POST" class="form w-100" novalidate="novalidate" id="kt_sign_in_form" action="{{ route('login') }}">
                            @csrf
                            <div class="text-center mb-10">
                                <h1 class="text-dark mb-3">Sign In</h1>
                            </div>
                            <div class="fv-row mb-10">
                                <label class="form-label fs-6 fw-bolder text-dark">Email</label>
                                <input class="form-control form-control-lg" type="text" name="email" autocomplete="off" />
                                @error('email')
                                    <span class="invalid-feedback" role="alert" style="display: block !important;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="fv-row mb-10">
                                <div class="d-flex flex-stack mb-2">
                                    <label class="form-label fw-bolder text-dark fs-6 mb-0">Password</label>
                                    {{-- <a href="{{ route('password.request') }}" class="link-primary fs-6 fw-bolder">Forgot Password ?</a> --}}
                                </div>
                                <div class="position-relative">
                                    <input class="form-control form-control-lg" type="password" name="password" autocomplete="off" id="password" />
                                    <span class="toggle-password" id="togglePassword"><i class="fa fa-eye"></i></span>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert" style="display: block !important;">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                                    <span class="indicator-label">Continue</span>
                                    <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.footer')
    <!-- <script src="{{ asset('js/custom/authentication/sign-in/general.js')}}"></script> -->

<script>
    $(document).ready(function() {
      $('#togglePassword').click(function() {
        const passwordInput = $('#password');
        const passwordFieldType = passwordInput.attr('type');
        
        if (passwordFieldType === 'password') {
          passwordInput.attr('type', 'text');
          $('#togglePassword i').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          passwordInput.attr('type', 'password');
          $('#togglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });
    });
  </script>
</body>
</html>