@php
  $configData = Helper::appClasses();
@endphp
  <!-- Create App Modal -->
<div class="modal fade" id="createApp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-simple modal-dialog-centered modal-simple modal-upgrade-plan">
    <div class="modal-content">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        {{--        <div class="">--}}
        {{--          <h4 class="mb-2">Edit Product: Street Fighter™ 6</h4>--}}
        {{--          <p class="mb-6">Provide data with this form to create your app.</p>--}}
        {{--        </div>--}}


        <div class="mb-4">
          <h4 class="mb-2">Edit Product: Street Fighter™ 6</h4>
          <p class="mb-3 text-muted">
            <span class="fw-semibold">SKU:</span> 77754 •
            <span class="fw-semibold ms-2">Publisher:</span> Capcom •
            <span class="fw-semibold ms-2">Genre:</span> Fighting •
            <span class="fw-semibold ms-2">Released:</span> 6/1/2023
          </p>
        </div>

        <!-- Property Listing Wizard -->
        <div id="wizard-create-app" class="bs-stepper vertical wizard-vertical-icons mt-2 shadow-none">
          <div class="bs-stepper-header border-0 p-1">
            <div class="step" data-target="#basic_info">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-file-text-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Basic info</span>
                  <small class="bs-stepper-subtitle">Enter Details</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#media">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-star-smile-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Media</span>
                  <small class="bs-stepper-subtitle"> </small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#localizations">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-pie-chart-2-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Localizations</span>
                  <small class="bs-stepper-subtitle">Select Database</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#pricing">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-pie-chart-2-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Pricing</span>
                  <small class="bs-stepper-subtitle">Select Pricing</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#countries">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-bank-card-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Countries</span>
                  <small class="bs-stepper-subtitle">Country Details</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#tags">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-bank-card-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Tags</span>
                  <small class="bs-stepper-subtitle">Tags</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#ratings">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-bank-card-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Ratings</span>
                  <small class="bs-stepper-subtitle">Ratings</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#systemRequirements">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-bank-card-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">System Requirements</span>
                  <small class="bs-stepper-subtitle">System Requirements</small>
                </span>
              </button>
            </div>
            <div class="step" data-target="#submit">
              <button type="button" class="step-trigger">
                <span class="avatar">
                  <span class="avatar-initial rounded-1">
                    <i class="icon-base ri ri-check-double-line icon-24px"></i>
                  </span>
                </span>
                <span class="bs-stepper-label flex-column align-items-start gap-1 ps-1 ms-3">
                  <span class="bs-stepper-title text-uppercase">Submit</span>
                  <small class="bs-stepper-subtitle">Submit</small>
                </span>
              </button>
            </div>
          </div>
          <div class="bs-stepper-content p-1">
            <form onSubmit="return false">

              <div id="basic_info" class="content pt-4 pt-lg-0">
                <!-- Product Name -->
                <div class="form-floating form-floating-outline mb-4">
                  <input type="text" class="form-control form-control-lg" id="productName" placeholder="Product Name"/>
                  <label for="productName">Product Name</label>
                  <div class="form-text">This will also update the main product name</div>
                </div>

                <div class="row g-4">
                  <!-- Genre -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <select class="form-select" id="genre">
                        <option value="">Select Genre</option>
                        <option value="Action RPG">Action RPG</option>
                        <option value="Fantasy RPG">Fantasy RPG</option>
                        <option value="First-Person Shooter">First-Person Shooter</option>
                        <option value="Sports Simulation">Sports Simulation</option>
                        <option value="Strategy">Strategy</option>
                        <option value="Racing">Racing</option>
                        <option value="Adventure">Adventure</option>
                        <option value="Puzzle">Puzzle</option>
                        <option value="Fighting">Fighting</option>
                        <option value="Horror">Horror</option>
                        <option value="Virtual Currency">Virtual Currency</option>
                      </select>
                      <label for="genre">Genre</label>
                    </div>
                  </div>

                  <!-- Category -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <select class="form-select" id="category">
                        <option value="">Select Category</option>
                        <option value="Game">Game</option>
                        <option value="Gift Card">Gift Card</option>
                        <option value="DLC">DLC</option>
                        <option value="Subscription">Subscription</option>
                      </select>
                      <label for="category">Category</label>
                    </div>
                  </div>
                </div>

                <div class="row g-4 mt-0">
                  <!-- Supplier -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <select class="form-select" id="supplier">
                        <option value="ztorm">Ztorm</option>
                        <option value="incomm">InComm</option>
                        <option value="GCL">GCL</option>
                      </select>
                      <label for="supplier">Supplier</label>
                    </div>
                  </div>

                  <!-- Release Date -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <input type="date" class="form-control" id="releaseDate" placeholder="Release Date"/>
                      <label for="releaseDate">Release Date</label>
                    </div>
                  </div>
                </div>

                <div class="row g-4 mt-0">
                  <!-- Platform -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <select class="form-select" id="platform">
                        <option value="">Select Platform</option>
                        <option value="Steam">Steam</option>
                        <option value="Epic Games Store">Epic Games Store</option>
                        <option value="Microsoft Store">Microsoft Store</option>
                        <option value="PlayStation Store">PlayStation Store</option>
                        <option value="GOG">GOG (DRM-free)</option>
                        <option value="Battle.net">Battle.net</option>
                        <option value="EA App">EA App</option>
                        <option value="Ubisoft Connect">Ubisoft Connect</option>
                        <option value="Origin">Origin</option>
                        <option value="DRM-Free">DRM-Free</option>
                      </select>
                      <label for="platform">Platform</label>
                    </div>
                  </div>

                  <!-- Publisher -->
                  <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                      <select class="form-select" id="publisher">
                        <option value="">Select Publisher</option>
                        <option value="CD Projekt RED">CD Projekt RED</option>
                        <option value="EA Sports">EA Sports</option>
                        <option value="Activision">Activision</option>
                        <option value="Ubisoft">Ubisoft</option>
                        <option value="Sony Interactive Entertainment">Sony Interactive Entertainment</option>
                        <option value="Microsoft Studios">Microsoft Studios</option>
                        <option value="Nintendo">Nintendo</option>
                        <option value="Take-Two Interactive">Take-Two Interactive</option>
                        <option value="Square Enix">Square Enix</option>
                        <option value="Bethesda Softworks">Bethesda Softworks</option>
                      </select>
                      <label for="publisher">Publisher</label>
                    </div>
                  </div>
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex justify-content-between mt-5">
                  <button class="btn btn-outline-secondary btn-prev" disabled>
                    <i class="ri ri-arrow-left-line me-2"></i> Previous
                  </button>
                  <button class="btn btn-primary btn-next">
                    Next <i class="ri ri-arrow-right-line ms-2"></i>
                  </button>
                </div>
              </div>
              <!-- Frameworks -->
              <div id="media" class="content pt-4 pt-lg-0">
                <!-- Main Image -->
                <div class="mb-4">
                  <label for="mainImage" class="form-label fw-semibold">Main Image URL</label>
                  <div class="input-group">
                    <input type="url" id="mainImage" class="form-control" placeholder="https://example.com/image.jpg"/>

                  </div>
                  <div class="form-text">Provide a direct link to the main product image</div>
                </div>

                <!-- Additional Images -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-semibold mb-0">Additional Images</label>
                    <button type="button" class="btn btn-sm btn-outline-primary">
                      <i class="ri ri-add-line me-1"></i> Add Image
                    </button>
                  </div>

                  <!-- Example Additional Image Item -->
                  <div class="card mb-3 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                      <div class="flex-grow-1 me-2">
                        <input type="url" class="form-control" placeholder="https://example.com/image.jpg"/>
                      </div>
                      <button type="button" class="btn btn-outline-danger btn-sm">
                        <i class="ri ri-delete-bin-6-line"></i>
                      </button>
                    </div>
                  </div>


                </div>

                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>

              <!-- Database -->
              <div id="localizations" class="content pt-4 pt-lg-0">

                <!-- EN - English -->
                <div class="card mb-4 border shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title mb-4">EN - English</h5>

                    <!-- Localized Name -->
                    <div class="form-floating form-floating-outline mb-4">
                      <input type="text" class="form-control" id="localizedNameEn" placeholder="Localized Name">
                      <label for="localizedNameEn">Localized Name</label>
                    </div>

                    <!-- Short Description -->
                    <div class="form-floating form-floating-outline mb-4">
                      <textarea class="form-control" id="shortDescriptionEn" placeholder="Brief product description..."
                                style="height: 80px"></textarea>
                      <label for="shortDescriptionEn">Short Description</label>
                    </div>

                    <!-- Long Description -->
                    <div class="form-floating form-floating-outline mb-0">
                      <textarea class="form-control" id="longDescriptionEn"
                                placeholder="Detailed product description..." style="height: 150px"></textarea>
                      <label for="longDescriptionEn">Long Description</label>
                    </div>
                  </div>
                </div>

                <!-- PT - Portuguese -->
                <div class="card mb-4 border shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title mb-4">PT - Portuguese</h5>

                    <div class="form-floating form-floating-outline mb-4">
                      <input type="text" class="form-control" id="localizedNamePt" placeholder="Localized Name">
                      <label for="localizedNamePt">Localized Name</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-4">
                      <textarea class="form-control" id="shortDescriptionPt" placeholder="Brief product description..."
                                style="height: 80px"></textarea>
                      <label for="shortDescriptionPt">Short Description</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-0">
                      <textarea class="form-control" id="longDescriptionPt"
                                placeholder="Detailed product description..." style="height: 150px"></textarea>
                      <label for="longDescriptionPt">Long Description</label>
                    </div>
                  </div>
                </div>

                <!-- ES - Spanish -->
                <div class="card mb-4 border shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title mb-4">ES - Spanish</h5>

                    <div class="form-floating form-floating-outline mb-4">
                      <input type="text" class="form-control" id="localizedNameEs" placeholder="Localized Name">
                      <label for="localizedNameEs">Localized Name</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-4">
                      <textarea class="form-control" id="shortDescriptionEs" placeholder="Brief product description..."
                                style="height: 80px"></textarea>
                      <label for="shortDescriptionEs">Short Description</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-0">
                      <textarea class="form-control" id="longDescriptionEs"
                                placeholder="Detailed product description..." style="height: 150px"></textarea>
                      <label for="longDescriptionEs">Long Description</label>
                    </div>
                  </div>
                </div>


                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>

              <!-- billing -->


              <div id="pricing" class="content pt-4 pt-lg-0">
                <div class="card mb-4 border shadow-sm">

                  <!-- BRL - Brazilian Real -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">BRL - Brazilian Real</h5>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Ztorm Price (BRL)</label>
                          <input type="number" class="form-control" value="89.90">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Steam Price (BRL)</label>
                          <input type="number" class="form-control" value="95.90">
                        </div>
                      </div>
                      <div class="mt-4">
                        <label class="form-label fw-semibold">Compare Price (BRL)</label>
                        <input type="number" class="form-control" value="179.80">
                      </div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Promo Start Date</label>
                          <input type="date" class="form-control" value="2024-01-15">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Promo End Date</label>
                          <input type="date" class="form-control" value="2024-01-22">
                        </div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">BRL 179.80</span> <span
                            class="fw-bold text-primary ms-2">BRL 89.90</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">BRL 95.90</span></div>
                        <div class="text-success small fw-semibold">Save BRL 89.90 on Ztorm</div>
                        <div class="text-warning small">Steam is BRL 6.00 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- MXN - Mexican Peso -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">MXN - Mexican Peso</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (MXN)</label><input
                            type="number" class="form-control" value="549.90"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (MXN)</label><input
                            type="number" class="form-control" value="599.90"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (MXN)</label><input
                          type="number" class="form-control" value="1099.90"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">MXN 1099.90</span> <span
                            class="fw-bold text-primary ms-2">MXN 549.90</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">MXN 599.90</span></div>
                        <div class="text-success small fw-semibold">Save MXN 550.00 on Ztorm</div>
                        <div class="text-warning small">Steam is MXN 50.00 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- CLP - Chilean Peso -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">CLP - Chilean Peso</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (CLP)</label><input
                            type="number" class="form-control" value="19990"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (CLP)</label><input
                            type="number" class="form-control" value="20990"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (CLP)</label><input
                          type="number" class="form-control" value="39990"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">CLP 39,990</span> <span
                            class="fw-bold text-primary ms-2">CLP 19,990</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">CLP 20,990</span></div>
                        <div class="text-success small fw-semibold">Save CLP 20,000 on Ztorm</div>
                        <div class="text-warning small">Steam is CLP 1,000 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- COP - Colombian Peso -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">COP - Colombian Peso</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (COP)</label><input
                            type="number" class="form-control" value="79900"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (COP)</label><input
                            type="number" class="form-control" value="84900"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (COP)</label><input
                          type="number" class="form-control" value="159900"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">COP 159,900</span> <span
                            class="fw-bold text-primary ms-2">COP 79,900</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">COP 84,900</span></div>
                        <div class="text-success small fw-semibold">Save COP 80,000 on Ztorm</div>
                        <div class="text-warning small">Steam is COP 5,000 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- PEN - Peruvian Sol -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">PEN - Peruvian Sol</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (PEN)</label><input
                            type="number" class="form-control" value="89.90"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (PEN)</label><input
                            type="number" class="form-control" value="95.90"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (PEN)</label><input
                          type="number" class="form-control" value="179.80"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">PEN 179.80</span> <span
                            class="fw-bold text-primary ms-2">PEN 89.90</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">PEN 95.90</span></div>
                        <div class="text-success small fw-semibold">Save PEN 89.90 on Ztorm</div>
                        <div class="text-warning small">Steam is PEN 6.00 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- UYU - Uruguayan Peso -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">UYU - Uruguayan Peso</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (UYU)</label><input
                            type="number" class="form-control" value="599.90"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (UYU)</label><input
                            type="number" class="form-control" value="649.90"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (UYU)</label><input
                          type="number" class="form-control" value="1199.90"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">UYU 1199.90</span> <span
                            class="fw-bold text-primary ms-2">UYU 599.90</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">UYU 649.90</span></div>
                        <div class="text-success small fw-semibold">Save UYU 600.00 on Ztorm</div>
                        <div class="text-warning small">Steam is UYU 50.00 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- CRC - Costa Rican Colón -->
                  <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                      <h5 class="card-title mb-4">CRC - Costa Rican Colón</h5>
                      <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Ztorm Price (CRC)</label><input
                            type="number" class="form-control" value="14990"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Steam Price (CRC)</label><input
                            type="number" class="form-control" value="15990"></div>
                      </div>
                      <div class="mt-4"><label class="form-label fw-semibold">Compare Price (CRC)</label><input
                          type="number" class="form-control" value="29990"></div>
                      <div class="row g-3 mt-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo Start Date</label><input
                            type="date" class="form-control" value="2024-01-15"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Promo End Date</label><input
                            type="date" class="form-control" value="2024-01-22"></div>
                      </div>
                      <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-semibold mb-2">Price Preview</h6>
                        <div><span class="fw-semibold">Ztorm:</span> <span
                            class="text-muted text-decoration-line-through">CRC 29,990</span> <span
                            class="fw-bold text-primary ms-2">CRC 14,990</span></div>
                        <div><span class="fw-semibold">Steam:</span> <span
                            class="fw-bold text-success ms-2">CRC 15,990</span></div>
                        <div class="text-success small fw-semibold">Save CRC 15,000 on Ztorm</div>
                        <div class="text-warning small">Steam is CRC 1,000 more expensive</div>
                        <div class="alert alert-warning p-2 mt-3 mb-0"><strong>Promotion Period:</strong> 1/15/2024 -
                          1/22/2024 (7 days)
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Pricing Guidelines -->
                  <div class="alert alert-info mt-4">
                    <h6 class="fw-semibold text-primary mb-2">Pricing Guidelines</h6>
                    <ul class="mb-0 small">
                      <li>• Compare price should be higher than regular price to show savings</li>
                      <li>• BRL pricing typically ranges from R$29.90 - R$199.90 for games</li>
                      <li>• Consider local purchasing power when setting regional prices</li>
                      <li>• Chilean/Colombian pesos use larger numbers (no decimals typically)</li>
                      <li>• Promotions typically run for 7 days (1 week)</li>
                      <li>• Set promo dates to track promotion duration and scheduling</li>
                    </ul>
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>


              <div id="countries" class="content pt-4 pt-lg-0">


                <div class="card mb-4 border shadow-sm p-5">
                  <div id="allowed-countries" class="mb-4">
                    <label class="form-label fw-medium mb-2">Allowed Countries</label>
                    <p class="text-muted small mb-3">Select the countries where this product can be sold</p>

                    <div class="row g-3">
                      <!-- Brazil -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryBrazil" checked>
                        <label class="form-check-label" for="countryBrazil">Brazil</label>
                      </div>
                      <!-- Mexico -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryMexico" checked>
                        <label class="form-check-label" for="countryMexico">Mexico</label>
                      </div>
                      <!-- Chile -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryChile" checked>
                        <label class="form-check-label" for="countryChile">Chile</label>
                      </div>
                      <!-- Colombia -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryColombia" checked>
                        <label class="form-check-label" for="countryColombia">Colombia</label>
                      </div>
                      <!-- Peru -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryPeru" checked>
                        <label class="form-check-label" for="countryPeru">Peru</label>
                      </div>
                      <!-- Uruguay -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryUruguay">
                        <label class="form-check-label" for="countryUruguay">Uruguay</label>
                      </div>
                      <!-- Costa Rica -->
                      <div class="col-6 col-md-4 form-check form-check-custom form-check-primary">
                        <input class="form-check-input" type="checkbox" id="countryCostaRica" checked>
                        <label class="form-check-label" for="countryCostaRica">Costa Rica</label>
                      </div>
                    </div>

                    <!-- Selected Countries Summary -->
                    <div class="mt-4 p-3 bg-light border rounded">
                      <p class="small text-muted mb-2">Selected countries:</p>
                      <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary">Costa Rica</span>
                        <span class="badge bg-primary">Colombia</span>
                      </div>
                      <p class="text-primary small mt-2">Total: 2 countries selected</p>
                    </div>
                  </div>

                  <!-- Regional Considerations -->
                  <div class="mt-4 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                    <h6 class="fw-semibold text-warning mb-2">Regional Considerations</h6>
                    <ul class="small text-warning mb-0">
                      <li>Latin American markets have varying economic conditions affecting pricing</li>
                      <li>Consider local payment methods and currency fluctuations</li>
                      <li>Some countries may have import restrictions or taxes</li>
                      <li>Language localization important for Spanish/Portuguese markets</li>
                    </ul>
                  </div>

                </div>


                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>
              <div id="tags" class="content pt-4 pt-lg-0">

                <div class="card mb-4 shadow-sm p-4">
                  <!-- Header -->
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-semibold mb-0">Product Tags</label>
                    <button type="button" class="btn btn-sm btn-primary d-flex align-items-center">
                      <span class="me-1">+</span> Add Tag
                    </button>
                  </div>

                  <!-- Tag Input Rows -->
                  <div class="mb-2 d-flex gap-2 align-items-center">
                    <input type="text" class="form-control" placeholder="e.g., top_seller, action_game, multiplayer">
                    <button type="button" class="btn btn-outline-danger btn-sm">Remove</button>
                  </div>
                  <div class="mb-2 d-flex gap-2 align-items-center">
                    <input type="text" class="form-control" placeholder="e.g., top_seller, action_game, multiplayer">
                    <button type="button" class="btn btn-outline-danger btn-sm">Remove</button>
                  </div>

                  <!-- Common Tags -->
                  <div class="mt-3 p-3 bg-light rounded">
                    <p class="small text-muted mb-2">Common tags:</p>
                    <div class="d-flex flex-wrap gap-2">
                      <button type="button" class="btn btn-sm btn-outline-secondary">top_seller</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">staff_pick</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">new_release</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">on_sale</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">multiplayer</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">single_player</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">action_game</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">rpg</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary">strategy</button>
                    </div>
                  </div>
                </div>


                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>
              <div id="ratings" class="content">
                <div class="card mb-4 shadow-sm p-4">
                  <!-- Inputs Row -->
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Average Rating</label>
                      <input type="number" min="0" max="5" step="0.1" class="form-control" placeholder="4.8" value="">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Total Reviews</label>
                      <input type="number" min="0" class="form-control" placeholder="200" value="">
                    </div>
                  </div>

                  <!-- Rating Preview -->
                  <div class="p-3 bg-light rounded">
                    <h6 class="fw-medium text-primary mb-2">Rating Preview</h6>
                    <div class="d-flex align-items-center gap-2">
                      <!-- Stars -->
                      <div class="d-flex">
                        <span class="text-muted fs-5">★</span>
                        <span class="text-muted fs-5">★</span>
                        <span class="text-muted fs-5">★</span>
                        <span class="text-muted fs-5">★</span>
                        <span class="text-muted fs-5">★</span>
                      </div>
                      <!-- Reviews Text -->
                      <small class="text-muted">0 stars (0 reviews)</small>
                    </div>
                  </div>
                </div>


                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>
              <div id="systemRequirements" class="content">

                <div class="card mb-4 shadow-sm p-4">
                  <!-- English Section -->
                  <div class="mb-4 border rounded p-3">
                    <h5 class="fw-medium mb-3">EN - English</h5>
                    <div class="mb-3">
                      <label class="form-label">Minimum Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 10, Intel i5, 8GB RAM, GTX 1060"></textarea>
                    </div>
                    <div>
                      <label class="form-label">Recommended Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 11, Intel i7, 16GB RAM, RTX 3070"></textarea>
                    </div>
                  </div>

                  <!-- Portuguese Section -->
                  <div class="mb-4 border rounded p-3">
                    <h5 class="fw-medium mb-3">PT - Portuguese</h5>
                    <div class="mb-3">
                      <label class="form-label">Minimum Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 10, Intel i5, 8GB RAM, GTX 1060"></textarea>
                    </div>
                    <div>
                      <label class="form-label">Recommended Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 11, Intel i7, 16GB RAM, RTX 3070"></textarea>
                    </div>
                  </div>

                  <!-- Spanish Section -->
                  <div class="mb-4 border rounded p-3">
                    <h5 class="fw-medium mb-3">ES - Spanish</h5>
                    <div class="mb-3">
                      <label class="form-label">Minimum Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 10, Intel i5, 8GB RAM, GTX 1060"></textarea>
                    </div>
                    <div>
                      <label class="form-label">Recommended Requirements</label>
                      <textarea rows="3" class="form-control" placeholder="Windows 11, Intel i7, 16GB RAM, RTX 3070"></textarea>
                    </div>
                  </div>

                  <!-- Guidelines Section -->
                  <div class="p-3 bg-light rounded">
                    <h6 class="fw-medium text-primary mb-2">System Requirements Guidelines</h6>
                    <ul class="small text-primary mb-0">
                      <li>Minimum requirements should allow the game to run at lowest settings</li>
                      <li>Recommended requirements should provide optimal gaming experience</li>
                      <li>Include OS, CPU, RAM, and GPU specifications</li>
                      <li>Consider localizing technical terms for different markets</li>
                      <li>Gift cards and virtual currency only need platform/account requirements</li>
                      <li>Focus on internet connectivity for digital products</li>
                    </ul>
                  </div>
                </div>


                <div class="col-12 d-flex justify-content-between mt-6">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-sm-block d-none ms-2">Previous</span></button>
                  <button class="btn btn-primary btn-next"><span class="align-middle d-sm-block d-none me-2">Next</span>
                    <i class="icon-base ri ri-arrow-right-line icon-16px"></i></button>
                </div>
              </div>

              <!-- submit -->
              <div id="submit" class="content text-center pt-4 pt-lg-0">
                <h5 class="mb-1 mt-4">Submit</h5>
                <p class="small">Submit to kick start your project.</p>
                <!-- image -->
                <img src="{{ asset('assets/img/illustrations/illustration-john.png') }}" alt="Create App img"
                     width="265" class="img-fluid"/>
                <div class="col-12 d-flex justify-content-between mt-4 pt-2">
                  <button class="btn btn-outline-secondary btn-prev"><i
                      class="icon-base ri ri-arrow-left-line icon-16px"></i> <span
                      class="align-middle d-none d-sm-block ms-2">Previous</span></button>
                  <button class="btn btn-success btn-submit me-2"><span
                      class="align-middle d-none d-sm-block me-sm-1_5">Submit</span><i
                      class="icon-base ri ri-check-line icon-16px"></i></button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!--/ Property Listing Wizard -->
    </div>
  </div>
</div>
<!--/ Create App Modal -->
