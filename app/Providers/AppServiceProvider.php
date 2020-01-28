<?php

namespace App\Providers;

use App\EventsUsers;

use App\Party;
use Auth;
use Cache;
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

        view()->composer('*', function ($view) {
            if (Auth::check()) {

                // $notifications = Party::whereDate('events.event_date', '<', date('Y-m-d'))
                //                   ->whereDate('events.event_date', '>=', date("Y-m-d", strtotime("-6 months")))
                //                     ->leftjoin('devices', 'devices.event', '=', 'events.idevents')
                //                       ->join('groups', 'events.group', '=', 'idgroups')
                //                         ->whereNull('devices.iddevices')
                //                           ->select('events.*', 'groups.name', 'groups.idgroups')
                //                             ->orderBy('events.event_date', 'DESC');
                //
                // if (!FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
                //     $event_ids = EventsUsers::where('user', Auth::id())->where('role', 3)->pluck('event')->toArray();
                //     $notifications = $notifications->whereIn('idevents', $event_ids);
                // }

                /**
                 * Query Discourse API for current logged in user
                 */
                if (Cache::has('talk_notification_'.Auth::user()->username)) {
                    $total_talk_notifications = Cache::get('talk_notification_'.Auth::user()->username);
                } else {
                    $talk_notifications = FixometerHelper::discourseAPICall('notifications.json', [
                        // 'offset' => '60',
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
    }
}
