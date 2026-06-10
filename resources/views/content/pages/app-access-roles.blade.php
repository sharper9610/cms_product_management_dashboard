@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Roles ')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
  <script>
    var routes = {
      accessRolesList: @json(route('access-roles.index')),
      rolesStore: @json(route('access-roles.store')),
      updateRole: "{{ url('/access-roles') }}",
      deleteRole: "{{ url('/access-roles') }}",
      getRole: "{{ url('/access-roles') }}"
    };
  </script>
@vite(['resources/assets/js/app-access-roles.js', 'resources/assets/js/modal-add-role.js'])
@endsection

@section('content')
  @include('_partials.toast-message')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Roles</h4>
    @can('role.create')
      <button data-bs-target="#addRoleModal" data-bs-toggle="modal" class="btn btn-sm btn-primary text-nowrap add-new-role">Add New Role</button>
    @endcan
  </div>
<p class="mb-6">A role provided access to predefined menus and features so that depending on assigned role an administrator can have access to what user needs.</p>
<!-- Role cards -->
<div class="row g-6" id="roleList">


</div>
<!--/ Role cards -->

<!-- Modal -->
@include('_partials/_modals/modal-add-role')
<!-- / Add Role Modal -->
@endsection
