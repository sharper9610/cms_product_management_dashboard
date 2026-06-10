@extends('layouts/layoutMaster')

@section('title', 'Publisher List - Pages')


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

  @vite('resources/assets/js/app-publishers.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Publisher List</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-publishers table border-top">
        <thead>
        <tr>

          <th></th>
          <th style="width:70%">Name</th>

          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPublisher" aria-labelledby="offcanvasAddPublisherLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddPublisherLabel" class="offcanvas-title">Add Publisher</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <form class="add-new-user pt-0" id="addNewPublisherForm" onsubmit="return false">
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <input type="text" class="form-control" id="add-prompt-name" name="name"
                   aria-label=""/>
            <label for="add-prompt-name">Name</label>
          </div>



          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>


    <div class="offcanvas offcanvas-end " tabindex="-1" id="offcanvasEditPublisher" aria-labelledby="offcanvasEditPublisherLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditPublisherLabel" class="offcanvas-title">Edit Publisher</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <div class="loader-wrapper text-center my-5" style="display: none;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div class="form-wrapper">
          <form class="edit-user-form pt-0" id="editPublisherForm" onsubmit="return false">
            @method('PUT')
            @csrf
            <input type="hidden" name="publisherId" id="publisherId" class="edit-publisher-id">

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="text" class="form-control edit-name" id="edit-name" placeholder="Publisher name" name="name"
                     aria-label="John Doe"/>
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
