<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;

/**
 * This can be used by a client to set the locale for a request.  An example is when an API user wants to return
 * country names in a particular language.
 */
class APISetLocale extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        if ($request->has('locale')) {
            $locale = $request->input('locale');
            app()->setLocale($locale);
        }

        return $next($request);
    }
}