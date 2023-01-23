<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'discourse' => [
        // The route's URI that acts as the entrypoint for Discourse to start the SSO process.
        // Used by Discourse to route incoming logins.
        'route' => 'discourse/sso',

        // Secret string used to encrypt/decrypt SSO information,
        // be sure that it is 10 chars or longer
        'secret' => env('DISCOURSE_SECRET'),

        // Disable Discourse from sending welcome message
        'suppress_welcome_message' => 'true',

        // Where the Discourse form lives
        'url' => env('DISCOURSE_URL'),

        // User specific items
        // NOTE: The 'email' & 'external_id' are the only 2 required fields
        'user' => [
            // Check to see if the user has forum access & should be logged in via SSO
            'access' => null,

            // Groups to make sure that the user is part of in a comma-separated string
            // NOTE: Groups cannot have spaces in their names & must already exist in Discourse
            'add_groups' => null,

            // Boolean for user a Discourse admin, leave null to ignore
            'admin' => null,

            // Full path to user's avatar image
            'avatar_url' => null,

            // The avatar is cached, so this triggers an update
            'avatar_force_update' => false,

            // Content of the user's bio
            'bio' => 'biography',

            // Verified email address (see "require_activation" if not verified)
            'email' => 'email',

            // Unique string to the user that will never change
            'external_id' => 'id',

            // Boolean for user a Discourse admin, leave null to ignore
            'moderator' => null,

            // Full name on Discourse if the user is new or
            // if SiteSetting.sso_overrides_name is set
            'name' => 'name',

            // Groups to make sure that the user is *NOT* part of in a comma-separated string
            // NOTE: Groups cannot have spaces in their names & must already exist in Discourse
            // There is not a way to specify the exact list of groups that a user is in, so
            // you may want to send the inverse of the 'add_groups'
            'remove_groups' => null,

            // If the email has not been verified, set this to true
            'require_activation' => false,

            // username on Discourse if the user is new or
            // if SiteSetting.sso_overrides_username is set
            'username' => 'name',
        ],
    ],

];
