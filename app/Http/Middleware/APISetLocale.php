<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Auth\Middleware\Authenticate;

/**
 * This can be used by a client to set the locale for a request.  An example is when an API user wants to return
 * country names in a particular language.
 */
class APISetLocale extends Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if ($request->has('locale')) {
            $locale = $request->input('locale');
            app()->setLocale($locale);
        }

        return parent::handle($request, $next, ...$guards);
    }
}