@php
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
  <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
    <a href="{{ url('/') }}" class="app-brand-link gap-2">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
    </a>

    <!-- Display menu close icon only for horizontal-menu with navbar-full -->
    @if (isset($menuHorizontal))
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
        <i class="icon-base ri ri-close-line d-flex align-items-center justify-content-center"></i>
      </a>
    @endif
  </div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
  <div
    class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 {{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base ri ri-menu-line icon-md"></i>
    </a>
  </div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

  @if ($configData['hasCustomizer'] == true)
    <!-- Style Switcher -->
    <div class="navbar-nav align-items-center">
      <li class="nav-item dropdown me-sm-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" id="nav-theme"
          href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="icon-base ri ri-sun-line icon-22px theme-icon-active"></i>
          <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
          <li>
            <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
              aria-pressed="false">
              <span> <i class="icon-base ri ri-sun-line icon-md me-3" data-icon="sun-line"></i>Light</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark"
              aria-pressed="true">
              <span> <i class="icon-base ri ri-moon-clear-line icon-md me-3" data-icon="moon-clear-line"></i>Dark</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
              aria-pressed="false">
              <span> <i class="icon-base ri ri-computer-line icon-md me-3" data-icon="computer-line"></i>System</span>
            </button>
          </li>
        </ul>
      </li>
    </div>
    <!-- / Style Switcher-->
  @endif

    @if($notices && count($notices) > 0)
      <div style="width: 72%" class="marquee-container w-100  py-1 px-2 rounded">
        <marquee behavior="scroll" direction="left" scrollamount="5" onmouseover="this.stop();" onmouseout="this.start();">
          @foreach($notices as $notice)
            <a href="javascript:void(0);"
               class="text-dark fw-semibold mx-3 notice-link"
               data-bs-toggle="modal"
               data-bs-target="#noticeModal"
               data-title="{{ $notice->title }}"
               data-description="{{ $notice->details }}">
              📢 {{ $notice->title }}
            </a>
          @endforeach
        </marquee>
      </div>
    @endif

  <ul class="navbar-nav flex-row align-items-center ms-auto">
    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">

{{--          <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"--}}
{{--            alt="alt" class="rounded-circle" />--}}

          <img src="{{asset('assets/img/avatars/empty_person.png') }}" alt class="w-px-40 h-auto rounded-circle">



        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('profile.show') ? route('profile.show') : 'javascript:void(0);' }}">
            <div class="d-flex align-items-center">
{{--              <div class="flex-shrink-0 me-2">--}}
{{--                <div class="avatar avatar-online">--}}
{{--                 --}}
{{--                  <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"--}}
{{--                    alt="alt" class="w-px-40 h-auto rounded-circle" />--}}
{{--                  --}}
{{--                </div>--}}
{{--              </div>--}}
              <div class="flex-grow-1">
                <h6 class="mb-0 small">
                  @if (Auth::check())
                    {{ Auth::user()->name }}
                  @else
                    John Doe
                  @endif
                </h6>
                <small class="text-body-secondary">
                  @if (Auth::check() && Auth::user()->role)
                    {{ Auth::user()->role->name }}
                  @else
                    No Role
                  @endif
                </small>              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider"></div>
        </li>

        @if (Auth::check())
          <li>
            @if (!Auth::user()->google2fa_enabled)
              <a class="dropdown-item" href="{{ route('2fa.setup') }}">
                <i class="icon-base ri ri-shield-keyhole-line me-2"></i>
                <span>Enable 2FA</span>
              </a>
            @else
              <a class="dropdown-item" href="{{ route('2fa.setup') }}">
                <i class="icon-base ri ri-shield-check-line me-2 text-success"></i>
                <span>Manage 2FA</span>
              </a>
            @endif
          </li>

          <li>
            <div class="dropdown-divider"></div>
          </li>
        @endif

        @if (Auth::check())
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <a class="btn btn-danger d-flex" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
                <small class="align-middle">Logout</small>
              </a>
            </div>
          </li>
          <form method="POST" id="logout-form" action="{{ route('logout') }}">
            @method('DELETE')
            @csrf
          </form>
        @else
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <a class="btn btn-danger d-flex"
                href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                <small class="align-middle">Login</small>
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
              </a>
            </div>
          </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>
