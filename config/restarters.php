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

    'microtasking' => [
        'discussion_tag' => env('MICROTASKING_DISCUSSION_TAG', 'workbench'),
        'active_quest' => env('MICROTASKING_ACTIVE_QUEST', 'default'),
    ],

    'xref_types' => [
        'networks' => 7,
    ],

];
