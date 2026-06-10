$(document).ready(function () {
  const productId = $('#product_id_get').val();

  /**
   * Populates the media container with data from the server.
   * @param {Array} mediaArray - Array of media objects.
   */


  /**
   * Updates the input field within a media block based on type and if it's an existing item.
   * @param {jQuery} $block - The jQuery object for the .media-block element.
   */
  function updateInputVisibility($block) {
    const index = $block.data('index');
    const type = $block.find('.media-type').val();
    const $container = $block.find('.media-input-container');
    const mediaId = $block.find(`input[name="media[${index}][id]"]`).val();
    const imageUrl = $block.data('image-url') || '';

    $container.empty(); // Clear previous content

    if (type === 'boxshot') {
      $block.find('.image_orientation-container').show();
    } else {
      $block.find('.image_orientation-container').hide();
    }


    if (type === 'image' || type==='boxshot' || type==='screenshot') {
      // If it's an existing image from the database
      if (mediaId && imageUrl) {
        $container.html(`
                    <label class="form-label">Current Image</label>
                    <div>
                        <img src="${imageUrl}" class="img-preview" style="max-width: 120px; border-radius: 4px; margin-bottom: 10px;">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary replace-image-btn">Replace</button>
                    <div class="file-input-wrapper d-none mt-2">
                        <label class="form-label">New Image File</label>
                        <input type="file" name="media[${index}][file]" class="form-control media-file">
                        <div class="invalid-feedback"></div>
                    </div>
                `);
      } else { // It's a new image block
        $container.html(`
                    <label class="form-label">Select Image File</label>
                    <input type="file" name="media[${index}][file]" class="form-control media-file">
                    <div class="invalid-feedback"></div>
                `);
      }
    } else { // 'videos' or 'videos_steam'
      $container.html(`
                <label class="form-label">Video URL</label>
                <input type="url" name="media[${index}][url]" class="form-control" value="">
                <div class="invalid-feedback"></div>
            `);
    }
  }


  /**
   * Adds a new media block to the container.
   * @param {Object} [media={}] - Optional media data to pre-populate the block.
   */


  // --- EVENT LISTENERS ---

  // Add a new media block
  $('#addMedia').on('click', function() {
    window.addMediaBlock();

    const $lastBlock = $('#mediaContainer .media-block').last();

    if ($lastBlock.length) {
      // Scroll the container so the last block is visible
      const $container = $('#mediaContainer');
      $container.stop().animate({
        scrollTop: $container.scrollTop() + $lastBlock.position().top
      }, 500);

      // Optionally focus the first input inside the new block
      $lastBlock.find('input, select, textarea').first().focus();
    }
  });

  // Remove a media block (works for both existing and new)
  $(document).on('click', '.removeMedia', function() {
    const $mediaBlock = $(this).closest('.media-block'); // store reference

    const mediaId = $mediaBlock.find('input[name*="[id]"]').val();



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
        if (mediaId){

          $.ajax({
            type: 'POST', // Method is spoofed by _method field for PUT
            url: `/product-management/${productId}`,
            headers: {
              'X-CSRF-TOKEN':  $('meta[name="csrf-token"]').attr('content'),
              'X-HTTP-Method-Override': 'PUT'
            },
            data: {
              tab_name: 'media_delete',
              media_id: mediaId
            },
            beforeSend: () => window.showLoader && window.showLoader(),
            complete: () => window.hideLoader && window.hideLoader(),
            success: function(response){
              window.loadProductData && window.loadProductData();
              showToast(response.message, 'Success', 'text-success');
            },
            error: function(){
              showToast('Failed to delete localization', 'Error', 'text-warning');
            }
          });
        }
        else{
          $mediaBlock.remove();
        }

      }
    });

  });

  // Handle changing the media type dropdown
  $(document).on('change', '.media-type', function() {
    const $block = $(this).closest('.media-block');
    updateInputVisibility($block);
  });

  // Show file input when "Replace" is clicked for an existing image
  $(document).on('click', '.replace-image-btn', function() {
    $(this).hide();
    $(this).siblings('.file-input-wrapper').removeClass('d-none');
  });

  // Ensure only one "Main Media" checkbox can be checked
  $(document).on('change', '.mainMediaCheckbox', function() {
    if (this.checked) {
      // $('.mainMediaCheckbox').not(this).prop('checked', false);
    }
  });


  $('#mediaForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const formData = new FormData(this);

    // Clear previous errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    $.ajax({
      type: 'POST', // Method is spoofed by _method field for PUT
      url: `/product-management/${productId}`,
      headers: { 'X-HTTP-Method-Override': 'PUT' },
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: () => window.showLoader && window.showLoader(),
      complete: () => window.hideLoader && window.hideLoader(),
      success: function(response) {
        if (response.success) {
          showToast(response.message, 'Success', 'text-success');
          // Reload data to get updated IDs and URLs
          window.loadProductData && window.loadProductData();
        } else {
          showToast(response.message || 'Something went wrong', 'Error', 'text-warning', 10000);
        }
      },
      error: function(xhr) {
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          const $form = $('#mediaForm'); // Make sure $form is defined

          $.each(errors, function (key, messages) {
            const name = key.split('.').map((part, index) => {
              return index === 0 ? part : `[${part}]`;
            }).join('');

            // --- DEBUGGING LINE 1 ---
            console.log(`Trying to find input for key: '${key}' with selector: [name="${name}"]`);

            const $input = $form.find(`[name="${name}"]`);

            // --- DEBUGGING LINE 2 ---
            console.log('Found input element:', $input); // Should show a jQuery object with length 1

            if ($input.length > 0) {
              $input.addClass('is-invalid');

              const $feedbackContainer = $input.closest('.media-input-container').find('.invalid-feedback');

              // --- DEBUGGING LINE 3 ---
              console.log('Found feedback container:', $feedbackContainer); // Should show the div

              if($feedbackContainer.length > 0) {
                $feedbackContainer.text(messages[0]);
              } else {
                console.error('ERROR: Could not find the .invalid-feedback div for this input!');
              }
            } else {
              console.error('ERROR: Input element NOT found!');
            }
          });
          // ...
        }
        else {
          showToast('An unexpected server error occurred.', 'Error', 'text-warning', 10000);
        }
      }
    });
  });

  // This part should be in your main edit page JS, but including here for context
  // You should have a function like this that calls `populateMedia`
  /*
  function loadProductData() {
      $.ajax({
          //... your ajax setup
          success: function(response) {
              //... populate other fields
              populateMedia(response.media || []);
          }
      });
  }
  window.loadProductData = loadProductData;
  loadProductData();
  */
});
