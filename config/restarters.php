<?php

return [

    'features' => [
        'discourse_integration' => env('FEATURE__DISCOURSE_INTEGRATION', true) && !empty(env('DISCOURSE_URL')),
        // Disabled on preview/staging apps, which share the production image bucket.
        'image_upload' => env('FEATURE__IMAGE_UPLOAD', true),
        'wordpress_integration' => env('FEATURE__WORDPRESS_INTEGRATION', true) && !empty(env('WP_XMLRPC_ENDPOINT')),
    ],

    'wiki' => [
        'base_url' => env('WIKI_URL'),
        'cookie_prefix' => env('WIKI_COOKIE_PREFIX', 'wiki_db'),
    ],

    'repairdirectory' => [
        'base_url' => env('REPAIRDIRECTORY_URL'),
    ],

    'carto' => [
        // CARTO's raster basemaps need an API key; without one the tiles come
        // back watermarked. The key is served to the browser, so it isn't a
        // secret in the usual sense, but keeping it in config means it stays
        // out of the repo and can differ per environment.
        'api_key' => env('CARTO_API_KEY'),
    ],

    'xref_types' => [
        'networks' => 7,
    ],

    'support_email_address' => env('SUPPORT_EMAIL_ADDRESS'),
];
