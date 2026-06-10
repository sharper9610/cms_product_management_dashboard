document.addEventListener('DOMContentLoaded', function () {
  // These come from your PHP blade -> JS
  let total = window.totalProductCount; // e.g. 1204
  let completed = window.totalCompletedProductCount; // e.g. 1
  let remaining = total - completed;

  // Calculate % values
  let completedPercent = total > 0 ? (completed / total) * 100 : 0;
  let remainingPercent = 100 - completedPercent;

  var options = {
    chart: {
      type: 'bar',
      height: 120,
      stacked: true,
      toolbar: {show: false}
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 8,
        barHeight: '40%'
      }
    },
    dataLabels: {
      enabled: true,
      style: {
        fontSize: '14px',
        fontWeight: 'bold',
        colors: ['#fff']
      },
      formatter: function (val, opts) {
        if (opts.seriesIndex === 0) {
          return val.toFixed(2) + '%'; // show % only on completed
        }
        return '';
      }
    },
    series: [
      {
        name: 'Completed',
        data: [completedPercent]
      },
      {
        name: 'Remaining',
        data: [remainingPercent]
      }
    ],
    xaxis: {
      categories: ['Overall Completion'],
      max: 100,
      labels: {
        formatter: function (val) {
          return val + '%';
        }
      }
    },
    colors: ['#28c76f', '#e0e0e0'],
    grid: {
      borderColor: '#e5e7eb',
      strokeDashArray: 4
    },
    legend: {
      show: false
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val.toFixed(2) + '%';
        }
      }
    }
  };

  var chart = new ApexCharts(document.querySelector('#completionOverviewChart'), options);
  chart.render();
});

