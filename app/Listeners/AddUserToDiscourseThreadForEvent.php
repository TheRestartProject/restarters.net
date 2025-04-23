<?php

namespace App\Listeners;

use App\Events\UserConfirmedEvent;
use App\Models\Party;
use App\Models\Role;
use App\Services\DiscourseService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\InteractsWithQueue;

class AddUserToDiscourseThreadForEvent implements ShouldQueue {
    private $discourseService;
    public $tries = 1;

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

    public function handle(UserConfirmedEvent $e): void {
        // This call can block for a long time - add our own timeout so that we can fail it rather than block
        // the whole queue.
        pcntl_signal(SIGALRM, function () {
            $this->fail();
        });

        pcntl_alarm(10);

        if ($e->iduser) {
            $event = Party::find($e->idevents);
            $user = User::find($e->iduser);

            if ($event->theGroup->archived_at) {
                // Suppress notifications for archived groups.
                return;
            }

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

        pcntl_alarm(0);
    }
}