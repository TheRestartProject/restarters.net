<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API is consumed cross-origin by (a) the Nuxt SPA and (b) partner
    | sites' browser JavaScript (therestartproject.org stats widgets/share
    | plugins), which is why allowed_origins stays '*'. That is safe here
    | because auth is header-token based and supports_credentials is false:
    | no browser will attach cookies, and a hostile origin cannot read
    | anything a public browser couldn't — it has no way to obtain a user's
    | bearer token. Do NOT set supports_credentials=true together with '*';
    | if credentialed CORS is ever needed, switch to an explicit origin list.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
