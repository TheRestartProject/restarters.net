<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;
use View;

class AgentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $agent = new Agent();

        View::share('agent', $agent);
    }

    public function register()
    {
        //
    }
}
