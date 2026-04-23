<?php

return [

    'features' => [
        'discourse_integration' => env('FEATURE__DISCOURSE_INTEGRATION', true),
    ],

    'wiki' => [
        'base_url' => env('WIKI_URL'),
        'cookie_prefix' => env('WIKI_COOKIE_PREFIX', 'wiki_db'),
    ],

    'repairdirectory' => [
        'base_url' => env('REPAIRDIRECTORY_URL'),
    ],

    'xref_types' => [
        'networks' => 7,
    ],

    'support_email_address' => env('SUPPORT_EMAIL_ADDRESS'),
];
