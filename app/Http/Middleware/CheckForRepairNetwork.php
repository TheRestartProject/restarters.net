<?php

namespace App\Http\Middleware;

use App;
use Auth;
use Closure;
use LaravelLocalization;

class CheckForRepairNetwork
{
    /**
     * Middleware that looks at the incoming host and determines their experience
     * on the website
     * @author Dean Appleton-Claydon
     * @date   2019-03-20
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $update_user = [];

        // Assumed Restart Project
        switch ($host) {
            case 'repairshare.restarters':
                $locale = 'fr';
                $repair_network = 2;

                break;
        }

        // We don't want to override locale if a session already exists
        if (isset($locale) && ! session('locale')) {
            // Update various locale methods/sessions etc
            session()->put('locale', $locale);
            App::setLocale($locale);
            LaravelLocalization::setLocale($locale);

            $update_user['language'] = $locale;
        }

        if (isset($repair_network)) {
            session()->put('repair_network', $repair_network);

            $update_user['repair_network'] = $repair_network;
        }

        if (Auth::check() && ! empty($update_user)) {
            Auth::user()->update($update_user);
        }

        return $next($request);
    }
}
