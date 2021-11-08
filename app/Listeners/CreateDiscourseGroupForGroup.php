<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\EventsUsers;
use App\Party;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class CreateDiscourseGroupForGroup
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
     * @param  ApproveGroup  $event
     * @return void
     */
    public function handle(ApproveGroup $event)
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $group = $event->group;
        $group->createDiscourseGroup();
    }
}
