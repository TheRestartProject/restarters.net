<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use App\UserGroups;

class RemoveSoftDeletedUserFromAllGroups
{
    /**
     * @param UserDeleted $event
     */
    public function handle(UserDeleted $event)
    {
        UserGroups::where('user', $event->user->id)->delete();
    }
}
