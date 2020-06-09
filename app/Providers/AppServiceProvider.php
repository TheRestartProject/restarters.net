<?php

namespace App\Providers;

use App\EventsUsers;

use App\Party;
use Auth;
use Cache;
use App\Helpers\Geocoder;
use FixometerHelper;
use Illuminate\Support\ServiceProvider;
use Schema;
use \OwenIt\Auditing\Models\Audit;

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
                } else {
                    $talk_notifications = FixometerHelper::discourseAPICall('notifications.json', [
                        'api_username' => Auth::user()->username,
                    ]);
                    if (is_object($talk_notifications)) {
                        $total_talk_notifications = 0;
                        foreach ($talk_notifications->notifications as $notification) {
                            if ($notification->read !== true) {
                                $total_talk_notifications++;
                            }
                        }
                        Cache::put('talk_notification_'.Auth::user()->username, $total_talk_notifications, 10);
                    } else {
                        $total_talk_notifications = null;
                    }
                }

                $view->with([
                    // 'notifications' => $notifications->get(),
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
        $this->app->singleton(Geocoder::class, function ($app) {
            return new Geocoder();
        });
    }
}
