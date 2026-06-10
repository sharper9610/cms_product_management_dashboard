/**
 * Page Countries
 */

'use strict';

function initializeCountriesTagify(source) {
  // For Genba (source 4) - whitelist and blacklist
  if (source === 4 || source === '4') {
    // const whitelistEl = document.querySelector('#whitelistCountries');
    // const blacklistEl = document.querySelector('#blacklistCountries');

    // if (whitelistEl) {
    //   new Tagify(whitelistEl);
    // }
    // if (blacklistEl) {
    //   new Tagify(blacklistEl);
    // }


    const allowedCountriesEl = document.querySelector('#allowedCountries');
    if (allowedCountriesEl) {
      new Tagify(allowedCountriesEl);
    }
  } else {
    // For other sources - allowed countries
    const allowedCountriesEl = document.querySelector('#allowedCountries');
    if (allowedCountriesEl) {
      new Tagify(allowedCountriesEl);
    }
  }
}

window.populateCountriesData = function (product) {
  const source = product.source;

  if (source === 4 || source === '4') {
    // Genba: Use whitelist and blacklist from product object
    // const whitelistArr = product.whitelist;
    // const blacklistArr = product.blacklist;

    // const whitelistEl = document.querySelector('#whitelistCountries');
    // const blacklistEl = document.querySelector('#blacklistCountries');

    // // Handle whitelist - check if it's an array
    // if (whitelistEl) {
    //   if (Array.isArray(whitelistArr) && whitelistArr.length > 0) {
    //     whitelistEl.value = whitelistArr.join(', ');
    //   } else {
    //     whitelistEl.value = whitelistArr || '';
    //   }
    // }

    // // Handle blacklist - check if it's an array
    // if (blacklistEl) {
    //   if (Array.isArray(blacklistArr) && blacklistArr.length > 0) {
    //     blacklistEl.value = blacklistArr.join(', ');
    //   } else {
    //     blacklistEl.value = blacklistArr || '';
    //   }
    // }



    const allowedCountriesArr = product.allowed_countries;
    const allowedCountriesEl = document.querySelector('#allowedCountries');

    if (allowedCountriesEl) {
      if (Array.isArray(allowedCountriesArr) && allowedCountriesArr.length > 0) {
        allowedCountriesEl.value = allowedCountriesArr.join(', ');
      } else {
        allowedCountriesEl.value = allowedCountriesArr || '';
      }
    }
  } else {
    // Other sources: Use allowed_countries from product object
    const allowedCountriesArr = product.allowed_countries;
    const allowedCountriesEl = document.querySelector('#allowedCountries');

    if (allowedCountriesEl) {
      if (Array.isArray(allowedCountriesArr) && allowedCountriesArr.length > 0) {
        allowedCountriesEl.value = allowedCountriesArr.join(', ');
      } else {
        allowedCountriesEl.value = allowedCountriesArr || '';
      }
    }
  }

  // Initialize tagify after populating
  initializeCountriesTagify(source);
};
