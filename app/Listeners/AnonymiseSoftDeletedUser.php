<?php

namespace App\Listeners;

use App\Events\UserDeleted;

class AnonymiseSoftDeletedUser extends BaseEvent
{
    /**
     * @param UserDeleted $event
     */
    public function handle(UserDeleted $event)
    {
        $event->user->anonymise()->save();
    }
}
