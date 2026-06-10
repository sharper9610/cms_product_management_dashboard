/**
 * Page User List
 */

'use strict';


document.addEventListener('DOMContentLoaded', function () {

  const supportedLanguages = Object.keys(window.allLanguages);

  const interfaceEl = document.querySelector('#interface');
  if (interfaceEl) {
    const tagifyCustomListSuggestion = new Tagify(interfaceEl, {
      whitelist: supportedLanguages,
      maxTags: 50,
      dropdown: {
        maxItems: 50,
        classname: '',
        enabled: 0,
        closeOnSelect: false
      }
    });
  }

  const full_audioEl = document.querySelector('#full_audio');
  if (full_audioEl) {
    const tagifyCustomListSuggestion = new Tagify(full_audioEl, {
      whitelist: supportedLanguages,
      maxTags: 50,
      dropdown: {
        maxItems: 50,
        classname: '',
        enabled: 0,
        closeOnSelect: false
      }
    });
  }

  const subtitlesEl = document.querySelector('#subtitles');
  if (subtitlesEl) {
    const tagifyCustomListSuggestion = new Tagify(subtitlesEl, {
      whitelist: supportedLanguages,
      maxTags: 50,
      dropdown: {
        maxItems: 50,
        classname: '',
        enabled: 0,
        closeOnSelect: false
      }
    });
  }

  const dlc_products = document.querySelector('#dlc_products');
  if (dlc_products) {
    new Tagify(dlc_products);
  }



});





$('#summary').on('submit', function(e) {
  e.preventDefault();
  let $form = $(this);




  // $('#legalTextsContainer .legalTexts-block').each(function() {
  //   // 'this' refers to the current .systemReq-block div
  //   const $block = $(this);

  //   // Find the Quill content div within this block. Quill creates a div with the class '.ql-editor'.
  //   const $editorContent = $block.find('.editor .ql-editor');

  //   // Find the hidden textarea that corresponds to this editor
  //   const $hiddenTextarea = $block.find('textarea[name="localizations[legal_texts][]"]');

  //   if ($editorContent.length && $hiddenTextarea.length) {
  //     // Get the raw HTML from the dynamic editor
  //     let rawLocalHtml = $editorContent.html(); // .html() is jQuery's equivalent of innerHTML

  //     // Sanitize it using your existing function for consistency
  //     let cleanLocalHtml = sanitizeQuillHtml(rawLocalHtml);

  //     // Set the value of the hidden textarea
  //     $hiddenTextarea.val(cleanLocalHtml);
  //   }
  // });





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





// $(document).ready(function () {
//   $('#translateTermsConditionBtn').on('click', function () {
//     const $btn = $(this); // cache the button

//     // Check existing translated inputs
//     let allValid = false; // assume all are valid initially

//     // $('.legal_text_id').each(function () {
//     //   const val = $(this).val();
//     //   console.log(val);
//     //   if (val === '0' || val === '' || val === null) {
//     //     allValid = false;
//     //     return false; // stop loop if any invalid value found
//     //   }
//     // });

//     // Prepare messages
//     let title = allValid ? 'Already Translated!' : 'Are you sure?';
//     let text = allValid
//       ? "Translated data already exists. If you continue, the previous data will be removed. Do you want to re-translate?"
//       : "Do you want to translate  Terms & Conditions to pt-BR and es-419?";

//     Swal.fire({
//       title: title,
//       text: text,
//       icon: 'question',
//       showCancelButton: true,
//       confirmButtonText: allValid ? 'Yes, Re-Translate' : 'Yes, Translate',
//       cancelButtonText: 'Cancel'
//     }).then((result) => {
//       if (result.isConfirmed) {
//         $.ajax({
//           type: 'POST',
//           url: `/product-management/${$('#product_id_get').val()}`,
//           headers: {
//             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
//             'X-HTTP-Method-Override': 'PUT'
//           },
//           data: {
//             tab_name: 'translate-terms-conditions'
//           },
//           beforeSend: function() {
//             if (window.showLoader) window.showLoader();
//             // disable button and show loading text
//             $btn.prop('disabled', true).text('Translating to pt-BR, es-419...');
//           },
//           complete: function() {
//             if (window.hideLoader) window.hideLoader();
//             // reset button text
//             $btn.prop('disabled', false).text('Translate to pt-BR, es-419');
//           },
//           success: function (response) {
//             if(response.success) {
//               showToast(response.message ?? 'System requirements translated successfully!', 'Success', 'text-success');
//               if (window.loadProductData) window.loadProductData();
//             } else {
//               showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 5000);
//             }
//           },
//           error: function () {
//             showToast('Something went wrong', 'Error', 'text-warning', 5000);
//           }
//         });
//       }
//     });
//   });
// });


// function sanitizeQuillHtml(htmlString) {
//   // Use the browser's built-in parser to safely manipulate the HTML
//   const parser = new DOMParser();
//   const doc = parser.parseFromString(htmlString, 'text/html');

//   // Find all list items that Quill has marked as bullets
//   doc.querySelectorAll('li[data-list="bullet"]').forEach(li => {
//     // 1. Remove the data-list attribute
//     li.removeAttribute('data-list');

//     // 2. Find and remove the extra <span class="ql-ui"></span> that Quill adds
//     const quillSpan = li.querySelector('span.ql-ui');
//     if (quillSpan) {
//       quillSpan.remove();
//     }
//   });

//   // Find all <ol> tags that might have been used for bullet lists
//   doc.querySelectorAll('ol').forEach(ol => {
//     // If the first list item inside was a bullet, we assume the whole list should be a <ul>.
//     // This is a safe assumption for Quill's output.
//     if (ol.querySelector('li') && !ol.querySelector('li[data-list]')) { // Check if we've cleaned its children
//       const ul = doc.createElement('ul');
//       // Move all children (the <li> elements) from <ol> to the new <ul>
//       while (ol.firstChild) {
//         ul.appendChild(ol.firstChild);
//       }
//       // Replace the old <ol> with the new <ul>
//       ol.parentNode.replaceChild(ul, ol);
//     }
//   });

//   // Return the cleaned HTML from the body of our temporary document
//   return doc.body.innerHTML;
// }
