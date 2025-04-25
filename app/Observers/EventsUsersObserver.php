<?php

namespace App\Observers;

use App\Events\UserConfirmedEvent;
use App\Events\UserLeftEvent;
use App\Models\EventsUsers;
use App\Models\Party;
use App\Services\DiscourseService;
use App\Models\User;

/**
 * This class triggers add/removes from the Discourse thread when a user joins/leaves an event.
 * It also maintains the count of volunteers for the event.
 *
 * Class EventsUsersObserver
 * @package App\Observers
 */
class EventsUsersObserver {
    private $discourseService;

    public function __construct(DiscourseService $discourseService)
    {
        $this->discourseService = $discourseService;
    }

    /**
     * Listen to the created event.
     */
    public function created(EventsUsers $eu): void
    {
        $event = $this->getEvent($eu);
        $user = $this->getUser($eu);
        
        if ($eu->status == 1) {
            // Confirmed. Make sure they are on the thread.
            $this->confirmed($event, $user, true);
        } else {
            // Not confirmed. Make sure they are not on the thread.
            $this->removed($event, $user, false);
        }
    }

    /**
     * Listen to the updated event.
     */
    public function updating(EventsUsers $eu): void {
        if ($eu->isDirty('status')) {
            $event = $this->getEvent($eu);
            $user = $this->getUser($eu);

            // The confirmed status has changed, so we need to update the thread.
            if ($eu->status == 1) {
                // Confirmed. Make sure they are on the thread.
                $this->confirmed($event, $user, true);
            } else {
                // Not confirmed. Make sure they are not on the thread.
                $this->removed($event, $user, true);
            }
        }
    }

    /**
     * Listen to the deleted event.
     */
    public function deleted(EventsUsers $eu): void
    {
        $event = $this->getEvent($eu);
        $user = $this->getUser($eu);

        // Make sure they are not on the thread. If they were confirmed, we need to update the volunteer count.
        $this->removed($event, $user, true, $eu->status == 1);
    }

    /**
     * Get Party model from EventsUsers
     */
    private function getEvent(EventsUsers $eu): ?Party
    {
        // Get the party directly or via relationship
        $event = $eu->party;
        if (!$event) {
            $event = Party::where('idevents', $eu->event)->first();
        }
        return $event;
    }

    /**
     * Get User model from EventsUsers
     */
    private function getUser(EventsUsers $eu): ?User
    {
        // Get user via relationship if available or direct query
        if ($eu->userObj) {
            return $eu->userObj;
        } elseif ($eu->user) {
            return User::where('id', $eu->user)->first();
        }
        return null;
    }

    private function confirmed(?Party $event, ?User $user, $count): void
    {
        if ($event && $count) {
            $event->increment('volunteers');
        }

        if ($event) {
            event(new UserConfirmedEvent($event->idevents, $user ? $user->id : null));
        }
    }

    private function removed(?Party $event, ?User $user, $count, $wasConfirmed = false): void
    {
        if ($event && $count && $wasConfirmed) {
            $event->decrement('volunteers');
        }

        if ($event) {
            event(new UserLeftEvent($event->idevents, $user ? $user->id : null));
        }
    }
}
