<?php

namespace App\Listeners;

use Carbon\Carbon;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

// Don't extend BaseEvent - we don't want to queue because this needs to happen before we return to the client.
class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        $user->last_login_at = Carbon::now()->toDateTimeString();
        $user->number_of_logins += 1;

        $user->save();
    }
}
