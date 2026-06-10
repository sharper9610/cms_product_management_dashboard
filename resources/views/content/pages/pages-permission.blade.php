@extends('layouts/layoutMaster')

@section('title', 'Permission List - Pages')


@section('vendor-style')


  @vite(['resources/assets/vendor/libs/notyf/notyf.scss',
   'resources/assets/vendor/libs/animate-css/animate.scss'])



  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
   'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])





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


  @vite('resources/assets/js/app-permission-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">

    <div class="card-datatable table-responsive">
      <table class="datatables-permissions table border-top">
        <thead>
        <tr>
          <th></th>
          <th></th>
          <th>Name</th>
          <th>Group</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>
    <!-- Offcanvas to add new user -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPermission" aria-labelledby="offcanvasAddPermissionLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddPermissionLabel" class="offcanvas-title">Add</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-users pt-0" id="addNewPermissionForm" onsubmit="return false" >
          @csrf
          <div class="mb-6">
            <label class="form-label" for="name">Name</label>
            <input type="text" class="form-control" id="name"  placeholder=" " name="name" aria-label=" " />
          </div>
          <div class="mb-6">
            <label class="form-label" for="email">Group Name</label>
            <input type="text" id="group_name" class="form-control"  name="group_name" />
          </div>





          <button type="submit" name="submitButton" class="btn btn-primary me-3 data-submit">Submit</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>


    </div>















    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditPermission" aria-labelledby="offcanvasEditPermissionLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditPermissionLabel" class="offcanvas-title">Edit</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-users pt-0" id="editPermissionForm" onsubmit="return false" >
          @method('PUT')
          @csrf
          <input type="hidden" value="" class="edit-permission-id" name="permissionId" id="permissionId">
          <div class="mb-6">
            <label class="form-label" for="edit-name"> Name</label>
            <input type="text" class="form-control edit-name" id="edit-name" placeholder=" " name="name" aria-label=" " />
          </div>



          <div class="mb-6">
            <label class="form-label" for="edit-group_name">Group Name</label>
            <input type="text" class="form-control edit-group_name" name="group_name" id="edit-group_name" />
          </div>

          <button type="submit" name="submitButton" class="btn btn-primary me-3 data-submit">Submit</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>


    </div>


  </div>

@endsection
