<?php

namespace App\Observers;

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

    private function getHost($idevents) {
        $hosts = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.event', $idevents)
            ->where('events_users.role', Role::HOST)
            ->select('users.*')
            ->get();

        return $hosts->count() ? $hosts[0] : null;
    }

    /**
     * @param Party $event
     * @param User $user
     * @return void
     */
    private function confirmed($event, $user): void
    {
        $event->increment('volunteers');

        if ($user && $event->discourse_thread) {
            // We need a host of the event to add the user to the thread.
            // TODO Queue this.
            $host = $this->getHost($event->idevents);

            if ($host) {
                $this->discourseService->addUserToPrivateMessage(
                    $event->discourse_thread,
                    $host->username,
                    $user->username
                );
            }
        }
    }

    /**
     * @param Party $event
     * @param User $user
     * @return void
     */
    private function removed($event, $user): void
    {
        $event->decrement('volunteers');

        if ($user && $event->discourse_thread) {
            // We need a host of the event to remove the user to the thread.
            $host = $this->getHost($event->idevents);

            if ($host) {
                // TODO Queue this.
//                $this->discourseService->addUserToPrivateMessage(
//                    $event->discourse_thread,
//                    $hosts[0]->username,
//                    $user->username
//                );
            }
        }
    }
}
