<?php

return [
    [
        'title' => 'Restarters API',
    ],
    'routes' => [
        'api' => 'apiv2/documentation',
        'docs' => 'docs',

        // We don't use OAuth to log in, but the code wrongly requires this to be set.
        'oauth2_callback' => 'l5-swagger.oauth2_callback',
        'middleware' => [
            'api' => [],
            'asset' => [],
            'docs' => [],
            'oauth2_callback' => [],
        ],
    ],
    'constants' => [
        'L5_SWAGGER_CONST_HOST_TEST' => env('L5_SWAGGER_CONST_HOST_TEST', 'http://restarters.test:8000'),
        'L5_SWAGGER_CONST_HOST_LIVE' => env('L5_SWAGGER_CONST_HOST_LIVE', 'https://restarters.net'),
    ],
];