<!-- Localization Form -->
<form id="ratingForm" class="rating" data-tab="rating" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="rating">
  <div class="row g-3">

    <!-- <div class="col-md-6">
      <label for="average_rating" class="form-label">
        Play Purple Index (PPI)
      </label>
      <div class="input-group">
        <input type="number" step="any" min="0" id="average_rating" name="average_rating" class="form-control">
        <button type="button" id="generateRatingBtn" class="btn btn-secondary">
          Generate PPI
        </button>
      </div>
      <div class="invalid-feedback" id="error_average_rating"></div>
    </div> -->

    <!-- <div class="col-md-6">
      <label for="total_reviews" class="form-label">Total Reviews</label>
      <input type="number" step="any" min="0" id="total_reviews" name="total_reviews" class="form-control">
      <div class="invalid-feedback" id="error_total_reviews"></div>
    </div> -->

    @if($product->source == '1' || $product->source == '3' || $product->source == '4')

    <div class="row mt-4">
      <div class="col">
        <label class="form-label">PEGI Ratings</label>
        <div class="d-flex flex-wrap gap-2">
          @php
            $ratings = $product->pegi_ratings_formatted ?? [];
          @endphp

          @if(count($ratings) > 0)
            @foreach($ratings as $rating)
              <div class="text-center" style="width: 100px;">
                <img
                  src="{{ $rating['logo'] }}"
                  alt="{{ $rating['text'] }}"
                  title="{{ $rating['text'] }}"
                  class="img-fluid mb-1"
                  style="max-height: 100px; object-fit: contain;"
                >

              </div>
            @endforeach
          @else
            <div>No ratings available</div>
          @endif
        </div>
      </div>
    </div>

    @endif

  </div>

  @if($product->source == '1' || $product->source == '3' || $product->source == '4')
  <div class="row mt-4">
    <div class="col d-flex justify-content-end">
      <button type="submit" class="btn btn-primary">Update rating</button>
    </div>
  </div>
  @endif
</form>

