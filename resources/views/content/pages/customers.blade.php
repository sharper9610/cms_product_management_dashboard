@extends('layouts/layoutMaster')

@section('title', 'Customers - Pages')


@section('vendor-style')

@vite(['resources/assets/vendor/libs/notyf/notyf.scss',
   'resources/assets/vendor/libs/animate-css/animate.scss',
   'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])


@vite([
       'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

<style>
    /* Apply to all td starting from the 3rd column */
    .datatables-products > tbody > tr > td:nth-child(n+3) {
      padding-left: 3px !important;
      padding-right: 2px !important;
    }

    /* Apply to all th starting from the 3rd column */
    .table thead tr th:nth-child(n+3) {
      padding-left: 3px !important;
      padding-right: 2px !important;
    }

    .fixedHeader-floating thead {
      background-color: #E7E7FF !important; /* Light gray background */
      box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); /* Optional shadow */
      transition: background-color 0.3s ease-in-out;

    }

    #filter-section {
      visibility: hidden; /* keeps layout space intact */
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    #filter-section.show {
      visibility: visible;
      opacity: 1;
    }

  </style>

@endsection

@section('vendor-script')
  @vite([

      'resources/assets/vendor/libs/notyf/notyf.js',
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',

      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
      'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
   'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js'
  ])

@endsection

@section('page-script')

  @vite('resources/assets/js/app-customer-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')
  @include('_partials.translating-loading')

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0" id="all_customers_title">All Customers</h5>
    </div>

    <div class="row mt-2 mx-2" id="filter-section">

        {{-- Search --}}
        <div class="col-md-4">
            <div class="input-group input-group-merge">
                <span class="input-group-text" id="customer-search-icon">
                    <i class="icon-base ri ri-search-line icon-20px"></i>
                </span>
                <input
                    type="text"
                    id="search-customers"
                    class="form-control dt-input"
                    data-column="1" data-column-index="0"
                    placeholder="Search by name or email..."
                    aria-label="Search..."
                    aria-describedby="customer-search-icon" />
            </div>
        </div>

        {{-- State --}}
        <div class="col-md-2">
            <div class="form-floating form-floating-outline">
                <select id="filter-state" class="form-select dt-input" data-column="4" data-column-index="3">
                    <option value="">All States</option>
                    <option value="ENABLED">Enabled</option>
                    <option value="DISABLED">Disabled</option>
                    <option value="INVITED">Invited</option>
                </select>
                <label for="filter-state">State</label>
            </div>
        </div>

        {{-- Verified email --}}
        <div class="col-md-2">
            <div class="form-floating form-floating-outline">
                <select id="filter-verified" class="form-select dt-input" data-column="5" data-column-index="4">
                    <option value="">All</option>
                    <option value="1">Verified</option>
                    <option value="0">Not Verified</option>
                </select>
                <label for="filter-verified">Email Verified</label>
            </div>
        </div>

    </div>

    <div class="card-datatable table-responsive">
        <table id="datatables-customers" class="datatables-customers table border-top">
            <thead>
                <tr>
                    <th></th>
                    <th>CUSTOMER</th>
                    <th>SHOPIFY ID</th>
                    <th>LOCALE</th>
                    <th>STATE</th>
                    <th>VERIFIED</th>
                    <th>PHONE</th>
                    <th>AMOUNT SPENT</th>
                    <th>ORDERS</th>
                    <th>JOINED</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection
