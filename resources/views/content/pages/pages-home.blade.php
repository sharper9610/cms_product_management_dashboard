@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Home')

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

  <script>

    window.totalProductCount = @json($totalCount);
    window.totalActiveProductCount = @json($activeCount);
    window.totalCompletedProductCount = @json($completedCount);
    window.totalIncompletedProductCount = @json($incompletedCount);
  </script>

  @vite(['resources/assets/js/home.js'])

@endsection

@section('page-style')
  <style>
    /* Make list group items feel more clickable */
    .list-group-item-action {
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }

    .country-card {
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }

    /* Style for the currently selected country */
    .list-group-item-action.active {
      background-color: #0d6efd;
      /* Bootstrap primary color */
      border-color: #0d6efd;
      color: white;
    }

    .list-group-item-action.active h6 {
      color: white !important;
    }

    /* Ensure the text inside the active item is readable */
    .list-group-item-action.active small {
      color: rgba(255, 255, 255, 0.85);
    }
  </style>
@endsection


@section('content')
  @include('_partials.toast-message')
  @include('_partials.translating-loading')





  <div class="">
    <div class="row g-6">
      <div class="col-sm-6 col-lg-3 ">
        <div class="card card-border-shadow-primary h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ri ri-shopping-bag-line icon-24px"></i>
                                </span>
              </div>
              <h4 class="mb-0">{{$totalCount}}</h4>
            </div>
            <h6 class="mb-0 fw-normal">Total Products</h6>
            <p class="mb-0">
              <small class="text-body-secondary">{{$activeCount}} active</small>

            </p>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3 ">
        <div class="card card-border-shadow-success h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ri ri-checkbox-circle-line icon-24px"></i>
                                </span>
              </div>
              <h4 class="mb-0">{{$completedCount}}</h4>
            </div>
            <h6 class="mb-0 fw-normal">Complete Products</h6>
            <p class="mb-0">
              <small class="text-body-secondary">{{$completionRate}}% completion rate</small>
            </p>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3 ">
        <div class="card card-border-shadow-danger h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="icon-base ri ri-error-warning-line icon-24px"></i>
                                </span>
              </div>
              <h4 class="mb-0">{{$incompletedCount}}</h4>
            </div>
            <h6 class="mb-0 fw-normal">Incomplete Products</h6>
            <p class="mb-0">
              {{-- <span class="me-1 fw-medium">Need attention</span>--}}
              <small class="text-body-secondary">Need attention</small>
            </p>
          </div>
        </div>
      </div>
      {{-- <div class="col-sm-6 col-lg-3 ">--}}
      {{-- <div class="card card-border-shadow-info h-100">--}}
      {{-- <div class="card-body">--}}
      {{-- <div class="d-flex align-items-center mb-2">--}}
      {{-- <div class="avatar me-4">--}}
      {{-- <span class="avatar-initial rounded bg-label-info">--}}
      {{-- <i class="icon-base ri ri-global-line icon-24px"></i>--}}
      {{-- </span>--}}
      {{-- </div>--}}
      {{-- <h4 class="mb-0">13</h4>--}}
      {{-- </div>--}}
      {{-- <h6 class="mb-0 fw-normal">Countries Served</h6>--}}
      {{-- <p class="mb-0">--}}
      {{-- --}}{{-- <span class="me-1 fw-medium">14 total SKUs</span>--}}
      {{-- <small class="text-body-secondary">14 total SKUs</small>--}}
      {{-- </p>--}}
      {{-- </div>--}}
      {{-- </div>--}}
      {{-- </div>--}}

      <div class="col-sm-6 col-lg-3">
        <div class="card card-border-shadow-info h-100 country-card" data-bs-toggle="modal"
             data-country-modal="#countriesModal">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ri ri-global-line icon-24px"></i>
                                </span>
              </div>
              <h4 class="mb-0">{{$totalCountries}}</h4>
            </div>
            <h6 class="mb-0 fw-normal">Countries Served</h6>
            <p class="mb-0">
              <small class="text-body-secondary">{{$totalProducts}} total SKUs</small>
            </p>
          </div>
        </div>
      </div>

      <div class="{{ auth()->user()->can('product.import') ? 'col-lg-8' : 'col-lg-12' }} mb-6">

      <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Completion Overview</h5>
            <i class="ri-bar-chart-horizontal-line fs-4 text-muted"></i>
          </div>
          <div class="card-body">
            <!-- Chart -->
            <div id="completionOverviewChart"></div>

            <!-- Legend Stats -->
            <div class="d-flex justify-content-around mt-4">
              <div class="d-flex align-items-center">
                <span class="badge bg-success rounded-circle me-2 p-2"></span>
                <div>
                  <h6 class="mb-0 text-success">{{$completedCount}}</h6>
                  <small class="text-muted">Complete</small>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span class="badge bg-danger rounded-circle me-2 p-2"></span>
                <div>
                  <h6 class="mb-0 text-danger">{{$incompletedCount}}</h6>
                  <small class="text-muted">Incomplete</small>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span class="badge bg-info rounded-circle me-2 p-2"></span>
                <div>
                  <h6 class="mb-0 text-info">{{$completionRate}}%</h6>
                  <small class="text-muted">Overall</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      @can('product.import')

      <div class="col-lg-4 mb-6">
        <div class="card h-100 shadow-sm text-center p-4">
          <h5 class="mb-4 fw-bold">Import Options</h5>

          <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
            <!-- Product Import Button -->


            <button
              class="btn btn-primary d-flex align-items-center justify-content-center gap-2 px-4 py-3 fw-semibold shadow-sm"
              data-bs-toggle="modal"
              data-bs-target="#productImportModal"
            >
              <i class="ri ri-database-2-line fs-5"></i>
              <span>Import Product & Prices</span>
            </button>


            <!-- Price Import Button -->
{{--            <button class="btn btn-success d-flex align-items-center justify-content-center gap-2 px-4 py-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#priceImportModal">--}}
{{--              <i class="ri ri-price-tag-2-line fs-5"></i>--}}
{{--              <span>Price</span>--}}
{{--            </button>--}}
          </div>

          <!-- Import Status Section -->
{{--          <div class="row mt-1">--}}
{{--            @foreach($imortStatus as $source => $types)--}}
{{--              <h6 class="fw-semibold  text-uppercase w-100">{{ ucfirst($source) }}</h6>--}}

