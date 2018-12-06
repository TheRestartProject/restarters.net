<?php

namespace App\Http\Middleware;

use Auth;
use App\User;
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
        } else {
            return redirect('/user/register');
        }

        return redirect('/user/register');
    }
}
