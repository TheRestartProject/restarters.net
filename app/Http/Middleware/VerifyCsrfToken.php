<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Tus resumable-upload protocol requests (POST/PATCH/HEAD/DELETE) cannot carry a
        // Laravel CSRF token. This route already sits outside the "web" middleware group
        // (routes/api.php uses the "api" group, which doesn't include CSRF verification
        // at all) - this entry is kept for defense-in-depth in case that ever changes.
        'api/tus',
        'api/tus/*',
    ];
}