document.addEventListener('DOMContentLoaded', function (e) {

  // Variable declaration for table
  const dt_products_table = document.querySelector('.datatables-product-completeness');


  const buttons = [];


  //  datatable
  if (dt_products_table) {
    const dt_user = new DataTable(dt_products_table, {
      serverSide: true,
      ajax: {
        url: baseUrl + 'home-product-list',
        type: 'GET',
        beforeSend: function () {
          $('.datatables-product-completeness tbody').html(`
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
          $('.datatables-product-completeness tbody').find('.spinner-border').closest('tr').remove();
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
        {data: 'sku'},
        {data: 'name'},
        {data: 'completion'},
        {data: 'is_media_main'},
        {data: 'is_localizations'},
        {data: 'is_prices'},
        {data: 'is_countries'},
        {data: 'is_tags'},
        {data: 'is_rating'},
        {data: 'is_system_requirements'},
        {data: 'missingItems'},
        {data: 'category', visible: false},
        {data: 'developer', visible: false},
        {data: 'publisher', visible: false},
        {data: 'platform', visible: false},
        {data: 'platform', visible: false},
        {data: 'completed_product', visible: false},
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

        {
          targets: 2,
          orderable: true,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            let name = full['name'] || '';
            let sku = full['sku'] || '';
            let description = full['description'] || ''; // optional if you have a description

            // truncate name if longer than 50 chars
            if (name.length > 50) {
              name = name.substring(0, 50) + '...';
            }

            return `
      <div>
        <div class="text-sm font-medium text-gray-900">${name}</div></div>
    `;
          }
        },
        {
          targets: 3,
          orderable: false,
          render: function (data, type, full, meta) {
            if (full['is_completion']) {
              return `
        <span class="me-2">
          <span class="badge bg-label-success rounded p-1_5">
            <i class="icon-base ri ri-checkbox-line icon-24px"></i>
          </span>
        </span>
      `;
            } else {
              return `
        <span class="d-flex align-items-center gap-1">
          <span class="badge bg-label-danger rounded p-1_5">
            <i class="icon-base ri ri-close-line icon-24px"></i>
          </span>
          <span class="text-danger fw-semibold">${full['req_completion']}%</span>
        </span>
      `;
            }
          }
        },

        {
          targets: 4,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_media_main']);
          }
        },
        {
          targets: 5,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_localizations']);
          }
        },
        {
          targets: 6,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_prices']);
          }
        },
        {
          targets: 7,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_countries']);
          }
        },
        {
          targets: 8,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_tags']);
          }
        },
        {
          targets: 9,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_rating']);
          }
        },
        {
          targets: 10,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderStatusBadge(full['is_system_requirements']);
          }
        },
        {
          targets: 11, // 11th column (0-based index)
          orderable: false,
          render: function (data, type, full, meta) {
            if (full['missing_items'] && full['missing_items'].length > 0) {
              // join array into comma-separated string
              return `<span class="text-danger">${full['missing_items'].join(', ')}</span>`;
            }
            return ''; // no missing items
          }
        },


        {
          targets: [12, 13, 14, 15, 16,17],
          orderable: false,
          visible: false,
          render: function (data, type, full, meta) {
            return "";
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
            if (userPermissions.includes('product.edit')) {
              actionButtons += `
        <a href="${baseUrl}product/${full.sku}/edit"
           class="btn btn-sm btn-outline-primary rounded-pill me-1"
           id="edit-user-${full.sku}">
          <i class="ri ri-edit-box-line me-1"></i> Edit
        </a>`;
            }

            // Translate button with icon + text
            if (userPermissions.includes('product.edit')) {
              actionButtons += `
        <button type="button"
                class="btn btn-sm btn-outline-success rounded-pill me-1 translate-btn"
                data-sku="${full.sku}"
                title="Translate">
          <i class="ri ri-translate me-1"></i> Translate
        </button>`;
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
                menu: [10, 25, 50, 100],
                text: 'Show_MENU_entries'
              }
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
      }
    });

    //? The 'delete-record' class is necessary for the functionality of the following code.

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

    function renderStatusBadge(isTrue) {
      if (isTrue) {
        return `
      <span class="me-4">
        <span class="badge bg-label-success rounded p-1_5">
          <i class="icon-base ri ri-checkbox-line icon-24px"></i>
        </span>
      </span>
    `;
      } else {
        return `
      <span class="me-4">
        <span class="badge bg-label-danger rounded p-1_5">
          <i class="icon-base ri ri-close-line icon-24px"></i>
        </span>
      </span>
    `;
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

    const dt_adv_filter_table = $('.datatables-product-completeness');
    const table = $('#product-completeness').DataTable(); // Initialize once



    $('select#search-filter-type').on('change', function () {
      const selectValue = $(this).val();
      const inputValue = $('.multiple-input').val() ?? '';
      const table = $('#product-completeness').DataTable();

      if (inputValue) {
        // Clear all
        table.column(1).search('');
        table.column(2).search('');
        table.column(13).search('');

        // Apply only the one that matches
        if (selectValue === 'sku') {
          table.column(1).search(inputValue);
        } else if (selectValue === 'name') {
          table.column(2).search(inputValue);
        } else if (selectValue === 'developers') {
          table.column(13).search(inputValue);
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
        table.column(1).search('');
        table.column(2).search('');
        table.column(13).search('');

        // Apply only the selected search type
        if (optionValue === 'sku') {
          table.column(1).search(inputValue);
        } else if (optionValue === 'name') {
          table.column(2).search(inputValue);
        } else if (optionValue === 'developers') {
          table.column(13).search(inputValue);
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

$(document).ready(function () {
  let allCountriesData = []; // To store the fetched data for filtering

  // --- Open Modal and Fetch Data ---
  $('.country-card').on('click', function () {
    $('#countriesModal').modal('show');

    // Fetch data only if it hasn't been fetched yet
    if (allCountriesData.length === 0) {
      $.ajax({
        url: baseUrl + 'countries-with-products',
        method: 'GET',
        success: function (data) {
          allCountriesData = data; // Store data
          $('#countriesModal .modal-title').text(`Countries Served (${allCountriesData.length})`);

          renderCountryList(allCountriesData); // Render the full list
        },
        error: function () {
          $('#country-list').html('<p class="text-center text-danger">Failed to load countries.</p>');
        }
      });
    }
  });

  // --- Function to Render the Country List ---
  function renderCountryList(countries) {
    let list = $('#country-list');
    list.empty();

    if (countries.length === 0) {
      list.html('<p class="text-center text-muted">No countries found.</p>');
      return;
    }
    $('#countriesModalLabel').val('Countries Served' + '(' + countries.length + ')');

    countries.forEach(country => {
      let item = $(`
        <a href="#" class="list-group-item list-group-item-action country-item" data-code="${country.country_code}">
          <div class="d-flex w-100 justify-content-between align-items-center">
            <div>
              <h6 class="mb-1">${country.country_name}</h6>
              <small class="text-muted">${country.country_code} • ${country.region}</small>
            </div>
            <span class="badge bg-primary rounded-pill">${country.product_count}</span>
          </div>
        </a>
      `);
      list.append(item);
    });
  }

  // --- Handle Clicking on a Country Item ---
  // $(document).on('click', '.country-items', function (e) {
  //   e.preventDefault(); // Prevent default anchor tag behavior
  //
  //   // Highlight the selected item
  //   $('.country-item').removeClass('active');
  //   $(this).addClass('active');
  //
  //   let countryCode = $(this).data('code');
  //   // Find the country data from our stored array
  //   let countryData = allCountriesData.find(c => c.country_code === countryCode);
  //
  //   if (countryData) {
  //     renderCountryDetails(countryData);
  //   }
  // });

  $(document).on('click', '.country-items', function (e) {
    e.preventDefault();
    $('.country-item').removeClass('active');
    $(this).addClass('active');

    let countryCode = $(this).data('code');
    let detailsContainer = $('#country-details');

    // Show loading spinner centered
    detailsContainer.html(`
        <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading ...</p>
            </div>
        </div>
    `);

    $.ajax({
      url: baseUrl + `country-with-products/${countryCode}`,
      type: 'GET',
      success: function (response) {
        if (response) {
          // Find the country data from your stored array
          let countryData = allCountriesData.find(c => c.country_code === countryCode);

          if (countryData) {
            renderCountryDetails(countryData);
          }
        } else {
          detailsContainer.html('<p class="text-danger">No data available for this country.</p>');
        }
      },
      error: function () {
        detailsContainer.html('<p class="text-danger">Failed to fetch country details.</p>');
      }
    });
  });

  // --- Function to Render Country Details on the Right Side ---

  function renderCountryDetailsss(country) {
    let detailsContainer = $('#country-details');
    // Assuming 'products' is an array of strings in your country object. Adjust if needed.
    let productsHtml =
      country.products && country.products.length > 0
        ? country.products.map(p => `<li class="list-group-item">${p}</li>`).join('')
        : '<li class="list-group-item">No specific products listed.</li>';

    // Assuming you might have a variable for the total, e.g., country.product_count
    const totalCount = country.product_count || 0;

    let detailsContent = `
      <div class="p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">${country.country_name}</h5>
            <small class="text-muted">${country.country_code} &bull; ${country.currency_name}</small>
          </div>
          <div>
            <span class="fw-bold">Products Available: ${totalCount}</span>
          </div>
        </div>
        <hr>
        <dl class="row">
    <div class="col-sm-6 col-lg-4">
    <div class="card card-border-shadow-primary h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ri ri-newspaper-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">10</h4>
        </div>
        <h6 class="mb-0 fw-normal">Active Publishers</h6>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-4">
    <div class="card card-border-shadow-danger h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ri ri-file-damage-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">1</h4>
        </div>
        <h6 class="mb-0 fw-normal">Missing Publishers</h6>
      </div>
    </div>
  </div>

  <div class="col-sm-12 col-lg-4">
    <div class="card card-border-shadow-info h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ri ri-compass-discover-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">67%</h4>
        </div>
        <h6 class="mb-0 fw-normal">Publisher Coverage</h6>
      </div>
    </div>
  </div>




      </div>
  `;
    detailsContainer.html(detailsContent);
  }

  // --- Global Data (assuming this exists) ---
  // let allCountriesData = [
  //   { country_code: 'US', country_name: 'United States', currency_name: 'US Dollar' },
  //   { country_code: 'CA', country_name: 'Canada', currency_name: 'Canadian Dollar' },
  //   // ... more countries
  // ];

  // --- Event Listener for Country Clicks ---
  $(document).on('click', '.country-item', function (e) {
    e.preventDefault();
    $('.country-item').removeClass('active');
    $(this).addClass('active');

    let countryCode = $(this).data('code');
    let detailsContainer = $('#country-details');

    // Show loading spinner
    detailsContainer.html(`
      <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
          <div class="text-center">
              <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Loading...</p>
          </div>
      </div>
  `);

    $.ajax({
      url: baseUrl + `country-with-products/${countryCode}`,
      type: 'GET',
      success: function (response) {
        if (response.success) {
          // Pass the country code and the full API response to the render function
          renderCountryDetails(countryCode, response);
        } else {
          detailsContainer.html(`<p class="text-danger">${response.message}</p>`);
        }
      },
      error: function () {
        detailsContainer.html('<p class="text-danger">Failed to fetch country details.</p>');
      }
    });
  });

  // --- Function to Render Country Details on the Right Side ---
  function renderCountryDetails(countryCode, data) {
    let detailsContainer = $('#country-details');

    // Find the country data from your stored array using the countryCode
    // This is necessary because your API response doesn't include the country name/currency.
    let countryData = allCountriesData.find(c => c.country_code === countryCode);

    let totalActive = data.active.count;
    let totalInactive = data.inactive.count;
    let totalCount = data.total;
    let totalCoverage = data.totalCoverage ?? '0';

    // let activePublishersHtml = data.active.publishers.length > 0 ?
    //   data.active.publishers.map(p => `<li class="list-group-item d-flex align-items-center"><i class="ri ri-check-line text-success me-2"></i>${p}</li>`).join('') :
    //   '<li class="list-group-item">No active publishers.</li>';
    //
    // let inactivePublishersHtml = data.inactive.publishers.length > 0 ?
    //   data.inactive.publishers.map(p => `<li class="list-group-item d-flex align-items-center"><i class="ri ri-close-line text-danger me-2"></i>${p}</li>`).join('') :
    //   '<li class="list-group-item">No inactive publishers.</li>';

    let activePublishersHtml =
      data.active.publishers.length > 0
        ? data.active.publishers
          .map(
            p => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><i class="ri ri-check-line text-success me-2"></i>${p.publisher_name}</span>
        <span class="badge bg-success rounded-pill">${p.count}</span>
      </li>
    `
          )
          .join('')
        : '<li class="list-group-item">No active publishers.</li>';

    let inactivePublishersHtml =
      data.inactive.publishers.length > 0
        ? data.inactive.publishers
          .map(
            p => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><i class="ri ri-close-line text-danger me-2"></i>${p.publisher_name}</span>
        <span class="badge bg-danger rounded-pill">${p.count}</span>
      </li>
    `
          )
          .join('')
        : '<li class="list-group-item">No inactive publishers.</li>';

    let detailsContent = `
    <div class="p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">${countryData ? countryData.country_name : 'Country Details'}</h5>
          <small class="text-muted">${countryData ? countryData.country_code : ''} &bull; ${countryData ? countryData.currency_name : ''}</small>
        </div>
        <div>
          <span class="fw-bold">Products Available: ${countryData ? countryData.product_count : ''}</span>
        </div>
      </div>
      <hr>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
          <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar me-4">
                  <span class="avatar-initial rounded bg-label-primary">
                       <i class="icon-base ri ri-newspaper-line icon-24px"></i>
                  </span>
                </div>
                <h4 class="mb-0">${totalActive}</h4>
              </div>
              <h6 class="mb-0 fw-normal">Active Publishers</h6>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="card card-border-shadow-danger h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar me-4">
                  <span class="avatar-initial rounded bg-label-danger">


                    <i class="icon-base ri ri-file-damage-line icon-24px"></i>
                  </span>
                </div>
                <h4 class="mb-0">${totalInactive}</h4>
              </div>
              <h6 class="mb-0 fw-normal">Inactive Publishers</h6>
            </div>
          </div>
        </div>
        <div class="col-sm-12 col-lg-4">
          <div class="card card-border-shadow-info h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar me-4">
                  <span class="avatar-initial rounded bg-label-info">
                    <i class="icon-base ri ri-compass-discover-line icon-24px"></i>
                  </span>
                </div>
                <h4 class="mb-0">${totalCoverage} %</h4>
              </div>
              <h6 class="mb-0 fw-normal">Publisher Coverage</h6>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">Active Publishers</h5>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                ${activePublishersHtml}
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header bg-danger text-white">
              <h5 class="mb-0">Inactive Publishers</h5>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                ${inactivePublishersHtml}
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
    detailsContainer.html(detailsContent);
  }

  // --- Live Search Functionality ---
  // --- Live Search Functionality (Corrected) ---
  $('#countrySearch').on('keyup', function () {
    let searchTerm = $(this).val().toLowerCase();

    let filteredCountries = allCountriesData.filter(country => {
      // Ensure country_name and country_code are treated as strings, even if null
      const name = country.country_name || '';
      const code = country.country_code || '';

      // Now, safely perform the search
      return name.toLowerCase().includes(searchTerm) || code.toLowerCase().includes(searchTerm);
    });

    renderCountryList(filteredCountries);
  });
});

// Handle Translate button click
$(document).on('click', '.translate-btn', function () {
  let sku = $(this).data('sku');

  $.ajax({
    url: baseUrl + 'product-management/translate',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    type: 'POST',
    data: {sku: sku},

    beforeSend: function () {
      // Show page loader
      $('#page-loader').fadeIn();
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
      $('#page-loader').fadeOut();
    }
  });
});



$(document).ready(function(){

  function ajaxFormSubmit(formId, spinnerId, modalId, url) {
    $(formId).on('submit', function(e){
      e.preventDefault();

      var form = $(this);
      var spinner = $(spinnerId);

      spinner.removeClass('d-none');
      form.find('button[type="submit"]').prop('disabled', true);

      const params = new URLSearchParams(window.location.search);
      let fullUrl = baseUrl + `${url}`;

      // If 'check' param exists, append it
      if (params.has('check')) {
        const checkValue = params.get('check');
        fullUrl += `?check=${checkValue}`;
      }

      $.ajax({
        url: fullUrl,
        method: "POST",
        data: form.serialize(),
        success: function(response){

          if (response?.success) {
            showToast(response?.message ?? 'Imported successfully!', 'Success', 'bg-success');

          } else {
            showToast(response?.message ?? 'Something went wrong!', 'Error!', 'bg-warning');
          }

          $(modalId).modal('hide');
          form[0].reset();
        },
        error: function(xhr){
          showToast('Something went wrong!', 'Error!', 'bg-danger');

        },
        complete: function(){
          spinner.addClass('d-none');
          form.find('button[type="submit"]').prop('disabled', false);
        }
      });
    });
  }

  // Initialize AJAX for Product
  ajaxFormSubmit('#productImportForm',
    '#productSpinner',
    '#productImportModal', "import-products");

  // Initialize AJAX for Price
  ajaxFormSubmit('#priceImportForm',
    '#priceSpinner', '#priceImportModal',
    "import-prices");



  $(document).ready(function() {
    // Function to handle the toggle logic
    function toggleSkuField(checkboxId, skuFieldClass) {
      const $checkbox = $(checkboxId);
      const $skuField = $checkbox.closest('.modal-body').find('.' + skuFieldClass);
      const $skuInput = $skuField.find('input[name="sku"]');

      // Initial check on load (though usually the box starts unchecked)
      if ($checkbox.is(':checked')) {
        $skuField.hide();
        $skuInput.prop('required', false);
        $skuInput.val(''); // Clear value when hidden
      } else {
        $skuField.show();
        $skuInput.prop('required', true);
      }

      // Event listener for changes
      $checkbox.on('change', function() {
        if ($(this).is(':checked')) {
          // If 'Import All' is checked
          $skuField.slideUp(200); // Hide the field
          $skuInput.prop('required', false); // Not required
          $skuInput.val(''); // Clear value
        } else {
          // If 'Import All' is unchecked
          $skuField.slideDown(200); // Show the field
          $skuInput.prop('required', true); // Required
        }
      });
    }

    // Apply the logic to both modals
    // Product Modal: checkbox is #product-import-all, SKU container has class .sku-field
    toggleSkuField('#product-import-all', 'sku-field');


  });
});


