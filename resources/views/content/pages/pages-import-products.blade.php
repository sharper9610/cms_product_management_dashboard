@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Product Import')

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apexcharts.js',
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app-import-products.js'])
@endsection

@section('page-style')
@endsection

@section('content')
  @include('_partials.toast-message')
  @include('_partials.translating-loading')
  <div class="container ">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card shadow-sm border-0">
          <div class="card-header">
            <h5 class="mb-0">Product Import</h5>
          </div>
          <div class="card-body mt-2">
            <form id="productImportForm" class="row g-3  ">
              @csrf

              {{-- SKU Input --}}
              <div class="col-md-10">
                <label for="product-sku" class="form-label fw-semibold">SKU</label>
                <input
                  type="text"
                  id="product-sku"
                  name="sku"
                  class="form-control"
                  placeholder="Enter one or multiple SKUs separated by commas (e.g. 12345,67890,11223)"
                  required
                >
                <small class="text-muted">
                  Enter one or more SKUs, separated by commas
                </small>
              </div>

              {{-- Source Dropdown --}}
              <div class="col-md-2">
                <label for="product-source" class="form-label fw-semibold">Source</label>
                <select id="product-source" name="source" class="form-select" required>
                  <option value="">Select</option>
                  <option value="ztorm">Ztorm</option>
                  <option value="incomm">Incomm</option>
                </select>
              </div>

              <div class="col-md-2 ">
                <button type="submit" class="btn btn-primary w-100">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" id="productSpinner"></span>
                  Import
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
    {{-- Cron Scheduler Log Section --}}
    <div class="row justify-content-center mt-4">
      <div class="col-md-12">
        <div class="card shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Product Cron Scheduler Log</h5>
          </div>

          <div class="card-body">

            {{-- ===== ZTORM LOG ===== --}}
            <h6 class="fw-bold text-primary mb-3">Supplier: Ztorm</h6>

            <div class="row mb-4 p-3 bg-light rounded border">
              <div class="col-md-4">
                <span class="fw-semibold">Status:</span>
                @if($ztormLog['status'] == 'completed')
                  <span class="badge bg-success ms-2">Completed</span>
                @elseif($ztormLog['status'] == 'running')
                  <span class="badge bg-warning text-dark ms-2">Running</span>
                @elseif($ztormLog['status'])
                  <span class="badge bg-info text-dark ms-2">{{ ucfirst($ztormLog['status']) }}</span>
                @else
                  <span class="badge bg-secondary ms-2">No Status</span>
                @endif
              </div>

              <div class="col-md-4">
                <span class="fw-semibold">Start Time:</span><br>
                <span class="text-dark">{{ $ztormLog['start_time'] ?: '—' }}</span>
              </div>

              <div class="col-md-4">
                <span class="fw-semibold">End Time:</span><br>
                <span class="text-dark">{{ $ztormLog['end_time'] ?: '—' }}</span>
              </div>
            </div>



            {{-- ===== INCOMM LOG ===== --}}
            <h6 class="fw-bold text-primary mb-3">Supplier: Incomm</h6>

            <div class="row mb-2 p-3 bg-light rounded border">
              <div class="col-md-4">
                <span class="fw-semibold">Status:</span>
                @if($incommLog['status'] == 'completed')
                  <span class="badge bg-success ms-2">Completed</span>
                @elseif($incommLog['status'] == 'running')
                  <span class="badge bg-warning text-dark ms-2">Running</span>
                @elseif($incommLog['status'])
                  <span class="badge bg-info text-dark ms-2">{{ ucfirst($incommLog['status']) }}</span>
                @else
                  <span class="badge bg-secondary ms-2">No Status</span>
                @endif
              </div>

              <div class="col-md-4">
                <span class="fw-semibold">Start Time:</span><br>
                <span class="text-dark">{{ $incommLog['start_time'] ?: '—' }}</span>
              </div>

              <div class="col-md-4">
                <span class="fw-semibold">End Time:</span><br>
                <span class="text-dark">{{ $incommLog['end_time'] ?: '—' }}</span>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>


  </div>



@endsection
