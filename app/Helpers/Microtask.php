<?php

namespace App\Helpers;

use App;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Session;

class Microtask
{
    /**
     * Creates a user object for a guest user and enables session-based monitoring
     * of user clicks and duration to provide callbacks to controllers so
     * they can execute custom actions, e.g. "UI call to action".
     *
     * Example:
     * 1. Every x clicks, return action = 1
     * 2. Stop counting clicks
     * 3. Once the wait period has elapsed start counting clicks again
     *
     * @see App\Http\Controllers\MisscatController
     *
     * @param Request $request
     * @param int $clicks Number of clicks before action
     * @param int $wait Seconds to wait between actions
     * @return stdClass $user
     */
    public static function getAnonUserCta(Request $request, $clicks = 5, $wait = 900)
    {
        $user = new \stdClass();
        $user->id = 0;
        $user->name = 'Guest';
        $user->action = 0;
        $user = self::initTaskSession($request, $user);
        if (! $user->cta) {
            $request->session()->put('microtask.clicks', ++$user->clicks);
            if ($user->clicks % $clicks == 0) {
                $request->session()->put('microtask.cta', 1);
                $request->session()->put('microtask.sesh', Carbon::now()->timestamp);
                $user->action = 1;
            }
        } else {
            if ((Carbon::now()->timestamp - $user->sesh) > $wait) {
                self::resetTaskSession($request, $user);
            }
        }

        return self::getTaskSession($request, $user);
    }

    protected static function initTaskSession(Request $request, $user)
    {
        $user->clicks = $request->session()->get('microtask.clicks', 0);
        if (! $user->clicks) {
            $request->session()->put('microtask.clicks', $user->clicks);
        }
        $user->cta = $request->session()->get('microtask.cta', 0);
        if (! $user->cta) {
            $request->session()->put('microtask.cta', $user->cta);
        }
        $user->sesh = $request->session()->get('microtask.sesh', 0);
        if (! $user->sesh) {
            $user->sesh = Carbon::now()->timestamp;
            $request->session()->put('microtask.sesh', $user->sesh);
        }

        return $user;
    }

    protected static function getTaskSession(Request $request, $user)
    {
        $user->clicks = $request->session()->get('microtask.clicks', 0);
        $user->cta = $request->session()->get('microtask.cta', 0);
        $user->sesh = $request->session()->get('microtask.sesh', 0);

        return $user;
    }

    protected static function resetTaskSession(Request $request)
    {
        $request->session()->put('microtask.cta', 0);
        $request->session()->put('microtask.clicks', 0);
        $request->session()->put('microtask.sesh', Carbon::now()->timestamp);
    }
}
