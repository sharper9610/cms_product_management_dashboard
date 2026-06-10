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
$(document).ready(function() {
  $('#filter-missing').select2({
    placeholder: "Select a field",
    allowClear: true,
    width: '100%' // ensures it fits inside the form-floating div
  });

  $('#filter-publisher').select2({
    placeholder: "Select a publisher",
    allowClear: true,
    width: '100%' // ensures it fits inside the form-floating div
  });
});

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {


  // Variable declaration for table
  const dt_products_table = document.querySelector('.datatables-products');


  const buttons = [];

  //  datatable
  if (dt_products_table) {
    const dt_user = new DataTable(dt_products_table, {
      serverSide: true,
      ajax: {
        url: baseUrl + 'product-list',
        type: 'GET',
        beforeSend: function () {
          $('.datatables-products tbody').html(`
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
          $('.datatables-products tbody').find('.spinner-border').closest('tr').remove();

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
        {data: 'name'},
        {data: 'publisher_name'},
        {data: 'developers'},
        {data: 'genres'},
        {data: 'product_type'},
        {data: 'platform'},
        {data: 'source'},
        {data: 'status'},
        {data: 'release_date'},
        {data: 'release_date', visible: false},
        {data: 'release_date', visible: false},
        {data: 'country_code'},
        {data: 'action'},
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
    //
    //     {
    //       targets: 1,
    //       orderable: true,
    //       responsivePriority: 4,
    //       render: function (data, type, full, meta) {
    //         let name = full['name'] || '';
    //         let sku = full['sku'] || '';
    //         let description = full['description'] || ''; // optional if you have a description
    //
    //         // truncate name if longer than 50 chars
    //         if (name.length > 50) {
    //           name = name.substring(0, 50) + '...';
    //         }
    //
    //         return `
    //   <div>
    //     <div class="text-sm font-medium text-gray-900">${name}</div>
    //     <div class="text-sm text-gray-500">SKU: ${sku}</div>
    //   </div>
    // `;
    //       }
    //     },
        {
          targets: 1,
          orderable: true,
          responsivePriority: 4,
          width: "450px",   // ✅ set fixed width

          render: function (data, type, full, meta) {
            let name = full['name'] || '';
            let sku = full['sku'] || '';
            let source = full['source'] || '';
            let status = full['status'] || '';
            let platform = full['platform'] || '';
            let product_type = full['product_type'] || '';



            // Source badge
            let sourceClass = 'badge bg-label-secondary';
            if (source.toLowerCase() === 'ztorm') sourceClass = 'badge bg-label-primary';
            if (source.toLowerCase() === 'incomm') sourceClass = 'badge bg-label-info';
            if (source.toLowerCase() === 'point nexus') sourceClass = 'badge bg-label-warning';
            if (source.toLowerCase() === 'genba') sourceClass = 'badge bg-label-success';

            // Status badge
            let statusClass = 'badge bg-label-secondary';
            if (status.toLowerCase() === 'active') statusClass = 'badge bg-label-success';
            if (status.toLowerCase() === 'inactive') statusClass = 'badge bg-label-danger';

            return `
      <div class="d-flex flex-column">

        <div class="fw-semibold text-dark">${name}</div>

        <div class="text-muted small">SKU: ${sku}</div>

        <div class="mt-1 d-flex gap-1 flex-wrap">
          <span class="${sourceClass}">${source}</span>
          <span class="${statusClass}">${status}</span>
        </div>

 <div class="text-muted small mt-1">
  <strong>Platform:</strong> ${platform}
  <span class="ms-2"><strong>Category:</strong> ${product_type}</span>
</div>


      </div>
    `;
          }
        },
        {
          targets: 2,
          width: "150px",
          orderable: false,
          render: function (data, type, full, meta) {
            var $publisher_name = full['publisher_name'] ?? '';

            return `
      <span
        class="d-inline-block position-relative me-4 product-name"
        style="max-width:180px; word-wrap: break-word; white-space: normal;"
      >
        ${$publisher_name}
      </span>
    `;
          }
        },
        {
          targets: 3,
          width: "150px",
          orderable: false,
          render: function (data, type, full, meta) {
            var $developers= full['developers'] ?? '';

            return `
      <span
        class="d-inline-block position-relative me-4 product-name"
        style="max-width:200px; word-wrap: break-word; white-space: normal;"
      >
        ${$developers}
      </span>
    `;
          }
        },{
          targets: 4,
          width: "150px",
          orderable: false,
          render: function (data, type, full, meta) {
            var $genres= full['genres'] ?? '';

            return `
      <span
        class="d-inline-block position-relative me-4 product-name"
        style="max-width:180px; word-wrap: break-word; white-space: normal;"
      >
        ${$genres}
      </span>
    `;
          }
        },


        {
          targets: 7, // source column index
          title: 'Source',
          searchable: true,
          orderable: true,
          visible: false,
          render: function (data, type, full, meta) {
            const source = full.source || '';
            let badgeClass = 'badge rounded-pill bg-label-secondary'; // default gray

            if (source.toLowerCase() === 'ztorm') {
              badgeClass = 'badge rounded-pill bg-label-primary'; // blue
            } else if (source.toLowerCase() === 'incomm') {
              badgeClass = 'badge rounded-pill bg-label-info'; // yellow/orange
            } else if (source.toLowerCase() === 'point nexus') {
              badgeClass = 'badge rounded-pill bg-label-warning';
            } else if (source.toLowerCase() === 'genba') {
              badgeClass = 'badge rounded-pill bg-label-success';
            }

            return `<span class="${badgeClass}">${source}</span>`;
          }
        },


        {
          targets: 8, // status column index
          title: 'Status',
          searchable: true,
          orderable: true,
          visible: false,
          render: function (data, type, full, meta) {
            const status = full.status || '';
            let badgeClass = 'badge rounded-pill bg-label-secondary'; // default gray

            if (status.toLowerCase() === 'active') {
              badgeClass = 'badge rounded-pill bg-label-success';
            } else if (status.toLowerCase() === 'inactive') {
              badgeClass = 'badge rounded-pill bg-label-danger';
            }

            return `<span class="${badgeClass}">${status}</span>`;
          }
        },
        {
          targets: [5,6,10,11],
          orderable: false,
          visible: false,
          render: function (data, type, full, meta) {
            return "";
          }
        },
        {
          targets: 12,
          width: "150px",
          orderable: false,
          render: function (data, type, full, meta) {
            const countries = full['allowed_countries'] || [];
            const count = countries.length;

            if (count === 0) {
              return '<span class="badge bg-label-danger">No countries</span>';
            }

            const countryList = countries.join(', ');
            return `
              <span
                class="badge bg-label-info"
                data-bs-toggle="tooltip"
                data-bs-title="${countryList}"
                title="${countryList}"
                style="cursor: pointer;"
              >
                ${count} countries
              </span>
            `;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            let actionButtons = '<div class="d-flex flex-column align-items-start gap-1">';

            if (userPermissions.includes('product.edit')) {
              actionButtons += `
        <a href="${baseUrl}product/${full.sku}/edit"
           class="btn btn-sm btn-outline-primary rounded-pill"
           id="edit-user-${full.sku}">
          <i class="ri ri-edit-box-line me-1"></i> Edit
        </a>`;
            }

            if (userPermissions.includes('product.edit')) {
              actionButtons += `
        <button type="button"
                class="btn btn-sm btn-outline-success rounded-pill translate-btn"
                data-sku="${full.sku}">
          <i class="ri ri-translate me-1"></i> Translate
        </button>`;
            }

            actionButtons += '</div>';
            return actionButtons;
          }
        },


      ],

      order: [[0, 'desc']],

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

        var blockPage = json.block_page ?? false;


        if (blockPage) {
          const $tableBody = $('.datatables-products tbody');
          $tableBody.empty(); // remove all rows

          // Add single message row
          $tableBody.append(`
      <tr>
        <td colspan="100%" class="text-center text-danger fw-bold py-4">
          Price updating, please try again after some time.
        </td>
      </tr>
    `);

          return; // stop further processing
        }


        if (json?.recordsTotal) {
          $('#all_products_title').text(`All Products (${json.recordsTotal})`);
        }
        $('#total-info-block').remove();

        if (json) {
          let total_active = json.total_active ?? 0;
          let total_inactive = json.total_inactive ?? 0;
          let total_ztorm = json.total_ztorm ?? 0;
          let total_incomm = json.total_incomm ?? 0;
          let total_point_nexus = json.total_point_nexus ?? 0;
          let total_genba = json.total_genba ?? 0;


          $('#datatables-products_wrapper > div:first-child .dt-layout-end').append(`
  <div class="d-flex align-items-center gap-3 ms-3" id="total-info-block">
    <span id="stat-active">Active: ${total_active}</span>
    <span id="stat-inactive">Inactive: ${total_inactive}</span>
    <span id="stat-ztorm">Ztorm: ${total_ztorm}</span>
    <span id="stat-incomm">InComm: ${total_incomm}</span>
    <span id="stat-point-nexus">Point Nexus: ${total_point_nexus}</span>
    <span id="stat-genba">Genba: ${total_genba}</span>
  </div>
`);
        }

        // Reinitialize tooltips for country badges
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
          const tooltip = bootstrap.Tooltip.getOrCreateInstance(element);
          tooltip.show();
          tooltip.hide();
        });


      }
    });


    if (window.Helpers.isNavbarFixed()) {
      var navHeight = $('#layout-navbar').outerHeight();
      var fixedHeader = new $.fn.dataTable.FixedHeader(dt_products_table, {
        headerOffset: navHeight
      });

      // Apply background color when FixedHeader is enabled
      $('.fixedHeader-floating thead').css({
        'background-color': 'black !important', // Change to your desired color
        'box-shadow': '0px 2px 5px rgba(0, 0, 0, 0.1)', // Optional shadow for better visibility
        'z-index': '1050' // Ensure it stays above other elements
      });
    } else {
      new $.fn.dataTable.FixedHeader(dt_products_table);
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


  // $(function () {
  //   var dt_adv_filter_table = $('.datatables-products');
  //
  //   $('input.dt-input').on('keyup', function () {
  //     filterColumn($(this).attr('data-column'), $(this).val());
  //   });
  //
  //   $('select.dt-input').on('change', function () {
  //     filterColumn($(this).attr('data-column'), $(this).val());
  //   });
  //
  //   function filterColumn(i, val) {
  //     dt_adv_filter_table.DataTable().column(i).search(val, false, true).draw();
  //   }
  // });

  $(function () {

    const dt_adv_filter_table = $('.datatables-products');
    const table = $('#datatables-products').DataTable(); // Initialize once



    $('select#search-filter-type').on('change', function () {
      const selectValue = $(this).val();
      const inputValue = $('.multiple-input').val() ?? '';
      const table = $('#datatables-products').DataTable();

      if (inputValue) {
        // Clear all
        table.column(4).search('');
        table.column(1).search('');
        table.column(3).search('');

        // Apply only the one that matches
        if (selectValue === 'sku') {
          table.column(4).search(inputValue);
        } else if (selectValue === 'name') {
          table.column(1).search(inputValue);
        } else if (selectValue === 'developers') {
          table.column(3).search(inputValue);
        }

        // ✅ Only one draw
        table.draw();
      }
    });







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
      const optionValue = $('#search-filter-type').val() ?? '';

      if (dataColumn == 1 && optionValue) {
        // Clear all first
        table.column(4).search('');
        table.column(1).search('');
        table.column(3).search('');

        // Apply only the selected search type
        if (optionValue === 'sku') {
          table.column(4).search(inputValue);
        } else if (optionValue === 'name') {
          table.column(1).search(inputValue);
        } else if (optionValue === 'developers') {
          table.column(3).search(inputValue);
        }

        // ✅ Single draw (1 AJAX call only)
        table.draw();
      }
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




$(document).on('click', '.translate-btn', function () {
  let sku = $(this).data('sku');

  $.ajax({
    url: baseUrl + "product-management/translate",
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
    type: "POST",
    data: {sku: sku},

    beforeSend: function () {
      // Show page loader
      $("#page-loader").fadeIn();
    },

    success: function (response) {
      if (response.success) {
        showToast(response.message, 'Success', 'text-success');
      } else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 4000);
      }
    },

    error: function () {
      showToast('Something went wrong', 'Error', 'text-warning', 4000);
    },

    complete: function () {
      // Hide page loader
      $("#page-loader").fadeOut();
    }
  });
});
