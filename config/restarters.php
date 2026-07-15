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

    'xref_types' => [
        'networks' => 7,
    ],

    'support_email_address' => env('SUPPORT_EMAIL_ADDRESS'),

    // Hash that exposes the admin "all events" calendar feed. Read via config
    // (not env() directly in code) so it still resolves when config is cached.
    'calendar_hash' => env('CALENDAR_HASH'),

    // Origin of the Nuxt client app. Used for post-auth redirects (SSO bridge,
    // email deep-link redirectors) and surfaced to the client via /api/v2/session.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    // Per-minute rate limit for login/register/password endpoints (per IP).
    // Test runs raise it via AUTH_RATE_LIMIT to allow Playwright bursts.
    'auth_rate_limit' => env('AUTH_RATE_LIMIT', 10),

    // Client-visible config surfaced through GET /api/v2/session (replaces the
    // env() values previously inlined into Blade layouts).
    'client' => [
        'gtm_id' => env('GOOGLE_TAG_MANAGER_ID'),
        'show_branch_banner' => (bool) env('APP_SHOW_BRANCH', false),
        'branch_label' => env('FLY_APP_NAME'),
        'mailpit_url' => env('MAILPIT_URL'),
        'community_test' => (bool) env('APP_SHOW_COMMUNITY_TEST', false),
    ],
];
