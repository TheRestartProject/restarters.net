<?php

return [

    'features' => [
        'discourse_integration' => env('FEATURE__DISCOURSE_INTEGRATION', true)
    ],

    'wiki' => [
        'base_url' => env('WIKI_URL'),
    ],

    'repairdirectory' => [
        'base_url' => env('REPAIRDIRECTORY_URL'),
    ],

    'xref_types' => [
        'networks' => 7,
    ],

];
