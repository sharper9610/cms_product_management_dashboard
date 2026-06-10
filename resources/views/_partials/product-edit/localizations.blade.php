@php
  $languages = config('languages'); // assuming your config file is config/languages.php
@endphp
<!-- Localization Form -->
<form id="localizationForm" class="tabForm" data-tab="localization">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="localization">
  <div class="p-3 overflow-auto">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Localizations</h5>
{{--      <button type="button" class="btn btn-primary btn-sm" id="addLocalization">--}}
{{--        <i class="bi bi-plus"></i> Add Localization--}}
{{--      </button>--}}


      <div class="d-flex gap-2">
        <!-- Translate Button -->
        <button type="button"
                title="Translate into all required languages"
                class="btn btn-secondary btn-sm"
                id="translateAllLocalization"
                data-sku="{{ $product->sku }}">
          <i class="ri ri-translate me-1"></i>Translate to All
        </button>


        <!-- Add Localization Button -->
        <button type="button" class="btn btn-primary btn-sm" id="addLocalization">
          <i class="ri ri-add-fill me-1"></i> Add Localization

        </button>
      </div>
    </div>

    <!-- Container for visible localization blocks -->
    <div id="localizationContainer">
      <!-- Dynamic content injected via JS -->
    </div>



  </div>

  <div class="row mt-4">
    <div class="col d-flex justify-content-end">
      <button type="submit" class="btn btn-primary">Update Localizations</button>
    </div>
  </div>
</form>
<!-- Hidden template for cloning -->
<div id="localizationTemplate" class="d-none">
  <div class="card mb-3 localization-block">
    <div class="card-body">
      <input type="hidden" name="localizations[id][]" value="">

      <div class="mt-2 d-flex justify-content-end gap-2">
        {{-- Translate button --}}
{{--        <button type="button" class="btn btn-outline-secondary btn-sm translate-btn">--}}
{{--          <i class="bi bi-translate"></i> Translate--}}
{{--        </button>--}}

        {{-- Remove button --}}
        <button type="button" class="btn btn-outline-danger btn-sm removeLocalization">
          <i class="ri ri-delete-bin-6-line me-1"></i> Remove
        </button>
      </div>


      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Language Code</label>
          <input type="text" name="localizations[language_code][]" class="form-control" placeholder="e.g., en, pt-br">
          <div class="invalid-feedback"></div>
        </div>


{{--        <div class="col-md-6">--}}
{{--          <label class="form-label">Language Code</label>--}}
{{--          <select name="localizations[language_code][]" class="form-select">--}}
{{--            <option value="" >Select code</option>--}}
{{--            @foreach($languages as $name => $code)--}}
{{--              <option value="{{ $code }}"> {{ $code }}</option>--}}
{{--            @endforeach--}}
{{--          </select>--}}
{{--          <div class="invalid-feedback"></div>--}}
{{--        </div>--}}
        <div class="col-md-6">
          <label class="form-label">Localized Name</label>
          <input type="text" name="localizations[localized_name][]" class="form-control ">
          <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
          <label class="form-label">Short Description</label>
          <div data-name="localizations[short_description][]" class="editor"></div>
          <textarea name="localizations[short_description][]" class="d-none"></textarea>

          <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
          <label class="form-label">Long Description</label>
          <div data-name="localizations[long_description][]" class="editor"></div>
          <textarea name="localizations[long_description][]" class="d-none"></textarea>

          <div class="invalid-feedback" ></div>
        </div>
      </div>


    </div>
  </div>
</div>

