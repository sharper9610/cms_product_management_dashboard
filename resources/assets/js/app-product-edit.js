/**
 * Page User List
 */


'use strict';

// After initializing Tagify


const fullToolbar = [
  [{font: []}, {size: []}],
  ['bold', 'italic', 'underline', 'strike'],
  [{color: []}, {background: []}],
  [{script: 'super'}, {script: 'sub'}],
  [{header: '1'}, {header: '2'}, 'blockquote', 'code-block'],
  [{list: 'ordered'}, {indent: '-1'}, {indent: '+1'}],
  [{direction: 'rtl'}, {align: []}],
  ['link', 'image', 'video', 'formula'],
  ['clean']
];

if (document.querySelector('#full-editor')) {
  window.fullEditor = new Quill('#full-editor', {
    bounds: '#full-editor',
    placeholder: 'System Requirements...',
    modules: {syntax: true, toolbar: fullToolbar},
    theme: 'snow'
  });
}


$(document).ready(function () {


  let productId = $('#product_id_get').val();
  const baseUrl = '/';

  // Make loader functions global
  window.showLoader = function () {
    const $container = $('.tab-content');
    if ($container.css('position') === 'static') {
      $container.css('position', 'relative');
    }
    if ($container.find('.ajax-loader').length === 0) {
      const loader = $(`
                <div class="ajax-loader" style="
                    position: absolute;
                    top: 0; left: 0;
                    width: 100%; height: 100%;
                    background: rgba(255,255,255,0.7);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                ">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Loading...</div>
                    </div>
                </div>
            `);
      $container.append(loader);
    }
  };

  window.hideLoader = function () {
    $('.tab-content').find('.ajax-loader').remove();
  };

  // Make loadProductData global
  window.loadProductData = function () {
    $.ajax({
      url: `${baseUrl}product-management/${productId}/edit-ajax`,
      type: 'GET',
      beforeSend: function () {
        window.showLoader();
      },
      complete: function () {
        setTimeout(() => window.hideLoader(), 400);
      },
      success: function (res) {

        var blockPage = res.block_page ?? false;

        if (blockPage) {
          const $container = $('.nav-tabs-shadow');

          // Hide the content
          $container.hide();

          // Remove any old message before appending
          $('#price-update-message').remove();

          // Add a centered H1 message
          $container.parent().append(`
            <div id="price-update-message" class="text-center py-5">
              <h6 class="text-danger fw-bold">
                Price updating, please try again after some time.
              </h6>
            </div>
          `);

          return true;
        } else {
          // Restore content and remove message
          $('#price-update-message').remove();
          $('.nav-tabs-shadow').show();
        }


        var product = res?.product ?? {};

        // renderSeoTags(res?.seo_localizations ?? [])
        // renderLocalizationsTags(res?.franchise_genre_localizations ?? [])
        $('#name').val(product.name);
        $('#product_url_title').val(product.seo_url_name);
        $('#merchantCommission').val(product.merchant_commission_percentage ?? '');

        $('#supplier').val(product.source);


        if (product.source === 2) {
          window.setProductDates({
            release_date: product.release_date_formatted ?? '',
            // download_date: product.download_date_formatted ?? ''
          });
        } else {

          $('#release_date').val(product.release_date_formatted ?? '');
          // $('#download_date').val(product.download_date_formatted ?? '');
        }


        $('#platform').val(product.platform);
        // $('#drm_type').val(product.platform);
        $('#publisher').val(product.publisher_name);
        $('#developers').val(product.developers?.Developer ?? '');
        // $('#product_type').val(product.product_type);
        $('#product_type').val('Game');
        $('#status').val(product.status_formatted ?? '');
        $('#default_language').val(product.default_language ?? '');
        $('#skip_update').val(product.skip_update ?? '');


        // $('#region_tag').val(product.region_tag ?? '');
        // $('#auxiliary_field').val(product.auxiliary_field ?? '');
        // $('#bundled_products').val(product.bundled_products ?? '');
        // $('#classification').val(product.classification ?? '');
        // $('#community_discussion').val(product.community_discussion ?? '');
        $('#dlc_master_product_id').val(product.dlc_master_product_id ?? '');
        $('#is_dlc').val(product.is_dlc ?? 0);
        $('#ignore_update').prop('checked', product.ignore_update == 1);
        renderLocalizedTitles(product.localizations ?? []);

        // Get ProductID and ensure it's an array
        let productIDs = product.dlc_products_formatted?.ProductID;
// If it's not an array, make it an array
        if (!Array.isArray(productIDs)) {
          productIDs = productIDs ? [productIDs] : [];
        }
// Join array elements with comma
        const productIDsString = productIDs.join(',');

// Set the input value
        $('#dlc_products').val(productIDs);


        // $('#face_value').val(product.face_value ?? '');
        // $('#redemption').val(product.redemption ?? '');
        // $('#redemption_field').val(product.redemption_field ?? '');
        $('#terms_and_conditions').val(product.terms_and_conditions ?? '');
        // $('#validade').val(product.validade ?? '');
        $('#interface').val(product.supported_languages_formatted?.interface ?? []);
        $('#full_audio').val(product.supported_languages_formatted?.full_audio ?? []);
        $('#subtitles').val(product.supported_languages_formatted?.subtitles ?? []);


        // $('#average_rating').val(product.average_rating ?? '');
        // $('#total_reviews').val(product.total_reviews ?? '');




        // Populate countries data - handles both Genba (whitelist/blacklist) and other sources (allowed_countries)
        if (window.populateCountriesData) {
          window.populateCountriesData(product);
        }




        // --- REVISED LOCALIZATION LOGIC ---
        const $localizationContainer = $('#localizationContainer');
        $localizationContainer.empty(); // Clear existing blocks

        if (product.localizations && product.localizations.length > 0) {
          product.localizations.forEach(loc => {
            const $clone = $('#localizationTemplate .localization-block').clone();

            $clone.find('input[name="localizations[id][]"]').val(loc.id);
            $clone.find('input[name="localizations[language_code][]"]').val(loc.locale);
            $clone.find('input[name="localizations[localized_name][]"]').val(loc.title);

            // Append the block to the DOM FIRST
            $localizationContainer.append($clone);

            // NOW, initialize Quill on the elements within the newly added block
            const shortDescEditor = $clone.find('[data-name="localizations[short_description][]"]')[0];
            const longDescEditor = $clone.find('[data-name="localizations[long_description][]"]')[0];

            // Pass the content directly to the initialization function
            window.initializeQuillInBlock(shortDescEditor, loc.short_description || '');
            window.initializeQuillInBlock(longDescEditor, loc.long_description || '');
          });
        }


        // --- REVISED LOCALIZATION LOGIC ---
        const $systemReqContainer = $('#systemReqContainer');
        $systemReqContainer.empty(); // Clear existing blocks

        if (product.system_requirement_items && product.system_requirement_items.length > 0) {
          product.system_requirement_items.forEach(loc => {
            const $clone = $('#systemReqTemplate .systemReq-block').clone();

            $clone.find('input[name="localizations[id][]"]').val(loc.id);
            $clone.find('input[name="localizations[locale][]"]').val(loc.locale);
            $clone.find('label.form-label').text(`Language (${loc.locale || 'N/A'})`);


            // Append the block to the DOM FIRST
            $systemReqContainer.append($clone);

            // NOW, initialize Quill on the elements within the newly added block
            const system_requirementEditor = $clone.find('[data-name="localizations[system_requirements][]"]')[0];

            // Pass the content directly to the initialization function
            window.initializeQuillInBlock(system_requirementEditor, loc.system_requirements || '');

          });
        }


        renderLegalTexts(res.legal_texts_localizations ?? [])


        window.toggleTranslateButtons();


        populateMedia(product?.media ?? [])

        function populateMedia(mediaArray) {
          const $container = $('#mediaContainer');
          $container.empty();

          if (!mediaArray || mediaArray.length === 0) return;

          mediaArray.sort((a, b) => b.is_main - a.is_main);

          mediaArray.forEach((media) => {
            // Mapping from DB media_type (1,2,3) to string ('image', 'videos', ...)
            let type = 'image';
            if (media.media_type === 2) type = 'videos';
            else if (media.media_type === 1) type = 'image';
            else if (media.media_type === 3) type = 'boxshot';
            else if (media.media_type === 4) type = 'screenshot';

            window.addMediaBlock({
              id: media.id,
              type: type,
              url: media.url,
              is_main: media.is_main,
              media_source: media.media_source,
              image_orientation: media.image_orientation,
            });
          });
        }

        // Make it accessible to the global scope if needed by other scripts
        window.populateMedia = populateMedia;


        populatePrices(product.prices ?? [], product?.skip_updates ?? []);
        renderSkipUpdateFields(product?.skip_updates ?? []);
        // renderSupportedLanguages(res?.supported_languages_Localizations ?? []);

        $('.tab-content').show();
      },
      error: function () {
        showToast('Something went wrong', 'Error!', 'text-warning', 10000);
      },
    });
  };


  function renderLocalizedTitles(localizations) {
    const container = document.getElementById('localizedTitlesContainer');
    container.innerHTML = '';

    const otherLocalizations = localizations.filter(loc => loc.locale !== 'en');

    if (otherLocalizations.length === 0) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.classList.add('border', 'border-dark-subtle', 'rounded', 'p-4', 'mb-3');

    const header = document.createElement('h6');
    header.classList.add('text-body', 'fw-semibold', 'mb-3');
    header.textContent = 'Localized Product Names';
    wrapper.appendChild(header);

    otherLocalizations.forEach(localization => {
      const block = document.createElement('div');
      block.classList.add('mb-3');

      const label = document.createElement('label');
      label.classList.add('form-label', 'fw-medium');
      label.textContent = `(${localization.locale.toUpperCase()})`;

      const input = document.createElement('input');
      input.type = 'text';
      input.name = 'localization_titles[]';
      input.dataset.locale = localization.locale;
      input.dataset.locId = localization.id;
      input.classList.add('form-control', 'localization-title-input');
      input.value = localization.title || '';
      input.placeholder = `Enter product name in ${localization.locale}`;

      block.appendChild(label);
      block.appendChild(input);
      wrapper.appendChild(block);
    });

    container.appendChild(wrapper);
  }

  function renderLocalizationsTags(localizations) {
    const container = document.querySelector('.tags-show .row');
    container.innerHTML = ''; // clear old

    // helper to build one block (Franchise, Genre, SEO)
    function buildBlock(title, field) {
      const block = document.createElement('div');
      block.classList.add('col-md-12', 'mb-4');

      let html = `<label class="form-label">${title}</label><div class="row">`;

      localizations.forEach(loc => {
        html += `
                <div class="col-md-4 mb-3">
                    <label class="form-label">${loc.locale.toUpperCase()}</label>

                    <!-- hidden fields -->
                    <input type="hidden"
                           name="localizations[${loc.locale}][id]"
                           value="${loc.id}" />
                    <input type="hidden"
                           name="localizations[${loc.locale}][locale]"
                           value="${loc.locale}" />

                    <input id="${field}_${loc.locale}"
                           name="localizations[${loc.locale}][${field}]"
                           class="form-control h-auto"
                           placeholder="Add ${title.toLowerCase()}" />
                </div>
            `;
      });

      html += `</div>`;
      block.innerHTML = html;
      container.appendChild(block);

      // Init Tagify on each textarea
      localizations.forEach(loc => {
        const el = document.querySelector(`#${field}_${loc.locale}`);
        const tagify = new Tagify(el, {
          enforceWhitelist: false,
          dropdown: {
            enabled: 0 // no suggestions, free typing
          }
        });

        // Preload existing tags
        if (loc[field]) {
          tagify.addTags(loc[field].split(','));
        }
      });
    }

    // Build sections
    buildBlock('Genre Tags', 'genre_tags');
    buildBlock('Franchise Tags', 'franchise_tags');
    buildBlock('Community Tags', 'community_tags');
    // buildBlock('SEO Tags', 'seo_tags');
  }

  function renderSeoTags(localizations) {
    const container = document.querySelector('.seo-tags-show .row');
    container.innerHTML = ''; // clear old

    // helper to build one block (Franchise, Genre, SEO)
    function buildBlock(title, field) {
      const block = document.createElement('div');
      block.classList.add('col-md-12', 'mb-4');

      let html = `<label class="form-label"></label><div class="row">`;

      localizations.forEach(loc => {
        html += `
                <div class="col-md-12 mb-3">
                    <label class="form-label">${loc.locale.toUpperCase()}</label>

                    <!-- hidden fields -->
                    <input type="hidden"
                           name="localizations[${loc.locale}][id]"
                           value="${loc.id}" />
                    <input type="hidden"
                           name="localizations[${loc.locale}][locale]"
                           value="${loc.locale}" />

                    <input id="${field}_${loc.locale}"
                           name="localizations[${loc.locale}][${field}]"
                           class="form-control h-auto"
                           placeholder="Add ${title.toLowerCase()}" />
                </div>
            `;
      });

      html += `</div>`;
      block.innerHTML = html;
      container.appendChild(block);

      // Init Tagify on each textarea
      localizations.forEach(loc => {
        const el = document.querySelector(`#${field}_${loc.locale}`);
        const tagify = new Tagify(el, {
          enforceWhitelist: false,
          dropdown: {
            enabled: 0 // no suggestions, free typing
          }
        });

        // Preload existing tags
        if (loc[field]) {
          tagify.addTags(loc[field].split(','));
        }
      });
    }


    buildBlock('SEO Tags', 'seo_tags');
  }

  function renderSupportedLanguages(localizations) {
    const container = document.querySelector('.supported-languages .row');
    container.innerHTML = ''; // clear old content

    localizations.forEach((loc) => {
      const {locale, supported_languages} = loc;

      // Normalize key variations
      const interfaceLangs =
        supported_languages.interface ||
        supported_languages.interfaz ||
        [];
      const fullAudioLangs =
        supported_languages.full_audio ||
        supported_languages.audio_completo ||
        supported_languages['Áudio_completo'] ||
        [];
      const subtitlesLangs =
        supported_languages.subtitles ||
        supported_languages.subtítulos ||
        supported_languages.legendas ||
        [];

      // Build block HTML
      const block = document.createElement('div');
      block.classList.add('col-md-12', 'mb-4');
      block.innerHTML = `
      <label class="form-label">${locale.toUpperCase()}</label>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Interface</label>
          <input id="interface_${locale}"
           class="form-control h-auto"
               name="supported_languages[${locale}][interface]"
           />
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Full Audio</label>
          <input id="full_audio_${locale}" class="form-control h-auto"
           name="supported_languages[${locale}][full_audio]" />
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Subtitles</label>
          <input id="subtitles_${locale}" class="form-control h-auto"
           name="supported_languages[${locale}][subtitles]" />
        </div>
      </div>
    `;
      container.appendChild(block);

      // Wait for DOM to update
      setTimeout(() => {
        const tagifyOptions = {
          readonly: false,
          userInput: true,

        };

        const iEl = document.querySelector(`#interface_${locale}`);
        const fEl = document.querySelector(`#full_audio_${locale}`);
        const sEl = document.querySelector(`#subtitles_${locale}`);

        const interfaceTagify = new Tagify(iEl, tagifyOptions);
        interfaceTagify.addTags(interfaceLangs);

        const fullAudioTagify = new Tagify(fEl, tagifyOptions);
        fullAudioTagify.addTags(fullAudioLangs);

        const subtitlesTagify = new Tagify(sEl, tagifyOptions);
        subtitlesTagify.addTags(subtitlesLangs);
      }, 0);
    });
  }


  window.addMediaBlock = function (media = {}) {
    const index = $('#mediaContainer .media-block').length;
    const template = $('#mediaTemplate').html().replace(/__INDEX__/g, index);
    const $block = $(template);
    const media_source = media?.media_source ?? '';
    const image_orientation = media?.image_orientation ?? '';
    const media_source_name = media_source == '1' ? 'Ztorm' :
      (media_source == '2' ? 'InComm' :
        (media_source == '3' ? 'Manual' :
          (media_source == '4' ? 'Steam' : '')))

    const $imgPreviewLink = $block.find('.img-preview-link');



    // Store data on the element for easy access
    $block.data('index', index);
    if (media.url && (media.type === 'image' || media.type==='boxshot' || media.type==='screenshot')) {

      if ($imgPreviewLink.length) {
        $imgPreviewLink.attr('href', media.url);
        // $imgPreviewLink.show(); // show the link if it was hidden
      }
      const sourceId = Number($('#source-id-get').val() ?? 0);

      if ((sourceId===1 || sourceId===3 || sourceId===4) && (media_source===1 || media_source===4 )){
        if (media.url.includes('_full')) {
          media.url = media.url.replace('_full', '_thumb');
        }

      }
      $block.data('image-url', media.url);
    }


    // Set values from data
    $block.find('.image_orientation').val(image_orientation || 0);
    $block.find(`input[name="media[${index}][id]"]`).val(media.id || '');
    $block.find('.media-type').val(media.type || 'videos');
    $block.find('.mainMediaCheckbox').prop('checked', media.is_main == 1);
    $block.find('.source-media-text').text(media_source_name);
    // Assuming $block is already defined

    if (!media_source_name) {
      $block.find('.source-media-label').hide();
    } else {
      $block.find('.source-media-label').show();
    }


    $('#mediaContainer').append($block);

    // Populate the dynamic input area
    updateInputVisibility($block);

    // If it's a video, we need to set its URL value after the input is created
    if (media.url && (media.type === 'videos' || media.type === 'videos_steam')) {
      $block.find(`input[name="media[${index}][url]"]`).val(media.url);
    }
  }

  // Initial load
  window.loadProductData();

  function updateInputVisibility($block) {
    const index = $block.data('index');
    const type = $block.find('.media-type').val();
    const $container = $block.find('.media-input-container');


    const imagPreviewLink = $block.find('.img-preview-link');
    const currentHref = imagPreviewLink.attr('href');  // gets the current href

    const mediaId = $block.find(`input[name="media[${index}][id]"]`).val();
    const imageUrl = $block.data('image-url') || '';

    $container.empty(); // Clear previous content

    if (type === 'boxshot') {
      $block.find('.image_orientation-container').show();
    } else {
      $block.find('.image_orientation-container').hide();
    }

    if (type === 'image' || type==='boxshot' || type==='screenshot') {
      // If it's an existing image from the database
      if (mediaId && imageUrl) {
        $container.html(`
                    <label class="form-label">Current Image</label>
                    <div>
                               <a href="${currentHref}" class="img-preview-link" target="_blank" >
    <img class="img-preview m-2" src="${imageUrl}" style="max-width:120px;">
  </a>

                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary replace-image-btn">Replace</button>
                    <div class="file-input-wrapper d-none mt-2">
                        <label class="form-label">New Image File</label>
                        <input type="file" name="media[${index}][file]" class="form-control media-file">
                        <div class="invalid-feedback"></div>
                    </div>
                `);
      } else { // It's a new image block
        $container.html(`
                    <label class="form-label">Select Image File</label>
                    <input type="file" name="media[${index}][file]" class="form-control media-file">
                    <div class="invalid-feedback"></div>
                `);
      }
    } else { // 'videos' or 'videos_steam'
      $container.html(`
                <label class="form-label">Video URL</label>
                <input type="url" name="media[${index}][url]" class="form-control" value="">
                <div class="invalid-feedback"></div>
            `);
    }
  }

  function renderLegalTexts(legal_texts_localizations = []) {


    // --- REVISED legal_texts LOGIC ---
    const $legalTextsContainer = $('#legalTextsContainer');
    $legalTextsContainer.empty(); // Clear existing blocks

    if (legal_texts_localizations && legal_texts_localizations.length > 0) {
      legal_texts_localizations.forEach(loc => {
        const $clone = $('#legalTextsTemplate .legalTexts-block').clone();

        $clone.find('input[name="localizations[id][]"]').val(loc.id);
        $clone.find('input[name="localizations[locale][]"]').val(loc.locale);
        $clone.find('label.form-label').text(`Language (${loc.locale || 'N/A'})`);


        // Append the block to the DOM FIRST
        $legalTextsContainer.append($clone);

        // NOW, initialize Quill on the elements within the newly added block
        const system_requirementEditor = $clone.find('[data-name="localizations[legal_texts][]"]')[0];

        // Pass the content directly to the initialization function
        window.initializeQuillInBlock(system_requirementEditor, loc.legal_texts || '');

      });
    }
  }

  function populatePrices(prices = [], skip_updates=[]) {
    const $priceContainer = $('#priceContainer');
    $priceContainer.empty(); // Clear existing content

    if (!prices || prices.length === 0) return;

    // Determine the source from the first price item
    const firstSource = prices[0].source;

    // Create the main table structure
    const $table = $('<table class="table table-sm "></table>');
    $priceContainer.append($table);

    let headerHtml = '';
    if (firstSource == '1' || firstSource == '3' || firstSource == '4') {
      // Source 1 Header
      headerHtml = `
            <thead>
                <tr>
                    <th>Country Code</th>
                    <th>Vat Rate (%)</th>
                    <th>Currency</th>
                    <th>Cost Price</th>
                    <th>Ztorm Price</th>
                    <th>Steam Price</th>
                    <th> In-Stock Price</th>
                     <th>In-Stock Price (€)</th>

                    <th>Discount Price</th>
                    <th>Discount (%)</th>

                </tr>
            </thead>`;
    } else {
      // Source 2 Header
      headerHtml = `
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Vat Rate (%)</th>
                    <th>Title</th>
                    <th>Cost Price</th>
                    <th style="width:150px">Price</th>
                    <th>Discount (%)</th>

                    <th>Actions</th>
                </tr>
            </thead>`;
    }

    $table.append(headerHtml);
    $table.append('<tbody></tbody>');
    const $tbody = $table.find('tbody');

    // Map skip_updates for easy lookup
    const skipMap = (skip_updates || []).reduce((acc, item) => {
      acc[item.field_name] = true;
      return acc;
    }, {});


    prices.forEach(price => {
      let rowHtml = '';

      if (price.source == '1' || price.source == '3' || price.source == '4') {
        // Source 1: Readonly table row
        // Helper function for clearer discount display
        const discountDisplay = (percent, valid_from, valid_to) => {
          if (!percent) return '-';
          return `
            <span class="d-block">${percent ? Number(percent).toFixed(2) : ''}%</span>
            <small class="text-muted d-block">(${valid_from ?? ''} to ${valid_to ?? ''})</small>
          `;
        };

        const is_converted = price?.is_converted ? true : false;
        const rowStyle = is_converted ? 'style="background-color: #fff8b3;"' : ''; // soft yellow


        const isSteamEditable = skipMap['steam_price']; // editable if in skip_updates
        const steamPriceContent = isSteamEditable
          ? `
    <div class="input-group input-group-sm">
        <input type="number" class="form-control steam-price-input" step="any"
               value="${price.steam_price ?? ''}" data-price-id="${price.id}" data-price="${price.steam_price}">
        <button class="btn btn-primary steam-price-submit" title="submit" type="button" data-price-id="${price.id}">
            <i class="menu-icon icon-base ri ri-save-line"></i>
        </button>
    </div>
    `
          : `${price.steam_price ? Number(price.steam_price).toFixed(2) : ''}`;



        rowHtml = `
                <tr ${rowStyle}>
                    <td>${price.country_code ?? ''}</td>
                    <td>${price.vat_rate ?? ''}</td>
                    <td>${price.currency ?? ''}</td>
                    <td>${price.cost_estimate_sourcewise ? Number(price.cost_estimate_sourcewise).toFixed(2) : ''}</td>
                    <td>${price.price ? Number(price.price).toFixed(2) : ''}</td>
                     <td>${steamPriceContent}</td>
                     <td>${price.last_avg_cost ? Number(price.last_avg_cost).toFixed(2) : ''}</td>
                    <td>${price.last_avg_cost_eur ? '€' + Number(price.last_avg_cost_eur).toFixed(2) : ''}</td>
                    <td>${price.discount_amount ? Number(price.discount_amount).toFixed(2) : ''}</td>
                    <td>${discountDisplay(price.discount_percent_raw, price.discount_valid_from_formatted, price.discount_valid_to_formatted)}</td>
                </tr>`;

      } else {


        const priceId = price.id || 'new'; // Assuming prices have an ID for update/delete
        const discountDisplay = (percent, valid_from, valid_to) => {
          if (!percent) return '-';
          return `
            <span class="d-block">${percent ? Number(percent).toFixed(2) : ''}%</span>
            <small class="text-muted d-block">(${valid_from ?? ''} to ${valid_to ?? ''})</small>
          `;
        };


        rowHtml = `
                <tr data-price-id="${priceId}" class="price-row">
           <td class="align-middle ">
  <div class="d-flex flex-column">
    <span><strong>Country:</strong> ${price.country_code ?? ''}</span>
    <span class="text-muted small mt-1"><strong>Currency:</strong> ${price.currency ?? ''}</span>
  </div>
</td>

                    <td class="align-middle">
  ${price.vat_rate ?? ''}
</td>

                    <td class="align-middle">
  ${price.title ?? ''}
</td>

                   <td class="align-middle">
  ${price.cost_estimate_sourcewise ?? ''}
</td>

                   <td class="align-middle">
  ${price.price ?? ''}
</td>

                        <td>${discountDisplay(price.discount_percent_raw, price.discount_valid_from_formatted, price.discount_valid_to_formatted)}</td>

                    <td class="align-middle text-center">
                        <div class="d-flex gap-1 justify-content-center">
 <button
      type="button"
      class="btn btn-primary btn-sm edit-price-btn"
      data-price-id="${priceId}"
      title="Update"
    >
      <i class="ri ri-edit-line me-1"></i> Edit
    </button>

                            <button type="button" class="btn btn-outline-danger btn-sm delete-price-btn"
                             data-price-id="${priceId}" title="Delete">
                               <i class="ri ri-delete-bin-6-line me-1"></i>
                             </button>
                        </div>
                    </td>
                </tr>`;
      }

      $tbody.append(rowHtml);
    });
  }



  // REVISED: Make the initialization function more robust
  window.initializeQuillInBlock = function (editorElement, content = '') {
    // Prevent re-initialization on the same element
    if (!editorElement || editorElement.__quill) {
      return;
    }

    const quill = new Quill(editorElement, {
      bounds: editorElement,
      placeholder: 'Enter description...',
      modules: {toolbar: fullToolbar},
      theme: 'snow'
    });

    // If there's content, paste it into the new editor instance
    if (content) {
      quill.clipboard.dangerouslyPasteHTML(content);
    }

    // Store a reference to the Quill instance on the DOM element
    editorElement.__quill = quill;
  };


  document.getElementById('reloadProductData').addEventListener('click', function () {
    if (window.loadProductData) window.loadProductData();
  });


// === Toggle Translate Buttons Globally ===
  window.toggleTranslateButtons = function () {
    $('#localizationContainer .localization-block').each(function () {
      const $block = $(this);
      const lang = ($block.find('input[name="localizations[language_code][]"]').val() || '')
        .trim()
        .toLowerCase();
      const $btn = $block.find('.translate-btn');

      if (!lang || lang === 'en' || lang.startsWith('en-') || lang.startsWith('en_')) {
        // Hide + disable if empty or English
        $btn.prop('disabled', true).addClass('d-none');
      } else {
        // Show + enable otherwise
        $btn.prop('disabled', false).removeClass('d-none');
      }
    });
  };


  function renderSkipUpdateFields(skip_updates) {
    // Your original Laravel fields list (converted to JS)
    const fields = {
      name: 'Product Name',
      platform: 'Platform',
      product_type: 'Product type',
      publisher_name: 'Publisher Name',
      status: 'Status',
      // region_tag: 'Region tag',
      // download_date: 'Download date',
      release_date: 'Release date',
      // seo_tags: 'Seo tags',
      // genre_tags: 'Genre tags',
      // franchise_tags: 'Franchise tags',
      // community_tags: 'Community tags',
      // system_requirements: 'System requirements',
      // short_description: 'Short description',
      // long_description: 'Long description',
      // supported_languages: 'Supported languages',
      developers: 'Developers',
      // legal_texts: 'Terms and conditions',
      default_language: 'Default language',
      // average_rating: 'Play Purple Index (PPI)',
      pegi_ratings: 'Pegi ratings',
      // total_reviews: 'Total reviews',
      // bundled_products: 'Bundled products',
      // community_discussion: 'Community discussion',
      dlc_products: 'Dlc products',
      dlc_master_product_id: 'Dlc master product id',
      is_dlc: 'Is DLC',
      // face_value: 'Face value',
      product_media: 'Product media',
      steam_price: 'Steam price',
      prices: 'Prices',
      // auxiliary_field: 'Auxiliary field (Incomm)',
      // classification: 'Classification (Incomm)',
      // redemption: 'Redemption (Incomm)',
      // redemption_field: 'Redemption field (Incomm)',
      // validade: 'validade (Incomm)',
    };

    const skipUpdates = (skip_updates || []).map(item => item.field_name);

    const $container = $('#skipUpdateForm .row.g-3');
    $container.empty(); // clear previous fields

    $.each(fields, function (dbField, label) {
      const isChecked = skipUpdates.includes(dbField) ? 'checked' : '';
      const html = `
      <div class="col-6 col-md-4 col-lg-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox"
                 name="skip_fields[]" value="${dbField}"
                 id="skip_${dbField}" ${isChecked}>
          <label class="form-check-label" for="skip_${dbField}">
            ${label}
          </label>
        </div>
      </div>
    `;
      $container.append(html);
    });
  }


});

