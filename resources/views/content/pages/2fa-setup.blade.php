@extends('layouts/layoutMaster')

@section('title', 'Setup 2FA')

@section('content')

<div class="container py-5">
  <div class="card">
    <div class="card-body text-center">

      @if($errors->any())
        <div class="alert alert-danger">
          {{ $errors->first() }}
        </div>
      @endif

      @if(!$google2fa_enabled)
        <h4 class="mb-3">Enable Mandatory Two-Factor Authentication 🔐</h4>
        <p class="mb-4">
          Scan the QR code using Google Authenticator or any compatible app.
        </p>

        <!-- QR Code -->
        <div class="mb-4">
          {!! $qrImage !!}
        </div>

        <!-- Manual Key -->
        <p class="mb-2">Or enter this code manually:</p>
        <code class="fs-5">{{ $secret }}</code>

        <hr class="my-4">

        <!-- OTP Confirmation -->
        <form method="POST" action="{{ route('2fa.enable') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label">Enter Code to Confirm</label>
            <input
              type="text"
              name="one_time_password"
              class="form-control text-center fs-4 tracking-widest w-px-200 m-auto"
              maxlength="6"
              required>
          </div>

          <button class="btn btn-success">
            Enable 2FA
          </button>
        </form>
      @else
        <h4 class="mb-3">Disable Two-Factor Authentication 🔐</h4>
        <p class="mb-4">
          Enter the 6-digit code from your authenticator app.
        </p>

        <!-- OTP Confirmation -->
        <form method="POST" action="{{ route('2fa.disable') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label">Enter Code to Confirm</label>
            <input
              type="text"
              name="one_time_password"
              class="form-control text-center fs-4 tracking-widest w-px-200 m-auto"
              maxlength="6"
              required>
          </div>

          <button class="btn btn-danger">
            Disable 2FA
          </button>
        </form>
      @endif

    </div>
  </div>
</div>

@endsection

@section('page-script')

<script>
  document.querySelector('input[name="one_time_password"]').focus();
</script>

@endsection
