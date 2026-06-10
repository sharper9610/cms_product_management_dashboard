<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sources' => [
        'ztorm'  => 1,
        'incomm' => 2,
        'point_nexus' => 3,
        'genba' => 4,
    ],

    'webhook_secret' => env('WEBHOOK_SECRET'),

    'parent_sku_webhook' => [
        'url'   => env('PARENT_SKU_WEBHOOK_URL'),
        'token' => env('PARENT_SKU_WEBHOOK_TOKEN'),
    ],


    // todo: it should replaced with ProductMedia model const
    'mediatype' => [
        'images'  => 1,
        'videos' => 2,
        'videos_steam' => 3,
    ],

    // todo: it should replaced with ProductMedia model const
    'media_sources' => [
        'ztorm' => 1,
        'incomm' => 2,
        'manual' => 3,
        'point_nexus' => 4,
        'genba' => 6,
    ],

    'api' => [
        'password' => env('API_PASSWORD', ''),
        'url' => env('APP_API_URL', ''),
    ],

    'incomm' => [
        'password' => env('INCOMM_API_PASSWORD', ''),
        'endpoint' => env('INCOMM_API_ENDPOINT', ''),
    ],

    'incomm_product' => [
        'password' => env('INCOMM_PRODUCT_API_PASSWORD', ''),
        'endpoint' => env('INCOMM_PRODUCT_API_ENDPOINT', ''),
    ],

    'ztorm' => [
        'password' => env('ZTORM_API_PASSWORD', ''),
        'endpoint' => env('ZTORM_API_ENDPOINT', ''),
    ],

    'point_nexus' => [
        'password' => env('POINT_NEXUS_API_PASSWORD', ''),
        'endpoint' => env('POINT_NEXUS_ENDPOINT', ''),
    ],

    'genba' => [
        'password' => env('GENBA_API_PASSWORD', ''),
        'endpoint' => env('GENBA_ENDPOINT', ''),
    ],
    'storefront' => [
        'password' => env('STOREFRONT_API_PASSWORD'),
    ],

];
