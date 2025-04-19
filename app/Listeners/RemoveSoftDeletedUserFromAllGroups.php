<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use App\UserGroups;

class RemoveSoftDeletedUserFromAllGroups extends BaseEvent
{
    /**
     * @param UserDeleted $event
     */
    public function handle(UserDeleted $event): void
    {
        UserGroups::where('user', $event->user->id)->delete();
    }
}
