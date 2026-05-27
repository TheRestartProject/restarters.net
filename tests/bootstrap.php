<?php

// PHP 8.4 deprecated implicit nullable types (e.g. `Type $p = null`).
// guzzlehttp/promises 1.x uses this pattern in functions.php, which is
// loaded as a Composer autoload file. Suppress E_DEPRECATED only during
// the autoload phase so the deprecations don't reach the test error handler.
$prevReporting = error_reporting(error_reporting() & ~E_DEPRECATED);
require __DIR__ . '/../vendor/autoload.php';
error_reporting($prevReporting);
