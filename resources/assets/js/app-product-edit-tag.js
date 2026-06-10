/**
 * Page User List
 */

'use strict';
// Add a new media block
document.addEventListener('DOMContentLoaded', function () {
  const tags = document.querySelector('#tags');
  if (tags) {
    new Tagify(tags);
  }
})
document.addEventListener('DOMContentLoaded', function () {
  const aiResponseContent = document.querySelector('#aiResponseContent');
  if (aiResponseContent) {
    new Tagify(aiResponseContent);
  }
});

$('#tagForm').on('submit', function (e) {
  e.preventDefault();
  let $form = $(this);
  let formData = $form.serialize();

  // Clear previous errors
  $form.find('.invalid-feedback').text('');
  $form.find('.form-control, .form-select, textarea').removeClass('is-invalid');

  $.ajax({
    type: 'POST',
    url: `/product-management/${$('#product_id_get').val()}`,
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'X-HTTP-Method-Override': 'PUT'
    },
    data: formData,
    beforeSend: function () {
      // Show loader using global function
      if (window.showLoader) window.showLoader();
    },
    complete: function () {
      // Hide loader using global function
      if (window.hideLoader) window.hideLoader();
    },
    success: function (response) {
      if (response.success) {
        showToast(response.message, 'Success', 'text-success');

        // Reload product data after successful update
        if (window.loadProductData) window.loadProductData();

      } else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);
      }
    },
    error: function (xhr) {
      if (xhr.status === 422) {
        let errors = xhr.responseJSON.errors;

        $.each(errors, function (key, messages) {
          // Handle array and non-array fields
          const match = key.match(/\.(\d+)$/);
          let $input;

          if (!match) {
            $input = $form.find(`[name="${key}"]`);
          } else {
            const index = parseInt(match[1]);
            const baseKey = key.substring(0, match.index);
            const nameAttribute = baseKey.replace(/\./g, '[') + '][]';
            $input = $form.find(`[name="${nameAttribute}"]`).eq(index);
          }

          if ($input.length) {
            $input.addClass('is-invalid');
            $input.closest('[class*="col-"]').find('.invalid-feedback').text(messages[0]);
          }
        });

      } else {
        showToast('An unexpected error occurred.', 'Error', 'text-warning', 10000);
      }
    }
  });
});




document.querySelectorAll(".generateTagsBtn").forEach(button => {
  button.addEventListener("click", () => {
    let modal = new bootstrap.Modal(document.getElementById("generateTagsModal"));
    modal.show();
  });

});



$(document).ready(function() {

  // Main function to fetch AI-generated content
  $("#fetchTagsBtn").on("click", function() {
    let promptId = $("#promptSelect").val();
    let $btn = $(this);
    let $promptSelect = $("#promptSelect");
    let $resultContainer = $("#generatedResultContainer");


    const $selectedOption = $promptSelect.find('option:selected');
    const selectedLang = $selectedOption.data('lang');



    $promptSelect.removeClass("is-invalid");

    if (!promptId) {
      $promptSelect.addClass("is-invalid");
      return;
    }

    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating...');

    // Loading state
    $resultContainer.html(`
      <div class="d-flex flex-column align-items-center justify-content-center h-100 p-3 border rounded bg-light" style="min-height: 250px;">
          <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Generating results, please wait...</p>
      </div>
    `);

    $.ajax({
      type: 'POST',
      url: `/product-management/${$('#product_id_get').val()}`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      data: {
        prompt: promptId,
        lang: selectedLang,
        tab_name: 'tag-suggest' // Or whatever your tab name is
      },
      success: function(response) {
        if (response.success && response.data) {
          const data = response.data;

          // Format cost for display
          const formattedCost = data.cost.toLocaleString('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 6
          });



          const editableUI = `
      <form id="aiTagsForm">
          <input type="hidden" name="lang" value="${data.lang}">
          <input type="hidden" name="field" value="${data.field}">
          <input type="hidden" name="tab_name" value="tag-suggest-save">

          <div class="card shadow-sm">
              <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                  <h5 class="card-title mb-0">
                      <i class="bi bi-pencil-square me-2 text-primary"></i>Review & Edit Content
                  </h5>
                  <button type="submit" class="btn btn-primary" id="saveTagsBtn">
                      <i class="bi bi-check-lg me-1"></i> Save Generated Tags
                  </button>
              </div>
              <div class="card-body bg-light pt-2">
                  <div class="mb-0">
                      <label for="aiResponseContent" class="form-label visually-hidden">AI Generated Content</label>
                      <textarea class="form-control" id="aiResponseContent" name="response_content" rows="8" style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit; font-size: 0.95rem;">${data.response_content}</textarea>
                  </div>
              </div>
          </div>
      </form>

      <div id="saveMessageContainer" class="mt-3"></div>

      <div class="accordion mt-3" id="detailsAccordion">
          <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                      Generation Details
                  </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#detailsAccordion">
                  <div class="accordion-body">
                      <strong class="text-muted">Prompt Sent:</strong>
                      <pre class="bg-white p-2 rounded small mt-1 mb-3"><code>${data.promptText}</code></pre>

                      <ul class="list-group list-group-flush">
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                              <div><i class="bi bi-cash-coin me-2 text-success"></i>Cost</div>
                              <span class="badge bg-success-subtle text-success-emphasis rounded-pill">${formattedCost}</span>
                          </li>
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                              <div><i class="bi bi-cpu me-2 text-primary"></i>Token Usage</div>
                              <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">
                              Total: ${data.usage.total_tokens} (Prompt: ${data.usage.prompt_tokens}, Completion: ${data.usage.completion_tokens})
                              </span>
                          </li>
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                              <div><i class="bi bi-robot me-2 text-info"></i>Model</div>
                              <span class="badge bg-info-subtle text-info-emphasis rounded-pill">${data.model}</span>
                          </li>
                      </ul>
                  </div>
              </div>
          </div>
      </div>
    `;

          $resultContainer.html(editableUI);

          const el = document.querySelector(`#aiResponseContent`);

          // Ensure the element was actually found before calling new Tagify
          if (el) {
            const tagify = new Tagify(el, {
              enforceWhitelist: false,
              dropdown: {
                enabled: 0 // no suggestions, free typing
              }
            });
          }


        } else {
          $resultContainer.html(`<div class="alert alert-danger">Error: ${response.message || 'Failed to generate content.'}</div>`);
        }
      },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An unexpected error occurred.';
        $resultContainer.html(`<div class="alert alert-danger">Error ${xhr.status}: ${errorMsg}</div>`);
      },
      complete: function() {
        $btn.prop("disabled", false).html("AI Generate Content");
      }
    });
  });



  // Separate function to handle saving the edited/tagified content
