
<input type="hidden" name="tab_name" value="upload-json">
<div class="p-3" >
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Upload PlayStation JSON File</h4>
      <button class="btn btn-secondary btn-sm" id="resetBtn">Reset All</button>
    </div>

    <div class="card-body">
      <input type="file" class="form-control" id="jsonFileInput" multiple accept=".json">
      <div class="mt-2">
        <a href="{{ asset('assets/demo/PlayStation.json') }}" download>Download Demo JSON</a>

      </div>
      <button class="btn btn-primary mt-3" id="previewBtn">
        Preview JSON
      </button>
    </div>
  </div>

  <!-- Preview Section -->
  <div class="card" id="previewCard" style="display:none;">
    <div class="card-header">
      <h5 class="mb-0">Preview </h5>
    </div>

    <div class="card-body">
      <div id="jsonPreviewArea"></div>

      <button class="btn btn-success mt-2" id="uploadBtn">
        Upload to Server
      </button>
    </div>
  </div>



</div>
