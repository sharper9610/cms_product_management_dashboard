

<form  class="tagForm" data-tab="basic_info" id="tagForm" >
  @csrf
  @method('PUT')
  <input type="hidden"  name="tab_name" value="tag">
  <div class="p-3 overflow-auto">
    <h5 class="mb-3">SEO Tags</h5>

{{--    <div class="col-md-12">--}}

{{--      <input type="text" id="tags" name="tags" class="form-control" placeholder="Type a tag and press Enter or comma">--}}
{{--     --}}
{{--     --}}
{{--     --}}
{{--      <div class="form-text">--}}
{{--        Type a tag and press Enter or comma to add. Example: Action, Adventure, RPG, Multiplayer, Strategy--}}
{{--      </div>--}}
{{--      <div class="invalid-feedback" id="error_tags"></div>--}}

{{--    </div>--}}

    <div class="col-md-12 seo-tags-show">
      <div class="row">

      </div>
    </div>

    <div class="row mt-4">
      <div class="col d-flex justify-content-end gap-2">
        <!-- Generate Tags Button -->
        <button type="button" class="btn btn-secondary generateTagsBtn" id="">

          Generate Tags with AI
        </button>

        <!-- Update Tags Button -->
        <button type="submit" class="btn btn-primary">
          Update Tags
        </button>
      </div>
    </div>
  </div>

</form>

