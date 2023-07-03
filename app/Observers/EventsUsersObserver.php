<?php

namespace App\Observers;

use App\Events\UserConfirmedEvent;
use App\Events\UserLeftEvent;
use App\EventsUsers;
use App\Role;
use App\Services\DiscourseService;
use App\User;

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
     *
     * @param  \App\EventsUsers  $eu
     * @return void
     */
    public function created(EventsUsers $eu)
    {
        $idevents = $eu->event;
        $event = \App\Party::find($idevents);
        $iduser = $eu->user;
        $user = $iduser ? User::find($iduser) : null;
        
        if ($eu->status == 1) {
            // Confirmed.  Make sure they are on the thread.
            $this->confirmed($event, $user);
        } else {
            // Not confirmed.  Make sure they are not on the thread.
            $this->removed($event, $user);
        }
    }

    /**
     * Listen to the updated event.
     *
     * @param  \App\EventsUsers  $eu
     * @return void
     */
    public function updated(EventsUsers $eu) {
        $idevents = $eu->event;
        $event = \App\Party::find($idevents);
        $iduser = $eu->user;
        $user = $iduser ? User::find($iduser) : null;

        if ($eu->status == 1) {
            // Confirmed.  Make sure they are on the thread.
            $this->confirmed($event, $user);
        } else {
            // Not confirmed.  Make sure they are not on the thread.
            $this->removed($event, $user);
        }
    }

    /**
     * Listen to the deleted event.
     *
     * @param  \App\EventsUsers  $eu
     * @return void
     */
    public function deleted(EventsUsers $eu)
    {
        $idevents = $eu->event;
        $event = \App\Party::find($idevents);
        $iduser = $eu->user;
        $user = $iduser ? User::find($iduser) : null;

        // Make sure they are not on the thread.
        $this->removed($event, $user);
    }

    /**
     * @param Party $event
     * @param User $user
     * @return void
     */
    private function confirmed($event, $user): void
    {
        $event->increment('volunteers');
        event(new UserConfirmedEvent($event->idevents, $user ? $user->id : null));
    }

    /**
     * @param Party $event
     * @param User $user
     * @return void
     */
    private function removed($event, $user): void
    {
        $event->decrement('volunteers');
        event(new UserLeftEvent($event->idevents, $user ? $user->id : null));
    }
}
