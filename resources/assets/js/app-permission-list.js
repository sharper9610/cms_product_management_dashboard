/**
 * Page User List
 */

'use strict';


// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_user_table = $('.datatables-permissions'),
    select2 = $('.select2'),
    userView = baseUrl + 'app/user/view/account',
    statusObj = {
      1: {title: 'Pending', class: 'bg-label-warning'},
      2: {title: 'Active', class: 'bg-label-success'},
      3: {title: 'Inactive', class: 'bg-label-secondary'}
    };

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({

      serverSide: true,
      ajax: {
        url: baseUrl + 'permission-list',
        type: 'GET',
        beforeSend: function () {

          $('.datatables-permissions tbody').html(`
            <tr>
                <td colspan="100%" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);


        },
        complete: function () {
          $('.datatables-permissions tbody').find('.spinner-border').closest('tr').remove();

        },
        error: function (xhr, error, thrown) {
          if (xhr.status === 401) {
            window.location.reload(); // Reload page when 401 occurs
          }
          var errorMessage = 'An unexpected error occurred. Please try again.';
          showToast(errorMessage, 'Error!', 'bg-warning');
        }
      },

      columns: [
        // columns according to JSON
        {data: 'id'},
        {data: 'id'},
        {data: 'name'},
        {data: 'group_name'},
        {data: 'action'}
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // For Checkboxes
          targets: 1,
          orderable: false,
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          },
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input" >';
          },
          searchable: false
        },
        {
          targets: 2,
          orderable: true,

          render: function (data, type, full, meta) {
            var $name = full['name'];
            return "<span class='text-truncate d-flex align-items-center text-heading'>" + $name + '</span>';
          }
        },     {
          targets: 3,
          orderable: true,

          render: function (data, type, full, meta) {
            var $group_name = full['group_name'];
            return "<span class='text-truncate d-flex align-items-center text-heading'>" + $group_name + '</span>';
          }
        },


        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var $id = full['id'];
            var actionButtons = '<div class="d-flex align-items-center">';

            // Check for edit permission
            if (userPermissions.includes('user.edit')) {
              actionButtons += '<a href="javascript:;" class="btn btn-icon edit-permission" data-id="' + $id + '" data-element="edit-permission-' + $id + '" id="edit-permission-' + $id + '">' +
                ' <i class="icon-base ri ri-edit-box-line icon-md"></i></a>';
            }

            // Check for delete permission
            if (userPermissions.includes('user.delete')) {
              actionButtons += '<a href="javascript:;" class="btn btn-icon delete-record" data-id="' + $id + '">' +
                '<i class="icon-base ri ri-delete-bin-7-line icon-md"></i>';
            }

            actionButtons += '</div>';
            return actionButtons;
          }
        }
      ],
      order: [],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search User',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: getButtons(),
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                col.rowIndex +
                '" data-dt-column="' +
                col.columnIndex +
                '">' +
                '<td>' +
                col.title +
                ':' +
                '</td> ' +
                '<td>' +
                col.data +
                '</td>' +
                '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
      }
    });
    // To remove default btn-secondary in export buttons
    $('.dt-buttons > .btn-group > button').removeClass('btn-secondary');
  }


  function getButtons() {
    var buttons = [
      {
        extend: 'collection',
        className: 'btn btn-label-secondary dropdown-toggle me-4',
        text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
        buttons: [
          {
            extend: 'csv',
            text: '<i class="bx bx-file me-2" ></i>Csv',
            className: 'dropdown-item',
            exportOptions: {
              columns: [1, 2, 3],
              format: {
                body: function (inner) {
                  if (inner.length <= 0) return inner;
                  var el = $.parseHTML(inner);
                  var result = '';
                  $.each(el, function (index, item) {
                    if (item.classList && item.classList.contains('user-name')) {
                      result += item.lastChild.firstChild.textContent;
                    } else {
                      result += item.innerText !== undefined ? item.innerText : item.textContent;
                    }
                  });
                  return result;
                }
              }
            }
          }
        ]
      }
    ];

    // Add "Add New User" button only if user has "user.create" permission
    if (userPermissions.includes('user.create')) {
      buttons.push({
        text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Add New</span>',
        className: 'add-new btn btn-primary',
        attr: {
          'data-bs-toggle': 'offcanvas',
          'data-bs-target': '#offcanvasAddPermission'
        }
      });
    }

    return buttons;
  }


  $('.datatables-permissions tbody').on('click', '.delete-record', function () {
    var userId = $(this).data('id');


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
          url: baseUrl + 'permission-list/' + userId,
          type: "DELETE",
          // data: { _token: routes.csrfToken },
          headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          success: function (response) {

            if (response.success) {
              showToast(response.message, 'Deleted', 'bg-success');
              $('.datatables-permissions').DataTable().ajax.reload();
            } else {
              showToast(response.message, 'Error!', 'bg-warning');
            }


          },
          error: function (xhr) {

            if (xhr.status === 401) {
              window.location.reload(); // Reload page when 401 occurs
            }
            showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');

          }
        });

      }
    });


  });


  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});


// Validation & Phone mask
(function () {

  const addNewPermissionForm = document.getElementById('addNewPermissionForm');

  // Add New User Form Validation
  const fv = FormValidation.formValidation(addNewPermissionForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter name '
          }
        }
      }, group_name: {
        validators: {
          notEmpty: {
            message: 'Please enter group name '
          }
        }
      },


    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        // Use this for enabling/changing valid/invalid class
        eleValidClass: '',
        rowSelector: function (field, ele) {
          // field is the field name & ele is the field element
          return '.mb-6';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      // Submit the form when all fields are valid
      // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function () {

    $.ajax({
      url: baseUrl + 'permission-list',
      type: 'POST',
      data: $('#addNewPermissionForm').serialize(),
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function (response) {


        if (response.success) {

          showToast(response.message, 'Created', 'bg-success');

          let offcanvasElement = document.getElementById('offcanvasAddPermission');
          let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          // Refresh DataTable (if needed)
          $('.datatables-permissions').DataTable().ajax.reload();


        } else {
          showToast(response.message, 'Error!', 'bg-warning');
        }


      },
      error: function (xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;

          $('#addNewPermissionForm .error-message').text('');
          $('#addNewPermissionForm .form-control').removeClass('is-invalid');

          // Display validation errors under each corresponding input field
          $.each(errors, function (field, messages) {
            let input = $('#addNewPermissionForm').find(`[name="${field}"]`);
            let errorContainer = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            let errorMessage = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            // Show error message and add 'is-invalid' class
            input.addClass('is-invalid');
            errorContainer.html(errorMessage);
          });
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


})();

$(document).on("click", ".edit-permission", function () {
  let userId = $(this).data("id");
  let element = $(this).data("element"); // Ensure this contains a valid ID


  let targetElement = $("#" + element);
  let offcanvasElement = $("#offcanvasEditPermission");

  // Set attributes dynamically
  targetElement.attr("data-bs-toggle", "offcanvas");
  targetElement.attr("data-bs-target", "#offcanvasEditPermission");

  // Fetch User Data
  $.ajax({
    url: baseUrl + "permission-list/" + userId, // Adjust this to your actual route
    type: "GET",
    success: function (response) {
      if (response.success) {
        if (offcanvasElement.length) {
          $("#offcanvasEditPermission .edit-permission-id").val(response.permission.id);
          $("#offcanvasEditPermission .edit-name").val(response.permission.name);
          $("#offcanvasEditPermission .edit-group_name").val(response.permission.group_name);



          // Manually show the Offcanvas
          let offcanvas = new bootstrap.Offcanvas(offcanvasElement[0]);
          offcanvas.show();
        } else {
          showToast(response.message ?? 'Error', 'Error!', 'bg-warning');
        }
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


(function () {

  const addNewPermissionForm = document.getElementById('editPermissionForm');
  const fv = FormValidation.formValidation(addNewPermissionForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter name '
          }
        }
      },group_name: {
        validators: {
          notEmpty: {
            message: 'Please enter name '
          }
        }
      },
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        // Use this for enabling/changing valid/invalid class
        eleValidClass: '',
        rowSelector: function (field, ele) {
          // field is the field name & ele is the field element
          return '.mb-6';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      // Submit the form when all fields are valid
      // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function () {

    let permissionId = document.getElementById('permissionId').value;

    $.ajax({
      url: baseUrl + 'permission-list/' + permissionId,
      type: 'PUT',
      data: $('#editPermissionForm').serialize(),
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function (response) {

        if (response.success) {

          showToast(response.message, 'Updated', 'bg-success');
          let offcanvasElement = document.getElementById('offcanvasEditPermission');
          let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          $('.datatables-permissions').DataTable().ajax.reload();


        } else {
          showToast(response.message, 'Error!', 'bg-warning');
        }


        // Refresh DataTable (if needed)

      },
      error: function (xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;

          $('#editPermissionForm .error-message').text('');
          $('#editPermissionForm .form-control').removeClass('is-invalid');

          // Display validation errors under each corresponding input field
          $.each(errors, function (field, messages) {
            let input = $('#editPermissionForm').find(`[name="${field}"]`);
            let errorContainer = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            let errorMessage = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            // Show error message and add 'is-invalid' class
            input.addClass('is-invalid');
            errorContainer.html(errorMessage);
          });
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


})();

