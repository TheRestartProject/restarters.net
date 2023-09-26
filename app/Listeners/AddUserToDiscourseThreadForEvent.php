<?php

namespace App\Listeners;

use App\Events\UserConfirmedEvent;
use App\Party;
use App\Role;
use App\Services\DiscourseService;
use App\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddUserToDiscourseThreadForEvent implements ShouldQueue {
    private $discourseService;

    public function __construct(DiscourseService $discourseService)
    {
        $this->discourseService = $discourseService;
    }

    private function getHost($idevents) {
        $hosts = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.event', $idevents)
            ->where('events_users.role', Role::HOST)
            ->select('users.*')
            ->get();

        return $hosts->count() ? $hosts[0] : null;
    }

    public function handle(UserConfirmedEvent $e) {
        if ($e->iduser) {
            $event = Party::find($e->idevents);
            $user = User::find($e->iduser);

            // Might not exist - timing windows.
            if ($event && $user && $event->discourse_thread) {
                // We need a host of the event to add the user to the thread.
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
    }
}