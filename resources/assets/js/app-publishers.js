/**
 * Page Publisher List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {

  // Variable declaration for table
  const dt_publisher_table = document.querySelector('.datatables-publishers');



  const buttons = [];

  if (userPermissions.includes('settings.publisher.management')) {
    buttons.push({
      text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-inline-block">Add publisher</span>',
      className: 'add-new btn btn-primary',
      attr: {
        'data-bs-toggle': 'offcanvas',
        'data-bs-target': '#offcanvasAddPublisher'
      }
    });
  }
  // Publisher datatable
  if (dt_publisher_table) {
    const dt_publisher = new DataTable(dt_publisher_table, {
      serverSide: true,
      ajax: {
        url: baseUrl+'settings/publisher-list',
        type: 'GET',
        beforeSend: function () {
          $('.datatables-publishers tbody').html(`
            <tr>
                <td colspan="100%" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);        },
        complete: function () {
          $('.datatables-publishers tbody').find('.spinner-border').closest('tr').remove();
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
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var name = full['name'];


            // Only show Name and Email
            var row_output =
              '<div class="d-flex flex-column">' +
              '<span class="fw-medium text-truncate">' + name + '</span>' +
              '</div>';

            return row_output;
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
            if (userPermissions.includes('settings.publisher.management')) {
              actionButtons += `
  <a href="javascript:;"
     class="btn btn-icon btn-text-primary rounded-pill edit-record me-1 edit-publisher"
     data-id="${full.id}"
     data-element="edit-publisher-${full.id}"
     id="edit-publisher-${full.id}">
    <i class="icon-base ri ri-edit-box-line icon-md"></i>
  </a>`;}

            // Delete button
            if (userPermissions.includes('settings.publisher.management')) {
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
              return 'Details of ' + data['name'];
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

        console.log(json);


      }
    });



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

  $('.datatables-publishers tbody').on('click', '.delete-record', function () {
    var publisherId = $(this).data('id');


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
          url: baseUrl + 'settings/publisher-list/' + publisherId,
          type: "DELETE",
          // data: { _token: routes.csrfToken },
          headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          success: function (response) {

            if (response.success) {
              showToast(response.message, 'Deleted', 'bg-success');
              $('.datatables-publishers').DataTable().ajax.reload();
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
  // ? setTimeout used for publisher-list table initialization
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
  const addNewPublisherForm = document.getElementById('addNewPublisherForm');


  // Add New Publisher Form Validation
  const fv = FormValidation.formValidation(addNewPublisherForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter publisher name'
          }
        }
      }
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



  fv.on('core.form.valid', function () {
    const formData = new FormData(addNewPublisherForm);
    const $form = $('#addNewPublisherForm');
    const submitButton = $form.find('.data-submit');

    // Disable submit button and show loading state
    submitButton.prop('disabled', true).text('Submitting...');

    $.ajax({
      url: baseUrl + 'settings/publisher-list',
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
          const offcanvasElement = document.getElementById('offcanvasAddPublisher');
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          // Reset form
          $form[0].reset();
          fv.resetForm(true); // Also reset validation states

          // Optional: Refresh DataTable
          $('.datatables-publishers').DataTable().ajax.reload();
        } else {
          showToast(response.message || 'Something went wrong', 'Error!', 'bg-warning');
        }
      },
      error: function (xhr) {
        // Re-enable and reset the submit button
        const submitButton = $form.find('.data-submit');
        submitButton.prop('disabled', false).text('Submit');

        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;

          // Clear any previous validation messages
          fv.resetForm();

          $.each(errors, function (field, messages) {
            const input = $form.find(`[name="${field}"]`);
            if (input.length > 0) {
              // Find the parent container that FormValidation uses
              const fieldContainer = input.closest('.form-control-validation');

              // Add the 'is-invalid' class to the input for the red border
              input.addClass('is-invalid');

              // Add FormValidation's invalid state classes to the parent container
              fieldContainer.addClass('fv-plugins-icon-container fv-plugins-bootstrap5-row-invalid');

              // Check if an error message container already exists
              let messageContainer = fieldContainer.find('.fv-plugins-message-container');

              // If not, create and append it to the parent container
              if (messageContainer.length === 0) {
                messageContainer = $('<div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>');
                fieldContainer.append(messageContainer);
              }

              // Display the error message from the server
              messageContainer.html(`<div data-field="${field}" data-validator="server">${messages[0]}</div>`);
            }
          });
        } else if (xhr.status === 401) {
          // Handle unauthorized access, e.g., redirect to login
          window.location.reload();
        } else {
          // Handle other server errors
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
        }
      }


    });
  });







  const editPublisherForm = document.getElementById('editPublisherForm');

// Edit Publisher Form Validation
  const fvEdit = FormValidation.formValidation(editPublisherForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter publisher name'
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
    const $form = $('#editPublisherForm');
    const submitButton = $form.find('.data-submit');
    const publisherId = $('#publisherId').val();

    // Disable submit button
    submitButton.prop('disabled', true).text('Submitting...');

    $.ajax({
      url: `${baseUrl}settings/publisher-list/${publisherId}`,
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

          const offcanvasElement = document.getElementById('offcanvasEditPublisher');
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance.hide();

          $form[0].reset();
          fvEdit.resetForm(true);

          $('.datatables-publishers').DataTable().ajax.reload();
        } else {
          showToast(response.message || 'Something went wrong', 'Error!', 'bg-warning', 10000);
        }
      },
      error: function (xhr) {
        // Re-enable and reset the submit button
        const submitButton = $form.find('.data-submit');
        submitButton.prop('disabled', false).text('Submit');

        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;

          // Clear any previous validation messages
          fv.resetForm();

          $.each(errors, function (field, messages) {
            const input = $form.find(`[name="${field}"]`);
            if (input.length > 0) {
              // Find the parent container that FormValidation uses
              const fieldContainer = input.closest('.form-control-validation');

              // Add the 'is-invalid' class to the input for the red border
              input.addClass('is-invalid');

              // Add FormValidation's invalid state classes to the parent container
              fieldContainer.addClass('fv-plugins-icon-container fv-plugins-bootstrap5-row-invalid');

              // Check if an error message container already exists
              let messageContainer = fieldContainer.find('.fv-plugins-message-container');

              // If not, create and append it to the parent container
              if (messageContainer.length === 0) {
                messageContainer = $('<div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>');
                fieldContainer.append(messageContainer);
              }

              // Display the error message from the server
              messageContainer.html(`<div data-field="${field}" data-validator="server">${messages[0]}</div>`);
            }
          });
        } else if (xhr.status === 401) {
          // Handle unauthorized access, e.g., redirect to login
          window.location.reload();
        } else {
          // Handle other server errors
          showToast('An unexpected error occurred. Please try again.', 'Error!', 'bg-warning');
        }
      }
    });
  });





});


$(document).on("click", ".edit-publisher", function () {
  let publisherId = $(this).data("id");
  let offcanvasElement = $("#offcanvasEditPublisher");

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

  // Fetch Publisher Data
  $.ajax({
    url: baseUrl + "settings/publisher-list/" + publisherId,
    type: "GET",
    success: function (response) {
      if (response.success && offcanvasElement.length) {
        console.log(response)
        // Fill form
        $("#offcanvasEditPublisher .edit-publisher-id").val(response.publisher.id);
        $("#offcanvasEditPublisher .edit-name").val(response.publisher.name);

        // Show form, hide loader
        offcanvasElement.find(".loader-wrapper").hide();
        offcanvasElement.find(".form-wrapper").fadeIn(200);
      } else {
        showToast(response.message ?? 'Error', 'Error!', 'bg-warning');
      }
    },
    error: function (xhr) {
      offcanvasElement.find(".loader-wrapper").hide();
      showToast('Failed to load publisher data.', 'Error!', 'bg-warning');
    }
  });
});

// Optional: Cleanup backdrop when offcanvas is hidden
document.getElementById('offcanvasEditPublisher').addEventListener('hidden.bs.offcanvas', function () {
  $('.offcanvas-backdrop').remove(); // Force remove backdrop
});




