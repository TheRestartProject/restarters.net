<?php

namespace App\Providers;

use App\EventsUsers;
use App\Helpers\Geocoder;
use App\Helpers\RobustTranslator;
use App\Observers\EventsUsersObserver;
use Auth;
use Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The admin area is unusable without this
        if (app()->isLocal()) {
            error_reporting(E_ALL ^ E_NOTICE);
        }

        Schema::defaultStringLength(191);

        // Don't create Audit entries when nothing that we want to audit has changed.
        // see: https://github.com/owen-it/laravel-auditing/issues/263#issuecomment-330695869
        Audit::creating(function (Audit $model) {
            if (empty($model->old_values) && empty($model->new_values)) {
                return false;
            }
        });

        \Illuminate\Pagination\Paginator::useBootstrapThree();

        EventsUsers::observe(EventsUsersObserver::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Use our own copy of Sanctum's personal_access_tokens migration
        // (database/migrations, guarded with Schema::hasTable) instead of the
        // vendor one. Production had the table created out-of-band without a
        // migrations row, so the unguarded vendor migration re-ran and hit
        // "1050 Table already exists"; the guarded copy is a no-op when the
        // table is already present but still creates it on fresh installs.
        Sanctum::ignoreMigrations();

        $this->app->singleton(Geocoder::class, function () {
            return new Geocoder();
        });

        // Override the existing translator with our own robust one.
        $this->app->extend('translator', function (Translator $translator) {
            $trans = new RobustTranslator($translator->getLoader(), $translator->getLocale());
            $trans->setFallback($translator->getFallback());
            return $trans;
        });

        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }
}
