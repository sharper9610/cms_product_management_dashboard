/**
 * Page User List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_user_table = document.querySelector('.datatables-users');



  const buttons = [];

  if (userPermissions.includes('settings.drm.type')) {
    buttons.push({
      text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-inline-block">Add New</span>',
      className: 'add-new btn btn-primary',
      attr: {
        'data-bs-toggle': 'offcanvas',
        'data-bs-target': '#offcanvasAddDrmType'
      }
    });
  }
  // Users datatable
  if (dt_user_table) {
    const dt_user = new DataTable(dt_user_table, {
      serverSide: true,
      ajax: {
        url: baseUrl+'settings/drm-type-management',
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
        `);        },
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
        { data: 'id' },
        { data: 'name' },




        { data: 'action' }
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
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            let actionButtons = '<div class="d-flex align-items-center gap-1">';

            // Edit button
            if (userPermissions.includes('settings.drm.type')) {
              actionButtons += `
  <a href="javascript:;"
     class="btn btn-icon btn-text-primary rounded-pill edit-record me-1 edit-user"
     data-id="${full.id}"
     data-element="edit-user-${full.id}"
     id="edit-user-${full.id}">
    <i class="icon-base ri ri-edit-box-line icon-md"></i>
  </a>`;}

            // Delete button
            if (userPermissions.includes('settings.drm.type')) {
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
      order: [[0, 'desc']],
      layout: {
        topStart: {
          rowClass: 'row mx-2 justify-content-between',
          features: [
            {
              pageLength: {
                menu: [ 10, 25, 50, 100],
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

    //? The 'delete-record' class is necessary for the functionality of the following code.
    function deleteRecord(event) {
      let row = document.querySelector('.dtr-expanded');
      if (event) {
        row = event.target.parentElement.closest('tr');
      }
      if (row) {
        dt_user.row(row).remove().draw();
      }
    }



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
          url: baseUrl + 'settings/drm-type-management/' + userId,
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

  // Filter form control to default size
  // ? setTimeout used for user-list table initialization
  setTimeout(() => {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
      },
      { selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5' },
      {
        selector: '.dt-layout-start .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
      },
      {
        selector: '.dt-layout-end .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
      },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12' },
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
    ];

    // Delete record
    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      document.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }, 100);

  // Validation & Phone mask
  const addNewDrmTypeForm = document.getElementById('addNewDrmTypeForm');


  // Add New User Form Validation
  const fv = FormValidation.formValidation(addNewDrmTypeForm, {
    fields: {
      name: {
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
          return '.form-control-validation';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      // Submit the form when all fields are valid
      // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  });


  fv.on('core.form.valid', function () {
    const formData = new FormData(addNewDrmTypeForm);
    const $form = $('#addNewDrmTypeForm');
    const submitButton = $form.find('.data-submit');

    // Disable submit button and show loading state
    submitButton.prop('disabled', true).text('Submitting...');

    $.ajax({
      url: baseUrl + 'settings/drm-type-management',
      type: 'POST',
      data: $form.serialize(),
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        // Re-enable and reset submit button
        submitButton.prop('disabled', false).text('Submit');

        if (response.success) {
          showToast(response.message, 'Created', 'bg-success');

          // Hide offcanvas
          const offcanvasElement = document.getElementById('offcanvasAddDrmType');
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          // Reset form
          $form[0].reset();
          fv.resetForm(true); // Also reset validation states

          // Optional: Refresh DataTable
          $('.datatables-users').DataTable().ajax.reload();
        } else {
          showToast(response.message || 'Something went wrong', 'Error!', 'bg-warning');
        }
      },
      error: function (xhr) {
        // Re-enable and reset submit button
        submitButton.prop('disabled', false).text('Submit');

        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;

          $form.find('.error-message').text('');
          $form.find('.form-control').removeClass('is-invalid');

          $.each(errors, function (field, messages) {
            const input = $form.find(`[name="${field}"]`);
            const container = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            const errorHtml = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            input.addClass('is-invalid');
            container.html(errorHtml);
          });
        } else if (xhr.status === 401) {
          window.location.reload(); // Unauthorized
        } else {
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
        }
      }
    });
  });







  const editDrmTypeForm = document.getElementById('editDrmTypeForm');

// Edit User Form Validation
  const fvEdit = FormValidation.formValidation(editDrmTypeForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter name'
          }
        }
      },

    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: function (field, ele) {
          return '.form-control-validation';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  });

  fvEdit.on('core.form.valid', function () {
    const $form = $('#editDrmTypeForm');
    const submitButton = $form.find('.data-submit');
    const userId = $('#userId').val();

    // Disable submit button
    submitButton.prop('disabled', true).text('Submitting...');

    $.ajax({
      url: `${baseUrl}settings/drm-type-management/${userId}`,
      type: 'PUT',
      data: $form.serialize(),
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      success: function (response) {
        submitButton.prop('disabled', false).text('Submit');

        if (response.success) {
          showToast(response.message, 'Updated', 'bg-success');

          const offcanvasElement = document.getElementById('offcanvasEditDrmType');
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          $form[0].reset();
          fvEdit.resetForm(true);

          $('.datatables-users').DataTable().ajax.reload();
        } else {
          showToast(response.message || 'Something went wrong', 'Error!', 'bg-warning', 10000);
        }
      },
      error: function (xhr) {
        submitButton.prop('disabled', false).text('Submit');

        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;

          $form.find('.error-message').text('');
          $form.find('.form-control').removeClass('is-invalid');

          $.each(errors, function (field, messages) {
            const input = $form.find(`[name="${field}"]`);
            const container = input.closest('.fv-plugins-icon-container').find('.fv-plugins-message-container');

            const errorHtml = `<div data-field="${field}" data-validator="notEmpty">${messages[0]}</div>`;

            input.addClass('is-invalid');
            container.html(errorHtml);
          });
        } else if (xhr.status === 401) {
          window.location.reload();
        } else {
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
        }
      }
    });
  });





});


$(document).on("click", ".edit-user", function () {
  let userId = $(this).data("id");
  let offcanvasElement = $("#offcanvasEditDrmType");

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
    url: baseUrl + "settings/drm-type-management/" + userId,
    type: "GET",
    success: function (response) {
      if (response.success && offcanvasElement.length) {
        // Fill form
        $("#offcanvasEditDrmType .edit-user-id").val(response.user.id);
        $("#offcanvasEditDrmType .edit-name").val(response.user.name);



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
document.getElementById('offcanvasEditDrmType').addEventListener('hidden.bs.offcanvas', function () {
  $('.offcanvas-backdrop').remove(); // Force remove backdrop
});




