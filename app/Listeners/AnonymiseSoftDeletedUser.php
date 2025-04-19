<?php

namespace App\Listeners;

use App\Events\UserDeleted;

class AnonymiseSoftDeletedUser extends BaseEvent
{
    /**
     * @param UserDeleted $event
     */
    public function handle(UserDeleted $event): void
    {
        $event->user->anonymise()->save();
    }
}
