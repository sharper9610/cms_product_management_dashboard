/**
 * Page User List
 */

'use strict';
window.addEventListener('load', function () {
  const filterSection = document.getElementById('filter-section');
  if (filterSection) {
    filterSection.classList.add('show');
  }
});

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {


  // Variable declaration for table
  const dt_customers_table = document.querySelector('.datatables-customers');


  const buttons = [];

  //  datatable
  if (dt_customers_table) {
    const dt_user = new DataTable(dt_customers_table, {
      serverSide: true,
      ajax: {
        url: baseUrl + 'customer-list',
        type: 'GET',
        beforeSend: function () {
          $('.datatables-customers tbody').html(`
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
          $('.datatables-customers tbody').find('.spinner-border').closest('tr').remove();

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
        {data: 'email'},
        {data: 'shopify_legacy_id'},
        {data: 'locale'},
        {data: 'state'},
        {data: 'verified_email'},
        {data: 'phone'},
        {data: 'amount_spent'},
        {data: 'number_of_orders'},
        {data: 'shopify_created_at'},
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
          searchable: true,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            let id = full['id'] || '';
            let name = full['full_name'] || '';
            let email = full['email'] || '';

            return `<div>
                      <a href="/customer-list/${id}" class="fw-medium text-body">${name}</a>
                      <div><small class="text-muted">${email}</small></div>
                    </div>`;
          }
        },
        {
          targets: 2,
          orderable: false,
          render: function (data, type, full, meta) {
            var shopify_legacy_id = full['shopify_legacy_id'] ?? '';

            return `<code class="text-muted" style="font-size:11px;">${shopify_legacy_id}</code>`;
          }
        },
        {
          targets: 3,
          orderable: false,
          render: function (data, type, full, meta) {
            var locale = full['locale'] ?? '';

            return `<span class="badge bg-label-info">${locale}</span>`;
          }
        },
        {
          targets: 4,
          orderable: false,
          searchable: true,
          render: function (data, type, full, meta) {
            var state = full['state'] ?? '';

            const map = {
              ENABLED:  'bg-label-success',
              DISABLED: 'bg-label-danger',
              INVITED:  'bg-label-warning',
            };
            const cls = map[state] ?? 'bg-label-secondary';

            return `<span class="badge rounded-pill ${cls}">${data}</span>`;
          }
        },
        {
          targets: 5,
          orderable: true,
          searchable: true,
          render: function (data, type, full, meta) {
            var verified_email = full['verified_email'] ?? '';

            return verified_email
            ? `<span class="badge bg-label-success"><i class="ri ri-check-line me-1"></i>Yes</span>`
            : `<span class="badge bg-label-secondary">No</span>`;
          }
        },
        {
          targets: 6,
          orderable: false,
          render: function (data, type, full, meta) {
            var phone = full['phone'] ?? '';

            return `<span class="">${phone}</span>`;
          }
        },
        {
          targets: 7,
          searchable: true,
          orderable: true,
          visible: false,
          render: function (data, type, full, meta) {
            var amount_spent = full['amount_spent'] ?? '';

            return `<strong class="text-success">$${amount_spent}</strong>`;
          }
        },
        {
          targets: 8,
          searchable: true,
          orderable: true,
          visible: false,
          render: function (data, type, full, meta) {
            var number_of_orders = full['number_of_orders'] ?? '';

            return `<span class="badge bg-label-primary">${number_of_orders}</span>`;
          }
        },
        {
          targets: 9,
          orderable: true,
          render: function (data, type, full, meta) {
            var shopify_created_at = full['shopify_created_at'] ?? '';

            return `<span class="">${shopify_created_at}</span>`;
          }
        },


        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            let id = full['id'] || '';

            return `
              <div class="d-flex gap-1">
                <a href="/customer-list/${id}"
                  class="btn btn-sm btn-icon btn-outline-primary"
                  title="View Detail">
                  <i class="ri ri-eye-line"></i>
                </a>
              </div>`;
          }
        }


      ],

      order: [[9, 'desc']],

      layout: {
        topStart: {
          rowClass: 'row mx-2 justify-content-between',
          features: [
            {
              pageLength: {
                menu: [10, 25, 50, 100],
                text: 'Show_MENU_entries'
              },

            }
          ]
        },
        topEnd: {},
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
        var data = this.api().rows({search: 'f'}).data();


        var api = this.api();
        var json = api.ajax.json();


        if (json?.total_customers) {
          $('#all_customers_title').text(`All Customers (${json.total_customers})`);
        }
        $('#total-info-block').remove();

        if (json) {
          let total_enabled = json.total_enabled ?? 0;
          let total_disabled = json.total_disabled ?? 0;
          let total_invited = json.total_invited ?? 0;
          let total_verified = json.total_verified ?? 0;


          $('#datatables-customers_wrapper > div:first-child .dt-layout-end').append(`
            <div class="d-flex align-items-center gap-3 ms-3" id="total-info-block">
              <span id="stat-enabled">Enabled: ${total_enabled}</span>
              <span id="stat-disabled">Disabled: ${total_disabled}</span>
              <span id="stat-invited">Invited: ${total_invited}</span>
              <span id="stat-verified">Verified: ${total_verified}</span>
            </div>
          `);
        }


      }
    });


    if (window.Helpers.isNavbarFixed()) {
      var navHeight = $('#layout-navbar').outerHeight();
      var fixedHeader = new $.fn.dataTable.FixedHeader(dt_customers_table, {
        headerOffset: navHeight
      });

      // Apply background color when FixedHeader is enabled
      $('.fixedHeader-floating thead').css({
        'background-color': 'black !important', // Change to your desired color
        'box-shadow': '0px 2px 5px rgba(0, 0, 0, 0.1)', // Optional shadow for better visibility
        'z-index': '1050' // Ensure it stays above other elements
      });
    } else {
      new $.fn.dataTable.FixedHeader(dt_customers_table);
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


  // Filter form control to default size
  // ? setTimeout used for user-list table initialization
  setTimeout(() => {
    const elementsToModify = [
      {selector: '.dt-buttons .btn', classToRemove: 'btn-secondary'},
      {selector: '.dt-length .form-select', classToAdd: 'ms-0'},
      {selector: '.dt-length', classToAdd: 'mb-md-4 mb-0'},
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
      },
      {selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5'},
      {
        selector: '.dt-layout-start .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
      },
      {
        selector: '.dt-layout-end .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
      },
      {selector: '.dt-layout-table', classToRemove: 'row mt-2'},
      {selector: '.dt-layout-full', classToRemove: 'col-md col-12'},
      {selector: '.dt-layout-full .table', classToAdd: 'table-responsive'}
    ];

    // Delete record
    elementsToModify.forEach(({selector, classToRemove, classToAdd}) => {
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

  $(function () {

    const dt_adv_filter_table = $('.datatables-customers');
    const table = $('#datatables-customers').DataTable(); // Initialize once

    // Debounce helper function
    function debounce(func, delay = 500) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
      };
    }

    // Debounced search function
    const debouncedSearch = debounce(function () {
      const dataColumn = $(this).attr('data-column');
      const inputValue = $(this).val();

      table.column(dataColumn).search(inputValue);
      // ✅ Single draw (1 AJAX call only)
      table.draw();

    }, 800); // 800ms delay

    // Apply debounce to keyup
    $('input.dt-input').on('keyup', debouncedSearch);

    $('select.dt-input').on('change', function () {
      filterColumn($(this).attr('data-column'), $(this).val());
    });

    function filterColumn(i, val) {
      dt_adv_filter_table.DataTable().column(i).search(val, false, true).draw();
    }
  });
});


