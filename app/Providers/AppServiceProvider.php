<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Auth;
use App\Party;
use App\EventsUsers;
use FixometerHelper;
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
        Schema::defaultStringLength(191);

        view()->composer('*',function($view){

          if( Auth::check() ){

            $notifications = Party::whereDate('events.event_date', '<', date('Y-m-d'))
                                  ->whereDate('events.event_date', '>=', date("Y-m-d", strtotime("-6 months")))
                                    ->leftjoin('devices', 'devices.event', '=', 'events.idevents')
                                      ->join('groups', 'events.group', '=', 'idgroups')
                                        ->whereNull('devices.iddevices')
                                          ->select('events.*', 'groups.name', 'groups.idgroups')
                                            ->orderBy('events.event_date', 'DESC');

            if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') ){
              $event_ids = EventsUsers::where('user', Auth::id())->where('role', 3)->pluck('event')->toArray();
              $notifications = $notifications->whereIn('idevents', $event_ids);
            }

            $view->with('notifications', $notifications->get());

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
        //
    }
}
