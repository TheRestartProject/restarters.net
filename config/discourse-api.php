<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Discourse API base URL.
    |--------------------------------------------------------------------------
    */

    'base_url' => env('DISCOURSE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Discourse API connection parameters.
    |--------------------------------------------------------------------------
    | To become authenticated you will need to create an API Key
    | from the admin panel of Discourse as stated within the docs.
    */

    'api_key' => env('DISCOURSE_APIKEY'),

    'api_username' => env('DISCOURSE_APIUSER', ''),

    'sso_secret' => env('DISCOURSE_SECRET', ''),
];