{{--              @foreach($types as $typeName => $type)--}}
{{--                @php--}}
{{--                  $collapseId = $source.'-'.$typeName;--}}
{{--                  $isRunning = $type['situation'] === 'running';--}}
{{--                  $badgeClass = $isRunning ? 'bg-warning text-dark' : 'bg-success';--}}
{{--                @endphp--}}

{{--                <div class="col-md-6 mb-1">--}}
{{--                  <div class="border rounded bg-light shadow-sm position-relative h-100">--}}
{{--                    <!-- Header clickable -->--}}
{{--                    <div--}}
{{--                      class="d-flex justify-content-between align-items-center p-3"--}}
{{--                      data-bs-toggle="collapse"--}}
{{--                      href="#collapse-{{ $collapseId }}"--}}
{{--                      role="button"--}}
{{--                      aria-expanded="false"--}}
{{--                      aria-controls="collapse-{{ $collapseId }}"--}}
{{--                    >--}}
{{--                      <span class="fw-semibold text-uppercase small">{{ ucfirst($typeName) }}</span>--}}
{{--                      <span class="badge {{ $badgeClass }} rounded-pill small px-2 py-1">--}}
{{--              {{ ucfirst($type['situation']) }}--}}
{{--            </span>--}}
{{--                    </div>--}}

