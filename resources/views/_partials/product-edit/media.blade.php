<input type="hidden" id="source-id-get" value="{{$product->source}}">
<form id="mediaForm" class="tabForm" data-tab="media" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="media">

  <div class="p-3 overflow-auto">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Media</h5>
      <button type="button" class="btn btn-primary btn-sm" id="addMedia">
        <i class="bi bi-plus"></i> Add Media
      </button>
    </div>
    <div class="col d-flex justify-content-end mb-1">
      <button type="submit" class="btn btn-sm btn-primary">Update Media</button>
    </div>

    <div id="mediaContainer"></div>
  </div>

  <div class="row mt-4">
    <div class="col d-flex justify-content-end">
      <button type="submit" class="btn btn-primary">Update Media</button>
    </div>
  </div>
</form>

<!-- Hidden template for cloning -->
<div id="mediaTemplate" class="d-none">
  <div class="card mb-3 media-block">
    <div class="card-body">
      <div class="row g-3">
        <!-- Media Type -->
        <div class="col-md-2">
          <label class="form-label">Media Type</label>
          <select name="media[__INDEX__][type]" class="form-select media-type">
{{--            <option value="image">Image</option>--}}
            <option value="videos">Video</option>
{{--            <option value="videos_steam">Video Steam</option>--}}
            <option value="boxshot">Boxshot</option>
            <option value="screenshot">Screenshot</option>
{{--            <option value="image">Image</option>--}}
          </select>
          <div class="invalid-feedback"></div>
          <!-- Source Media Label (styled display) -->
          <span class="badge bg-secondary mt-2 source-media-label">
            Source Media: <span class="source-media-text">Manual</span>
          </span>


        </div>

        <div class="col-md-2 image_orientation-container">
          <label class="form-label">Image orientation</label>
          <select name="media[__INDEX__][image_orientation]" class="form-select image_orientation">
            <option value="0">Select</option>
            <option value="1">Portrait</option>
            <option value="2">Landscape</option>

          </select>
          <div class="invalid-feedback"></div>

        </div>

        <!-- Media Input (file or URL) -->
        <div class="col-md-5 media-input-container">
          <label class="form-label">Select Image</label>
          <input type="file" name="media[__INDEX__][file]" class="form-control media-file">
{{--          <img class="img-preview mt-2" style="max-width:120px; display:none;">--}}
          <a href="" class="img-preview-link" target="_blank" style="display:none;">
            <img class="img-preview mt-2" style="max-width:120px;">
          </a>

          <input type="url" name="media[__INDEX__][url]" class="form-control d-none">
          <div class="invalid-feedback"></div>
        </div>

        <!-- Main Media Checkbox -->
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input type="checkbox" name="media[__INDEX__][main]" class="form-check-input mainMediaCheckbox" value="1">
            <label class="form-check-label">Main Media</label>
            <div class="invalid-feedback"></div>
          </div>
        </div>

        <!-- Remove Button -->
        <div class="col-md-1 d-flex justify-content-end align-items-start">
          <button type="button" class="btn btn-outline-danger btn-sm removeMedia">
            <i class="icon-base ri ri-delete-bin-line"></i>
          </button>
        </div>

        <!-- Hidden ID for existing media -->
        <input type="hidden" name="media[__INDEX__][id]" value="">
      </div>
    </div>
  </div>
</div>
