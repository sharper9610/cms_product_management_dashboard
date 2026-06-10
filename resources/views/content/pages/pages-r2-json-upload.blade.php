@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'R2 Json Upload')

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
  @vite(['resources/assets/js/app-r2-json-upload.js'])
@endsection

@section('page-style')
@endsection

@section('content')
  @include('_partials.toast-message')
  @include('_partials.translating-loading')
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card shadow-sm border-0">
          <div class="card-header">
            <h5 class="mb-0">R2 JSON Upload</h5>
          </div>
          <div class="card-body mt-2">
            <form id="r2JsonUploadForm" class="row g-3">
              @csrf

              <div class="col-md-3">
                <label for="upload-source" class="form-label fw-semibold">Source</label>
                <select id="upload-source" name="source" class="form-select">
                  @foreach($sourceOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                  @endforeach
                </select>
                <small class="text-muted">
                  Select a source to upload only that source.
                </small>
              </div>

              <div class="col-md-9">
                <label for="product-sku" class="form-label fw-semibold">Product SKU(s)</label>
                <input
                  type="text"
                  id="product-sku"
                  name="sku"
                  class="form-control"
                  placeholder="Enter SKU or multiple SKUs separated by commas"
                >
                <small class="text-muted">
                  Optional. If entered, only the specified product(s) will be uploaded.
                </small>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Ignore Timestamp</label>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="ignore-timestamp" name="ignore_timestamp">
                  <label class="form-check-label" for="ignore-timestamp">
                    Upload all matching products regardless of last update timestamp
                  </label>
                </div>
              </div>

              <div class="col-md-8">

              </div>

              <div class="col-md-2 mt-3">
                <button type="submit" class="btn btn-primary w-100">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" id="r2JsonSpinner"></span>
                  Upload
                </button>
              </div>

              <div class="col-md-2 mt-3">
                <button type="button" class="btn btn-secondary w-100" id="parentSkuSyncBtn">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" id="parentSkuSyncSpinner"></span>
                  Parent SKU Sync
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
