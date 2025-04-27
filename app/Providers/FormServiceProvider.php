<?php

namespace App\Providers;

use App\Helpers\FormHelper;
use Illuminate\Support\ServiceProvider;
use Spatie\Html\Html;

class FormServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('form', function ($app) {
            return new FormHelper($app->make(Html::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 