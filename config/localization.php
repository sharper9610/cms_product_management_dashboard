<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency to Locales Mapping
    |--------------------------------------------------------------------------
    |
    | Define which locales are needed for each currency. 
    | The API will use this mapping to generate "localization_needed".
    |
    */
    'currency_locales' => [
        'EUR' => [
            "en",
            "fr-fr",
            "de-de",
            "it-it",
            "fi-fi",
            "nl-nl",
            "pt-pt",
            "es-419",
            "bg-bg",
            "hr-hr",
            "cs-cz",
            "el-gr",
            "hu-hu",
            "lv-lv",
            "lt-lt",
            "ro-ro",
            "sk-sk",
            "sl-si",
            "et-ee",
            "mt-mt",
            "ga-ie"
        ],

        'USD' => ["en", "es-419"],
        'GBP' => ["en"],
        'CAD' => ["en", "fr-fr"],
        'AUD' => ["en"],
        'NZD' => ["en"],
        'PLN' => ["pl-pl", "en"],
        'CHF' => ["de-de", "fr-fr", "it-it", "en"],
        'NOK' => ["no-no", "en"],
        'SEK' => ["sv-se", "en"],
        'DKK' => ["da-dk", "en"],
        'ZAR' => ["en"],
        'BRL' => ["pt-br"],
        'MXN' => ["es-419"],
        'CLP' => ["es-419"],
        'COP' => ["es-419"],
        'PEN' => ["es-419"],
        'CRC' => ["es-419"],
        'UYU' => ["es-419"],
    ],


];
