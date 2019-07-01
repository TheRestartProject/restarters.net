<?php
namespace wouterNL\Drip;

use Illuminate\Support\ServiceProvider;

class DripServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('Drip', function($app) {
            return new DripPhp();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/drip.php' => config_path('drip.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/config/drip.php', 'drip'
        );
    }

    public function provides()
    {
        return [
            'Drip',
        ];
    }
}
