
<form  class="tabForm" data-tab="summary" id="summary" >
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="summary">
  <div class="row g-3">




    <!-- Platform -->
    <!-- <div class="col-md-6">
      <label for="drm_type" class="form-label">DRM Type</label>
      <input type="text" id="drm_type" name="drm_type" class="form-control">
      <div class="invalid-feedback" id="error_drm_type"></div>
    </div> -->

    <!-- <div class="col-md-6">
      <label for="region_tag" class="form-label"> Region tag	</label>
      <input type="text" id="region_tag" name="region_tag" class="form-control"
             @if($product->source===1) readonly disabled @endif
             >
      <div class="invalid-feedback" id="error_region_tag"></div>
    </div> -->

    <!-- <div class="col-md-6">
      <label for="auxiliary_field" class="form-label">Auxiliary field (Incomm)</label>
      <input type="text" id="auxiliary_field" name="auxiliary_field" class="form-control">
      <div class="invalid-feedback" id="error_auxiliary_field"></div>
    </div> -->
    <!-- <div class="col-md-6">
      <label for="bundled_products" class="form-label">Bundled products</label>
      <input type="text" id="bundled_products" name="bundled_products" class="form-control">
      <div class="invalid-feedback" id="error_bundled_products"></div>
    </div> -->


    <!-- <div class="col-md-6">
      <label for="classification" class="form-label">Classification	(Incomm)</label>
      <input type="text" id="classification" name="classification" class="form-control">
      <div class="invalid-feedback" id="error_classification"></div>
    </div> -->

   <!-- <div class="col-md-6">
      <label for="community_discussion" class="form-label">Community discussion	</label>
      <input type="text" id="community_discussion" name="community_discussion" class="form-control">
      <div class="invalid-feedback" id="error_community_discussion"></div>
    </div> -->





    <div class="col-md-12">
      <label for="dlc_products" class="form-label">DLC Product IDs 	</label>
      <input type="text"  id="dlc_products" name="dlc_products"   class="form-control">

      <div class="form-text">Type a Product id and press Enter or comma
      </div>
      <div class="invalid-feedback" id="error_dlc_products"></div>
    </div>

    <div class="col-md-6">
      <label for="dlc_master_product_id" class="form-label">DLC Master Product ID 	</label>
      <input type="number" id="dlc_master_product_id" name="dlc_master_product_id"   class="form-control">
      <div class="invalid-feedback" id="error_dlc_master_product_id"></div>
    </div>
    <div class="col-md-6">
      <label for="is_dlc" class="form-label">IS DLC</label>
      <select id="is_dlc" name="is_dlc" class="form-select">
        <option value="">Select</option>
        <option value="1">Yes</option>
        <option value="0">No</option>

      </select>
      <div class="invalid-feedback" id="error_is_dlc"></div>
    </div>


    <!-- <div class="col-md-12">
      <label for="face_value" class="form-label"> Face value	</label>
      <input type="text" id="face_value" name="face_value" class="form-control">
      <div class="invalid-feedback" id="error_face_value"></div>
    </div> -->
    <!-- <div class="col-md-12">
      <label for="redemption" class="form-label"> Redemption (Incomm)</label>
      <input type="text" id="redemption" name="redemption" class="form-control">
      <div class="invalid-feedback" id="error_redemption"></div>
    </div> -->
    <!-- <div class="col-md-12">
      <label for="redemption_field" class="form-label"> Redemption field (Incomm)</label>
      <input type="text" id="redemption_field" name="redemption_field" class="form-control">
      <div class="invalid-feedback" id="error_redemption_field"></div>
    </div> -->


    <!-- <div class="col-md-12">
      <label for="validade" class="form-label"> Validade (Incomm)</label>
      <input type="text" id="validade" name="validade" class="form-control">
      <div class="invalid-feedback" id="error_validade"></div>
    </div> -->

    <!-- <div class="col-md-12 supported-languages">
      <label class="form-label">Supported Languages</label>


        <div class="row">

        </div>

    </div> -->
    <!-- <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center">
        <label for="terms_and_conditions" class="form-label mb-0">
          Terms & Conditions
        </label>
        <button type="button" id="translateTermsConditionBtn" class="btn btn-sm btn-secondary ">
          Translate to pt-BR, es-419
        </button>
      </div>

      <div id="legalTextsContainer" class="mt-2">
      </div>
    </div> -->


  </div>

  <div class="row mt-4">
    <div class="col d-flex justify-content-end">
      <button type="submit" class="btn btn-primary">Update Summary</button>
    </div>
  </div>
</form>


<!-- <div id="legalTextsTemplate" class="d-none">
  <div class="card mb-3 legalTexts-block">
    <div class="card-body">
      <input type="hidden" name="localizations[id][]" class="legal_text_id" value="">
      <input type="hidden" name="localizations[locale][]" class="locale-lang-code" value="">

      <div class="col-12">
        <label class="form-label">Language ()</label>
        <div data-name="localizations[legal_texts][]" class="editor"></div>
        <textarea name="localizations[legal_texts][]" class="d-none"></textarea>

        <div class="invalid-feedback"></div>
      </div>
    </div>


  </div>
</div> -->

