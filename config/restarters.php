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
        // The group reporting dashboard, filtered to one group, one per language - Metabase can only show a
        // dashboard in another language as a separate dashboard.  {group} is replaced by the group id and
        // {group_name} by its name, e.g.
        // https://metabase.example.org/public/dashboard/abc?group_id={group}&group_name={group_name}#hide_parameters=group_id%2Cgroup_name
        // A group gets the dashboard for its network's language, or English if there isn't one.
        'group_urls' => [
            'en' => env('GROUP_REPORTING_URL_EN'),
            'fr' => env('GROUP_REPORTING_URL_FR'),
        ],
        // Further reports on all the repair data, linked from the Fixometer page.  No link if unset.
        'fixometer_url' => env('FIXOMETER_REPORTING_URL'),
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

    // Answer geocoding from a fixed table instead of calling Google. Set by CI
    // when no API key is available (forked pull requests get no project
    // environment variables), never in production.
    'geocoder_stub' => env('GEOCODER_STUB', false),
];
