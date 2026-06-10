<form class="skipUpdateForm" data-tab="basic_info" id="skipUpdateForm"
      method="POST"
      enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="skip-update">

  <div class="mb-3">
    <h4>Skip Update Fields</h4>
    <p class="text-muted">Select the fields you want to skip for this product.</p>
  </div>

  @php
    $fields = [
        'name' => 'Name',
        'genres' => 'Genres',
        'platform' => 'Platform',
        'product_type' => 'Product type',
        'publisher_name' => 'Publisher Name',
        'status' => 'Status',
        'region_tag' => 'Region tag',

        'download_date' => 'Download date',
        'release_date' => 'Release date',
        'system_requirements' => 'System requirements',
        'supported_languages' => 'Supported languages',



    ];
  @endphp

{{--  <div class="row g-3">--}}
{{--    @foreach($fields as $dbField => $label)--}}
{{--      <div class="col-6 col-md-4 col-lg-3">--}}
{{--        <div class="form-check form-switch">--}}
{{--          <input class="form-check-input" type="checkbox"--}}
{{--                 name="skip_fields[]"--}}
{{--                 value="{{ $dbField }}"--}}
{{--                 id="skip_{{ $dbField }}"--}}
{{--                 @if($product->skipUpdates->pluck('field_name')->contains($dbField)) checked @endif>--}}
{{--          <label class="form-check-label" for="skip_{{ $dbField }}">--}}
{{--            {{ $label }}--}}
{{--          </label>--}}
{{--        </div>--}}
{{--      </div>--}}
{{--    @endforeach--}}
{{--  </div>--}}

  <div class="row g-3" id="skipUpdateFieldsContainer">
    <!-- Skip update fields will be dynamically loaded here by JS -->
  </div>

  <div class="d-flex justify-content-end mt-4">
    <button type="submit" class="btn btn-primary">Update</button>
  </div>
</form>
