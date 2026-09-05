<?php

// PHP 8.4 deprecated implicit nullable types (e.g. `Type $p = null`).
// guzzlehttp/promises 1.x uses this pattern in functions.php, which is
// loaded as a Composer autoload file. Suppress E_DEPRECATED only during
// the autoload phase so the deprecations don't reach the test error handler.
$prevReporting = error_reporting(error_reporting() & ~E_DEPRECATED);
require_once __DIR__ . '/../vendor/autoload.php';
error_reporting($prevReporting);

// Clear the config cache to ensure tests run with APP_ENV=testing and not a cached 'local' environment.
// A stale config cache (bootstrap/cache/config.php) produced with APP_ENV=local causes
// the CSRF middleware's runningUnitTests() to return false, breaking all POST tests.
$cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($cachedConfig)) {
    unlink($cachedConfig);
}
