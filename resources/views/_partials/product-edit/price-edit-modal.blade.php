<!-- Generate Tags Modal -->

<!-- Generate Tags Modal -->
<div class="modal fade" id="priceEditModal" tabindex="-1" aria-labelledby="priceEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <div id="modal_loader"
           style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.6);
           z-index:1056; display:flex; justify-content:center; align-items:center;">
        <div class="spinner-border"></div>
      </div>

      <div class="modal-header">
        <h5 class="modal-title" id="priceEditModalLabel">Edit Price</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="modal_price_form" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <input type="hidden" id="modal_price_id" name="price_id">
          <input type="hidden"  name="tab_name" value="price-info-update">
          <div id="modal_error" class="text-danger text-center mb-2" style="font-size: 1.1rem;"></div>




          <!-- Title -->
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" id="modal_title" name="title" class="form-control">
          </div>



          <!-- Price, Discount, Concept ID -->
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Discount (%)</label>
              <input type="text" id="modal_discount_percent" name="discount_percent" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Discount Valid From</label>
              <input type="text" id="modal_discount_valid_from"
                     name="discount_valid_from"
                     class="form-control" autocomplete="off" placeholder="Y-m-d H:i:S">
            </div>
            <div class="col-md-4">
              <label class="form-label">Discount Valid To</label>
              <input type="text" id="modal_discount_valid_to"
                     name="discount_valid_to"
                     class="form-control" autocomplete="off" placeholder="Y-m-d H:i:S">
            </div>


            <div class="col-md-3">
              <label class="form-label">Price</label>
              <input type="text" id="modal_price" name="price" class="form-control">
            </div>

            <div class="col-md-9">
              <label class="form-label">Concept ID</label>
              <input type="text" id="modal_concept_id" name="concept_id" class="form-control">
            </div>
          </div>

          <!-- Platforms (Tagify) -->
          <div class="mb-3 mt-3">
            <label class="form-label">Platforms</label>
            <input id="modal_platforms" name="platforms" class="form-control">
          </div>

          <!-- Scrape URL -->
          <div class="mb-3 mt-3">
            <label class="form-label">Scrape URL</label>
            <input type="text" id="modal_scrape_url" name="scrape_url" class="form-control">
          </div>

          <!-- Description -->
          <div class="mb-3 mt-3">
            <label class="form-label">Description</label>
            <textarea id="modal_description" name="description" class="form-control" rows="3"></textarea>
          </div>

          <!-- Primary Image -->
          <div class="row g-3 mb-3 mt-3">
            <div class="col-md-6">
              <label class="form-label">Primary Image</label>
              <input type="file" id="modal_image_upload" name="primary_image" class="form-control" accept="image/*">
              <small class="text-muted">Select a new image to replace existing one</small>
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-center">
              <img id="modal_image_preview" src="" class="img-fluid border rounded"
                   style="max-height:150px; width:auto; display:none;">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="modal_save_btn">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>
