<?php

namespace App\Http\Middleware;

use Closure;
use App;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class LangaugeSwitcher
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
        // App::setLocale(Session::has('locale') ? Session::get('locale') : Config::get('app.locale'));

        $locale = $request->cookie('locale', Config::get('app.locale'));
        App::setLocale($locale);
        Carbon::setLocale($locale);


        return $next($request);
    }
}
