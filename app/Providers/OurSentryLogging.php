<?php

namespace App\Providers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class OurSentryLogging extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            if ($e->level == 'error') {
                \Sentry\captureMessage($e->message);
            }
        });
    }
}