{{--                    <!-- Collapsible content -->--}}
{{--                    <div class="collapse border-top" id="collapse-{{ $collapseId }}">--}}
{{--                      <div class="p-3 small text-muted text-start">--}}
{{--                        <div><strong>Start:</strong> {{ $type['start_time'] ?: '-' }}</div>--}}
{{--                        @if($type['situation'] === 'complete')--}}
{{--                          <div><strong>End:</strong> {{ $type['end_time'] ?: '-' }}</div>--}}
{{--                        @endif--}}
{{--                      </div>--}}
{{--                    </div>--}}
{{--                  </div>--}}
{{--                </div>--}}
{{--              @endforeach--}}
{{--            @endforeach--}}
{{--          </div>--}}


        </div>
      </div>


      {{-- Product Import Modal --}}
      <div class="modal fade" id="productImportModal" tabindex="-1" aria-labelledby="productImportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title" id="productImportModalLabel">Product Import</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productImportForm">
              @csrf
              <div class="modal-body">
{{--                <div class="mb-3 form-check">--}}
{{--                  <input type="checkbox" class="form-check-input" id="product-import-all" name="import_all">--}}
{{--                  <label class="form-check-label fw-bold" for="product-import-all">Import All Products</label>--}}
{{--                </div>--}}

                {{-- SKU Input - Added class 'sku-field' for targeting --}}
                <div class="mb-3 sku-field">
                  <label for="product-sku" class="form-label">SKU</label>
                  <input type="text"
                         id="product-sku"
                         name="sku"
                         class="form-control"

                         placeholder="Enter one or multiple SKUs separated by commas (e.g. 12345,67890,11223)"

                         required>
                  <small class="text-muted d-block mt-1">
                    Enter one or more SKUs, separated by commas
                  </small>
                </div>

                <div class="mb-3">
                  <label for="product-source" class="form-label">Source</label>
                  <select id="product-source" name="source" class="form-select" required>
                    <option value="">Select Source</option>
                    <option value="ztorm">Ztorm</option>
                    <option value="incomm">Incomm</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" id="productSpinner"></span>
                  Import Product
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {{-- Price Import Modal --}}
      {{-- Price Import Modal --}}
      <div class="modal fade" id="priceImportModal" tabindex="-1" aria-labelledby="priceImportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title" id="priceImportModalLabel">Price Import</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="priceImportForm">
              @csrf
              <div class="modal-body">


                {{-- SKU Input - Added class 'sku-field' for targeting --}}
                <div class="mb-3 sku-field">
                  <label for="price-sku" class="form-label">SKU</label>
                  <input type="text" id="price-sku" name="sku" class="form-control" placeholder="Enter SKU" required>
                </div>

                <div class="mb-3">
                  <label for="price-source" class="form-label">Source</label>
                  <select id="price-source" name="source" class="form-select" required>
                    <option value="">Select Source</option>
                    <option value="ztorm">Ztorm</option>
                    <option value="incomm">Incomm</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" id="priceSpinner"></span>
                  Import Price
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      @endcan
    </div>


    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0" id="all_products_title">Product Completeness</h5>
        <small>Track missing data for each product
        </small>


      </div>
      <div class="row mt-2 mx-2 " id="filter-section">
        {{-- <div class="col-md-4">
            <div class="input-group input-group-merge">
                <span class="input-group-text" id="basic-addon-search31">
                    <i class="icon-base ri ri-search-line icon-20px"></i>
                </span>
                <input type="text" class="form-control dt-input"
                    placeholder="Search by name, SKU, publisher, developer, or genre..." aria-label="Search..."
                    aria-describedby="basic-addon-search31" data-column=1 data-column-index="0" />
            </div>
        </div> --}}

        <div class="col-md-4">
          <div class="input-group input-group-merge">
                        <span class="input-group-text" id="basic-addon-search-icon">
                            <i class="icon-base ri ri-search-line icon-20px"></i>
                        </span>

            <input type="text" class="form-control dt-input multiple-input" placeholder="Search by "
                   aria-label="Search..."
                   aria-describedby="basic-addon-search-icon" data-column=1 data-column-index="0"
                   style="border-right: none;"/>

            <select class="form-select" id="search-filter-type"
                    style="text-align: center; max-width: 150px; border-left: none; /* Adjust width as needed */">
              <option value="sku">SKU</option>
              <option value="name">Name</option>
              <option value="developers">Developer</option>

            </select>

          </div>
        </div>
        <div class="col-md-2">
          <div class="form-floating form-floating form-floating-outline">
            <select id="filter-publisher" class="form-select  dt-input" data-column=12 data-column-index="11"
                    data-style="btn-default">
              <option value="">All Publishers</option>

              @foreach($publishers as $publisher)
                @php
                  $name = $publisher->publisher_name ?? '';
                  $label = $name !== '' ? $name : 'Empty Publisher';
                  $value = $publisher->publisher_id ?? 'null';
                @endphp
                <option value="{{ $value  }}">{{ $label }}</option>
              @endforeach

            </select>
            <label for="filter-publisher">Publisher</label>

          </div>
        </div>

        <div class="col-md-6">
          <div class="row">

            <div class="col-md-3">
              <div class="form-floating form-floating form-floating-outline">
                <select class="form-select dt-input" id="filter-status" data-column=14 data-column-index="13"
                        aria-label="Filter Status">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                <label for="filter-status">Status</label>
              </div>
            </div>


            <div class="col-md-3">
              <div class="form-floating form-floating form-floating-outline">
                <select id="filter-supplier" class=" form-select dt-input" data-column=15 data-column-index="14"
                        data-style="btn-default">
                  <option value="">All Suppliers</option>
                  <option value="1">ztorm</option>
                  <option value="2">incomm</option>

                </select>
                <label for="filter-status">Supplier</label>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-floating form-floating-outline">
                <select id="filter-release" class="form-select dt-input" data-column="16" data-column-index="15">
                  <option value="">All Releases</option>
                  <option value="upcoming">Upcoming Release</option>
                  <option value="new_release">New Releases</option>
                </select>
                <label for="filter-release">Release Type</label>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-floating form-floating-outline">
                <select id="filter-completed" class="form-select dt-input"
                        data-column="17" data-column-index="16">
                  <option value="">All</option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </select>
                <label for="filter-completed">Completed</label>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="card-datatable table-responsive">


        <table id="product-completeness" class="datatables-product-completeness table border-top">
          <thead>
          <tr>

            <th></th>
            <th>SKU</th>
            <th>PRODUCT</th>
            <th>Completion</th>
            <th>Main Image</th>
            <th>Localizations</th>
            <th>Pricing</th>
            <th>Countries</th>
            <th>Tags</th>
            <th>Ratings</th>
            <th>System Req</th>
            <th>Missing Items</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th>Actions</th>


          </tr>
          </thead>
        </table>
      </div>
    </div>


  </div>


  <div class="modal fade" id="countriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header border-0">
          <div class="modal-title-container">
            <h5 class="modal-title" id="countriesModalLabel">Countries Served</h5>
            <small class="text-muted d-block">Detailed breakdown of product availability by country</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="height: 70vh;">
          <div class="row h-100">

            <div class="col-md-4 h-100 d-flex flex-column">
              <div class="input-group mb-3">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search"></i> </span>
                <input type="text" class="form-control border-start-0" placeholder="Search countries..."
                       id="countrySearch">
              </div>
              <div class="flex-grow-1 overflow-auto pe-2">
                <div class="list-group" id="country-list">
                  <p class="text-center text-muted">Loading countries...</p>
                </div>
              </div>
            </div>

            <div class="col-md-8 ps-md-4 border-start h-100 overflow-auto" id="country-details">
              <div class="d-flex flex-column justify-content-center align-items-center h-100 text-center">
                <i class="bi bi-geo-alt-fill" style="font-size: 4rem; color: #e0e0e0;"></i>
                <h4 class="mt-3">Select a Country</h4>
                <p class="text-muted">Choose a country from the list to view its details and available
                  products.</p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
