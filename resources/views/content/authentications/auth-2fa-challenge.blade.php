@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', '2FA Verification')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card p-sm-7 p-2">
        <!-- Logo -->
        <div class="app-brand justify-content-center mt-5">
          <a href="{{ url('/') }}" class="app-brand-link gap-3">
            <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
          </a>
        </div>

        <div class="card-body mt-1 text-center">
          <h4 class="mb-2">Two-Factor Authentication 🔐</h4>
          <p class="mb-4">Enter the 6-digit code from your authenticator app.</p>

          @if($errors->any())
          <div class="alert alert-danger">
            {{ $errors->first() }}
          </div>
          @endif

          <form method="POST" class="mb-5"  action="{{ route('2fa.verify') }}">
            @csrf

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input
                id="one-time-code"
                type="text"
                name="one_time_password"
                class="form-control text-center fs-4 tracking-widest"
                maxlength="6"
                autocomplete="one-time-code"
                required>
            </div>
            <div class="mb-5">
              <button class="btn btn-primary d-grid w-100">Verify</button>
            </div>
          </form>

        </div>
      </div>

      <img src="{{ asset('assets/img/illustrations/tree-3.png') }}" alt="auth-tree"
        class="authentication-image-object-left d-none d-lg-block" />
      <img src="{{ asset('assets/img/illustrations/auth-basic-mask-' . $configData['theme'] . '.png') }}"
        class="authentication-image d-none d-lg-block scaleX-n1-rtl" height="172" alt="triangle-bg"
        data-app-light-img="illustrations/auth-basic-mask-light.png"
        data-app-dark-img="illustrations/auth-basic-mask-dark.png" />
      <img src="{{ asset('assets/img/illustrations/tree.png') }}" alt="auth-tree"
        class="authentication-image-object-right d-none d-lg-block" />

    </div>
  </div>
</div>
@endsection

@section('page-script')

<script>
  document.querySelector('input[name="one_time_password"]').focus();
</script>

@endsection
