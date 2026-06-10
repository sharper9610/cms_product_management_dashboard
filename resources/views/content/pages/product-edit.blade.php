@extends('layouts/layoutMaster')

@section('title', 'Product List - Pages')


@section('vendor-style')

  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
    'resources/assets/vendor/libs/notyf/notyf.scss',
   'resources/assets/vendor/libs/animate-css/animate.scss',
   'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])


  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

  @vite(['resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/highlight/highlight.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/typeahead-js/typeahead.scss'])

  <style>
    #tagForm .tagify {
      min-height: 400px !important;
    }

    #allowed_countries_div .tagify {
      height: 200px;
      min-height: 400px !important;
    }

    .supported-languages .tagify {
      min-height: 70px !important;
      padding: 5px !important; /* optional for spacing */
    }

    .tags-show .tagify {
      min-height: 80px !important;
      padding: 8px !important; /* optional for spacing */
    }

    .modal-backdrop.show { z-index: 1040 !important; }
    .modal.show { z-index: 1050 !important; }
    #jsonExampleCollapse pre {
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.5;
      }


  </style>

@endsection

@section('vendor-script')

  @vite([
      'resources/assets/vendor/libs/notyf/notyf.js',
    'resources/assets/vendor/libs/moment/moment.js',
     'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/tagify/tagify.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',

  ])


  @vite(['resources/assets/vendor/libs/quill/katex.js',
   'resources/assets/vendor/libs/highlight/highlight.js',
  'resources/assets/vendor/libs/quill/quill.js',
  'resources/assets/vendor/libs/typeahead-js/typeahead.js',
'resources/assets/vendor/libs/bloodhound/bloodhound.js'])

@endsection

@section('page-script')

  @vite([
      'resources/assets/js/languages.js',
      'resources/assets/js/app-product-edit.js',
      'resources/assets/js/app-product-edit-basic-info.js',
      'resources/assets/js/app-product-edit-summary.js',
      'resources/assets/js/app-product-edit-localizations.js',
      'resources/assets/js/app-product-edit-media.js',
      'resources/assets/js/app-product-edit-prices.js',
      'resources/assets/js/app-product-edit-countries.js',
      'resources/assets/js/app-product-edit-tag.js',
      'resources/assets/js/app-product-edit-rating.js',
      'resources/assets/js/app-product-edit-system-requirements.js',
      'resources/assets/js/app-product-edit-skip-update.js',
  ])
  @if($product->source === 2)
      @vite(['resources/assets/js/app-product-edit-upload-json.js'])
  @endif
@endsection

