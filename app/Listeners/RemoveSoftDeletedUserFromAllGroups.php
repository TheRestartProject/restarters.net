<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use App\Models\UserGroups;

class RemoveSoftDeletedUserFromAllGroups extends BaseEvent
{
    public function handle(UserDeleted $event): void
    {
        UserGroups::where('user', $event->user->id)->delete();
    }
}
