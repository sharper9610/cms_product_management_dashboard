/**
 * Page User List
 */

'use strict';
// Add a new media block


let discountFromPicker;
let discountToPicker;

document.addEventListener('DOMContentLoaded', function () {

  const options = {
    enableTime: true,
    enableSeconds: true,
    time_24hr: true,
    allowInput: false,
    disableMobile: true,
    static: true,
    dateFormat: "Y-m-d H:i:S",

  };

  discountFromPicker = flatpickr("#modal_discount_valid_from", options);
  discountToPicker   = flatpickr("#modal_discount_valid_to", options);
});


// Add new price row for Source 2
$('#addPrice').on('click', function () {
  const $priceContainer = $('#priceContainer');
  const $table = $priceContainer.find('table');

  // If table doesn't exist yet, create it
  if ($table.length === 0) {
    const tableHtml = `
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                         <th style="width:150px">Price</th>
                        <th>Discount (%)</th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;
    $priceContainer.html(tableHtml);
  }

  const $tbody = $priceContainer.find('tbody');

  // Generate a unique ID for this new price (can be used in Save/Delete)
  const newPriceId = 'new_' + Date.now();

  const rowHtml = `
        <tr data-price-id="${newPriceId}" class="price-row">
           <td class="align-middle ">
  <div class="d-flex flex-column">
    <span><strong>Country:</strong> BR</span>
    <span class="text-muted small mt-1"><strong>Currency:</strong> BRL</span>
  </div>
</td>
    <td class="align-middle">
                <div class="d-flex align-items-center gap-2">
                    <input type="text"  class="form-control form-control-sm  price-title"
                        data-price-id="${newPriceId}" placeholder="Title">

                </div>
            </td>

            <td class="align-middle">
                <div class="d-flex align-items-center gap-2">
                    <input type="number" step="0.01" class="form-control form-control-sm price-input price-value"
                        data-price-id="${newPriceId}" placeholder="0.00">

                </div>
            </td>
            <td class="align-middle"></td>
            <td class="align-middle"></td>
            <td class="align-middle text-center">
             <button type="button" class="btn btn-primary btn-sm new-save-price-btn"
                        data-price-id="${newPriceId}" title="Save">
                        <i class="ri ri-edit-line me-1"></i> Save
                    </button>
                <button type="button" class="btn btn-outline-danger btn-sm new-delete-price-btn"
                    data-price-id="${newPriceId}" title="Delete">
                    <i class="ri ri-delete-bin-6-line me-1"></i>
                </button>
            </td>
        </tr>
    `;

  $tbody.append(rowHtml);
});





// Delete dynamically added price row
$(document).on('click', '.new-delete-price-btn', function () {
  const $row = $(this).closest('tr'); // Get the parent row
  $row.remove(); // Remove the row from the DOM
});


$(document).on('click', '.new-save-price-btn', function (e) {
  e.preventDefault();

  const $btn = $(this);
  const $row = $btn.closest('tr');
  const $priceInput = $row.find('.price-input');
  const $titleInput = $row.find('.price-title');
  let enteredPrice = $priceInput.val().trim();
  let enteredTitle = $titleInput.val().trim();

  // Validate price
  if (!/^\d+(\.\d{1,2})?$/.test(enteredPrice)) {
    showToast('Please enter a valid price with up to 2 decimal places.', 'Error!', 'bg-warning');
    return;
  }

  enteredPrice = parseFloat(enteredPrice);

  const data = {
    tab_name: 'price-create',
    price: enteredPrice.toFixed(2),
    title:enteredTitle,
  };

  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Save it!',
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        type: 'POST',
        url: `/product-management/${$('#product_id_get').val()}`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'X-HTTP-Method-Override': 'PUT'
        },
        data: data,
        beforeSend: function () {
          if (window.showLoader) window.showLoader();
        },
        success: function (response) {
          if (response?.success) {
            showToast(response?.message ?? 'Price updated successfully!', 'Success', 'bg-success');
            if (window.loadProductData) window.loadProductData();
          } else {
            showToast(response?.message ?? 'Failed to update price!', 'Error!', 'bg-warning');
          }
        },
        error: function (xhr) {
          showToast('Failed to update price.', 'Error!', 'bg-danger');
        },
        complete: function () {
          if (window.hideLoader) window.hideLoader();
        }
      });
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const aiResponseContent = document.querySelector('#modal_platforms');
  if (aiResponseContent) {
    new Tagify(aiResponseContent);
  }
});
$(document).on('click', '.edit-price-btn', function(e) {
  e.preventDefault();

  const priceId = $(this).data('price-id');

  $.ajax({
    type: 'POST',
    url: `/product-management/${$('#product_id_get').val()}`,
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      "X-HTTP-Method-Override": "PUT"
    },
    data: {
      tab_name: "get-price",
      price_id: priceId
    },
    beforeSend: function() {
      $("#modal_loader").show();
    },
    success: function(response) {

      if (!response.success) {
        showToast(response.message ?? "Failed to fetch price!", "Error!", "bg-warning");
        return;
      }

      const price = response.price;

      // Fill fields
      $("#modal_price_id").val(priceId);
      $("#modal_title").val(price.title ?? "");
      $("#modal_price").val(price.price ?? "");
      $("#modal_discount_percent").val(price.discount_percent ?? "");
      $("#modal_concept_id").val(price.concept_id ?? "");
      $("#modal_scrape_url").val(price.scrape_url ?? "");
      $("#modal_description").val(price.description ?? "");
      $("#modal_platforms").val(price.platforms ?? "");


      // Set discount dates (formatted already: "2025-11-25 14:54")
      if (price.discount_valid_from_formatted) {
        discountFromPicker.setDate(
          price.discount_valid_from_formatted,
          true, // trigger change
          "Y-m-d H:i"
        );
      } else {
        discountFromPicker.clear();
      }

      if (price.discount_valid_to_formatted) {
        discountToPicker.setDate(
          price.discount_valid_to_formatted,
          true,
          "Y-m-d H:i"
        );
      } else {
        discountToPicker.clear();
      }




      // Primary Image Preview
      if (price.primary_image_url) {
        $("#modal_image_preview").attr("src", price.primary_image_url).show();
      } else {
        $("#modal_image_preview").hide();
      }

      $("#modal_error").text("");

      // Show modal
      new bootstrap.Modal(document.getElementById("priceEditModal"), {
        backdrop: "static",
        keyboard: false
      }).show();
    },
    complete: function() {
      $("#modal_loader").hide();
    },
    error: function() {
      showToast("Failed to fetch price!", "Error!", "bg-danger");
    }
  });
});

// Image live preview
$('#modal_image_upload').on('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;

  // Only allow image files
  if (!file.type.startsWith('image/')) {
    showToast('Please select a valid image', 'Error', 'text-warning');
    $(this).val('');
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    $('#modal_image_preview')
      .attr('src', e.target.result)
      .show();
  }
  reader.readAsDataURL(file);
});



$('#saveCommission').on('click', function () {
  const commission = $('#merchantCommission').val();
  const productId = $(this).data('product-id');

  // Basic validation
  if (commission === '' || commission < 0 || commission > 100) {
    showToast('Commission must be between 0 and 100', 'Error', 'text-warning');
    return;
  }

  Swal.fire({
    title: 'Confirm Update',
    text: `Set merchant commission to ${commission}%?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Save',
    cancelButtonText: 'Cancel',
    customClass: {
      confirmButton: 'btn btn-primary me-2',
      cancelButton: 'btn btn-outline-secondary'
    },
    buttonsStyling: false
  }).then((result) => {

    // ❌ User cancelled
    if (!result.isConfirmed) return;

    // ✅ User confirmed → AJAX request
    $.ajax({
      url: `/product-management/${$('#product_id_get').val()}`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      type: 'POST',
      data: {
        tab_name: 'commission_update',
        product_id: productId,
        merchant_commission_percentage: commission
      },
      beforeSend: function () {
        $('#saveCommission').prop('disabled', true);
      },
      success: function (response) {

        if (response.success) {
          showToast(response.message ?? 'Commission saved successfully', 'Success', 'text-success');

          // Reload product data after successful update
          if (window.loadProductData) window.loadProductData();
        } else {
          showToast(response.message || 'Something went wrong', 'Error', 'text-warning');
        }
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          showToast('Validation error', 'Error', 'text-warning');
        } else {
          showToast('Something went wrong. Please try again.', 'Error', 'text-warning');
        }
      },
      complete: function () {
        $('#saveCommission').prop('disabled', false);
      }
    });

  });
});




