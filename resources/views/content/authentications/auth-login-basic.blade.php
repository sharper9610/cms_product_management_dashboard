@php
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login Basic - Pages')

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
      <div class="authentication-inner py-6 mx-4">
        <!-- Login -->
        <div class="card p-sm-7 p-2">
          <!-- Logo -->
          <div class="app-brand justify-content-center mt-5">
            <a href="{{ url('/') }}" class="app-brand-link gap-3">
{{--              <span class="app-brand-logo demo">@include('_partials.macros')</span>--}}
              <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->

          <div class="card-body mt-1">
{{--            <h4 class="mb-1">Welcome to {{ config('variables.templateName') }}! 👋🏻</h4>--}}
            <p class="mb-5">Please sign-in to your account and start the adventure</p>



            @if(session('error'))
              <div class="alert alert-danger">
                {{ session('error') }}
              </div>
            @endif

            <form id="formAuthentication" class="mb-5" action="{{route('login')}}" method="POST">
              @csrf
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control"  id="email" name="email"
                  placeholder="Enter your email or username" autofocus />
                <label for="email">Email or Username</label>

                @error('email')
                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                @enderror
              </div>
              <div class="mb-5">
                <div class="form-password-toggle form-control-validation">
                  <div class="input-group input-group-merge">
                    <div class="form-floating form-floating-outline">
                      <input  type="password" id="password" class="form-control" name="password"
                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                        aria-describedby="password" />
                      <label for="password">Password</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i
                        class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                  </div>
                </div>
                @error('password')
                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                @enderror
              </div>
              <div class="mb-5 pb-2 d-flex justify-content-between pt-2 align-items-center">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me"> Remember Me </label>
                </div>
{{--                <a href="javascript:;" class="float-end mb-1">--}}
{{--                  <span>Forgot Password?</span>--}}
{{--                </a>--}}
              </div>
              <div class="mb-5">
                <button class="btn btn-primary d-grid w-100" type="submit">login</button>
              </div>
            </form>

{{--            <p class="text-center mb-5">--}}
{{--              <span>New on our platform?</span>--}}
{{--              <a href="{{ url('auth/register-basic') }}">--}}
{{--                <span>Create an account</span>--}}
{{--              </a>--}}
{{--            </p>--}}

{{--            <div class="divider my-5">--}}
{{--              <div class="divider-text">or</div>--}}
{{--            </div>--}}

{{--            <div class="d-flex justify-content-center gap-2">--}}
{{--              <a href="javascript:;" class="btn btn-icon btn-lg rounded-pill btn-text-facebook">--}}
{{--                <i class="icon-base ri ri-facebook-fill icon-24px"></i>--}}
{{--              </a>--}}

{{--              <a href="javascript:;" class="btn btn-icon btn-lg rounded-pill btn-text-twitter">--}}
{{--                <i class="icon-base ri ri-twitter-fill icon-24px"></i>--}}
{{--              </a>--}}

{{--              <a href="javascript:;" class="btn btn-icon btn-lg rounded-pill btn-text-github">--}}
{{--                <i class="icon-base ri ri-github-fill icon-24px"></i>--}}
{{--              </a>--}}

{{--              <a href="javascript:;" class="btn btn-icon btn-lg rounded-pill btn-text-google-plus">--}}
{{--                <i class="icon-base ri ri-google-fill icon-24px"></i>--}}
{{--              </a>--}}
{{--            </div>--}}
          </div>
        </div>
        <!-- /Login -->
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
