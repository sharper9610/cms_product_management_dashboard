/**
 * Page User List
 */

'use strict';
// Add a new media block



document.getElementById('addLocalization').addEventListener('click', function() {
  let container = document.getElementById('localizationContainer');
  let template = document.getElementById('localizationTemplate').innerHTML;
  container.insertAdjacentHTML('beforeend', template);

  // --- REVISED ---
  // Initialize Quill on the editors inside THIS new block
  const newBlock = container.lastElementChild;
  newBlock.querySelectorAll('.editor').forEach(editor => {
    // Initialize with no content
    window.initializeQuillInBlock(editor, '');
    window.toggleTranslateButtons();
  });


});


document.addEventListener('click', function(e) {
  if(e.target.closest('.removeLocalization')){

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        let block = e.target.closest('.localization-block');
        let hiddenId = block.querySelector('input[name="localizations[id][]"]').value;

        // If it exists in DB, send AJAX delete immediately
        if(hiddenId) {
          $.ajax({
            url: `/localizations/${hiddenId}`, // your route for deleting
            beforeSend: function() {
              // Show loader using global function
              if (window.showLoader) window.showLoader();
            },
            complete: function() {
              // Hide loader using global function
              if (window.hideLoader) window.hideLoader();
              if (window.loadProductData) window.loadProductData();
            },
            type: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response){
              showToast(response.message, 'Success', 'text-success');
            },
            error: function(){
              showToast('Failed to delete localization', 'Error', 'text-warning');
            }
          });
        }

        // Remove the block from DOM
        block.remove();

        window.toggleTranslateButtons();
      }
    });


  }
});



$('#localizationForm').on('submit', function(e) {
  e.preventDefault();
  let $form = $(this);

  // --- CHECK FOR DUPLICATE LANGUAGE CODES ---
  let selectedLanguages = [];
  let hasDuplicate = false;

  $form.find('input[name="localizations[language_code][]"]').each(function() {
    const val = $(this).val();
    if (selectedLanguages.includes(val)) {
      hasDuplicate = true;
      $(this).addClass('is-invalid');
      $(this).closest('[class*="col-"]').find('.invalid-feedback').text('This language code is already selected.');
    } else {
      selectedLanguages.push(val);
      $(this).removeClass('is-invalid');
      $(this).closest('[class*="col-"]').find('.invalid-feedback').text('');
    }
  });

  if (hasDuplicate) {
    showToast('Please select unique language codes for each localization.', 'Error', 'text-warning', 5000);
    return; // Stop submission
  }

  // --- COPY QUILL CONTENT TO TEXTAREAS ---
  $form.find('.editor').each(function() {
    const quill = this.__quill;
    if (quill) {
      const targetName = $(this).data('name');
      const $textarea = $(this).siblings(`textarea[name="${targetName}"]`);
      if ($textarea.length) {
        $textarea.val(quill.root.innerHTML);
      }
    }
  });

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
    beforeSend: function() { if (window.showLoader) window.showLoader(); },
    complete: function() { if (window.hideLoader) window.hideLoader(); },
    success: function(response) {
      if(response.success) {
        showToast(response.message, 'Success', 'text-success');
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


// === Auto toggle when language changes ===
$(document).on('change', 'input[name="localizations[language_code][]"]', function () {
  window.toggleTranslateButtons();
});

// === Translate button click ===
$(document).on('click', '.translate-btn', function () {
  const $block = $(this).closest('.localization-block');
  const lang = ($block.find('input[name="localizations[language_code][]"]').val() || '')
    .trim()
    .toLowerCase();

  // if (!lang || lang === 'en' || lang.startsWith('en-') || lang.startsWith('en_')) {
  //   alert('Translation is not available for English.');
  //   return;
  // }

  $.ajax({
    url: '/translate', // <-- your route here
    type: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
      locale: lang,
      product_id:$('#product_id_get').val(),
    },
    beforeSend: function () {
      // optional: disable button while loading
      $block.find('.translate-btn').prop('disabled', true).text('Translating...');
      if (window.showLoader) window.showLoader();

    },
    success: function (response) {

      if(response.success) {
        showToast(response.message, 'Success', 'text-success');
        if (window.loadProductData) window.loadProductData();
      }
      else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);

      }

    },
    error: function (xhr) {
      let msg = 'Translation failed. Please try again.';

      // If JSON returned from Laravel
      if (xhr.responseJSON) {
        if (xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        } else if (xhr.responseJSON.Response?.ErrorMsg) {
          msg = xhr.responseJSON.Response.ErrorMsg;
        }
      }

      showToast(msg, 'Error', 'text-warning');
    },
    complete: function () {
      // re-enable button
      $block.find('.translate-btn').prop('disabled', false).text('Translate');
      if (window.hideLoader) window.hideLoader();
    }
  });
});


$(document).on('click', '#translateAllLocalization', function () {
  let sku = $(this).data('sku');

  $.ajax({
    url: baseUrl + "product-management/translate",
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
    type: "POST",
    data: { sku: sku },

    beforeSend: function () {
      // Show page loader
      if (window.showLoader) window.showLoader();
    },

    success: function (response) {
      if (response.success) {
        if (window.loadProductData) window.loadProductData();
        showToast(response.message, 'Success', 'text-success');
      } else {
        showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 4000);
      }
    },

    error: function () {
      showToast('Something went wrong', 'Error', 'text-warning', 4000);
    },

    complete: function () {
      if (window.hideLoader) window.hideLoader();
    }
  });
});
