$(document).ready(function(){
  $('#parentSkuSyncBtn').on('click', function() {
    var btn = $(this);
    var spinner = $('#parentSkuSyncSpinner');

    spinner.removeClass('d-none');
    btn.prop('disabled', true);

    $.ajax({
      url: baseUrl + 'product/r2-json-upload/sync-parent-sku',
      method: 'POST',
      data: { _token: $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        var body = response?.data ?? response;
        var detail = '\n' + JSON.stringify(body, null, 2);

        if (response?.success) {
          showToast((response?.message ?? 'Parent SKU sync triggered successfully!') + detail, 'Success', 'bg-success', 8000);
        } else {
          showToast((response?.message ?? 'Something went wrong!') + detail, 'Error!', 'bg-danger', 8000);
        }
      },
      error: function(xhr) {
        var payload = xhr.responseJSON ?? {};
        var message = payload.message ?? 'Something went wrong!';
        var detail = '\n' + JSON.stringify(payload, null, 2);

        showToast(message + detail, 'Error!', 'bg-danger', 8000);
      },
      complete: function() {
        spinner.addClass('d-none');
        btn.prop('disabled', false);
      }
    });
  });

  $('#r2JsonUploadForm').on('submit', function(e) {
    e.preventDefault();

    var form = $(this);
    var spinner = $('#r2JsonSpinner');

    spinner.removeClass('d-none');
    form.find('button[type="submit"]').prop('disabled', true);

    const params = new URLSearchParams(window.location.search);
    let fullUrl = baseUrl + 'product/r2-json-upload';

    if (params.has('check')) {
      const checkValue = params.get('check');
      fullUrl += `?check=${checkValue}`;
    }

    $.ajax({
      url: fullUrl,
      method: 'POST',
      data: form.serialize(),
      success: function(response) {
        if (response?.success) {
          showToast(response?.message ?? 'Upload completed successfully!', 'Success', 'bg-success');
        } else {
          showToast(response?.message ?? 'Something went wrong!', 'Error!', 'bg-warning');
        }

        form[0].reset();
      },
      error: function(xhr) {
        var message = 'Something went wrong!';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }

        showToast(message, 'Error!', 'bg-danger');
      },
      complete: function() {
        spinner.addClass('d-none');
        form.find('button[type="submit"]').prop('disabled', false);
      }
    });
  });
});
