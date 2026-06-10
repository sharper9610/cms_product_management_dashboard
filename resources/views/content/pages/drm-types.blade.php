@extends('layouts/layoutMaster')

@section('title', 'DRM Types List')

@section('vendor-style')


  @vite(['resources/assets/vendor/libs/notyf/notyf.scss',
   'resources/assets/vendor/libs/animate-css/animate.scss'])



  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])




  <style>

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
//    'resources/assets/vendor/libs/cleavejs/cleave.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',

      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'

  ])




@endsection

@section('page-script')

  @vite('resources/assets/js/app-drm-type-management.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">DRM Types</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>

          <th></th>
          <th style="width: 80%">Name</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>








    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddDrmType" aria-labelledby="offcanvasAddDrmTypeLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddDrmTypeLabel" class="offcanvas-title">Add DRM Type</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <form class="pt-0" id="addNewDrmTypeForm" onsubmit="return false">
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <input type="text" class="form-control"
                   id="add-drm-type-name"
                   placeholder="e.g., SecuROM, Tages"
                   name="name"
                 />
            <label for="add-drm-type-name">Name</label>
          </div>

          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>







    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditDrmType" aria-labelledby="offcanvasEditDrmTypeLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditDrmTypeLabel" class="offcanvas-title">Edit DRM Type</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <div class="loader-wrapper text-center my-5" style="display: none;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div class="form-wrapper">
          <form class="pt-0" id="editDrmTypeForm" onsubmit="return false">
            @method('PUT')
            @csrf
            <input type="hidden" name="userId" id="userId" class="edit-user-id">

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="text" class="form-control edit-name"
                     id="edit-name"
                     placeholder="e.g., SecuROM, Tages"
                     name="name"  />
              <label for="edit-name">Name</label>
            </div>

            <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
            <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
          </form>

        </div>


      </div>
    </div>


  </div>

@endsection
