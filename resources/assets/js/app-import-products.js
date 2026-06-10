



$(document).ready(function(){

  function ajaxFormSubmit(formId, spinnerId, modalId, url) {
    $(formId).on('submit', function(e){
      e.preventDefault();

      var form = $(this);
      var spinner = $(spinnerId);

      spinner.removeClass('d-none');
      form.find('button[type="submit"]').prop('disabled', true);

      const params = new URLSearchParams(window.location.search);
      let fullUrl = baseUrl + `${url}`;

      // If 'check' param exists, append it
      if (params.has('check')) {
        const checkValue = params.get('check');
        fullUrl += `?check=${checkValue}`;
      }

      $.ajax({
        url: fullUrl,
        method: "POST",
        data: form.serialize(),
        success: function(response){

          if (response?.success) {
            showToast(response?.message ?? 'Imported successfully!', 'Success', 'bg-success');

          } else {
            showToast(response?.message ?? 'Something went wrong!', 'Error!', 'bg-warning');
          }

          $(modalId).modal('hide');
          form[0].reset();
        },
        error: function(xhr){
          showToast('Something went wrong!', 'Error!', 'bg-danger');

        },
        complete: function(){
          spinner.addClass('d-none');
          form.find('button[type="submit"]').prop('disabled', false);
        }
      });
    });
  }

  // Initialize AJAX for Product
  ajaxFormSubmit('#productImportForm',
    '#productSpinner',
    '#productImportModal', "import-products");

  // Initialize AJAX for Price
  ajaxFormSubmit('#priceImportForm',
    '#priceSpinner', '#priceImportModal',
    "import-prices");



  $(document).ready(function() {
    // Function to handle the toggle logic
    function toggleSkuField(checkboxId, skuFieldClass) {
      const $checkbox = $(checkboxId);
      const $skuField = $checkbox.closest('.modal-body').find('.' + skuFieldClass);
      const $skuInput = $skuField.find('input[name="sku"]');

      // Initial check on load (though usually the box starts unchecked)
      if ($checkbox.is(':checked')) {
        $skuField.hide();
        $skuInput.prop('required', false);
        $skuInput.val(''); // Clear value when hidden
      } else {
        $skuField.show();
        $skuInput.prop('required', true);
      }

      // Event listener for changes
      $checkbox.on('change', function() {
        if ($(this).is(':checked')) {
          // If 'Import All' is checked
          $skuField.slideUp(200); // Hide the field
          $skuInput.prop('required', false); // Not required
          $skuInput.val(''); // Clear value
        } else {
          // If 'Import All' is unchecked
          $skuField.slideDown(200); // Show the field
          $skuInput.prop('required', true); // Required
        }
      });
    }

    // Apply the logic to both modals
    // Product Modal: checkbox is #product-import-all, SKU container has class .sku-field
    toggleSkuField('#product-import-all', 'sku-field');


  });
});