// Use $(document).on('event', 'selector', function) for dynamically created elements
  $(document).on('submit', '#aiTagsForm', function(e) {
    e.preventDefault(); // Prevent the default form submission

    let $form = $(this);
    let $btn = $form.find('#saveTagsBtn');
    let $messageContainer = $('#saveMessageContainer');

    // Get the product ID from the element outside the dynamic form
    const productId = $('#product_id_get').val();

    $messageContainer.empty(); // Clear previous messages

    // 1. Disable button and show spinner
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

    // 2. Prepare data (serialize() converts form inputs into a URL-encoded string)
    var formData = $form.serialize();
    formData += '&tab_name=saveTag';


    // 3. Make the AJAX call to save the data
    $.ajax({
      type: 'POST',
      url: `/product-management/${productId}`, // The target route for the save action
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), // Include CSRF token for POST/PUT requests
        'X-HTTP-Method-Override': 'PUT' // Use this if your Laravel/backend route is defined as a PUT method
      },
      success: function(response) {
        if(response.success) {

          // 1. Get the DOM element for the modal
          const modalElement = document.getElementById("generateTagsModal");

          // 2. Check if the element exists and create a Bootstrap Modal instance
          if (modalElement) {
            // Use the static method getOrCreateInstance to ensure a Modal object exists
            const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);

            // 3. Hide the modal
            modal.hide();
          }


          // ✅ Reset form fields
          $form.trigger('reset');

          // ✅ Reset dropdown to default
          $('#promptSelect').val('');

          // ✅ Reset generated result container to default design
          $('#generatedResultContainer').html(`
          <div class="d-flex align-items-center justify-content-center h-100 text-muted fst-italic p-3 border rounded bg-light" style="min-height: 250px;">
            Generated results will appear here.
          </div>
        `);


          showToast(response.message, 'Success', 'text-success');

          // Reload product data after successful update
          if (window.loadProductData) window.loadProductData();

        } else {
          showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);
        }
      },
      error: function(xhr) {
        showToast('An unexpected error occurred.', 'Error', 'text-warning', 10000);

      },
      complete: function() {
        // 4. Re-enable button
        $btn.prop("disabled", false).html('<i class="bi bi-check-lg me-1"></i> Save Generated Tags');


      }
    });
  });








  // --- Delegated event handler for the copy button ---
  // Attaches the listener to a static parent, so it works for dynamically added buttons.
  $("#generatedResultContainer").on("click", "#copyContentBtn", function() {
    let $copyBtn = $(this);
    // Use data-content attribute to get the text to copy
    const textToCopy = $copyBtn.data("content");

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(textToCopy).then(() => {
        // --- Provide user feedback ---
        const originalHtml = $copyBtn.html();
        $copyBtn.prop('disabled', true).html('<i class="bi bi-check-lg me-1"></i> Copied!');

        // Revert the button text after 2 seconds
        setTimeout(() => {
          $copyBtn.prop('disabled', false).html(originalHtml);
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy text: ', err);
        alert('Failed to copy text. Please try again.');
      });
    } else {
      // Fallback for older browsers
      let textArea = document.createElement("textarea");
      textArea.value = textToCopy;
      textArea.style.position = "fixed";
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand('copy');
        const originalHtml = $copyBtn.html();
        $copyBtn.prop('disabled', true).html('<i class="bi bi-check-lg me-1"></i> Copied!');
        setTimeout(() => {
          $copyBtn.prop('disabled', false).html(originalHtml);
        }, 2000);
      } catch (err) {
        console.error('Fallback copy failed: ', err);
        alert('Failed to copy text. Please copy manually.');
      }
      document.body.removeChild(textArea);
    }
  });

});
