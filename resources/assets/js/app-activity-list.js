/**
 * Page User List
 */

'use strict';


$(document).ready(function() {
  $('#filter-event').select2({
    placeholder: "Select an event",
    allowClear: true,
    width: '100%' // ensures it fits inside the form-floating div
  });
});

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {

  const startDateEle = document.querySelector('.start_date');
  const endDateEle = document.querySelector('.end_date');
  const rangePickr = document.querySelector('.flatpickr-range');
  const descInput = $('.dt-description');

  function debounce(func, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }





  const today = moment().format("Y-MM-DD");

// Set input values manually for default
  startDateEle.value = today;
  endDateEle.value = today;

  flatpickr(rangePickr, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    defaultDate: [today, today],
    onChange: function (selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        startDateEle.value = instance.formatDate(selectedDates[0], 'Y-m-d');
        endDateEle.value = instance.formatDate(selectedDates[1], 'Y-m-d');
        rangePickr.value = `${startDateEle.value} to ${endDateEle.value}`;

        // Update DataTable column 5 filter
        const val = startDateEle.value === endDateEle.value
          ? startDateEle.value
          : `${startDateEle.value} to ${endDateEle.value}`;
        dt_user.column(5).search(val, false, true).draw();
      }
    }
  });

// Now compute searchValue AFTER input values are set
  let searchValue = startDateEle.value === endDateEle.value
    ? startDateEle.value
    : `${startDateEle.value} to ${endDateEle.value}`;




  const buttons = [];

  const dt_user = new DataTable('.datatables-activity-log', {
    serverSide: true,
    ajax: {
      url: baseUrl+'activity-list',
      type: 'GET',
      data: function (d) {
        d.start_date = document.querySelector('.start_date').value;
        d.end_date = document.querySelector('.end_date').value;
      },
      beforeSend: function () {
        $('.datatables-activity-log tbody').html(`
            <tr>
                <td colspan="100%" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);        },
      complete: function () {
        $('.datatables-activity-log tbody').find('.spinner-border').closest('tr').remove();
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
      {data: 'log_name'},
      {data: 'description'},
      {data: 'description'},
      {data: 'causer_name'},
      {data: 'created_at'},
      {data: 'event'},
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
        targets: 2,
        orderable: false,
        render: function(data, type, row, meta) {
          return `<div class="wrap-text">${row.description}</div>`;
        }
      },
      {
        // User Role
        targets: 3,
        orderable: false,
        render: function (data, type, full, meta) {
          var $id = full['id'];
          var $properties = full['properties'];

          // Check if $id is not an empty array
          if ($properties && Array.isArray($properties) && $properties.length === 0) {
            return ''; // Return empty string to hide the icon
          }

          var actionButtons = '<div class="d-flex align-items-center">';

          actionButtons += '<a href="javascript:;" class="btn btn-icon view-activity-log" data-id="'+$id+'" >' +
            '<i class="icon-base ri ri-eye-fill icon-md"></i></a>';



          actionButtons += '</div>';
          return actionButtons;
        }
      },

      {
        // User Role
        targets: 4,
        orderable: false,
        // visible: false,
        render: function (data, type, full, meta) {
          var $name = full['causer_name'] ?? 'Not found',
            $email = full['causer_email'] ?? '';
          var $output;
          // For Avatar badge
          var stateNum = Math.floor(Math.random() * 6);
          var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
          var $state = states[stateNum],
            $initials = $name.match(/\b\w/g) || [];
          $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
          $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';

          // Creates full output for row
          var $row_output =
            '<div class="d-flex justify-content-start align-items-center user-name">' +
            '<div class="avatar-wrapper">' +
            '<div class="avatar avatar-sm me-4">' +
            $output +
            '</div>' +
            '</div>' +
            '<div class="d-flex flex-column">' +
            '<a class="text-heading text-truncate"><span class="fw-medium">' +
            $name +
            '</span></a>' +
            '<small>' +
            $email +
            '</small>' +
            '</div>' +
            '</div>';
          return $row_output;
        }
      },
      {
        targets: 6,
        orderable: false,
        render: function(data, type, row, meta) {
          return `<div class="wrap-text">${row.event}</div>`;
        }
      },


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
              menu: [ 10, 25, 50, 100],
              text: 'Show_MENU_entries'
            }
          }
        ]
      },
      topEnd: {
        features: [

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
            return 'Details of ' + data['log_name'];
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



    },
    searchCols: [
      null, // col 0
      null, // col 1
      null, // col 2
      null, // col 3
      null, // col 4
      { search: searchValue }, // col 5 default search
      null // col 6 default search
    ]

  });







  // Filter Column Function
  // ------------------------------
  function filterColumn(columnIndex, value) {
    dt_user.column(columnIndex).search(value, false, true).draw();
  }

  const debouncedFilterColumn = debounce(filterColumn, 500);

  // ------------------------------
  // Description input (column 2) debounce
  // ------------------------------
  descInput.on('keyup', function () {
    const value = $(this).val().trim();
    debouncedFilterColumn(2, value);
  });

  $('select.dt-input').on('change', function () {
    filterColumn($(this).attr('data-column'), $(this).val());
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








});








$(document).on('click', '.view-activity-log', function () {
  var userId = $(this).data('id'); // Get the user ID from the clicked icon

  $.ajax({
    url: baseUrl + 'activity-list/' + userId,
    type: 'GET',
    success: function (response) {
      let data = response?.data ?? {};
      let contentHtml = '';
      let properties = data?.properties ?? {};

      // Iterate over properties
      Object.keys(properties).forEach(key => {
        let value = properties[key];

        // If value is an array
        if (Array.isArray(value)) {
          contentHtml += `<h5 class="mt-3">${key}</h5>`;

          // Check if array contains objects
          if (value.length && typeof value[0] === 'object') {
            contentHtml += `
                            <table class="table table-bordered w-100 m-auto">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        ${Object.keys(value[0]).map(k => `<th>${k}</th>`).join('')}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${value.map((item, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            ${Object.keys(item).map(k => `<td style="white-space:pre-wrap; word-break:break-word;">${item[k]}</td>`).join('')}
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
          } else {
            // Array of primitives
            contentHtml += `
                            <table class="table table-bordered w-50 m-auto">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${value.map((item, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td style="white-space:pre-wrap; word-break:break-word;">${item}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
          }

        } else if (typeof value === 'object' && value !== null) {
          // Nested object, display as JSON
          contentHtml += `<p><strong>${key}:</strong><pre>${JSON.stringify(value, null, 2)}</pre></p>`;
        } else {
          // Primitive value
          contentHtml += `<p><strong>${key}:</strong> ${value}</p>`;
        }
      });

      $('#modalContent').html(contentHtml);
      $('#userModal').modal('show');
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
