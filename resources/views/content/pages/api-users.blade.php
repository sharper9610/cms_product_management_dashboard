@extends('layouts/layoutMaster')

@section('title', 'API user list - Pages')


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

  @vite('resources/assets/js/app-api-user-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">API Users</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>

          <th></th>
          <th>User</th>
          <th>IP/Domain</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
      </table>
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add API User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 h-100">
        <form class="add-new-user pt-0" id="addNewUserForm" onsubmit="return false">
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <input type="text" class="form-control" id="add-user-name" placeholder="John Doe" name="name"
                   aria-label="John Doe"/>
            <label for="add-user-name">Name</label>
          </div>
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <input type="text" id="add-user-email" class="form-control" placeholder="john.doe@example.com"
                   aria-label="john.doe@example.com" name="email"/>
            <label for="add-user-email">Email</label>
          </div>

          <div class="form-floating form-floating-outline mb-4 form-control-validation">
            <input type="text" id="add-user-domain" class="form-control" name="domain" placeholder="example.com"/>
            <label for="add-user-domain">Domain</label>
          </div>


          <div class="form-floating form-floating-outline mb-4 form-control-validation">
            <input type="text" id="add-user-ip" class="form-control" name="ip" placeholder="192.168.1.1"/>
            <label for="add-user-ip">IP Address</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <select name="status" id="user-status" class="form-select">
              <option value="">Select status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
            <label for="user-status">Status</label>
          </div>

          <div class="mb-5">
            <div class="form-password-toggle form-control-validation">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <input type="password" id="password" class="form-control" name="password"
                         placeholder="************" aria-describedby="password" autocomplete="new-password"/>
                  <label for="password">Password</label>
                </div>
                <span class="input-group-text cursor-pointer" onclick="togglePassword('password', this)">
        <i class="icon-base ri ri-eye-off-line icon-20px"></i>
      </span>
              </div>
            </div>
          </div>

          <div class="mb-5">
            <div class="form-password-toggle form-control-validation">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <input type="password" id="password_confirmation" class="form-control" name="password_confirmation"
                         placeholder="************" aria-describedby="password_confirmation"
                         autocomplete="new-password"/>
                  <label for="password_confirmation">Confirm Password</label>
                </div>
                <span class="input-group-text cursor-pointer" onclick="togglePassword('password_confirmation', this)">
        <i class="icon-base ri ri-eye-off-line icon-20px"></i>
      </span>
              </div>
            </div>
          </div>


          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditUser" aria-labelledby="offcanvasEditUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditUserLabel" class="offcanvas-title">Edit API User</h5>
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

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="text" class="form-control edit-name" id="edit-name" placeholder="John Doe" name="name"
                     aria-label="John Doe"/>
              <label for="edit-name">Name</label>
            </div>

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="email" id="edit-email" class="form-control edit-email" placeholder="john.doe@example.com"
                     aria-label="john.doe@example.com" name="email" autocomplete="off"/>
              <label for="edit-email">Email</label>
            </div>

            <div class="form-floating form-floating-outline mb-4 form-control-validation">
              <input type="text" id="edit-domain" class="form-control edit-domain" name="domain" placeholder="example.com"/>
              <label for="edit-domain">Domain</label>
            </div>


            <div class="form-floating form-floating-outline mb-4 form-control-validation">
              <input type="text" id="edit-ip" class="form-control edit-ip" name="ip" placeholder="192.168.1.1"/>
              <label for="edit-ip">IP Address</label>
            </div>

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <select name="status" id="edit-status" class="form-select edit-status">
                <option value="">Select status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
              <label for="edit-status">Status</label>
            </div>


            <div class="mb-5">
              <div class="form-password-toggle form-control-validation">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="new_password" class="form-control" name="new_password"
                           placeholder="************" aria-describedby="new_password" autocomplete="new-password"/>
                    <label for="new_password">Password</label>
                  </div>
                  <span class="input-group-text cursor-pointer" onclick="togglePassword('new_password', this)">
              <i class="icon-base ri ri-eye-off-line icon-20px"></i>
            </span>
                </div>
              </div>
            </div>

            <div class="mb-5">
              <div class="form-password-toggle form-control-validation">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="new_password_confirmation" class="form-control"
                           name="new_password_confirmation"
                           placeholder="************" aria-describedby="new_password_confirmation"
                           autocomplete="new-password"/>
                    <label for="new_password_confirmation">Confirm Password</label>
                  </div>
                  <span class="input-group-text cursor-pointer"
                        onclick="togglePassword('new_password_confirmation', this)">
              <i class="icon-base ri ri-eye-off-line icon-20px"></i>
            </span>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
            <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Cancel</button>
          </form>

        </div>


      </div>
    </div>


  </div>

@endsection
