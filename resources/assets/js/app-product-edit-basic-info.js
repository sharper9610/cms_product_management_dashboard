/**
 * Page User List
 */

'use strict';


document.addEventListener('DOMContentLoaded', function () {
  const genres = document.querySelector('#genres');
  const developers = document.querySelector('#developers');
  const franchise = document.querySelector('#franchise');

  if (genres) {
    new Tagify(genres);
  }
  if (developers) {
    new Tagify(developers);
  }
  if (franchise) {
    new Tagify(franchise);
  }

  const select = document.getElementById('default_language');

  if (!select) return;

  // Loop through supportedLanguages object
  for (const [name, code] of Object.entries(window.allLanguages)) {
    const option = document.createElement('option');
    option.value = code;   // short code
    option.textContent = `${name} (${code})`; // Example: English (en)
    select.appendChild(option);
  }




});


document.addEventListener("DOMContentLoaded", function() {
  const flatpickrReleaseDate = document.querySelectorAll(".release_date_flatpickr");

  if (typeof flatpickr !== "undefined" && flatpickrReleaseDate.length > 0) {
    flatpickr(flatpickrReleaseDate, {
      dateFormat: "Y-m-d", // actual value stored in input
      altInput: true,      // visible input
      altFormat: "Y-m-d",  // format for visible input
      allowInput: false,
      enableTime: false
    });
  }

  const flatpickrOrderDate = document.querySelectorAll(".order_date_flatpickr");

  if (typeof flatpickr !== "undefined" && flatpickrOrderDate.length > 0) {
    flatpickr(flatpickrOrderDate, {
      dateFormat: "Y-m-d", // actual value stored in input
      altInput: true,      // visible input
      altFormat: "Y-m-d",  // format for visible input
      allowInput: false,
      enableTime: false
    });
  }





  // Global function to set release and download dates
  window.setProductDates = function({ release_date = '', download_date = '' }) {
    if (typeof flatpickr === "undefined") return;

    // Set release date
    document.querySelectorAll(".release_date_flatpickr").forEach(function(el) {
      if (el._flatpickr && release_date) {
        el._flatpickr.setDate(release_date, true); // true updates altInput
      }
    });

    // Set download date
    document.querySelectorAll(".order_date_flatpickr").forEach(function(el) {
      if (el._flatpickr && download_date) {
        el._flatpickr.setDate(download_date, true);
      }
    });
  };





});




$('#basic_info').on('submit', function(e) {
  e.preventDefault();
  let $form = $(this);

  let formData = new FormData(this);

  const localizations = [];
  $form.find('.localization-title-input').each(function() {
    const $input = $(this);
    localizations.push({
      id: $input.data('loc-id'),
      locale: $input.data('locale'),
      title: $input.val()
    });
  });

  localizations.forEach((loc, index) => {
    formData.append(`localizations[${index}][id]`, loc.id);
    formData.append(`localizations[${index}][locale]`, loc.locale);
    formData.append(`localizations[${index}][title]`, loc.title);
  });

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
    processData: false,
    contentType: false,
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
