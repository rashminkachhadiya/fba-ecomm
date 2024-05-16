@extends('layouts.app')

@section('title', 'Profile')
@section('breadcrumb')
<li class="breadcrumb-item text-primary"><a href="{{ route('users.index') }}"><i class="zmdi zmdi-home" aria-hidden="true"></i> {{ __('User') }}</a></li>
<li class="breadcrumb-item">{{ __('Profile') }} - {{Auth()->user()->name}}</li>
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
		<div class="post d-flex flex-column-fluid" id="kt_post">
			<div id="kt_content_container" class="container-fluid px-0">
				<div class="container-fluid">
					<div class="card ">
						<div class="card-body px-0 pb-20 mb-20">
							<div class="tab-content mt-5">
								{{ Form::open(['route' => ['update-profile'], 'name' => 'update_user_record', 'id' => 'update_user_form', 'method' => 'post']) }}
			                        @csrf
			                        {{ Form::hidden('profile_update', $user->id, ['id' => 'user_id']) }} 
									<div class="row">
									


										<div class="col">

											<div class="row">
												<div class="col-sm-4">
													<div class="mb-10">
														<label class="form-label required">Name</label>
														{{ Form::text('name', !empty($user->name) ? $user->name : old('name'), ['id' => 'name', "class" => "form-control form-control-solid validate","placeholder"=>"Name"]) }}
						                                @error('name')
						                                    <span class="invalid-feedback" role="alert">
						                                        <stro0ng>{{ $message }}</strong>
						                                    </span>
						                                @enderror
													</div>
												</div>
												
												<div class="col-sm-4">
													<div class="mb-10">
														<label class="form-label required">Email</label>
														{{ Form::email('email', !empty($user->email) ? $user->email : old('email'), ['id' => 'email', "class" => "form-control form-control-solid validate","placeholder"=>"Email"]) }}
						                                @error('email')
						                                    <span class="invalid-feedback" role="alert">
						                                        <strong>{{ $message }}</strong>
						                                    </span>
						                                @enderror
													</div>
												</div>
												
												<div class="col-sm-4">
													<div class="mb-10">
														<label class="form-label">Contact Number</label>
														{{ Form::text('contact_number', !empty($user->contact_no) ? $user->contact_no : old('contact_number'), ['id' => 'contact_number', "class" => "form-control form-control-solid validate","placeholder"=>"Contact Number", 'onkeypress'=>'return onlyNumericAllowed(this,event)']) }}
														@error('contact_number')
						                                    <span class="invalid-feedback" role="alert">
						                                        <strong>{{ $message }}</strong>
						                                    </span>
						                                @enderror
													</div>
												</div>
												<div class="col-sm-4">
													<div class="mb-10">
														<label class="form-label required">Password</label>
														<input type="password" name="password" class="form-control form-control-solid validate" placeholder="Confirm Password" value=""  autocomplete="off">
						                                @error('password')
						                                    <span class="invalid-feedback" role="alert">
						                                        <strong>{{ $message }}</strong>
						                                    </span>
						                                @enderror
													</div>
												</div>
												<div class="col-sm-4">
													<div class="mb-10">
														<label class="form-label required">Confirm Password</label>
														<input type="password" name="password_confirmation" class="form-control form-control-solid validate" placeholder="Confirm Password" value=""  autocomplete="off">
	                                                    @error('password_confirmation')
	                                                        <span class="invalid-feedback" role="alert">
	                                                            <strong>{{ $message }}</strong>
	                                                        </span>
	                                                    @enderror
													</div>
												</div>
											</div>
										</div>
									
										<div class="footer-fixed">
											<div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
													<!--begin::Container-->
													<div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-end">
														<!--begin::Copyright-->
														<div class="text-dark order-2 order-md-1">
															<a href="{{ url()->previous() }}" type="submit" name="" id="" class="btn btn-sm fs-6 btn btn-secondary">{{ __('Cancel') }}</a>
															<button type="submit" class="btn btn-sm fs-6 btn-primary ms-3">
							                                    {{ __('Save') }}
							                                </button>
														</div>
														<!--end::Copyright-->
														
													</div>
													<!--end::Container-->
												</div>
										</div>

									</div>
								{{ Form::close()}}
							</div>
						
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


@endsection
@section('page-script')
{!! JsValidator::formRequest('App\Http\Requests\UserProfileUpdateRequest', '#update_user_form') !!}

<script>
	var isUserUpdate = false;
$("form#update_user_form").submit(function(e) {
    e.preventDefault();
    if ($("#update_user_form").valid()) {
        if (!isUserUpdate) {
            var dataString = new FormData($("#update_user_form")[0]);
            isUserUpdate = true;
            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: dataString,
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function () {
                    show_loader();
                },
                complete: function () {
                    hide_loader();
                },
                success: function (data) {
                    hide_loader();  
                    isUserUpdate = false;
                    displaySuccessMessage(data.message);
				setTimeout(function(){
					location.reload();
				},2000);
                },
                error: function (xhr, err) {
                    isUserUpdate = false;
					console.log(xhr.responseJSON.message);
                    if (typeof xhr.responseJSON.message != "undefined" && xhr.responseJSON.message.length > 0) {
                        if (typeof xhr.responseJSON.errors != "undefined") {
                            commonFormErrorShow(xhr, err);
                        } else {
                            displayErrorMessage(xhr.responseJSON.message);
                        }
                    } else {
                        displayErrorMessage(xhr.responseJSON.errors);
                    }
                }
            });                 
        }
    }
});

</script>
@stop