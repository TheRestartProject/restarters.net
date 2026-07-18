<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // The login page lives in the SPA; Auth::routes() (and with it the
        // 'login' route name) is gone.
        return $request->expectsJson() ? null : rtrim(config('restarters.frontend_url'), '/').'/login';
    }
}
