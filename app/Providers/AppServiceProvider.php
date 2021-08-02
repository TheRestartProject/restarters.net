<?php

namespace App\Providers;

use App\EventsUsers;
use App\Helpers\Geocoder;
use App\Party;
use Auth;
use Cache;
use App\Helpers\Fixometer;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Models\Audit;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
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

        view()->composer('layouts.header', function ($view) {
            if (Auth::check()) {
                if (Cache::has('talk_notification_'.Auth::user()->username)) {
                    $total_talk_notifications = Cache::get('talk_notification_'.Auth::user()->username);
                } elseif (! config('restarters.features.discourse_integration')) {
                    // If we don't have Discourse integration, we will still render the badge, but always have no
                    // notifications.
                    $total_talk_notifications = 0;
                } else {
                    $client = app('discourse-client');
                    $response = $client->request('GET', '/notifications.json?username='.Auth::user()->username);
                    $talk_notifications = json_decode($response->getBody()->getContents(), true);

                    if (! empty($talk_notifications) && array_key_exists('notifications', $talk_notifications)) {
                        $total_talk_notifications = 0;
                        foreach ($talk_notifications['notifications'] as $notification) {
                            if ($notification['read'] !== true) {
                                $total_talk_notifications++;
                            }
                        }
                        Cache::put('talk_notification_'.Auth::user()->username, $total_talk_notifications, 600);
                    } else {
                        $total_talk_notifications = null;
                    }
                }

                $view->with([
                    'total_talk_notifications' => $total_talk_notifications,
                ]);
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Geocoder::class, function () {
            return new Geocoder();
        });
    }
}
