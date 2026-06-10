/**
 * Page User List
 */

'use strict';

$(document).ready(function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});

// Reinitialize tooltips after DataTable update
$('.datatables-users').on('draw.dt', function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  const startDateEle = document.querySelector('.start_date');
  const endDateEle = document.querySelector('.end_date');
  const rangePickr = document.querySelector('.flatpickr-range'),
    dateFormat = 'YYYY/MM/DD';

  if (rangePickr) {
    rangePickr.flatpickr({
      mode: 'range',
      dateFormat: 'Y/m/d',
      onClose: function (selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
          const startDate = instance.formatDate(selectedDates[0], 'Y/m/d');
          const endDate = instance.formatDate(selectedDates[1], 'Y/m/d');

          // ✅ Set the input value so DataTables column search works
          rangePickr.value = `${startDate} to ${endDate}`;

          // ✅ Optional: update hidden fields if backend needs separate start/end
          startDateEle.value = startDate;
          endDateEle.value = endDate;

          // ✅ Trigger DataTables filter
          dt_adv_filter
            .column(12)
            .search(rangePickr.value) // now it will send this value in payload
            .draw();
        }
      }
    });
  }

  // Advance filter function
  // We pass the column location, the start date, and the end date
  // Clear existing custom filters
  if (typeof $.fn !== 'undefined' && typeof $.fn.dataTableExt !== 'undefined') {
    $.fn.dataTableExt.afnFiltering.length = 0;
  }

  const filterByDate = function (column, startDate, endDate) {
    // Custom filter syntax requires pushing the new filter to the global filter array
    if (typeof $.fn !== 'undefined' && typeof $.fn.dataTableExt !== 'undefined') {
      $.fn.dataTableExt.afnFiltering.push(function (oSettings, aData, iDataIndex) {
        const rowDate = normalizeDate(aData[column]);
        const start = normalizeDate(startDate);
        const end = normalizeDate(endDate);

        // If our date from the row is between the start and end
        if (start <= rowDate && rowDate <= end) {
          return true;
        } else if (rowDate >= start && end === '' && start !== '') {
          return true;
        } else if (rowDate <= end && start === '' && end !== '') {
          return true;
        } else {
          return false;
        }
      });
    }
  };

  // Convert date strings to a Date object, then normalize into YYYYMMDD format
  const normalizeDate = function (dateString) {
    const date = new Date(dateString);
    const normalized =
      date.getFullYear() +
      ('0' + (date.getMonth() + 1)).slice(-2) + // Ensure month is two digits
      ('0' + date.getDate()).slice(-2); // Ensure day is two digits
    return normalized;
  };

  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  var statusObj = {
    PENDING: { text: 'Pending', class: 'bg-label-warning' },
    PROCESSING: { text: 'Processing', class: 'bg-label-info' },
    COMPLETED: { text: 'Completed', class: 'bg-label-success' },
    PARTIALLY_COMPLETED: { text: 'Partially Completed', class: 'bg-label-primary' },
    FAILED: { text: 'Failed', class: 'bg-label-danger' },
    VALIDATION_FAILED: { text: 'Validation Failed', class: 'bg-label-danger' },
    CANCELLED: { text: 'Cancelled', class: 'bg-label-secondary' }
  };

  // Advanced Search
  // --------------------------------------------------------------------

  const dt_adv_filter_table = document.querySelector('.datatables-users');
  let dt_adv_filter;
  // Advanced Filter table
  if (dt_adv_filter_table) {
    dt_adv_filter = new DataTable(dt_adv_filter_table, {
      serverSide: true,
      ajax: {
        url: baseUrl + 'order-list',
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
        { data: 'id' },
        { data: 'id' },
        { data: 'order_id_2game' },
        { data: 'product' },
        { data: 'status' },
        { data: 'total_qty' },
        { data: 'total_price' },
        { data: 'cost_price' },
        { data: 'vat_amount' },
        { data: 'payment_fee' },

        { data: 'profit' },

        { data: 'source' },
        { data: 'created_at' },
        {data: 'country_code', visible: false},
      ],
      columnDefs: [
        {
          className: 'control',
          orderable: false,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          targets: 1, // order_id column
          orderable: true,
          render: function (data, type, full, meta) {
            var id = full['id'] ?? '';
            var order_id_2game = full['order_id_2game'] ?? '';

            // Escape quotes for tooltip
            var escapedOrderId = order_id_2game.replace(/'/g, '&#39;').replace(/"/g, '&quot;');

            // Show first 10 characters
            var displayValue = order_id_2game.length > 14 ? order_id_2game.substring(0, 14) + '...' : order_id_2game;

            return `
      <a href="#"
         class="order-details-link"
         data-order-id="${full.id}"
    >
        ${id}
      </a>`;
          }
        },
        {
          targets: 2, // order_id column
          orderable: true,
          render: function (data, type, full, meta) {
            var id = full['id'] ?? '';
            var order_id_2game = full['order_id_2game'] ?? '';

            // Escape quotes for tooltip
            var escapedOrderId = order_id_2game.replace(/'/g, '&#39;').replace(/"/g, '&quot;');

            return `
  <a href="#"
     class="order-details-link"
     data-order-id="${full.id}"
     style="
        max-width:130px;
        display:inline-block;
        white-space:normal;
        word-wrap:break-word;
        word-break:break-all;
     "
  >
      ${order_id_2game}
  </a>`;
          }
        },
        {
          targets: 3,
          orderable: false,
          render: function (data, type, full, meta) {
            var $product = full['product'] ?? '';

            return `
      <span
        class="d-inline-block position-relative me-4 product-name"
        style="max-width:220px; word-wrap: break-word; white-space: normal;"
      >
        ${$product}
      </span>
    `;
          }
        },

        {
          targets: 4,
          orderable: false,
          render: function (data, type, full, meta) {
            const statuses = full['item_status'] ?? {};
            const lastFailureReason = full['last_failure_reason'];

            const badges = Object.entries(statuses).map(([status, count]) => {
              const statusClass = statusObj[status]?.class || 'bg-label-warning';

              return `
        <div class="mb-1">
          <span class="badge ${statusClass} d-inline-flex align-items-center" style="white-space: nowrap;">
            ${status}
            ${count > 0 ? `<span class="ms-1 px-1 py-0 rounded bg-light text-dark" style="font-size: 0.7em;">${count}</span>` : ''}
          </span>
        </div>
      `;
            });

            const badgesHtml = badges.length ? `<div class="d-flex flex-column gap-1">${badges.join('')}</div>` : '';

            if (lastFailureReason && badgesHtml) {
              return `<span data-bs-toggle="tooltip" title="${lastFailureReason}">${badgesHtml}</span>`;
            }

            return badgesHtml;
          }
        },

        {
          targets: 6,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderPriceWithEuro(full['total_price'], full['total_price_euro'], full['currency_code']);
          }
        },

        {
          targets: 7,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderPriceWithEuro(full['cost_price'], full['cost_price_euro'], full['currency_code']);
          }
        },
        {
          targets: 8,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderPriceWithEuro(full['vat_amount'], full['vat_amount_euro'], full['currency_code']);
          }
        },
        {
          targets: 9,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderPriceWithEuro(full['payment_fee'], full['payment_fee_euro'], full['currency_code']);
          }
        },
        {
          targets: 10,
          orderable: false,
          render: function (data, type, full, meta) {
            return renderPriceWithEuro(full['profit'], full['profit_euro'], full['currency_code']);
          }
        },
        {
          targets: 5,
          orderable: false
        },
        {
          targets: 12,
          orderable: true
        },
        {
          targets: 11, // source column index
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            const sources = full.source || []; // expecting array like [1,2]
            if (!Array.isArray(sources) || sources.length === 0) {
              return '';
            }

            const country_code = full['country_code'];

            return sources
              .map(source => {
                let badgeClass = 'badge rounded-pill bg-label-secondary'; // default gray
                let label = source;

                if (source === 1) {
                  badgeClass = 'badge rounded-pill bg-label-primary'; // blue
                  label = 'Ztorm';
                } else if (source === 2) {
                  badgeClass = 'badge rounded-pill bg-label-info'; // yellow/orange
                  label = 'Incomm';
                } else if (source === 3) {
                  badgeClass = 'badge rounded-pill bg-label-warning';
                  label = 'Point Nexus';
                } else if (source === 4) {
                  badgeClass = 'badge rounded-pill bg-label-success'; // pick a color
                  label = 'Genba';
                }

                return `<span class="${badgeClass} d-block mb-1">${label}</span>
                  <span class="badge rounded-pill bg-label-light d-block mb-1" data-bs-toggle="tooltip" title="Country Code">CC: ${country_code}</span>`;
              })
              .join('');
          }
        },
        {
          targets: [13],
          orderable: false,
          visible: false,
          render: function (data, type, full, meta) {
            return "";
          }
        },
      ],
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
          rowClass: 'row mx-2 justify-content-between',
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
      order: [[11, 'desc']],

      orderCellsTop: true,
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['order_id_2game'];
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

      drawCallback: function (settings) {
        var api = this.api();
        var json = api.ajax.json();

        // Remove old totals block
        $('#total-info-block').remove();

        // Add new totals block if totals exist
        if (json?.totals) {
          // Sanitize and default values
          const getFormattedValue = key => {
            const value = json.totals[key] ?? 0;
            // Ensure to use the formatted number from the server or format it here if needed
            // Since the server side formats 'price' and 'costPrice', we'll assume they are strings
            // If they are not formatted, you should use: Number(value).toLocaleString('en-US', { minimumFractionDigits: 2 })
            return value;
          };

          let total_price = getFormattedValue('price');
          let costPrice = getFormattedValue('costPrice');

          let paymentFeeTotal = getFormattedValue('paymentFeeTotal');
          let vat_amount = getFormattedValue('vat_amount');
          let profit = getFormattedValue('profit');
          let total_qty = getFormattedValue('qty');

          // Append totals to DataTable header (using Bootstrap/standard classes for spacing and flex)
          $('#DataTables_Table_0_wrapper > div:first-child .dt-layout-end').append(`

      <div class="d-flex flex-wrap align-items-center gap-2 ms-3" id="total-info-block" style="font-size: 0.85rem;">

    <div class="badge bg-secondary px-2 py-1 shadow-sm">
        <strong class="me-1">Quantity:</strong> ${total_qty.toLocaleString()}
    </div>

    <div class="badge bg-secondary px-2 py-1 shadow-sm">
        <strong class="me-1">Sales :</strong> €${total_price}
    </div>

    <div class="badge bg-secondary px-2 py-1 shadow-sm" title="Total Cost Price">
        <strong class="me-1">Cost :</strong> €${costPrice}
    </div>

    <div class="badge bg-secondary px-2 py-1 shadow-sm" title="Total VAT">
        <strong class="me-1">VAT :</strong> €${vat_amount}
    </div>

    <div class="badge bg-secondary px-2 py-1 shadow-sm" title="Total Payment Fee">
        <strong class="me-1">Payment Fee :</strong> €${paymentFeeTotal}
    </div>

    <div class="badge bg-secondary px-2 py-1 shadow-sm" title="Total Profit">
        <strong class="me-1">Profit :</strong> €${profit}
    </div>

</div>

    `);
          // Note: I removed paymentFeeTotal and vat_amount to keep it condensed,
          // but you can add them back using the same structure.
        }

        // Re-initialize tooltips in case they are inside the table
        $('[data-bs-toggle="tooltip"]').tooltip();
      }
    });
  }

  function renderPriceWithEuro(price = 0, euro = 0, currency_code = '') {
    // Define symbols for known currencies
    let currencySymbol = '';
    switch (currency_code.toUpperCase()) {
      case 'BRL':
        currencySymbol = 'R$';
        break;
      case 'USD':
        currencySymbol = '$';
        break;
      case 'GBP':
        currencySymbol = '£';
        break;
      case 'EUR':
        currencySymbol = '€';
        break;
      default:
        currencySymbol = ''; // fallback
    }

    return `
    <div class="text-start">
      <div>
        <span class="text-primary">
          ${currencySymbol}${price}
        </span>
      </div>
      <div>
        <span class="text-success">
          €${euro}
        </span>
      </div>
    </div>
  `;
  }

  if (window.Helpers.isNavbarFixed()) {
    var navHeight = $('#layout-navbar').outerHeight();
    var fixedHeader = new $.fn.dataTable.FixedHeader(dt_adv_filter_table, {
      headerOffset: navHeight
    });

    // Apply background color when FixedHeader is enabled
    $('.fixedHeader-floating thead').css({
      'background-color': 'black !important', // Change to your desired color
      'box-shadow': '0px 2px 5px rgba(0, 0, 0, 0.1)', // Optional shadow for better visibility
      'z-index': '1050' // Ensure it stays above other elements
    });
  } else {
    new $.fn.dataTable.FixedHeader(dt_adv_filter_table);
  }

  function debounce(func, wait) {
    let timeout;
    return function () {
      const context = this,
        args = arguments;
      const later = function () {
        timeout = null;
        func.apply(context, args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // --- Global State for Date Filtering ---
  // Cache the last applied date range to avoid redundant filtering/redraws
  let lastDateFilter = { startDate: '', endDate: '' };

  // --- Debounced Filter Function for Text Inputs ---
  // Adjust the delay (e.g., 300ms) as needed for your application
  const debouncedFilterColumn = debounce(function (i, val) {
    // This part remains the same as your original 'else' block for text search
    dt_adv_filter
      .column(i) // Access the correct column
      .search(val, false, true) // Apply the search
      .draw(); // Redraw the table
  }, 300); // 300ms debounce time

  // --- Filter column wise function (Now focused on logic) ---
  function filterColumnsss(i, val) {
    if (i == 12) {
      // Assuming '12' is the date column
      const startDate = startDateEle.value;
      const endDate = endDateEle.value;

      // --- NEW: Check if the date range has changed ---
      if (startDate === lastDateFilter.startDate && endDate === lastDateFilter.endDate) {
        console.log('Date filter unchanged. Skipping redraw.');
        return; // Exit early if the same date range is applied
      }

      if (startDate !== '' && endDate !== '') {
        // Reset custom filter
        $.fn.dataTable.ext.search.length = 0;

        // Custom date filtering logic
        filterByDate(i, startDate, endDate);

        // --- NEW: Update the cache ---
        lastDateFilter.startDate = startDate;
        lastDateFilter.endDate = endDate;
      } else if (startDate === '' && endDate === '') {
        // If both are cleared, you might want to clear the custom filter
        $.fn.dataTable.ext.search.length = 0;
        // Also reset the cache
        lastDateFilter.startDate = '';
        lastDateFilter.endDate = '';
      }

      // Redraw the DataTable
      dt_adv_filter.draw();
    } else {
      // This is now only for select inputs which do not need debounce
      // The text inputs will use the debouncedFilterColumn directly
      dt_adv_filter.column(i).search(val, false, true).draw();
    }
  }
  function filterColumn(i) {
    if (i == 12) {
      // Date column
      const startDate = startDateEle.value.split('/').join(''); // or just use raw
      const endDate = endDateEle.value.split('/').join('');

      // If no range selected, reset
      if (!startDate || !endDate) {
        $.fn.dataTable.ext.search.length = 0;
        dt_adv_filter.draw();
        return;
      }

      $.fn.dataTable.ext.search.length = 0;

      $.fn.dataTable.ext.search.push(function (settings, aData, dataIndex) {
        const rowDate = aData[i]; // you can ignore or just return true
        // always true if you just want to redraw
        return true;
      });

      dt_adv_filter.draw();
    } else {
      const val = document.querySelector(`[data-column="${i}"]`).value;
      dt_adv_filter.column(i).search(val, false, true).draw();
    }
  }

  // --- Event Listeners ---
  document.querySelectorAll('input.dt-input, select.dt-input').forEach(input => {
    const column = input.getAttribute('data-column');

    // For text inputs (keyup)
    if (input.type === 'text' || input.type === 'search') {
      input.addEventListener('keyup', function () {
        // Check if it's the date input which should be handled by 'change' or a separate logic
        debouncedFilterColumn(column, this.value);
      });
    }

    // Skip the date range input from the change event
    if (input.classList.contains('flatpickr-range')) return;

    // For select inputs and date inputs (change)
    // 'change' is appropriate for both select elements and date inputs (especially if using a picker)
    input.addEventListener('change', function () {
      const value = this.value;

      if (column == 12) {
        if (input === rangePickr && !input.value.includes(' to ')) {
          return; // Prevent early AJAX
        }
      }
      filterColumn(column, value); // Use the direct filterColumn function
    });
  });

  $(document).on('click', '.order-details-link', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const orderId = $(this).data('order-id');
    const $tbody = $('#orderItemsTable tbody');
    $tbody.empty();

    // Get filter values
    const status = $('.status-filter').val();
    const source = $('.source-filter').val();
    const product_id = $('.product-id-filter').val();

    // Show loading spinner
    $tbody.append(`
    <tr>
      <td colspan="12" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </td>
    </tr>
  `);

    // Show modal
    var orderModal = new bootstrap.Modal(document.getElementById('orderItemsModal'));
    orderModal.show();

    $.ajax({
      url: baseUrl + 'order-list/' + orderId,
      type: 'GET',
      data: { status: status, source: source, product_id: product_id },
      success: function (response) {
        $tbody.empty();

        if (response.items && response.items.length) {
          document.getElementById('order_id').textContent = response.order.order_id_2game || '';
          document.getElementById('country_code').textContent = response.order.country_code || '';

          const $paymentBox = $('#paymentInfoBox');
          $paymentBox.empty().addClass('d-none');

          let transactionId = '';
          let gateway = '';

          if (response.transactions && response.transactions.length > 0) {
            transactionId = response.transactions[0].transaction_id || '';
            gateway = response.transactions[0].gateway || '';
          }

          const orderStatus = response.order && response.order.status;
          const orderLastFailure = response.order && response.order.last_failure_reason;

          let orderStatusHtml = '';
          if (orderStatus) {
            const statusInfo = statusObj[orderStatus];
            const badgeClass = statusInfo ? `badge rounded-pill ${statusInfo.class}` : 'badge rounded-pill bg-label-secondary';
            const statusText = statusInfo ? statusInfo.text : orderStatus;
            if (orderLastFailure) {
              orderStatusHtml = `<span class="${badgeClass}" data-bs-toggle="tooltip" title="${orderLastFailure}">${statusText}</span>`;
            } else {
              orderStatusHtml = `<span class="${badgeClass}">${statusText}</span>`;
            }
          }

          if (transactionId || gateway || orderStatusHtml) {
            $paymentBox.removeClass('d-none').html(`
      <div class="alert alert-secondary d-flex flex-wrap gap-4 align-items-center mb-0">
        ${orderStatusHtml ? `<div><strong>Order Status:</strong> <span class="ms-1">${orderStatusHtml}</span></div>` : ''}
        ${transactionId ? `<div><strong>Payment ID:</strong><span class="ms-1">${transactionId}</span></div>` : ''}
        ${gateway ? `<div><strong>Gateway:</strong><span class="ms-1">${gateway}</span></div>` : ''}
      </div>
    `);

          }

          response.items.forEach((item, index) => {
            // ✅ If status = FAILED → show reason in tooltip

            let statusHtml = item.status;
            if (item.status === 'FAILED' && item.failed_reason) {
              statusHtml = `
    <a href="#" class="text-danger view-reason"
       data-reason='${JSON.stringify(item.failed_reason)}'>
      ${item.status}
    </a>
  `;
            }

            let redeemedContent = '';

            // CASE 1️⃣ : Already redeemed → show date + Redeem Again
            if (item.status === 'COMPLETED' && item.redeemed_at && item.redeemed_at.trim() !== '') {
              redeemedContent = `
    <div class="mb-1 ">
      ${item.redeemed_at}
    </div>
    <button class="btn btn-sm btn-outline-primary redeem-key-btn"
            data-order-item-id="${item.id}"
            data-order-id="${orderId}">
      <i class="ri ri-refresh-line me-1"></i> Redeem Again
    </button>
  `;
            }

            // CASE 2️⃣ : Completed but NOT redeemed → show Redeem Key
            else if (item.status === 'COMPLETED') {
              redeemedContent = `
    <button class="btn btn-sm btn-primary redeem-key-btn"
            data-order-item-id="${item.id}"
            data-order-id="${orderId}">
      <i class="ri ri-key-2-line me-1"></i> Redeem Key
    </button>
  `;
            }

            $tbody.append(`
            <tr>

  <td>
  <div class="d-flex flex-column gap-1">
    <div>
      <small class="text-muted">SKU:</small>
      <span class="fw-semibold">${item.product_id || ''}</span>
    </div>
    <div>
      <small class="text-muted">Name:</small>
      <span class="fw-semibold">${item.product_name || ''}</span>
    </div>
    <div>
      <small class="text-muted">Supplier:</small>
      <span class="fw-semibold">
        ${(() => {
          let badgeClass = 'badge rounded-pill bg-secondary'; // default
          if (item.source === 'ztorm') badgeClass = 'badge rounded-pill bg-label-primary';
          else if (item.source === 'incomm') badgeClass = 'badge rounded-pill bg-label-info';
          else if (item.source === 'point nexus') badgeClass = 'badge rounded-pill bg-label-warning';
          else if (item.source === 'genba') badgeClass = 'badge rounded-pill bg-label-success';
          return `<span class="${badgeClass}">${item.source || ''}</span>`;
        })()}
      </span>
    </div>
    <div>
      <small class="text-muted">Status:</small>
      <span class="fw-semibold">
        ${(() => {
          if (statusObj[item.status]) {
            let badgeClass = `badge rounded-pill ${statusObj[item.status].class}`;
            let statusText = statusObj[item.status].text;

            // Show tooltip if FAILED with reason
            if (item.status === 'FAILED' && item.failed_reason) {
              const reasonData = btoa(JSON.stringify(item.failed_reason));

              return `
    <a href="#"
      class="${badgeClass} view-reason"
      data-reason="${reasonData}"
      data-bs-toggle="tooltip"
      title="Click to view reason">
      ${statusText}
    </a>
  `;
            } else {
              return `<span class="${badgeClass}">${statusText}</span>`;
            }
          }
          return item.status || '';
        })()}
      </span>
    </div>
     <div>
      <small class="text-muted">QTY:</small>
      <span class="fw-semibold">${item.qty || 0}</span>
    </div>
  </div>
</td>

   <td class="text-break" style="max-width: 150px; white-space: normal;">
  ${
    item.source === 'ztorm' && item.retailer_order_id
      ? `<a href="https://cms.2gamedigital.com/orders/show/${item.retailer_order_id}" target="_blank">${item.retailer_order_id}</a>`
      : item.retailer_order_id || ''
  }
</td>

             <td>${item.currency_code || ''}</td>
            <td>${renderPriceWithEuro(item.row_total, item.row_total_euro, item.currency_code)}</td>
            <td>${renderPriceWithEuro(item.cost_price, item.cost_price_euro, item.currency_code)}</td>
            <td>${renderPriceWithEuro(item.vat_amount, item.vat_amount_euro, item.currency_code)}</td>

            <td class="redeem-column-cell">${redeemedContent}</td>

            </tr>
          `);
          });

          // ✅ Initialize tooltips after rows are appended
          const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
          });
        } else {
          $tbody.append(`<tr><td colspan="12" class="text-center">No items found</td></tr>`);
        }
      },
      error: function () {
        $tbody.empty();
        $tbody.append(`<tr><td colspan="12" class="text-center text-danger">Failed to load items.</td></tr>`);
      }
    });
  });

  $(document).on('click', '.view-reason', function (e) {
    e.preventDefault();

    // 1️⃣ Get Base64 encoded string from data-reason
    let encodedReason = $(this).attr('data-reason'); // use .attr instead of .data to avoid jQuery auto-parsing
    let parsed;

    // 2️⃣ Decode and parse safely
    try {
      if (encodedReason) {
        const decoded = atob(encodedReason); // decode Base64
        parsed = JSON.parse(decoded); // parse JSON
      } else {
        parsed = {};
      }
    } catch (err) {
      console.error('Failed to parse reason:', err);
      parsed = encodedReason; // fallback
    }

    // 3️⃣ Convert object to nicely formatted string
    let reasonString = typeof parsed === 'object' ? JSON.stringify(parsed, null, 2) : String(parsed);

    // 4️⃣ Mask any sensitive fields (example: password)
    reasonString = reasonString.replace(/password=[^&\s"]+/gi, 'password=**********');

    // 5️⃣ Wrap in <pre> for nice display
    let formattedHtml = `
    <pre style="max-height:300px; overflow:auto; white-space: pre-wrap; word-wrap: break-word; font-family: monospace;">
${reasonString}
    </pre>
  `;

    // 6️⃣ Inject into modal and show
    $('#failedReasonContent').html(formattedHtml.trim());

    const reasonModal = new bootstrap.Modal(document.getElementById('failedReasonModal'));
    reasonModal.show();
  });

  $(document).on('click', '.redeem-key-btn', function () {
    const orderItemId = $(this).data('order-item-id');
    const $cell = $(this).closest('td');
    const $container = $('#redeemKeyContainer');

    const modal = new bootstrap.Modal(document.getElementById('redeemKeyModal'));

    Swal.fire({
      title: 'Redeem Key?',
      text: 'Are you sure you want to process this redemption?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Redeem!',
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-outline-secondary ms-2'
      },
      buttonsStyling: false
    }).then(result => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });

      $.ajax({
        url: baseUrl + 'orders/redeem-key/' + orderItemId,
        type: 'GET',
        success: function (res) {
          Swal.close();
          $container.empty();

          /* =======================
             ❌ ERROR RESPONSE
          ======================= */
          if (!res.success) {
            // 🔴 Header → Error
            $('#redeemModalHeader').removeClass('bg-success').addClass('bg-danger');

            $('#redeemModalIcon').removeClass('ri-shield-check-line').addClass('ri-error-warning-line');

            $('#redeemModalTitle').text('Redemption Failed');

            $container.html(`
      <div class="col-12">
        <div class="alert alert-danger mb-0">
          <i class="ri-error-warning-line me-1"></i>
          ${res.message}
        </div>
      </div>
    `);

            modal.show();
            return;
          }

          /* =======================
             ✅ SUCCESS RESPONSE
          ======================= */

          // 🟢 Header → Success
          $('#redeemModalHeader').removeClass('bg-danger').addClass('bg-success');

          $('#redeemModalIcon').removeClass('ri-error-warning-line').addClass('ri-shield-check-line');

          $('#redeemModalTitle').text('Redemption Successful');

          const keyId = res.details.key_id; // always show first
          const rawString = res.details.Licensekey;
          let html = '';

          // 1️⃣ First show Key ID
          html += `
    <div class="col-12">
      <label class="form-label text-muted small fw-bold text-uppercase mb-1">Key ID</label>
      <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded border">
        <span class="fw-semibold text-dark text-break me-2">${keyId}</span>
        <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard="${keyId}">
          <i class="menu-icon icon-base ri ri-file-copy-line"></i>
        </button>
      </div>
    </div>
  `;

          // 2️⃣ Then show Licensekey(s)
          if (rawString.includes(', ')) {
            // multiple values → split by comma and show each
            const parts = rawString.split(', ');

            parts.forEach(part => {
              let [label, ...val] = part.split(': ');
              let value = val.join(': ').trim();
              if (!value) return;

              html += `
        <div class="col-12">
          <label class="form-label text-muted small fw-bold text-uppercase mb-1">${label}</label>
          <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded border">
            <span class="fw-semibold text-dark text-break me-2">${value}</span>
            <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard="${value}">
              <i class="menu-icon icon-base ri ri-file-copy-line"></i>
            </button>
          </div>
        </div>
      `;
            });
          } else {
            // single value → just show Licensekey
            html += `
      <div class="col-12">
        <label class="form-label text-muted small fw-bold text-uppercase mb-1">Licensekey</label>
        <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded border">
          <span class="fw-semibold text-dark text-break me-2">${rawString}</span>
          <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard="${rawString}">
            <i class="menu-icon icon-base ri ri-file-copy-line"></i>
          </button>
        </div>
      </div>
    `;
          }

          $container.html(html);

          // Optional: update table cell
          // $cell.html(`<span class="badge bg-success">Redeemed</span>`);

          modal.show();
        },

        error: function () {
          Swal.close();
          Swal.fire('Error', 'Something went wrong', 'error');
        }
      });
    });
  });

  $(document).on('click', '.copy-btn', function () {
    const text = $(this).data('clipboard');
    navigator.clipboard.writeText(text);

    const $icon = $(this).find('i');
    $icon.removeClass('ri-file-copy-line').addClass('ri-check-line text-success');

    setTimeout(() => {
      $icon.removeClass('ri-check-line text-success').addClass('ri-file-copy-line');
    }, 1500);
  });
});