@section('content')

  @include('_partials.toast-message')

  <div class="card">
    <div class="card-header border-bottom">



      <div class="d-flex justify-content-between align-items-center mb-2">
        <!-- Left: Title + SKU -->
        <div>
          <h5 class="card-title mb-1 fw-semibold text-primary">
            Edit Product — {{ $product->name }}
          </h5>
          <div class="d-flex flex-wrap gap-5 align-items-center">
            <!-- SKU -->
            <small class="text-muted">
              <span class="fw-medium text-dark">SKU:</span> {{ $product->sku }}
            </small>

            <!-- Source Badge -->
            <small>
              <span class="fw-medium text-dark">Source:</span>
              @php
                switch($product->source) {
                    case 1:
                        $sourceName = 'ZTORM';
                        $badgeClass = 'bg-label-primary';
                        break;
                    case 2:
                        $sourceName = 'INCOMM';
                        $badgeClass = 'bg-label-info';
                        break;
                    case 3:
                        $sourceName = 'POINT NEXUS';
                        $badgeClass = 'bg-label-warning';
                        break;
                    case 4:
                        $sourceName = 'GENBA';
                        $badgeClass = 'bg-label-success';
                        break;
                    default:
                        $sourceName = 'Unknown';
                        $badgeClass = 'bg-label-dark';
                }
              @endphp
              <span class="badge rounded-pill {{ $badgeClass }}">{{ $sourceName }}</span>
            </small>
          </div>

        </div>

        <!-- Right: Reload button -->
                <button type="button" class="btn btn-outline-secondary btn-sm" id="reloadProductData" title="Reload">
                  <i class="menu-icon icon-base ri ri-refresh-line"></i>
                </button>
      </div>
      <hr class="mt-2 mb-3"/>


      <input type="hidden" id="product_id_get" value="{{$product->id}}">
      <div class="row g-6">
        <div class="col-xl-12">
          {{--          <h6 class="text-body-secondary">Basic</h6>--}}
          <div class="nav-align-top nav-tabs-shadow">
            <ul class="nav nav-tabs  d-flex flex-nowrap overflow-x-auto" role="tablist">
              <li class="nav-item">
                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-basic_info" aria-controls="navs-top-basic_info" aria-selected="true">
                  Basic Info
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-summary" aria-controls="navs-top-summary" aria-selected="true">Summary
                </button>
              </li>
              <!-- <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-localizations" aria-controls="navs-top-localizations"
                        aria-selected="false">Localizations
                </button>
              </li> -->
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-media"
                        aria-controls="navs-top-media" aria-selected="false">Media
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-pricing" aria-controls="navs-top-pricing"
                        aria-selected="false">Pricing
                </button>
              </li>

              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-countries" aria-controls="navs-top-countries"
                        aria-selected="false">Countries
                </button>
              </li>
              <!-- <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-tags" aria-controls="navs-top-tags"
                        aria-selected="false">Tags
                </button>
              </li> -->
              <li class="nav-item">
                  <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                          data-bs-target="#navs-top-rating" aria-controls="navs-top-rating"
                          aria-selected="false">Rating
                  </button>
                </li>



              <!-- <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-system_requirements" aria-controls="navs-top-system_requirements"
                        aria-selected="false">System Requirements
                </button>
              </li> -->
              @if($product->source===2)
                <li class="nav-item">
                  <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                          data-bs-target="#navs-top-upload_json" aria-controls="navs-top-upload_json"
                          aria-selected="false">Upload JSON
                  </button>
                </li>
              @endif



              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-skip-update" aria-controls="navs-top-skip-update"
                        aria-selected="false">Skip update
                </button>
              </li>

            </ul>
            <div class="tab-content">
              <div class="tab-pane fade show active" id="navs-top-basic_info" role="tabpanel">
                @include('_partials.product-edit.basic_info')

              </div>
              <div class="tab-pane fade" id="navs-top-summary" role="tabpanel">
                @include('_partials.product-edit.summary')

              </div>
              <div class="tab-pane fade" id="navs-top-localizations" role="tabpanel">
                @include('_partials.product-edit.localizations')

              </div>
              <div class="tab-pane fade" id="navs-top-media" role="tabpanel">

                @include('_partials.product-edit.media')

              </div>
              <div class="tab-pane fade" id="navs-top-pricing" role="tabpanel">
                @include('_partials.product-edit.pricing')

              </div>
              <div class="tab-pane fade" id="navs-top-countries" role="tabpanel">

                @include('_partials.product-edit.countries')

              </div>
              <div class="tab-pane fade" id="navs-top-tags" role="tabpanel">
                @include('_partials.product-edit.tag')
              </div>

                <div class="tab-pane fade" id="navs-top-rating" role="tabpanel">

                  @include('_partials.product-edit.rating')

                </div>

              <div class="tab-pane fade" id="navs-top-system_requirements" role="tabpanel">

                @include('_partials.product-edit.system_requirements')

              </div>
              @if($product->source===2)
                <div class="tab-pane fade" id="navs-top-upload_json" role="tabpanel">
                  @include('_partials.product-edit.upload-json')
                </div>
              @endif



              <div class="tab-pane fade" id="navs-top-skip-update" role="tabpanel">

                @include('_partials.product-edit.skip-updates')

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @include('_partials.product-edit.suggestion-from-prompt-modal')
    @include('_partials.product-edit.price-edit-modal')

  </div>

@endsection
