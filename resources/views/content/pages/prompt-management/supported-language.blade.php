@extends('layouts/layoutMaster')

@section('title', 'Prompt List - Pages')


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
    .description-area {
      min-height: 80px !important;
    }

    .template-area {
      min-height: 200px !important;
    }

    .offcanvas {
      min-width: 50% !important;
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
//    'resources/assets/vendor/libs/cleavejs/cleave.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',

      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'

  ])

@endsection

@section('page-script')

  @vite('resources/assets/js/app-prompt-supported-language-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Supported Language Prompt List</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>

          <th></th>
          <th>Name</th>
          <th>Interface</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add Prompt</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <form class="add-new-user pt-0" id="addNewUserForm" onsubmit="return false">
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <input type="text" class="form-control" id="add-prompt-name" name="name"
                   aria-label="John Doe"/>
            <label for="add-prompt-name">Name</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-interface" class="form-control  template-area" name="interface"
                      placeholder="Enter interface"></textarea>
            <label for="add-prompt-interface">Interface</label>
          </div>
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-full_audio" class="form-control  template-area" name="full_audio"
                      placeholder="Enter full_audio"></textarea>
            <label for="add-prompt-full_audio">Full_audio</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-subtitles" class="form-control  template-area" name="subtitles"
                      placeholder="Enter subtitles"></textarea>
            <label for="add-prompt-subtitles">Subtitles</label>
          </div>


          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <select name="status" id="user-status" class="form-select">
              <option value="">Select status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
            <label for="user-status">Status</label>
          </div>


          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>


    <div class="offcanvas offcanvas-end " tabindex="-1" id="offcanvasEditUser" aria-labelledby="offcanvasEditUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditUserLabel" class="offcanvas-title">Edit Prompt</h5>
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
            <input type="hidden" name="promptId" id="promptId" class="edit-prompt-id">

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="text" class="form-control edit-name" id="edit-name" placeholder="John Doe" name="name"
                     aria-label="John Doe"/>
              <label for="edit-name">Name</label>
            </div>


            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-interface" class="form-control edit-interface template-area"
                        name="interface" placeholder="Enter interface"

              ></textarea>
              <label for="edit-interface">Interface	</label>
            </div>

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-full_audio" class="form-control edit-full_audio template-area "
                        name="full_audio" placeholder="Enter full_audio"

              ></textarea>
              <label for="edit-full_audio">Full audio	</label>
            </div>

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-subtitles" class="form-control edit-subtitles template-area"
                        name="subtitles" placeholder="Enter subtitles"

              ></textarea>
              <label for="edit-subtitles">Subtitles	</label>
            </div>








            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <select name="status" id="edit-status" class="form-select edit-status">
                <option value="">Select status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
              <label for="edit-status">Status</label>
            </div>


            <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
            <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
          </form>

        </div>


      </div>
    </div>


  </div>

@endsection
