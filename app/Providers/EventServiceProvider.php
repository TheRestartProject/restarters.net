<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // 'Illuminate\Auth\Events\Login' => [
        //   'App\Listeners\LogSuccessfulLogin',
        // ],

        // Notify When Add Event Error Occurs
        'App\Events\AddEventError' => [
          'App\Listeners\NotifyAddEventError',
        ],

        // Notify When Edit Event Error Occurs
        'App\Events\EditEventError' => [
          'App\Listeners\NotifyEditEventError',
        ],

        // Notify When Add Group Error Occurs
        'App\Events\AddGroupError' => [
          'App\Listeners\NotifyAddGroupError',
        ],

        // Notify When Edit Group Error Occurs
        'App\Events\EditGroupError' => [
          'App\Listeners\NotifyEditGroupError',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
