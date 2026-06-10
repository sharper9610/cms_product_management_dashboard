
<input type="hidden" name="tab_name" value="prices">
<div class="p-3" >
  <div class="d-flex justify-content-between align-items-center mb-3">

    @if($product->source == '1' || $product->source == '3' || $product->source == '4')
      <h5>Prices</h5>
    @elseif($product->source == '2')

      <div>
        <h5>
          Prices
          @if($product->min_value && $product->max_value)
            <small class="text-muted">
              (Allowed range: {{ number_format($product->min_value, 2) }} – {{ number_format($product->max_value, 2) }})
            </small>
          @endif
        </h5>
        <div class="input-group input-group-sm" style="width: 400px;">
          <span class="input-group-text">Commission %</span>

          <input
            type="number"
            class="form-control"
            name="merchant_commission_percentage"
            placeholder="Commission"
            id="merchantCommission"
            min="0"
            max="100"
            step="0.01"
            aria-label="Merchant Commission Percentage"
          >


          <button class="btn btn-primary"
                  type="button"
                  data-product-id="{{ $product->id }}"
                  id="saveCommission" title="Save Commission">
           Update
          </button>


        </div>

      </div>

    @endif



    @if($product->source=='2')
          <button type="button" class="btn btn-primary btn-sm" id="addPrice">
            <i class="bi bi-plus"></i> Add New Price
          </button>
    @endif
  </div>

  <div id="priceContainer">

  </div>

  <!-- Hidden template for JS -->
  <div id="priceTemplate" class="d-none">
    <div class="card mb-3 price-block">
      <div class="card-body">

        @if($product->source=='1' || $product->source=='3' || $product->source=='4')

          <div class="row g-3 align-items-center">
            <table>
              <thead>
              <tr>
                <th>  Country code</th>
                <th>  Currency</th>
                <th>  Ztorm Price</th>
                <th>  Steam Price</th>
                <th>  In-Stock Price</th>
                <th>  Discount</th>


              </tr>
              </thead>

              <tbody>
              <tr>

              </tr>
              </tbody>
            </table>

          </div>



        @elseif($product->source=='2')

          <div class="row g-3 align-items-center">
            <table>
              <thead>
              <tr>
                <th>  Country code</th>
                <th>  Currency</th>
                <th>  Price</th>
                <th>  Discount</th>
              </tr>
              </thead>

              <tbody>
              <tr>

              </tr>
              </tbody>
            </table>

          </div>



        @endif


      </div>
    </div>
  </div>

</div>

<!-- Price Edit Modal -->


