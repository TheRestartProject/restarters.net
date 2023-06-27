<?php

namespace App\Listeners;

use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use App\Events\UserUpdated;

class SyncUserProperties extends BaseEvent
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
        if ($event->user->isDirty('email')) {
            event(new UserEmailUpdated($event->user));
        }

        if ($event->user->isDirty('language')) {
            event(new UserLanguageUpdated($event->user));
        }
    }
}