// AJAX Submit
$(document).on('click', '#modal_save_btn', function(e) {
  e.preventDefault();

  const form = document.getElementById('modal_price_form');
  const formData = new FormData(form);



  $.ajax({
    url: `/product-management/${$('#product_id_get').val()}`,
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'X-HTTP-Method-Override': 'PUT'
    },
    type: 'POST',
    data: formData,
    processData: false,  // <--- IMPORTANT for FormData
    contentType: false,
    beforeSend: function() { $('#modal_loader').show(); },
    complete: function() { $('#modal_loader').hide(); },
    success: function(response) {
      if (response.success) {
        showToast(response.message || 'Price updated successfully', 'Success', 'text-success');
        $('#priceEditModal').modal('hide');
        // Optional: reload product data
        window.loadProductData && window.loadProductData();
      } else {
        $('#modal_error').text(response.message || 'Failed to update price');
      }
    },
    error: function(xhr) {
      $('#modal_error').text('Something went wrong, please try again');
    }
  });


});

// Delegate Delete button click event for dynamically generated rows
$(document).on('click', '.delete-price-btn', function (e) {
  e.preventDefault();

  const $btn = $(this);
  const $row = $btn.closest('tr');
  const priceId = $btn.data('price-id');

  const data = {
    tab_name: 'price_delete',
    price_id: priceId
  };

  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Delete it!',
    customClass: {
      confirmButton: 'btn btn-danger me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        type: 'POST',
        url: `/product-management/${$('#product_id_get').val()}`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'X-HTTP-Method-Override': 'PUT'
        },
        data: data,
        beforeSend: function () {
          if (window.showLoader) window.showLoader();
        },
        success: function (response) {
          if (response?.success) {
            showToast(response?.message ?? 'Price deleted successfully!', 'Success', 'bg-success');
            // Remove row from table if needed
            $row.remove();
            if (window.loadProductData) window.loadProductData();
          } else {
            showToast(response?.message ?? 'Failed to delete price!', 'Error!', 'bg-warning');
          }
        },
        error: function (xhr) {
          showToast('Failed to delete price.', 'Error!', 'bg-danger');
        },
        complete: function () {
          if (window.hideLoader) window.hideLoader();
        }
      });
    }
  });
});



