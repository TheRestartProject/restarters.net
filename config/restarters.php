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

    'reporting' => [
        // The group reporting dashboard, filtered to one group.  {group} is replaced by the group id, e.g.
        // https://metabase.example.org/public/dashboard/abc?group_id={group}#hide_parameters=group_id
        'group_url' => env('GROUP_REPORTING_URL'),
    ],

    'xref_types' => [
        'networks' => 7,
    ],

    'support_email_address' => env('SUPPORT_EMAIL_ADDRESS'),

    // Answer geocoding from a fixed table instead of calling Google. Set by CI
    // when no API key is available (forked pull requests get no project
    // environment variables), never in production.
    'geocoder_stub' => env('GEOCODER_STUB', false),
];
