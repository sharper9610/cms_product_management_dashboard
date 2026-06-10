<form id="systemReqForm" class="systemReq" data-tab="systemReq" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="systemReq">
  <div class="p-3 overflow-auto" >
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>System Requirements</h5>

      <!-- Translate Button -->
      <button type="button" id="translateBtn" class="btn btn-sm btn-secondary ">
        Translate to pt-BR, es-419
      </button>
    </div>
{{--    <div id="requirementTemplate">--}}
{{--      <div class="card mb-3 requirement-block">--}}
{{--        <div class="card-body">--}}
{{--          <label class="form-label">Language (en)</label>--}}

{{--          <div id="full-editor"> </div>--}}
{{--          <input type="hidden" name="system_requirement" id="system_requirement">--}}

{{--        </div>--}}
{{--      </div>--}}
{{--    </div>--}}

    <div id="systemReqContainer">
    </div>

    <div class="row mt-4">
      <div class="col d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Update Requirements</button>
      </div>
    </div>
  </div>


</form>

<div id="systemReqTemplate" class="d-none">
  <div class="card mb-3 systemReq-block">
    <div class="card-body">
      <input type="hidden" name="localizations[id][]" value="">
      <input type="hidden" name="localizations[locale][]" class="locale-lang-code" value="">

      <div class="col-12">
          <label class="form-label">Language ()</label>
          <div data-name="localizations[system_requirements][]" class="editor"></div>
          <textarea name="localizations[system_requirements][]" class="d-none"></textarea>

          <div class="invalid-feedback"></div>
      </div>
      </div>


    </div>
  </div>

