<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\EventsUsers;
use App\Invite;
use App\UserGroups;
use Closure;
use Illuminate\Support\Facades\Auth;

class AcceptUserInvites
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if there are existing Groups/Events Shareable Invites for the
        // Current User
        if (! empty($request->session()->get('groups') || ! empty($request->session()->get('events')))) {
            $request->session()->put('invites-feedback');
        } else {
            $request->session()->forget('invites-feedback');
        }

        if (! empty($request->session()->get('groups'))) {
            foreach ($request->session()->get('groups') as $hashs) {
                foreach ($hashs as $hash) {
                    $acceptance = Invite::where('hash', $hash)->firstOrFail();
                    $group = $acceptance->group;

                    // If the $acceptance type is a Group
                    // and the User has not already joined.
                    // Accept or Update a record and
                    // delete the Invite and create a new session
                    if ($acceptance->type == 'group' && ! $group->isVolunteer()) {
                        UserGroups::updateOrCreate([
                            'user' => auth()->id(),
                            'group' => $acceptance->record_id,
                            'status' => 1,
                            'role' => 4,
                        ]);
                        $acceptance->delete();
                        $request->session()->push('invites-feedback', __('groups.you_have_joined', [
                            'url' => url("/group/view/{$group->idgroups}"),
                            'name' => $group->name
                        ]));

                    // Else that must mean the User is already part of the Group.
                        // We can then delete the Invite and create a new session
                    } else {
                        $request->session()->push('invites-feedback', 'You are already a member of <a class="plain-link" href='.url("/group/view/{$group->idgroups}").">{$group->name}</a>");
                    }
                }
                $request->session()->forget('groups');
            }
        }

        if (! empty($request->session()->get('events'))) {
            foreach ($request->session()->get('events') as $hashs) {
                foreach ($hashs as $hash) {
                    $acceptance = Invite::where('hash', $hash)->firstOrFail();
                    $event = $acceptance->event;

                    // If the $acceptance type is a Event
                    // and the User has not already joined.
                    // Accept or Update a record and
                    // delete the Invite and create a new session
                    if ($acceptance->type == 'event' && ! $event->isVolunteer()) {
                        EventsUsers::updateOrCreate([
                            'user' => auth()->id(),
                            'event' => $acceptance->record_id,
                            'status' => 1,
                            'role' => 4,
                        ]);
                        $acceptance->delete();
                        $request->session()->push('invites-feedback', __('events.you_have_joined', [
                            'url' => url("/party/view/{$event->idevents}"),
                            'name' => $event->venue
                        ]));

                    // Else that must mean the User is already part of the Event.
                        // We can then delete the Invite and create a new session
                    } else {
                        $request->session()->push('invites-feedback', 'You are already a member of <a class="plain-link" href='.url("/party/view/{$event->idevents}").">{$event->venue}</a>");
                    }
                }
                $request->session()->forget('events');
            }
        }

        return $next($request);
    }
}
