@extends('layouts/layoutMaster')

@section('title', 'Activity Log - Pages')


@section('vendor-style')


  @vite(['resources/assets/vendor/libs/notyf/notyf.scss',
   'resources/assets/vendor/libs/animate-css/animate.scss',
   ])

  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',])



@endsection

@section('vendor-script')
  @vite([

      'resources/assets/vendor/libs/notyf/notyf.js',
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
//    'resources/assets/vendor/libs/cleavejs/cleave.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',

      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/select2/select2.js',

  ])


@endsection
@section('page-style')

  <style>

    .wrap-text {
      width: 500px;          /* Fixed width */
      white-space: normal;    /* Allow wrapping */
      word-wrap: break-word;  /* Break long words */
      overflow-wrap: break-word;
    }

  </style>
@endsection

@section('page-script')

  @vite('resources/assets/js/app-activity-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')

  <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalLabel">Activity Log Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Dynamic content will be loaded here -->
          <div id="modalContent"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Activity Log</h5>

    </div>

    <div class="card-body mt-3 pb-0">
      <form class="dt_adv_search" method="GET">
        <div class="row">
          <div class="col-12">
            <div class="row g-3">


              <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">Description:</label>
                <input type="text" class="form-control form-control-sm dt-input dt-description" data-column=2 data-column-index="1">
              </div>

              <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">START DATE - END DATE:</label>
                <div class="mb-0">
                  <input type="text" class="form-control form-control-sm dt-date flatpickr-range dt-input" data-column="5" placeholder="StartDate to EndDate" data-column-index="4" name="dt_date" />
                  <input type="hidden" class="form-control dt-date start_date dt-input" data-column="5" data-column-index="4" name="value_from_start_date" />
                  <input type="hidden" class="form-control dt-date end_date dt-input" name="value_from_end_date" data-column="5" data-column-index="4" />
                </div>
              </div>

              <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label" for="filter-event">Event:</label>
                <select id="filter-event" class="form-select  dt-input mb-0"
                        data-column="6" data-column-index="5"
                        data-style="btn-default">
                  <option value="">All Events</option>
                  @foreach($events as $event)
                    <option value="{{ $event }}">{{ $event }}</option>
                  @endforeach
                </select>

              </div>

            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-activity-log table border-top">
        <thead>
        <th></th>
        <th>Log Name</th>
        <th>Description</th>
        <th>Properties</th>
        <th>User</th>
        <th>Date & Time</th>
        <th>Event</th>
        </thead>
      </table>
    </div>


  </div>

@endsection
