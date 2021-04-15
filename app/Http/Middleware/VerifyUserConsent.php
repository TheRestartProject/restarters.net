<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class VerifyUserConsent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasUserGivenConsent()) {
            return $next($request);
        }

        return redirect('/user/register');
    }
}
