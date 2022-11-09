<?php

namespace App\Http\Middleware;

use App;
use App\Network;
use Auth;
use Closure;
use Illuminate\Support\Str;
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

        // Restart network is the default.
        if (Str::contains($host, 'repairshare')) {
            $networkQuery = Network::where('shortname', 'repairshare');
        } elseif (Str::contains($host, 'repairtogether')) {
            $networkQuery = Network::where('shortname', 'repairtogether');
        } elseif (Str::contains($host, 'hauts-de-france')) {
            $networkQuery = Network::where('shortname', 'hauts-de-france');
        } elseif (Str::contains($host, 'test-restarters')) {
            $networkQuery = Network::where('shortname', 'test');
        } else {
            $networkQuery = Network::where('shortname', 'like', 'restarters');
        }

        $network = $networkQuery->first();
        if (empty($network)) {
            throw new \Exception('Could not determine repair network from domain');
        }
        $locale = $network->default_language;
        $repair_network = $network->id;

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
