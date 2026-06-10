@extends('layouts/layoutMaster')

@section('title', 'Notice List - Pages')


@section('vendor-style')


  @vite([
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
      'resources/assets/vendor/libs/notyf/notyf.scss',
'resources/assets/vendor/libs/moment/moment.js',

   'resources/assets/vendor/libs/animate-css/animate.scss'])



  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])




  <style>
    .flatpickr-calendar {
      z-index: 2000 !important;
    }

  </style>
@endsection

@section('vendor-script')
  @vite([
     'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/notyf/notyf.js',
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
//    'resources/assets/vendor/libs/cleavejs/cleave.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',

      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'

  ])






@endsection

@section('page-script')

  @vite('resources/assets/js/app-notice-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Notices</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>
          <th></th>
          <th>Title</th>
          <th>Details</th>
          <th>Date Start-End</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>








    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <form class="add-new-user pt-0" id="addNewUserForm" onsubmit="return false">
          <div class="mb-6">
            <label class="form-label" for="title">Title</label>
            <textarea  class="form-control" id="title"  placeholder="Notice title" name="title" aria-label="Notice title"></textarea>

          </div>
          <div class="mb-6">
            <label class="form-label" for="details">Details</label>
            <textarea id="details" class="form-control"  placeholder="Notice Details" aria-label="" name="details" autocomplete="off"></textarea>

          </div>

          <div class="mb-6">
            <label class="form-label" for="start_date">Start Date</label>
            <input type="text" id="start_date" class="form-control flatpickr-datetime"
                   placeholder="DD-MM-YYYY HH:mm::ss"
                   aria-label="" name="start_date" autocomplete="off"/>
          </div>
          <div class="mb-6">
            <label class="form-label" for="end_date">End Date</label>
            <input type="text" id="end_date" class="form-control flatpickr-datetime end_date-flatpickr"
                   placeholder="DD-MM-YYYY HH:mm::ss"
                   aria-label="" name="end_date" autocomplete="off"/>
          </div>
          <div class="mb-6">
            <label class="form-label" for="status">Select Status</label>
            <select name="status" id="status" class="form-select">
              <option value="" selected disabled >Select status</option>
              <option value="active"   >Active</option>
              <option value="inactive"   >Inactive</option>

            </select>
          </div>










          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>







    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditUser" aria-labelledby="offcanvasEditUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditUserLabel" class="offcanvas-title">Edit User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <div class="loader-wrapper text-center my-5" style="display: none;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div class="form-wrapper">
          <form class="edit-user-form pt-0" id="editUserForm" onsubmit="return false">
            @method('PUT')
            @csrf
            <input type="hidden" name="userId" id="userId" class="edit-user-id">






            <input type="hidden" value="" class="edit-notice-id" name="noticeId" id="noticeId">


            <div class="mb-6">
              <label class="form-label" for="title">Title</label>
              <textarea class="form-control edit-title" id="edit-title"  placeholder="Notice title" name="title" aria-label="Notice title" >
            </textarea>

            </div>
            <div class="mb-6">
              <label class="form-label" for="edit-details">Details</label>

              <textarea id="edit-details" class="form-control edit-details flatpickr-datetime"  placeholder="Notice Details" aria-label="" name="details" autocomplete="off">    </textarea>


            </div>

            <div class="mb-6">
              <label class="form-label" for="edit-start_date">Start Date</label>
              <input type="text" id="edit-start_date" class="form-control edit-start_date flatpickr-datetime"
                     placeholder="DD-MM-YYYY HH:mm::ss"
                     aria-label="" name="start_date" autocomplete="off"/>
            </div>
            <div class="mb-6">
              <label class="form-label" for="edit-end_date">End Date</label>
              <input type="text" id="edit-end_date" class="form-control edit-end_date"
                     placeholder="DD-MM-YYYY HH:mm::ss"
                     aria-label="" name="end_date" autocomplete="off"/>
            </div>
            <div class="mb-6">
              <label class="form-label" for="edit-status">Select Status</label>
              <select name="status" id="edit-status" class="form-select edit-status">
                <option value="" selected disabled >Select status</option>
                <option value="active"   >Active</option>
                <option value="inactive"   >Inactive</option>

              </select>
            </div>


            <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
            <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
          </form>

        </div>


      </div>
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasViewNotice" aria-labelledby="offcanvasViewNoticeLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasViewNoticeLabel" class="offcanvas-title ">
          View Notice
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>

      <div class="offcanvas-body mx-0 p-4">
        <div class="view-notice-content">


          <div class="mb-3">
            <label class="form-label fw-semibold text-muted">Title</label>
            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notice-title">--</p>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-muted">Details</label>
            <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notice-details" style="white-space: pre-wrap;">--</div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Start Date</label>
              <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notice-start_date">--</p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">End Date</label>
              <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notice-end_date">--</p>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold text-muted">Status</label>
            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notice-status">--</p>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
            <i class="bx bx-x me-1"></i> Close
          </button>
        </div>
      </div>
    </div>


  </div>

@endsection