// Handle click on submit button
$(document).off('click', '.steam-price-submit').on('click', '.steam-price-submit', function() {
  const priceId = $(this).data('price-id');
  const $input = $(this).siblings('.steam-price-input'); // input is sibling in input-group
  const oldPrice = parseFloat($input.data('price')) || 0;
  const newPrice = parseFloat($input.val()) || 0;

  if (oldPrice === newPrice) {
    Swal.fire({
      icon: 'info',
      title: 'No change',
      text: 'Steam Price is the same as before.',
    });
    return;
  }

  Swal.fire({
    title: 'Are you sure?',
    text: `Do you want to update Steam Price from ${oldPrice} to ${newPrice}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, update it!',
    cancelButtonText: 'Cancel',
  }).then((result) => {
    if (result.isConfirmed) {
      const data = {
        tab_name: 'steam-price-update',
        price_id: priceId,
        steam_price: newPrice,
      };

      $.ajax({
        type: 'POST',
        url: `/product-management/${$('#product_id_get').val()}`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'X-HTTP-Method-Override': 'PUT'
        },
        data: data,
        beforeSend: function () {
          if (window.showLoader) window.showLoader();
        },
        success: function (response) {
          if (response?.success) {
            showToast(response?.message ?? 'Steam Price updated successfully!', 'Success', 'bg-success');
            // Remove row from table if needed
            if (window.loadProductData) window.loadProductData();
          } else {
            showToast(response?.message ?? 'Failed to updated price!', 'Error!', 'bg-warning');
          }
        },
        error: function (xhr) {
          showToast('Failed to updated price.', 'Error!', 'bg-danger');
        },
        complete: function () {
          if (window.hideLoader) window.hideLoader();
        }
      });
    }
  });
});
