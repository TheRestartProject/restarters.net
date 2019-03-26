<?php

namespace App\Http\Middleware;

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
    public function handle($request, Closure $next)
    {
        if ( ! empty($request->session()->get('groups') || ! empty($request->session()->get('events')))) {
            $request->session()->put('invites-feedback');
        } else {
            $request->session()->forget('invites-feedback');
        }

        if ( ! empty($request->session()->get('groups'))) {
            foreach ($request->session()->get('groups') as $group_code => $hashs) {
                foreach ($hashs as $hash) {
                    if ( ! is_null($hash)) {
                        $acceptance = Invite::where('hash', $hash)->first();
                        if ( ! empty($acceptance) && $acceptance->type == 'group') {
                            UserGroups::updateOrCreate([
                                'user' => auth()->id(),
                                'group' => $acceptance->record_id,
                                'status' => 1,
                                'role' => 4,
                            ]);
                            $acceptance->delete();
                        }
                    }
                }
            }

            $count = count($hashs);
            $request->session()->push('invites-feedback', 'You have accepted '.$count.' group '.str_plural('invite', $count));
            $request->session()->forget('groups');
        }

        if ( ! empty($request->session()->get('events'))) {
            foreach ($request->session()->get('events') as $event_code => $hashs) {
                foreach ($hashs as $hash) {
                    if ( ! is_null($hash)) {
                        $acceptance = Invite::where('hash', $hash)->first();
                        if ( ! empty($acceptance) && $acceptance->type == 'event') {
                            EventsUsers::updateOrCreate([
                                'user' => auth()->id(),
                                'event' => $acceptance->record_id,
                                'status' => 1,
                                'role' => 4,
                            ]);
                            $acceptance->delete();
                        }
                    }
                }
            }

            $count = count($hashs);
            $request->session()->push('invites-feedback', 'You have accepted '.$count.' event '.str_plural('invite', $count));
            $request->session()->forget('events');
        }

        return $next($request);
    }
}
