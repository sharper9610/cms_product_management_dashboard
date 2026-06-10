@extends('layouts/layoutMaster')

@section('title', 'Product List - Pages')


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

  @vite('resources/assets/js/app-product-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')
  @include('_partials.translating-loading')


  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0" id="all_products_title">All Products</h5>


    </div>
    <div class="" id="filter-section">
      <div class="row mt-2 mx-2">
        <div class="col-md-3">
          <div class="input-group input-group-merge">
                          <span class="input-group-text" id="basic-addon-search-icon">
                              <i class="icon-base ri ri-search-line icon-20px"></i>
                          </span>

            <input type="text" class="form-control dt-input multiple-input" placeholder="Search by "
                  aria-label="Search..."
                  aria-describedby="basic-addon-search-icon" data-column=1 data-column-index="0"
                  style="border-right: none;"/>

            <select class="form-select" id="search-filter-type"
                    style="text-align: center; max-width: 110px; border-left: none; /* Adjust width as needed */">
              <option value="sku">SKU</option>
              <option value="name">Name</option>
              <option value="developers">Developer</option>

            </select>

          </div>
        </div>

        <div class="col-md-2">
          <div class="form-floating form-floating form-floating-outline">
            <select id="filter-publisher" class="form-select  dt-input"
                    data-column=2 data-column-index="1"
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

        <div class="col-md-1">
          <div class="form-floating form-floating form-floating-outline">
            <select class="form-select dt-input" id="filter-status"
                    data-column=8 data-column-index="7"
                    aria-label="Filter Status">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <label for="filter-status">Status</label>
          </div>
        </div>

        <div class="col-md-2">
          <div class="form-floating form-floating form-floating-outline">
            <select id="filter-supplier" class=" form-select dt-input"
                    data-column=7 data-column-index="6"
                    data-style="btn-default">
              <option value="">All Suppliers</option>
              <option value="1">ztorm</option>
              <option value="2">incomm</option>
              <option value="3">point nexus</option>
              <option value="4">genba</option>
              <option value="999">manual</option>
            </select>
            <label for="filter-status">Supplier</label>
          </div>
        </div>

        <div class="col-md-2">
          <div class="form-floating form-floating-outline">
            <select id="filter-release" class="form-select dt-input"
                    data-column="9" data-column-index="8">
              <option value="">All Releases</option>
              <option value="upcoming">Upcoming Release</option>
              <option value="new_release">New Releases</option>
            </select>
            <label for="filter-release">Release Type</label>
          </div>
        </div>

        <div class="col-md-2">
          <div class="form-floating form-floating-outline">
            <select id="filter-completed" class="form-select dt-input"
                    data-column="10" data-column-index="9">
              <option value="">All</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
            <label for="filter-completed">Completed</label>
          </div>
        </div>
      </div>

      <div class="row mt-2 mx-2">
        <div class="col-md-2">
          <div class="form-floating form-floating-outline">
            <select id="filter-country" class="form-select dt-input"
                    data-column="12" data-column-index="11">
              <option value="">All Countries</option>
              @foreach($countries as $country)
                <option value="{{ $country }}">{{ $country }}</option>
              @endforeach
            </select>
            <label for="filter-country">Country</label>
          </div>
        </div>
        @php
          $missingFields = [
              ['value' => 'name', 'label' => 'Name'],
              ['value' => 'release_date', 'label' => 'Release date'],
              ['value' => 'download_date', 'label' => 'Download date'],
              ['value' => 'publisher_name', 'label' => 'Publisher Name'],
              ['value' => 'platform', 'label' => 'Platform'],
              ['value' => 'product_type', 'label' => 'Product type'],
              ['value' => 'region_tag', 'label' => 'Region tag'],
              ['value' => 'default_language', 'label' => 'Default language'],
              ['value' => 'developers', 'label' => 'Developers'],
              ['value' => 'pegi_ratings', 'label' => 'Pegi ratings'],
              ['value' => 'average_rating', 'label' => 'Average rating'],
              ['value' => 'total_reviews', 'label' => 'Total reviews'],


              ['value' => 'auxiliary_field', 'label' => 'Auxiliary field (Incomm)'],
              ['value' => 'bundled_products', 'label' => 'Bundled products'],
              ['value' => 'classification', 'label' => 'Classification (Incomm)'],
              ['value' => 'community_discussion', 'label' => 'Community discussion'],

              ['value' => 'dlc_products', 'label' => 'Dlc products'],
              ['value' => 'dlc_master_product_id', 'label' => 'Dlc master product id'],
              ['value' => 'is_dlc', 'label' => 'IS DLC'],
              ['value' => 'face_value', 'label' => 'Face value'],
              ['value' => 'redemption', 'label' => 'Redemption (Incomm)'],
              ['value' => 'redemption_field', 'label' => 'Redemption field (Incomm)'],
              ['value' => 'validade', 'label' => 'Validade (Incomm)'],


              ['value' => 'franchise_tags', 'label' => 'Franchise tags'],
              ['value' => 'community_tags', 'label' => 'Community tags'],
              ['value' => 'genre_tags', 'label' => 'Genre tags'],
              ['value' => 'legal_texts', 'label' => 'Terms & Conditions'],
              ['value' => 'seo_tags', 'label' => 'Seo tags'],
              ['value' => 'long_description', 'label' => 'Long description'],
              ['value' => 'short_description', 'label' => 'Short description'],
              ['value' => 'system_requirements', 'label' => 'System requirements'],
              ['value' => 'supported_languages', 'label' => 'Supported languages'],

              ['value' => 'media', 'label' => 'Media'],
              ['value' => 'media_main', 'label' => 'Media (Main)'],
              ['value' => 'prices', 'label' => 'Prices'],








          ];

          // Sort array alphabetically by 'label'
          usort($missingFields, function ($a, $b) {
              return strcmp($a['label'], $b['label']);
          });
        @endphp

        <div class="col-md-3">
          <div class="form-floating form-floating-outline">
            <select id="filter-missing" class="form-select dt-input" data-column="11" data-column-index="10">
              <option value="">Select</option>
              @foreach ($missingFields as $field)
                <option value="{{ $field['value'] }}">{{ $field['label'] }}</option>
              @endforeach
            </select>
            <label for="filter-missing">Missing Field</label>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">


      <table id="datatables-products" class="datatables-products table border-top">
        <thead>
        <tr>

          <th></th>
          <th>PRODUCT</th>
          <th>PUBLISHER</th>
          <th>DEVELOPERS</th>
          <th>GENRES</th>
          <th>CATEGORY</th>
          <th>PLATFORM</th>
          <th>SUPPLIER</th>
          <th>STATUS</th>
          <th>RELEASE DATE</th>
          <th></th>
          <th></th>
          <th>COUNTRY</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>


  </div>

@endsection
