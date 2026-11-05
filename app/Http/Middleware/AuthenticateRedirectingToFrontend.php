<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

/**
 * Authenticate variant used ONLY by the Discourse SSO route (via the
 * services.discourse.middleware config): unauthenticated browsers are sent to
 * the SPA's login page instead of Laravel's /login, carrying the original SSO
 * URL so the client can route back through /auth/bridge after login.
 *
 * Deliberately not wired to the global 'auth' alias — the legacy Blade app
 * keeps its own login redirect until cutover (design §4.3).
 */
class AuthenticateRedirectingToFrontend extends Authenticate
{
    protected function redirectTo($request)
    {
        return config('restarters.frontend_url').'/login?redirect='.urlencode($request->fullUrl());
    }
}
