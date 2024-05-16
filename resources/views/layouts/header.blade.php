<style>
	.dt-hasChild+tr>td {
		padding: 0 !important;
	}
</style>
<div id="kt_header" style="" class="header align-items-stretch shadow-sm">
	<div class="container-fluid d-flex align-items-stretch justify-content-between">
		<div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Show aside menu">
			<div class="btn btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" id="kt_aside_mobile_toggle">
				<span class="svg-icon svg-icon-1">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="black" />
						<path d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z" fill="black" />
					</svg>
				</span>
			</div>
		</div>
		<div class="d-flex align-items-center justify-content-center flex-grow-1 flex-lg-grow-0">
			<a href="#" class="d-lg-none">
				<img alt="Logo" src="{{ asset('media/Stanbi_logo_white.svg') }}" class="h-40px" />
				{{-- <img alt="Logo" src="{{ asset('media/europarts--1.png') }}" class="h-40px" /> --}}
			</a>
		</div>
		<div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
			<div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
				<h1 class="d-flex text-dark fw-bolder fs-3 align-items-center my-1">@yield('title')</h1>
				<span class="h-20px border-gray-300 border-start mx-4"></span>
				<ul class="breadcrumb fw-bold fs-7 my-1">
					@yield('breadcrumb')
				</ul>
			</div>
			<div class="d-flex align-items-stretch flex-shrink-0 ms-auto">
				<div class="d-flex align-items-center ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
					<div class="cursor-pointer symbol symbol-30px symbol-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
						<img class="rounded-circle" src="{{ !empty(Auth::user()->profile_image) ? config('app.url').'/storage/' . Auth::user()->profile_image : asset('media/circle-user-solid.svg') }}" alt="user" />
					</div>
					<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px" data-kt-menu="true">
						<div class="menu-item px-3">
							<div class="menu-content d-flex align-items-center px-3">
								<div class="symbol symbol-50px me-5">
									<img alt="Logo" src="{{ !empty(Auth::user()->profile_image) ? config('app.url').'/storage/' . Auth::user()->profile_image : asset('media/circle-user-solid.svg') }}" />
								</div>
								<div class="d-flex flex-column">
									<div class="fw-bolder d-flex align-items-center fs-5">{{ (Auth::check()) ? Auth::user()->name : '' }}</div>
									<a href="#" class="fw-bold text-muted text-hover-primary fs-7">{{ (Auth::check()) ? Auth::user()->email : '' }}</a>
								</div>
							</div>
						</div>
						<div class="separator my-2"></div>
						{{-- <div class="menu-item px-5"> --}}
						{{-- <a class="menu-link px-5" href="{{ route('get-edit-profile', ['id' => Auth::user()->id]) }}">Profile</a> --}}
						{{-- <a class="menu-link px-5" href="">Profile</a> --}}
						{{-- </div> --}}
						<div class="menu-item px-5">
							<a class="menu-link px-5" href="javascript:void(0)" id="header-logout-btn" onclick="userLogout('logout-form')">
								{{ __('Logout') }}
							</a>
							<a class="menu-link px-5" href="{{route('profile')}}" id="header-logout-btn">
								{{ __('Profile') }}
							</a>
							<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
								@csrf
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>