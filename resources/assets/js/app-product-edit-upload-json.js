/**
 * Page User List
 */

'use strict';
// Add a new media block


let jsonDataList = [];

// Read & Preview JSON


let singleJsonData = null;   // replace jsonDataList





document.getElementById('previewBtn').addEventListener('click', function () {
  const file = document.getElementById('jsonFileInput').files[0];

  if (!file) {
    showToast('Select a JSON file first', 'Error', 'text-warning', 10000);
    return;
  }

  const requiredFields = [
    'product_id', 'region', 'source', 'concept_id', 'title','scrape_url',
    'description', 'platforms', 'primary_image_url', 'pricing'
  ];

  const reader = new FileReader();

  reader.onload = function (e) {
    try {
      const json = JSON.parse(e.target.result);

      // Validate required fields
      const missingFields = requiredFields.filter(f => !(f in json));
      if (missingFields.length > 0) {
        showToast(`Missing fields: ${missingFields.join(', ')}`, 'Error', 'text-warning', 10000);
        return;
      }

      // Validate pricing fields
      const pricingFields = ['currency', 'price_original', 'price_current', 'discount_percent', 'promo_active'];
      const missingPricingFields = pricingFields.filter(f => !(f in json.pricing));
      if (missingPricingFields.length > 0) {
        showToast(`Missing pricing fields: ${missingPricingFields.join(', ')}`, 'Error', 'text-warning', 10000);
        return;
      }

      // Show preview
      const previewArea = document.getElementById('jsonPreviewArea');
      previewArea.innerHTML = `
        <div class="alert alert-secondary p-3 mb-3">
          <strong>File:</strong> ${file.name}
          <pre>${JSON.stringify(json, null, 2)}</pre>
          <div id="preview-single" class="fw-bold text-primary">Checking database...</div>
        </div>
      `;

      singleJsonData = json;   // store ONE object only

      document.getElementById('previewCard').style.display = 'block';

      // Check DB
      $.ajax({
        url: `/product-management/${$('#product_id_get').val()}`,
        type: "POST",
        data: JSON.stringify({ item: json, tab_name: "check-json" }),
        contentType: "application/json",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
          "X-HTTP-Method-Override": "PUT"
        },
        beforeSend: function() { if (window.showLoader) window.showLoader(); },
        complete: function() { if (window.hideLoader) window.hideLoader(); },
        success: function(response) {
          const el = document.getElementById('preview-single');
          const uploadBtn = document.getElementById('uploadBtn');

          if (response.concept_id_message) {
            el.innerHTML = `
      <div class="p-2 border rounded bg-warning">
        <strong class="text-dark">Notice:</strong>
        <p class="text-dark mb-0">${response.concept_id_message}</p>
      </div>
    `;
            uploadBtn.disabled = !response.exists; // disable if product doesn't exist
            return;
          }

          if (response.exists) {
            el.innerHTML = `
      <div class="p-2 border rounded bg-light">
        <strong>Existing Product ID:</strong>
        <span class="text-success fw-bold">${response.product_id}</span>
        <p class="mb-0">
          Product exists — you can now upload it.
        </p>
      </div>
    `;
            uploadBtn.disabled = false;
          } else {
            el.innerHTML = `
      <div class="p-2 border rounded bg-warning">
        <strong class="text-dark">Product Not Found:</strong>
        <span class="text-dark fw-bold">${response.product_id}</span>
        <p class="text-dark mb-0">
          This product does not exist in the database. Upload is disabled.
        </p>
      </div>
    `;
            uploadBtn.disabled = true;
          }
        },

        error: function () {
          document.getElementById('preview-single').innerHTML =
            "<span class='text-danger'>Error checking DB</span>";
          document.getElementById('uploadBtn').disabled = true;
        }
      });

    } catch (error) {
      showToast('Invalid JSON: ' + file.name, 'Error', 'text-warning', 10000);
    }
  };

  reader.readAsText(file);
});





$('#resetBtn').on('click', function() {
  // Clear file input
  $('#jsonFileInput').val('');

  // Hide preview card
  $('#previewCard').hide();

  // Clear preview area
  $('#jsonPreviewArea').html('');

  // Clear any JS data array (if used)
  jsonDataList = [];
});




// Upload final data
$('#uploadBtn').on('click', function () {
  $.ajax({
    url: `/product-management/${$('#product_id_get').val()}`,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      json_data: singleJsonData,
      tab_name: "upload-json"
    }),
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      "X-HTTP-Method-Override": "PUT"
    },
    beforeSend: function() {
      // Show loader using global function
      if (window.showLoader) window.showLoader();
    },
    complete: function() {
      // Hide loader using global function
      if (window.hideLoader) window.hideLoader();
    },
    success: function(response) {

      if (response.success) {
        showToast(response.message, 'Success', 'text-success');
        // Reload data to get updated IDs and URLs
        window.loadProductData && window.loadProductData();
      } else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);
      }

      // Optionally, you can reset file input and preview here
      $('#jsonFileInput').val('');
      $('#jsonPreviewArea').html('');
      $('#previewCard').hide();
      jsonDataList = [];
    },
    error: function(xhr, status, error) {
      console.error(error);
      showToast( 'Something went wrong', 'Error', 'text-warning', 10000);

    }
  });
});



