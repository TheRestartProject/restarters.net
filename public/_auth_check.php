<?php
// Called as an nginx auth_request subrequest.
// Returns 200 if the site_auth cookie is valid, 401 otherwise.
// No output — nginx only looks at the status code.
$password = getenv('BASIC_AUTH_PASSWORD') ?: 'project';
$secret   = getenv('APP_KEY') ?: 'fallback';
$expected = hash_hmac('sha256', $password, $secret);

http_response_code(
    isset($_COOKIE['site_auth']) && hash_equals($expected, $_COOKIE['site_auth']) ? 200 : 401
);
