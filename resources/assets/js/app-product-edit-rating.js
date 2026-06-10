/**
 * Page User List
 */

'use strict';
// Add a new media block





$('#ratingForm').on('submit', function(e) {
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
    beforeSend: function() {
      // Show loader using global function
      if (window.showLoader) window.showLoader();
    },
    complete: function() {
      // Hide loader using global function
      if (window.hideLoader) window.hideLoader();
    },
    success: function(response) {
      if(response.success) {
        showToast(response.message, 'Success', 'text-success');

        // Reload product data after successful update
        if (window.loadProductData) window.loadProductData();

      } else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);
      }
    },
    error: function(xhr) {
      if (xhr.status === 422) {
        let errors = xhr.responseJSON.errors;

        $.each(errors, function(key, messages) {
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

$('#generateRatingBtn').on('click', function () {
  let btn = $(this);

  Swal.fire({
    title: 'Are you sure?',
    text: "Do you want to generate  for this product?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, Generate!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      const data = {
        tab_name: 'generate-rating',
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
          btn.prop('disabled', true).text('Generating...');
          if (window.showLoader) window.showLoader();
        },
        success: function (response) {
          if (response?.success) {
            // fill the rating fields if returned from API

            showToast(response?.message ?? 'generated successfully!', 'Success', 'bg-success');
          } else {
            showToast(response?.message ?? 'Failed to generate rating!', 'Error!', 'bg-warning');
          }

          if (window.loadProductData) window.loadProductData();
        },
        error: function (xhr) {
          showToast("Error generating . Please try again.", 'Error!', 'bg-warning');
        },
        complete: function () {
          if (window.hideLoader) window.hideLoader();
          btn.prop('disabled', false).text('Generate PPI');
        }
      });
    }
  });
});

