<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Models\Group;
use App\Models\Party;
use App\Models\EventsUsers;
use App\Models\User;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Notifications\EventConfirmed;

class NotifyApprovedEvent extends BaseEvent
{
    /**
     * Handle the event.
     */
    public function handle(ApproveEvent $event): void
    {
        $partyId = $event->party->idevents;

        $theParty = Party::find($partyId);

        if (empty($theParty)) {
            Log::error('Event not found');

            return;
        }

        $group = Group::findOrFail($theParty->group);

        if ($group->archived_at) {
            // Suppress notifications for archived groups.
            return;
        }

        // Only send notifications if the event is in the future.
        // We don't want to send emails to Restarters about past events being added.
        if ($theParty->isUpcoming()) {
            $group_restarters = $group->membersRestarters();

            // If there are restarters against the group
            if ($group_restarters->count()) {
                // Send user a notification and email
                $users = $group_restarters->get();
                Notification::send($users, new NotifyRestartersOfNewEvent([
                    'event_venue' => $theParty->venue,
                    'event_url' => url('/party/view/' . $partyId),
                    'event_group' => $group->name,
                    'event_start' => $theParty->event_date_local . ' ' . $theParty->start_local,
                ]));
            }
        }

        // Notify the person who created it that it has now been approved.
        $eu = EventsUsers::where('event', $partyId)->orderBy('idevents_users')->first();

        if ($eu) {
            $host = User::find($eu->user);

            if ($host) {
                Notification::send($host, new EventConfirmed($theParty));
            }
        }
    }
}

