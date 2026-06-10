<form class="tabForm" data-tab="basic_info" id="basic_info">
  @csrf
  @method('PUT')
  <input type="hidden" name="tab_name" value="basic_info">
  <div class="row g-3">

    <!-- Product Name -->
    <div class="col-md-9">
      <label for="name" class="form-label">Product Name *</label>
      <input type="text" id="name" name="name" class="form-control">
      <div class="invalid-feedback" id="error_name"></div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Ignore update</label>
      <div class="form-check form-switch switch-lg">
        <input
          class="form-check-input"
          type="checkbox"
          id="ignore_update"
          name="ignore_update"
          value="1"
        >
        <label class="form-check-label" for="ignore_update">
          Ignore update
        </label>
      </div>
      <div class="invalid-feedback" id="error_ignore_update"></div>
    </div>

    <div class="col-md-12" id="localizedTitlesContainer">
    </div>


    <div class="col-md-12">
      <label for="product_url_title" class="form-label">Product URL Title</label>
      <input type="text" id="product_url_title" name="product_url_title" class="form-control">
      <div class="invalid-feedback" id="error_product_url_title"></div>
    </div>

    <!-- <div class="col-md-12 tags-show">
      <div class="row">

      </div>
    </div> -->


    <div class="col-md-4">
      <label for="supplier" class="form-label">Supplier</label>
      <select id="supplier" name="supplier" class="form-select">
        @if($product->source==1)
          <option value="1">Ztorm</option>
        @elseif($product->source==2)
          <option value="2">InComm</option>
        @elseif($product->source==3)
          <option value="3">Point Nexus</option>
        @elseif($product->source==4)
          <option value="4">Genba</option>
        @endif
      </select>
      <div class="invalid-feedback" id="error_supplier"></div>
    </div>


    @if($product->source==2)
      <div class="col-md-4">
        <label for="release_date" class="form-label">Release Date</label>
        <input type="text" name="release_date" id="release_date " placeholder="YYYY-MM-DD"
               class="form-control release_date_flatpickr"  >
        <div class="invalid-feedback" id="error_release_date"></div>
      </div>



      <!-- <div class="col-md-4">
        <label for="download_date" class="form-label">Download Date </label>

        <input type="text" id="download_date" name="download_date"
               placeholder="YYYY-MM-DD"
               class="form-control order_date_flatpickr">
        <div class="invalid-feedback" id="error_download_date"></div>
      </div> -->

    @else

      <div class="col-md-4">
        <label for="release_date" class="form-label">Release Date</label>
        <input type="text" name="release_date" id="release_date" placeholder="DD-MM-YYYY"
               class="form-control" disabled readonly>
        <div class="invalid-feedback" id="error_release_date"></div>
      </div>



      <!-- <div class="col-md-4">
        <label for="download_date" class="form-label">Download Date </label>

        <input type="text" id="download_date" name="download_date" class="form-control" disabled readonly>
        <div class="invalid-feedback" id="error_download_date"></div>
      </div> -->
    @endif


    <div class="col-md-4">
      <label for="platform" class="form-label">Platform</label>
      <input type="text" id="platform" name="platform" class="form-control">
      <div class="invalid-feedback" id="error_platform"></div>
    </div>

    <div class="col-md-4">
      <label for="publisher" class="form-label">Publisher</label>

      @if($product->source == 2)
        <select id="publisher" name="publisher" class="form-select">
          <option value="">Select Publisher</option>
          @foreach($publishers as $publisher)
            <option value="{{$publisher['name'] }}" >
              {{ $publisher['name'] ?? '' }}
            </option>
          @endforeach
        </select>
      @else
        <input type="text" id="publisher" name="publisher"
               value=""
               class="form-control">
      @endif

      <div class="invalid-feedback" id="error_publisher"></div>
    </div>





    <div class="col-md-4">
      <label for="product_type" class="form-label">Product Type</label>
      <input type="text" id="product_type" name="product_type" class="form-control">
      <div class="invalid-feedback" id="error_product_type"></div>
    </div>


    <!-- Developer -->
    <div class="col-md-12">
      <label for="developer" class="form-label">Developers</label>
      <input type="text" id="developers" name="developers" class="form-control">
      <div class="form-text">Type a developer and press Enter or comma to add (e.g., CAPCOM, Nintendo)
      </div>


      <div class="invalid-feedback" id="error_developer"></div>
    </div>

    @if($product->source === 2)
    <div class="col-md-12">
      <button
        class="btn btn-sm btn-outline-dark"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#jsonExampleCollapse"
        aria-expanded="false"
        aria-controls="jsonExampleCollapse">
        <i class="ri-code-s-slash-line me-1"></i> Display Key Format
      </button>

      <div class="collapse mt-3" id="jsonExampleCollapse">
        <div class="card card-body bg-light p-0">
          <pre class="bg-dark text-white p-3 rounded mb-0" style="max-height: 805px; overflow-y: auto;">
            <code>
              {
                "item": {
                  "transaction_code": "4ebcab2d-b5c1-4c08-b425-afcf91ac8ec9",
                  "transaction_date": "2022-01-20",
                  "magic_link": "https://giftcard-hmg.todo.gift/MnrdJTar6sjp71KqFY3EE2tWiF-zqJgHcefB",
                  "card_number": "0000014281767635",
                  "card_password": "",
                  "redemption_code": "9443315754",
                  "expire_date": null,
                  "external_partner_load_id": "1",
                  "metadata": [
                    {
                      "name": "ddpId",
                      "value": 123456
                    },
                    {
                      "name": "prodType",
                      "value": "pin_realtime"
                    },
                    {
                      "name": "serialNum",
                      "value": 654321
                    },
                    {
                      "name": "pin",
                      "value": "123ABC"
                    },
                    {
                      "name": "vsn",
                      "value": "ABC123"
                    }
                  ]
                }
              }
            </code>
          </pre>
        </div>
      </div>
    </div>
    @endif

    <!-- Product Type -->
    {{--    <div class="col-md-6">--}}
    {{--      <label for="product_type" class="form-label">Product Type</label>--}}
    {{--      <select id="product_type" name="product_type" class="form-select">--}}
    {{--        <option value="game">Game</option>--}}
    {{--        <option value="gift_card">Gift Card</option>--}}
    {{--        <option value="dlc">DLC</option>--}}
    {{--        <option value="subscription">Subscription</option>--}}
    {{--      </select>--}}
    {{--      <div class="invalid-feedback" id="error_product_type"></div>--}}
    {{--    </div>--}}



    <!-- Status -->
    <div class="col-md-6">
      <label for="status" class="form-label">Status</label>
      <select id="status" name="status" class="form-select">
        <option value="">Select status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <div class="invalid-feedback" id="error_status"></div>
    </div>


    <div class="col-md-6">
      <label for="default_language" class="form-label">Default language</label>
      <select id="default_language" name="default_language" class="form-select">
        <option value="">Select Language</option>

      </select>
      <div class="invalid-feedback" id="error_default_language"></div>
    </div>
{{--    <div class="col-md-4">--}}
{{--      <label for="skip_update" class="form-label">Skip update</label>--}}
{{--      <select id="skip_update" name="skip_update" class="form-select">--}}
{{--        <option value="">Select Skip update</option>--}}
{{--        <option value="1">Yes</option>--}}
{{--        <option value="0">No</option>--}}

{{--      </select>--}}
{{--      <div class="invalid-feedback" id="error_skip_update"></div>--}}
{{--    </div>--}}


  </div>


  <div class="row mt-4">
    <div class="col d-flex justify-content-end gap-2">
      <!-- Generate Tags Button -->
      <button type="button" class="btn btn-secondary generateTagsBtn">
        Generate with AI
      </button>

      <!-- Update Tags Button -->
      <button type="submit" class="btn btn-primary">
        Update Basic Info
      </button>
    </div>
  </div>
</form>
