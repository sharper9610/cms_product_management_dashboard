<!-- Generate Tags Modal -->


<div class="modal fade" id="generateTagsModal" tabindex="-1" aria-labelledby="generateTagsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl ">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="generateTagsModalLabel">Generate with prompt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row">

          <div class="col-lg-12">
            <h6 class="text-uppercase text-muted small mb-3">Select a Prompt</h6>

            <div class="d-flex align-items-start gap-2">
              <div class="flex-grow-1">
                <label for="promptSelect" class="form-label visually-hidden">Select Prompt</label>
                <select id="promptSelect" class="form-select ">
                  <option value="" selected>-- Choose a prompt --</option>
                  @foreach($prompts as $prompt)
                    <option
                      data-lang="en"
                      value="{{$prompt->id}}">{{$prompt->name}} (en)</option>
                    <option
                      data-lang="pt-br"
                      value="{{$prompt->id}}">{{$prompt->name}} (pt-br)</option>
                    <option
                      data-lang="es-419"
                      value="{{$prompt->id}}">{{$prompt->name}} (es-419)</option>
                  @endforeach
                </select>
                <div id="promptError" class="invalid-feedback mt-1">
                  Please select a prompt.
                </div>
              </div>


              <div>
                <button type="button" class="btn btn-primary d-flex align-items-center" id="fetchTagsBtn">
                  <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                  AI Generate
                </button>
              </div>
            </div>

          </div>




          <div class="col-lg-12 border-start ps-lg-4 mt-4">
            <h6 class="text-uppercase text-muted small">Generated Results</h6>

            <div id="generatedResultContainer" class="mt-3" style="min-height: 250px;">

              <div class="d-flex align-items-center justify-content-center h-100 text-muted fst-italic p-3 border rounded bg-light" style="min-height: 250px;">
                Generated results will appear here.
              </div>

            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
