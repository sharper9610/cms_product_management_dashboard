
  <div class="p-3 overflow-auto" style="" id="allowed_countries_div">
    {{-- For Genba (source 4) --}}
    @if($product->source === 4)
      <!-- <h5 class="mb-3">Allowed Countries (Whitelist)</h5>
      <div class="mb-4">
        <label for="whitelistCountries" class="form-label">Whitelisted Country Codes</label>
        <textarea
          id="whitelistCountries"
          name="whitelist"
          rows="4"
          class="form-control"
          readonly
          placeholder=""></textarea>
      </div>

      <h5 class="mb-3">Disallowed Countries (Blacklist)</h5>
      <div class="mb-3">
        <label for="blacklistCountries" class="form-label">Blacklisted Country Codes</label>
        <textarea
          id="blacklistCountries"
          name="blacklist"
          rows="4"
          class="form-control"
          readonly
          placeholder=""></textarea>
      </div> -->


      <h5 class="mb-3">Allowed Countries</h5>
      <div class="mb-3">
        <label for="allowedCountries" class="form-label">Country Codes</label>
        <textarea
          id="allowedCountries"
          name="allowed_countries"
          rows="4"
          class="form-control"
          readonly
          placeholder=""></textarea>
      </div>
    @else
      {{-- For other sources --}}
      <h5 class="mb-3">Allowed Countries</h5>
      <div class="mb-3">
        <label for="allowedCountries" class="form-label">Country Codes</label>
        <textarea
          id="allowedCountries"
          name="allowed_countries"
          rows="4"
          class="form-control"
          readonly
          placeholder=""></textarea>
      </div>
    @endif
  </div>

