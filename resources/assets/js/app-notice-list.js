/**
 * Page User List
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const offcanvas = document.getElementById('offcanvasAddUser');

  let isFlatpickrInitialized = false;

  offcanvas.addEventListener('shown.bs.offcanvas', function () {
    if (!isFlatpickrInitialized) {
      flatpickr("#start_date", {
        enableTime: true,
        enableSeconds: true,
        time_24hr: true,
        allowInput: false,
        disableMobile: true,
        static: true,
        dateFormat: "Y-m-d H:i:S",
      });

      flatpickr("#end_date", {
        enableTime: true,
        enableSeconds: true,
        time_24hr: true,
        allowInput: false,
        disableMobile: true,
        static: true,
        dateFormat: "Y-m-d H:i:S",
      });

      isFlatpickrInitialized = true;
    }
  });
});


// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {


  // Variable declaration for table
  const dt_user_table = document.querySelector('.datatables-users');


  const buttons = [];

  if (userPermissions.includes('notice.create')) {
    buttons.push({
      text: '<span class="d-inline-block">Add New</span>',
      className: 'add-new btn btn-primary',
      attr: {
        'data-bs-toggle': 'offcanvas',
        'data-bs-target': '#offcanvasAddUser'
      }
    });
  }
  // Users datatable
  if (dt_user_table) {
    const dt_user = new DataTable(dt_user_table, {
      serverSide: true,
      ajax: {
        url: baseUrl + 'notice-list',
        type: 'GET',
        beforeSend: function () {
          $('.datatables-users tbody').html(`
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
          $('.datatables-users tbody').find('.spinner-border').closest('tr').remove();
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
        {data: 'title'},
        {data: 'status'},
        {data: 'details'},
        {data: 'start_date'},

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
          targets: 1,
          orderable: false,
          render: function (data, type, row, meta) {
            return `<div class="wrap-text">${row.title}</div>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            var $details = full['details'] || '';

            // Truncate to 200 characters
            if ($details.length > 300) {
              $details = $details.substring(0, 300) + '...';
            }

            // Wrap in a span with fixed width and text-truncate class
            return (
              '<div style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' +
              $details +
              '</div>'
            );
          }
        },
        {
          targets: 3, // adjust index as needed
          render: function (data, type, full, meta) {
            var startDate = full['start_date'] || '--';
            var endDate = full['end_date'] || '--';

            return (
              '<div class="d-flex flex-column">' +
              '<span class="text-muted small">' +
           startDate +
              '</span>' +
              '<span class="text-muted small">' +
             endDate +
              '</span>' +
              '</div>'
            );
          }
        },

        {
          targets: 4,
          render: function (data, type, full, meta) {
            var $status = full['status'];
            var badgeClass = '';
            var badgeText = '';

            if ($status == 1 || $status === 'active') {
              badgeClass = 'bg-label-success';
              badgeText = 'Active';
            } else {
              badgeClass = 'bg-label-danger';
              badgeText = 'Inactive';
            }

            return (
              "<span class='badge " + badgeClass + " text-capitalize px-3 py-2'>" +
              badgeText +
              "</span>"
            );
          }
        },


        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,


          render: (data, type, full, meta) => {
            var id = full.id;
            let actionButtons = '<div class="d-flex align-items-center gap-1">';
            actionButtons +=
              '<a href="javascript:;" class="btn btn-icon  btn-text-primary  rounded-pill view-notice" data-id="' + id + '" data-element="view-notice-' + id + '" id="view-notice-' + id + '">' +
              '<i class="icon-base ri ri-eye-line icon-md"></i>' +
              '</a>';
            // Edit button
            if (userPermissions.includes('notice.edit')) {
              actionButtons += `
  <a href="javascript:;"
     class="btn btn-icon btn-text-primary rounded-pill edit-record me-1 edit-user"
     data-id="${full.id}"
     data-element="edit-notice-${full.id}"
     id="edit-notice-${full.id}">
    <i class="icon-base ri ri-edit-box-line icon-md"></i>
  </a>`;
            }

            // Delete button
            if (userPermissions.includes('notice.delete')) {
              actionButtons += `
        <a href="javascript:;"
           class="btn btn-icon btn-text-danger rounded-pill delete-record"
           data-id="${full.id}">
          <i class="icon-base ri ri-delete-bin-7-line icon-md"></i>
        </a>`;
            }

            actionButtons += '</div>';
            return actionButtons;
          }
        }
      ],
      select: {
        style: 'multi',
        selector: 'td:nth-child(2)'
      },
      order: [[0, 'desc']],
      layout: {
        topStart: {
          rowClass: 'row mx-2 justify-content-between',
          features: [
            {
              pageLength: {
                menu: [10, 25, 50, 100],
                text: 'Show_MENU_entries'
              }
            }
          ]
        },
        topEnd: {
          features: [
            {
              search: {
                placeholder: 'Type search here',
                text: '_INPUT_'
              }
            },
            {
              buttons: buttons
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      language: {
        paginate: {
          next: '<i class="icon-base ri ri-arrow-right-s-line scaleX-n1-rtl icon-22px"></i>',
          previous: '<i class="icon-base ri ri-arrow-left-s-line scaleX-n1-rtl icon-22px"></i>',
          first: '<i class="icon-base ri ri-skip-back-mini-line scaleX-n1-rtl icon-22px"></i>',
          last: '<i class="icon-base ri ri-skip-forward-mini-line scaleX-n1-rtl icon-22px"></i>'
        }
      },
      // For responsive popup
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Details of ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.title !== '' // Do not show row in modal popup if title is blank (for check box)
                  ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                    </tr>`
                  : '';
              })
              .join('');

            if (data) {
              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              div.appendChild(table);
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              return div;
            }
            return false;
          }
        }
      },
      initComplete: function () {
        const api = this.api();


      },
      drawCallback: function (settings) {
        var data = this.api().rows({search: 'applied'}).data();


        var api = this.api();
        var json = api.ajax.json();


      }
    });


    // Initial event binding
    // bindDeleteEvent();

    // Re-bind events when modal is shown or hidden
    document.addEventListener('show.bs.modal', function (event) {
      if (event.target.classList.contains('dtr-bs-modal')) {
        // bindDeleteEvent();
      }
    });

    document.addEventListener('hide.bs.modal', function (event) {
      if (event.target.classList.contains('dtr-bs-modal')) {
        // bindDeleteEvent();
      }
    });
  }

  $('.datatables-users tbody').on('click', '.delete-record', function () {
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
          url: baseUrl + 'notice-list/' + userId,
          type: "DELETE",
          // data: { _token: routes.csrfToken },
          headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          success: function (response) {

            if (response.success) {
              showToast(response.message, 'Deleted', 'bg-success');
              $('.datatables-users').DataTable().ajax.reload();
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


  const addNewUserForm = document.getElementById('addNewUserForm');

  // Add New User Form Validation
  const fv = FormValidation.formValidation(addNewUserForm, {
    fields: {
      title: {
        validators: {
          notEmpty: {
            message: 'Please enter name '
          }
        }
      },


      status: {
        validators: {
          notEmpty: {
            message: 'Please select a role'
          }
        }
      }
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
      url: baseUrl + 'notice-list',
      type: 'POST',
      data: $('#addNewUserForm').serialize(),
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function (response) {


        if (response.success) {

          showToast(response.message, 'Created', 'bg-success');

          let offcanvasElement = document.getElementById('offcanvasAddUser');
          let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();
          addNewUserForm.reset(); // Resets input field values
          fv.resetForm(true);

          // Refresh DataTable (if needed)
          $('.datatables-users').DataTable().ajax.reload();


        } else {
          showToast(response.message, 'Error!', 'bg-warning');
        }


      },
      error: function (xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;

          $('#addNewUserForm .error-message').text('');
          $('#addNewUserForm .form-control').removeClass('is-invalid');

          // Display validation errors under each corresponding input field
          $.each(errors, function (field, messages) {
            let input = $('#addNewUserForm').find(`[name="${field}"]`);
            let errorContainer = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            let errorMessage = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            // Show error message and add 'is-invalid' class
            input.addClass('is-invalid');
            errorContainer.html(errorMessage);
          });
        } else if (xhr.status === 401) {
          window.location.reload(); // Reload page when 401 occurs
        } else {
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');

        }
      }
    });


  });


  const editUserForm = document.getElementById('editUserForm');
  const fvEdit = FormValidation.formValidation(editUserForm, {
    fields: {
      title: {
        validators: {
          notEmpty: {
            message: 'Please enter name '
          }
        }
      },


      status: {
        validators: {
          notEmpty: {
            message: 'Please select a role'
          }
        }
      }
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

    let userId = document.getElementById('noticeId').value;

    $.ajax({
      url: baseUrl + 'notice-list/' + userId,
      type: 'PUT',
      data: $('#editUserForm').serialize(),
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function (response) {

        if (response.success) {

          showToast(response.message, 'Updated', 'bg-success');
          let offcanvasElement = document.getElementById('offcanvasEditUser');
          let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          $('.datatables-users').DataTable().ajax.reload();


        } else {
          showToast(response.message, 'Error!', 'bg-warning');
        }


        // Refresh DataTable (if needed)

      },
      error: function (xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;

          $('#editUserForm .error-message').text('');
          $('#editUserForm .form-control').removeClass('is-invalid');

          // Display validation errors under each corresponding input field
          $.each(errors, function (field, messages) {
            let input = $('#editUserForm').find(`[name="${field}"]`);
            let errorContainer = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            let errorMessage = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            // Show error message and add 'is-invalid' class
            input.addClass('is-invalid');
            errorContainer.html(errorMessage);
          });
        } else if (xhr.status === 401) {
          window.location.reload(); // Reload page when 401 occurs
        } else {
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');

        }
      }
    });


  });


});


// Global Flatpickr variables
var editStartPicker = null;
var editEndPicker = null;

// Initialize Flatpickr once (use element, not selector string)
function initEditPickers() {
  if (!editStartPicker) {
    editStartPicker = flatpickr(document.getElementById("edit-start_date"), {
      enableTime: true,
      enableSeconds: true,
      time_24hr: true,
      allowInput: false,
      disableMobile: true,
      static: true,
      dateFormat: "Y-m-d H:i:S"
    });
  }

  if (!editEndPicker) {
    editEndPicker = flatpickr(document.getElementById("edit-end_date"), {
      enableTime: true,
      enableSeconds: true,
      time_24hr: true,
      allowInput: false,
      disableMobile: true,
      static: true,
      dateFormat: "Y-m-d H:i:S"
    });
  }
}

// Call this once at the top
initEditPickers();


$(document).on("click", ".edit-user", function () {
  let userId = $(this).data("id");
  let offcanvasElement = $("#offcanvasEditUser");

  if (offcanvasElement.length) {
    // Show modal first, but only loader
    let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement[0]);
    if (!offcanvasInstance) {
      offcanvasInstance = new bootstrap.Offcanvas(offcanvasElement[0]);
    }
    offcanvasInstance.show();

    // Show loader, hide form
    offcanvasElement.find(".loader-wrapper").show();
    offcanvasElement.find(".form-wrapper").hide();
  }


  // Fetch User Data
  $.ajax({
    url: baseUrl + "notice-list/" + userId,
    type: "GET",
    success: function (response) {
      if (response.success && offcanvasElement.length) {
        // Fill form
        $("#offcanvasEditUser .edit-notice-id").val(response.notice.id);
        $("#offcanvasEditUser .edit-title").val(response.notice.title);
        $("#offcanvasEditUser .edit-details").val(response.notice.details);

        // $("#offcanvasEditUser .edit-start_date").val(response.notice.start_date);
        // $("#offcanvasEditUser .edit-end_date").val(response.notice.end_date);
        $("#offcanvasEditUser .edit-status").val(response.notice.status)


        if (editStartPicker) editStartPicker.setDate(response.notice.start_date, true);
        if (editEndPicker) editEndPicker.setDate(response.notice.end_date, true);
        // Show form, hide loader
        offcanvasElement.find(".loader-wrapper").hide();
        offcanvasElement.find(".form-wrapper").fadeIn(200);
      } else {
        showToast(response.message ?? 'Error', 'Error!', 'bg-warning');
      }
    },
    error: function (xhr) {
      offcanvasElement.find(".loader-wrapper").hide();
      showToast('Failed to load user data.', 'Error!', 'bg-warning');
    }
  });
});

// Optional: Cleanup backdrop when offcanvas is hidden
document.getElementById('offcanvasEditUser').addEventListener('hidden.bs.offcanvas', function () {
  $('.offcanvas-backdrop').remove(); // Force remove backdrop
});


$(document).on("click", ".view-notice", function () {
  let userId = $(this).data("id");

  $.ajax({
    url: baseUrl + "notice-list/" + userId,
    type: "GET",
    success: function (response) {
      if (response.success && response.notice) {
        const notice = response.notice;


        $("#view-notice-title").text(notice.title ?? '--');
        $("#view-notice-details").text(notice.details ?? '--');
        $("#view-notice-start_date").text(notice.start_date ?? '--');
        $("#view-notice-end_date").text(notice.end_date ?? '--');
        $("#view-notice-status").text(notice.status == 'active' ? "Active" : "Inactive");

        let offcanvas = new bootstrap.Offcanvas(document.getElementById("offcanvasViewNotice"));
        offcanvas.show();
      } else {
        showToast(response.message ?? 'Unable to fetch notice details.', 'Error!', 'bg-warning');
      }
    },
    error: function (xhr) {
      if (xhr.status === 401) {
        window.location.reload();
      } else {
        showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
      }
    }
  });
});
