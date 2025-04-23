<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\Models\EventsUsers;
use App\Models\Party;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class CreateDiscourseGroupForGroup extends BaseEvent
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
     */
    public function handle(ApproveGroup $event): void
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $group = $event->group;

        if ($group->archived_at) {
            // Suppress notifications for archived groups.
            return;
        }

        $group->createDiscourseGroup();
    }
}
