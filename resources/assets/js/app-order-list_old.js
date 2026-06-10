/**
 * Page User List
 */

'use strict';




$(document).ready(function () {
  // var dateStr = '2022-09-23';

  var today = moment().format('MM/DD/YYYY'); // Get today's date in MM/DD/YYYY format

  // Set the hidden input fields with today's date
  $('.start_date').val(today);
  $('.end_date').val(today);

  // Initialize Flatpickr with today's date as default
  $('.flatpickr-range').flatpickr({
    mode: 'range',
    dateFormat: 'm/d/Y',
    defaultDate: [today, today], // Set default range to today
    onClose: function (selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        var startDate = moment(selectedDates[0]).format('MM/DD/YYYY');
        var endDate = moment(selectedDates[1]).format('MM/DD/YYYY');

        $('.start_date').val(startDate);
        $('.end_date').val(endDate);
      }
    }
  });
});


$(document).ready(function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});

// Reinitialize tooltips after DataTable update
$('.datatables-users').on('draw.dt', function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});






// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {



  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_user_table = document.querySelector('.datatables-users'),
    userView = baseUrl + 'app/user/view/account';
  var statusObj = {
    'PENDING': { text: 'Pending', class: 'bg-label-warning' },
    'PROCESSING': { text: 'Processing', class: 'bg-label-info' },
    'COMPLETED': { text: 'Completed', class: 'bg-label-success' },
    'PARTIALLY_COMPLETED': { text: 'Partially Completed', class: 'bg-label-primary' },
    'FAILED': { text: 'Failed', class: 'bg-label-danger' }
  };


  var select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    select2Focus($this);
    $this.select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }
  const buttons = [];

  let dt_user_filter;
  // Users datatable
  if (dt_user_table) {
    const dt_user_filter = new DataTable(dt_user_table, {
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
        { data: 'order_id_2game' },
        { data: 'product' },
        { data: 'status' },
        { data: 'total_qty' },
        { data: 'total_amount_paid' },
        { data: 'cost_price' },
        { data: 'cost_price_euro' },
        { data: 'payment_fee' },
        { data: 'vat_amount' },
        { data: 'profit' },

        { data: 'source' },
        { data: 'updated_at' },

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
          targets: 1, // order_id column
          orderable: true,
          render: function(data, type, full, meta) {
            var order_id_2game = full['order_id_2game'] ?? '';

            // Escape quotes for tooltip
            var escapedOrderId = order_id_2game.replace(/'/g, '&#39;').replace(/"/g, '&quot;');

            // Show first 10 characters
            var displayValue = order_id_2game.length > 14
              ? order_id_2game.substring(0, 14) + '...'
              : order_id_2game;

            return `
      <a href="#"
         class="order-details-link"
         data-order-id="${full.id}"
    >
        ${displayValue}
      </a>`;
          }
        },


        //     {
        //       targets: 2,
        //       orderable: false,
        //       render: function (data, type, full, meta) {
        //         var $products = full['products'] ?? '';
        //         var $product = full['product'] ?? '';
        //         var badgeCount = full['total_product'] ?? 0;
        //         var escapedTitle = $products.replace(/'/g, '&#39;');
        //
        //         return `<span class="text-nowrap d-inline-flex position-relative me-4">
        //   <span class="tooltip-trigger" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom" title="${escapedTitle}">
        //     ${$product}
        //   </span>
        //   ${badgeCount > 1 ? `
        //     <span class="position-absolute top-0 start-100 translate-middle badge badge-center rounded-pill bg-light text-black ms-3">
        //       ${badgeCount - 1}
        //     </span>` : ''}
        // </span>`;
        //       }
        //     },
        {
          targets: 2,
          orderable: false,
          render: function (data, type, full, meta) {
            var $products = full['products'] ?? '';
            var $product = full['product'] ?? '';
            var badgeCount = full['total_product'] ?? 0;
            var escapedTitle = $products.replace(/'/g, '&#39;');

            return `
      <span class="text-nowrap d-inline-flex position-relative me-4">
        <span class="tooltip-trigger"
              data-bs-toggle="tooltip"
              data-bs-html="true"
              data-bs-placement="bottom"
              title="${escapedTitle}"
              style="display:inline-block; width:200px; white-space:normal; word-wrap:break-word; overflow:hidden; text-overflow:ellipsis;">
          ${$product}
        </span>
        ${badgeCount > 1 ? `
          <span class="position-absolute top-0 start-100 translate-middle badge badge-center rounded-pill bg-light text-black ms-3">
            ${badgeCount - 1}
          </span>` : ''}
      </span>
    `;
          }
        },


        {
          targets: 3,
          orderable: false,
          render: function (data, type, full, meta) {
            const statuses = full['item_status'] ?? {}; // associative array like { CANCEL: 3, DELIVERED: 1 }

            const badges = Object.entries(statuses).map(([status, count]) => {
              const statusClass = statusObj[status]?.class || 'bg-label-warning';
              // const statusText = statusObj[status]?.text || status;
              const statusText =  status;

              return `
        <span class="text-nowrap d-inline-flex position-relative me-1">
          <span class="badge badge-sm ${statusClass} text-capitalize px-2 py-1 mb-sm-1">
            ${statusText}
          </span>
          ${count > 0 ? `
            <span class="position-absolute top-0 start-100 translate-middle badge badge-center rounded-pill bg-light text-black">
              ${count}
            </span>` : ''}
        </span>
      `;
            });

            return badges.length ? badges.join('') : '';
          }
        },

        {
          targets: [4,5,6,7,8,9,10,12],
          orderable: false
        },
        {
          targets: 11, // source column index
          searchable: false,
          orderable: false,
          render: function(data, type, full, meta) {
            const sources = full.source || []; // expecting array like [1,2]
            if (!Array.isArray(sources) || sources.length === 0) {
              return '';
            }

            return sources.map(source => {
              let badgeClass = 'badge rounded-pill bg-label-secondary'; // default gray
              let label = source;

              if (source === 1) {
                badgeClass = 'badge rounded-pill bg-label-primary'; // blue
                label = 'Ztorm';
              } else if (source === 2) {
                badgeClass = 'badge rounded-pill bg-label-info'; // yellow/orange
                label = 'Incomm';
              }

              return `<span class="${badgeClass} d-block mb-1">${label}</span>`;
            }).join('');
          }
        }


      ],
      order: [],

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

        let defaultDate = $('.flatpickr-range').val().trim();

        if (defaultDate) {
          api.column(12).search(defaultDate).draw();
        }


      },
      // drawCallback: function (settings) {
      //   var api = this.api();
      //   var json = api.ajax.json();
      //
      //   // Remove old totals block
      //   $('#total-info-block').remove();
      //
      //   // Add new totals block if totals exist
      //   if (json?.totals) {
      //     let total_price = json.totals.price ?? 0;
      //     let costPrice = json.totals.costPrice ?? 0;
      //     let costPriceEuro = json.totals.costPriceEuro ?? 0;
      //     let paymentFeeTotal = json.totals.paymentFeeTotal ?? 0;
      //     let vat_amount = json.totals.vat_amount ?? 0;
      //     let profit = json.totals.profit ?? 0;
      //     let total_qty = json.totals.qty ?? 0;
      //
      //     // Append totals to DataTable header
      //     $('#DataTables_Table_0_wrapper > div:first-child .dt-layout-end').append(`
      //       <div class="d-flex align-items-center gap-3 ms-3" id="total-info-block">
      //
      //           <span class="badge badge-sm bg-secondary text-white px-3 py-2 shadow-sm">
      //               <strong>Qty:</strong> ${total_qty}
      //           </span>
      //           <span class="badge badge-sm bg-secondary px-3 py-2 shadow-sm">
      //               <strong>Sales :</strong> ${total_price.toLocaleString()}
      //           </span>
      //            <span class="badge badge-sm bg-secondary px-3 py-2 shadow-sm">
      //               <strong>Cost :</strong> ${costPrice.toLocaleString()}
      //           </span>
      //               <span class="badge badge-sm bg-secondary px-3 py-2 shadow-sm">
      //               <strong>Cost (Euro) :</strong> ${costPriceEuro.toLocaleString()}
      //           </span>
      //            </span>
      //               <span class="badge badge-sm bg-secondary px-3 py-2 shadow-sm">
      //               <strong>Payment fee :</strong> ${paymentFeeTotal.toLocaleString()}
      //           </span>
      //            <span class="badge badge-sm bg-secondary px-3 py-2 shadow-sm">
      //               <strong>Vat :</strong> ${vat_amount.toLocaleString()}
      //           </span>
      //           <span class="badge badge-sm bg-secondary text-white px-3 py-2 shadow-sm">
      //               <strong>Profit:</strong> ${profit}
      //           </span>
      //       </div>
      //   `);
      //   }
      //
      //   // Re-initialize tooltips in case they are inside the table
      //   $('[data-bs-toggle="tooltip"]').tooltip();
      // },
      drawCallback: function (settings) {
        var api = this.api();
        var json = api.ajax.json();

        // Remove old totals block
        $('#total-info-block').remove();

        // Add new totals block if totals exist
        if (json?.totals) {
          // Sanitize and default values
          const getFormattedValue = (key) => {
            const value = json.totals[key] ?? 0;
            // Ensure to use the formatted number from the server or format it here if needed
            // Since the server side formats 'price' and 'costPrice', we'll assume they are strings
            // If they are not formatted, you should use: Number(value).toLocaleString('en-US', { minimumFractionDigits: 2 })
            return value;
          };

          let total_price = getFormattedValue('price');
          let costPrice = getFormattedValue('costPrice');
          let costPriceEuro = getFormattedValue('costPriceEuro');
          // Note: The paymentFeeTotal, vat_amount, and profit keys were not in your PHP response
          // If your server-side code is updated to include them, they will work.
          let paymentFeeTotal = getFormattedValue('paymentFeeTotal');
          let vat_amount = getFormattedValue('vat_amount');
          let profit = getFormattedValue('profit');
          let total_qty = getFormattedValue('qty');

          // Append totals to DataTable header (using Bootstrap/standard classes for spacing and flex)
          $('#DataTables_Table_0_wrapper > div:first-child .dt-layout-end').append(`
      <div class="d-flex flex-wrap align-items-center gap-2 ms-3" id="total-info-block" style="font-size: 0.85rem;">

        <div class="badge bg-secondary px-2 py-1 shadow-sm">
              <strong class="me-1">Qty:</strong> ${total_qty.toLocaleString()}
          </div>

          <div class="badge bg-secondary px-2 py-1 shadow-sm">
              <strong class="me-1">Sales:</strong> ${total_price}
          </div>

          <div class="badge bg-secondary px-2 py-1 shadow-sm" data-bs-toggle="tooltip" title="Total Cost Price">
              <strong class="me-1">Cost:</strong> ${costPrice}
          </div>

          <div class="badge bg-secondary px-2 py-1 shadow-sm" data-bs-toggle="tooltip" title="Total Cost Price (EUR)">
              <strong class="me-1">Cost (EUR):</strong> ${costPriceEuro}
          </div>
           <div class="badge bg-secondary px-2 py-1 shadow-sm" data-bs-toggle="tooltip" title="Total Payment fee">
              <strong class="me-1">Payment fee:</strong> ${paymentFeeTotal}
          </div>
           <div class="badge bg-secondary px-2 py-1 shadow-sm" data-bs-toggle="tooltip" title="Total vat">
              <strong class="me-1">Vat:</strong> ${vat_amount}
          </div>

          <div class="badge bg-secondary px-2 py-1 shadow-sm" data-bs-toggle="tooltip" title="Total Profit">
              <strong class="me-1">Profit:</strong> ${profit}
          </div>

      </div>
    `);
          // Note: I removed paymentFeeTotal and vat_amount to keep it condensed,
          // but you can add them back using the same structure.
        }

        // Re-initialize tooltips in case they are inside the table
        $('[data-bs-toggle="tooltip"]').tooltip();
      },

    });





    if (window.Helpers.isNavbarFixed()) {
      var navHeight = $('#layout-navbar').outerHeight();
      var fixedHeader = new $.fn.dataTable.FixedHeader(dt_user_table, {
        headerOffset: navHeight
      });

      // Apply background color when FixedHeader is enabled
      $('.fixedHeader-floating thead').css({
        'background-color': 'black !important', // Change to your desired color
        'box-shadow': '0px 2px 5px rgba(0, 0, 0, 0.1)', // Optional shadow for better visibility
        'z-index': '1050' // Ensure it stays above other elements
      });
    } else {
      new $.fn.dataTable.FixedHeader(dt_user_table);
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



  $(document).on('click', '.order-details-link', function(e) {
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
      success: function(response) {
        $tbody.empty();

        if (response.items && response.items.length) {
          document.getElementById('order_id').textContent = response.order.order_id_2game || '';

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


            $tbody.append(`
            <tr>

           <td>
    <div class="d-flex flex-column">
      <small class="text-muted">SKU: ${item.product_id || ''}</small>
      <span class="fw-semibold">${item.product_name || ''}</span>
    </div>
  </td>
              <td>${item.qty || 0}</td>
              <td>${item.price ? parseFloat(item.price).toFixed(2) : '0.00'}</td>
              <td>${item.currency_code || ''}</td>
              <td>${item.cost_price ? parseFloat(item.cost_price).toFixed(2) : '0.00'}</td>
              <td>${item.cost_price_euro ? parseFloat(item.cost_price_euro).toFixed(2) : '0.00'}</td>
              <td>${item.vat_amount ? parseFloat(item.vat_amount).toFixed(2) : '0.00'}</td>



              <td>${statusHtml}</td>
              <td>${item.redeemed_at || ''}</td>
              <td>${item.source || ''}</td>
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
      error: function() {
        $tbody.empty();
        $tbody.append(`<tr><td colspan="12" class="text-center text-danger">Failed to load items.</td></tr>`);
      }
    });
  });


// Event delegation (since rows are dynamic)
// Recursive formatter for nested JSON
  function formatReason(obj, level = 0) {
    let indent = level * 20; // indent nested levels
    let html = "<div class='nested-reason'>";

    for (const [key, value] of Object.entries(obj)) {
      if (typeof value === "object" && value !== null) {
        html += `
        <div style="margin-left:${indent}px; font-weight:bold;">${key}:</div>
        <div style="margin-left:${indent + 20}px; border-left:2px solid #ddd; padding-left:10px; margin-bottom:5px;">
          ${formatReason(value, level + 1)}
        </div>
      `;
      } else {
        const isError = key.toLowerCase().includes("error") || key.toLowerCase().includes("message");
        html += `
        <div style="margin-left:${indent}px;">
          <span style="font-weight:bold;">${key}:</span>
          <span style="${isError ? 'color:red;' : ''}">${value}</span>
        </div>
      `;
      }
    }

    html += "</div>";
    return html;
  }

  $(document).on('click', '.view-reason', function(e) {
    e.preventDefault();

    let reasonData = $(this).data('reason');
    let parsed;

    // Try to parse safely
    try {
      if (typeof reasonData === "string") {
        parsed = JSON.parse(reasonData);
      } else {
        parsed = reasonData;
      }
    } catch (err) {
      parsed = reasonData; // fallback if invalid JSON
    }

    let formattedHtml;

    if (typeof parsed === "object" && parsed !== null) {
      formattedHtml = `<div style="max-height:300px; overflow:auto; font-family:monospace;">${formatReason(parsed)}</div>`;
    } else {
      formattedHtml = `<pre style="max-height:300px; overflow:auto; white-space: pre-wrap; word-wrap: break-word;">${parsed}</pre>`;
    }

    $('#failedReasonContent').html(formattedHtml);

    var reasonModal = new bootstrap.Modal(document.getElementById('failedReasonModal'));
    reasonModal.show();
  });




  $(function () {
    var dt_order_filter_table = $('.datatables-users');

    var rangePickr = $('.flatpickr-range'),
      dateFormat = 'MM/DD/YYYY';

    let prevStartDate = '';
    let prevEndDate = '';

// Debounce function to prevent multiple calls
    function debounce(func, delay) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
      };
    }

// Function to apply the filter only if values changed
    function applyFilter() {
      let startDate = $('.start_date').val().trim();
      let endDate = $('.end_date').val().trim();

      // Prevent calling if dates didn't change
      if (startDate === prevStartDate && endDate === prevEndDate) {
        console.log("No date change detected, skipping filter...");
        return;
      }

      // Update previous values
      prevStartDate = startDate;
      prevEndDate = endDate;


      if (startDate !== "" && endDate !== "") {
        filterColumn($('.flatpickr-range').attr('data-column'), startDate + ' to ' + endDate);
      } else {
        filterColumn($('.flatpickr-range').attr('data-column'), ""); // Reset filter when empty
      }
    }

// Debounced version of applyFilter
    const debouncedApplyFilter = debounce(applyFilter, 300);

    if (rangePickr.length) {
      rangePickr.flatpickr({
        mode: 'range',
        dateFormat: 'm/d/Y',
        orientation: isRtl ? 'auto right' : 'auto left',
        locale: {
          format: dateFormat
        },
        onClose: function (selectedDates, dateStr, instance) {
          let startDate = '',
            endDate = '';

          if (selectedDates.length > 0) {
            startDate = moment(selectedDates[0]).format('MM/DD/YYYY');
            $('.start_date').val(startDate);
          } else {
            $('.start_date').val('');
          }

          if (selectedDates.length > 1) {
            endDate = moment(selectedDates[1]).format('MM/DD/YYYY');
            $('.end_date').val(endDate);
          } else {
            $('.end_date').val('');
          }

          debouncedApplyFilter(); // Call filter only if values changed
        }
      });
    }

// Attach the debounced filter call to change events
    $('.start_date, .end_date').on('change', debouncedApplyFilter);


    $('input.dt-input').on('keyup', function () {
      let column = $(this).attr('data-column');
      let value = $(this).val().trim();

      // If the input has the class 'flatpickr-range'
      if ($(this).hasClass('flatpickr-range')) {
        // let startDate = $('.start_date').val().trim();
        // let endDate = $('.end_date').val().trim();
        //
        // // Only call filterColumn if both start_date and end_date are not empty
        // if (startDate !== "" && endDate !== "") {
        //   filterColumn($(this).attr('data-column'), $(this).val());
        // }
      } else {
        // Call filterColumn normally for other inputs
        filterColumn($(this).attr('data-column'), $(this).val());
      }
    });


    $('select.dt-input').on('change', function () {
      filterColumn($(this).attr('data-column'), $(this).val());
    });

    function filterColumn(i, val) {
      dt_order_filter_table.DataTable().column(i).search(val, false, true).draw();
    }


  });

});


