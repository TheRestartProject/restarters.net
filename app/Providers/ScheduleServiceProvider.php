<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('language:sync')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('discourse:syncgroups')->everyFifteenMinutes()->withoutOverlapping();
        });
    }

    public function register(): void
    {
    }
}
