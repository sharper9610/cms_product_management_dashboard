/**
 * App user list
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {

  loadRoles();
  function loadRolessOld() {
    $.ajax({
      url: routes.accessRolesList,
      type: "GET",
      success: function (response) {
        let roleHtml = '';

        response.forEach(role => {
          const users = role?.users ?? [];
          const totalUsers = role.users_count || users.length || 0;

          roleHtml += `
          <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <p class="mb-0">Total ${totalUsers} user${totalUsers !== 1 ? 's' : ''}</p>
                  <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                    ${users.slice(0, 3).map(user => `
                      <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="${user.name}" class="avatar pull-up">
                        <img class="rounded-circle" src="assets/img/avatars/empty_person.png" alt="Avatar" />
                      </li>
                    `).join('')}
                    ${users.length > 3 ? `
                      <li class="avatar">
                        <span class="avatar-initial rounded-circle pull-up text-body" data-bs-toggle="tooltip" data-bs-placement="bottom" title="${users.length - 3} more">+${users.length - 3}</span>
                      </li>` : ''}
                  </ul>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="role-heading">
                    <h5 class="mb-1">${role.name}</h5>
                    ${userPermissions.includes('role.edit') ? `
                      <a href="javascript:;" class="role-edit-modal" data-id="${role.id}" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <p class="mb-0">Edit Role</p>
                      </a>` : ''}
                  </div>
                  ${userPermissions.includes('role.delete') ? `
                    <a href="javascript:void(0);" class="delete-role text-danger" data-id="${role.id}">
                      <i class="icon-base ri ri-delete-bin-line icon-22px"></i>
                    </a>` : `
                    <a href="javascript:void(0);" class="text-secondary">
                      <i class="icon-base ri ri-file-copy-line icon-22px"></i>
                    </a>`}
                </div>
              </div>
            </div>
          </div>`;
        });

        $('#roleList').html(roleHtml);
      },
      error: function (xhr) {
        if (xhr.status === 401) {
          window.location.reload();
        }
        showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
      }
    });
  }


  function loadRoles() {
    $.ajax({
      url: routes.accessRolesList,  // Use the variable instead of Blade syntax
      type: "GET",
      success: function (response) {
        let roleHtml = '';
        response.forEach(role => {
          const users = role?.users ?? [];
          roleHtml += `
                        <div class="col-xl-4 col-lg-6 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="fw-normal mb-0 text-body">Total ${role.users_count ? role.users_count : 0} users</h6>



                                    </div>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div class="role-heading">
                                            <h5 class="mb-1">${role.name}</h5>


   ${userPermissions.includes('role.edit') ?
            `<a href="javascript:;" class="role-edit-modal" data-id="${role.id}"><span>Edit Role</span></a>`
            : ''}
    </div>

                                      ${userPermissions.includes('role.delete') ?
            `<a href="javascript:void(0);" class="delete-role" data-id="${role.id}"><i class="icon-base ri ri-delete-bin-line text-danger icon-22px"></i>
</a>`
            : ''}

                                    </div>
                                </div>
                            </div>
                        </div>`;
        });
        $('#roleList').html(roleHtml);
      },
      error: function (xhr, error, thrown) {
        if (xhr.status === 401) {
          window.location.reload(); // Reload page when 401 occurs
        }
        var errorMessage = 'An unexpected error occurred. Please try again.';
        showToast(errorMessage, 'Error!', 'bg-warning');
      }
    });
  }

  // Add or Update Role with Permissions
  $('#roleForm').submit(function (e) {
    e.preventDefault();

    let roleId = $('#roleId').val(); // Assuming you have a hidden input to hold the role ID
    let url = roleId ? `${routes.updateRole}/${roleId}` : routes.rolesStore; // Ensure correct URL for update
    let method = roleId ? 'PUT' : 'POST'; // Use PUT for updates
    let messageAction = roleId ? 'Updated' : 'Created'; // Use PUT for updates

    let formData = $(this).serialize();

    $.ajax({
      url: url,
      type: method,
      data: formData,
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function (response) {
        if (response.success){
          showToast(response.message, messageAction, 'bg-success');
          $('#addRoleModal').modal('hide');
          $('#roleForm')[0].reset();
          loadRoles();
        }
        else { showToast(response.message, 'Error!', 'bg-warning');}

        if (method==='PUT' && response?.isLoginUserRoleUpdate==true){
          window.location.reload();
        }

      },
      error: function (xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;
          let errorMessage = 'Validation Errors:\n';
          $.each(errors, function (key, value) {
            errorMessage += `- ${value[0]}\n`; // Display first error for each field
          });
          showToast(errorMessage, 'Error!', 'bg-warning');

        }
        else if (xhr.status === 401) {
          window.location.reload(); // Reload page when 401 occurs
        }
        else {
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
        }
      }
    });
  });

// Edit Role - Load Data into Modal
  $(document).on('click', '.add-new-role', function () {

    $('#roleId').val('');
    $('#roleName').val('');
    $('.role-title').text('Add new Role');
    $('.permission-field').prop('checked', false);

  });


  $(document).on('click', '.role-edit-modal', function () {
    let roleId = $(this).data('id');

    $.ajax({
      url: `${routes.getRole}/${roleId}`,
      type: "GET",
      success: function (role) {
        $('#roleId').val(role.id);
        $('#roleName').val(role.name);
        $('.role-title').text('Edit Role');


        // Uncheck all checkboxes first
        $('input[type="checkbox"]').prop('checked', false);

        // Check permissions assigned to this role
        role.permissions.forEach(permission => {
          $(`input[name="permission[${permission.id}]"]`).prop('checked', true);
        });

        $('#addRoleModal').modal('show');
      }
    });
  });

  // Delete Role
  $(document).on('click', '.delete-role', function () {

    let roleId = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {

        $.ajax({
          url: `${routes.deleteRole}/${roleId}`,
          type: "DELETE",
          headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          success: function(response) {
            if (response.success){
              showToast(response.message, 'Deleted', 'bg-success');

            }
            else {
              showToast(response.message, 'Error!', 'bg-warning');
            }

            loadRoles();
          },
          error: function (xhr) {
            if (xhr.status === 401) {
              window.location.reload();
            }
            showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');

          }
        });

      }
    });

  });



  // On edit role click, update text
  var roleEditList = document.querySelectorAll('.role-edit-modal'),
    roleAdd = document.querySelector('.add-new-role'),
    roleTitle = document.querySelector('.role-title');

  roleAdd.onclick = function () {
    roleTitle.innerHTML = 'Add New Role'; // reset text
  };
  if (roleEditList) {
    roleEditList.forEach(function (roleEditEl) {
      roleEditEl.onclick = function () {
        roleTitle.innerHTML = 'Edit Role'; // reset text
      };
    });
  }
});
$(document).ready(function () {
  $("#selectAll").change(function () {
    $(".permission-field").prop("checked", $(this).prop("checked"));
  });

  $(".permission-field").change(function () {
    if ($(".permission-field:checked").length === $(".permission-field").length) {
      $("#selectAll").prop("checked", true);
    } else {
      $("#selectAll").prop("checked", false);
    }
  });
});
