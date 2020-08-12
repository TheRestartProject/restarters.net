<?php

namespace App\Listeners;

use App\Events\UserUpdated;
use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncUserProperties
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserUpdated  $event
     * @return void
     */
    public function handle(UserUpdated $event)
    {
        if ($event->user->isDirty('email'))
            event(new UserEmailUpdated($event->user));

        if ($event->user->isDirty('language'))
            event(new UserLanguageUpdated($event->user));
    }
}
