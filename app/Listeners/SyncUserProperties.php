<?php

namespace App\Listeners;

use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use App\Events\UserUpdated;

// Don't extend BaseEvent - we don't want to queue because this breaks tests in a way that isn't obvious and isn't
// worth fixing given how frequently this occurs.
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
        if ($event->user->isDirty('email')) {
            event(new UserEmailUpdated($event->user));
        }

        if ($event->user->isDirty('language')) {
            event(new UserLanguageUpdated($event->user));
        }
    }
}
