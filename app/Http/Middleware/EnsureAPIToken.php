<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\User;
use Auth;
use Closure;

class EnsureAPIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()) {
            // We want to ensure that every user has an API token.  This is because the Vue client's use of the API
            // currently relies on that authentication.  Longer term we should probably move to Laravel Passport.
            $token = Auth::user()->ensureAPIToken();

            // Return the API token as a cookie.  This means it can be picked up by the Vue client.
            $response = $next($request);

            if (method_exists($response, 'withCookie')) {
                $response->withCookie(cookie()->forever('restarters_apitoken', $token, null, null, false, false));
            }

            return $response;
        } else {
            return $next($request);
        }
    }
}
