/**
 * Page User List
 */

'use strict';
// Add a new media block


// This is the new cleaning function.
// It converts Quill's special lists back to standard <ul> lists.
function sanitizeQuillHtml(htmlString) {
  // Use the browser's built-in parser to safely manipulate the HTML
  const parser = new DOMParser();
  const doc = parser.parseFromString(htmlString, 'text/html');

  // Find all list items that Quill has marked as bullets
  doc.querySelectorAll('li[data-list="bullet"]').forEach(li => {
    // 1. Remove the data-list attribute
    li.removeAttribute('data-list');

    // 2. Find and remove the extra <span class="ql-ui"></span> that Quill adds
    const quillSpan = li.querySelector('span.ql-ui');
    if (quillSpan) {
      quillSpan.remove();
    }
  });

  // Find all <ol> tags that might have been used for bullet lists
  doc.querySelectorAll('ol').forEach(ol => {
    // If the first list item inside was a bullet, we assume the whole list should be a <ul>.
    // This is a safe assumption for Quill's output.
    if (ol.querySelector('li') && !ol.querySelector('li[data-list]')) { // Check if we've cleaned its children
      const ul = doc.createElement('ul');
      // Move all children (the <li> elements) from <ol> to the new <ul>
      while (ol.firstChild) {
        ul.appendChild(ol.firstChild);
      }
      // Replace the old <ol> with the new <ul>
      ol.parentNode.replaceChild(ul, ol);
    }
  });

  // Return the cleaned HTML from the body of our temporary document
  return doc.body.innerHTML;
}



$('#systemReqForm').on('submit', function(e) {
  e.preventDefault();
  let $form = $(this);

  // --- Step 1: Handle the MAIN editor (your existing code is good) ---
  // let rawQuillHtml = fullEditor.root.innerHTML;
  // let cleanHtml = sanitizeQuillHtml(rawQuillHtml);
  // $('#system_requirement').val(cleanHtml);


  // --- Step 2: NEW - Handle EACH DYNAMIC localization editor ---
  // Loop through each localization block that has been added to the container.
  $('#systemReqContainer .systemReq-block').each(function() {
    // 'this' refers to the current .systemReq-block div
    const $block = $(this);

    // Find the Quill content div within this block. Quill creates a div with the class '.ql-editor'.
    const $editorContent = $block.find('.editor .ql-editor');

    // Find the hidden textarea that corresponds to this editor
    const $hiddenTextarea = $block.find('textarea[name="localizations[system_requirements][]"]');

    if ($editorContent.length && $hiddenTextarea.length) {
      // Get the raw HTML from the dynamic editor
      let rawLocalHtml = $editorContent.html(); // .html() is jQuery's equivalent of innerHTML

      // Sanitize it using your existing function for consistency
      let cleanLocalHtml = sanitizeQuillHtml(rawLocalHtml);

      // Set the value of the hidden textarea
      $hiddenTextarea.val(cleanLocalHtml);
    }
  });
  // --- End of new code block ---


  // --- Step 3: Now that all textareas are populated, serialize the form ---
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
      if (window.showLoader) window.showLoader();
    },
    complete: function() {
      if (window.hideLoader) window.hideLoader();
    },
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

$(document).ready(function () {
  $('#translateBtn').on('click', function () {
    const $btn = $(this); // cache the button

    // Check existing translated inputs
    let hasTranslated = false;
    $('.locale-lang-code').each(function () {
      const val = $(this).val();
      if (val === 'pt-BR' || val === 'es-419') {
        hasTranslated = true;
      }
    });

    // Prepare messages
    let title = hasTranslated ? 'Already Translated!' : 'Are you sure?';
    let text = hasTranslated
      ? "Translated data already exists. If you continue, the previous data will be removed. Do you want to re-translate?"
      : "Do you want to translate system requirements to pt-BR and es-419?";

    Swal.fire({
      title: title,
      text: text,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: hasTranslated ? 'Yes, Re-Translate' : 'Yes, Translate',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: 'POST',
          url: `/product-management/${$('#product_id_get').val()}`,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-HTTP-Method-Override': 'PUT'
          },
          data: {
            tab_name: 'translate-system-req'
          },
          beforeSend: function() {
            if (window.showLoader) window.showLoader();
            // disable button and show loading text
            $btn.prop('disabled', true).text('Translating to pt-BR, es-419...');
          },
          complete: function() {
            if (window.hideLoader) window.hideLoader();
            // reset button text
            $btn.prop('disabled', false).text('Translate to pt-BR, es-419');
          },
          success: function (response) {
            if(response.success) {
              showToast(response.message ?? 'System requirements translated successfully!', 'Success', 'text-success');
              if (window.loadProductData) window.loadProductData();
            } else {
              showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 5000);
            }
          },
          error: function () {
            showToast('Something went wrong', 'Error', 'text-warning', 5000);
          }
        });
      }
    });
  });
});

