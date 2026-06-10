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
      min-height: 150px !important;
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

  @vite('resources/assets/js/app-prompt-list.js')
@endsection

@section('content')

  @include('_partials.toast-message')



  <!-- Users List Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Prompt List</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>

          <th></th>
          <th>Name</th>
          <th>Description</th>
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
            <textarea id="add-prompt-description" class="form-control  description-area" name="description"
                      placeholder="Enter description"></textarea>
            <label for="add-prompt-description">Description</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template" class="form-control template-area" name="template"
                      placeholder="Enter template (en)"></textarea>
            <label for="add-prompt-template">Template(en)</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template_pt" class="form-control template-area" name="template_pt"
                      placeholder="Enter template (pt-br)"></textarea>
            <label for="add-prompt-template_pt">Template(pt-br)</label>
          </div>
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template_es" class="form-control template-area" name="template_es"
                      placeholder="Enter template (es-419)"></textarea>
            <label for="add-prompt-template_es">Template(es-419)</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template_gift_card" class="form-control template-area" name="template_gift_card"
                      placeholder="Enter template gift card (en)"></textarea>
            <label for="add-prompt-template_gift_card">Template gift card(en)</label>
          </div>

          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template_gift_card_pt" class="form-control template-area" name="template_gift_card_pt"
                      placeholder="Enter template gift card (pt-br)"></textarea>
            <label for="add-prompt-template_gift_card_pt">Template gift card(pt-br)</label>
          </div>
          <div class="form-floating form-floating-outline mb-5 form-control-validation">
            <textarea id="add-prompt-template_gift_card_es" class="form-control template-area" name="template_gift_card_es"
                      placeholder="Enter template gift card (es-419)"></textarea>
            <label for="add-prompt-template_gift_card_es">Template gift card(es-419)</label>
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
              <textarea id="edit-description" class="form-control edit-description description-area"
                        name="description" placeholder="Enter description"

              ></textarea>
              <label for="edit-description">Description</label>
            </div>

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template" class="form-control edit-template template-area"
                        name="template" placeholder="Enter template (en)"

              ></textarea>
              <label for="edit-template">Template (en)</label>
            </div>
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template_pt" class="form-control edit-template_pt template-area" name="template_pt"
                        placeholder="Enter template (pt-br)"></textarea>
              <label for="edit-template_pt">Template(pt-br)</label>
            </div>
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template_es" class="form-control edit-template_es template-area" name="template_es"
                        placeholder="Enter template (es-419)"></textarea>
              <label for="edit-template_es">Template(es-419)</label>
            </div>


            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template_gift_card" class="form-control edit-template_gift_card template-area"
                        name="template_gift_card" placeholder="Enter template gift card (en)"

              ></textarea>
              <label for="edit-template">Template gift card(en)</label>
            </div>
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template_gift_card_pt" class="form-control edit-template_gift_card_pt template-area" name="template_gift_card_pt"
                        placeholder="Enter template gift card (pt-br)"></textarea>
              <label for="edit-template_gift_card_pt">Template gift card(pt-br)</label>
            </div>
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <textarea id="edit-template_gift_card_es" class="form-control edit-template_gift_card_es template-area" name="template_gift_card_es"
                        placeholder="Enter template gift card (es-419)"></textarea>
              <label for="edit-template_gift_card_es">Template gift card(es-419)</label>
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
